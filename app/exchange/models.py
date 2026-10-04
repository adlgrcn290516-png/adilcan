"""Borsa-bağımsız veri modelleri. Fiyat/miktar için Decimal (float yuvarlama hatası yok)."""
from __future__ import annotations

from dataclasses import dataclass, field
from decimal import Decimal


@dataclass(frozen=True)
class SymbolInfo:
    symbol: str
    base: str
    quote: str
    status: str
    tick_size: Decimal
    step_size: Decimal
    min_qty: Decimal
    max_qty: Decimal
    min_notional: Decimal
    spot_trading_allowed: bool
    permissions: tuple[str, ...] = ()

    @property
    def is_trading(self) -> bool:
        return self.status == "TRADING" and self.spot_trading_allowed


@dataclass(frozen=True)
class Ticker24h:
    symbol: str
    last_price: Decimal
    price_change_pct: Decimal
    high: Decimal
    low: Decimal
    volume: Decimal
    quote_volume: Decimal
    trades: int
    bid: Decimal | None = None
    ask: Decimal | None = None


@dataclass(frozen=True)
class Kline:
    open_time: int  # ms
    open: Decimal
    high: Decimal
    low: Decimal
    close: Decimal
    volume: Decimal
    close_time: int  # ms
    quote_volume: Decimal
    trades: int
    taker_buy_base: Decimal
    taker_buy_quote: Decimal


@dataclass(frozen=True)
class OrderBook:
    symbol: str
    last_update_id: int
    bids: list[tuple[Decimal, Decimal]]
    asks: list[tuple[Decimal, Decimal]]

    @property
    def spread_pct(self) -> Decimal | None:
        if not self.bids or not self.asks:
            return None
        mid = (self.bids[0][0] + self.asks[0][0]) / 2
        return (self.asks[0][0] - self.bids[0][0]) / mid * 100

    def imbalance(self, levels: int = 10) -> Decimal | None:
        """(bid_vol - ask_vol)/(bid_vol + ask_vol) ilk N seviye, [-1, 1]."""
        b = sum((q for _, q in self.bids[:levels]), Decimal(0))
        a = sum((q for _, q in self.asks[:levels]), Decimal(0))
        return (b - a) / (b + a) if (a + b) else None


@dataclass(frozen=True)
class Balance:
    asset: str
    free: Decimal
    locked: Decimal

    @property
    def total(self) -> Decimal:
        return self.free + self.locked


@dataclass(frozen=True)
class AccountInfo:
    can_trade: bool
    can_withdraw: bool
    can_deposit: bool
    account_type: str
    balances: list[Balance] = field(default_factory=list)
    update_time: int = 0


@dataclass(frozen=True)
class OpenOrder:
    symbol: str
    order_id: int
    client_order_id: str
    side: str
    type: str
    price: Decimal
    orig_qty: Decimal
    executed_qty: Decimal
    status: str
    time: int
