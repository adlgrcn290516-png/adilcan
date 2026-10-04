"""Saf gösterge fonksiyonları (pandas). Hepsi nedensel: t anındaki değer yalnızca <= t verisinden hesaplanır."""
from __future__ import annotations

import numpy as np
import pandas as pd


def ema(s: pd.Series, n: int) -> pd.Series:
    return s.ewm(span=n, adjust=False, min_periods=n).mean()


def rsi(close: pd.Series, n: int = 14) -> pd.Series:
    """Wilder RSI. Kayıp=0 ve kazanç>0 -> 100; ikisi de 0 -> 50."""
    d = close.diff()
    gain = d.clip(lower=0).ewm(alpha=1 / n, adjust=False, min_periods=n).mean()
    loss = (-d.clip(upper=0)).ewm(alpha=1 / n, adjust=False, min_periods=n).mean()
    rs = gain / loss.replace(0, np.nan)
    out = 100 - 100 / (1 + rs)
    out = out.where(~((loss == 0) & (gain > 0)), 100.0)
    return out.where(~((loss == 0) & (gain == 0)), 50.0)


def macd(close: pd.Series, fast: int = 12, slow: int = 26, signal: int = 9):
    line = ema(close, fast) - ema(close, slow)
    sig = line.ewm(span=signal, adjust=False, min_periods=signal).mean()
    return line, sig, line - sig


def atr(high: pd.Series, low: pd.Series, close: pd.Series, n: int = 14) -> pd.Series:
    pc = close.shift(1)
    tr = pd.concat([high - low, (high - pc).abs(), (low - pc).abs()], axis=1).max(axis=1)
    tr.iloc[0] = (high - low).iloc[0]
    return tr.ewm(alpha=1 / n, adjust=False, min_periods=n).mean()


def bollinger(close: pd.Series, n: int = 20, k: float = 2.0):
    mid = close.rolling(n).mean()
    sd = close.rolling(n).std(ddof=0)
    return mid, mid + k * sd, mid - k * sd


def prior_high(high: pd.Series, n: int) -> pd.Series:
    """Önceki N barın en yükseği (GÜNCEL bar hariç -> look-ahead yok)."""
    return high.shift(1).rolling(n).max()


def prior_low(low: pd.Series, n: int) -> pd.Series:
    return low.shift(1).rolling(n).min()


def rolling_pctile(s: pd.Series, n: int) -> pd.Series:
    """Son değerin son n değer içindeki yüzdelik sırası [0,1]."""
    return s.rolling(n).apply(lambda w: (w <= w[-1]).mean(), raw=True)
