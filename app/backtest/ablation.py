"""Katkı (ablation) analizi: her stratejinin / skor bileşeninin / risk motorunun sonuca etkisini ÖLÇER."""
from __future__ import annotations

from app.backtest.engine import BacktestCfg, Backtester, SignalCache, make_variant, with_params
from app.config import Settings

COMPONENTS = ["trend", "momentum", "volume", "breakout", "volatility", "liquidity"]


def run_ablation(cache: SignalCache, settings: Settings, cfg: BacktestCfg, start_ts: int, end_ts: int,
                 infos=None, progress=None) -> list[dict]:
    names = [s.name for s in cache.strats]
    rows: list[dict] = []

    def go(label: str, s: Settings | None = None, c: BacktestCfg | None = None, only=None, solo=None):
        s = s or settings
        if progress:
            progress(f"   ablation: {label}")
        r = Backtester(cache, make_variant(s, cache, only, solo), s, c or cfg, infos).run(start_ts, end_ts)
        m = r.metrics
        rows.append({"variant": label, "trades": m["n_trades"], "return": m["total_return"], "maxdd": m["max_drawdown"],
                     "sharpe": m["sharpe"], "pf": m["profit_factor"], "win": m["win_rate"],
                     "exp_pct": m["expectancy_pct"], "fees": m.get("fees_total", 0.0)})

    go("BASELINE (AI Composite, 6 strateji)")
    for n in names:
        go(f"- {n} çıkarıldı", only=[x for x in names if x != n])
    for n in names:
        go(f"yalnızca {n}", solo=n)
    for comp in COMPONENTS:
        go(f"{comp} ağırlığı = 0", s=with_params(settings, {f"scoring.{comp}_weight": 0.0}))
    go("risk motoru KAPALI (yalnız boyut)", c=BacktestCfg(**{**cfg.__dict__, "apply_risk": False}))
    go("çıkış sinyali açık (SELL oyu kapatır)", c=BacktestCfg(**{**cfg.__dict__, "exit_on_signal": True}))
    go("gevşek eşik (skor≥55, 1 oy)", s=with_params(settings, {"scoring.buy_score_threshold": 55.0,
                                                               "scoring.min_strategy_votes": 1}))
    base = rows[0]["return"]
    for r in rows:
        r["d_return"] = r["return"] - base
    return rows
