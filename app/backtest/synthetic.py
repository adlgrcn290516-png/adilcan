"""Sentetik piyasa verisi: çevrimdışı demo ve testler için. SENTETİK veride kâr/zarar GERÇEK DEĞİLDİR."""
from __future__ import annotations

import math

import pandas as pd

H = 3_600_000


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
