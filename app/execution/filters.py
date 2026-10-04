"""Binance sembol filtreleri: tickSize / stepSize / minQty / minNotional (borsa reddetmeden önce yerelde)."""
from __future__ import annotations

from decimal import ROUND_DOWN, ROUND_UP, Decimal

from app.exchange.models import SymbolInfo
from app.execution.types import OrderRejected, OrderRequest, OrderType, Side


def floor_to_step(value: Decimal, step: Decimal) -> Decimal:
    if step <= 0:
        return value
    return (value / step).to_integral_value(rounding=ROUND_DOWN) * step


def round_price(price: Decimal, tick: Decimal, side: Side) -> Decimal:
    """Pasif tarafta kal: BUY aşağı, SELL yukarı yuvarla."""
    if tick <= 0:
        return price
    mode = ROUND_DOWN if side is Side.BUY else ROUND_UP
    return (price / tick).to_integral_value(rounding=mode) * tick


def normalize(req: OrderRequest, info: SymbolInfo) -> OrderRequest:
    """Miktarı step'e, fiyatı tick'e uydurur; min/max ve min-notional kurallarını denetler."""
    if not info.is_trading:
        raise OrderRejected(f"{info.symbol} şu an TRADING değil (status={info.status})")
    qty = floor_to_step(req.quantity, info.step_size)
    if qty < info.min_qty:
        raise OrderRejected(f"miktar {qty} < minQty {info.min_qty}")
    if info.max_qty > 0 and qty > info.max_qty:
        raise OrderRejected(f"miktar {qty} > maxQty {info.max_qty}")
    price = req.price
    if req.type is OrderType.LIMIT:
        if price is None or price <= 0:
            raise OrderRejected("LIMIT emri için fiyat gerekli")
        price = round_price(price, info.tick_size, req.side)
        ref = price
    else:
        ref = req.ref_price
        if ref is None:
            raise OrderRejected("MARKET emri için ref_price (notional kontrolü) gerekli")
    if qty * ref < info.min_notional:
        raise OrderRejected(f"notional {qty * ref:.8f} < minNotional {info.min_notional}")
    return OrderRequest(req.symbol, req.side, req.type, qty, price, req.ref_price, req.strategy, req.intent_id)
