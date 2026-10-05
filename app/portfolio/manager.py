"""PortfolioManager — pozisyon açma/yönetme/kapatma. Risk motoru ve ExecutionEngine'i birleştirir.

Pozisyon yönetimi: initial stop, TP1 (kısmi), TP2, break-even, trailing. Stop yalnızca YUKARI taşınır.

DİKKAT (bilinen sınır): Bu fazda stop BOT TARAFINDAN izlenir (yazılım stop). Borsa tarafında stop emri
(STOP_LOSS_LIMIT/OCO) YOKTUR; bot kapalıyken stop çalışmaz. LIVE'dan önce borsa-tarafı stop ZORUNLU (Faz 9).
"""
from __future__ import annotations

import logging
import time
from dataclasses import dataclass, field
from decimal import Decimal
from typing import Callable, Mapping

import pandas as pd

from app.config import Settings
from app.core.features import Features
from app.core.scanner import Opportunity
from app.data.db import SqlitePortfolioRepository
from app.exchange.models import SymbolInfo
from app.execution.broker import Broker
from app.execution.engine import ExecutionEngine
from app.execution.filters import floor_to_step
from app.execution.types import OrderRejected, OrderRequest, OrderState, OrderType, Side, Status
from app.portfolio.position import Position
from app.risk.engine import Decision, PortfolioState, RiskEngine, Verdict

log = logging.getLogger(__name__)
D = Decimal


@dataclass
class Quote:
    bid: Decimal
    ask: Decimal

    @property
    def mid(self) -> Decimal:
        return (self.bid + self.ask) / 2


@dataclass
class OpenResult:
    verdict: Verdict | None
    order: OrderState | None = None
    position: Position | None = None
    note: str = ""


@dataclass
class ExitEvent:
    symbol: str
    reason: str
    qty: Decimal
    price: Decimal | None
    pnl: Decimal
    status: str


class PortfolioManager:
    def __init__(self, broker: Broker, engine: ExecutionEngine, risk: RiskEngine, symbols: dict[str, SymbolInfo],
                 settings: Settings, repo: SqlitePortfolioRepository, quote_asset: str = "USDT",
                 clock: Callable[[], float] = time.time):
        self.broker, self.engine, self.risk, self.symbols = broker, engine, risk, symbols
        self.s, self.repo, self.quote, self._clock = settings, repo, quote_asset, clock
        self.positions: dict[str, Position] = {}
        self._sod_day: float | None = None
        self._sod_equity: Decimal | None = None
        self._peak: Decimal | None = repo.peak_equity()
        for d in repo.open_positions():  # yeniden başlatma: DB'deki açık pozisyonları yükle
            p = Position.from_dict(d)
            self.positions[p.symbol] = p

    # ---------- durum ----------
    def _px(self, sym: str, quotes: Mapping[str, Quote]) -> Decimal:
        q = quotes.get(sym)
        if q is None:
            log.warning("%s için fiyat yok; giriş fiyatı kullanılıyor", sym)
            return self.positions[sym].entry_price
        return q.bid

    def equity(self, quotes: Mapping[str, Quote]) -> Decimal:
        return self.broker.free_balance(self.quote) + sum(
            (p.qty * self._px(p.symbol, quotes) for p in self.positions.values()), D(0))

    def exposure(self, quotes: Mapping[str, Quote]) -> Decimal:
        return sum((p.qty * self._px(p.symbol, quotes) for p in self.positions.values()), D(0))

    def state(self, quotes: Mapping[str, Quote]) -> PortfolioState:
        eq = self.equity(quotes)
        now = self._clock()
        day = now - now % 86400
        if self._sod_day != day:  # UTC gün değişimi (yeniden başlatmada DB'den devam eder)
            self._sod_day = day
            self._sod_equity = self.repo.first_equity_since(day) or eq
        self._peak = max(self._peak or eq, eq)
        return PortfolioState(eq, self.broker.free_balance(self.quote), self.exposure(quotes),
                              list(self.positions.values()), self._sod_equity, self._peak)

    def snapshot(self, quotes: Mapping[str, Quote]) -> PortfolioState:
        st = self.state(quotes)
        self.repo.record_snapshot(self._clock(), st.equity, st.free_quote, st.exposure,
                                  st.equity - st.start_of_day_equity,
                                  (st.peak_equity - st.equity) / st.peak_equity if st.peak_equity else 0)
        return st

    # ---------- acil durdurma ----------
    def set_emergency(self, on: bool, close_positions: bool = False, quotes: Mapping[str, Quote] | None = None):
        self.risk.emergency_stop = self.engine.emergency_stop = on
        log.critical("EMERGENCY_STOP=%s (close_positions=%s)", on, close_positions)
        self.repo.record_risk_event(self._clock(), "*", "EMERGENCY", f"on={on} close={close_positions}")
        if on and close_positions:
            return self.close_all("EMERGENCY", quotes or {})
        return []

    def close_all(self, reason: str, quotes: Mapping[str, Quote]) -> list[ExitEvent]:
        return [e for p in list(self.positions.values()) if (e := self._close(p, p.qty, reason, quotes))]

    def preview(self, opp: Opportunity, f: Features, quote: Quote, quotes: Mapping[str, Quote] | None = None,
                returns: Mapping[str, pd.Series] | None = None) -> tuple[Verdict, Decimal, Decimal, Decimal, Decimal]:
        """Emir GÖNDERMEDEN risk kararı. -> (verdict, stop, stop_dist, tp1_dist, tp2_dist); 1R mesafesi canlı ask'a taşınır."""
        quotes = dict(quotes or {})
        quotes[opp.symbol] = quote
        ask = quote.ask
        e0, s0, t1, t2 = (D(str(x)) for x in (opp.entry, opp.stop, opp.tp1, opp.tp2))
        stop_dist, tp1_dist, tp2_dist = e0 - s0, t1 - e0, t2 - e0
        stop = ask - stop_dist
        drift = abs(ask - e0)
        if f.atr and drift > D(str(f.atr)):  # bayat sinyal: güncel ask sinyal fiyatından 1 ATR'den fazla uzak
            return (Verdict(Decision.REJECT, reasons=[f"fiyat sinyalden uzaklaştı ({drift:.8g} > 1 ATR)"]),
                    stop, stop_dist, tp1_dist, tp2_dist)
        v = self.risk.evaluate_entry(f, ask, stop, self.symbols[opp.symbol], self.state(quotes), returns)
        return v, stop, stop_dist, tp1_dist, tp2_dist

    # ---------- giriş ----------
    def try_open(self, opp: Opportunity, f: Features, quote: Quote, quotes: Mapping[str, Quote] | None = None,
                 returns: Mapping[str, pd.Series] | None = None) -> OpenResult:
        info = self.symbols[opp.symbol]
        ask = quote.ask
        verdict, stop, stop_dist, tp1_dist, tp2_dist = self.preview(opp, f, quote, quotes, returns)
        self.repo.record_risk_event(self._clock(), opp.symbol, verdict.decision.value, verdict.summary())
        if verdict.decision is Decision.REJECT:
            return OpenResult(verdict, note="; ".join(verdict.reasons))

        before = self.broker.free_balance(info.base)
        req = OrderRequest(opp.symbol, Side.BUY, OrderType.MARKET, verdict.qty, None, ask, "ai_composite",
                           f"{opp.symbol}-{int(opp.ts)}")
        try:
            st = self.engine.place(req)
        except OrderRejected as exc:
            return OpenResult(verdict, note=f"emir yerelde reddedildi: {exc}")
        if st.executed_qty <= 0:
            return OpenResult(verdict, st, note=f"dolum yok (durum {st.status.value}: {st.reason})")
        # gerçek elde tutulan miktar: bakiye farkı (komisyon base'den düşmüş olabilir)
        delta = floor_to_step(self.broker.free_balance(info.base) - before, info.step_size)
        qty = delta if delta > 0 else floor_to_step(st.executed_qty, info.step_size)
        fill = st.avg_price
        pos = Position(opp.symbol, qty, fill, fill - stop_dist, fill + tp1_dist, fill + tp2_dist, fill - stop_dist,
                       st.quote_qty, strategy="ai_composite")
        self.positions[opp.symbol] = pos
        self.repo.save_position(pos)
        self.repo.record_trade(self._clock(), opp.symbol, "BUY", st.executed_qty, fill, st.fee_amount, st.fee_asset,
                               st.client_order_id, pos.strategy, D(0))
        log.info("POZİSYON AÇILDI %s qty=%s giriş=%s stop=%s TP1=%s TP2=%s (yazılım stop!)", pos.symbol, pos.qty,
                 fill, pos.stop, pos.tp1, pos.tp2)
        return OpenResult(verdict, st, pos)

    # ---------- yönetim ----------
    def manage(self, quotes: Mapping[str, Quote]) -> list[ExitEvent]:
        """Kurallar `portfolio/rules.step` içinde (backtest ile ORTAK). Burada yalnızca emir yürütme var."""
        from app.portfolio import rules
        cfg, events = self.s.position, []
        for p in list(self.positions.values()):
            q = quotes.get(p.symbol)
            if q is None:
                log.warning("%s: fiyat yok, bu turda yönetilemedi", p.symbol)
                continue
            bid = q.bid
            while p.status == "OPEN":
                act = rules.step(p, bid, bid, cfg)
                if act is None:
                    break
                if act.qty_fraction >= 1:  # STOP / TRAILING/BE_STOP / TP2 -> tamamını sat
                    if e := self._close(p, p.qty, act.kind, quotes):
                        events.append(e)
                    break
                info = self.symbols[p.symbol]
                part = floor_to_step(p.qty * act.qty_fraction, info.step_size)
                remaining_val = (p.qty - part) * bid
                if part < info.min_qty or part * bid < info.min_notional or remaining_val < info.min_notional:
                    part = p.qty  # parça ya da kalan çok küçük -> tamamını sat
                e = self._close(p, part, "TP1_PARTIAL" if part < p.qty else "TP1", quotes)
                if e is None:
                    break
                events.append(e)
                if p.status == "OPEN":
                    rules.mark_partial(p, cfg)
            if p.status == "OPEN":
                self.repo.save_position(p)
        return events

    def _close(self, p: Position, qty: Decimal, reason: str, quotes: Mapping[str, Quote]) -> ExitEvent | None:
        info = self.symbols[p.symbol]
        qty = floor_to_step(min(qty, p.qty), info.step_size)
        bid = quotes[p.symbol].bid if p.symbol in quotes else p.entry_price
        if qty < info.min_qty:
            log.warning("%s kapanış miktarı çok küçük (toz): %s -> pozisyon KAPALI işaretlendi", p.symbol, qty)
            return self._finalize(p, qty, None, D(0), reason, None)
        req = OrderRequest(p.symbol, Side.SELL, OrderType.MARKET, qty, None, bid, p.strategy, f"pos{p.id}-exit-{p.exit_seq}")
        try:
            st = self.engine.place(req)
        except OrderRejected as exc:
            log.error("%s ÇIKIŞ yerelde reddedildi (%s): %s", p.symbol, reason, exc)
            p.exit_seq += 1  # sonraki turda yeni niyetle tekrar dene
            self.repo.save_position(p)
            return None
        if st.executed_qty <= 0:
            log.error("%s ÇIKIŞ dolmadı (%s, durum %s)", p.symbol, reason, st.status.value)
            if st.status in (Status.REJECTED, Status.CANCELED, Status.EXPIRED):
                p.exit_seq += 1  # terminal başarısız: yeni niyetle tekrar. UNKNOWN ise aynı niyet -> engine broker'a sorar.
            self.repo.save_position(p)
            return None
        p.exit_seq += 1  # bu çıkış emri TÜKETİLDİ: sonraki çıkış (örn. TP1 sonrası TP2) yeni niyet kimliği alır
        proceeds = st.quote_qty - (st.fee_amount if st.fee_asset == self.quote else D(0))
        portion = st.executed_qty / p.qty
        cost_part = p.cost_quote * portion
        pnl = proceeds - cost_part
        p.qty -= st.executed_qty
        p.cost_quote -= cost_part
        p.realized_pnl += pnl
        self.repo.record_trade(self._clock(), p.symbol, "SELL", st.executed_qty, st.avg_price, st.fee_amount,
                               st.fee_asset, st.client_order_id, f"{p.strategy}|{reason}", pnl)
        return self._finalize(p, st.executed_qty, st.avg_price, pnl, reason, st)

    def _finalize(self, p: Position, qty: Decimal, price: Decimal | None, pnl: Decimal, reason: str,
                  st: OrderState | None) -> ExitEvent:
        info = self.symbols[p.symbol]
        dust = p.qty < info.min_qty or (price is not None and p.qty * price < info.min_notional)
        if p.qty <= 0 or dust:
            p.status, p.closed_at = "CLOSED", self._clock()
            self.positions.pop(p.symbol, None)
        self.repo.save_position(p)
        log.info("ÇIKIŞ %s %s qty=%s fiyat=%s pnl=%s kalan=%s (%s)", p.symbol, reason, qty, price, pnl, p.qty, p.status)
        return ExitEvent(p.symbol, reason, qty, price, pnl, p.status)

    # ---------- yeniden başlatma senkronizasyonu ----------
    def reconcile(self) -> list[str]:
        """DB pozisyonları ile broker'daki GERÇEK bakiye/emirleri karşılaştırır (otomatik işlem YAPMAZ)."""
        issues = []
        for p in self.positions.values():
            have = self.broker.free_balance(self.symbols[p.symbol].base)
            if have < p.qty * D("0.999"):
                issues.append(f"{p.symbol}: kayıtlı {p.qty} ama borsada serbest {have} (elle satılmış olabilir)")
        for o in self.broker.open_orders():
            issues.append(f"borsada açık emir var: {o.symbol} {o.side.value} {o.orig_qty} ({o.client_order_id})")
        for i in issues:
            log.warning("RECONCILE: %s", i)
        return issues
