"""6 temel strateji (long-only Spot). Eşikler config'ten (StrategyCfg); mantık açıklanabilir ve deterministik.

NOT: Eşikler ÖNSEL varsayımlardır; geçmiş veriyle (Faz 5 backtest, walk-forward) doğrulanana kadar
kârlılık iddiası YOKTUR.
"""
from __future__ import annotations

import math

from app.core.features import Features
from app.strategies.base import SignalResult, Strategy


def _clip(x: float, lo: float = 0.0, hi: float = 1.0) -> float:
    return max(lo, min(hi, x))


class TrendFollowing(Strategy):
    name = "trend"

    def evaluate(self, f: Features) -> SignalResult:
        if math.isnan(f.ema50_slope_pct):
            return self.hold(f, "yetersiz veri")
        long_ok = math.isnan(f.ema200) or f.price > f.ema200
        up = f.price > f.ema20 > f.ema50 and long_ok and f.ema50_slope_pct > 0 and f.macd_hist > 0
        if up and 45 <= f.rsi <= 75:
            conf = 0.5 + 0.25 * _clip(f.ema50_slope_pct / 1.0) + 0.25 * _clip((f.rsi - 45) / 20)
            return self.buy(f, conf * (1.0 if not math.isnan(f.ema200) else 0.9),
                            f"EMA20>EMA50, fiyat>EMA20{', >EMA200' if not math.isnan(f.ema200) else ''}, "
                            f"MACD hist>0, EMA50 eğimi %{f.ema50_slope_pct:.2f}, RSI {f.rsi:.0f}")
        if f.price < f.ema50 and f.ema20 < f.ema50:  # girişin aynası: düşüş hizalaması
            return self.exit(f, 0.6, "fiyat EMA50 altında ve EMA20<EMA50 (trend bozuldu)")
        return self.hold(f, "trend koşulları sağlanmadı")


class Momentum(Strategy):
    name = "momentum"

    def evaluate(self, f: Features) -> SignalResult:
        if math.isnan(f.momentum_pct):
            return self.hold(f, "yetersiz veri")
        if f.momentum_pct >= self.cfg.momentum_min_pct and 55 <= f.rsi <= 78 and f.macd_hist > 0 \
                and f.macd_hist > f.macd_hist_prev:
            conf = 0.5 + 0.3 * _clip(f.momentum_pct / 6.0) + 0.2 * _clip((78 - f.rsi) / 23)
            return self.buy(f, conf, f"12 bar getiri %{f.momentum_pct:.2f}, RSI {f.rsi:.0f}, MACD hist artıyor")
        if f.rsi < 45 and f.macd_hist < 0 and f.momentum_pct < 0:
            return self.exit(f, 0.55, f"momentum negatif (%{f.momentum_pct:.2f}), RSI {f.rsi:.0f}")
        return self.hold(f, "momentum yetersiz")


class Breakout(Strategy):
    name = "breakout"

    def evaluate(self, f: Features) -> SignalResult:
        if math.isnan(f.breakout_strength_atr):
            return self.hold(f, "yetersiz veri")
        if f.breakout_up and 0.2 <= f.breakout_strength_atr <= 2.5 and f.macd_hist > 0:
            conf = 0.5 + 0.3 * _clip(f.breakout_strength_atr / 1.5) + 0.2 * _clip(f.volume_ratio / 2.0 if not math.isnan(f.volume_ratio) else 0)
            return self.buy(f, conf, f"{f.high_n:.6g} direnci kırıldı ({f.breakout_strength_atr:.2f} ATR), hacim x{f.volume_ratio:.1f}")
        if f.breakout_up and f.breakout_strength_atr > 2.5:
            return self.hold(f, "kırılım fazla uzamış (geç kalındı)")
        return self.hold(f, "kırılım yok")


class VolumeBreakout(Strategy):
    name = "volume_breakout"

    def evaluate(self, f: Features) -> SignalResult:
        if math.isnan(f.volume_ratio) or math.isnan(f.breakout_strength_atr):
            return self.hold(f, "yetersiz veri")
        if f.breakout_up and f.breakout_strength_atr <= 3.0 and f.volume_ratio >= self.cfg.volume_breakout_mult \
                and f.taker_buy_ratio > 0.52:
            conf = 0.55 + 0.25 * _clip((f.volume_ratio - 2) / 3) + 0.2 * _clip((f.taker_buy_ratio - 0.52) / 0.15)
            return self.buy(f, conf, f"kırılım + hacim x{f.volume_ratio:.1f}, taker alım payı %{f.taker_buy_ratio * 100:.0f}")
        return self.hold(f, "hacim onaylı kırılım yok")


class MeanReversion(Strategy):
    name = "mean_reversion"

    def evaluate(self, f: Features) -> SignalResult:
        if math.isnan(f.bb_mid) or math.isnan(f.ema50_slope_pct):
            return self.hold(f, "yetersiz veri")
        falling_knife = f.ema50_slope_pct < -1.5 or (not math.isnan(f.ema200) and f.price < f.ema200 * 0.9)
        if f.bb_zscore <= self.cfg.meanrev_z and f.rsi <= self.cfg.meanrev_rsi and not falling_knife:
            conf = 0.5 + 0.3 * _clip((-f.bb_zscore - 2) / 1.5) + 0.2 * _clip((self.cfg.meanrev_rsi - f.rsi) / 15)
            stop = f.price - 1.5 * f.atr
            return self.buy(f, conf, f"aşırı satım: z={f.bb_zscore:.2f}, RSI {f.rsi:.0f}; hedef bant ortası",
                            tp1=max(f.bb_mid, f.price + 0.5 * f.atr), stop=stop)
        if f.bb_zscore >= 2 and f.rsi >= 70:
            return self.exit(f, 0.5, f"aşırı alım: z={f.bb_zscore:.2f}, RSI {f.rsi:.0f}")
        return self.hold(f, "aşırı satım yok")


class VolatilityBreakout(Strategy):
    name = "volatility_breakout"

    def evaluate(self, f: Features) -> SignalResult:
        if math.isnan(f.bb_upper) or math.isnan(f.volume_ratio):
            return self.hold(f, "yetersiz veri")
        if f.squeeze_recent and f.price > f.bb_upper and f.volume_ratio >= 1.2:
            conf = 0.55 + 0.25 * _clip((f.volume_ratio - 1.2) / 2) + 0.2 * _clip(f.macd_hist / max(f.atr, 1e-12))
            return self.buy(f, conf, f"sıkışma sonrası üst banda kırılım, hacim x{f.volume_ratio:.1f}")
        return self.hold(f, "sıkışma-kırılım yok")


CLASSIC = [TrendFollowing, Momentum, Breakout, VolumeBreakout, MeanReversion, VolatilityBreakout]
