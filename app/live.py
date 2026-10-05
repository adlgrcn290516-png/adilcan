"""Gerçek para yardımcıları: toplam kayıp sigortası ve tek emirlik uçtan uca test.

KİLİTLER (hepsi gerekir): (1) TRADING_MODE=live + LIVE_TRADING_CONFIRM cümlesi (Settings),
(2) app/execution/safety.REAL_MONEY_ENABLED=True (kod), (3) bat dosyasında kullanıcının yazdığı EVET.
"""
from __future__ import annotations

import json
import logging
import time
from decimal import Decimal
from pathlib import Path

from app.execution.filters import floor_to_step
from app.execution.types import OrderRequest, OrderType, Side

log = logging.getLogger(__name__)
D = Decimal


class LiveGuard:
    """Toplam özsermaye başlangıçtan `max_loss` (quote) kadar düşerse: acil durdurma + tüm pozisyonları kapat."""

    def __init__(self, mgr, orch, path: str | Path, max_loss: Decimal):
        self.mgr, self.orch, self.path, self.max_loss = mgr, orch, Path(path), D(str(max_loss))
        self.tripped = False
        self.start: Decimal | None = None
        try:
            self.start = D(json.loads(self.path.read_text(encoding="utf-8"))["start_equity"])
        except Exception:  # noqa: BLE001
            pass

    def __call__(self) -> None:
        if self.tripped:
            return
        quotes = self.orch.quotes_for(set(self.mgr.positions)) if self.mgr.positions else {}
        eq = self.mgr.equity(quotes)
        if self.start is None:
            self.start = eq
            self.path.write_text(json.dumps({"start_equity": str(eq), "max_loss": str(self.max_loss)}), encoding="utf-8")
            log.info("SİGORTA: başlangıç özsermayesi %s, azami kayıp %s", eq, self.max_loss)
        if eq <= self.start - self.max_loss:
            self.tripped = True
            log.critical("SİGORTA DEVREDE: özsermaye %s <= %s - %s -> ACİL DURDURMA + tüm pozisyonlar kapatılıyor",
                         eq, self.start, self.max_loss)
            self.mgr.set_emergency(True, close_positions=True, quotes=quotes)


def smoke_test(engine, broker, adapter, info, symbol: str = "BTCUSDT", margin: Decimal = D("1.3")) -> dict:
    """Tek küçük AL + hemen SAT. Emir yolunun gerçek borsada çalıştığını doğrular. Maliyet ≈ komisyon + spread."""
    si = info[symbol]
    ob = adapter.order_book(symbol, 5)
    ask, bid = ob.asks[0][0], ob.bids[0][0]
    qty = floor_to_step(si.min_notional * margin / ask, si.step_size) + si.step_size
    qty = max(qty, si.min_qty)
    usdt0, base0 = broker.free_balance(si.quote), broker.free_balance(si.base)
    tag = str(int(time.time()))
    buy = engine.place(OrderRequest(symbol, Side.BUY, OrderType.MARKET, qty, None, ask, "livetest", tag))
    out = {"qty": qty, "buy": buy, "sell": None, "usdt_before": usdt0}
    if buy.executed_qty <= 0:
        return out
    held = floor_to_step(broker.free_balance(si.base) - base0, si.step_size)  # komisyon base'den düşmüş olabilir
    sell = engine.place(OrderRequest(symbol, Side.SELL, OrderType.MARKET, held, None, bid, "livetest", tag))
    out["sell"] = sell
    out["usdt_after"] = broker.free_balance(si.quote)
    out["base_left"] = broker.free_balance(si.base) - base0
    return out
