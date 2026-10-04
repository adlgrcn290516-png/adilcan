"""Deterministik sentetik piyasa verisi (testler için)."""
from __future__ import annotations

import math
from decimal import Decimal as D

import pandas as pd

from app.exchange.models import Kline

H = 3_600_000


def make_df(closes, vols=None, start_ms=1_700_000_000_000, wick=0.002, taker=0.55) -> pd.DataFrame:
    n = len(closes)
    vols = vols or [1000.0] * n
    rows, prev = [], closes[0]
    for i, c in enumerate(closes):
        o = prev
        hi, lo = max(o, c) * (1 + wick), min(o, c) * (1 - wick)
        qv = vols[i] * c
        rows.append(dict(open_time=start_ms + i * H, close_time=start_ms + (i + 1) * H - 1, open=o, high=hi, low=lo,
                         close=c, volume=vols[i], quote_volume=qv, taker_buy_quote=qv * taker, trades=100))
        prev = c
    return pd.DataFrame(rows)


def to_klines(df: pd.DataFrame) -> list[Kline]:
    return [Kline(int(r.open_time), D(str(r.open)), D(str(r.high)), D(str(r.low)), D(str(r.close)), D(str(r.volume)),
                  int(r.close_time), D(str(r.quote_volume)), int(r.trades), D(str(r.volume * 0.5)),
                  D(str(r.taker_buy_quote))) for r in df.itertuples()]


def sideways(n=150, base=100.0, amp=0.004):
    return [base * (1 + amp * math.sin(i / 3.0)) for i in range(n)]


def grind_up(n=260, base=100.0):
    """Çoğunlukla yukarı ama geri çekilmeli (RSI doygunluğa gitmez)."""
    out, p = [], base
    for i in range(n):
        p *= 1.0045 if i % 3 != 2 else 0.9965
        out.append(p)
    return out


def gbm_df(n=600, seed=0, drift=0.0, vol=0.004, start=100.0, start_ms=1_700_000_000_000, vol_regime=True):
    """Rastgele yürüyüş (GBM) + gerçekçi mum fitilleri/hacim/taker oranı. drift=0 -> kenar (edge) YOK."""
    import numpy as np
    rng = np.random.default_rng(seed)
    rows, prev = [], start
    for i in range(n):
        sig = vol * (1.8 if (vol_regime and (i // 150) % 3 == 1) else 1.0)
        c = prev * math.exp(rng.normal(drift, sig))
        hi = max(prev, c) * (1 + abs(rng.normal(0, sig / 2)))
        lo = min(prev, c) * (1 - abs(rng.normal(0, sig / 2)))
        v = float(rng.lognormal(7, 0.4) * (3 if rng.random() < 0.03 else 1))
        qv = v * c
        tk = float(min(0.9, max(0.1, rng.normal(0.5, 0.06))))
        rows.append(dict(open_time=start_ms + i * H, close_time=start_ms + (i + 1) * H - 1, open=prev, high=hi, low=lo,
                         close=c, volume=v, quote_volume=qv, taker_buy_quote=qv * tk, trades=100))
        prev = c
    return pd.DataFrame(rows)
