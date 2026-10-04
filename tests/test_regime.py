import numpy as np
import pytest

from app.backtest import regime as RG


def series(n=1500, seed=0, drift=0.0006, vol=0.03):
    rng = np.random.default_rng(seed)
    r = rng.normal(drift, vol, n)
    c = 100 * np.cumprod(1 + r)
    o = np.concatenate([[100.0], c[:-1]]) * (1 + rng.normal(0, 0.002, n))
    return np.arange(n) * RG.DAY_MS + 1_500_000_000_000, o, c


def test_sma_and_positions_are_causal():
    t, o, c = series()
    p1 = RG.positions(c, 50)
    c2 = c.copy()
    c2[1000:] *= 3.0                      # geleceği bozmak, önceki kararları DEĞİŞTİRMEMELİ
    assert (RG.positions(c2, 50)[:1000] == p1[:1000]).all()
    s = RG.sma(np.arange(1.0, 11.0), 3)
    assert np.isnan(s[:2]).all() and s[2] == 2.0 and s[-1] == 9.0


def test_strategy_returns_execution_and_cost():
    o = np.array([100, 100, 110, 121.0, 121.0])
    c = np.array([100, 110, 121, 121.0, 100.0])
    p = np.array([False, True, True, False, False])       # 1. kapanışta al (2. açılışta uygula), 3. kapanışta sat
    r = RG.strategy_returns(o, c, p, cost=0.0)
    assert r[2] == pytest.approx(121 / 110 - 1)           # gün2: açılış 110'dan giriş, kapanış 121
    assert r[3] == pytest.approx(121 / 121 - 1)           # gün3: açılışta çıkış (121/121)
    assert r[4] == 0.0
    r2 = RG.strategy_returns(o, c, p, cost=0.01)
    assert r2[2] == pytest.approx(121 / 110 - 1 - 0.01) and r2[3] == pytest.approx(0.0) and r2[4] == pytest.approx(-0.01)  # çıkış maliyeti 5. günün açılışında


def test_filter_reduces_drawdown_in_crash_and_costs_hurt():
    rng = np.random.default_rng(3)
    up = 100 * np.cumprod(1 + rng.normal(0.003, 0.01, 500))
    down = up[-1] * np.cumprod(1 + rng.normal(-0.004, 0.01, 400))
    c = np.concatenate([up, down])
    o = np.concatenate([[100.0], c[:-1]])
    t = np.arange(len(c)) * RG.DAY_MS + 1_500_000_000_000
    r = RG.evaluate_asset("X", t, o, c, n=100, n_shifts=100)
    assert r.filt["maxdd"] < r.hold["maxdd"]              # düşüşte nakde geçti
    free = RG.evaluate_asset("X", t, o, c, n=100, fee=0.0, slip_bps=0.0, n_shifts=10)
    assert free.filt["total"] > r.filt["total"]            # maliyet monoton zarar
    assert 0 <= r.shift_pctile_sharpe <= 1 and r.switches >= 1


def test_verdict_needs_all_criteria_on_all_assets():
    good = dict(sharpe=1.5, maxdd=0.2, total=1, cagr=.1, calmar=1)
    hold = dict(sharpe=1.0, maxdd=0.7, total=2, cagr=.2, calmar=.3)
    mk = lambda pc: RG.AssetResult("A", hold, good, .5, 10, pc, .5, {2020: (.1, .2)}, 1000, 0)  # noqa: E731
    assert RG.verdict([mk(0.95), mk(0.95)])[1] is True
    assert RG.verdict([mk(0.95), mk(0.50)])[1] is False
    bad = RG.AssetResult("B", hold, dict(good, maxdd=0.5), .5, 10, .99, .5, {}, 1000, 0)
    assert RG.verdict([mk(0.95), bad])[1] is False
