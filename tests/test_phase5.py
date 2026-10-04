import math
from decimal import Decimal as D

import numpy as np
import pandas as pd
import pytest

from app.backtest import metrics as M
from app.backtest.ablation import run_ablation
from app.backtest.engine import (BacktestCfg, Backtester, SignalCache, make_variant, with_params)
from app.backtest.walkforward import default_grid, walk_forward
from app.config import Settings
from app.data.history import HistoryStore
from app.exchange.models import Kline
from app.strategies.base import Signal, SignalResult
from tests.synth import H, gbm_df

T0 = 1_700_000_000_000


def bars_df(rows):
    """rows: [(o,h,l,c), ...] sabit hacim."""
    out = []
    for i, (o, h, l, c) in enumerate(rows):
        out.append(dict(open_time=T0 + i * H, close_time=T0 + (i + 1) * H - 1, open=o, high=h, low=l, close=c,
                        volume=1000.0, quote_volume=1000.0 * c, taker_buy_quote=500.0 * c, trades=10))
    return pd.DataFrame(out)


class Scripted:
    """Belirli barın kapanışında BUY verir. stop/tp mesafeleri fiyat birimi."""

    def __init__(self, df, signal_bars, stop_d=2.0, tp1_d=3.0, tp2_d=6.0):
        self.cts = {int(df["close_time"].iloc[i]) for i in signal_bars}
        self.d = (stop_d, tp1_d, tp2_d)

    def decide(self, f, outputs):
        if f.last_close_time in self.cts:
            s, a, b = self.d
            return SignalResult("scripted", Signal.BUY, 0.9, f.price, f.price - s, f.price + a, f.price + b, 1.0, 10.0,
                                "test", {"score": 90})
        return SignalResult("scripted", Signal.HOLD)


def flat_then(path, n_flat=60, price=100.0):
    rows = [(price, price * 1.001, price * 0.999, price)] * n_flat
    return bars_df(rows + path)


def run(df, signal_bar, cfg=None, settings=None, sym="XUSDT", **kw):
    s = settings or Settings()
    cache = SignalCache({sym: df}, s)
    cfg = cfg or BacktestCfg(warmup_bars=30)
    return Backtester(cache, Scripted(df, [signal_bar], **kw), s, cfg).run(), cache


# ---------- metrikler ----------
def test_metrics_known_values():
    eq = [(i * 3_600_000, v) for i, v in enumerate([100, 110, 99, 121])]
    tr = [M.Trade("A", 0, 1, 1, 1, 1, p, p, 1) for p in (10, -5, 5)]
    m = M.compute(eq, tr, 3_600_000)
    assert m["total_return"] == pytest.approx(0.21)
    assert m["max_drawdown"] == pytest.approx(0.1)
    assert m["profit_factor"] == pytest.approx(3.0) and m["win_rate"] == pytest.approx(2 / 3)
    assert m["avg_win"] == pytest.approx(7.5) and m["avg_loss"] == pytest.approx(-5) and m["expectancy"] == pytest.approx(10 / 3)
    assert m["sharpe"] > 0 and m["n_trades"] == 3
    flat = M.compute([(0, 100.0), (1, 100.0), (2, 100.0)], [], 3_600_000)
    assert flat["total_return"] == 0 and flat["sharpe"] == 0 and flat["max_drawdown"] == 0


# ---------- zamanlama + maliyet ----------
def test_entry_fills_at_next_open_with_costs_and_costs_hurt():
    df = flat_then([(100, 100.1, 99.9, 100)] * 40)
    r, _ = run(df, signal_bar=70)
    assert r.n_entries == 1 and len(r.trades) == 1
    t = r.trades[0]
    assert t.entry_ts == int(df["close_time"].iloc[71])               # sinyal 70'te, dolum 71'de (sonraki bar)
    assert t.entry_price == pytest.approx(100.0 * (1 + 5 / 10_000))   # open * (1 + kayma + yarım spread)
    assert t.reasons == ["END_OF_TEST"] and t.pnl < 0                 # düz piyasada net maliyet
    assert 0.002 < -t.pnl_pct / 100 < 0.006                           # ~%0.1+%0.1 komisyon + 2x5bps
    free, _ = run(df, 70, BacktestCfg(warmup_bars=30, fee_rate=0, slippage_bps=0, half_spread_bps=0))
    assert abs(free.trades[0].pnl) < 1e-6                              # maliyetsiz düz piyasada ~0
    assert r.equity[-1][1] < free.equity[-1][1]


def test_zero_latency_is_forbidden():
    with pytest.raises(ValueError):
        BacktestCfg(latency_bars=0)


def test_higher_costs_monotonically_lower_final_equity():
    s = Settings()
    data = {f"S{k}USDT": gbm_df(2500, seed=k) for k in range(3)}
    cache = SignalCache(data, s)
    s2 = with_params(s, {"scoring.buy_score_threshold": 55.0, "scoring.min_strategy_votes": 1})
    ends = []
    for fee in (0.0, 0.001, 0.003):
        cfg = BacktestCfg(fee_rate=fee, slippage_bps=fee * 10_000 / 4, half_spread_bps=0)
        ends.append(Backtester(cache, make_variant(s2, cache), s2, cfg).run().equity[-1][1])
    assert ends[0] > ends[1] > ends[2]


# ---------- pozisyon kuralları (bar OHLC, kötümser) ----------
def test_stop_hit_and_gap_down():
    # giriş ~100.05, stop ~98.05. 73. barda low 97 -> STOP
    df = flat_then([(100, 100.2, 99.8, 100)] * 2 + [(100, 100.1, 97.0, 98.5)] + [(98.5, 99, 98, 98.5)] * 10)
    r, _ = run(df, 60 + 0)                                             # sinyal 60 -> dolum 61
    t = r.trades[0]
    assert t.reasons == ["STOP"] and t.pnl < 0 and t.r_multiple < -0.9 and t.r_multiple > -1.6
    # gap aşağı: open stop'un altında -> open'dan dolum (stop fiyatından KÖTÜ)
    df2 = flat_then([(100, 100.2, 99.8, 100)] * 2 + [(95.0, 95.5, 94.0, 94.5)] * 10)
    r2, _ = run(df2, 60)
    assert r2.trades[0].reasons == ["STOP"] and r2.trades[0].exit_price < 98.05 * 0.99 and r2.trades[0].exit_price < 96


def test_partial_tp1_then_tp2():
    # R=2: tp1=+3, tp2=+6, giriş ~100.05
    df = flat_then([(100, 100.2, 99.8, 100), (100.1, 103.6, 102.2, 103.0), (103, 106.8, 102.5, 106.5)] + [(106, 106.5, 105.5, 106)] * 5)
    r, _ = run(df, 60)
    t = r.trades[0]
    assert t.reasons == ["TP1_PARTIAL", "TP2"] and t.pnl > 0 and t.r_multiple > 1.5


def test_pessimistic_same_bar_breakeven_exit():
    # bar yüksek 102.4 (>= +1R -> BE) ama TP1(103) yok; low 100.0 <= BE stop(~100.3) -> kötümser: çıkış
    df = flat_then([(100, 100.2, 99.8, 100), (100.1, 102.4, 100.0, 101.5)] + [(101.5, 101.8, 101.2, 101.5)] * 5)
    r, _ = run(df, 60)
    t = r.trades[0]
    assert t.reasons == ["TRAILING/BE_STOP"] and abs(t.pnl_pct) < 1.0     # ~başabaş (maliyet kadar), kazançta KALMAZ


# ---------- look-ahead / sahte kâr yok ----------
def test_future_data_cannot_change_past_trades():
    s = with_params(Settings(), {"scoring.buy_score_threshold": 55.0, "scoring.min_strategy_votes": 1})
    a = {f"S{k}USDT": gbm_df(1500, seed=k) for k in range(3)}
    b = {k: v.copy() for k, v in a.items()}
    for v in b.values():
        v.loc[1200:, ["open", "high", "low", "close"]] *= 1.7             # geleceği çarpıt
    ra = Backtester(SignalCache(a, s), make_variant(s, SignalCache(a, s)), s)
    cache_a, cache_b = SignalCache(a, s), SignalCache(b, s)
    ta = Backtester(cache_a, make_variant(s, cache_a), s).run().trades
    tb = Backtester(cache_b, make_variant(s, cache_b), s).run().trades
    cut = int(a["S0USDT"]["close_time"].iloc[1190])
    key = lambda t: (t.symbol, t.entry_ts, round(t.entry_price, 8), t.exit_ts, round(t.pnl, 8))  # noqa: E731
    pa, pb = sorted(key(t) for t in ta if t.exit_ts < cut), sorted(key(t) for t in tb if t.exit_ts < cut)
    assert pa and pa == pb


def test_pure_noise_has_no_edge_after_costs():
    s = with_params(Settings(), {"scoring.buy_score_threshold": 55.0, "scoring.min_strategy_votes": 1})
    rets, fees = [], []
    for seed in range(4):
        data = {f"S{k}USDT": gbm_df(3000, seed=100 * seed + k) for k in range(4)}
        cache = SignalCache(data, s)
        r = Backtester(cache, make_variant(s, cache), s).run()
        rets.append(r.metrics["total_return"])
        fees.append(r.metrics["fees_total"])
        assert r.metrics["n_trades"] > 20
    assert np.mean(rets) < 0 and max(rets) < 0.05 and min(fees) > 0


# ---------- risk entegrasyonu ----------
def test_risk_engine_limits_concurrent_positions():
    s = Settings()
    s.risk.max_open_positions = 1
    dfa = flat_then([(100, 100.2, 99.8, 100)] * 30)
    dfb = flat_then([(100, 100.2, 99.8, 100)] * 30)
    cache = SignalCache({"AUSDT": dfa, "BUSDT": dfb}, s)

    class Both(Scripted):
        pass
    v = Both(dfa, [60])
    r = Backtester(cache, v, s, BacktestCfg(warmup_bars=30)).run()
    assert r.n_signals == 2 and r.n_entries == 1
    assert any("pozisyon sayısı" in k for k in r.rejections)


def test_with_params_does_not_mutate():
    s = Settings()
    s2 = with_params(s, {"scoring.buy_score_threshold": 99.0, "position.trail_start_r": 5.0})
    assert s.scoring.buy_score_threshold == 65.0 and s2.scoring.buy_score_threshold == 99.0 and s2.position.trail_start_r == 5.0


# ---------- walk-forward / ablation ----------
def test_walk_forward_windows_do_not_overlap_and_holdout_is_untouched():
    s = Settings()
    data = {f"S{k}USDT": gbm_df(4000, seed=40 + k) for k in range(3)}
    cache = SignalCache(data, s)
    cfg = BacktestCfg()
    grid = default_grid()[:2]
    wf = walk_forward(cache, s, cfg, grid=grid, train_days=40, test_days=15, holdout_frac=0.25, min_trades=1)
    assert len(wf.folds) >= 2
    hold_start = wf.holdout_window[0]
    for f in wf.folds:
        assert f.train[1] < f.test[0] and f.test[1] < hold_start        # train < test < holdout
    for a, b in zip(wf.folds, wf.folds[1:]):
        assert a.test[1] < b.test[0]                                      # OOS pencereleri çakışmaz
    assert all(t.exit_ts < hold_start for t in wf.oos_trades)             # holdout seçimde HİÇ kullanılmadı
    assert wf.holdout_chosen is not None and wf.holdout_default is not None
    assert wf.chosen in [g for g in grid]
    assert isinstance(wf.verdict, list) and wf.verdict


def test_ablation_rows_and_baseline_delta():
    s = Settings()
    data = {f"S{k}USDT": gbm_df(1800, seed=60 + k) for k in range(3)}
    cache = SignalCache(data, s)
    tl = cache.prep["S0USDT"].a["ct"]
    rows = run_ablation(cache, s, BacktestCfg(), int(tl[250]), int(tl[-1]))
    labels = [r["variant"] for r in rows]
    assert labels[0].startswith("BASELINE") and rows[0]["d_return"] == 0
    for n in ("trend", "momentum", "breakout", "volume_breakout", "mean_reversion", "volatility_breakout"):
        assert any(f"- {n} çıkarıldı" == x for x in labels) and any(f"yalnızca {n}" == x for x in labels)
    assert any("risk motoru KAPALI" in x for x in labels) and len(rows) == 1 + 6 + 6 + 6 + 3


# ---------- geçmiş veri yükleyici ----------
class PagedAd:
    def __init__(self, df, limit_log):
        self.df, self.log = df, limit_log

    def klines(self, sym, interval, limit, start_ms=None, end_ms=None):
        d = self.df[self.df["open_time"] >= (start_ms or 0)].head(limit)
        self.log.append((start_ms, len(d)))
        return [Kline(int(r.open_time), D(str(r.open)), D(str(r.high)), D(str(r.low)), D(str(r.close)), D(str(r.volume)),
                      int(r.close_time), D(str(r.quote_volume)), 10, D("1"), D(str(r.taker_buy_quote))) for r in d.itertuples()]


def test_history_pagination_incremental_and_forming_candle(tmp_path):
    df = gbm_df(2500, seed=3, start_ms=T0)
    now = int(df["close_time"].iloc[2399]) + 100          # son 100 bar henüz "gelecek"; 2400. bar oluşuyor sayılmaz
    log = []
    st = HistoryStore(PagedAd(df, log), tmp_path)
    full = st.update("XUSDT", "1h", days=200, now_ms=now)
    assert len(log) >= 3 and full["open_time"].is_unique and full["close_time"].max() < now
    assert len(full) == 2400
    n_calls = len(log)
    now2 = int(df["close_time"].iloc[2499]) + 100
    full2 = st.update("XUSDT", "1h", days=200, now_ms=now2)
    assert len(full2) == 2500 and len(log) - n_calls <= 2   # yalnızca yeni kısım indirildi
    assert full2["open_time"].is_monotonic_increasing


def test_wf_chained_exposure_is_consistent_and_research_report(tmp_path):
    from pathlib import Path
    from app.backtest.research import ResearchOpts, run_research
    s = Settings()
    data = {f"S{k}USDT": gbm_df(3000, seed=70 + k) for k in range(3)}
    cfg = BacktestCfg()
    txt = run_research(data, s, cfg, ResearchOpts(train_days=30, test_days=15, holdout_frac=0.25, quick=True, min_trades=1,
                                                  synthetic=True), report_dir=Path(tmp_path), progress=lambda m: None)
    for key in ("SENTETİK", "BASELINE", "KIYAS", "KATKI ANALİZİ", "WALK-FORWARD", "OUT-OF-SAMPLE", "HOLDOUT", "HÜKÜMLER",
                "SINIRLAMALAR", "KÂR GARANTİSİ DEĞİLDİR", "survivorship"):
        assert key in txt, key
    assert list(Path(tmp_path).glob("research_*.txt")) and (Path(tmp_path) / "baseline_trades.csv").exists()
    # zincirlenmiş OOS maruziyeti makul aralıkta (önceki hata: kat sayısı bar sayısı gibi kullanılıyordu)
    cache = SignalCache(data, s)
    wf = walk_forward(cache, s, cfg, grid=default_grid()[:2], train_days=30, test_days=15, holdout_frac=0.25, min_trades=1)
    exp = wf.oos_metrics["exposure"]
    folds_exp = [f.oos_metrics["exposure"] for f in wf.folds]
    assert 0 <= exp <= 1 and min(folds_exp) - 0.05 <= exp <= max(folds_exp) + 0.05


def test_edge_mode_keeps_trading_after_drawdown_while_real_mode_latches():
    s = with_params(Settings(), {"scoring.buy_score_threshold": 50.0, "scoring.min_strategy_votes": 1,
                                 "risk.max_drawdown_pct": 0.03})
    data = {f"S{k}USDT": gbm_df(3000, seed=200 + k) for k in range(3)}
    cache = SignalCache(data, s)
    real = Backtester(cache, make_variant(s, cache), s, BacktestCfg()).run()
    edge = Backtester(cache, make_variant(s, cache), s, BacktestCfg(latch_resets_daily=True)).run()
    assert real.events and any("drawdown" in k for k in real.rejections)
    assert edge.n_entries > real.n_entries                       # kilit günlük açılınca daha çok işlem görür
    assert sum(v for k, v in edge.rejections.items() if "drawdown" in k) < sum(v for k, v in real.rejections.items() if "drawdown" in k)


def test_random_control_is_deterministic_and_summarized():
    from app.backtest.control import random_control, summarize
    s = Settings()
    data = {f"S{k}USDT": gbm_df(1500, seed=300 + k) for k in range(3)}
    cache = SignalCache(data, s)
    tl = cache.timeline_all()
    cfg = BacktestCfg()
    a = random_control(cache, s, cfg, tl[300], tl[-1], n_signals=60, n_runs=3)
    b = random_control(cache, s, cfg, tl[300], tl[-1], n_signals=60, n_runs=3)
    assert a == b and len(a) == 3 and all(x["trades"] > 0 for x in a)
    sm = summarize(a, baseline_return=0.0)
    assert 0 <= sm["pctile"] <= 1 and sm["min"] <= sm["median"] <= sm["max"]


def test_assess_says_no_edge_not_overfit_when_training_never_wins():
    from collections import Counter
    from app.backtest.walkforward import Fold, WFResult, assess
    m = {"n_trades": 100, "total_return": -0.2, "sharpe": -3.0}
    folds = [Fold(i, (0, 1), (2, 3), {"a.b": i}, -2.0, -3.0, m, m, 18, -2.5) for i in range(1, 7)]
    r = WFResult(folds, [], [], m, {}, Counter(f"p{i}" for i in range(6)), None, None, (0, 0))
    v = " ".join(assess(r, "sharpe", 10))
    assert "KENAR YOKLUĞU" in v and "Parametre kararsız" not in v
    folds2 = [Fold(i, (0, 1), (2, 3), {"a.b": i}, 2.0, -3.0, m, m, 18, 1.0) for i in range(1, 7)]
    r2 = WFResult(folds2, [], [], m, {}, Counter(f"p{i}" for i in range(6)), None, None, (0, 0))
    v2 = " ".join(assess(r2, "sharpe", 10))
    assert "aşırı uyum" in v2.lower() and "KENAR YOKLUĞU" not in v2


def test_control_summary_uses_per_trade_expectancy_and_quick_grid_covers_thresholds():
    from app.backtest.control import summarize
    from app.backtest.walkforward import quick_grid
    ctrl = [{"return": -0.981, "pf": 0.5, "exp_pct": -0.53, "trades": 4000, "sharpe": -4} for _ in range(5)]
    sm = summarize(ctrl, baseline_return=-0.9795, baseline_exp_pct=-0.56)
    assert sm["exp_edge_pp"] == pytest.approx(-0.03) and sm["pctile_exp"] == 0.0   # getiri kıyası %100 derdi; beklenti kıyası DEĞİL
    assert {g["scoring.buy_score_threshold"] for g in quick_grid()} == {58.0, 65.0, 72.0}


def test_decision_block_requires_all_criteria():
    from collections import Counter
    from app.backtest.research import decision_block
    from app.backtest.walkforward import Fold, WFResult
    good = {"n_trades": 300, "total_return": 0.2, "profit_factor": 1.4, "expectancy_pct": 0.5, "sharpe": 1.0}
    f = [Fold(1, (0, 1), (2, 3), {}, 1.0, 1.0, good, good, 6, 0.5)]
    r = WFResult(f, [], [], good, {}, Counter(), good, good, (0, 0))
    ok = decision_block(r, {"mean_exp_pct": 0.0})
    assert "KALDI" not in ok and "TÜM kriterler geçti" in ok
    bad_h = {**good, "total_return": -0.1}
    r2 = WFResult(f, [], [], good, {}, Counter(), bad_h, good, (0, 0))
    assert "KALDI — " in decision_block(r2, {"mean_exp_pct": 0.0})
    assert "KALDI — " in decision_block(r, {"mean_exp_pct": 0.4})       # rastgeleye göre +0.30 puan yok
    assert "KALDI — " in decision_block(r, None)
