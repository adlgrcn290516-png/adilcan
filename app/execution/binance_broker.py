"""SpotBinanceBroker — Binance Spot emirleri (SADECE testnet/demo; PROD kilitli).

Resmi SDK'dan doğrulanan çağrılar: new_order, get_order, delete_order, get_account, get_open_orders.
DİKKAT: SDK float'ı str() ile gönderir (0.00001 -> '1e-05'); bu yüzden miktar/fiyat HER ZAMAN düz string verilir.
"""
from __future__ import annotations

import logging
from decimal import Decimal
from typing import Any

from binance_sdk_spot.rest_api.models import enums as E

from app.config import Environment, Settings
from app.exchange.spot import BinanceSpotAdapter, to_plain
from app.execution import safety
from app.execution.broker import Broker
from app.execution.types import OrderRequest, OrderState, OrderType, Side, Status

log = logging.getLogger(__name__)
NO_SUCH_ORDER = -2013  # errors.md: -2013 NO_SUCH_ORDER (SDK status_code = Binance kodu)


def dec_str(d: Decimal) -> str:
    return format(d, "f")  # asla bilimsel gösterim


def _d(v: Any) -> Decimal:
    return Decimal(str(v)) if v not in (None, "") else Decimal(0)


class RealMoneyLocked(PermissionError):
    pass


class SpotBinanceBroker(Broker):
    def __init__(self, settings: Settings, adapter: BinanceSpotAdapter):
        if settings.environment is Environment.PROD and not safety.REAL_MONEY_ENABLED:
            raise RealMoneyLocked(
                "PROD ortamında emir gönderimi KİLİTLİ (app/execution/safety.py). "
                "Önce BINANCE_ENVIRONMENT=demo veya testnet kullan; canlı için açık onay gerekir.")
        if not settings.has_credentials:
            raise PermissionError("Emir için API key/secret gerekli (.env)")
        self.s, self.ad = settings, adapter
        self._rest = adapter._rest
        self.name = f"binance-{settings.environment.value}"

    def _state(self, raw: dict[str, Any], req_fallback: OrderRequest | None = None) -> OrderState:
        try:
            status = Status(raw.get("status", "UNKNOWN"))
        except ValueError:
            status = Status.UNKNOWN
        exec_q = _d(raw.get("executedQty"))
        fills = raw.get("fills") or []
        fee = sum((_d(f.get("commission")) for f in fills), Decimal(0))
        return OrderState(
            client_order_id=raw.get("clientOrderId", req_fallback.client_order_id if req_fallback else ""),
            symbol=raw["symbol"], side=Side(raw["side"]), type=OrderType(raw["type"]), status=status,
            orig_qty=_d(raw.get("origQty")), executed_qty=exec_q, quote_qty=_d(raw.get("cummulativeQuoteQty")),
            price=_d(raw.get("price")) or None, order_id=str(raw.get("orderId", "")),
            fee_asset=(fills[0].get("commissionAsset", "") if fills else ""), fee_amount=fee)

    def submit(self, req: OrderRequest) -> OrderState:
        kw: dict[str, Any] = dict(
            symbol=req.symbol, side=E.NewOrderSideEnum[req.side.value], type=E.NewOrderTypeEnum[req.type.value],
            quantity=dec_str(req.quantity), new_client_order_id=req.client_order_id,
            new_order_resp_type=E.NewOrderNewOrderRespTypeEnum.RESULT,
            recv_window=float(self.s.http.recv_window_ms))
        if req.type is OrderType.LIMIT:
            kw["price"] = dec_str(req.price)
            kw["time_in_force"] = E.NewOrderTimeInForceEnum.GTC
        log.info("[%s] ORDER SEND %s %s %s qty=%s price=%s cid=%s", self.name, req.symbol, req.side.value,
                 req.type.value, kw["quantity"], kw.get("price"), req.client_order_id)
        # Tek deneme: belirsiz hatada tekrar gönderme kararını ExecutionEngine verir (önce sorgular).
        self.ad.limiter.acquire(1)
        resp = self._rest.new_order(**kw)
        return self._state(to_plain(resp.data()), req)

    def get_order(self, symbol: str, client_order_id: str) -> OrderState | None:
        try:
            resp = self.ad._call(6, lambda: self._rest.get_order(
                symbol=symbol, orig_client_order_id=client_order_id,
                recv_window=float(self.s.http.recv_window_ms)), f"get_order {client_order_id}")
        except Exception as exc:  # noqa: BLE001
            if getattr(exc, "status_code", None) == NO_SUCH_ORDER:
                return None
            raise
        return self._state(to_plain(resp.data()))

    def cancel(self, symbol: str, client_order_id: str) -> OrderState | None:
        resp = self.ad._call(1, lambda: self._rest.delete_order(
            symbol=symbol, orig_client_order_id=client_order_id,
            recv_window=float(self.s.http.recv_window_ms)), "cancel")
        return self._state(to_plain(resp.data()))

    def free_balance(self, asset: str) -> Decimal:
        for b in self.ad.account().balances:
            if b.asset == asset:
                return b.free
        return Decimal(0)

    def open_orders(self, symbol: str | None = None) -> list[OrderState]:
        resp = self.ad._call(6 if symbol else 80, lambda: self._rest.get_open_orders(
            symbol=symbol, recv_window=float(self.s.http.recv_window_ms)), "open_orders")
        return [self._state(o) for o in to_plain(resp.data())]
