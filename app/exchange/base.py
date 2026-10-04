"""Adapter soyut arayüzü. Faz 1: yalnızca OKUMA (market + account data)."""
from __future__ import annotations

from abc import ABC, abstractmethod

from app.exchange.capabilities import ProductCapabilities, get
from app.exchange.models import AccountInfo, Kline, OpenOrder, OrderBook, SymbolInfo, Ticker24h


class ExchangeAdapter(ABC):
    PRODUCT: str

    @property
    def capabilities(self) -> ProductCapabilities:
        return get(self.PRODUCT)

    # CapabilityMatrix'ten türetilen bayraklar
    @property
    def supports_market_data(self) -> bool:
        return bool(self.capabilities.market_data)

    @property
    def supports_trading(self) -> bool:
        return bool(self.capabilities.trading)

    @property
    def supports_testnet(self) -> bool:
        return bool(self.capabilities.test_environment)

    @property
    def supports_websocket(self) -> bool:
        return bool(self.capabilities.websocket)

    @property
    def supports_paper(self) -> bool:  # paper = yerel simülasyon, her market-data ürününde mümkün
        return self.supports_market_data

    @abstractmethod
    def server_time_ms(self) -> int: ...
    @abstractmethod
    def exchange_info(self) -> dict[str, SymbolInfo]: ...
    @abstractmethod
    def tickers_24h(self) -> list[Ticker24h]: ...
    @abstractmethod
    def klines(self, symbol: str, interval: str, limit: int = 500,
               start_ms: int | None = None, end_ms: int | None = None) -> list[Kline]: ...
    @abstractmethod
    def order_book(self, symbol: str, limit: int = 100) -> OrderBook: ...
    @abstractmethod
    def account(self) -> AccountInfo: ...
    @abstractmethod
    def open_orders(self, symbol: str | None = None) -> list[OpenOrder]: ...
