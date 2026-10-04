"""Araştırma raporu: baseline backtest + buy&hold kıyası + ablation + walk-forward + holdout + dürüst sınırlamalar."""
from __future__ import annotations

import csv
import time
from dataclasses import dataclass, replace
from pathlib import Path

import numpy as np

from app.backtest import metrics as M
from app.backtest import report as R
from app.backtest.ablation import run_ablation
from app.backtest.control import random_control, summarize
from app.backtest.engine import BacktestCfg, Backtester, SignalCache, make_variant
from app.backtest.walkforward import default_grid, quick_grid, split_points, walk_forward
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
    skip_control: bool = False
    quick: bool = False
    synthetic: bool = False


def _iso(ms: int) -> str:
    return time.strftime("%Y-%m-%d", time.gmtime(ms / 1000))


def save_trades(path: Path, trades: list[M.Trade]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", newline="", encoding="utf-8") as f:
        w = csv.writer(f)
        w.writerow(["symbol", "entry_ts", "exit_ts", "entry_price", "exit_price", "qty", "pnl", "pnl_pct", "r", "reasons",
                    "bars_held", "fees"])
        for t in trades:
            w.writerow([t.symbol, t.entry_ts, t.exit_ts, t.entry_price, t.exit_price, t.qty, round(t.pnl, 6),
                        round(t.pnl_pct, 4), round(t.r_multiple, 3), "|".join(t.reasons), t.bars_held, round(t.fees, 6)])


CRITERIA = ("OOS toplam getiri > 0 ve OOS profit factor >= 1.2", "OOS işlem sayısı >= 100",
            "OOS işlem başı beklenti, rastgele giriş kontrolünden >= +0.30 puan iyi",
            "Holdout (seçilen parametre) getirisi > 0 ve profit factor >= 1.1")


def decision_block(wf, ctrl_sm) -> str:
    """Bir strateji ailesi ancak TÜM kriterleri geçerse 'GEÇTİ' sayılır. Eşikler yeni sonuç görülmeden önce sabitlendi."""
    if wf is None or not wf.folds:
        return "Walk-forward çalışmadı: karar verilemez."
    o, h = wf.oos_metrics, wf.holdout_chosen or {}
    pf = o.get("profit_factor", float("nan"))
    hpf = h.get("profit_factor", float("nan"))
    checks = [
        (CRITERIA[0], o.get("total_return", -1) > 0 and pf == pf and pf >= 1.2),
        (CRITERIA[1], o.get("n_trades", 0) >= 100),
        (CRITERIA[2], ctrl_sm is not None and (o.get("expectancy_pct", -9) - ctrl_sm["mean_exp_pct"]) >= 0.30),
        (CRITERIA[3], h.get("total_return", -1) > 0 and hpf == hpf and hpf >= 1.1),
    ]
    L = [f"  [{'GEÇTİ' if ok else 'KALDI'}] {c}" for c, ok in checks]
    L.append("  >>> SONUÇ: " + ("TÜM kriterler geçti (yine de ileri-test/paper gerekir; canlı için yeterli DEĞİL)."
                               if all(ok for _, ok in checks) else "KALDI — bu strateji ailesi için canlıya geçmek için dayanak YOK."))
    return "\n".join(L)


def run_research(data: dict, settings: Settings, cfg: BacktestCfg, opts: ResearchOpts, infos=None,
                 report_dir: Path | None = None, progress=print) -> str:
    L: list[str] = []
    add = L.append
    cache = SignalCache(data, settings)
    t0, hold_start, t1 = split_points(cache, cfg, opts.holdout_frac)
    sys_cfg = cfg                                   # gerçek sistem: drawdown kilidi AÇILMAZ
    cfg = replace(cfg, latch_resets_daily=True)     # KENAR ölçümü: kilit her gün açılır (yoksa dönem yarıda kesilir)
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

    # 1) Baseline
    progress("[1/4] baseline backtest (ilk hesaplama en uzun sürer)...")
    bt = Backtester(cache, make_variant(settings, cache), settings, cfg, infos)
    base = bt.run(t0, t1)
    bh = M.buy_and_hold(data, t0, t1)
    add("\n" + "-" * 78 + "\n1) BASELINE — tüm dönem, varsayılan parametreler (parametreler geçmişe UYDURULMADI)\n" + "-" * 78)
    add(R.metrics_block(base.metrics, "AI Composite — KENAR ÖLÇÜM modu (drawdown kilidi günlük açılır; tüm dönem işlem görür)"))
    add(f"  Sinyal {base.n_signals} → giriş {base.n_entries}; risk motoru reddi: {dict(base.rejections) or 'yok'}")
    sysr = Backtester(cache, make_variant(settings, cache), settings, sys_cfg, infos).run(t0, t1)
    add(R.metrics_block(sysr.metrics, "\nAynı sistem, GERÇEK davranışla (drawdown kilidi bir kez devreye girince AÇILMAZ)"))
    for e in sysr.events:
        ts = int(e.split(":")[0])
        add(f"  OLAY: kilit {_iso(ts)} tarihinde devreye girdi → dönemin %{(t1 - ts) / (t1 - t0) * 100:.0f}'inde yeni giriş YAPILAMADI")
    free = Backtester(cache, make_variant(settings, cache), settings, replace(cfg, fee_rate=0.0, slippage_bps=0.0, half_spread_bps=0.0), infos).run(t0, t1)
    add(R.metrics_block(free.metrics, "\nMALİYETSİZ (brüt) — komisyon/kayma/spread SIFIR: sinyalin ham öngörü gücü"))
    add(f"  → Brüt beklenti {R.num(free.metrics['expectancy_pct'])}%/işlem; maliyetli beklenti {R.num(base.metrics['expectancy_pct'])}%/işlem. "
        "Brüt de negatifse sorun maliyet değil, sinyalin kendisidir.")
    add(f"\nKIYAS (aynı dönemde SADECE TUTMAK): eşit ağırlıklı sepet {R.pct(bh['equal_weight'])}"
        + (f" | BTC {R.pct(bh['btc'])}" if bh.get("btc") is not None else ""))
    add("  → Sepet BUGÜNÜN büyük coinlerinden oluşur (hayatta kalma yanlılığı): 'tutmak' sonucu İYİMSER bir kıyastır.")
    if report_dir:
        save_trades(report_dir / "baseline_trades.csv", base.trades)

    ctrl_sm = None
    # 1b) Rastgele giriş kontrolü
    if not opts.skip_control:
        n_runs = 8 if opts.quick else 20
        progress(f"[2/4] rastgele-giriş kontrolü ({n_runs} deneme)...")
        ctrl = random_control(cache, settings, cfg, t0, t1, base.n_signals, n_runs, infos, lambda m: progress(m))
        sm = summarize(ctrl, base.metrics["total_return"], base.metrics["expectancy_pct"])
        add("\n" + "-" * 78 + "\n1b) KONTROL DENEYİ — aynı risk/pozisyon kuralları + maliyetler, ama RASTGELE zamanlarda giriş\n" + "-" * 78)
        add(f"  {n_runs} rastgele deneme (ort. {sm['mean_trades']:.0f} işlem): getiri ort. {R.pct(sm['mean'])}, medyan {R.pct(sm['median'])}, "
            f"aralık [{R.pct(sm['min'])} … {R.pct(sm['max'])}], işlem başına ort. {sm['mean_exp_pct']:.2f}%")
        add(f"  Sinyal motoru: işlem başına {base.metrics['expectancy_pct']:.2f}% | rastgele: {sm['mean_exp_pct']:.2f}% | "
            f"fark {sm['exp_edge_pp']:+.2f} puan/işlem (rastgele denemelerin %{sm['pctile_exp'] * 100:.0f}'inden iyi)")
        add(f"  Getiri kıyası: sinyal {R.pct(base.metrics['total_return'])} vs rastgele ort. {R.pct(sm['mean'])}"
            + ("  [İFLAS bölgesi: bileşik getiri artık bilgi taşımaz, işlem başına beklentiye bak]" if base.metrics["total_return"] < -0.9 else ""))
        if sm["exp_edge_pp"] < 0.10:
            add("  BULGU: Sinyal, rastgele girişlerden işlem başına ANLAMLI (≥ +0.10 puan) şekilde iyi DEĞİL → sinyal motorunun ek değeri YOK.")
        else:
            add("  Sinyal, rastgele girişlerden işlem başına ≥ +0.10 puan iyi (yine de OOS/holdout ile teyit gerekir).")
        ctrl_sm = sm

    # 2) Ablation (yalnız holdout öncesi)
    if not opts.skip_ablation:
        progress("[3/4] katkı (ablation) analizi...")
        rows = run_ablation(cache, settings, cfg, t0, hold_start, infos, progress)
        add("\n" + "-" * 78 + "\n2) KATKI ANALİZİ (ablation) — holdout ÖNCESİ dönem, in-sample/AÇIKLAYICI\n" + "-" * 78)
        add(R.ablation_table(rows))
        add("Okuma: ΔGetiri > 0 olan '- X çıkarıldı' satırı, X'in bu veride ZARARA katkı yaptığını düşündürür; "
            "ΔGetiri < 0 ise X yardımcı olmuştur. Bu analiz AYNI veride yapıldığı için parametre seçiminde TEK BAŞINA kullanılmamalı "
            "(seçim yapılırsa yeni bir holdout gerekir).")

    # 3) Walk-forward + holdout
    wf = None
    if not opts.skip_wf:
        progress("[4/4] walk-forward + holdout (birkaç dakika sürebilir)...")
        grid = quick_grid() if opts.quick else default_grid()
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
            elif wf.holdout_chosen["total_return"] < hb["equal_weight"]:
                add(f"  * UYARI: Holdout pozitif ({R.pct(wf.holdout_chosen['total_return'])}) ama sadece-tutmanın ({R.pct(hb['equal_weight'])}) ALTINDA. "
                    "Yükselen piyasada long-only strateji pozitif görünür; bu KENAR kanıtı DEĞİL. Holdout tek bir kısa rejimdir "
                    "ve seçilen parametre yüksek maruziyetli (gevşek eşik).")
            add(f"  * Holdout süresi yalnızca {(wf.holdout_window[1] - wf.holdout_window[0]) / 86_400_000:.0f} gün: tek rejim, tek örnek.")
            if report_dir:
                save_trades(report_dir / "oos_trades.csv", wf.oos_trades)

    add("\n" + "-" * 78 + "\nKARAR KRİTERLERİ (sonuçlar görülmeden ÖNCE sabitlendi)\n" + "-" * 78)
    add(decision_block(wf, ctrl_sm))
    add("\n" + "-" * 78 + "\nSINIRLAMALAR (sonuçları okurken MUTLAKA hesaba kat)\n" + "-" * 78)
    for x in [
        "Sembol listesi BUGÜNKÜ hacme göre seçilir → hayatta kalma (survivorship) yanlılığı: bugün büyük olanlar geçmişte kazananlardı.",
        "Tek bir piyasa rejimi/dönemi: boğa/ayı/yatay farklı davranır; birkaç ay veri güvenilir sonuç vermez.",
        "Order book geçmişi yok: spread/derinlik/likidite risk kontrolleri backtest'te UYGULANAMIYOR (sonuçlar buna göre İYİMSER).",
        "Piyasa etkisi (emir büyüklüğünün fiyatı oynatması) yok: küçük sermayede makul, büyük sermayede iyimser.",
        "Bar içi sıra bilinmez: KÖTÜMSER varsayım (stop önce). Kenarsız sentetik rastgele yürüyüşte, rastgele girişle ve SIFIR maliyetle bile ~-0.13%/işlem sapma ürettiği ÖLÇÜLDÜ → mutlak getiriler fazla kötümserdir; asıl adil karşılaştırma aynı motorda sinyal-vs-rastgele (bölüm 1b).",
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
