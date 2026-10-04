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
            # basit migrasyon: eski DB'lerde positions.extra yoksa ekle (Faz 3'te oluşmuş dosyalar bozulmasın)
            cols = {r["name"] for r in self.conn.execute("PRAGMA table_info(positions)")}
            if "extra" not in cols:
                self.conn.execute("ALTER TABLE positions ADD COLUMN extra TEXT")
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


class SqliteSignalRepository:
    """signals + strategy_results + market_snapshots tablolarına kayıt (kararlar sonradan incelenebilsin)."""

    def __init__(self, db: SqliteDb):
        self.db = db

    def record(self, o) -> None:  # o: app.core.scanner.Opportunity
        import json
        with self.db.lock:
            c = self.db.conn
            c.execute("INSERT INTO signals(ts,symbol,market,score,signal,confidence,entry,stop,tp,reasons) "
                      "VALUES(?,?,?,?,?,?,?,?,?,?)",
                      (o.ts, o.symbol, o.market, o.score, o.signal.value, o.confidence, o.entry, o.stop, o.tp1,
                       json.dumps({"contributions": o.contributions, "note": o.reason})))
            for v in o.votes:
                c.execute("INSERT INTO strategy_results(ts,symbol,strategy,signal,confidence,detail) VALUES(?,?,?,?,?,?)",
                          (o.ts, o.symbol, v["strategy"], v["signal"], v["confidence"], v["reason"]))
            c.execute("INSERT INTO market_snapshots(ts,symbol,data) VALUES(?,?,?)", (o.ts, o.symbol, json.dumps(o.features)))
            c.commit()

    def count(self, table: str = "signals") -> int:
        with self.db.lock:
            return self.db.conn.execute(f"SELECT COUNT(*) FROM {table}").fetchone()[0]


class SqlitePortfolioRepository:
    """positions / trades / portfolio_snapshots / risk_events."""

    def __init__(self, db: SqliteDb):
        self.db = db

    # ---- positions (ek alanlar JSON 'extra' içinde) ----
    def save_position(self, pos) -> int:
        import json
        d = json.dumps(pos.to_dict())
        with self.db.lock:
            c = self.db.conn
            if pos.id is None:
                cur = c.execute(
                    "INSERT INTO positions(symbol,market,side,qty,entry_price,stop_loss,take_profit,status,opened_at,closed_at,extra)"
                    " VALUES(?,?,?,?,?,?,?,?,?,?,?)",
                    (pos.symbol, "SPOT", "LONG", str(pos.qty), str(pos.entry_price), str(pos.stop), str(pos.tp1),
                     pos.status, pos.opened_at, pos.closed_at, d))
                pos.id = cur.lastrowid
            else:
                c.execute("UPDATE positions SET qty=?,stop_loss=?,take_profit=?,status=?,closed_at=?,extra=? WHERE id=?",
                          (str(pos.qty), str(pos.stop), str(pos.tp1), pos.status, pos.closed_at, d, pos.id))
            c.commit()
        return pos.id

    def open_positions(self) -> list[dict]:
        import json
        with self.db.lock:
            rows = self.db.conn.execute("SELECT id, extra FROM positions WHERE status='OPEN'").fetchall()
        out = []
        for r in rows:
            d = json.loads(r["extra"] or "{}")
            d["id"] = r["id"]
            out.append(d)
        return out

    # ---- trades ----
    def record_trade(self, ts, symbol, side, qty, price, fee, fee_asset, cid, strategy, pnl) -> None:
        with self.db.lock:
            self.db.conn.execute(
                "INSERT INTO trades(ts,symbol,side,qty,price,fee,fee_asset,client_order_id,strategy,pnl) VALUES(?,?,?,?,?,?,?,?,?,?)",
                (ts, symbol, side, str(qty), str(price), str(fee), fee_asset, cid, strategy, str(pnl)))
            self.db.conn.commit()

    def trades(self) -> list[sqlite3.Row]:
        with self.db.lock:
            return self.db.conn.execute("SELECT * FROM trades ORDER BY ts").fetchall()

    # ---- snapshots ----
    def record_snapshot(self, ts, equity, cash, exposure, daily_pnl, drawdown) -> None:
        with self.db.lock:
            self.db.conn.execute(
                "INSERT INTO portfolio_snapshots(ts,equity,cash,exposure,daily_pnl,drawdown) VALUES(?,?,?,?,?,?)",
                (ts, str(equity), str(cash), str(exposure), str(daily_pnl), str(drawdown)))
            self.db.conn.commit()

    def first_equity_since(self, ts: float) -> Decimal | None:
        with self.db.lock:
            r = self.db.conn.execute("SELECT equity FROM portfolio_snapshots WHERE ts>=? ORDER BY ts LIMIT 1", (ts,)).fetchone()
        return Decimal(r["equity"]) if r else None

    def peak_equity(self) -> Decimal | None:
        with self.db.lock:
            rows = self.db.conn.execute("SELECT equity FROM portfolio_snapshots").fetchall()
        return max((Decimal(r["equity"]) for r in rows), default=None)

    # ---- risk events ----
    def record_risk_event(self, ts, symbol, decision, reason) -> None:
        with self.db.lock:
            self.db.conn.execute("INSERT INTO risk_events(ts,symbol,decision,reason) VALUES(?,?,?,?)", (ts, symbol, decision, reason))
            self.db.conn.commit()

    def risk_events(self) -> list[sqlite3.Row]:
        with self.db.lock:
            return self.db.conn.execute("SELECT * FROM risk_events ORDER BY ts").fetchall()
