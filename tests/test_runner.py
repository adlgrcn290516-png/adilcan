import json
from decimal import Decimal as D

import pytest

from app.core.orchestrator import Orchestrator
from app.core.runner import Runner
from app.core.scanner import MarketScanner
from app.paper import state as pstate
from app.paper.broker import PaperBroker
from app.strategies.base import Signal
from tests.synth import grind_up
from tests.test_phase4 import _forced, _orch


class Clock:
    def __init__(self, t=1_700_000_000.0):
        self.t = t

    def __call__(self):
        return self.t

    def sleep(self, s):
        self.t += s


def mk(tmp_path, series=None, **kw):
    orch, mgr, ad, sc, health, repo = _orch(series or {"UPUSDT": grind_up(260)})
    clk = Clock()
    saved = []
    r = Runner(orch, mgr, tmp_path, save_state=lambda: saved.append(1), clock=clk, sleep=clk.sleep, **kw)
    return r, orch, mgr, ad, sc, clk, saved


def test_scan_first_then_light_manage_then_next_hour(tmp_path):
    r, orch, mgr, ad, sc, clk, saved = mk(tmp_path)
    calls = []
    orch_cycle, orch_manage = orch.cycle, orch.manage_only
    orch.cycle = lambda *a, **k: (calls.append("scan"), orch_cycle(*a, **k))[1]
    orch.manage_only = lambda: (calls.append("manage"), orch_manage())[1]
    r.run(max_ticks=5)
    assert calls[0] == "scan" and calls[1:] == ["manage"] * 4        # ilk tur tarama, sonra hafif yönetim
    clk.t = r.next_scan + 1                                          # saat başı geldi
    r.run(max_ticks=r.ticks + 1)
    assert calls[-1] == "scan" and len(saved) == 6 and (tmp_path / "heartbeat.json").exists()
    hb = json.loads((tmp_path / "heartbeat.json").read_text())
    assert hb["ticks"] == r.ticks and hb["emergency_stop"] is False and "cash" in hb


def test_stop_file_blocks_entries_and_close_closes_positions(tmp_path):
    r, orch, mgr, ad, sc, clk, saved = mk(tmp_path)
    o = _forced(sc, ad, "UPUSDT")
    q = orch.quotes_for({"UPUSDT"})
    res = mgr.try_open(o, o.f_obj, q["UPUSDT"], q)
    assert res.position and "UPUSDT" in mgr.positions
    (tmp_path / "STOP").write_text("CLOSE")
    r.tick()
    assert mgr.risk.emergency_stop and "UPUSDT" not in mgr.positions      # CLOSE: pozisyon kapandı
    (tmp_path / "STOP").unlink()                                          # silmek yeniden AÇMAZ
    r.tick()
    assert mgr.risk.emergency_stop
    r2, orch2, mgr2, ad2, sc2, *_ = mk(tmp_path / "b")
    o2 = _forced(sc2, ad2, "UPUSDT")
    q2 = orch2.quotes_for({"UPUSDT"})
    mgr2.try_open(o2, o2.f_obj, q2["UPUSDT"], q2)
    (tmp_path / "b" / "STOP").write_text("dur")                          # CLOSE yok: yalnızca yeni giriş durur
    r2.tick()
    assert mgr2.risk.emergency_stop and "UPUSDT" in mgr2.positions


def test_runner_survives_errors_and_auto_emergency(tmp_path):
    r, orch, mgr, ad, sc, clk, saved = mk(tmp_path, max_failures=3)
    orch.cycle = lambda *a, **k: (_ for _ in ()).throw(RuntimeError("boom"))
    r.run(max_ticks=4)
    assert r.failures >= 3 and mgr.risk.emergency_stop            # çökmedi, acil durdurdu
    assert len(saved) == 4


def test_paper_state_roundtrip_resumes(tmp_path):
    r, orch, mgr, ad, sc, clk, saved = mk(tmp_path)
    o = _forced(sc, ad, "UPUSDT")
    q = orch.quotes_for({"UPUSDT"})
    mgr.try_open(o, o.f_obj, q["UPUSDT"], q)
    broker = mgr.broker
    path = tmp_path / "paper_state.json"
    pstate.save(broker, path)
    fresh = PaperBroker({"USDT": D("1")}, broker.symbols, broker.book_fn)
    assert pstate.load(fresh, path)
    assert fresh.free == broker.free and fresh.locked == broker.locked and len(fresh.orders) == len(broker.orders)
    assert pstate.load(PaperBroker({}, {}, None), tmp_path / "yok.json") is False
    o1 = next(iter(fresh.orders.values()))
    assert o1.status == next(iter(broker.orders.values())).status


def test_next_scan_persists_and_once_mode_does_not_sleep(tmp_path):
    r, orch, mgr, ad, sc, clk, saved = mk(tmp_path)
    t0 = clk.t
    r.run(max_ticks=1)                                    # tek tur: uyumadan çıkar (cron modu)
    assert clk.t == t0 and r.next_scan > t0
    r2 = Runner(orch, mgr, tmp_path, clock=clk, sleep=clk.sleep)   # "yeni süreç": tarama zamanı diskten gelir
    assert r2.next_scan == r.next_scan
    calls = []
    oc, om = orch.cycle, orch.manage_only
    orch.cycle = lambda *a, **k: (calls.append("scan"), oc(*a, **k))[1]
    orch.manage_only = lambda: (calls.append("manage"), om())[1]
    r2.run(max_ticks=1)
    assert calls == ["manage"]                            # saat dolmadan yeniden TAM tarama yapmaz
