"""Sürekli çalışan koşucu (7/24).

* Her `manage_every_s` saniyede açık pozisyonlar yönetilir (stop/TP/trailing) — hafif.
* Her saat başı (bar kapanışı + offset) TAM tur: tarama -> risk -> giriş.
* `data/STOP` dosyası: yeni emir açmayı keser (içinde CLOSE yazarsa tüm pozisyonları da kapatır). Silmek yeniden AÇMAZ.
* Ardışık hata sınırı aşılırsa otomatik acil durdurma (yönetim sürer).
* Her turda durum diske yazılır (paper bakiye) + heartbeat; çökse/yeniden başlasa kaldığı yerden devam eder.
"""
from __future__ import annotations

import json
import logging
import time
from pathlib import Path
from typing import Callable

from app.core.orchestrator import CycleReport, Orchestrator
from app.portfolio.manager import PortfolioManager

log = logging.getLogger(__name__)


class Runner:
    def __init__(self, orch: Orchestrator, mgr: PortfolioManager, data_dir: str | Path, *,
                 save_state: Callable[[], None] | None = None, scan_every_s: int = 3600, scan_offset_s: int = 15,
                 manage_every_s: int = 60, max_failures: int = 10, clock: Callable[[], float] = time.time,
                 sleep: Callable[[float], None] = time.sleep):
        self.orch, self.mgr = orch, mgr
        self.dir = Path(data_dir)
        self.dir.mkdir(parents=True, exist_ok=True)
        self.save_state = save_state or (lambda: None)
        self.scan_every, self.scan_offset, self.manage_every = scan_every_s, scan_offset_s, manage_every_s
        self.max_failures, self._clock, self._sleep = max_failures, clock, sleep
        self.next_scan = 0.0            # ilk tur hemen tam tarama
        self.failures = 0
        self.ticks = 0
        self.last_scan_ts = 0.0
        self.stop_seen = False

    def _next_scan(self, now: float) -> float:
        return (now // self.scan_every + 1) * self.scan_every + self.scan_offset

    def _check_stop(self, quotes_fn=None) -> None:
        f = self.dir / "STOP"
        if f.exists() and not self.stop_seen:
            self.stop_seen = True
            close = "CLOSE" in f.read_text(encoding="utf-8", errors="ignore").upper()
            log.critical("STOP dosyası bulundu -> acil durdurma (tüm pozisyonları kapat=%s)", close)
            quotes = self.orch.quotes_for(set(self.mgr.positions)) if (close and self.mgr.positions) else {}
            self.mgr.set_emergency(True, close_positions=close, quotes=quotes)

    def tick(self) -> CycleReport:
        now = self._clock()
        self._check_stop()
        if now >= self.next_scan:
            rep = self.orch.cycle()
            self.next_scan = self._next_scan(now)
            self.last_scan_ts = now
            kind = "TARAMA"
        else:
            rep = self.orch.manage_only()
            kind = "yönetim"
        self.ticks += 1
        if rep.error:
            log.warning("[%s] %s", kind, rep.error)
        for r in rep.opened:
            p = r.position
            log.info("[%s] AÇILDI %s qty=%s giriş=%s stop=%s", kind, p.symbol, p.qty, p.entry_price, p.stop)
        for e in rep.exits:
            log.info("[%s] ÇIKIŞ %s %s pnl=%s", kind, e.symbol, e.reason, e.pnl)
        return rep

    def heartbeat(self) -> None:
        """Nabız dosyası: fiyat çağrısı YAPMAZ (log kirletmez); nakit + açık pozisyon listesi."""
        try:
            (self.dir / "heartbeat.json").write_text(json.dumps({
                "ts": self._clock(), "ticks": self.ticks, "cash": str(self.mgr.broker.free_balance(self.mgr.quote)),
                "open_positions": sorted(self.mgr.positions), "last_scan_ts": self.last_scan_ts,
                "emergency_stop": self.mgr.risk.emergency_stop, "failures": self.failures}), encoding="utf-8")
        except Exception:  # noqa: BLE001
            pass

    def run(self, max_ticks: int | None = None) -> None:
        log.info("Koşucu başladı (yönetim her %ss, tarama saat başı)", self.manage_every)
        while max_ticks is None or self.ticks < max_ticks:
            t0 = self._clock()
            try:
                self.tick()
                self.failures = 0
            except KeyboardInterrupt:
                log.warning("Kullanıcı durdurdu (Ctrl+C). Pozisyonlar DB'de; yeniden başlatınca devam eder.")
                break
            except Exception as exc:  # noqa: BLE001 — koşucu ASLA çökmesin
                self.failures += 1
                self.ticks += 1
                log.error("tur hatası (%d/%d): %s: %s", self.failures, self.max_failures, type(exc).__name__, exc)
                if self.failures >= self.max_failures and not self.mgr.risk.emergency_stop:
                    log.critical("Ardışık hata sınırı aşıldı -> OTOMATİK ACİL DURDURMA (yeni emir yok)")
                    self.mgr.set_emergency(True)
            try:
                self.save_state()
            except Exception as exc:  # noqa: BLE001
                log.error("durum kaydedilemedi: %s", exc)
            self.heartbeat()
            self._sleep(max(1.0, self.manage_every - (self._clock() - t0)))
