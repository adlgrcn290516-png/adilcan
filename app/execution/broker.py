"""Broker arayüzü: paper, testnet/demo ve (kilitli) live aynı sözleşmeyi kullanır."""
from __future__ import annotations

from abc import ABC, abstractmethod
from decimal import Decimal

from app.execution.types import OrderRequest, OrderState


class Broker(ABC):
    name: str

    @abstractmethod
    def submit(self, req: OrderRequest) -> OrderState:
        """Emri gönder. DÖNEN durum henüz 'doğrulanmış' sayılmaz."""

    @abstractmethod
    def get_order(self, symbol: str, client_order_id: str) -> OrderState | None:
        """Borsadaki/broker'daki gerçek durum. Emir yoksa None."""

    @abstractmethod
    def cancel(self, symbol: str, client_order_id: str) -> OrderState | None: ...

    @abstractmethod
    def free_balance(self, asset: str) -> Decimal: ...

    @abstractmethod
    def open_orders(self, symbol: str | None = None) -> list[OrderState]: ...
