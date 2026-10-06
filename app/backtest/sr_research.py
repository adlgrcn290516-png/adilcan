"""Araştırma #7: Fibonacci geri çekilme + destek + MACD/RSI teyidi (strategies/fib_pullback.py).

TASARIM (sonuç görülmeden SABİTLENDİ): parametre seçimi/ızgara YOK -> walk-forward gerekmez.
Dönem ikiye bölünür: [başlangıç, holdout) ve son %20 holdout. Aynı motor, maliyet ve pozisyon kuralları.
Kabul kriterleri (hepsi gerekir):
  1. Holdout öncesi: toplam getiri > 0 ve profit factor >= 1.2
  2. Holdout öncesi işlem sayısı >= 100
  3. İşlem başı beklenti, aynı motorda RASTGELE girişten >= +0.30 puan iyi
  4. Holdout: getiri > 0, profit factor >= 1.1 ve işlem >= 20
"""
from __future__ import annotations

import time
from collections import defaultdict
from dataclasses import replace
from pathlib import Path

import numpy as np

from app.backtest import metrics as M
from app.backtest import report as R
from app.backtest.control import random_control, summarize
from app.backtest.engine import BacktestCfg, Backtester, SignalCache, SoloVariant
from app.backtest.research import _iso, save_trades
from app.backtest.walkforward import split_points
from app.config import Settings
from app.strategies.fib_pullback import FibPullback

NAME = "fib_pullback"


def _year_rows(trades) -> list[str]:
    by = defaultdict(list)
    for t in trades:
        by[time.strftime("%Y", time.gmtime(t.entry_ts / 1000))].append(t.pnl_pct)
    return [f"  {y}: {len(v):>4} işlem | işlem başı ort. {np.mean(v):+.2f}% | kazanma {np.mean([x > 0 for x in v]) * 100:.0f}%"
            for y, v in sorted(by.items())]


def run_sr_research(data: dict, settings: Settings, cfg: BacktestCfg, infos=None, report_dir: Path | None = None,
                    progress=print, holdout_frac: float = 0.2, n_runs: int = 20) -> str:
    L: list[str] = []
    add = L.append
    cache = SignalCache(data, settings, extra=[FibPullback(settings.strategy)])
    t0, hold_start, t1 = split_points(cache, cfg, holdout_frac)
    cfg = replace(cfg, latch_resets_daily=True)   # kenar ölçümü (araştırma #1-#3 ile aynı kural)
    var = lambda: SoloVariant(NAME, 0.0)          # noqa: E731
    mk = lambda c: Backtester(cache, var(), settings, c, infos)  # noqa: E731
    add("=" * 78)
    add("ARAŞTIRMA #7 — FIBONACCI GERİ ÇEKİLME + DESTEK + MACD/RSI (long-only)")
    add("=" * 78)
    add(f"Semboller ({len(data)}): {', '.join(sorted(data))}")
    add(f"Aralık: {cfg.interval} | holdout öncesi: {_iso(t0)} → {_iso(hold_start)} | holdout: {_iso(hold_start)} → {_iso(t1)}")
    add(f"Maliyet: komisyon %{cfg.fee_rate * 100:.2f}, kayma {cfg.slippage_bps} bps, yarım-spread {cfg.half_spread_bps} bps; "
        "sinyal barı kapanışı → sonraki bar açılışı. Parametreler önceden sabit, ayar YOK.")
    progress("[1/4] holdout öncesi dönem...")
    pre = mk(cfg).run(t0, hold_start)
    add("\n" + "-" * 78 + "\n1) HOLDOUT ÖNCESİ DÖNEM (parametre seçilmedi: tamamı dürüst örnek)\n" + "-" * 78)
    add(R.metrics_block(pre.metrics, "Fib Pullback"))
    add(f"  Sinyal {pre.n_signals} → giriş {pre.n_entries}; risk motoru reddi: {dict(pre.rejections) or 'yok'}")
    add("  Yıllara göre dağılım (tek yıla bağlı mı?):")
    for r in _year_rows(pre.trades):
        add(r)
    progress("[2/4] maliyetsiz (brüt) çalıştırma...")
    free = mk(replace(cfg, fee_rate=0.0, slippage_bps=0.0, half_spread_bps=0.0)).run(t0, hold_start)
    add(f"\n  Brüt (maliyetsiz) beklenti {R.num(free.metrics['expectancy_pct'])}%/işlem; maliyetli {R.num(pre.metrics['expectancy_pct'])}%/işlem")
    progress(f"[3/4] rastgele-giriş kontrolü ({n_runs} deneme)...")
    ctrl = random_control(cache, settings, cfg, t0, hold_start, max(pre.n_signals, 1), n_runs, infos, progress)
    sm = summarize(ctrl, pre.metrics["total_return"], pre.metrics["expectancy_pct"])
    add("\n" + "-" * 78 + "\n2) KONTROL — aynı kurallar, RASTGELE zamanlarda giriş\n" + "-" * 78)
    add(f"  Sinyal: işlem başına {pre.metrics['expectancy_pct']:.2f}% | rastgele: {sm['mean_exp_pct']:.2f}% | "
        f"fark {sm['exp_edge_pp']:+.2f} puan/işlem (rastgelelerin %{sm['pctile_exp'] * 100:.0f}'inden iyi)")
    progress("[4/4] holdout (tek bakış)...")
    hold = mk(cfg).run(hold_start, t1)
    bh = M.buy_and_hold(data, hold_start, t1)
    add("\n" + "-" * 78 + f"\n3) HOLDOUT ({_iso(hold_start)} → {_iso(t1)}) — hiç kullanılmadı, TEK kez bakıldı\n" + "-" * 78)
    add(R.metrics_block(hold.metrics, "Fib Pullback"))
    add(f"  Aynı dönemde sadece tutmak (eşit ağırlık): {R.pct(bh['equal_weight'])}")
    o, h = pre.metrics, hold.metrics
    pf, hpf = o.get("profit_factor", float("nan")), h.get("profit_factor", float("nan"))
    checks = [
        ("Holdout öncesi getiri > 0 ve PF >= 1.2", o["total_return"] > 0 and pf == pf and pf >= 1.2),
        ("Holdout öncesi işlem >= 100", o["n_trades"] >= 100),
        ("İşlem başı beklenti rastgeleden >= +0.30 puan iyi", sm["exp_edge_pp"] >= 0.30),
        ("Holdout getiri > 0, PF >= 1.1, işlem >= 20", h["total_return"] > 0 and hpf == hpf and hpf >= 1.1 and h["n_trades"] >= 20),
    ]
    add("\n" + "-" * 78 + "\nKARAR KRİTERLERİ (sonuçlar görülmeden ÖNCE sabitlendi)\n" + "-" * 78)
    for c, ok in checks:
        add(f"  [{'GEÇTİ' if ok else 'KALDI'}] {c}")
    add("  >>> SONUÇ: " + ("TÜM kriterler geçti (yine de ileri-test gerekir; canlı için yeterli DEĞİL)."
                           if all(ok for _, ok in checks) else "KALDI — bu strateji için canlıya geçmek için dayanak YOK."))
    add("\nSINIRLAMALAR: hayatta kalma yanlılığı (bugünün büyük coinleri), order book yok, bar-içi kötümser sıra, "
        "az işlemde şans etkisi. Bu rapor KÂR GARANTİSİ DEĞİLDİR.")
    text = "\n".join(L)
    if report_dir:
        report_dir.mkdir(parents=True, exist_ok=True)
        save_trades(report_dir / "sr_pre_trades.csv", pre.trades)
        save_trades(report_dir / "sr_holdout_trades.csv", hold.trades)
        (report_dir / f"sr_research_{time.strftime('%Y%m%d_%H%M%S')}.txt").write_text(text, encoding="utf-8")
    return text
