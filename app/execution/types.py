"""Emir tipleri. Miktar/fiyat daima Decimal."""
from __future__ import annotations

import hashlib
import re
from dataclasses import dataclass, field
from decimal import Decimal
from enum import Enum


class Side(str, Enum):
    BUY = "BUY"
    SELL = "SELL"


class OrderType(str, Enum):
    MARKET = "MARKET"
    LIMIT = "LIMIT"


class Status(str, Enum):
    NEW = "NEW"
    PARTIALLY_FILLED = "PARTIALLY_FILLED"
    FILLED = "FILLED"
    CANCELED = "CANCELED"
    REJECTED = "REJECTED"
    EXPIRED = "EXPIRED"
    UNKNOWN = "UNKNOWN"  # sonucu doğrulanamadı -> insan/senkronizasyon bakmalı

    @property
    def terminal(self) -> bool:
        return self in (Status.FILLED, Status.CANCELED, Status.REJECTED, Status.EXPIRED)


@dataclass(frozen=True)
class OrderRequest:
    symbol: str
    side: Side
    type: OrderType
    quantity: Decimal
    price: Decimal | None = None          # LIMIT için zorunlu
    ref_price: Decimal | None = None      # MARKET'te notional kontrolü için (best ask/bid)
    strategy: str = "manual"
    intent_id: str = ""                   # karar başına benzersiz (örn. sinyal id) -> tekrar emir engeli

    @property
    def intent_key(self) -> str:
        return f"{self.strategy}:{self.symbol}:{self.side.value}:{self.intent_id}"

    @property
    def client_order_id(self) -> str:
        """Deterministik: aynı niyet = aynı id. Binance regex: ^[a-zA-Z0-9-_]{1,36}$"""
        cid = "bat-" + hashlib.sha1(self.intent_key.encode()).hexdigest()[:28]
        assert re.fullmatch(r"[a-zA-Z0-9\-_]{1,36}", cid)
        return cid


@dataclass
class OrderState:
    client_order_id: str
    symbol: str
    side: Side
    type: OrderType
    status: Status
    orig_qty: Decimal
    executed_qty: Decimal = Decimal(0)
    quote_qty: Decimal = Decimal(0)       # cummulativeQuoteQty
    price: Decimal | None = None
    order_id: str = ""
    fee_asset: str = ""
    fee_amount: Decimal = Decimal(0)
    verified: bool = False                # borsadan/broker'dan ikinci sorguyla doğrulandı mı
    reason: str = ""

    @property
    def avg_price(self) -> Decimal | None:
        return self.quote_qty / self.executed_qty if self.executed_qty else None


class OrderRejected(Exception):
    """Emir YEREL kontrolde reddedildi (borsaya hiç gönderilmedi)."""
