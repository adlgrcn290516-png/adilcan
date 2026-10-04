"""Metin/Markdown rapor üretimi."""
from __future__ import annotations

import math

from app.backtest.walkforward import WFResult


def pct(x, d=2):
    return "n/a" if x is None or (isinstance(x, float) and math.isnan(x)) else f"{x * 100:.{d}f}%"


def num(x, d=2):
    if x is None or (isinstance(x, float) and math.isnan(x)):
        return "n/a"
    return "inf" if x == float("inf") else f"{x:.{d}f}"


def metrics_block(m: dict, title: str) -> str:
    return (f"{title}\n"
            f"  Total Return {pct(m.get('total_return'))} | CAGR {pct(m.get('cagr'))} | Max Drawdown {pct(m.get('max_drawdown'))}\n"
            f"  Sharpe {num(m.get('sharpe'))} | Sortino {num(m.get('sortino'))} | Win Rate {pct(m.get('win_rate'), 1)} | "
            f"Profit Factor {num(m.get('profit_factor'))}\n"
            f"  Avg Win {num(m.get('avg_win'))} | Avg Loss {num(m.get('avg_loss'))} | Expectancy {num(m.get('expectancy'))} "
            f"({num(m.get('expectancy_pct'))}%/işlem, ort. R {num(m.get('avg_r'))}) | İşlem sayısı {m.get('n_trades')}\n"
            f"  Maruziyet {pct(m.get('exposure'), 1)} | Toplam ücret/kayma dahil komisyon {num(m.get('fees_total', m.get('fees', 0)))}")


def ablation_table(rows: list[dict]) -> str:
    h = f"{'Varyant':<42}{'İşlem':>6}{'Getiri':>9}{'ΔGetiri':>9}{'MaxDD':>8}{'Sharpe':>8}{'PF':>6}{'Win':>7}{'Exp%':>7}"
    L = [h, "-" * len(h)]
    for r in rows:
        L.append(f"{r['variant']:<42}{r['trades']:>6}{pct(r['return'], 1):>9}{pct(r['d_return'], 1):>9}{pct(r['maxdd'], 1):>8}"
                 f"{num(r['sharpe']):>8}{num(r['pf']):>6}{pct(r['win'], 0):>7}{num(r['exp_pct']):>7}")
    return "\n".join(L)


def wf_table(r: WFResult, objective: str) -> str:
    h = f"{'Fold':>4} {'Seçilen parametre':<44}{'IS ' + objective:>12}{'OOS ' + objective:>12}{'OOS getiri':>11}{'OOS işlem':>10}"
    L = [h, "-" * len(h)]
    for f in r.folds:
        p = ", ".join(f"{k.split('.')[-1]}={v}" for k, v in f.best_params.items())
        L.append(f"{f.idx:>4} {p:<44}{num(f.is_obj):>12}{num(f.oos_obj):>12}{pct(f.oos_metrics.get('total_return'), 1):>11}"
                 f"{f.oos_metrics.get('n_trades', 0):>10}")
    return "\n".join(L)
