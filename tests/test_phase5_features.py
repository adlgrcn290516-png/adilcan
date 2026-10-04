import math

import numpy as np
import pytest

from app.core import features as new
from tests import ref_features as old
from tests.synth import gbm_df


def _close(a, b):
    if a is None or b is None:
        return a is b
    if isinstance(a, (bool, int, str)) and not isinstance(a, float):
        return a == b
    return math.isclose(a, b, rel_tol=1e-9, abs_tol=1e-9)


@pytest.mark.parametrize("seed", [0, 1, 2])
def test_fast_features_equal_reference_and_have_no_lookahead(seed):
    df = gbm_df(500, seed)
    prep = new.prepare(df)
    for i in (100, 215, 300, 420, 499):
        fast = new.features_at(prep, i, "X").as_dict()
        ref = old.compute_features("X", df.iloc[:i + 1].reset_index(drop=True), None, None).as_dict()
        for k, v in ref.items():
            if k == "change_24h_pct":  # eski sürümde ticker yokken NaN; yenisi veriden türetir
                continue
            assert _close(fast[k], v), (i, k, fast[k], v)


def test_features_at_is_unaffected_by_future_bars():
    df = gbm_df(400, 5)
    p1 = new.prepare(df.iloc[:300].reset_index(drop=True))
    df2 = df.copy()
    df2.loc[300:, ["open", "high", "low", "close"]] *= 3.0     # geleceği çarpıt
    p2 = new.prepare(df2)
    for i in (150, 250, 299):
        a, b = new.features_at(p1, i, "X").as_dict(), new.features_at(p2, i, "X").as_dict()
        assert all(_close(a[k], b[k]) for k in a), i
