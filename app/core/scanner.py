"""MarketScanner: Universe filtre -> özellikler -> stratejiler -> composite skor -> sıralı fırsat listesi."""
from __future__ import annotations

import logging
import time
from dataclasses import dataclass, field
from typing import Callable

import pandas as pd

from app.config import Settings, UniverseCfg
from app.core.features import Features, closed_only, compute_features, klines_to_df
from app.exchange.models import SymbolInfo, Ticker24h
from app.exchange.spot import BinanceSpotAdapter
from app.strategies.ai_composite import AIComposite
from app.strategies.base import Signal
from app.utils.retry import RateLimitBanned

log = logging.getLogger(__name__)


@dataclass
class Opportunity:
    symbol: str
    market: str
    score: float
    signal: Signal
    confidence: float
    entry: float | None
    stop: float | None
    tp1: float | None
    tp2: float | None
    risk_score: float
    reason: str
    contributions: list[dict] = field(default_factory=list)
    votes: list[dict] = field(default_factory=list)
    features: dict = field(default_factory=dict)
    f_obj: Features | None = field(default=None, repr=False, compare=False)
    ts: float = field(default_factory=time.time)

    def explain(self) -> str:
        """Kararın NEDENİNİ okunur biçimde verir (log + dashboard)."""
        L = [f"{self.symbol}  [{self.market}]", f"Score: {self.score:.0f}", f"Signal: {self.signal.value}"
             + (f" (güven {self.confidence:.2f})" if self.signal is not Signal.HOLD else ""), "Reasons:"]
        for c in self.contributions:
            L.append(f"  {c['name']:<10} {c['points']:+6.1f}   (bileşen skoru {c['score']:.0f})")
        if self.signal is Signal.BUY:
            L += [f"Entry: {self.entry:.8g}", f"Stop:  {self.stop:.8g}", f"TP1:   {self.tp1:.8g}", f"TP2:   {self.tp2:.8g}"]
        voted = [f"{v['strategy']}={v['signal']}({v['confidence']:.2f})" for v in self.votes if v["signal"] != "HOLD"]
        L.append("Strateji oyları: " + (", ".join(voted) if voted else "hiçbiri aktif"))
        L.append(f"Risk skoru: {self.risk_score:.0f} | Risk motoru: BEKLİYOR (Faz 4)")
        L.append(f"Not: {self.reason}")
        return "\n".join(L)


def filter_universe(info: dict[str, SymbolInfo], tickers: list[Ticker24h], cfg: UniverseCfg) -> list[Ticker24h]:
    excl = {b.upper() for b in cfg.exclude_bases}
    out = []
    for t in tickers:
        si = info.get(t.symbol)
        if si is None or si.quote != cfg.quote_asset or not si.is_trading or si.base in excl:
            continue
        if float(t.quote_volume) < cfg.min_quote_volume_24h:
            continue
        out.append(t)
    out.sort(key=lambda t: t.quote_volume, reverse=True)
    return out[: cfg.max_symbols]


class MarketScanner:
    def __init__(self, adapter: BinanceSpotAdapter, settings: Settings, composite: AIComposite | None = None):
        self.ad, self.s = adapter, settings
        self.composite = composite or AIComposite(settings.strategy, settings.scoring)
        self.returns: dict[str, pd.Series] = {}   # korelasyon hesabı için son 100 bar getirisi (index=close_time)

    def evaluate_symbol(self, sym: str, ticker: Ticker24h | None, now_ms: int) -> Opportunity | None:
        sc = self.s.scanner
        ks = self.ad.klines(sym, sc.interval, sc.kline_limit)
        df = closed_only(klines_to_df(ks), now_ms)
        book = self.ad.order_book(sym, sc.book_depth)
        f = compute_features(sym, df, ticker, book, breakout_lookback=self.s.strategy.breakout_lookback,
                             squeeze_pctile=self.s.strategy.squeeze_pctile, min_bars=sc.min_bars)
        if f is None:
            log.info("%s: yetersiz kapanmış mum (%d) -> atlandı", sym, len(df))
            return None
        r = df["close"].pct_change().dropna().tail(100)
        self.returns[sym] = pd.Series(r.values, index=df["close_time"].iloc[-len(r):].values)
        return self.to_opportunity(f)

    def to_opportunity(self, f: Features) -> Opportunity:
        r = self.composite.evaluate(f)
        d = r.details
        return Opportunity(f.symbol, "SPOT", d["score"], r.signal, r.confidence, r.entry_price, r.stop_loss,
                           r.take_profit, r.take_profit_2, r.risk_score, r.reason, d["contributions"], d["votes"],
                           f.as_dict(), f)

    def scan(self, progress: Callable[[str], None] | None = None) -> list[Opportunity]:
        info = self.ad.exchange_info()
        tickers = filter_universe(info, self.ad.tickers_24h(), self.s.universe)
        now_ms = self.ad.server_time_ms()
        log.info("tarama evreni: %d sembol (interval=%s)", len(tickers), self.s.scanner.interval)
        out: list[Opportunity] = []
        for i, t in enumerate(tickers, 1):
            if progress:
                progress(f"[{i}/{len(tickers)}] {t.symbol}")
            try:
                o = self.evaluate_symbol(t.symbol, t, now_ms)
            except RateLimitBanned:
                raise  # IP ban: taramayı durdur
            except Exception as exc:  # noqa: BLE001 — tek sembol hatası taramayı öldürmesin
                log.warning("%s taranamadı: %s: %s", t.symbol, type(exc).__name__, exc)
                continue
            if o:
                out.append(o)
        out.sort(key=lambda o: o.score, reverse=True)
        return out
