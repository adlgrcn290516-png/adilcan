"""Strateji arayüzü. Backtest, paper ve live AYNI `evaluate()` fonksiyonunu çağırır."""
from __future__ import annotations

import math
from abc import ABC, abstractmethod
from dataclasses import dataclass, field
from enum import Enum

from app.config import StrategyCfg
from app.core.features import Features


class Signal(str, Enum):
    BUY = "BUY"
    SELL = "SELL"    # Spot: eldeki pozisyonu ÇIKIŞ sinyali (açığa satış değil)
    HOLD = "HOLD"


@dataclass
class SignalResult:
    strategy: str
    signal: Signal
    confidence: float = 0.0            # [0,1]
    entry_price: float | None = None
    stop_loss: float | None = None
    take_profit: float | None = None   # TP1
    take_profit_2: float | None = None
    expected_return: float | None = None  # % ; sezgisel: conf*ödül - (1-conf)*risk (TAHMİN DEĞİL)
    risk_score: float = 50.0           # [0,100]
    reason: str = ""
    details: dict = field(default_factory=dict)   # skor kırılımı, oylar vb. (açıklanabilirlik)

    def validate(self) -> None:
        if self.signal is Signal.BUY:
            assert self.stop_loss is not None and self.entry_price is not None, "BUY için stop zorunlu"
            assert self.stop_loss < self.entry_price, "stop entry'nin altında olmalı"
            assert self.take_profit is None or self.take_profit > self.entry_price


def risk_score(f: Features) -> float:
    """0-100, yüksek = riskli. Volatilite, spread, likidite, aşırı RSI."""
    r = 0.0
    r += min(40.0, (f.atr_pct / 5.0) * 40.0)                                       # ATR% 5 -> 40 puan
    if not math.isnan(f.spread_pct):
        r += min(25.0, f.spread_pct / 0.10 * 25.0)                                  # %0.10 spread -> 25
    if not math.isnan(f.depth_quote_1pct):
        r += 20.0 * (1 - min(1.0, math.log10(max(f.depth_quote_1pct, 1)) / 6.0))    # 1M$ derinlik -> 0
    if f.rsi > 78:
        r += min(15.0, (f.rsi - 78) / 12 * 15)
    return max(0.0, min(100.0, r))


class Strategy(ABC):
    name: str = "base"

    def __init__(self, cfg: StrategyCfg | None = None):
        self.cfg = cfg or StrategyCfg()

    @abstractmethod
    def evaluate(self, f: Features) -> SignalResult:
        """f: yalnızca KAPANMIŞ mumlardan hesaplanmış özellikler."""

    # ortak yardımcılar
    def hold(self, f: Features, why: str) -> SignalResult:
        return SignalResult(self.name, Signal.HOLD, 0.0, risk_score=risk_score(f), reason=why)

    def exit(self, f: Features, conf: float, why: str) -> SignalResult:
        return SignalResult(self.name, Signal.SELL, max(0.0, min(1.0, conf)), entry_price=f.price,
                            risk_score=risk_score(f), reason=why)

    def buy(self, f: Features, conf: float, why: str, *, tp1: float | None = None, stop: float | None = None) -> SignalResult:
        c = self.cfg
        entry = f.price
        stop = stop if stop is not None else entry - c.atr_stop_mult * f.atr
        dist = entry - stop
        t1 = tp1 if tp1 is not None else entry + c.rr1 * dist
        t2 = entry + c.rr2 * dist
        conf = max(0.0, min(1.0, conf))
        reward, risk = (t1 - entry) / entry * 100, dist / entry * 100
        res = SignalResult(self.name, Signal.BUY, conf, entry, stop, t1, t2,
                           expected_return=conf * reward - (1 - conf) * risk, risk_score=risk_score(f), reason=why)
        res.validate()
        return res
