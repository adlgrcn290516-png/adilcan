"""Nedensel (look-ahead'siz) swing / destek-direnç / Fibonacci geri çekilme seviyeleri.

Pivot: j. barın düşüğü (yükseği), [j-k, j+k] penceresinin en düşüğü (yükseği) ise swing dibi (tepesi).
Pivot, j+k barı KAPANINCA bilinir; bu yüzden bar i'de yalnızca j = i-k ve öncesindeki pivotlar kullanılır.
Yükseliş bacağı: bir swing dibini izleyen swing tepesi. Fib bölgesi bu bacağın [dip, tepe] aralığından hesaplanır.
"""
from __future__ import annotations

import numpy as np

NAN = float("nan")


def swing_levels(h: np.ndarray, l: np.ndarray, c: np.ndarray, k: int = 5):
    """-> fib_lo, fib_hi, pivot_support, pivot_resistance (her biri bar başına dizi, NaN = yok)."""
    n = len(h)
    fib_lo, fib_hi = np.full(n, NAN), np.full(n, NAN)
    sup, res = np.full(n, NAN), np.full(n, NAN)
    lows: list[tuple[int, float]] = []   # onaylı swing dipleri (idx, fiyat)
    cur_lo = cur_hi = sup_v = res_v = NAN
    for i in range(n):
        j = i - k
        if j >= k:
            if np.argmin(l[j - k:i + 1]) == k:          # j: swing dibi (pencerede ilk en düşük)
                lows.append((j, float(l[j])))
                sup_v = float(l[j])
            if np.argmax(h[j - k:i + 1]) == k:          # j: swing tepesi
                res_v = float(h[j])
                prev = [p for (jj, p) in lows if jj < j]
                if prev and h[j] > prev[-1]:
                    cur_lo, cur_hi = prev[-1], float(h[j])
        if cur_lo == cur_lo and c[i] < cur_lo:            # bacağın dibi kırıldı -> yapı geçersiz
            cur_lo = cur_hi = NAN
        fib_lo[i], fib_hi[i], sup[i], res[i] = cur_lo, cur_hi, sup_v, res_v
    return fib_lo, fib_hi, sup, res
