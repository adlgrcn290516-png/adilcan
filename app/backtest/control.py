"""Kontrol deneyi: AYNI risk/pozisyon kuralları ve maliyetlerle ama RASTGELE zamanlarda giriş.

Soru: sinyal motoru, rastgele girişlerden anlamlı şekilde iyi mi? Değilse 'kenar' sinyalden değil, rejimden/şanstan gelir.
"""
from __future__ import annotations

import random

import numpy as np

from app.backtest.engine import BacktestCfg, Backtester, SignalCache
from app.config import Settings
from app.core.features import Features
from app.strategies.base import Signal, SignalResult
from app.strategies.library import TrendFollowing


class RandomEntryVariant:
    def __init__(self, settings: Settings, p: float, seed: int):
        self.rng = random.Random(seed)
        self.p = p
        self._maker = TrendFollowing(settings.strategy)  # yalnızca ATR tabanlı stop/TP seviyeleri için

    def decide(self, f: Features, outputs) -> SignalResult:
        if f.atr > 0 and self.rng.random() < self.p:
            r = self._maker.buy(f, 0.6, "rastgele giriş (kontrol)")
            r.details = {"score": self.rng.random() * 100}
            return r
        return SignalResult("random", Signal.HOLD)


def random_control(cache: SignalCache, settings: Settings, cfg: BacktestCfg, start_ts: int, end_ts: int,
                   n_signals: int, n_runs: int = 20, infos=None, progress=None) -> list[dict]:
    tl = [t for t in cache.timeline_all() if start_ts <= t <= end_ts]
    eligible = max(1, len(tl) * len(cache.prep))
    p = min(1.0, n_signals / eligible)
    out = []
    for k in range(n_runs):
        if progress:
            progress(f"   rastgele kontrol {k + 1}/{n_runs}")
        bt = Backtester(cache, RandomEntryVariant(settings, p, seed=1000 + k), settings, cfg, infos)
        m = bt.run(start_ts, end_ts).metrics
        out.append({"return": m["total_return"], "pf": m["profit_factor"], "exp_pct": m["expectancy_pct"],
                    "trades": m["n_trades"], "sharpe": m["sharpe"]})
    return out


def summarize(ctrl: list[dict], baseline_return: float) -> dict:
    r = np.array([c["return"] for c in ctrl])
    return {"mean": float(r.mean()), "median": float(np.median(r)), "min": float(r.min()), "max": float(r.max()),
            "std": float(r.std()), "pctile": float((r < baseline_return).mean()),
            "mean_trades": float(np.mean([c["trades"] for c in ctrl])),
            "mean_exp_pct": float(np.mean([c["exp_pct"] for c in ctrl]))}
