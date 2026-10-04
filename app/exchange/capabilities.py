"""CapabilityMatrix: her Binance ürünü için ne yapılabildiği AYRI AYRI tanımlanır.

Kaynaklar (repo içinde doğrulandı):
  - binance-connector-python/clients/<ürün>/src/.../rest_api/rest_api.py metod listeleri
  - binance-spot-api-docs/{testnet,demo-mode}/general-info.md
  - binance_common/constants.py (PROD/TESTNET/DEMO URL sabitleri)
Doğrulanamayan her hücre UNVERIFIED'dır ve TRADING için asla True sayılmaz.
"""
from __future__ import annotations

from dataclasses import dataclass, field
from enum import Enum


class Cap(str, Enum):
    YES = "YES"
    NO = "NO"
    UNVERIFIED = "UNVERIFIED"

    def __bool__(self) -> bool:  # `if cap:` yalnızca YES için True
        return self is Cap.YES


@dataclass(frozen=True)
class ProductCapabilities:
    product: str
    market_data: Cap
    account: Cap
    trading: Cap
    test_environment: Cap
    websocket: Cap
    sdk_package: str
    evidence: str
    status: str = ""  # insan-okur özet


MATRIX: dict[str, ProductCapabilities] = {p.product: p for p in [
    ProductCapabilities(
        "SPOT", Cap.YES, Cap.YES, Cap.YES, Cap.YES, Cap.YES, "binance-sdk-spot",
        "SpotRestAPI: new_order, order_test, delete_order, get_account, klines, depth, ticker24hr, exchange_info. "
        "Testnet (testnet.binance.vision) + Demo Mode (demo-api.binance.com) spot-api-docs'ta mevcut.",
        "TRADABLE"),
    ProductCapabilities(
        "USDS_FUTURES", Cap.YES, Cap.YES, Cap.YES, Cap.YES, Cap.YES,
        "binance-sdk-derivatives-trading-usds-futures",
        "SDK constants: fapi.binance.com + TESTNET_URL + DEMO_URL. Emir endpointleri Faz 6'da SDK'dan "
        "metod metod doğrulanacak.",
        "TRADABLE (Faz 6'da doğrulanıp açılacak)"),
    ProductCapabilities(
        "ALPHA", Cap.YES, Cap.NO, Cap.NO, Cap.NO, Cap.YES, "binance-sdk-alpha",
        "AlphaRestAPI yalnızca market data metodları içerir: aggregated_trades, full_depth, get_exchange_info, "
        "klines, ticker, token_list. Emir/hesap metodu YOK; test ortamı sabiti YOK.",
        "DATA_ONLY / UNSUPPORTED_FOR_TRADING"),
    ProductCapabilities(
        "CONVERT", Cap.YES, Cap.YES, Cap.YES, Cap.UNVERIFIED, Cap.NO, "binance-sdk-convert",
        "ConvertRestAPI: send_quote_request, accept_quote, place_limit_order, cancel_limit_order, order_status, "
        "list_all_convert_pairs. Orderbook/kline yok -> tarayıcı için veri kaynağı değil; test ortamı doğrulanamadı.",
        "TRADABLE ama tarama/strateji için uygun DEĞİL (Faz 7)"),
    ProductCapabilities(
        "ALGO", Cap.NO, Cap.YES, Cap.YES, Cap.UNVERIFIED, Cap.NO, "binance-sdk-algo",
        "AlgoRestAPI: spot/futures TWAP, futures VP (volume_participation). Execution aracı, sinyal kaynağı değil.",
        "EXECUTION_ONLY (Faz 7)"),
]}


def get(product: str) -> ProductCapabilities:
    return MATRIX[product.upper()]


def require_trading(product: str) -> None:
    cap = get(product)
    if not cap.trading:
        raise PermissionError(f"{product}: trading desteklenmiyor/doğrulanamadı ({cap.status})")
