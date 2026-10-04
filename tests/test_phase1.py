from decimal import Decimal

import pytest
from binance_common.models import ApiResponse
from binance_sdk_spot.rest_api.models import (DepthResponse, ExchangeInfoResponse, GetAccountResponse,
                                              TimeResponse, Ticker24hrResponse)

from app.config import LIVE_CONFIRM_PHRASE, Settings, load_settings
from app.exchange import capabilities as caps
from app.exchange.spot import BinanceSpotAdapter
from app.utils.retry import NonRetryableError, WeightLimiter, retry_call


def resp(model):  # gerçek SDK modelini ApiResponse içine sar
    return ApiResponse(data_function=lambda: model, status=200, headers={})


class FakeRest:
    def __init__(self):
        self.calls = []

    def ping(self):
        return ApiResponse(data_function=lambda: None, status=200, headers={})

    def time(self):
        return resp(TimeResponse.from_dict({"serverTime": 1_700_000_000_000}))

    def exchange_info(self):
        return resp(ExchangeInfoResponse.from_dict({
            "timezone": "UTC", "serverTime": 1, "rateLimits": [], "exchangeFilters": [],
            "symbols": [{"symbol": "BTCUSDT", "status": "TRADING", "baseAsset": "BTC", "quoteAsset": "USDT",
                         "isSpotTradingAllowed": True, "permissions": ["SPOT"],
                         "filters": [{"filterType": "PRICE_FILTER", "minPrice": "0.01", "maxPrice": "1000000", "tickSize": "0.01"},
                                     {"filterType": "LOT_SIZE", "minQty": "0.00001", "maxQty": "9000", "stepSize": "0.00001"},
                                     {"filterType": "NOTIONAL", "minNotional": "5.0", "applyMinToMarket": True,
                                      "maxNotional": "9000000", "applyMaxToMarket": False, "avgPriceMins": 5}]}]}))

    def ticker24hr(self, **kw):
        self.calls.append(("ticker24hr", kw))
        item = {"symbol": "BTCUSDT", "priceChange": "10", "priceChangePercent": "1.5", "weightedAvgPrice": "1",
                "prevClosePrice": "1", "lastPrice": "100000.5", "lastQty": "1", "bidPrice": "100000.4", "bidQty": "1",
                "askPrice": "100000.6", "askQty": "1", "openPrice": "1", "highPrice": "101000", "lowPrice": "99000",
                "volume": "1000", "quoteVolume": "100000000", "openTime": 1, "closeTime": 2,
                "firstId": 1, "lastId": 2, "count": 12345}
        return resp(Ticker24hrResponse.from_dict([item]))

    def klines(self, **kw):
        row = [1700000000000, "1", "2", "0.5", "1.5", "10", 1700003599999, "15", 100, "5", "7.5", "0"]
        return resp([row, row])  # SDK iç içe listeleri ham liste olarak döndürür

    def depth(self, **kw):
        return resp(DepthResponse.from_dict({"lastUpdateId": 5, "bids": [["99", "3"], ["98", "1"]],
                                             "asks": [["101", "1"], ["102", "1"]]}))

    def get_account(self, **kw):
        return resp(GetAccountResponse.from_dict({
            "makerCommission": 10, "takerCommission": 10, "buyerCommission": 0, "sellerCommission": 0,
            "canTrade": True, "canWithdraw": False, "canDeposit": True, "updateTime": 1,
            "accountType": "SPOT", "balances": [{"asset": "USDT", "free": "123.45", "locked": "0"}],
            "permissions": ["SPOT"], "uid": 1}))

    def get_open_orders(self, **kw):
        return resp([])  # to_plain list'i de işler


class FakeClient:
    def __init__(self):
        self.rest_api = FakeRest()


@pytest.fixture
def adapter():
    s = Settings(api_key="k", api_secret="s")
    return BinanceSpotAdapter(s, client=FakeClient(), sleep=lambda _: None)


def test_exchange_info_parses_filters(adapter):
    si = adapter.exchange_info()["BTCUSDT"]
    assert si.tick_size == Decimal("0.01") and si.step_size == Decimal("0.00001")
    assert si.min_notional == Decimal("5.0") and si.is_trading


def test_ticker_klines_depth(adapter):
    t = adapter.tickers_24h()[0]
    assert t.last_price == Decimal("100000.5") and t.trades == 12345
    ks = adapter.klines("BTCUSDT", "1h", 2)
    assert len(ks) == 2 and ks[0].close == Decimal("1.5") and ks[0].taker_buy_quote == Decimal("7.5")
    ob = adapter.order_book("BTCUSDT", 20)
    assert round(ob.imbalance(2), 4) == Decimal("0.3333") and ob.spread_pct == Decimal("2")


def test_account_and_open_orders(adapter):
    acc = adapter.account()
    assert acc.can_trade and not acc.can_withdraw and acc.balances[0].free == Decimal("123.45")
    assert adapter.open_orders() == []


def test_account_requires_keys():
    ad = BinanceSpotAdapter(Settings(), client=FakeClient(), sleep=lambda _: None)
    with pytest.raises(NonRetryableError):
        ad.account()


def test_no_order_methods_in_phase1():
    forbidden = {"new_order", "place_order", "cancel_order", "create_order", "order_test"}
    assert not forbidden & set(dir(BinanceSpotAdapter))


def test_capability_matrix_alpha_is_data_only():
    a = caps.get("ALPHA")
    assert a.market_data and not a.trading
    with pytest.raises(PermissionError):
        caps.require_trading("ALPHA")
    caps.require_trading("SPOT")


def test_live_mode_locked():
    with pytest.raises(ValueError):
        Settings(mode="live", api_key="k", api_secret="s")
    with pytest.raises(ValueError):
        Settings(mode="live", environment="testnet", live_confirm=LIVE_CONFIRM_PHRASE, api_key="k", api_secret="s")
    with pytest.raises(ValueError):
        Settings(mode="live", live_confirm=LIVE_CONFIRM_PHRASE)  # key yok
    assert Settings(mode="live", live_confirm=LIVE_CONFIRM_PHRASE, api_key="k", api_secret="s").live_armed


def test_secrets_not_in_repr():
    s = Settings(api_key="SUPERSECRETKEY", api_secret="SUPERSECRETVAL")
    assert "SUPERSECRET" not in repr(s) and "SUPERSECRET" not in s.model_dump_json()


def test_load_settings_env_override(monkeypatch, tmp_path):
    monkeypatch.setenv("TRADING_MODE", "backtest")
    monkeypatch.setenv("BINANCE_ENVIRONMENT", "demo")
    monkeypatch.delenv("LIVE_TRADING_CONFIRM", raising=False)
    s = load_settings("config.yaml", env_file=None)
    assert s.mode.value == "backtest" and s.environment.value == "demo"


def test_retry_backoff_and_error_classes():
    from binance_common.errors import (BadRequestError, RateLimitBanError, ServerError,
                                       TooManyRequestsError)
    from app.utils.retry import RateLimitBanned
    n = {"c": 0}
    delays = []

    def flaky():
        n["c"] += 1
        if n["c"] < 3:
            raise TimeoutError("t")
        return "ok"
    assert retry_call(flaky, retries=4, base=1, sleep=delays.append) == "ok" and len(delays) == 2

    def boom(e):
        def f():
            raise e
        return f
    d = []
    with pytest.raises(BadRequestError):  # kalıcı hata: tekrar YOK
        retry_call(boom(BadRequestError("bad", -1121)), retries=4, sleep=d.append)
    assert d == []
    with pytest.raises(ServerError):
        retry_call(boom(ServerError("5xx", 500)), retries=2, sleep=d.append)
    assert len(d) == 2
    d.clear()
    with pytest.raises(TooManyRequestsError):  # 429: Retry-After'a uyar
        retry_call(boom(TooManyRequestsError("slow", -1003, 12)), retries=1, sleep=d.append)
    assert d == [12.0]
    d.clear()
    with pytest.raises(RateLimitBanned):  # 418: asla uyuyup denemez
        retry_call(boom(RateLimitBanError("ban", -1003, 3600)), retries=4, sleep=d.append)
    assert d == []


def test_weight_limiter_blocks_when_over_budget():
    t = {"now": 0.0}
    waits = []

    def sleep(s):
        waits.append(s)
        t["now"] += s
    lim = WeightLimiter(100, 0.5, clock=lambda: t["now"], sleep=sleep)  # bütçe 50
    lim.acquire(30)
    lim.acquire(20)
    lim.acquire(10)  # 60 > 50 -> beklemeli
    assert waits and sum(waits) >= 59
