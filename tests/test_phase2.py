import re
from decimal import Decimal as D

import pytest
from binance_common.errors import BadRequestError
from binance_common.models import ApiResponse
from binance_common.utils import encoded_string
from binance_sdk_spot.rest_api.models import GetOrderResponse, NewOrderResponse

from app.config import Settings
from app.data.db import SqliteDb, SqliteOrderRepository
from app.exchange.models import OrderBook, SymbolInfo
from app.exchange.spot import BinanceSpotAdapter
from app.execution.binance_broker import RealMoneyLocked, SpotBinanceBroker, dec_str
from app.execution.broker import Broker
from app.execution.engine import ExecutionEngine
from app.execution.filters import floor_to_step, normalize, round_price
from app.execution.types import (OrderRejected, OrderRequest, OrderState, OrderType, Side, Status)
from app.paper.broker import DuplicateOrder, PaperBroker

SYM = SymbolInfo("BTCUSDT", "BTC", "USDT", "TRADING", D("0.01"), D("0.00001"), D("0.00001"), D("9000"), D("5"), True)
BOOK = OrderBook("BTCUSDT", 1, [(D("99990"), D("1")), (D("99980"), D("1"))], [(D("100000"), D("1")), (D("100010"), D("1"))])


def req(side=Side.BUY, type=OrderType.MARKET, qty="0.01", price=None, iid="1", ref="100000"):
    return OrderRequest("BTCUSDT", side, type, D(qty), D(price) if price else None, D(ref), "t", iid)


def paper(usdt="1000000", btc="0", fee="0.001", slip="2"):
    bal = {"USDT": D(usdt)}
    if D(btc):
        bal["BTC"] = D(btc)
    return PaperBroker(bal, {"BTCUSDT": SYM}, lambda s: BOOK, D(fee), D(slip))


def engine(broker, **kw):
    s = Settings(api_key="k", api_secret="s")
    repo = SqliteOrderRepository(SqliteDb(":memory:"))
    return ExecutionEngine(broker, {"BTCUSDT": SYM}, repo, s, sleep=lambda _: None, **kw), repo


# ---------- filtreler ----------
def test_filters_round_and_reject():
    assert floor_to_step(D("0.123456789"), D("0.00001")) == D("0.12345")
    assert round_price(D("100.019"), D("0.01"), Side.BUY) == D("100.01")
    assert round_price(D("100.011"), D("0.01"), Side.SELL) == D("100.02")
    assert normalize(req(qty="0.0100099"), SYM).quantity == D("0.01000")
    with pytest.raises(OrderRejected):
        normalize(req(qty="0.000001"), SYM)  # minQty altı
    with pytest.raises(OrderRejected):
        normalize(req(qty="0.00004"), SYM)  # 0.00004*100000=4 < 5 notional
    with pytest.raises(OrderRejected):
        normalize(OrderRequest("BTCUSDT", Side.BUY, OrderType.MARKET, D("1")), SYM)  # ref_price yok
    halted = SymbolInfo("X", "X", "USDT", "BREAK", D("1"), D("1"), D("1"), D("9"), D("1"), True)
    with pytest.raises(OrderRejected):
        normalize(OrderRequest("X", Side.BUY, OrderType.LIMIT, D("1"), D("1")), halted)


def test_client_order_id_deterministic_and_valid():
    a, b, c = req(iid="1"), req(iid="1"), req(iid="2")
    assert a.client_order_id == b.client_order_id != c.client_order_id
    assert re.fullmatch(r"[a-zA-Z0-9\-_]{1,36}", a.client_order_id)


# ---------- paper broker ----------
def test_paper_market_buy_walks_book_with_slippage_and_fee_in_base():
    b = paper()
    st = b.submit(req(qty="1.5"))  # 1@100000 + 0.5@100010
    assert st.status is Status.FILLED and st.executed_qty == D("1.5")
    raw = D("100000") + D("0.5") * D("100010")
    assert st.quote_qty == raw * D("1.0002")
    assert st.fee_asset == "BTC" and st.fee_amount == D("0.0015")
    assert b.free_balance("BTC") == D("1.5") - D("0.0015")
    assert b.free_balance("USDT") == D("1000000") - st.quote_qty
    assert b.locked.get("USDT", 0) == 0


def test_paper_round_trip_costs_money():
    b = paper()
    b.submit(req(qty="0.1", iid="b"))
    btc = b.free_balance("BTC")
    sell = b.submit(req(Side.SELL, qty=str(floor_to_step(btc, D("0.00001"))), iid="s"))
    assert sell.status is Status.FILLED
    assert b.free_balance("USDT") < D("1000000")  # spread + slippage + fee zararı
    assert b.locked.get("BTC", 0) == 0


def test_paper_insufficient_balance_and_duplicate():
    b = paper(usdt="10")
    st = b.submit(req(qty="1"))
    assert st.status is Status.REJECTED and "yetersiz" in st.reason
    b2 = paper()
    b2.submit(req(type=OrderType.LIMIT, price="90000", qty="0.1", iid="x"))
    with pytest.raises(DuplicateOrder):
        b2.submit(req(type=OrderType.LIMIT, price="90000", qty="0.1", iid="x"))


def test_paper_limit_rests_locks_cancels_and_fills_on_move():
    b = paper()
    r = req(type=OrderType.LIMIT, price="99000", qty="0.1")
    st = b.submit(r)
    assert st.status is Status.NEW and b.locked["USDT"] == D("9900")
    assert b.free_balance("USDT") == D("1000000") - D("9900")
    # kitap düşünce dolar
    global BOOK
    old = BOOK
    try:
        BOOK = OrderBook("BTCUSDT", 2, [(D("98990"), D("5"))], [(D("99000"), D("5"))])
        done = b.process_resting()
        assert done and done[0].status is Status.FILLED
        assert done[0].quote_qty == D("9900")  # limit fiyatından, slippage yok
        assert b.locked["USDT"] == 0
    finally:
        BOOK = old
    b2 = paper()
    r2 = req(type=OrderType.LIMIT, price="99000", qty="0.1", iid="c")
    b2.submit(r2)
    b2.cancel("BTCUSDT", r2.client_order_id)
    assert b2.free_balance("USDT") == D("1000000") and b2.locked["USDT"] == 0


# ---------- engine ----------
def test_engine_happy_path_is_verified_and_persisted():
    e, repo = engine(paper())
    st = e.place(req())
    assert st.status is Status.FILLED and st.verified
    assert repo.get(st.client_order_id).status is Status.FILLED


def test_engine_duplicate_intent_does_not_resend():
    class Counting(PaperBroker):
        n = 0

        def submit(self, r):
            Counting.n += 1
            return super().submit(r)
    b = Counting({"USDT": D("1000000")}, {"BTCUSDT": SYM}, lambda s: BOOK)
    e, _ = engine(b)
    a = e.place(req(iid="same"))
    c = e.place(req(iid="same"))
    assert Counting.n == 1 and a.client_order_id == c.client_order_id


def test_engine_local_rejections():
    e, repo = engine(paper(usdt="1"))
    with pytest.raises(OrderRejected, match="yetersiz"):
        e.place(req())
    assert repo.all() == []  # reddedilen emir borsaya/DB'ye gitmedi
    e2, _ = engine(paper())
    e2.emergency_stop = True
    with pytest.raises(OrderRejected, match="EMERGENCY"):
        e2.place(req())
    e3, _ = engine(paper(btc="1"))
    e3.emergency_stop = True
    assert e3.place(req(Side.SELL, qty="0.5", ref="99990", iid="close")).status is Status.FILLED  # kapatma serbest


class Flaky(Broker):
    """submit'te timeout atar; `reached` True ise emir aslında borsaya ulaşmıştır."""
    name = "flaky"

    def __init__(self, reached, fail_times=1):
        self.reached, self.fail_times, self.submits, self.store = reached, fail_times, 0, {}

    def _mk(self, r):
        return OrderState(r.client_order_id, r.symbol, r.side, r.type, Status.FILLED, r.quantity, r.quantity,
                          r.quantity * 100000, order_id="9")

    def submit(self, r):
        self.submits += 1
        if self.submits <= self.fail_times:
            if self.reached:
                self.store[r.client_order_id] = self._mk(r)
            raise TimeoutError("timeout")
        self.store[r.client_order_id] = self._mk(r)
        return self.store[r.client_order_id]

    def get_order(self, s, cid):
        return self.store.get(cid)

    def cancel(self, s, cid): ...
    def free_balance(self, a): return D("1000000")
    def open_orders(self, s=None): return []


def test_timeout_after_order_reached_exchange_is_not_resent():
    b = Flaky(reached=True)
    e, _ = engine(b)
    st = e.place(req())
    assert b.submits == 1 and st.status is Status.FILLED and st.verified


def test_timeout_before_order_reached_retries_once():
    b = Flaky(reached=False)
    e, _ = engine(b)
    st = e.place(req())
    assert b.submits == 2 and st.status is Status.FILLED


def test_persistent_timeout_ends_unknown_and_never_duplicates():
    b = Flaky(reached=False, fail_times=99)
    e, repo = engine(b)
    st = e.place(req())
    assert st.status is Status.UNKNOWN and b.submits == 2
    assert e.place(req()).status is Status.UNKNOWN and b.submits == 2  # aynı niyet tekrar GÖNDERİLMEZ


def test_exchange_fatal_error_marks_rejected():
    class Bad(Flaky):
        def submit(self, r):
            raise BadRequestError("Account has insufficient balance", -2010)
    e, repo = engine(Bad(False))
    st = e.place(req())
    assert st.status is Status.REJECTED and "-2010" in st.reason or "insufficient" in st.reason
    assert repo.get(st.client_order_id).status is Status.REJECTED


def test_engine_trusts_get_order_over_submit_response():
    class Liar(Flaky):
        def submit(self, r):
            self.store[r.client_order_id] = OrderState(r.client_order_id, r.symbol, r.side, r.type, Status.CANCELED,
                                                       r.quantity, D(0), D(0), order_id="1")
            return self._mk(r)  # submit 'FILLED' diyor, gerçek durum CANCELED
    e, _ = engine(Liar(False))
    st = e.place(req())
    assert st.status is Status.CANCELED and st.executed_qty == 0 and st.verified


# ---------- Binance broker (SDK) ----------
class FakeRest:
    def __init__(self):
        self.kw = None

    def new_order(self, **kw):
        self.kw = kw
        return ApiResponse(lambda: NewOrderResponse.from_dict({
            "symbol": "BTCUSDT", "orderId": 7, "orderListId": -1, "clientOrderId": kw["new_client_order_id"],
            "transactTime": 1, "price": "0.00000000", "origQty": kw["quantity"], "executedQty": "0.00001",
            "cummulativeQuoteQty": "0.85", "status": "FILLED", "timeInForce": "GTC", "type": "MARKET", "side": "BUY",
            "workingTime": 1, "selfTradePreventionMode": "NONE"}), 200, {})

    def get_order(self, **kw):
        if kw["orig_client_order_id"] == "missing":
            raise BadRequestError("Order does not exist.", -2013)
        return ApiResponse(lambda: GetOrderResponse.from_dict({
            "symbol": "BTCUSDT", "orderId": 7, "orderListId": -1, "clientOrderId": kw["orig_client_order_id"],
            "price": "0", "origQty": "0.00001", "executedQty": "0.00001", "cummulativeQuoteQty": "0.85",
            "status": "FILLED", "timeInForce": "GTC", "type": "MARKET", "side": "BUY", "stopPrice": "0",
            "icebergQty": "0", "time": 1, "updateTime": 1, "isWorking": True, "workingTime": 1,
            "origQuoteOrderQty": "0", "selfTradePreventionMode": "NONE"}), 200, {})


class FakeClient:
    def __init__(self):
        self.rest_api = FakeRest()


def test_binance_broker_locked_on_prod():
    s = Settings(api_key="k", api_secret="s")  # environment=prod
    with pytest.raises(RealMoneyLocked):
        SpotBinanceBroker(s, BinanceSpotAdapter(s, client=FakeClient()))


def test_binance_broker_sends_plain_decimal_strings_not_floats():
    s = Settings(api_key="k", api_secret="s", environment="demo")
    ad = BinanceSpotAdapter(s, client=FakeClient(), sleep=lambda _: None)
    br = SpotBinanceBroker(s, ad)
    r = OrderRequest("BTCUSDT", Side.BUY, OrderType.MARKET, D("0.00001"), None, D("85000"), "t", "1")
    st = br.submit(r)
    kw = br._rest.kw
    assert kw["quantity"] == "0.00001" and isinstance(kw["quantity"], str)
    assert st.status is Status.FILLED and st.avg_price == D("85000")
    # SDK'nın gerçek kodlayıcısı bilimsel gösterim üretmiyor mu?
    enc = encoded_string({"quantity": kw["quantity"], "new_client_order_id": kw["new_client_order_id"]})
    assert "quantity=0.00001" in enc and "e-" not in enc.lower().replace("order", "")
    assert dec_str(D("1E-8")) == "0.00000001"
    assert br.get_order("BTCUSDT", "missing") is None  # -2013 -> None
    assert br.get_order("BTCUSDT", "abc").verified is False  # doğrulama engine'in işi
