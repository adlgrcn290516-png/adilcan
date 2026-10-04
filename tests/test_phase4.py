import math
from dataclasses import replace
from decimal import Decimal as D

import numpy as np
import pandas as pd
import pytest

from app.config import RiskCfg, Settings
from app.core.features import compute_features
from app.core.orchestrator import Orchestrator
from app.core.scanner import MarketScanner, Opportunity
from app.data.db import SqliteDb, SqliteOrderRepository, SqlitePortfolioRepository
from app.exchange.models import OrderBook, SymbolInfo
from app.execution.engine import ExecutionEngine
from app.execution.types import OrderState, Side, Status
from app.paper.broker import PaperBroker
from app.portfolio.manager import PortfolioManager, Quote
from app.portfolio.position import Position
from app.risk.engine import ApiHealth, Decision, PortfolioState, RiskEngine
from app.risk.sizing import risk_based_qty
from app.strategies.base import Signal
from app.utils.retry import RateLimitBanned
from tests.synth import grind_up, make_df

SYM = SymbolInfo("BTCUSDT", "BTC", "USDT", "TRADING", D("0.01"), D("0.00001"), D("0.00001"), D("9000"), D("5"), True)
INFO = {"BTCUSDT": SYM}


def base_features(**kw):
    f = compute_features("BTCUSDT", make_df(grind_up(260)), None, None)
    d = dict(price=100.0, atr=1.0, atr_pct=1.0, spread_pct=0.02, depth_quote_1pct=1e6, last_range_atr=1.0)
    d.update(kw)
    return replace(f, **d)


def pstate(equity=10000, free=None, exposure=0, positions=(), sod=None, peak=None):
    eq = D(str(equity))
    return PortfolioState(eq, D(str(free if free is not None else equity)), D(str(exposure)), list(positions),
                          D(str(sod if sod is not None else equity)), D(str(peak if peak is not None else equity)))


def evaluate(f=None, st=None, entry="100", stop="98", cfg=None, returns=None, risk=None):
    risk = risk or RiskEngine(cfg or RiskCfg())
    return risk.evaluate_entry(f or base_features(), D(entry), D(stop) if stop else None, SYM, st or pstate(), returns), risk


def dummy_pos(sym="ETHUSDT"):
    return Position(sym, D(1), D(100), D(98), D(103), D(106), D(98), D(100))


# ---------- sizing ----------
def test_risk_based_sizing_formula():
    assert risk_based_qty(D(10000), 0.01, D(100), D(98)) == D(50)   # 100$ risk / 2$ stop
    with pytest.raises(ValueError):
        risk_based_qty(D(1000), 0.01, D(100), D(100))


def test_approve_uses_min_of_risk_and_size_cap():
    v, _ = evaluate(st=pstate(10000))
    assert v.decision is Decision.APPROVE and v.qty == D(20)        # risk 50, cap %20 -> 2000$/100 = 20
    assert v.requested_qty == D(50)
    v2, _ = evaluate(st=pstate(1000))
    assert v2.qty == D(2)


# ---------- reject ----------
def test_rejects_each_hard_limit_and_lists_all_reasons():
    cases = {
        "spread": base_features(spread_pct=0.5), "likidite": base_features(depth_quote_1pct=1000.0),
        "volatilite": base_features(atr_pct=9.0), "ani hareket": base_features(last_range_atr=6.0)}
    for key, f in cases.items():
        v, _ = evaluate(f)
        assert v.decision is Decision.REJECT and any(key in r for r in v.reasons), (key, v.reasons)
    # birden çok neden birlikte raporlanır
    v, _ = evaluate(base_features(spread_pct=0.5, atr_pct=9.0))
    assert v.decision is Decision.REJECT and len(v.reasons) >= 2


def test_rejects_without_valid_stop():
    assert evaluate(stop=None)[0].decision is Decision.REJECT
    v, _ = evaluate(stop="100")
    assert v.decision is Decision.REJECT and any("stop" in r for r in v.reasons)


def test_emergency_daily_loss_positions_api():
    r = RiskEngine(RiskCfg())
    r.emergency_stop = True
    assert evaluate(risk=r)[0].decision is Decision.REJECT
    v, _ = evaluate(st=pstate(equity=950, sod=1000, peak=1000))   # -5% > %3 limit
    assert v.decision is Decision.REJECT and any("günlük" in x for x in v.reasons)
    v, _ = evaluate(st=pstate(positions=[dummy_pos(f"X{i}USDT") for i in range(5)]))
    assert v.decision is Decision.REJECT and any("pozisyon sayısı" in x for x in v.reasons)
    h = ApiHealth()
    for _ in range(5):
        h.error()
    v, _ = evaluate(risk=RiskEngine(RiskCfg(), h))
    assert v.decision is Decision.REJECT and any("API" in x for x in v.reasons)
    h.ok()
    assert evaluate(risk=RiskEngine(RiskCfg(), h))[0].decision is Decision.APPROVE


def test_already_open_symbol_rejected():
    v, _ = evaluate(st=pstate(positions=[dummy_pos("BTCUSDT")]))
    assert v.decision is Decision.REJECT


def test_drawdown_latches_until_manual_reset():
    r = RiskEngine(RiskCfg())
    v, _ = evaluate(st=pstate(equity=830, sod=830, peak=1000), risk=r)       # %17 > %15
    assert v.decision is Decision.REJECT and r.drawdown_latched
    # equity toparlansa bile kilit açılmaz
    v, _ = evaluate(st=pstate(equity=1000, sod=1000, peak=1000), risk=r)
    assert v.decision is Decision.REJECT and any("drawdown" in x for x in v.reasons)
    r.reset_drawdown_latch()
    assert evaluate(st=pstate(equity=1000), risk=r)[0].decision is Decision.APPROVE


# ---------- reduce ----------
def test_reduce_for_exposure_balance_depth_correlation():
    # maruziyet: limit %60 -> 6000, mevcut 5900 -> yer 100$ => qty 1
    v, _ = evaluate(st=pstate(10000, exposure=5900))
    assert v.decision is Decision.REDUCE and v.qty == D(1) and any("maruziyet" in x for x in v.reasons)
    v, _ = evaluate(st=pstate(10000, free=500))      # rezerv %5=500 -> harcanabilir 0 -> reject
    assert v.decision is Decision.REJECT
    v, _ = evaluate(st=pstate(10000, free=1500))     # harcanabilir 1000$ -> qty 10
    assert v.decision is Decision.REDUCE and v.qty == D(10)
    v, _ = evaluate(base_features(depth_quote_1pct=100_000.0))   # emir <= derinliğin %5 = 5000$ -> 50 qty > 20? cap 20 -> yok
    assert v.decision is Decision.APPROVE
    v, _ = evaluate(base_features(depth_quote_1pct=60_000.0))    # %5 = 3000$ -> 30 > 20 değil: APPROVE
    assert v.decision is Decision.APPROVE
    v, _ = evaluate(base_features(depth_quote_1pct=50_000.0), cfg=RiskCfg(max_order_depth_pct=2.0))  # 1000$ -> 10
    assert v.decision is Decision.REDUCE and v.qty == D(10)
    rng = np.random.default_rng(1)
    s = pd.Series(rng.normal(0, 0.01, 100))
    rets = {"BTCUSDT": s, "ETHUSDT": s * 1.0}      # mükemmel korelasyon
    v, _ = evaluate(st=pstate(positions=[dummy_pos("ETHUSDT")]), returns=rets)
    assert v.decision is Decision.REDUCE and v.qty == D(10) and any("korelasyon" in x for x in v.reasons)
    rets2 = {"BTCUSDT": s, "ETHUSDT": pd.Series(rng.normal(0, 0.01, 100))}   # ilintisiz
    assert evaluate(st=pstate(positions=[dummy_pos("ETHUSDT")]), returns=rets2)[0].decision is Decision.APPROVE


def test_reject_when_reduced_below_min_order_and_full_exposure():
    v, _ = evaluate(st=pstate(10000, exposure=5999.9))   # yer 0.1$ -> min notional 5
    assert v.decision is Decision.REJECT and any("minimum" in x for x in v.reasons) or v.decision is Decision.REJECT
    v, _ = evaluate(st=pstate(10000, exposure=6000))
    assert v.decision is Decision.REJECT


# ---------- position ----------
def test_position_stop_only_ratchets_up_and_requires_valid_stop():
    p = Position("BTCUSDT", D(1), D(100), D(98), D(103), D(106), D(98), D(100))
    p.highest = D(110)
    assert p.raise_stop(D(101)) and p.stop == D(101)
    assert not p.raise_stop(D(99)) and p.stop == D(101)          # aşağı çekilemez
    assert not p.raise_stop(D(101))                              # eşit -> değişmez
    with pytest.raises(ValueError):
        Position("BTCUSDT", D(1), D(100), D(100), D(103), D(106), D(100), D(100))
    assert Position.from_dict(p.to_dict()).stop == D(101)


# ---------- manager ----------
class Env:
    def __init__(self, usdt="10000", settings=None):
        self.px = {"BTCUSDT": (D("99.99"), D("100.00"))}
        self.s = settings or Settings()
        book = lambda sym: OrderBook(sym, 1, [(self.px[sym][0], D("100000"))], [(self.px[sym][1], D("100000"))])  # noqa
        self.broker = PaperBroker({"USDT": D(usdt)}, INFO, book)
        db = SqliteDb(":memory:")
        self.repo = SqlitePortfolioRepository(db)
        self.risk = RiskEngine(self.s.risk)
        self.eng = ExecutionEngine(self.broker, INFO, SqliteOrderRepository(db), self.s, sleep=lambda _: None)
        self.mgr = PortfolioManager(self.broker, self.eng, self.risk, INFO, self.s, self.repo)
        self.t = 1_700_000_000.0

    def set(self, bid, ask=None):
        self.px["BTCUSDT"] = (D(str(bid)), D(str(ask if ask is not None else bid)) + D("0.01"))

    def quotes(self):
        return {s: Quote(*v) for s, v in self.px.items()}

    def opp(self, ts=None):
        f = base_features()
        return Opportunity("BTCUSDT", "SPOT", 80, Signal.BUY, 0.8, 100.0, 98.0, 103.0, 106.0, 10.0, "test",
                           f_obj=f, ts=ts or self.t)

    def open(self, **kw):
        o = self.opp(**kw)
        return self.mgr.try_open(o, o.f_obj, Quote(D("99.99"), D("100.00")), self.quotes())


def test_open_position_levels_balance_delta_and_persistence():
    e = Env()
    r = e.open()
    p = r.position
    assert r.verdict.decision is Decision.APPROVE and p and p.id
    assert p.qty == D("19.98000")                          # 20 alındı, %0.1 komisyon base'den düştü (bakiye farkı)
    assert p.stop < p.entry_price < p.tp1 < p.tp2 and p.risk_dist == D(2)
    assert e.broker.free_balance("BTC") == p.qty
    assert [d["symbol"] for d in e.repo.open_positions()] == ["BTCUSDT"]
    assert e.repo.trades()[0]["side"] == "BUY"
    assert any(ev["decision"] == "APPROVE" for ev in e.repo.risk_events())


def test_stale_signal_rejected():
    e = Env()
    o = e.opp()
    r = e.mgr.try_open(o, o.f_obj, Quote(D("104.99"), D("105.00")), e.quotes())  # ask 5 > 1 ATR uzakta
    assert r.position is None and "uzaklaştı" in r.note


def test_breakeven_partial_tp1_tp2_flow_and_pnl():
    e = Env()
    p = e.open().position
    entry = p.entry_price
    # +1R: break-even
    e.set(entry + 2)
    assert e.mgr.manage(e.quotes()) == [] and p.breakeven_done and p.stop > entry
    # TP1: kısmi satış + stop BE'de kalır
    e.set(p.tp1 + D("0.05"))
    ev = e.mgr.manage(e.quotes())
    assert [x.reason for x in ev] == ["TP1_PARTIAL"] and p.partial_taken and p.status == "OPEN"
    assert p.qty < D("19.98") and ev[0].pnl > 0
    # TP2: kalanı sat -> kapanır
    e.set(p.tp2 + D("0.05"))
    ev = e.mgr.manage(e.quotes())
    assert [x.reason for x in ev] == ["TP2"] and p.status == "CLOSED" and "BTCUSDT" not in e.mgr.positions
    assert p.realized_pnl > 0 and e.broker.free_balance("USDT") > D("10000")
    assert e.broker.free_balance("BTC") < SYM.min_qty          # REGRESYON: iki çıkış da gerçekten satıldı (çift sayım yok)
    assert [t["side"] for t in e.repo.trades()] == ["BUY", "SELL", "SELL"]
    assert e.repo.open_positions() == []


def test_trailing_stop_ratchets_and_stop_loss_exit():
    e = Env()
    p = e.open().position
    entry = p.entry_price
    e.set(entry + D("3.5"))                         # +1.75R: BE + trailing başlar (TP1 +3R'de)
    e.mgr.manage(e.quotes())
    assert p.stop >= p.highest - D(2) and p.stop > entry
    s1 = p.stop
    e.set(entry + D("2.5"))                          # geri çekilme: stop AŞAĞI inmez
    e.mgr.manage(e.quotes())
    assert p.stop == s1
    e.set(p.stop - D("0.5"))                         # stop'a değdi
    ev = e.mgr.manage(e.quotes())
    assert [x.reason for x in ev] == ["TRAILING/BE_STOP"] and p.status == "CLOSED"


def test_initial_stop_loss_exit_realizes_loss():
    e = Env()
    p = e.open().position
    e.set(p.stop - D("0.5"))
    ev = e.mgr.manage(e.quotes())
    assert ev[0].reason == "STOP" and ev[0].pnl < 0 and p.realized_pnl < 0
    assert e.broker.free_balance("USDT") < D("10000")


def test_emergency_stop_blocks_entries_keeps_managing_and_can_close_all():
    e = Env()
    p = e.open().position
    e.mgr.set_emergency(True)
    r = e.open(ts=e.t + 1)
    assert r.position is None and any("EMERGENCY" in x for x in r.verdict.reasons)
    e.set(p.stop - D("1"))
    assert e.mgr.manage(e.quotes())[0].reason in ("STOP", "TRAILING/BE_STOP")   # yönetim sürer (SELL serbest)
    # ikinci pozisyon + close_positions
    e2 = Env()
    e2.open()
    ev = e2.mgr.set_emergency(True, close_positions=True, quotes=e2.quotes())
    assert [x.reason for x in ev] == ["EMERGENCY"] and not e2.mgr.positions
    assert any(x["decision"] == "EMERGENCY" for x in e2.repo.risk_events())


def test_daily_loss_limit_blocks_new_entries():
    e = Env()
    e.mgr.state(e.quotes())                    # gün başı equity = 10000
    e.broker.free["USDT"] = D("9500")          # -%5 (limit %3)
    r = e.open()
    assert r.position is None and any("günlük" in x for x in r.verdict.reasons)


def test_restart_loads_positions_and_reconcile_flags_mismatch():
    e = Env()
    e.open()
    mgr2 = PortfolioManager(e.broker, e.eng, e.risk, INFO, e.s, e.repo)       # "yeniden başlatma"
    assert "BTCUSDT" in mgr2.positions and mgr2.positions["BTCUSDT"].stop == e.mgr.positions["BTCUSDT"].stop
    assert mgr2.reconcile() == []
    e.broker.free["BTC"] = D(0)                # borsada coin yok (elle satıldı)
    assert any("BTCUSDT" in i for i in mgr2.reconcile())


def test_failed_exit_is_retried_with_new_intent(monkeypatch):
    e = Env()
    p = e.open().position
    e.set(p.stop - D("0.5"))
    real = e.eng.place
    calls = {"n": 0}

    def flaky(req):
        calls["n"] += 1
        if calls["n"] == 1:
            return OrderState(req.client_order_id, req.symbol, req.side, req.type, Status.REJECTED, req.quantity,
                              reason="test reddi")
        return real(req)
    monkeypatch.setattr(e.eng, "place", flaky)
    assert e.mgr.manage(e.quotes()) == [] and p.status == "OPEN" and p.exit_seq == 1
    ev = e.mgr.manage(e.quotes())
    assert ev and p.status == "CLOSED"


# ---------- orchestrator ----------
from tests.test_phase3 import FakeAd  # noqa: E402


class LiveishAd(FakeAd):
    def order_book(self, sym, limit=20):
        last = float(make_df(self.series[sym]).close.iloc[-1])
        bid, ask = D(str(round(last * 0.9999, 4))), D(str(round(last * 1.0001, 4)))
        return OrderBook(sym, 1, [(bid, D("100000"))], [(ask, D("100000"))])


def _orch(series, dry=False, usdt="10000"):
    s = Settings()
    ad = LiveishAd(series)
    info = ad.exchange_info()
    book = lambda sym: ad.order_book(sym)  # noqa
    broker = PaperBroker({"USDT": D(usdt)}, info, book)
    db = SqliteDb(":memory:")
    health = ApiHealth()
    risk = RiskEngine(s.risk, health)
    eng = ExecutionEngine(broker, info, SqliteOrderRepository(db), s, sleep=lambda _: None)
    repo = SqlitePortfolioRepository(db)
    mgr = PortfolioManager(broker, eng, risk, info, s, repo)
    sc = MarketScanner(ad, s)
    return Orchestrator(ad, sc, mgr, health, dry_run=dry), mgr, ad, sc, health, repo


def _forced(sc, ad, sym):
    o = sc.evaluate_symbol(sym, None, ad.server_time_ms())
    f = o.f_obj
    stop = f.price - 2 * f.atr
    o.signal, o.entry, o.stop = Signal.BUY, f.price, stop
    o.tp1, o.tp2 = f.price + 3 * f.atr, f.price + 6 * f.atr
    return o


def test_orchestrator_cycle_opens_manages_and_dry_run_does_nothing():
    series = {"UPUSDT": grind_up(260)}
    orch, mgr, ad, sc, health, repo = _orch(series)
    rep = orch.cycle([_forced(sc, ad, "UPUSDT")])
    assert len(rep.opened) == 1 and "UPUSDT" in mgr.positions and not rep.error
    assert len(repo.risk_events()) >= 1
    orch2, mgr2, ad2, sc2, *_ = _orch(series, dry=True)
    rep2 = orch2.cycle([_forced(sc2, ad2, "UPUSDT")])
    assert not mgr2.positions and rep2.skipped and "[DRY-RUN]" in rep2.skipped[0][1]


def test_orchestrator_api_failures_block_new_orders_and_ban_triggers_emergency():
    series = {"UPUSDT": grind_up(260)}
    orch, mgr, ad, sc, health, repo = _orch(series)
    o = _forced(sc, ad, "UPUSDT")
    ad.exchange_info = lambda: (_ for _ in ()).throw(TimeoutError("down"))
    for _ in range(5):
        orch.cycle()
    assert health.consecutive_errors >= 5
    ad.exchange_info = lambda: {"UPUSDT": SymbolInfo("UPUSDT", "UP", "USDT", "TRADING", D("0.01"), D("0.001"), D("0.001"), D("1e6"), D("5"), True)}
    rep = orch.cycle([o])
    assert not mgr.positions
    # IP ban -> emergency
    orch3, mgr3, ad3, sc3, *_ = _orch(series)
    ad3.klines = lambda *a, **k: (_ for _ in ()).throw(RateLimitBanned(60))
    # tarayıcı klines'ta ban yakalar ve yukarı fırlatır
    rep3 = orch3.cycle()
    assert mgr3.risk.emergency_stop and "ban" in rep3.error
