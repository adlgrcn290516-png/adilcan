"""Risk bazlı pozisyon boyutu:  qty = (equity * risk_per_trade) / stop_mesafesi"""
from __future__ import annotations

from decimal import Decimal


def risk_based_qty(equity: Decimal, risk_pct: float, entry: Decimal, stop: Decimal) -> Decimal:
    dist = entry - stop
    if dist <= 0:
        raise ValueError("stop entry'nin altında olmalı (long)")
    return equity * Decimal(str(risk_pct)) / dist


def size_cap_qty(equity: Decimal, max_position_pct: float, entry: Decimal) -> Decimal:
    return equity * Decimal(str(max_position_pct)) / entry
