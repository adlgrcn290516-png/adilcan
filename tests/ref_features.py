"""Özellik (feature) hesabı. SADECE kapanmış mumlar kullanılır; oluşmakta olan mum atılır.

Analitik hesaplar float ile yapılır (pandas); emir miktar/fiyatı execution katmanında Decimal'e çevrilir.
"""
from __future__ import annotations

import math
from dataclasses import asdict, dataclass

import numpy as np
import pandas as pd

from app.data import indicators as ind
from app.exchange.models import Kline, OrderBook, Ticker24h


def klines_to_df(ks: list[Kline]) -> pd.DataFrame:
    return pd.DataFrame({
        "open_time": [k.open_time for k in ks], "close_time": [k.close_time for k in ks],
        "open": [float(k.open) for k in ks], "high": [float(k.high) for k in ks],
        "low": [float(k.low) for k in ks], "close": [float(k.close) for k in ks],
        "volume": [float(k.volume) for k in ks], "quote_volume": [float(k.quote_volume) for k in ks],
        "taker_buy_quote": [float(k.taker_buy_quote) for k in ks], "trades": [k.trades for k in ks],
    })


def closed_only(df: pd.DataFrame, now_ms: int) -> pd.DataFrame:
    """close_time henüz geçmemiş (oluşmakta olan) mumu at."""
    return df[df["close_time"] < now_ms].reset_index(drop=True)


@dataclass
class Features:
    symbol: str
    price: float
    change_24h_pct: float
    quote_volume_24h: float
    volume_ratio: float            # son kapanmış mum hacmi / önceki 20 ort.
    volume_trend: float            # son 5 ort. / önceki 20 ort.
    volatility_pct: float          # 20 bar getiri std (%)
    atr: float
    atr_pct: float
    rsi: float
    macd: float
    macd_signal: float
    macd_hist: float
    macd_hist_prev: float
    ema20: float
    ema50: float
    ema200: float                  # NaN: yeterli veri yok
    ema50_slope_pct: float         # EMA50'nin 10 bar değişimi (%)
    momentum_pct: float            # 12 bar getiri (%)
    ob_imbalance: float            # [-1, 1] ilk N seviye
    spread_pct: float
    depth_quote_1pct: float        # mid ±%1 içindeki bid+ask quote hacmi
    taker_buy_ratio: float         # son 12 bar taker alım payı
    support: float
    resistance: float
    high_n: float                  # önceki N bar en yüksek
    breakout_up: bool
    breakout_strength_atr: float   # (close - high_n)/ATR
    bb_mid: float
    bb_upper: float
    bb_lower: float
    bb_zscore: float
    bb_width_pctile: float
    squeeze_recent: bool           # son 5 barda bant genişliği alt %X'te miydi
    last_range_atr: float          # son kapanmış mum (high-low)/ATR  -> ani hareket tespiti
    last_close_time: int

    def as_dict(self) -> dict:
        return {k: (None if isinstance(v, float) and math.isnan(v) else v) for k, v in asdict(self).items()}


def _f(x) -> float:
    return float(x) if x is not None and not (isinstance(x, float) and math.isnan(x)) else float("nan")


def compute_features(symbol: str, df: pd.DataFrame, ticker: Ticker24h | None, book: OrderBook | None,
                     *, breakout_lookback: int = 20, squeeze_pctile: float = 0.2,
                     min_bars: int = 60) -> Features | None:
    """df: KAPANMIŞ mumlar. Yetersiz veri -> None."""
    if len(df) < min_bars:
        return None
    c, h, l, v = df["close"], df["high"], df["low"], df["volume"]
    macd_l, macd_s, macd_h = ind.macd(c)
    atr_s = ind.atr(h, l, c)
    e20, e50, e200 = ind.ema(c, 20), ind.ema(c, 50), ind.ema(c, 200)
    mid, up, lo = ind.bollinger(c)
    width = (up - lo) / mid
    wp = ind.rolling_pctile(width, 100)
    ph = ind.prior_high(h, breakout_lookback)
    i = len(df) - 1
    price, atr_v = float(c.iloc[i]), _f(atr_s.iloc[i])
    vol_prev20 = v.iloc[i - 20:i].mean() if i >= 20 else float("nan")
    ret = c.pct_change()
    sd = float(ret.iloc[i - 19:i + 1].std(ddof=0) * 100) if i >= 20 else float("nan")
    slope = (float(e50.iloc[i] / e50.iloc[i - 10] - 1) * 100) if i >= 10 and not np.isnan(e50.iloc[i - 10]) else float("nan")
    tb = df["taker_buy_quote"].iloc[i - 11:i + 1].sum() / max(df["quote_volume"].iloc[i - 11:i + 1].sum(), 1e-12)
    bz = (price - mid.iloc[i]) / ((up.iloc[i] - mid.iloc[i]) / 2) if (up.iloc[i] - mid.iloc[i]) else 0.0
    hn = _f(ph.iloc[i])
    ob_imb = spread = depth = float("nan")
    if book is not None and book.bids and book.asks:
        ob_imb = float(book.imbalance(10) or 0)
        spread = float(book.spread_pct or 0)
        m = (float(book.bids[0][0]) + float(book.asks[0][0])) / 2
        depth = sum(float(p * q) for p, q in book.bids if float(p) >= m * 0.99) + \
            sum(float(p * q) for p, q in book.asks if float(p) <= m * 1.01)
    win = df.iloc[max(0, i - 47):i + 1]
    return Features(
        symbol=symbol, price=price, change_24h_pct=float(ticker.price_change_pct) if ticker else float("nan"),
        quote_volume_24h=float(ticker.quote_volume) if ticker else float(df["quote_volume"].iloc[-24:].sum()),
        volume_ratio=float(v.iloc[i] / vol_prev20) if vol_prev20 and not np.isnan(vol_prev20) else float("nan"),
        volume_trend=float(v.iloc[i - 4:i + 1].mean() / vol_prev20) if vol_prev20 and not np.isnan(vol_prev20) else float("nan"),
        volatility_pct=sd, atr=atr_v, atr_pct=atr_v / price * 100 if price else float("nan"),
        rsi=_f(ind.rsi(c).iloc[i]), macd=_f(macd_l.iloc[i]), macd_signal=_f(macd_s.iloc[i]),
        macd_hist=_f(macd_h.iloc[i]), macd_hist_prev=_f(macd_h.iloc[i - 1]),
        ema20=_f(e20.iloc[i]), ema50=_f(e50.iloc[i]), ema200=_f(e200.iloc[i]), ema50_slope_pct=slope,
        momentum_pct=float((price / c.iloc[i - 12] - 1) * 100) if i >= 12 else float("nan"),
        ob_imbalance=ob_imb, spread_pct=spread, depth_quote_1pct=depth, taker_buy_ratio=float(tb),
        support=float(win["low"].min()), resistance=float(win["high"].max()), high_n=hn,
        breakout_up=bool(not np.isnan(hn) and price > hn),
        breakout_strength_atr=(price - hn) / atr_v if (not np.isnan(hn) and atr_v) else float("nan"),
        bb_mid=_f(mid.iloc[i]), bb_upper=_f(up.iloc[i]), bb_lower=_f(lo.iloc[i]), bb_zscore=float(bz),
        bb_width_pctile=_f(wp.iloc[i]),
        squeeze_recent=bool((wp.iloc[max(0, i - 5):i] <= squeeze_pctile).any()),
        last_range_atr=float((h.iloc[i] - l.iloc[i]) / atr_v) if atr_v else float("nan"),
        last_close_time=int(df["close_time"].iloc[i]))
