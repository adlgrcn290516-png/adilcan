from decimal import Decimal as D

import pytest

from app.config import Settings
from app.execution import safety
from app.execution.binance_broker import RealMoneyLocked, SpotBinanceBroker
from app.live import LiveGuard
from tests.test_runner import mk
from tests.test_phase4 import _forced


def test_guard_trips_on_loss_and_closes_positions(tmp_path):
    r, orch, mgr, ad, sc, clk, saved = mk(tmp_path)
    o = _forced(sc, ad, "UPUSDT")
    q = orch.quotes_for({"UPUSDT"})
    assert mgr.try_open(o, o.f_obj, q["UPUSDT"], q).position
    g = LiveGuard(mgr, orch, tmp_path / "g.json", D("0.0000001"))
    g.start = mgr.equity(q) + D(1)           # başlangıç özsermaye şimdikinden yüksek -> kayıp > limit
    g()
    assert g.tripped and mgr.risk.emergency_stop and "UPUSDT" not in mgr.positions


def test_guard_does_not_trip_without_loss(tmp_path):
    r, orch, mgr, ad, sc, clk, saved = mk(tmp_path)
    g = LiveGuard(mgr, orch, tmp_path / "g.json", D(5))
    g()
    assert not g.tripped and (tmp_path / "g.json").exists()
    g()
    assert not g.tripped


def test_runner_calls_guard_each_tick(tmp_path):
    r, *_ = mk(tmp_path, guard=lambda: calls.append(1))
    calls = []
    r.run(max_ticks=3)
    assert len(calls) == 3


def test_real_money_lock_blocks_when_closed(monkeypatch):
    monkeypatch.setattr(safety, "REAL_MONEY_ENABLED", False)
    s = Settings(api_key="k", api_secret="s")
    with pytest.raises(RealMoneyLocked):
        SpotBinanceBroker(s, object())
