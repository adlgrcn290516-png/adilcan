import math
from dataclasses import replace
from decimal import Decimal as D

import pytest

from app.config import ScoringCfg, Settings, StrategyCfg, UniverseCfg
from app.core.features import closed_only, compute_features, klines_to_df
from app.core.ml import NullModel, PredictiveModel
from app.core.scanner import MarketScanner, filter_universe
from app.core.scoring import score
from app.data import indicators as ind
from app.data.db import SqliteDb, SqliteSignalRepository
from app.exchange.models import OrderBook, SymbolInfo, Ticker24h
from app.strategies.ai_composite import AIComposite
from app.strategies.base import Signal, SignalResult, Strategy
from app.strategies.library import (Breakout, MeanReversion, Momentum, TrendFollowing, VolatilityBreakout,
                                    VolumeBreakout)
from app.utils.retry import RateLimitBanned
from tests.synth import grind_up, make_df, sideways, to_klines
import pandas as pd


def feats(closes, vols=None, **kw):
    df = make_df(closes, vols)
    return compute_features("TESTUSDT", df, None, None, **kw)


# ---------- göstergeler / look-ahead ----------
def test_indicators_basic():
    assert ind.rsi(pd.Series([float(i) for i in range(1, 100)])).iloc[-1] == 100.0
    assert ind.rsi(pd.Series([5.0] * 60)).iloc[-1] == 50.0
    assert ind.ema(pd.Series([3.0] * 50), 10).iloc[-1] == 3.0
    df = make_df(sideways(80))
    assert ind.atr(df.high, df.low, df.close).iloc[-1] > 0
    ph = ind.prior_high(pd.Series([1, 2, 3, 10.0]), 3)
    assert ph.iloc[3] == 3  # GÜNCEL bar (10) dahil edilmez


def test_features_have_no_lookahead():
    closes = grind_up(220)
    df = make_df(closes)
    base = compute_features("X", df.iloc[:200].reset_index(drop=True), None, None)
    # gelecekteki barları tamamen değiştir: ilk 200 barın özellikleri AYNI kalmalı
    closes2 = closes[:200] + [closes[199] * 0.5] * 20
    df2 = make_df(closes2)
    again = compute_features("X", df2.iloc[:200].reset_index(drop=True), None, None)
    assert base.as_dict() == again.as_dict()


def test_forming_candle_is_dropped():
    df = make_df(sideways(100))
    now_inside_last = int(df.close_time.iloc[-1]) - 1000  # son mum henüz kapanmadı
    assert len(closed_only(df, now_inside_last)) == 99
    assert len(closed_only(df, int(df.close_time.iloc[-1]) + 1)) == 100


def test_insufficient_data_returns_none():
    assert compute_features("X", make_df(sideways(30)), None, None) is None


# ---------- stratejiler ----------
def test_flat_market_all_hold():
    f = feats(sideways(250))
    for S in (TrendFollowing, Momentum, Breakout, VolumeBreakout, MeanReversion, VolatilityBreakout):
        assert S().evaluate(f).signal is Signal.HOLD, S.__name__


def test_trend_following_buys_uptrend_with_valid_levels():
    r = TrendFollowing().evaluate(feats(grind_up(260)))
    assert r.signal is Signal.BUY and 0 < r.confidence <= 1
    assert r.stop_loss < r.entry_price < r.take_profit < r.take_profit_2
    assert r.stop_loss is not None  # stop ASLA boş değil


def test_trend_following_exits_downtrend():
    down = [100 * (0.996 ** i) for i in range(260)]
    r = TrendFollowing().evaluate(feats(down))
    assert r.signal is Signal.SELL


def _breakout_series():
    closes = sideways(150) + [102.0]            # önceki zirve ~100.4 -> belirgin kırılım
    vols = [1000.0] * 150 + [3500.0]
    return closes, vols


def test_breakout_and_volume_breakout():
    closes, vols = _breakout_series()
    f = feats(closes, vols)
    assert f.breakout_up and f.volume_ratio > 3
    b, vb = Breakout().evaluate(f), VolumeBreakout().evaluate(f)
    assert b.signal is Signal.BUY and vb.signal is Signal.BUY
    # düşük hacimle sadece breakout, volume_breakout değil
    f2 = feats(closes, [1000.0] * 151)
    assert Breakout().evaluate(f2).signal is Signal.BUY and VolumeBreakout().evaluate(f2).signal is Signal.HOLD


def test_overextended_breakout_is_not_chased():
    f = feats(sideways(150) + [130.0], [1000.0] * 150 + [4000.0])
    assert Breakout().evaluate(f).signal is Signal.HOLD


def test_mean_reversion_buys_sharp_selloff_in_range():
    closes = sideways(200) + [100 * (1 - 0.012 * k) for k in range(1, 8)]
    f = feats(closes)
    r = MeanReversion().evaluate(f)
    assert r.signal is Signal.BUY, (f.bb_zscore, f.rsi, f.ema50_slope_pct)
    assert r.take_profit >= f.bb_mid and r.stop_loss < r.entry_price


def test_mean_reversion_refuses_falling_knife():
    down = [100 * (0.99 ** i) for i in range(260)]  # sürekli çöküş
    assert MeanReversion().evaluate(feats(down)).signal is not Signal.BUY


def test_volatility_breakout_after_squeeze():
    noisy = [100 * (1 + 0.03 * math.sin(i / 1.5)) for i in range(100)]
    squeeze = [100 + 0.01 * math.sin(i) for i in range(60)]
    closes = noisy + squeeze + [101.5]
    vols = [1000.0] * (len(closes) - 1) + [2500.0]
    f = feats(closes, vols)
    assert f.squeeze_recent and f.price > f.bb_upper
    assert VolatilityBreakout().evaluate(f).signal is Signal.BUY


# ---------- skor ----------
def test_score_contributions_sum_to_composite_and_weights_are_config():
    f = feats(grind_up(260))
    cfg = ScoringCfg()
    s = score(f, cfg)
    raw = sum(c.points for c in s.contributions)
    assert s.composite == pytest.approx(max(0, min(100, raw)))
    assert all(c.points <= 0 for c in s.contributions if c.name == "Risk")
    # ağırlıkları ölçeklemek sonucu değiştirmez (normalize)
    cfg2 = ScoringCfg(trend_weight=0.5, momentum_weight=0.4, volume_weight=0.3, breakout_weight=0.3,
                      volatility_weight=0.1, liquidity_weight=0.4, risk_weight=0.25)
    assert score(f, cfg2).composite == pytest.approx(s.composite)
    # risk cezasını kaldırmak skoru artırır; trend ağırlığı değişince skor değişir
    assert score(f, ScoringCfg(risk_weight=0)).composite >= s.composite
    only_trend = ScoringCfg(trend_weight=1, momentum_weight=0, volume_weight=0, breakout_weight=0,
                            volatility_weight=0, liquidity_weight=0, risk_weight=0)
    assert score(f, only_trend).composite == pytest.approx(score(f, only_trend).contributions[0].score)


def test_score_ranges():
    for closes in (grind_up(260), sideways(250), [100 * 0.995 ** i for i in range(260)]):
        s = score(feats(closes), ScoringCfg())
        assert 0 <= s.composite <= 100 and all(0 <= c.score <= 100 for c in s.contributions)


# ---------- AI composite ----------
class Fixed(Strategy):
    def __init__(self, name, sig, conf=0.8):
        super().__init__()
        self.name, self._sig, self._conf = name, sig, conf

    def evaluate(self, f):
        if self._sig is Signal.BUY:
            return self.buy(f, self._conf, f"{self.name} test")
        if self._sig is Signal.SELL:
            return self.exit(f, self._conf, f"{self.name} çık")
        return self.hold(f, "-")


def _ai(strats, **sc):
    return AIComposite(StrategyCfg(), ScoringCfg(buy_score_threshold=0, **sc), strats)


def test_ai_composite_needs_votes_and_respects_veto():
    f = feats(grind_up(260))
    one = _ai([Fixed("a", Signal.BUY), Fixed("b", Signal.HOLD)]).evaluate(f)
    assert one.signal is Signal.HOLD and "yalnızca 1" in one.reason
    two = _ai([Fixed("a", Signal.BUY), Fixed("b", Signal.BUY)]).evaluate(f)
    assert two.signal is Signal.BUY and two.stop_loss < two.entry_price < two.take_profit
    assert two.details["contributions"] and len(two.details["votes"]) == 2
    veto = _ai([Fixed("a", Signal.BUY), Fixed("b", Signal.BUY), Fixed("c", Signal.SELL)]).evaluate(f)
    assert veto.signal is Signal.SELL
    risky = AIComposite(StrategyCfg(), ScoringCfg(buy_score_threshold=0, max_risk_score=0),
                        [Fixed("a", Signal.BUY), Fixed("b", Signal.BUY)]).evaluate(f)
    assert risky.signal is Signal.HOLD and "risk" in risky.reason


def test_ai_composite_default_threshold_blocks_weak_market():
    r = AIComposite().evaluate(feats(sideways(250)))
    assert r.signal is Signal.HOLD


def test_ml_interface_pluggable():
    assert NullModel().predict_probability(None) is None and not NullModel().is_trained

    class M(PredictiveModel):
        is_trained = True
        def predict_probability(self, f): return 1.0
        def predict_expected_return(self, f): return 1.0
        def predict_risk(self, f): return 10.0
    f = feats(grind_up(260))
    strats = [Fixed("a", Signal.BUY, 0.6), Fixed("b", Signal.BUY, 0.6)]
    base = AIComposite(StrategyCfg(), ScoringCfg(buy_score_threshold=0), strats).evaluate(f)
    with_m = AIComposite(StrategyCfg(), ScoringCfg(buy_score_threshold=0), strats, M()).evaluate(f)
    assert with_m.confidence > base.confidence


# ---------- universe + scanner ----------
def _si(sym, base, quote="USDT", status="TRADING"):
    return SymbolInfo(sym, base, quote, status, D("0.01"), D("0.001"), D("0.001"), D("1e6"), D("5"), True)


def _tk(sym, qv):
    return Ticker24h(sym, D("100"), D("1"), D("101"), D("99"), D("1000"), D(str(qv)), 1000)


def test_filter_universe():
    info = {s: _si(s, b) for s, b in [("BTCUSDT", "BTC"), ("USDCUSDT", "USDC"), ("ETHUSDT", "ETH"),
                                       ("SHIBUSDT", "SHIB"), ("LUNAUSDT", "LUNA")]}
    info["ETHBTC"] = _si("ETHBTC", "ETH", "BTC")
    info["LUNAUSDT"] = _si("LUNAUSDT", "LUNA", status="BREAK")
    tks = [_tk("BTCUSDT", 9e8), _tk("USDCUSDT", 1e9), _tk("ETHUSDT", 5e8), _tk("SHIBUSDT", 1e6),
           _tk("ETHBTC", 9e9), _tk("LUNAUSDT", 9e8)]
    out = filter_universe(info, tks, UniverseCfg(min_quote_volume_24h=5e6, max_symbols=10))
    assert [t.symbol for t in out] == ["BTCUSDT", "ETHUSDT"]  # stable, BTC paritesi, düşük hacim, BREAK elendi
    assert len(filter_universe(info, tks, UniverseCfg(min_quote_volume_24h=5e6, max_symbols=1))) == 1


class FakeAd:
    def __init__(self, series, fail=(), ban=None):
        self.series, self.fail, self.ban = series, set(fail), ban

    def exchange_info(self): return {s: _si(s, s[:-4]) for s in self.series}
    def tickers_24h(self): return [_tk(s, 1e8) for s in self.series]
    def server_time_ms(self): return 1_700_000_000_000 + 400 * 3_600_000

    def klines(self, sym, interval, limit):
        if sym == self.ban:
            raise RateLimitBanned(60)
        if sym in self.fail:
            raise TimeoutError("boom")
        df = make_df(self.series[sym])
        ks = to_klines(df)
        return ks[-limit:]

    def order_book(self, sym, limit=20):
        return OrderBook(sym, 1, [(D("99.9"), D("5000"))], [(D("100.0"), D("5000"))])


def test_scanner_ranks_skips_failures_and_records(tmp_path):
    series = {"UPUSDT": grind_up(260), "FLATUSDT": sideways(260), "ERRUSDT": sideways(260)}
    s = Settings()
    sc = MarketScanner(FakeAd(series, fail=["ERRUSDT"]), s)
    opps = sc.scan()
    assert [o.symbol for o in opps][0] == "UPUSDT" and {o.symbol for o in opps} == {"UPUSDT", "FLATUSDT"}
    assert opps[0].score >= opps[1].score
    txt = opps[0].explain()
    for key in ("Score:", "Signal:", "Reasons:", "Trend", "Risk skoru"):
        assert key in txt
    repo = SqliteSignalRepository(SqliteDb(":memory:"))
    for o in opps:
        repo.record(o)
    assert repo.count("signals") == 2 and repo.count("strategy_results") == 12 and repo.count("market_snapshots") == 2
    with pytest.raises(RateLimitBanned):
        MarketScanner(FakeAd(series, ban="UPUSDT"), s).scan()


def test_scanner_ignores_forming_candle():
    series = {"UPUSDT": grind_up(260)}
    ad = FakeAd(series)
    ad.server_time_ms = lambda: int(make_df(series["UPUSDT"]).close_time.iloc[-1]) - 1000  # son mum oluşuyor
    o = MarketScanner(ad, Settings()).scan()[0]
    expect = compute_features("X", make_df(series["UPUSDT"]).iloc[:-1].reset_index(drop=True), None, None)
    assert o.features["last_close_time"] == expect.last_close_time


def test_hold_reason_explains_threshold():
    f = feats(grind_up(260))
    r = AIComposite(StrategyCfg(), ScoringCfg(buy_score_threshold=100),
                    [Fixed("a", Signal.BUY), Fixed("b", Signal.BUY)]).evaluate(f)
    assert r.signal is Signal.HOLD and "eşik" in r.reason and "2 strateji BUY" in r.reason
