"""Fibonacci geri çekilme + destek + MACD/RSI dönüş teyidi (long-only).

Mantık (parametreler sonuç görülmeden SABİTLENDİ; ızgara/ayar YOK):
  1. Büyük resim yükselişte: EMA50 > EMA200 ve EMA50 eğimi > 0.
  2. Son yükseliş bacağı (swing dip -> swing tepe) anlamlı: tepe-dip >= 3 ATR.
  3. Fiyat bacağın %38.2 - %61.8 geri çekilme bölgesinde (destek bölgesi).
  4. Dönüş teyidi: MACD histogramı artıyor (önceki bara göre) ve RSI 30-55 arası (aşırı alınmamış).
  5. Stop: swing dibinin 0.25 ATR altı (yapısal destek). Stop mesafesi 0.5 - 6 ATR arasında olmalı.
  6. Hedefler: config'teki rr1/rr2 (mevcut sistemle aynı yönetim kuralları).
"""
from __future__ import annotations

import math

from app.core.features import Features
from app.strategies.base import SignalResult, Strategy


class FibPullback(Strategy):
    name = "fib_pullback"
    FIB_MIN, FIB_MAX = 0.382, 0.618
    MIN_LEG_ATR = 3.0
    RSI_MIN, RSI_MAX = 30.0, 55.0
    STOP_BUF_ATR = 0.25
    MIN_STOP_ATR, MAX_STOP_ATR = 0.5, 6.0

    def evaluate(self, f: Features) -> SignalResult:
        if any(math.isnan(x) for x in (f.fib_lo, f.fib_hi, f.ema200, f.ema50_slope_pct)) or f.atr <= 0:
            return self.hold(f, "swing yapısı/veri yok")
        if not (f.ema50 > f.ema200 and f.ema50_slope_pct > 0):
            return self.hold(f, "büyük resim yükselişte değil")
        leg = f.fib_hi - f.fib_lo
        if leg < self.MIN_LEG_ATR * f.atr:
            return self.hold(f, "yükseliş bacağı küçük")
        retr = (f.fib_hi - f.price) / leg
        if not (self.FIB_MIN <= retr <= self.FIB_MAX):
            return self.hold(f, f"fiyat Fib bölgesinde değil (geri çekilme %{retr * 100:.0f})")
        if not (f.macd_hist > f.macd_hist_prev and self.RSI_MIN <= f.rsi <= self.RSI_MAX):
            return self.hold(f, "dönüş teyidi yok (MACD/RSI)")
        stop = f.fib_lo - self.STOP_BUF_ATR * f.atr
        dist = f.price - stop
        if not (self.MIN_STOP_ATR * f.atr <= dist <= self.MAX_STOP_ATR * f.atr):
            return self.hold(f, "stop mesafesi uygun değil")
        return self.buy(f, 0.6, f"Fib %{retr * 100:.0f} geri çekilme, destek {f.fib_lo:.6g}, tepe {f.fib_hi:.6g}, "
                                f"MACD hist artıyor, RSI {f.rsi:.0f}", stop=stop)
