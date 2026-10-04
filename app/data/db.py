"""SQLite + repository pattern (sonra PostgreSQL'e geçmek için arayüz ayrı)."""
from __future__ import annotations

import sqlite3
import threading
import time
from abc import ABC, abstractmethod
from decimal import Decimal
from pathlib import Path

from app.execution.types import OrderState, OrderType, Side, Status

SCHEMA = """
CREATE TABLE IF NOT EXISTS orders(
  client_order_id TEXT PRIMARY KEY, intent_key TEXT UNIQUE NOT NULL, broker TEXT, symbol TEXT, side TEXT, type TEXT,
  status TEXT, orig_qty TEXT, executed_qty TEXT, quote_qty TEXT, price TEXT, order_id TEXT,
  fee_asset TEXT, fee_amount TEXT, verified INTEGER, reason TEXT, created_at REAL, updated_at REAL);
CREATE TABLE IF NOT EXISTS trades(id INTEGER PRIMARY KEY AUTOINCREMENT, ts REAL, symbol TEXT, side TEXT, qty TEXT,
  price TEXT, fee TEXT, fee_asset TEXT, client_order_id TEXT, strategy TEXT, pnl TEXT);
CREATE TABLE IF NOT EXISTS positions(id INTEGER PRIMARY KEY AUTOINCREMENT, symbol TEXT, market TEXT, side TEXT, qty TEXT,
  entry_price TEXT, stop_loss TEXT, take_profit TEXT, status TEXT, opened_at REAL, closed_at REAL);
CREATE TABLE IF NOT EXISTS signals(id INTEGER PRIMARY KEY AUTOINCREMENT, ts REAL, symbol TEXT, market TEXT, score REAL,
  signal TEXT, confidence REAL, entry TEXT, stop TEXT, tp TEXT, reasons TEXT);
CREATE TABLE IF NOT EXISTS market_snapshots(id INTEGER PRIMARY KEY AUTOINCREMENT, ts REAL, symbol TEXT, data TEXT);
CREATE TABLE IF NOT EXISTS strategy_results(id INTEGER PRIMARY KEY AUTOINCREMENT, ts REAL, symbol TEXT, strategy TEXT,
  signal TEXT, confidence REAL, detail TEXT);
CREATE TABLE IF NOT EXISTS portfolio_snapshots(id INTEGER PRIMARY KEY AUTOINCREMENT, ts REAL, equity TEXT, cash TEXT,
  exposure TEXT, daily_pnl TEXT, drawdown TEXT);
CREATE TABLE IF NOT EXISTS risk_events(id INTEGER PRIMARY KEY AUTOINCREMENT, ts REAL, symbol TEXT, decision TEXT,
  reason TEXT);
"""


class OrderRepository(ABC):
    @abstractmethod
    def get_by_intent(self, intent_key: str) -> OrderState | None: ...
    @abstractmethod
    def get(self, client_order_id: str) -> OrderState | None: ...
    @abstractmethod
    def insert(self, intent_key: str, st: OrderState, broker: str) -> None: ...
    @abstractmethod
    def update(self, st: OrderState) -> None: ...
    @abstractmethod
    def all(self) -> list[OrderState]: ...


class SqliteDb:
    def __init__(self, path: str | Path = "data/trading.db"):
        if str(path) != ":memory:":
            Path(path).parent.mkdir(parents=True, exist_ok=True)
        self.conn = sqlite3.connect(str(path), check_same_thread=False)
        self.conn.row_factory = sqlite3.Row
        self.lock = threading.Lock()
        with self.lock:
            self.conn.executescript(SCHEMA)
            self.conn.commit()


def _row_to_state(r: sqlite3.Row) -> OrderState:
    return OrderState(
        client_order_id=r["client_order_id"], symbol=r["symbol"], side=Side(r["side"]), type=OrderType(r["type"]),
        status=Status(r["status"]), orig_qty=Decimal(r["orig_qty"]), executed_qty=Decimal(r["executed_qty"]),
        quote_qty=Decimal(r["quote_qty"]), price=Decimal(r["price"]) if r["price"] else None,
        order_id=r["order_id"] or "", fee_asset=r["fee_asset"] or "", fee_amount=Decimal(r["fee_amount"] or 0),
        verified=bool(r["verified"]), reason=r["reason"] or "")


class SqliteOrderRepository(OrderRepository):
    def __init__(self, db: SqliteDb):
        self.db = db

    def _fetch(self, where: str, arg: str) -> OrderState | None:
        with self.db.lock:
            r = self.db.conn.execute(f"SELECT * FROM orders WHERE {where}=?", (arg,)).fetchone()
        return _row_to_state(r) if r else None

    def get_by_intent(self, intent_key: str) -> OrderState | None:
        return self._fetch("intent_key", intent_key)

    def get(self, client_order_id: str) -> OrderState | None:
        return self._fetch("client_order_id", client_order_id)

    def insert(self, intent_key: str, st: OrderState, broker: str) -> None:
        now = time.time()
        with self.db.lock:
            self.db.conn.execute(
                "INSERT INTO orders VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                (st.client_order_id, intent_key, broker, st.symbol, st.side.value, st.type.value, st.status.value,
                 str(st.orig_qty), str(st.executed_qty), str(st.quote_qty), str(st.price) if st.price else None,
                 st.order_id, st.fee_asset, str(st.fee_amount), int(st.verified), st.reason, now, now))
            self.db.conn.commit()

    def update(self, st: OrderState) -> None:
        with self.db.lock:
            self.db.conn.execute(
                "UPDATE orders SET status=?,executed_qty=?,quote_qty=?,price=?,order_id=?,fee_asset=?,fee_amount=?,"
                "verified=?,reason=?,updated_at=? WHERE client_order_id=?",
                (st.status.value, str(st.executed_qty), str(st.quote_qty), str(st.price) if st.price else None,
                 st.order_id, st.fee_asset, str(st.fee_amount), int(st.verified), st.reason, time.time(),
                 st.client_order_id))
            self.db.conn.commit()

    def all(self) -> list[OrderState]:
        with self.db.lock:
            rows = self.db.conn.execute("SELECT * FROM orders ORDER BY created_at").fetchall()
        return [_row_to_state(r) for r in rows]
