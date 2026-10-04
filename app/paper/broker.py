"""PaperBroker — gerçek emir göndermez. Gerçek order book üzerinde fee + slippage ile simüle eder.

Aynı Broker arayüzünü kullanır; böylece strateji/risk/execution kodu paper ve live'da DEĞİŞMEZ.
"""
from __future__ import annotations

from dataclasses import replace
from decimal import Decimal
from typing import Callable

from app.exchange.models import OrderBook, SymbolInfo
from app.execution.broker import Broker
from app.execution.types import OrderRequest, OrderState, OrderType, Side, Status


class DuplicateOrder(Exception):
    """Binance: 'Duplicate order sent.' (aynı clientOrderId hâlâ açıkken)."""


class PaperBroker(Broker):
    name = "paper"

    def __init__(self, balances: dict[str, Decimal], symbols: dict[str, SymbolInfo],
                 book_fn: Callable[[str], OrderBook], fee_rate: Decimal = Decimal("0.001"),
                 slippage_bps: Decimal = Decimal("2")):
        self.free: dict[str, Decimal] = dict(balances)
        self.locked: dict[str, Decimal] = {}
        self.symbols, self.book_fn = symbols, book_fn
        self.fee_rate, self.slip = fee_rate, slippage_bps / Decimal(10000)
        self.orders: dict[str, OrderState] = {}
        self._seq = 0

    # ---- bakiye ----
    def free_balance(self, asset: str) -> Decimal:
        return self.free.get(asset, Decimal(0))

    def _add(self, d: dict[str, Decimal], asset: str, amt: Decimal) -> None:
        d[asset] = d.get(asset, Decimal(0)) + amt

    # ---- simülasyon çekirdeği ----
    def _walk(self, book: OrderBook, side: Side, qty: Decimal, limit: Decimal | None,
              slippage: bool) -> tuple[Decimal, Decimal]:
        """Order book'u yürü: (dolan_miktar, toplam_quote). Slippage yalnızca MARKET'te uygulanır
        (limit emir limit fiyatından kötü dolmaz)."""
        levels = book.asks if side is Side.BUY else book.bids
        got, quote = Decimal(0), Decimal(0)
        for p, q in levels:
            if limit is not None and ((side is Side.BUY and p > limit) or (side is Side.SELL and p < limit)):
                break
            take = min(q, qty - got)
            got += take
            quote += take * p
            if got >= qty:
                break
        if got and slippage:
            quote *= (1 + self.slip) if side is Side.BUY else (1 - self.slip)
        return got, quote

    def _fill(self, st: OrderState, sym: SymbolInfo, qty: Decimal, quote: Decimal, lock_part: Decimal) -> None:
        """Dolumu bakiyeye yansıt. Spot'ta komisyon alınan varlıktan kesilir (BUY->base, SELL->quote)."""
        if st.side is Side.BUY:
            fee = qty * self.fee_rate
            self._add(self.locked, sym.quote, -lock_part)
            self._add(self.free, sym.quote, lock_part - quote)  # kilitli fazlayı iade et
            self._add(self.free, sym.base, qty - fee)
            st.fee_asset = sym.base
        else:
            fee = quote * self.fee_rate
            self._add(self.locked, sym.base, -qty)
            self._add(self.free, sym.quote, quote - fee)
            st.fee_asset = sym.quote
        st.fee_amount += fee
        st.executed_qty += qty
        st.quote_qty += quote

    def submit(self, req: OrderRequest) -> OrderState:
        cid = req.client_order_id
        if cid in self.orders and not self.orders[cid].status.terminal:
            raise DuplicateOrder(cid)
        sym = self.symbols[req.symbol]
        self._seq += 1
        st = OrderState(cid, req.symbol, req.side, req.type, Status.NEW, req.quantity, price=req.price,
                        order_id=f"paper-{self._seq}")
        self.orders[cid] = st
        is_mkt = req.type is OrderType.MARKET
        limit = None if is_mkt else req.price
        got, quote = self._walk(self.book_fn(req.symbol), req.side, req.quantity, limit, slippage=is_mkt)

        if req.side is Side.BUY:
            asset = sym.quote
            need = quote if is_mkt else req.quantity * req.price
        else:
            asset, need = sym.base, req.quantity
        if need <= 0:  # market ve kitap boş
            st.status, st.reason = Status.EXPIRED, "kitapta likidite yok"
            return st
        if self.free_balance(asset) < need:
            st.status = Status.REJECTED
            st.reason = f"yetersiz bakiye {asset}: gerekli {need}, mevcut {self.free_balance(asset)}"
            return st
        self._add(self.free, asset, -need)
        self._add(self.locked, asset, need)

        if got:
            lock_part = quote if (req.side is Side.BUY and is_mkt) else (req.price * got if req.side is Side.BUY else got)
            self._fill(st, sym, got, quote, lock_part)
        if st.executed_qty >= req.quantity:
            st.status = Status.FILLED
        elif is_mkt:  # market'te kalan düşer (Binance: EXPIRED)
            if req.side is Side.SELL:
                rem = req.quantity - st.executed_qty
                self._add(self.locked, sym.base, -rem)
                self._add(self.free, sym.base, rem)
            st.status = Status.EXPIRED
        else:  # limit kalan kitapta bekler
            st.status = Status.PARTIALLY_FILLED if st.executed_qty else Status.NEW
        return st

    def process_resting(self) -> list[OrderState]:
        """Bekleyen LIMIT emirleri güncel kitapla eşleştir (her tick'te çağrılır)."""
        done = []
        for st in list(self.open_orders()):
            sym = self.symbols[st.symbol]
            rem = st.orig_qty - st.executed_qty
            got, quote = self._walk(self.book_fn(st.symbol), st.side, rem, st.price, slippage=False)
            if got:
                self._fill(st, sym, got, quote, st.price * got if st.side is Side.BUY else got)
                st.status = Status.FILLED if st.executed_qty >= st.orig_qty else Status.PARTIALLY_FILLED
                done.append(st)
        return done

    def get_order(self, symbol: str, client_order_id: str) -> OrderState | None:
        st = self.orders.get(client_order_id)
        return None if st is None else replace(st)

    def cancel(self, symbol: str, client_order_id: str) -> OrderState | None:
        st = self.orders.get(client_order_id)
        if st is None or st.status.terminal:
            return st
        sym = self.symbols[symbol]
        rem = st.orig_qty - st.executed_qty
        if st.side is Side.BUY:
            amt = rem * (st.price or Decimal(0))
            self._add(self.locked, sym.quote, -amt)
            self._add(self.free, sym.quote, amt)
        else:
            self._add(self.locked, sym.base, -rem)
            self._add(self.free, sym.base, rem)
        st.status = Status.CANCELED
        return st

    def open_orders(self, symbol: str | None = None) -> list[OrderState]:
        return [s for s in self.orders.values() if not s.status.terminal and (symbol is None or s.symbol == symbol)]
