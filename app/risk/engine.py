"""RiskEngine — stratejiden BAĞIMSIZ. Karar: APPROVE / REDUCE / REJECT (+ nedenlerin tamamı).

Giriş (BUY) için kontroller: emergency stop, günlük zarar, drawdown (latch), açık pozisyon sayısı, API sağlığı,
stop zorunluluğu, spread, likidite/derinlik, volatilite, ani hareket (haber/şok) riski, korelasyon, bakiye,
toplam maruziyet, pozisyon büyüklüğü, minimum emir.
"""
from __future__ import annotations

import logging
import math
import time
from dataclasses import dataclass, field
from decimal import Decimal
from enum import Enum
from typing import Callable, Mapping

import pandas as pd

from app.config import RiskCfg
from app.core.features import Features
from app.exchange.models import SymbolInfo
from app.execution.filters import floor_to_step
from app.portfolio.position import Position
from app.risk.sizing import risk_based_qty, size_cap_qty

log = logging.getLogger(__name__)


class Decision(str, Enum):
    APPROVE = "APPROVE"
    REDUCE = "REDUCE"
    REJECT = "REJECT"


@dataclass
class Verdict:
    decision: Decision
    qty: Decimal = Decimal(0)
    requested_qty: Decimal = Decimal(0)
    reasons: list[str] = field(default_factory=list)      # reddedilme/azaltma nedenleri
    checks: list[tuple[str, bool, str]] = field(default_factory=list)  # (kontrol, geçti mi, ayrıntı)

    def summary(self) -> str:
        r = "; ".join(self.reasons) if self.reasons else "tüm kontroller geçti"
        return f"{self.decision.value} qty={self.qty} ({r})"


@dataclass
class PortfolioState:
    equity: Decimal
    free_quote: Decimal
    exposure: Decimal                      # açık pozisyonların toplam notional'ı
    positions: list[Position]
    start_of_day_equity: Decimal
    peak_equity: Decimal


class ApiHealth:
    """Ardışık API hatalarını izler; limit aşılınca yeni emir açılmaz."""

    def __init__(self):
        self.consecutive_errors = 0
        self.last_ok = time.time()

    def ok(self):
        self.consecutive_errors, self.last_ok = 0, time.time()

    def error(self):
        self.consecutive_errors += 1


class RiskEngine:
    def __init__(self, cfg: RiskCfg, health: ApiHealth | None = None, clock: Callable[[], float] = time.time):
        self.cfg, self.health, self._clock = cfg, health or ApiHealth(), clock
        self.emergency_stop = cfg.emergency_stop
        self.drawdown_latched = False   # max drawdown aşılınca KİLİTLENİR; yalnızca elle açılır

    def reset_drawdown_latch(self) -> None:
        log.warning("Drawdown kilidi ELLE kaldırıldı")
        self.drawdown_latched = False

    # ---- portföy düzeyi durum (günlük zarar / drawdown) ----
    def daily_pnl_pct(self, st: PortfolioState) -> float:
        return float((st.equity - st.start_of_day_equity) / st.start_of_day_equity) if st.start_of_day_equity else 0.0

    def drawdown_pct(self, st: PortfolioState) -> float:
        return float((st.peak_equity - st.equity) / st.peak_equity) if st.peak_equity else 0.0

    def status(self, st: PortfolioState) -> dict:
        dd = self.drawdown_pct(st)
        if dd >= self.cfg.max_drawdown_pct:
            self.drawdown_latched = True
        return {
            "emergency_stop": self.emergency_stop,
            "daily_pnl_pct": self.daily_pnl_pct(st), "daily_limit_hit": self.daily_pnl_pct(st) <= -self.cfg.max_daily_loss_pct,
            "drawdown_pct": dd, "drawdown_latched": self.drawdown_latched,
            "open_positions": len(st.positions), "api_errors": self.health.consecutive_errors,
        }

    # ---- ana karar ----
    def evaluate_entry(self, f: Features, entry: Decimal, stop: Decimal | None, info: SymbolInfo, st: PortfolioState,
                       returns: Mapping[str, pd.Series] | None = None) -> Verdict:
        c, chk, why = self.cfg, [], []

        def check(name: str, ok: bool, detail: str, reject_msg: str | None = None) -> bool:
            chk.append((name, ok, detail))
            if not ok and reject_msg:
                why.append(reject_msg)
            return ok

        s = self.status(st)
        check("emergency_stop", not self.emergency_stop, "EMERGENCY_STOP", "EMERGENCY_STOP aktif")
        check("günlük_zarar", not s["daily_limit_hit"], f"günlük {s['daily_pnl_pct'] * 100:.2f}% (limit -{c.max_daily_loss_pct * 100:.1f}%)",
              f"günlük zarar limiti aşıldı ({s['daily_pnl_pct'] * 100:.2f}%)")
        check("drawdown", not self.drawdown_latched, f"drawdown {s['drawdown_pct'] * 100:.2f}% (limit {c.max_drawdown_pct * 100:.1f}%)",
              f"max drawdown aşıldı/kilitli ({s['drawdown_pct'] * 100:.2f}%) — elle sıfırlanmalı")
        check("pozisyon_sayısı", len(st.positions) < c.max_open_positions, f"{len(st.positions)}/{c.max_open_positions}",
              f"açık pozisyon sayısı limitte ({c.max_open_positions})")
        check("api_sağlığı", self.health.consecutive_errors < c.max_api_errors, f"ardışık hata {self.health.consecutive_errors}",
              f"API sorunu: {self.health.consecutive_errors} ardışık hata")
        if any(p.symbol == f.symbol for p in st.positions):
            check("zaten_açık", False, f.symbol, f"{f.symbol} için zaten açık pozisyon var")
        if stop is None or stop >= entry:
            check("stop_zorunlu", False, "stop yok/geçersiz", "geçerli stop-loss olmadan giriş yapılamaz")
        sp = f.spread_pct
        check("spread", math.isnan(sp) or sp <= c.max_spread_pct, f"spread %{sp:.3f} (limit %{c.max_spread_pct})",
              f"spread çok geniş (%{sp:.3f})")
        depth = f.depth_quote_1pct
        check("likidite", math.isnan(depth) or depth >= c.min_depth_quote, f"derinlik {depth:,.0f} (min {c.min_depth_quote:,.0f})",
              f"likidite yetersiz (derinlik {depth:,.0f})")
        check("volatilite", f.atr_pct <= c.max_atr_pct, f"ATR% {f.atr_pct:.2f} (limit {c.max_atr_pct})",
              f"aşırı volatilite (ATR% {f.atr_pct:.2f})")
        shock = not math.isnan(f.last_range_atr) and f.last_range_atr > c.max_candle_atr
        check("ani_hareket", not shock, f"son mum {f.last_range_atr:.1f} ATR (limit {c.max_candle_atr})",
              f"ani hareket/şok riski (son mum {f.last_range_atr:.1f} ATR)")

        if why or stop is None or stop >= entry:
            return self._finish(Decision.REJECT, Decimal(0), Decimal(0), why, chk)

        # ---- boyutlandırma ----
        ideal = risk_based_qty(st.equity, c.max_risk_per_trade_pct, entry, stop)
        target = min(ideal, size_cap_qty(st.equity, c.max_position_size_pct, entry))
        reasons_reduce: list[str] = []
        qty = target

        room = st.equity * Decimal(str(c.max_total_exposure_pct)) - st.exposure
        if room <= 0:
            check("toplam_maruziyet", False, f"maruziyet {st.exposure:.2f} / {st.equity * Decimal(str(c.max_total_exposure_pct)):.2f}",
                  "toplam maruziyet limiti dolu")
            return self._finish(Decision.REJECT, Decimal(0), ideal, why, chk)
        if qty * entry > room:
            qty = room / entry
            reasons_reduce.append("toplam maruziyet limiti")
        check("toplam_maruziyet", True, f"kalan alan {room:.2f}")

        reserve = st.equity * Decimal(str(c.min_cash_reserve_pct))
        spendable = st.free_quote - reserve
        if spendable <= 0:
            check("bakiye", False, f"serbest {st.free_quote:.2f}, rezerv {reserve:.2f}", "kullanılabilir bakiye yok")
            return self._finish(Decision.REJECT, Decimal(0), ideal, why, chk)
        if qty * entry > spendable:
            qty = spendable / entry
            reasons_reduce.append("bakiye")
        check("bakiye", True, f"harcanabilir {spendable:.2f}")

        if not math.isnan(depth) and depth > 0:
            cap = Decimal(str(depth * c.max_order_depth_pct / 100)) / entry
            if qty > cap:
                qty = cap
                reasons_reduce.append(f"derinlik (emir ≤ derinliğin %{c.max_order_depth_pct})")

        mc = self._max_corr(f.symbol, returns, st.positions)
        if mc is not None:
            ok = mc <= c.max_correlation
            check("korelasyon", ok, f"max korelasyon {mc:.2f} (limit {c.max_correlation})")
            if not ok:
                qty *= Decimal(str(c.correlation_reduce))
                reasons_reduce.append(f"yüksek korelasyon ({mc:.2f})")
        else:
            check("korelasyon", True, "veri yok/pozisyon yok")

        qty = floor_to_step(qty, info.step_size)
        if qty < info.min_qty or qty * entry < info.min_notional:
            check("min_emir", False, f"qty {qty} notional {qty * entry:.2f} (min {info.min_notional})",
                  "azaltılmış boyut minimum emir limitinin altında")
            return self._finish(Decision.REJECT, Decimal(0), ideal, why, chk)

        decision = Decision.REDUCE if (reasons_reduce or qty < floor_to_step(target, info.step_size)) else Decision.APPROVE
        return self._finish(decision, qty, ideal, [f"azaltıldı: {r}" for r in reasons_reduce], chk)

    def _max_corr(self, sym: str, returns: Mapping[str, pd.Series] | None, positions: list[Position]) -> float | None:
        if not returns or sym not in returns or not positions:
            return None
        best = None
        for p in positions:
            other = returns.get(p.symbol)
            if other is None:
                continue
            a, b = returns[sym].align(other, join="inner")
            if len(a) >= 30:
                v = a.corr(b)
                if v is not None and not math.isnan(v):
                    best = v if best is None else max(best, v)
        return best

    def _finish(self, d: Decision, qty: Decimal, ideal: Decimal, reasons: list[str], chk) -> Verdict:
        v = Verdict(d, qty, ideal, reasons, chk)
        log.info("RISK %s", v.summary())
        return v
