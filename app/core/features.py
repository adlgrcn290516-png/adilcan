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


@dataclass(slots=True)
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


@dataclass
class Prepared:
    """Göstergeler BİR KEZ (nedensel) hesaplanır; bar i'deki özellikler yalnızca <= i verisinden okunur.
    Prefix üzerinde yeniden hesaplamayla birebir aynıdır (test ile kanıtlı) -> backtest hızlı ve look-ahead'siz."""
    df: pd.DataFrame
    a: dict
    breakout_lookback: int


def prepare(df: pd.DataFrame, *, breakout_lookback: int = 20, squeeze_pctile: float = 0.2) -> Prepared:
    c, h, l, v = df["close"], df["high"], df["low"], df["volume"]
    macd_l, macd_s, macd_h = ind.macd(c)
    mid, up, lo = ind.bollinger(c)
    width = (up - lo) / mid
    wp = ind.rolling_pctile(width, 100)
    e50 = ind.ema(c, 50)
    ret = c.pct_change()
    sq = (wp <= squeeze_pctile).astype(float).shift(1).rolling(5, min_periods=1).sum() > 0
    arr = lambda s_: s_.to_numpy(dtype=float)  # noqa: E731
    a = {
        "c": arr(c), "h": arr(h), "l": arr(l), "v": arr(v), "qv": arr(df["quote_volume"]),
        "tbq": arr(df["taker_buy_quote"]), "ct": df["close_time"].to_numpy(),
        "macd": arr(macd_l), "macd_s": arr(macd_s), "macd_h": arr(macd_h), "atr": arr(ind.atr(h, l, c)),
        "e20": arr(ind.ema(c, 20)), "e50": arr(e50), "e200": arr(ind.ema(c, 200)), "rsi": arr(ind.rsi(c)),
        "mid": arr(mid), "up": arr(up), "lo": arr(lo), "wp": arr(wp), "ph": arr(ind.prior_high(h, breakout_lookback)),
        "vprev20": arr(v.shift(1).rolling(20).mean()), "v5": arr(v.rolling(5).mean()),
        "sd20": arr(ret.rolling(20).std(ddof=0) * 100), "e50_10": arr(e50.shift(10)),
        "tb12": arr(df["taker_buy_quote"].rolling(12).sum()), "qv12": arr(df["quote_volume"].rolling(12).sum()),
        "sup": arr(l.rolling(48, min_periods=1).min()), "res": arr(h.rolling(48, min_periods=1).max()),
        "sq": sq.to_numpy(dtype=bool),
    }
    return Prepared(df, a, breakout_lookback)


def features_at(p: Prepared, i: int, symbol: str, ticker: Ticker24h | None = None, book: OrderBook | None = None,
                *, bars_per_day: int = 24) -> Features:
    """i. KAPANMIŞ bardaki özellikler. i >= 1 olmalı."""
    a = p.a
    nan = float("nan")
    price, atr_v = a["c"][i], a["atr"][i]
    vp = a["vprev20"][i]
    vp_ok = i >= 20 and not np.isnan(vp) and vp != 0
    e50_10 = a["e50_10"][i]
    slope = (a["e50"][i] / e50_10 - 1) * 100 if i >= 10 and not np.isnan(e50_10) else nan
    tb = a["tb12"][i] / max(a["qv12"][i], 1e-12)
    up_, mid_ = a["up"][i], a["mid"][i]
    bz = (price - mid_) / ((up_ - mid_) / 2) if (up_ - mid_) else 0.0
    hn = a["ph"][i]
    ob_imb = spread = depth = nan
    if book is not None and book.bids and book.asks:
        ob_imb = float(book.imbalance(10) or 0)
        spread = float(book.spread_pct or 0)
        m = (float(book.bids[0][0]) + float(book.asks[0][0])) / 2
        depth = sum(float(pp * q) for pp, q in book.bids if float(pp) >= m * 0.99) + \
            sum(float(pp * q) for pp, q in book.asks if float(pp) <= m * 1.01)
    if ticker is not None:
        chg, qv24 = float(ticker.price_change_pct), float(ticker.quote_volume)
    else:  # veriden türet (backtest)
        chg = (price / a["c"][i - bars_per_day] - 1) * 100 if i >= bars_per_day else nan
        qv24 = float(a["qv"][max(0, i - bars_per_day + 1):i + 1].sum())
    return Features(
        symbol=symbol, price=float(price), change_24h_pct=chg, quote_volume_24h=qv24,
        volume_ratio=float(a["v"][i] / vp) if vp_ok else nan,
        volume_trend=float(a["v5"][i] / vp) if vp_ok else nan,
        volatility_pct=float(a["sd20"][i]) if i >= 20 else nan, atr=float(atr_v),
        atr_pct=float(atr_v / price * 100) if price else nan, rsi=float(a["rsi"][i]), macd=float(a["macd"][i]),
        macd_signal=float(a["macd_s"][i]), macd_hist=float(a["macd_h"][i]), macd_hist_prev=float(a["macd_h"][i - 1]),
        ema20=float(a["e20"][i]), ema50=float(a["e50"][i]), ema200=float(a["e200"][i]), ema50_slope_pct=float(slope),
        momentum_pct=float((price / a["c"][i - 12] - 1) * 100) if i >= 12 else nan,
        ob_imbalance=ob_imb, spread_pct=spread, depth_quote_1pct=depth, taker_buy_ratio=float(tb),
        support=float(a["sup"][i]), resistance=float(a["res"][i]), high_n=float(hn),
        breakout_up=bool(not np.isnan(hn) and price > hn),
        breakout_strength_atr=float((price - hn) / atr_v) if (not np.isnan(hn) and atr_v) else nan,
        bb_mid=float(mid_), bb_upper=float(up_), bb_lower=float(a["lo"][i]), bb_zscore=float(bz),
        bb_width_pctile=float(a["wp"][i]), squeeze_recent=bool(a["sq"][i]),
        last_range_atr=float((a["h"][i] - a["l"][i]) / atr_v) if atr_v else nan,
        last_close_time=int(a["ct"][i]))


def compute_features(symbol: str, df: pd.DataFrame, ticker: Ticker24h | None, book: OrderBook | None,
                     *, breakout_lookback: int = 20, squeeze_pctile: float = 0.2,
                     min_bars: int = 60) -> Features | None:
    """df: KAPANMIŞ mumlar. Yetersiz veri -> None. (Canlı tarama: son bar için özellikler.)"""
    if len(df) < min_bars:
        return None
    p = prepare(df, breakout_lookback=breakout_lookback, squeeze_pctile=squeeze_pctile)
    return features_at(p, len(df) - 1, symbol, ticker, book)
