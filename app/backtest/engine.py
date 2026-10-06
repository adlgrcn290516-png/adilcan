"""Backtest motoru — canlıyla AYNI Strategy / RiskEngine / pozisyon kurallarını (portfolio.rules) kullanır.

Zamanlama (look-ahead YOK):
  * Sinyal, bar t KAPANIŞINDA yalnızca <= t verisinden üretilir.
  * Emir, bar t+latency_bars AÇILIŞINDA dolar (en az 1 bar gecikme).
  * Pozisyon yönetimi dolum barının OHLC'si ile yapılır; bar içi sıra bilinmediğinden KÖTÜMSER yol varsayılır
    (önce stop; bar içinde stop yükseldiyse ve low ona değiyorsa çıkış).
Maliyetler: komisyon (alışta base'den, satışta quote'tan — Binance gibi), kayma (slippage) ve yarım-spread her dolumda.
Kline verisinde order book olmadığından spread/derinlik risk kontrolleri backtest'te UYGULANAMAZ (NaN -> atlanır).
"""
from __future__ import annotations

import logging
from collections import Counter
from dataclasses import dataclass, field
from decimal import Decimal
from functools import reduce
from typing import Mapping

import numpy as np
import pandas as pd

from app.backtest import metrics as M
from app.config import Settings
from app.core.features import Features, features_at, prepare
from app.data.history import INTERVAL_MS
from app.exchange.models import SymbolInfo
from app.portfolio import rules
from app.portfolio.position import Position
from app.risk.engine import Decision, PortfolioState, RiskEngine
from app.risk.sizing import risk_based_qty, size_cap_qty
from app.strategies.ai_composite import AIComposite
from app.strategies.base import Signal, SignalResult
from app.strategies.library import CLASSIC

log = logging.getLogger(__name__)
D = Decimal


@dataclass
class BacktestCfg:
    interval: str = "1h"
    fee_rate: float = 0.001           # %0.1 taker (BNB indirimi YOK: kötümser)
    slippage_bps: float = 3.0         # market emir kayması
    half_spread_bps: float = 2.0      # kline'da spread yok: varsayım (her yönde)
    latency_bars: int = 1             # >=1: sinyal barı kapanışı -> sonraki bar açılışı
    initial_capital: float = 1000.0
    warmup_bars: int = 250            # EMA200 vb. oturması için (canlı tarama da 300 bar kullanır)
    apply_risk: bool = True           # False: yalnızca boyutlandırma (risk motoru değerini ölçmek için)
    latch_resets_daily: bool = False  # KENAR ÖLÇÜM modu: drawdown kilidi ertesi gün açılır (gerçek sistemde açılmaz!)
    exit_on_signal: bool = False      # stratejinin SELL (çıkış) sinyali pozisyonu kapatsın (canlıda henüz yok)

    def __post_init__(self):
        if self.latency_bars < 1:
            raise ValueError("latency_bars >= 1 olmalı (aynı bar kapanışında dolum = look-ahead)")


def with_params(s: Settings, params: Mapping[str, object]) -> Settings:
    """Nokta-yollu parametre geçersiz kılma: {"scoring.buy_score_threshold": 70}. Orijinali değiştirmez."""
    s2 = s.model_copy(deep=True)
    for k, v in params.items():
        obj, attr = k.rsplit(".", 1)
        setattr(reduce(getattr, obj.split("."), s2), attr, v)
    return s2


class SignalCache:
    """Özellik ve strateji çıktılarını (sembol, bar) başına BİR kez hesaplar; parametre varyantları yeniden kullanır."""

    def __init__(self, data: dict[str, pd.DataFrame], settings: Settings, extra=None):
        self.data, self.s = data, settings
        self.extra = list(extra or [])   # AI Composite'e OY VERMEZ; yalnızca bağımsız (solo) araştırma için
        iv = INTERVAL_MS[settings.scanner.interval]
        self.bars_per_day = max(1, 86_400_000 // iv)
        self.prep = {sym: prepare(df, breakout_lookback=settings.strategy.breakout_lookback,
                                  squeeze_pctile=settings.strategy.squeeze_pctile) for sym, df in data.items()}
        self.strats = [cls(settings.strategy) for cls in CLASSIC]
        self._timeline = sorted({int(ct) for p in self.prep.values() for ct in p.a["ct"]})
        self.index = {sym: {int(ct): i for i, ct in enumerate(p.a["ct"])} for sym, p in self.prep.items()}
        self._hold = {st.name: SignalResult(st.name, Signal.HOLD) for st in self.strats}  # paylaşımlı HOLD (bellek)
        self._f: dict[tuple[str, int], Features] = {}
        self._o: dict[tuple[str, int], dict[str, SignalResult]] = {}

    def timeline_all(self) -> list[int]:
        return self._timeline

    def features(self, sym: str, i: int) -> Features:
        k = (sym, i)
        f = self._f.get(k)
        if f is None:
            f = self._f[k] = features_at(self.prep[sym], i, sym, bars_per_day=self.bars_per_day)
        return f

    def outputs(self, sym: str, i: int) -> dict[str, SignalResult]:
        k = (sym, i)
        o = self._o.get(k)
        if o is None:
            f = self.features(sym, i)
            o = {}
            for st in self.strats:
                r = st.evaluate(f)
                o[st.name] = r if r.signal is not Signal.HOLD else self._hold[st.name]
            for st in self.extra:
                o[st.name] = st.evaluate(f)
            self._o[k] = o
        return o


class SoloVariant:
    """Tek stratejiyi bağımsız çalıştırır (katkı ölçümü için)."""

    def __init__(self, name: str, min_conf: float = 0.5):
        self.name, self.min_conf = name, min_conf

    def decide(self, f: Features, outputs: Mapping[str, SignalResult]) -> SignalResult:
        r = outputs[self.name]
        if r.signal is Signal.BUY and r.confidence < self.min_conf:
            return SignalResult(self.name, Signal.HOLD, reason="güven eşiğin altında")
        r.details = {"score": r.confidence * 100}
        return r


@dataclass
class BacktestResult:
    equity: list[tuple[int, float]]
    trades: list[M.Trade]
    metrics: dict
    rejections: Counter
    n_signals: int
    n_entries: int
    window: tuple[int, int]
    events: list[str] = field(default_factory=list)
    params: dict = field(default_factory=dict)
    exposure_bars: int = 0


@dataclass
class _Pending:
    kind: str                 # "BUY" | "EXIT"
    sym: str
    due: int                  # kalan bar sayısı
    res: SignalResult | None
    f: Features | None
    prio: float


class Backtester:
    def __init__(self, cache: SignalCache, variant, settings: Settings, cfg: BacktestCfg | None = None,
                 infos: Mapping[str, SymbolInfo] | None = None):
        self.cache, self.variant, self.s = cache, variant, settings
        self.cfg = cfg or BacktestCfg()
        self.infos = infos or {}
        self.timeline = sorted({int(ct) for p in cache.prep.values() for ct in p.a["ct"]})

    def _info(self, sym: str) -> SymbolInfo:
        return self.infos.get(sym) or SymbolInfo(sym, sym, "USDT", "TRADING", D("0.00000001"), D("0.00000001"),
                                                  D(0), D("1e12"), D(5), True)

    def run(self, start_ts: int | None = None, end_ts: int | None = None, params: dict | None = None) -> BacktestResult:
        cfg, cache = self.cfg, self.cache
        s = self.s
        tl = [t for t in self.timeline if (start_ts is None or t >= start_ts) and (end_ts is None or t <= end_ts)]
        slip = D(str((cfg.slippage_bps + cfg.half_spread_bps) / 10_000))
        fee = D(str(cfg.fee_rate))
        risk = RiskEngine(s.risk)
        cash = D(str(cfg.initial_capital))
        positions: dict[str, Position] = {}
        meta: dict[str, dict] = {}
        pending: list[_Pending] = []
        trades: list[M.Trade] = []
        rej: Counter = Counter()
        events: list[str] = []
        equity: list[tuple[int, float]] = []
        n_sig = n_ent = exposure_bars = 0
        last_close: dict[str, Decimal] = {}
        fees_total = D(0)
        sod_day, sod_eq, peak = None, cash, cash

        def mark(ct: int) -> Decimal:
            return cash + sum((p.qty * last_close.get(p.symbol, p.entry_price) for p in positions.values()), D(0))

        def pstate(eq: Decimal) -> PortfolioState:
            exp = sum((p.qty * last_close.get(p.symbol, p.entry_price) for p in positions.values()), D(0))
            return PortfolioState(eq, cash, exp, list(positions.values()), sod_eq, max(peak, eq))

        def sell(sym: str, qty: Decimal, trigger: Decimal, ct: int, reason: str) -> None:
            nonlocal cash, fees_total
            p, m = positions[sym], meta[sym]
            px = trigger * (1 - slip)
            gross = qty * px
            f_q = gross * fee
            cash += gross - f_q
            fees_total += f_q
            cost_part = p.cost_quote * (qty / p.qty)
            m["pnl"] += gross - f_q - cost_part
            m["fees"] += f_q
            m["exits"].append((qty, px))
            m["reasons"].append(reason)
            p.cost_quote -= cost_part
            p.qty -= qty
            if p.qty <= D("1e-12"):
                q = sum(x[0] for x in m["exits"])
                avg = sum(x[0] * x[1] for x in m["exits"]) / q
                trades.append(M.Trade(
                    sym, m["entry_ts"], ct, float(p.entry_price), float(avg), float(m["qty0"]), float(m["pnl"]),
                    float(m["pnl"] / m["cost0"] * 100), float(m["pnl"] / (p.risk_dist * m["qty0"])),
                    list(m["reasons"]), m["bars"], float(m["fees"])))
                positions.pop(sym)
                meta.pop(sym)

        def returns_for(sym: str, ct: int) -> dict[str, pd.Series]:
            out = {}
            for s_ in {sym, *positions}:
                j = cache.index[s_].get(ct)
                if j is None or j < 31:
                    continue
                c = cache.prep[s_].a["c"][j - 99 if j >= 99 else 0:j + 1]
                idx = cache.prep[s_].a["ct"][j - len(c) + 1:j + 1]
                out[s_] = pd.Series(np.diff(c) / c[:-1], index=idx[1:])
            return out

        for step_no, ct in enumerate(tl):
            day = ct // 86_400_000
            bars = {sym: cache.index[sym][ct] for sym in cache.prep if ct in cache.index[sym]}
            # ---- (a) bekleyen emirler: bu barın AÇILIŞINDA dolar ----
            due = [p_ for p_ in pending if p_.due <= 1]
            pending = [p_ for p_ in pending if p_.due > 1]
            for p_ in pending:
                p_.due -= 1
            for pd_ in sorted(due, key=lambda x: -x.prio):
                i = bars.get(pd_.sym)
                if i is None:
                    continue
                open_ = D(str(self.cache.data[pd_.sym]["open"].iat[i]))
                if pd_.kind == "EXIT":
                    if pd_.sym in positions:
                        sell(pd_.sym, positions[pd_.sym].qty, open_, ct, "SIGNAL_EXIT")
                    continue
                if pd_.sym in positions:
                    continue
                res, f = pd_.res, pd_.f
                fill = open_ * (1 + slip)
                stop_dist = D(str(res.entry_price - res.stop_loss))
                tp1_d, tp2_d = D(str(res.take_profit - res.entry_price)), D(str(res.take_profit_2 - res.entry_price))
                stop = fill - stop_dist
                eq_now = mark(ct)
                info = self._info(pd_.sym)
                if cfg.apply_risk:
                    v = risk.evaluate_entry(f, fill, stop, info, pstate(eq_now), returns_for(pd_.sym, ct))
                    if v.decision is Decision.REJECT:
                        for r_ in v.reasons:
                            rej[r_.split("(")[0].strip()] += 1
                        continue
                    qty = v.qty
                else:
                    qty = min(risk_based_qty(eq_now, s.risk.max_risk_per_trade_pct, fill, stop),
                              size_cap_qty(eq_now, s.risk.max_position_size_pct, fill))
                    qty = min(qty, cash / fill)
                cost = qty * fill
                if qty <= 0 or cost > cash:
                    rej["nakit yetersiz"] += 1
                    continue
                fee_base = qty * fee
                cash -= cost
                fees_total += fee_base * fill
                pos = Position(pd_.sym, qty - fee_base, fill, stop, fill + tp1_d, fill + tp2_d, stop, cost)
                positions[pd_.sym] = pos
                meta[pd_.sym] = {"entry_ts": ct, "qty0": pos.qty, "cost0": cost, "pnl": D(0), "fees": fee_base * fill,
                                 "exits": [], "reasons": [], "bars": 0}
                n_ent += 1
            # ---- (b) pozisyon yönetimi (OHLC, kötümser) ----
            for sym in list(positions):
                i = bars.get(sym)
                if i is None:
                    continue
                df = cache.data[sym]
                o_, h_, l_ = (D(str(df[c].iat[i])) for c in ("open", "high", "low"))
                p = positions[sym]
                meta[sym]["bars"] += 1
                while sym in positions:
                    act = rules.step(p, h_, l_, s.position, o_)
                    if act is None:
                        break
                    if act.qty_fraction >= 1:
                        sell(sym, p.qty, act.price, ct, act.kind)
                        break
                    part = p.qty * act.qty_fraction
                    if (p.qty - part) * act.price < self._info(sym).min_notional:
                        part = p.qty
                    sell(sym, part, act.price, ct, "TP1_PARTIAL" if part < p.qty else "TP1")
                    if sym in positions:
                        rules.mark_partial(p, s.position)
            # ---- (c) kapanışta değerleme ----
            for sym, i in bars.items():
                last_close[sym] = D(str(cache.data[sym]["close"].iat[i]))
            eq = mark(ct)
            if sod_day != day:
                sod_day, sod_eq = day, eq
                if cfg.latch_resets_daily and risk.drawdown_latched:
                    risk.drawdown_latched = False
                    peak = eq  # kilit açılınca zirve yeniden temellendirilir (aksi halde hemen tekrar kilitlenir)
            peak = max(peak, eq)
            st = pstate(eq)
            was = risk.drawdown_latched
            risk.status(st)
            if risk.drawdown_latched and not was:
                events.append(f"{ct}: MAX DRAWDOWN kilidi devreye girdi (yeni giriş yok)")
            equity.append((ct, float(eq)))
            exposure_bars += 1 if positions else 0
            # ---- (d) sinyaller (bar kapanışı) ----
            if step_no == len(tl) - 1:
                continue
            queued = {p_.sym for p_ in pending}
            for sym, i in bars.items():
                if i < cfg.warmup_bars:
                    continue
                f = cache.features(sym, i)
                r = self.variant.decide(f, cache.outputs(sym, i))
                if sym in positions:
                    if cfg.exit_on_signal and r.signal is Signal.SELL and sym not in queued:
                        pending.append(_Pending("EXIT", sym, cfg.latency_bars, None, None, 0))
                    continue
                if r.signal is Signal.BUY and sym not in queued:
                    n_sig += 1
                    pending.append(_Pending("BUY", sym, cfg.latency_bars, r, f, float(r.details.get("score", 0))))
        # ---- test sonu: açık pozisyonları son kapanıştan kapat ----
        if tl:
            for sym in list(positions):
                sell(sym, positions[sym].qty, last_close[sym], tl[-1], "END_OF_TEST")
            equity[-1] = (tl[-1], float(cash))
        m = M.compute(equity, trades, INTERVAL_MS[cfg.interval], exposure_bars)
        m["fees_total"] = float(fees_total)
        return BacktestResult(equity, trades, m, rej, n_sig, n_ent, (tl[0], tl[-1]) if tl else (0, 0), events,
                              dict(params or {}), exposure_bars)


def make_variant(settings: Settings, cache: SignalCache, only: list[str] | None = None, solo: str | None = None):
    if solo:
        return SoloVariant(solo, settings.scoring.min_vote_confidence)
    strats = [s_ for s_ in cache.strats if only is None or s_.name in only]
    return AIComposite(settings.strategy, settings.scoring, strats)
