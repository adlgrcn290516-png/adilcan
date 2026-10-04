"""Pozisyon yönetim kuralları — SAF mantık. Canlı PortfolioManager (tick: high=low=bid) ve Backtest (OHLC bar)
AYNI fonksiyonu kullanır.

Bar içi sıralama ölçülemez; bu yüzden KÖTÜMSER varsayılır:
  1) bar BAŞINDAKİ stop, bar'ın low'u ile test edilir (gap varsa open'dan dolum),
  2) bar içinde stop yukarı taşındıysa ve low yeni stop'a değiyorsa "önce yukarı sonra aşağı" yolu varsayılıp çıkılır.
"""
from __future__ import annotations

from dataclasses import dataclass
from decimal import Decimal

from app.config import PositionMgmtCfg
from app.portfolio.position import Position

D = Decimal


@dataclass
class Action:
    kind: str                 # STOP | TRAILING/BE_STOP | TP2 | TP1_PARTIAL
    price: Decimal            # beklenen çıkış fiyatı (backtest dolumu için; canlı piyasa emri kullanır)
    qty_fraction: Decimal     # 1 = tamamı


def _stop_kind(pos: Position) -> str:
    return "STOP" if pos.stop <= pos.initial_stop else "TRAILING/BE_STOP"


def be_level(pos: Position, cfg: PositionMgmtCfg) -> Decimal:
    return pos.entry_price * (1 + D(str(cfg.breakeven_buffer_pct)) / 100)


def step(pos: Position, high: Decimal, low: Decimal, cfg: PositionMgmtCfg, open_: Decimal | None = None) -> Action | None:
    """Bir bar/tick için bir sonraki eylemi döndürür (yoksa None). Stop'u yalnızca YUKARI taşır.
    Çağıran, eylemi uyguladıktan sonra tekrar çağırır (TP1 kısmi sonrası trailing/BE devam eder)."""
    R = pos.risk_dist
    # 1) bar başındaki stop
    if low <= pos.stop:
        px = pos.stop if open_ is None else min(pos.stop, open_)   # gap aşağı: open'dan dolum
        return Action(_stop_kind(pos), px, D(1))
    stop_before = pos.stop
    pos.highest = max(pos.highest, high)
    # 2) TP2
    if high >= pos.tp2:
        return Action("TP2", pos.tp2, D(1))
    # 3) break-even
    if not pos.breakeven_done and high >= pos.entry_price + D(str(cfg.breakeven_at_r)) * R:
        pos.raise_stop(be_level(pos, cfg))
        pos.breakeven_done = True
    # 4) TP1 kısmi
    if not pos.partial_taken and high >= pos.tp1:
        return Action("TP1_PARTIAL", pos.tp1, D(str(cfg.partial_tp_pct)))
    # 5) trailing
    if high >= pos.entry_price + D(str(cfg.trail_start_r)) * R:
        pos.raise_stop(pos.highest - D(str(cfg.trail_dist_r)) * R)
    # 6) kötümser bar-içi yeniden test: bu çağrıda stop yükseldiyse ve low yeni stop'a değiyorsa çık
    if pos.stop > stop_before and low <= pos.stop:
        return Action(_stop_kind(pos), pos.stop, D(1))
    return None


def mark_partial(pos: Position, cfg: PositionMgmtCfg) -> None:
    """TP1 kısmi satışı GERÇEKLEŞTİKTEN sonra çağrılır: stop break-even'a çekilir."""
    pos.partial_taken = True
    pos.raise_stop(be_level(pos, cfg))
    pos.breakeven_done = True
