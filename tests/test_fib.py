import numpy as np
import pandas as pd

from app.core import features as F
from app.core.swings import swing_levels
from app.strategies.fib_pullback import FibPullback
from app.strategies.base import Signal
from tests.synth import gbm_df


def _zigzag():
    # dip 100 -> tepe 140 -> geri çekilme 124 (≈%60) ; 5 bar onay gecikmesi için sonrası düz
    c = np.concatenate([np.linspace(110, 100, 12), np.linspace(100, 140, 25), np.linspace(140, 124, 20), np.full(8, 124.0)])
    h, l = c + 0.5, c - 0.5
    return h, l, c


def test_swings_are_causal_and_confirmed_with_delay():
    h, l, c = _zigzag()
    lo, hi, sup, res = swing_levels(h, l, c, k=5)
    j_top = int(np.argmax(h))
    assert np.isnan(hi[:j_top + 5]).all()            # tepe, k bar sonra onaylanır (önce bilinemez)
    assert hi[-1] == h[j_top] and lo[-1] < 100.6     # onaydan sonra bacak bilinir
    # geleceği çarpıtmak geçmiş değerleri değiştirmez
    h2, l2, c2 = h.copy(), l.copy(), c.copy()
    h2[50:] *= 2; l2[50:] *= 2; c2[50:] *= 2
    lo2, hi2, _, _ = swing_levels(h2, l2, c2, k=5)
    n = 50
    assert np.allclose(np.nan_to_num(lo[:n]), np.nan_to_num(lo2[:n])) and np.allclose(np.nan_to_num(hi[:n]), np.nan_to_num(hi2[:n]))


def test_leg_invalidated_when_low_breaks():
    h, l, c = _zigzag()
    c = c.copy(); c[-1] = 90.0; l = l.copy(); l[-1] = 89.0
    lo, hi, _, _ = swing_levels(h, l, c, k=5)
    assert np.isnan(lo[-1]) and np.isnan(hi[-1])


def test_strategy_buys_in_fib_zone_with_structural_stop_and_holds_otherwise():
    df = gbm_df(400, 3)
    p = F.prepare(df)
    st = FibPullback()
    seen_buy = False
    for i in range(250, 399):
        f = F.features_at(p, i, "X")
        r = st.evaluate(f)
        if r.signal is Signal.BUY:
            seen_buy = True
            assert r.stop_loss < r.entry_price and r.stop_loss < f.fib_lo
            assert st.FIB_MIN <= (f.fib_hi - f.price) / (f.fib_hi - f.fib_lo) <= st.FIB_MAX
    f0 = F.features_at(p, 250, "X")
    f0.fib_lo = float("nan")
    assert st.evaluate(f0).signal is Signal.HOLD
