"""PaperBroker durumunu diske yazar/okur (yeniden başlatmada sanal bakiye ve emirler kaybolmasın)."""
from __future__ import annotations

import json
import os
from decimal import Decimal
from pathlib import Path

from app.execution.types import OrderState, OrderType, Side, Status
from app.paper.broker import PaperBroker


def _od(o: OrderState) -> dict:
    return {"client_order_id": o.client_order_id, "symbol": o.symbol, "side": o.side.value, "type": o.type.value,
            "status": o.status.value, "orig_qty": str(o.orig_qty), "executed_qty": str(o.executed_qty),
            "quote_qty": str(o.quote_qty), "price": str(o.price) if o.price is not None else None,
            "order_id": o.order_id, "fee_asset": o.fee_asset, "fee_amount": str(o.fee_amount),
            "verified": o.verified, "reason": o.reason}


def _do(d: dict) -> OrderState:
    return OrderState(d["client_order_id"], d["symbol"], Side(d["side"]), OrderType(d["type"]), Status(d["status"]),
                      Decimal(d["orig_qty"]), Decimal(d["executed_qty"]), Decimal(d["quote_qty"]),
                      Decimal(d["price"]) if d["price"] is not None else None, d["order_id"], d["fee_asset"],
                      Decimal(d["fee_amount"]), d["verified"], d["reason"])


def save(broker: PaperBroker, path: str | Path) -> None:
    p = Path(path)
    p.parent.mkdir(parents=True, exist_ok=True)
    state = {"free": {k: str(v) for k, v in broker.free.items()}, "locked": {k: str(v) for k, v in broker.locked.items()},
             "seq": broker._seq, "orders": {k: _od(v) for k, v in broker.orders.items()}}
    tmp = p.with_suffix(".tmp")
    tmp.write_text(json.dumps(state), encoding="utf-8")
    os.replace(tmp, p)  # atomik: yazarken kesilse bile eski dosya bozulmaz


def load(broker: PaperBroker, path: str | Path) -> bool:
    p = Path(path)
    if not p.exists():
        return False
    d = json.loads(p.read_text(encoding="utf-8"))
    broker.free = {k: Decimal(v) for k, v in d["free"].items()}
    broker.locked = {k: Decimal(v) for k, v in d["locked"].items()}
    broker._seq = d["seq"]
    broker.orders = {k: _do(v) for k, v in d["orders"].items()}
    return True
