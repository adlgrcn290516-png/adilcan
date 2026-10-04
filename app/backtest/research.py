"""Araştırma raporu: baseline backtest + buy&hold kıyası + ablation + walk-forward + holdout + dürüst sınırlamalar."""
from __future__ import annotations

import csv
import time
from dataclasses import dataclass
from pathlib import Path

import numpy as np

from app.backtest import metrics as M
from app.backtest import report as R
from app.backtest.ablation import run_ablation
from app.backtest.engine import BacktestCfg, Backtester, SignalCache, make_variant
from app.backtest.walkforward import default_grid, split_points, walk_forward
from app.config import Settings


@dataclass
class ResearchOpts:
    train_days: int = 90
    test_days: int = 30
    holdout_frac: float = 0.2
    min_trades: int = 10
    objective: str = "sharpe"
    skip_ablation: bool = False
    skip_wf: bool = False
    quick: bool = False
    synthetic: bool = False


def _iso(ms: int) -> str:
    return time.strftime("%Y-%m-%d", time.gmtime(ms / 1000))


def save_trades(path: Path, trades: list[M.Trade]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", newline="") as f:
        w = csv.writer(f)
        w.writerow(["symbol", "entry_ts", "exit_ts", "entry_price", "exit_price", "qty", "pnl", "pnl_pct", "r", "reasons",
                    "bars_held", "fees"])
        for t in trades:
            w.writerow([t.symbol, t.entry_ts, t.exit_ts, t.entry_price, t.exit_price, t.qty, round(t.pnl, 6),
                        round(t.pnl_pct, 4), round(t.r_multiple, 3), "|".join(t.reasons), t.bars_held, round(t.fees, 6)])


def run_research(data: dict, settings: Settings, cfg: BacktestCfg, opts: ResearchOpts, infos=None,
                 report_dir: Path | None = None, progress=print) -> str:
    L: list[str] = []
    add = L.append
    cache = SignalCache(data, settings)
    t0, hold_start, t1 = split_points(cache, cfg, opts.holdout_frac)
    nb = {s: len(df) for s, df in data.items()}
    if opts.synthetic:
        add("!!! SENTETİK (rastgele üretilmiş) VERİ: bu raporun sayıları GERÇEK PİYASAYI TEMSİL ETMEZ !!!\n")
    add("=" * 78)
    add("ARAŞTIRMA RAPORU — BINANCE AI TRADING SYSTEM")
    add("=" * 78)
    add(f"Semboller ({len(data)}): {', '.join(sorted(data))}")
    add(f"Aralık: {cfg.interval} | işlem penceresi: {_iso(t0)} → {_iso(t1)} (holdout: {_iso(hold_start)} → {_iso(t1)}) | bar/sembol ort. {int(np.mean(list(nb.values())))}")
    add(f"Maliyet varsayımı: komisyon %{cfg.fee_rate * 100:.2f}/işlem, kayma {cfg.slippage_bps} bps, yarım-spread {cfg.half_spread_bps} bps, "
        f"gecikme: sinyal barı kapanışı → sonraki bar açılışı")
    add(f"Başlangıç sermayesi: {cfg.initial_capital:,.0f} | risk/işlem %{settings.risk.max_risk_per_trade_pct * 100:.1f} | "
        f"maks pozisyon {settings.risk.max_open_positions}")

    # 1) Baseline: tüm dönem, varsayılan parametre
    progress("[1/3] baseline backtest...")
    bt = Backtester(cache, make_variant(settings, cache), settings, cfg, infos)
    base = bt.run(t0, t1)
    bh = M.buy_and_hold(data, t0, t1)
    add("\n" + "-" * 78 + "\n1) BASELINE — tüm dönem, varsayılan parametreler (parametreler geçmişe UYDURULMADI)\n" + "-" * 78)
    add(R.metrics_block(base.metrics, "AI Composite"))
    add(f"  Sinyal {base.n_signals} → giriş {base.n_entries}; risk motoru reddi: {dict(base.rejections) or 'yok'}")
    for e in base.events:
        add("  OLAY: " + e)
    add(f"\nKIYAS (aynı dönemde SADECE TUTMAK): eşit ağırlıklı sepet {R.pct(bh['equal_weight'])}"
        + (f" | BTC {R.pct(bh['btc'])}" if bh.get("btc") is not None else ""))
    add("  → Strateji, 'hiçbir şey yapmadan tutmak'tan iyi mi? Risk-ayarlı bakış için Sharpe ve MaxDD'ye bak; "
        "tutmanın MaxDD'si genelde çok yüksektir.")
    if report_dir:
        save_trades(report_dir / "baseline_trades.csv", base.trades)

    # 2) Ablation (yalnız holdout öncesi)
    if not opts.skip_ablation:
        progress("[2/3] katkı (ablation) analizi...")
        rows = run_ablation(cache, settings, cfg, t0, hold_start, infos)
        add("\n" + "-" * 78 + "\n2) KATKI ANALİZİ (ablation) — holdout ÖNCESİ dönem, in-sample/AÇIKLAYICI\n" + "-" * 78)
        add(R.ablation_table(rows))
        add("Okuma: ΔGetiri > 0 olan '- X çıkarıldı' satırı, X'in bu veride ZARARA katkı yaptığını düşündürür; "
            "ΔGetiri < 0 ise X yardımcı olmuştur. Bu analiz AYNI veride yapıldığı için parametre seçiminde TEK BAŞINA kullanılmamalı "
            "(seçim yapılırsa yeni bir holdout gerekir).")

    # 3) Walk-forward + holdout
    wf = None
    if not opts.skip_wf:
        progress("[3/3] walk-forward + holdout (birkaç dakika sürebilir)...")
        grid = default_grid()[:6] if opts.quick else default_grid()
        wf = walk_forward(cache, settings, cfg, grid=grid, train_days=opts.train_days, test_days=opts.test_days,
                          holdout_frac=opts.holdout_frac, min_trades=opts.min_trades, objective=opts.objective, infos=infos,
                          progress=lambda m: progress("   " + m))
        add("\n" + "-" * 78 + f"\n3) WALK-FORWARD (train {opts.train_days}g → test {opts.test_days}g, ızgara {len(grid)} kombinasyon)\n" + "-" * 78)
        if not wf.folds:
            add("Yeterli veri yok: daha fazla gün gerekli.")
        else:
            add(R.wf_table(wf, opts.objective))
            add(R.metrics_block(wf.oos_metrics, "\nOUT-OF-SAMPLE (test pencereleri zincirlenmiş)"))
            oos_bh = float(np.prod([1 + M.buy_and_hold(data, f.test[0], f.test[1])["equal_weight"] for f in wf.folds
                                    if not np.isnan(M.buy_and_hold(data, f.test[0], f.test[1])["equal_weight"])]) - 1)
            add(f"  Aynı OOS pencerelerinde sadece tutmak (eşit ağırlık): {R.pct(oos_bh)}")
            add(f"\nHOLDOUT ({_iso(wf.holdout_window[0])} → {_iso(wf.holdout_window[1])}) — seçimde HİÇ kullanılmadı, TEK kez bakıldı")
            add(R.metrics_block(wf.holdout_chosen, "  walk-forward'ın en sık seçtiği parametre"))
            add(R.metrics_block(wf.holdout_default, "  varsayılan parametre"))
            hb = M.buy_and_hold(data, *wf.holdout_window)
            add(f"  Holdout'ta sadece tutmak: {R.pct(hb['equal_weight'])}" + (f" | BTC {R.pct(hb['btc'])}" if hb.get("btc") is not None else ""))
            add("\nHÜKÜMLER:")
            for v in wf.verdict:
                add("  * " + v)
            if wf.holdout_chosen["total_return"] <= 0:
                add("  * BULGU: Holdout getirisi ≤ 0 → canlıya geçmek için dayanak YOK.")
            if report_dir:
                save_trades(report_dir / "oos_trades.csv", wf.oos_trades)

    add("\n" + "-" * 78 + "\nSINIRLAMALAR (sonuçları okurken MUTLAKA hesaba kat)\n" + "-" * 78)
    for x in [
        "Sembol listesi BUGÜNKÜ hacme göre seçilir → hayatta kalma (survivorship) yanlılığı: bugün büyük olanlar geçmişte kazananlardı.",
        "Tek bir piyasa rejimi/dönemi: boğa/ayı/yatay farklı davranır; birkaç ay veri güvenilir sonuç vermez.",
        "Order book geçmişi yok: spread/derinlik/likidite risk kontrolleri backtest'te UYGULANAMIYOR (sonuçlar buna göre İYİMSER).",
        "Piyasa etkisi (emir büyüklüğünün fiyatı oynatması) yok: küçük sermayede makul, büyük sermayede iyimser.",
        "Bar içi sıra bilinmez: KÖTÜMSER varsayım (stop önce). Gerçek sonuç bundan iyi ya da kötü olabilir.",
        "Izgara küçük tutuldu ama yine de çoklu deneme etkisi vardır; asıl kanıt OOS + holdout'tur.",
        "İstatistiksel anlamlılık için yüzlerce işlem gerekir; az işlemli sonuçlar şans olabilir.",
        "Bu rapor KÂR GARANTİSİ DEĞİLDİR. Geçmiş performans gelecekteki sonuçları garanti etmez.",
    ]:
        add("  - " + x)
    text = "\n".join(L)
    if report_dir:
        report_dir.mkdir(parents=True, exist_ok=True)
        (report_dir / f"research_{time.strftime('%Y%m%d_%H%M%S')}.txt").write_text(text, encoding="utf-8")
    return text
