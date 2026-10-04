"""Açıklanabilir bileşen skorları (0-100) ve ağırlıklı composite skor.

composite = Σ (normalize_ağırlık_i * skor_i)  -  risk_weight * risk_score        (sonra [0,100]'e kırp)
Her bileşenin katkısı PUAN olarak raporlanır -> toplam, composite'e eşittir (kırpma öncesi).
Ağırlıklar config'ten gelir (ScoringCfg); kodda sabit yok.
"""
from __future__ import annotations

import math
from dataclasses import dataclass

from app.config import ScoringCfg
from app.core.features import Features
from app.strategies.base import risk_score


def _c(x: float, lo: float = 0.0, hi: float = 1.0) -> float:
    return max(lo, min(hi, x))


def _nz(x: float, default: float = 0.0) -> float:
    return default if x is None or (isinstance(x, float) and math.isnan(x)) else x


def trend_score(f: Features) -> float:
    s = 0.0
    s += 20 if f.price > f.ema20 else 0
    s += 20 if f.ema20 > f.ema50 else 0
    s += (20 if f.price > f.ema200 else 0) if not math.isnan(f.ema200) else 10   # veri yoksa nötr
    s += 20 * _c(_nz(f.ema50_slope_pct) / 1.0)
    s += 20 if f.macd_hist > 0 else 0
    return s


def momentum_score(f: Features) -> float:
    m = 60 * _c(_nz(f.momentum_pct) / 5.0)
    r = 40 * _c((f.rsi - 40) / 25) - 20 * _c((f.rsi - 75) / 15)   # 65 civarı ideal, aşırı alımda ceza
    return _c(m + r, 0, 100) if (m + r) > 0 else 0.0


def volume_score(f: Features) -> float:
    vr = 60 * _c((_nz(f.volume_ratio, 1.0) - 0.8) / (2.5 - 0.8))
    tb = 40 * _c((f.taker_buy_ratio - 0.45) / 0.15)
    return vr + tb


def breakout_score(f: Features) -> float:
    if math.isnan(f.high_n) or not f.atr:
        return 0.0
    dist_atr = (f.high_n - f.price) / f.atr           # >0: direncin altında
    proximity = 60 * _c(1 - max(dist_atr, 0) / 3.0)
    confirm = 40 * _c(_nz(f.breakout_strength_atr) / 1.0) if f.breakout_up else 0.0
    if f.breakout_up and _nz(f.breakout_strength_atr) > 3.0:   # aşırı uzamış kırılım: geç
        proximity *= 0.5
    return _c(proximity + confirm, 0, 100)


def volatility_score(f: Features) -> float:
    """'Uygun' volatilite: ATR% ~1.5 civarı ideal; çok düşük (hareket yok) ya da çok yüksek kötü."""
    a = f.atr_pct
    if a <= 0 or math.isnan(a):
        return 0.0
    if a < 1.5:
        return 100 * _c(a / 1.5)
    return 100 * _c(1 - (a - 1.5) / 4.5)


def liquidity_score(f: Features) -> float:
    vol = 40 * _c((math.log10(max(f.quote_volume_24h, 1)) - 6.0) / 2.0)        # 1M$ -> 0 ... 100M$ -> 40
    parts, total = vol, 40.0
    if not math.isnan(f.depth_quote_1pct):
        parts += 35 * _c((math.log10(max(f.depth_quote_1pct, 1)) - 4.5) / 1.5)  # 30k$ -> 0 ... 1M$ -> 35
        total += 35
    if not math.isnan(f.spread_pct):
        parts += 25 * _c(1 - f.spread_pct / 0.10)
        total += 25
    return 100 * parts / total


@dataclass
class Contribution:
    name: str
    score: float      # 0-100 ham bileşen skoru
    weight: float     # normalize ağırlık (risk için ceza katsayısı)
    points: float     # composite'e katkı (risk negatif)


@dataclass
class ScoreResult:
    composite: float
    contributions: list[Contribution]
    risk: float

    def explain(self) -> list[str]:
        return [f"{c.name} {c.points:+.1f} (skor {c.score:.0f})" for c in self.contributions]


def score(f: Features, cfg: ScoringCfg) -> ScoreResult:
    comps = {
        "Trend": (trend_score(f), cfg.trend_weight), "Momentum": (momentum_score(f), cfg.momentum_weight),
        "Volume": (volume_score(f), cfg.volume_weight), "Breakout": (breakout_score(f), cfg.breakout_weight),
        "Volatility": (volatility_score(f), cfg.volatility_weight), "Liquidity": (liquidity_score(f), cfg.liquidity_weight),
    }
    wsum = sum(w for _, w in comps.values()) or 1.0
    contribs = [Contribution(n, s, w / wsum, s * w / wsum) for n, (s, w) in comps.items()]
    rk = risk_score(f)
    contribs.append(Contribution("Risk", rk, cfg.risk_weight, -cfg.risk_weight * rk))
    raw = sum(c.points for c in contribs)
    return ScoreResult(max(0.0, min(100.0, raw)), contribs, rk)
