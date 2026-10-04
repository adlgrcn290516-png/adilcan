"""BinanceSpotAdapter — resmi `binance-sdk-spot` üzerinde ince, güvenli katman.

FAZ 1: bu sınıfta emir gönderen/iptal eden HİÇBİR metod yoktur (bilinçli).
"""
from __future__ import annotations

import logging
import time
from decimal import Decimal
from typing import Any, Callable, TypeVar

from binance_common.configuration import ConfigurationRestAPI
from binance_common.constants import (SPOT_REST_API_DEMO_URL, SPOT_REST_API_PROD_URL,
                                      SPOT_REST_API_TESTNET_URL)
from binance_sdk_spot.rest_api.models import enums as sdk_enums
from binance_sdk_spot.spot import Spot

from app.config import Environment, Settings
from app.exchange.base import ExchangeAdapter
from app.exchange.models import (AccountInfo, Balance, Kline, OpenOrder, OrderBook, SymbolInfo,
                                 Ticker24h)
from app.utils.retry import NonRetryableError, WeightLimiter, retry_call

log = logging.getLogger(__name__)
T = TypeVar("T")

BASE_URLS = {
    Environment.PROD: SPOT_REST_API_PROD_URL,
    Environment.DEMO: SPOT_REST_API_DEMO_URL,
    Environment.TESTNET: SPOT_REST_API_TESTNET_URL,
}


def to_plain(obj: Any) -> Any:
    """SDK pydantic modellerini (oneOf/root sarmalayıcıları dahil) düz dict/list'e çevirir.

    model_dump KULLANILMAZ: oneOf modellerde doğrulayıcı kopyalarını da döker. Alanlar alias (Binance
    anahtarı) ile, `additional_properties` düzleştirilerek alınır.
    """
    if obj is None or isinstance(obj, (str, int, float, bool)):
        return obj
    if isinstance(obj, (list, tuple)):
        return [to_plain(i) for i in obj]
    if isinstance(obj, dict):
        return {k: to_plain(v) for k, v in obj.items()}
    if hasattr(obj, "actual_instance"):
        return to_plain(obj.actual_instance)
    if hasattr(obj, "root"):
        return to_plain(obj.root)
    fields = getattr(type(obj), "model_fields", None)
    if fields is not None:
        out: dict[str, Any] = {}
        for name, f in fields.items():
            if name == "additional_properties":
                continue
            v = getattr(obj, name, None)
            if v is not None:
                out[f.alias or name] = to_plain(v)
        out.update({k: to_plain(v) for k, v in (getattr(obj, "additional_properties", None) or {}).items()})
        return out
    return obj


def _d(v: Any, default: str = "0") -> Decimal:
    return Decimal(str(v)) if v not in (None, "") else Decimal(default)


def _depth_weight(limit: int) -> int:
    for cap, w in ((100, 5), (500, 25), (1000, 50)):
        if limit <= cap:
            return w
    return 250  # 1001-5000


class BinanceSpotAdapter(ExchangeAdapter):
    PRODUCT = "SPOT"

    def __init__(self, settings: Settings, *, client: Spot | None = None,
                 limiter: WeightLimiter | None = None, sleep: Callable[[float], None] = time.sleep):
        self.s = settings
        self.base_url = BASE_URLS[settings.environment]
        self._sleep = sleep
        self.limiter = limiter or WeightLimiter(settings.rate_limit.max_weight_per_min,
                                                settings.rate_limit.safety_ratio)
        if client is None:
            cfg = ConfigurationRestAPI(
                api_key=settings.api_key.get_secret_value() if settings.api_key else None,
                api_secret=settings.api_secret.get_secret_value() if settings.api_secret else None,
                base_path=self.base_url,
                timeout=settings.http.timeout_ms,
                retries=0,  # tekrar deneme tek yerde (retry_call) yönetilir
            )
            client = Spot(config_rest_api=cfg)
        self._rest = client.rest_api
        self._symbols: dict[str, SymbolInfo] = {}

    # ---- iç yardımcılar ----
    def _call(self, weight: int, fn: Callable[[], T], what: str) -> T:
        def attempt() -> T:
            self.limiter.acquire(weight)
            resp = fn()
            for rl in getattr(resp, "rate_limits", None) or []:
                if getattr(rl, "rate_limit_type", "") == "REQUEST_WEIGHT" and getattr(rl, "interval", "") == "MINUTE":
                    self.limiter.sync_from_header(int(getattr(rl, "count", 0) or 0))
            return resp
        h = self.s.http
        return retry_call(attempt, retries=h.retries, base=h.backoff_base_s, cap=h.backoff_max_s,
                          sleep=self._sleep, what=what)

    def _need_keys(self) -> None:
        if not self.s.has_credentials:
            raise NonRetryableError("Bu işlem için BINANCE_API_KEY/SECRET gerekli (.env)")

    # ---- bağlantı ----
    def ping(self) -> bool:
        self._call(1, self._rest.ping, "ping")
        return True

    def server_time_ms(self) -> int:
        return int(to_plain(self._call(1, self._rest.time, "time").data())["serverTime"])

    def clock_drift_ms(self) -> int:
        """Yerel saat - sunucu saati (RTT/2 düzeltmeli). İmzalı isteklerde recvWindow için kritik."""
        t0 = time.time() * 1000
        srv = self.server_time_ms()
        t1 = time.time() * 1000
        return int((t0 + t1) / 2 - srv)

    # ---- market data ----
    def exchange_info(self) -> dict[str, SymbolInfo]:
        raw = to_plain(self._call(20, self._rest.exchange_info, "exchange_info").data())
        out: dict[str, SymbolInfo] = {}
        for s in raw.get("symbols", []):
            f = {x["filterType"]: x for x in s.get("filters", [])}
            pf, lot = f.get("PRICE_FILTER", {}), f.get("LOT_SIZE", {})
            nt = f.get("NOTIONAL") or f.get("MIN_NOTIONAL") or {}
            out[s["symbol"]] = SymbolInfo(
                symbol=s["symbol"], base=s["baseAsset"], quote=s["quoteAsset"], status=s["status"],
                tick_size=_d(pf.get("tickSize")), step_size=_d(lot.get("stepSize")),
                min_qty=_d(lot.get("minQty")), max_qty=_d(lot.get("maxQty")),
                min_notional=_d(nt.get("minNotional")),
                spot_trading_allowed=bool(s.get("isSpotTradingAllowed", False)),
                permissions=tuple(s.get("permissions", []) or ()),
            )
        self._symbols = out
        return out

    def tickers_24h(self) -> list[Ticker24h]:
        # symbol verilmezse tüm semboller (weight 80, dokümanla doğrulandı)
        raw = to_plain(self._call(80, lambda: self._rest.ticker24hr(type=sdk_enums.Ticker24hrTypeEnum.FULL),
                                  "ticker24hr").data())
        if isinstance(raw, dict):
            raw = [raw]
        return [Ticker24h(
            symbol=t["symbol"], last_price=_d(t.get("lastPrice")), price_change_pct=_d(t.get("priceChangePercent")),
            high=_d(t.get("highPrice")), low=_d(t.get("lowPrice")), volume=_d(t.get("volume")),
            quote_volume=_d(t.get("quoteVolume")), trades=int(t.get("count", 0) or 0),
            bid=_d(t["bidPrice"]) if t.get("bidPrice") else None,
            ask=_d(t["askPrice"]) if t.get("askPrice") else None) for t in raw]

    def klines(self, symbol: str, interval: str, limit: int = 500,
               start_ms: int | None = None, end_ms: int | None = None) -> list[Kline]:
        iv = sdk_enums.KlinesIntervalEnum(interval)
        raw = to_plain(self._call(2, lambda: self._rest.klines(symbol=symbol, interval=iv, limit=limit,
                                                               start_time=start_ms, end_time=end_ms),
                                  f"klines {symbol}").data())
        return [Kline(open_time=int(k[0]), open=_d(k[1]), high=_d(k[2]), low=_d(k[3]), close=_d(k[4]),
                      volume=_d(k[5]), close_time=int(k[6]), quote_volume=_d(k[7]), trades=int(k[8]),
                      taker_buy_base=_d(k[9]), taker_buy_quote=_d(k[10])) for k in raw]

    def order_book(self, symbol: str, limit: int = 100) -> OrderBook:
        raw = to_plain(self._call(_depth_weight(limit), lambda: self._rest.depth(symbol=symbol, limit=limit),
                                  f"depth {symbol}").data())
        conv = lambda side: [(_d(p), _d(q)) for p, q in raw.get(side, [])]  # noqa: E731
        return OrderBook(symbol, int(raw.get("lastUpdateId", 0)), conv("bids"), conv("asks"))

    # ---- account data (imzalı, SALT OKUNUR) ----
    def account(self) -> AccountInfo:
        self._need_keys()
        raw = to_plain(self._call(
            20, lambda: self._rest.get_account(omit_zero_balances=True,
                                               recv_window=float(self.s.http.recv_window_ms)),
            "get_account").data())
        return AccountInfo(
            can_trade=bool(raw.get("canTrade")), can_withdraw=bool(raw.get("canWithdraw")),
            can_deposit=bool(raw.get("canDeposit")), account_type=str(raw.get("accountType", "")),
            balances=[Balance(b["asset"], _d(b["free"]), _d(b["locked"])) for b in raw.get("balances", [])],
            update_time=int(raw.get("updateTime", 0) or 0))

    def open_orders(self, symbol: str | None = None) -> list[OpenOrder]:
        self._need_keys()
        raw = to_plain(self._call(
            6 if symbol else 80,
            lambda: self._rest.get_open_orders(symbol=symbol, recv_window=float(self.s.http.recv_window_ms)),
            "open_orders").data())
        return [OpenOrder(
            symbol=o["symbol"], order_id=int(o["orderId"]), client_order_id=o.get("clientOrderId", ""),
            side=o["side"], type=o["type"], price=_d(o.get("price")), orig_qty=_d(o.get("origQty")),
            executed_qty=_d(o.get("executedQty")), status=o["status"], time=int(o.get("time", 0)))
            for o in raw]
