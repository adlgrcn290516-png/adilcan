"""Ana döngü (bir tur): Market Data -> Scanner -> Risk -> Portfolio -> Execution -> Verification -> Position Mgmt -> Log.

Backtest/paper/live aynı strateji + risk + execution zincirini kullanır; yalnızca Broker değişir.
"""
from __future__ import annotations

import logging
from dataclasses import dataclass, field
from decimal import Decimal

from app.core.scanner import MarketScanner, Opportunity
from app.exchange.spot import BinanceSpotAdapter
from app.portfolio.manager import ExitEvent, OpenResult, PortfolioManager, Quote
from app.risk.engine import ApiHealth
from app.strategies.base import Signal
from app.utils.retry import RateLimitBanned

log = logging.getLogger(__name__)


@dataclass
class CycleReport:
    opportunities: list[Opportunity] = field(default_factory=list)
    opened: list[OpenResult] = field(default_factory=list)
    skipped: list[tuple[str, str]] = field(default_factory=list)   # (symbol, neden)
    exits: list[ExitEvent] = field(default_factory=list)
    error: str = ""


class Orchestrator:
    def __init__(self, adapter: BinanceSpotAdapter, scanner: MarketScanner, manager: PortfolioManager,
                 health: ApiHealth, *, dry_run: bool = False):
        self.ad, self.scanner, self.mgr, self.health, self.dry_run = adapter, scanner, manager, health, dry_run

    def quotes_for(self, symbols: set[str]) -> dict[str, Quote]:
        out = {}
        for s in symbols:
            ob = self.ad.order_book(s, 5)
            if ob.bids and ob.asks:
                out[s] = Quote(ob.bids[0][0], ob.asks[0][0])
        return out

    def cycle(self, extra: list[Opportunity] | None = None) -> CycleReport:
        rep = CycleReport()
        try:
            rep.opportunities = self.scanner.scan()
        except RateLimitBanned:
            self.mgr.set_emergency(True)  # IP ban: yeni emir yok
            rep.error = "IP ban (418): yeni emirler durduruldu"
            return rep
        except Exception as exc:  # noqa: BLE001
            self.health.error()
            rep.error = f"{type(exc).__name__}: {exc}"
            log.error("tarama başarısız: %s", rep.error)
        opps = list(extra or []) + rep.opportunities
        buys = [o for o in opps if o.signal is Signal.BUY and o.symbol not in self.mgr.positions]
        try:
            quotes = self.quotes_for(set(self.mgr.positions) | {o.symbol for o in buys})
        except RateLimitBanned:
            self.mgr.set_emergency(True)
            rep.error = "IP ban (418): yeni emirler durduruldu"
            return rep
        except Exception as exc:  # noqa: BLE001
            self.health.error()
            rep.error = f"fiyat alınamadı: {exc}"
            return rep
        # 1) mevcut pozisyonları yönet (stop/TP/trailing) — her zaman önce
        rep.exits = self.mgr.manage(quotes)
        # 2) yeni girişler (risk motoru onayıyla)
        for o in sorted(buys, key=lambda o: o.score, reverse=True):
            q = quotes.get(o.symbol)
            if q is None or o.f_obj is None:
                rep.skipped.append((o.symbol, "fiyat/özellik yok"))
                continue
            if self.dry_run:
                v, *_ = self.mgr.preview(o, o.f_obj, q, quotes, self.scanner.returns)
                rep.skipped.append((o.symbol, f"[DRY-RUN] {v.summary()}"))
                continue
            r = self.mgr.try_open(o, o.f_obj, q, quotes, self.scanner.returns)
            (rep.opened if r.position else rep.skipped).append(r if r.position else (o.symbol, r.note or r.verdict.summary()))
        self.mgr.snapshot(quotes)
        if not rep.error:
            self.health.ok()  # yalnızca TAMAMEN hatasız turda sayaç sıfırlanır
        return rep
