"""Backtest metrikleri: Total Return, CAGR, Max Drawdown, Sharpe, Sortino, Win Rate, Profit Factor,
Avg Win/Loss, Expectancy, #Trades (+ maruziyet, ücret, buy&hold kıyası)."""
from __future__ import annotations

import math
from dataclasses import dataclass, field

import numpy as np

YEAR_MS = 365 * 86_400_000


@dataclass
class Trade:
    symbol: str
    entry_ts: int
    exit_ts: int
    entry_price: float
    exit_price: float          # çıkışların ağırlıklı ortalaması
    qty: float                 # başlangıç miktarı
    pnl: float                 # quote, komisyon sonrası
    pnl_pct: float             # maliyete göre %
    r_multiple: float
    reasons: list[str] = field(default_factory=list)
    bars_held: int = 0
    fees: float = 0.0


def max_drawdown(eq: np.ndarray) -> float:
    if len(eq) == 0:
        return 0.0
    peak = np.maximum.accumulate(eq)
    return float(((peak - eq) / peak).max())


def compute(equity: list[tuple[int, float]], trades: list[Trade], interval_ms: int, exposure_bars: int = 0) -> dict:
    ts = np.array([t for t, _ in equity], dtype=float)
    eq = np.array([e for _, e in equity], dtype=float)
    out: dict = {"n_trades": len(trades)}
    if len(eq) < 2:
        return {**out, "total_return": 0.0, "cagr": float("nan"), "max_drawdown": 0.0, "sharpe": float("nan"),
                "sortino": float("nan"), "win_rate": float("nan"), "profit_factor": float("nan"),
                "avg_win": 0.0, "avg_loss": 0.0, "expectancy": 0.0, "expectancy_pct": 0.0, "exposure": 0.0, "fees": 0.0}
    total = eq[-1] / eq[0] - 1
    days = (ts[-1] - ts[0]) / 86_400_000
    cagr = (1 + total) ** (365 / days) - 1 if days >= 1 and (1 + total) > 0 else float("nan")
    r = np.diff(eq) / eq[:-1]
    ppy = YEAR_MS / interval_ms
    sd = r.std(ddof=1) if len(r) > 1 else 0.0
    sharpe = float(r.mean() / sd * math.sqrt(ppy)) if sd > 0 else 0.0
    dn = np.minimum(r, 0)
    dd = math.sqrt(float((dn ** 2).mean()))
    sortino = float(r.mean() / dd * math.sqrt(ppy)) if dd > 0 else (float("inf") if r.mean() > 0 else 0.0)
    wins = [t.pnl for t in trades if t.pnl > 0]
    losses = [t.pnl for t in trades if t.pnl <= 0]
    gp, gl = sum(wins), -sum(losses)
    out.update(
        total_return=float(total), cagr=float(cagr), max_drawdown=max_drawdown(eq), sharpe=sharpe, sortino=sortino,
        win_rate=len(wins) / len(trades) if trades else float("nan"),
        profit_factor=(gp / gl if gl > 0 else (float("inf") if gp > 0 else float("nan"))),
        avg_win=float(np.mean(wins)) if wins else 0.0, avg_loss=float(np.mean(losses)) if losses else 0.0,
        expectancy=float(np.mean([t.pnl for t in trades])) if trades else 0.0,
        expectancy_pct=float(np.mean([t.pnl_pct for t in trades])) if trades else 0.0,
        avg_r=float(np.mean([t.r_multiple for t in trades])) if trades else 0.0,
        avg_bars_held=float(np.mean([t.bars_held for t in trades])) if trades else 0.0,
        exposure=exposure_bars / max(len(eq) - 1, 1), fees=float(sum(t.fees for t in trades)),
        days=float(days))
    return out


def buy_and_hold(data: dict, start_ts: int, end_ts: int) -> dict:
    """Aynı pencerede, her sembolü tutmanın getirisi (eşit ağırlık ortalaması) — kıyas ölçütü."""
    rets = {}
    for sym, df in data.items():
        w = df[(df["close_time"] >= start_ts) & (df["close_time"] <= end_ts)]
        if len(w) >= 2:
            rets[sym] = float(w["close"].iloc[-1] / w["close"].iloc[0] - 1)
    return {"per_symbol": rets, "equal_weight": float(np.mean(list(rets.values()))) if rets else float("nan"),
            "btc": rets.get("BTCUSDT")}
