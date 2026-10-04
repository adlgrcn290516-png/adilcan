"""Walk-forward + son holdout.

* Veri zaman çizgisinin SON `holdout_frac` kısmı (varsayılan %20) parametre seçiminde ASLA kullanılmaz.
* Geri kalanında kayan pencere: TRAIN penceresinde küçük bir ızgaradan en iyi parametre seçilir, hemen sonraki TEST
  penceresinde (out-of-sample) bir kez değerlendirilir. TEST sonuçları zincirlenir.
* Aşırı uyum işaretleri: IS->OOS bozulma oranı, parametre kararlılığı, işlem sayısı, OOS Sharpe.
"""
from __future__ import annotations

import itertools
import math
from collections import Counter
from dataclasses import dataclass, field

from app.backtest import metrics as M
from app.backtest.engine import BacktestCfg, Backtester, SignalCache, make_variant, with_params
from app.config import Settings
from app.data.history import INTERVAL_MS

DAY = 86_400_000


def default_grid() -> list[dict]:
    """KÜÇÜK ızgara (18 kombinasyon): ne kadar çok deneme, o kadar çok şans eseri 'en iyi' bulma riski."""
    out = []
    for thr, votes, trail in itertools.product([58.0, 65.0, 72.0], [1, 2, 3], [1.5, 2.5]):
        out.append({"scoring.buy_score_threshold": thr, "scoring.min_strategy_votes": votes,
                    "position.trail_start_r": trail})
    return out


def objective_value(m: dict, name: str, min_trades: int) -> float:
    if m["n_trades"] < min_trades:
        return float("-inf")
    if name == "profit_factor":
        pf = m["profit_factor"]
        return float(pf) if not math.isnan(pf) else float("-inf")
    if name == "return_over_dd":
        return m["total_return"] / max(m["max_drawdown"], 1e-9)
    return float(m["sharpe"])


@dataclass
class Fold:
    idx: int
    train: tuple[int, int]
    test: tuple[int, int]
    best_params: dict
    is_obj: float
    oos_obj: float
    is_metrics: dict
    oos_metrics: dict
    n_combos: int
    is_median_obj: float


@dataclass
class WFResult:
    folds: list[Fold]
    oos_equity: list[tuple[int, float]]
    oos_trades: list
    oos_metrics: dict
    chosen: dict
    param_counts: Counter
    holdout_chosen: dict | None
    holdout_default: dict | None
    holdout_window: tuple[int, int]
    verdict: list[str] = field(default_factory=list)


def chain(curves: list[list[tuple[int, float]]], start: float) -> list[tuple[int, float]]:
    out: list[tuple[int, float]] = []
    level = start
    for c in curves:
        if not c:
            continue
        base = c[0][1]
        for ts, e in c:
            out.append((ts, level * e / base))
        level = out[-1][1]
    return out


def split_points(cache: SignalCache, cfg: BacktestCfg, holdout_frac: float) -> tuple[int, int, int]:
    """(t0: göstergeler oturduktan sonraki başlangıç, hold_start: holdout başlangıcı, t1: son)"""
    tl = sorted({int(ct) for p in cache.prep.values() for ct in p.a["ct"]})
    t0 = tl[0] + cfg.warmup_bars * INTERVAL_MS[cfg.interval]
    t1 = tl[-1]
    return t0, t0 + int((t1 - t0) * (1 - holdout_frac)), t1


def walk_forward(cache: SignalCache, settings: Settings, cfg: BacktestCfg, *, grid: list[dict] | None = None,
                 train_days: int = 90, test_days: int = 30, step_days: int | None = None, holdout_frac: float = 0.2,
                 min_trades: int = 10, objective: str = "sharpe", infos=None, progress=None) -> WFResult:
    grid = grid or default_grid()
    step_days = step_days or test_days
    iv = INTERVAL_MS[cfg.interval]
    t0, hold_start, t1 = split_points(cache, cfg, holdout_frac)   # t0: göstergeler oturmadan işlem yok
    folds: list[Fold] = []
    a = t0
    while a + (train_days + test_days) * DAY <= hold_start:
        tr = (a, a + train_days * DAY)
        te = (tr[1] + 1, tr[1] + test_days * DAY)
        scores = []
        for params in grid:
            s2 = with_params(settings, params)
            bt = Backtester(cache, make_variant(s2, cache), s2, cfg, infos)
            r = bt.run(*tr, params=params)
            scores.append((objective_value(r.metrics, objective, min_trades), params, r.metrics))
        scores.sort(key=lambda x: x[0], reverse=True)
        best_obj, best_params, is_m = scores[0]
        finite = sorted(x[0] for x in scores if x[0] != float("-inf"))
        s2 = with_params(settings, best_params)
        oos = Backtester(cache, make_variant(s2, cache), s2, cfg, infos).run(*te, params=best_params)
        folds.append(Fold(len(folds) + 1, tr, te, best_params, best_obj,
                          objective_value(oos.metrics, objective, 0), is_m, oos.metrics, len(grid),
                          finite[len(finite) // 2] if finite else float("-inf")))
        folds[-1]._oos = oos  # type: ignore[attr-defined]
        if progress:
            progress(f"fold {len(folds)} bitti")
        a += step_days * DAY
    curves = [f._oos.equity for f in folds]  # type: ignore[attr-defined]
    trades = [t for f in folds for t in f._oos.trades]  # type: ignore[attr-defined]
    eq = chain(curves, cfg.initial_capital)
    oos_m = M.compute(eq, trades, iv, sum(f._oos.exposure_bars for f in folds)) if eq else {}  # type: ignore[attr-defined]
    counts = Counter(tuple(sorted(f.best_params.items())) for f in folds)
    chosen = dict(counts.most_common(1)[0][0]) if counts else {}
    hold = (hold_start + 1, t1)
    hc = hd = None
    if folds:
        s2 = with_params(settings, chosen)
        hc = Backtester(cache, make_variant(s2, cache), s2, cfg, infos).run(*hold, params=chosen).metrics
        hd = Backtester(cache, make_variant(settings, cache), settings, cfg, infos).run(*hold).metrics
    res = WFResult(folds, eq, trades, oos_m, chosen, counts, hc, hd, hold)
    res.verdict = assess(res, objective, min_trades)
    return res


def assess(r: WFResult, objective: str, min_trades: int) -> list[str]:
    v: list[str] = []
    if not r.folds:
        return ["YETERSİZ VERİ: hiç walk-forward katmanı oluşmadı (daha fazla gün gerekli)."]
    n = r.oos_metrics.get("n_trades", 0)
    if n < 30:
        v.append(f"UYARI: OOS toplam işlem sayısı çok az ({n} < 30): sonuçlar istatistiksel olarak ANLAMSIZ.")
    ins = [f.is_obj for f in r.folds if f.is_obj != float("-inf")]
    oos = [f.oos_obj for f in r.folds if f.oos_obj != float("-inf")]
    if ins and oos and sum(ins) > 0:
        deg = (sum(oos) / len(oos)) / (sum(ins) / len(ins))
        v.append(f"IS->OOS bozulma oranı ({objective}): {deg:.2f} (1.0 = bozulma yok; <0.5 aşırı uyum işareti)")
        if deg < 0.5:
            v.append("UYARI: Ciddi IS->OOS bozulma: seçilen parametreler büyük olasılıkla geçmişe uyduruldu.")
    if r.oos_metrics.get("total_return", 0) <= 0:
        v.append("BULGU: Out-of-sample toplam getiri ≤ 0 -> bu konfigürasyonun maliyet sonrası kenarı KANITLANAMADI.")
    if r.oos_metrics.get("sharpe", 0) <= 0:
        v.append("BULGU: OOS Sharpe ≤ 0.")
    top = r.param_counts.most_common(1)[0][1] if r.param_counts else 0
    if len(r.folds) >= 3 and top / len(r.folds) < 0.5:
        v.append(f"UYARI: Parametre kararsız (en sık seçilen yalnızca {top}/{len(r.folds)} katmanda): tipik aşırı uyum belirtisi.")
    if not v:
        v.append("Belirgin aşırı uyum işareti yok (bu, kârlılık GARANTİSİ değildir).")
    return v
