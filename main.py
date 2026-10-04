"""BINANCE AI TRADING SYSTEM — CLI.   Faz 1: check | capabilities   Faz 2: paper-demo   Faz 3: scan   Faz 4: paper-run   Faz 5: fetch-history, research, regime-test   Faz 9: run, status"""
from __future__ import annotations

import argparse
import sys
import time
from decimal import Decimal

from app.config import load_settings
from app.exchange import capabilities
from app.exchange.spot import BinanceSpotAdapter
from app.utils.logging import setup_logging


def cmd_capabilities(_a, _s) -> int:
    for p in capabilities.MATRIX.values():
        print(f"{p.product:13} market={p.market_data.value:10} account={p.account.value:10} "
              f"trading={p.trading.value:10} testenv={p.test_environment.value:10} ws={p.websocket.value:10} "
              f"-> {p.status}")
    return 0


def cmd_check(_a, s) -> int:
    ad = BinanceSpotAdapter(s)
    print(f"mode={s.mode.value} env={s.environment.value} url={ad.base_url} keys={'yes' if s.has_credentials else 'no'}")
    ad.ping()
    drift = ad.clock_drift_ms()
    print(f"[OK] ping | saat farkı={drift} ms", "(UYARI: recvWindow'a yakın!)" if abs(drift) > s.http.recv_window_ms / 2 else "")
    info = ad.exchange_info()
    trading = [x for x in info.values() if x.is_trading]
    print(f"[OK] exchange_info: {len(info)} sembol, {len(trading)} TRADING")
    quote = s.universe.quote_asset
    tick = [t for t in ad.tickers_24h() if t.symbol in info and info[t.symbol].quote == quote
            and info[t.symbol].is_trading and t.quote_volume >= s.universe.min_quote_volume_24h]
    tick.sort(key=lambda t: t.quote_volume, reverse=True)
    print(f"[OK] evren ({quote}, hacim>={s.universe.min_quote_volume_24h:,.0f}): {len(tick)} sembol")
    for t in tick[:5]:
        print(f"     {t.symbol:12} {t.last_price:>14} {t.price_change_pct:>7}%  vol={t.quote_volume:,.0f}")
    if tick:
        sym = tick[0].symbol
        ks = ad.klines(sym, "1h", 5)
        ob = ad.order_book(sym, 20)
        print(f"[OK] {sym}: {len(ks)} kline, spread={ob.spread_pct:.4f}%, imbalance={ob.imbalance():.3f}")
    if s.has_credentials:
        acc = ad.account()
        print(f"[OK] hesap: canTrade={acc.can_trade} canWithdraw={acc.can_withdraw} varlık={len(acc.balances)}")
        if acc.can_withdraw:
            print("[UYARI] Hesapta çekim yetkisi açık görünüyor; API key'de withdrawal iznini KAPAT, IP kısıtı AÇ.")
        for b in sorted(acc.balances, key=lambda b: b.total, reverse=True)[:5]:
            print(f"     {b.asset:8} free={b.free} locked={b.locked}")
        print(f"[OK] açık emir: {len(ad.open_orders())}")
    else:
        print("[ATLANDI] hesap verisi: API key yok (.env)")
    return 0


def cmd_paper_demo(a, s) -> int:
    """Gerçek piyasa verisi + SANAL para. Hiçbir emir Binance'e gitmez."""
    from app.data.db import SqliteDb, SqliteOrderRepository
    from app.execution.engine import ExecutionEngine
    from app.execution.filters import floor_to_step
    from app.execution.types import OrderRejected, OrderRequest, OrderType, Side
    from app.paper.broker import PaperBroker

    ad = BinanceSpotAdapter(s)
    info = ad.exchange_info()
    sym = a.symbol.upper()
    if sym not in info:
        print(f"[HATA] {sym} bulunamadı")
        return 2
    si = info[sym]
    broker = PaperBroker({si.quote: Decimal(str(a.capital))}, info, lambda x: ad.order_book(x, 20))
    repo = SqliteOrderRepository(SqliteDb(a.db))
    eng = ExecutionEngine(broker, info, repo, s)
    print(f"PAPER (sanal para) | {sym} | başlangıç: {a.capital} {si.quote} | komisyon %0.1 + slippage 2bps\n")

    ask = ad.order_book(sym, 5).asks[0][0]
    qty = Decimal(str(a.budget)) / ask
    iid = str(int(time.time()))
    buy = OrderRequest(sym, Side.BUY, OrderType.MARKET, qty, None, ask, "paper-demo", iid)
    b = eng.place(buy)
    print(f"1) BUY  {b.symbol}: durum={b.status.value} doğrulandı={b.verified} miktar={b.executed_qty} "
          f"ort.fiyat={b.avg_price:.4f} komisyon={b.fee_amount:f} {b.fee_asset}")

    dup = eng.place(buy)
    print(f"2) AYNI EMİR TEKRAR: yeni emir gönderilmedi -> mevcut durum={dup.status.value} (duplicate koruması)")

    try:
        eng.place(OrderRequest(sym, Side.BUY, OrderType.MARKET, Decimal("0.0000001"), None, ask, "paper-demo", iid + "x"))
    except OrderRejected as e:
        print(f"3) Çok küçük emir YEREL olarak reddedildi (borsaya gitmedi): {e}")

    held = floor_to_step(broker.free_balance(si.base), si.step_size)
    bid = ad.order_book(sym, 5).bids[0][0]
    sell = eng.place(OrderRequest(sym, Side.SELL, OrderType.MARKET, held, None, bid, "paper-demo", iid + "s"))
    print(f"4) SELL {sell.symbol}: durum={sell.status.value} doğrulandı={sell.verified} miktar={sell.executed_qty} "
          f"ort.fiyat={sell.avg_price:.4f}")
    end = broker.free_balance(si.quote)
    pnl = end - Decimal(str(a.capital))
    print(f"\nSonuç: {end:.4f} {si.quote} | alıp-hemen-satma maliyeti (spread+slippage+komisyon): {pnl:.4f} {si.quote}")
    print(f"Kayıt: {a.db} ({len(repo.all())} emir). Not: gerçek para/emir YOK.")
    return 0


def cmd_scan(a, s) -> int:
    """Piyasayı tarar, her sembole 0-100 fırsat skoru verir. SALT OKUNUR: emir yok."""
    from app.core.scanner import MarketScanner
    from app.data.db import SqliteDb, SqliteSignalRepository
    from app.strategies.base import Signal

    if a.interval:
        s.scanner.interval = a.interval
    if a.max_symbols:
        s.universe.max_symbols = a.max_symbols
    ad = BinanceSpotAdapter(s)
    sc = MarketScanner(ad, s)
    print(f"Tarama: interval={s.scanner.interval} | en fazla {s.universe.max_symbols} sembol | "
          f"min 24s hacim {s.universe.min_quote_volume_24h:,.0f} {s.universe.quote_asset}")
    opps = sc.scan(progress=lambda m: print("  " + m, end="\r", flush=True))
    print(" " * 40)
    repo = SqliteSignalRepository(SqliteDb(a.db))
    for o in opps:
        repo.record(o)
    top = opps[: a.top or s.scanner.top_n_report]
    print(f"{'Symbol':<12}{'Score':>6} {'Signal':<6}{'Conf':>5} {'Entry':>12}{'Stop':>12}{'TP1':>12}{'Risk':>5}  Reason")
    for o in top:
        f = lambda x: f"{x:.6g}" if x else "-"  # noqa: E731
        print(f"{o.symbol:<12}{o.score:>6.0f} {o.signal.value:<6}{o.confidence:>5.2f} {f(o.entry):>12}{f(o.stop):>12}"
              f"{f(o.tp1):>12}{o.risk_score:>5.0f}  {o.reason[:60]}")
    buys = [o for o in opps if o.signal is Signal.BUY]
    print(f"\n{len(opps)} sembol tarandı, {len(buys)} BUY sinyali. Kayıt: {a.db}")
    show = buys[: a.details] or opps[: a.details]
    print(f"\n--- İlk {len(show)} sembolün AÇIKLAMASI ---")
    for o in show:
        print("\n" + o.explain())
    print("\nUYARI: Skor ağırlıkları/eşikler ÖNSEL varsayımdır; backtest (Faz 5) ile doğrulanmadı. "
          "Bu çıktı yatırım tavsiyesi değildir; hiçbir emir gönderilmedi.")
    return 0


def cmd_paper_run(a, s) -> int:
    """Tam zincir, SANAL parayla: tara -> risk -> portföy -> (paper) emir -> doğrula -> yönet. Gerçek emir YOK."""
    import os
    import time as _t
    from app.core.orchestrator import Orchestrator
    from app.core.scanner import MarketScanner, Opportunity
    from app.data.db import SqliteDb, SqliteOrderRepository, SqlitePortfolioRepository
    from app.execution.engine import ExecutionEngine
    from app.paper.broker import PaperBroker
    from app.portfolio.manager import PortfolioManager
    from app.risk.engine import ApiHealth, RiskEngine
    from app.strategies.base import Signal

    if not a.resume and os.path.exists(a.db):
        os.remove(a.db)  # paper bakiyesi bellekte tutulur; temiz başla
    ad = BinanceSpotAdapter(s)
    info = ad.exchange_info()
    broker = PaperBroker({s.universe.quote_asset: Decimal(str(a.capital))}, info, lambda x: ad.order_book(x, 20))
    db = SqliteDb(a.db)
    health = ApiHealth()
    risk = RiskEngine(s.risk, health)
    eng = ExecutionEngine(broker, info, SqliteOrderRepository(db), s)
    repo = SqlitePortfolioRepository(db)
    mgr = PortfolioManager(broker, eng, risk, info, s, repo, s.universe.quote_asset)
    if a.emergency:
        mgr.set_emergency(True)
    if a.max_symbols:
        s.universe.max_symbols = a.max_symbols
    orch = Orchestrator(ad, MarketScanner(ad, s), mgr, health, dry_run=a.dry_run)
    print(f"PAPER-RUN | sanal sermaye {a.capital} {s.universe.quote_asset} | tur sayısı {a.cycles} | dry-run={a.dry_run} | "
          f"emergency_stop={risk.emergency_stop}\n")
    for n in range(1, a.cycles + 1):
        extra = []
        if a.force_buy and n == 1:
            sym = a.force_buy.upper()
            o = orch.scanner.evaluate_symbol(sym, None, ad.server_time_ms())
            if o is None:
                print(f"[HATA] {sym} için veri yetersiz")
                return 2
            f = o.f_obj
            stop = f.price - s.strategy.atr_stop_mult * f.atr
            risk_d = f.price - stop
            o.signal, o.confidence, o.entry, o.stop = Signal.BUY, 0.5, f.price, stop
            o.tp1, o.tp2 = f.price + s.strategy.rr1 * risk_d, f.price + s.strategy.rr2 * risk_d
            o.reason = "MANUEL DEMO SİNYALİ (strateji değil) — yalnızca risk/portföy zincirini göstermek için"
            extra = [o]
            print(f"[DEMO] {sym} için MANUEL BUY sinyali üretildi (stratejiden gelmedi).")
        print(f"--- TUR {n}/{a.cycles} ---")
        rep = orch.cycle(extra)
        if rep.error:
            print(f"  UYARI: {rep.error}")
        buys = [o for o in rep.opportunities if o.signal is Signal.BUY]
        print(f"  taranan: {len(rep.opportunities)} | BUY sinyali: {len(buys)} | açılan pozisyon: {len(rep.opened)}")
        for r in rep.opened:
            p = r.position
            print(f"  AÇILDI {p.symbol}: qty={p.qty} giriş={p.entry_price:.6g} stop={p.stop:.6g} TP1={p.tp1:.6g} "
                  f"TP2={p.tp2:.6g} | risk kararı: {r.verdict.decision.value} ({'; '.join(r.verdict.reasons) or 'tüm kontroller geçti'})")
        for sym, why in rep.skipped:
            print(f"  ATLANDI {sym}: {why}")
        for e in rep.exits:
            print(f"  ÇIKIŞ {e.symbol} {e.reason}: qty={e.qty} fiyat={e.price} pnl={e.pnl:.4f}")
        if n < a.cycles:
            _t.sleep(a.sleep)
    q = orch.quotes_for(set(mgr.positions))
    st = mgr.state(q)
    rs = risk.status(st)
    print(f"\nPORTFÖY: equity={st.equity:.4f} nakit={st.free_quote:.4f} maruziyet={st.exposure:.4f} "
          f"günlük={rs['daily_pnl_pct'] * 100:.2f}% drawdown={rs['drawdown_pct'] * 100:.2f}% açık={len(st.positions)}")
    for p in st.positions:
        print(f"  {p.symbol}: qty={p.qty} giriş={p.entry_price:.6g} stop={p.stop:.6g} TP1={p.tp1:.6g}")
    print(f"RİSK: {rs}")
    ev = repo.risk_events()
    print(f"Risk olayı kaydı: {len(ev)} (örnek: {[(e['symbol'], e['decision']) for e in ev[:5]]})")
    print(f"Kayıt: {a.db}. Not: gerçek para/emir YOK; stop'lar yazılım stop'u (borsada stop emri yok).")
    return 0


def _pick_symbols(ad, s, a):
    from app.core.scanner import filter_universe
    info = ad.exchange_info()
    if a.symbols:
        return [x.strip().upper() for x in a.symbols.split(",") if x.strip()], info
    u = s.universe.model_copy(update={"max_symbols": a.top})
    syms = [t.symbol for t in filter_universe(info, ad.tickers_24h(), u)]
    return syms, info


def cmd_fetch_history(a, s) -> int:
    from app.data.history import HistoryStore
    ad = BinanceSpotAdapter(s)
    syms, _ = _pick_symbols(ad, s, a)
    now = ad.server_time_ms()
    st = HistoryStore(ad, a.history_dir)
    for i, sym in enumerate(syms, 1):
        df = st.update(sym, a.interval, a.days, now)
        print(f"[{i}/{len(syms)}] {sym}: {len(df)} bar")
    print(f"Önbellek: {a.history_dir}")
    return 0


def cmd_research(a, s) -> int:
    import time as _t
    from pathlib import Path
    from app.backtest.engine import BacktestCfg
    from app.backtest.research import ResearchOpts, run_research
    s.scanner.interval = a.interval
    cfg = BacktestCfg(interval=a.interval, fee_rate=a.fee, slippage_bps=a.slippage, half_spread_bps=a.spread,
                      initial_capital=a.capital)
    opts = ResearchOpts(a.train_days, a.test_days, a.holdout, a.min_trades, a.objective, a.skip_ablation, a.skip_wf,
                        a.skip_control, a.quick, bool(a.synthetic))
    infos = None
    if a.synthetic:
        from app.backtest.synthetic import gbm_df
        data = {f"SYN{k}USDT": gbm_df(int(a.days * 24), seed=k) for k in range(a.synthetic)}
        print(f"SENTETİK veri: {len(data)} sembol (gerçek piyasa DEĞİL)")
    else:
        from app.data.history import HistoryStore
        ad = BinanceSpotAdapter(s)
        syms, info = _pick_symbols(ad, s, a)
        infos = info
        now = ad.server_time_ms()
        st = HistoryStore(ad, a.history_dir)
        data = {}
        for i, sym in enumerate(syms, 1):
            print(f"  veri [{i}/{len(syms)}] {sym}", end="\r", flush=True)
            df = st.update(sym, a.interval, a.days, now)
            if len(df) >= cfg.warmup_bars + 200:
                data[sym] = df
            else:
                print(f"\n  {sym}: yetersiz geçmiş ({len(df)} bar) -> atlandı")
        print(" " * 40)
    if len(data) < 2:
        print("[HATA] yeterli veri yok")
        return 2
    t_start = _t.time()
    text = run_research(data, s, cfg, opts, infos, Path(a.report_dir))
    print(text)
    print(f"\n[Toplam süre: {(_t.time() - t_start) / 60:.1f} dk] [RAPOR TAMAMLANDI]")
    print(f"\nRapor ve işlem listeleri: {a.report_dir}/")
    return 0


class _Tee:
    """Ekrana yazılanı aynı anda dosyaya da (anında flush) yazar: donsa/çökse bile dosyada iz kalır."""

    def __init__(self, stream, fh):
        self.stream, self.fh = stream, fh

    def write(self, text):
        self.stream.write(text)
        try:
            self.fh.write(text)
            self.fh.flush()
        except Exception:  # noqa: BLE001
            pass
        return len(text)

    def flush(self):
        self.stream.flush()

    def __getattr__(self, n):
        return getattr(self.stream, n)


def cmd_regime_test(a, s) -> int:
    import numpy as np
    from app.backtest import regime as RG
    from app.data.history import HistoryStore
    ad = BinanceSpotAdapter(s)
    now = ad.server_time_ms()
    st = HistoryStore(ad, a.history_dir)
    res = []
    for sym in ("BTCUSDT", "ETHUSDT"):
        df = st.update(sym, "1d", a.days, now)
        if len(df) < a.n + 400:
            print(f"[HATA] {sym}: yetersiz geçmiş ({len(df)} gün)")
            return 2
        print(f"{sym}: {len(df)} gün")
        res.append(RG.evaluate_asset(sym, df["close_time"].to_numpy(dtype=float), df["open"].to_numpy(dtype=float),
                                     df["close"].to_numpy(dtype=float), a.n, a.fee, a.slippage))
    print(RG.report(res, a.n, a.fee, a.slippage))
    return 0


def cmd_run(a, s) -> int:
    """7/24 koşucu. VARSAYILAN: gerçek canlı veri + SANAL para (paper). Gerçek para kilitli."""
    import logging
    import shutil
    from pathlib import Path
    from app.core.orchestrator import Orchestrator
    from app.core.runner import Runner
    from app.core.scanner import MarketScanner
    from app.data.db import SqliteDb, SqliteOrderRepository, SqlitePortfolioRepository
    from app.execution.engine import ExecutionEngine
    from app.paper import state as pstate
    from app.paper.broker import PaperBroker
    from app.portfolio.manager import PortfolioManager
    from app.risk.engine import ApiHealth, RiskEngine
    from app.utils.logging import RedactFilter

    d = Path(a.data_dir)
    if a.fresh and d.exists():
        shutil.rmtree(d)
    d.mkdir(parents=True, exist_ok=True)
    fh = logging.FileHandler(d / "runner.log", encoding="utf-8")
    fh.setFormatter(logging.Formatter("%(asctime)s %(levelname)-7s %(name)s | %(message)s"))
    fh.addFilter(RedactFilter())
    logging.getLogger().addHandler(fh)
    if a.max_symbols:
        s.universe.max_symbols = a.max_symbols
    ad = BinanceSpotAdapter(s)
    info = ad.exchange_info()
    quote = s.universe.quote_asset
    state_path = d / "paper_state.json"
    if a.broker == "paper":
        broker = PaperBroker({quote: Decimal(str(a.capital))}, info, lambda x: ad.order_book(x, 20))
        resumed = pstate.load(broker, state_path)
        save = lambda: pstate.save(broker, state_path)  # noqa: E731
        mode = f"PAPER (sanal para {a.capital} {quote}; canlı veri)" + (" — kaldığı yerden devam" if resumed else "")
    else:
        from app.config import Environment
        from app.execution.binance_broker import SpotBinanceBroker
        if s.environment is Environment.PROD or not s.has_credentials:
            print("[HATA] --broker demo için BINANCE_ENVIRONMENT=demo|testnet ve .env'de demo API key gerekir (PROD kilitli).")
            return 2
        broker = SpotBinanceBroker(s, ad)
        save = lambda: None  # noqa: E731
        mode = f"BINANCE {s.environment.value.upper()} (demo hesap; gerçek para DEĞİL)"
    db = SqliteDb(d / "forward.db")
    health = ApiHealth()
    risk = RiskEngine(s.risk, health)
    eng = ExecutionEngine(broker, info, SqliteOrderRepository(db), s)
    repo = SqlitePortfolioRepository(db)
    mgr = PortfolioManager(broker, eng, risk, info, s, repo, quote)
    for issue in mgr.reconcile():
        print("[UYARI]", issue)
    meta = d / "forward_meta.json"
    if not meta.exists():
        import json
        import time as _t
        try:
            ob = ad.order_book("BTCUSDT", 5)
            btc = float((ob.bids[0][0] + ob.asks[0][0]) / 2)
        except Exception:  # noqa: BLE001
            btc = None
        meta.write_text(json.dumps({"start_ts": _t.time(), "capital": a.capital, "btc_start": btc, "mode": mode}))
    orch = Orchestrator(ad, MarketScanner(ad, s), mgr, health)
    runner = Runner(orch, mgr, d, save_state=save, manage_every_s=a.manage_every, max_failures=a.max_failures)
    if a.once:  # cron modu: tek tur yap ve çık; üst üste binen çalıştırmaları kilit dosyası engeller
        import time as _t
        lock = d / "run.lock"
        if lock.exists() and _t.time() - lock.stat().st_mtime < 600:
            print("Önceki tur hâlâ çalışıyor (kilit dosyası var); bu tur atlandı.")
            return 0
        lock.write_text(str(_t.time()))
        try:
            runner.run(max_ticks=1)
        finally:
            lock.unlink(missing_ok=True)
        return 0
    print("=" * 70)
    print(f" SÜREKLİ KOŞUCU | {mode}")
    print(f" Veri/kayıt klasörü: {d}   (log: runner.log)")
    print(" DURDURMAK: bu pencerede Ctrl+C  |  ACİL: '%s' dosyası oluştur (içine CLOSE yazarsan pozisyonlar da kapanır)" % (d / "STOP"))
    print(" UYARI: stratejiler backtest'te KAYBETTİ; bu çalışma ileri-test (kanıt toplama) amaçlıdır.")
    print("=" * 70)
    runner.run(max_ticks=a.max_ticks or None)
    return 0


def cmd_status(a, s) -> int:
    import json
    import time as _t
    from pathlib import Path
    from app.data.db import SqliteDb, SqlitePortfolioRepository
    d = Path(a.data_dir)
    if not (d / "forward.db").exists():
        print("Henüz çalışma kaydı yok (önce 'run').")
        return 2
    db = SqliteDb(d / "forward.db")
    with db.lock:
        snaps = db.conn.execute("SELECT ts, equity FROM portfolio_snapshots ORDER BY ts").fetchall()
        closed = db.conn.execute("SELECT extra FROM positions WHERE status='CLOSED'").fetchall()
        opened = db.conn.execute("SELECT symbol, entry_price, stop_loss FROM positions WHERE status='OPEN'").fetchall()
    meta = json.loads((d / "forward_meta.json").read_text()) if (d / "forward_meta.json").exists() else {}
    if not snaps:
        print("Henüz anlık görüntü yok.")
        return 2
    eq = [float(r["equity"]) for r in snaps]
    peak, mdd = eq[0], 0.0
    for e in eq:
        peak = max(peak, e)
        mdd = max(mdd, (peak - e) / peak)
    days = (snaps[-1]["ts"] - snaps[0]["ts"]) / 86400
    pnls = [float(json.loads(r["extra"] or "{}").get("realized_pnl", 0)) for r in closed]
    wins, losses = [x for x in pnls if x > 0], [x for x in pnls if x <= 0]
    pf = (sum(wins) / -sum(losses)) if losses and sum(losses) < 0 else float("inf") if wins else float("nan")
    ret = eq[-1] / eq[0] - 1
    print(f"İLERİ TEST DURUMU | {meta.get('mode', '')}")
    print(f"  Süre: {days:.1f} gün | equity {eq[0]:.2f} → {eq[-1]:.2f} | getiri {ret * 100:+.2f}% | maks. drawdown {mdd * 100:.2f}%")
    print(f"  Kapanan pozisyon: {len(pnls)} | kazanma oranı {len(wins) / len(pnls) * 100:.0f}% | profit factor {pf:.2f} | "
          f"ort. PnL {sum(pnls) / len(pnls):+.3f}" if pnls else "  Kapanan pozisyon: 0")
    print(f"  Açık pozisyon: {len(opened)} " + ", ".join(f"{r['symbol']}" for r in opened))
    try:
        ad = BinanceSpotAdapter(s)
        ob = ad.order_book("BTCUSDT", 5)
        now = float((ob.bids[0][0] + ob.asks[0][0]) / 2)
        if meta.get("btc_start"):
            print(f"  Aynı sürede sadece BTC tutmak: {(now / meta['btc_start'] - 1) * 100:+.2f}%")
    except Exception:  # noqa: BLE001
        pass
    n = len(pnls)
    print("\n  İLERİ-TEST KARARI (önceden sabit): >=100 kapanan pozisyon VE getiri>0 VE profit factor>=1.2 → 'devam'; aksi halde elenir.")
    if n < 100:
        print(f"  Henüz karar için erken: {n}/100 kapanan pozisyon (az örnek = şans olabilir).")
    else:
        ok = ret > 0 and pf >= 1.2
        print("  >>> " + ("KRİTERLER GEÇTİ (yine de gerçek para için ayrı, bilinçli karar gerekir)." if ok else "KALDI — bu sistem için gerçek paraya geçmek için dayanak YOK."))
    return 0


def main(argv=None) -> int:
    for st in (sys.stdout, sys.stderr):  # Windows Türkçe konsolda (cp1254) '→' gibi karakterler çökmesin
        try:
            st.reconfigure(encoding="utf-8", errors="replace")
        except Exception:  # noqa: BLE001
            pass
    ap = argparse.ArgumentParser(prog="main.py")
    ap.add_argument("--config", default="config.yaml")
    ap.add_argument("--log-level", default="INFO")
    ap.add_argument("--debug", action="store_true", help="hata olursa ayrıntılı iz (traceback) yaz")
    sub = ap.add_subparsers(dest="cmd", required=True)
    sub.add_parser("check", help="Bağlantı + market data + account data doğrulaması (salt okunur)")
    sub.add_parser("capabilities", help="Ürün yetenek matrisi")
    pd = sub.add_parser("paper-demo", help="Sanal parayla emir motoru demosu (gerçek emir YOK)")
    pd.add_argument("--symbol", default="BTCUSDT")
    pd.add_argument("--budget", type=float, default=50.0, help="alım bütçesi (quote)")
    pd.add_argument("--capital", type=float, default=1000.0, help="sanal başlangıç bakiyesi")
    pd.add_argument("--db", default="data/paper.db")
    sc = sub.add_parser("scan", help="Piyasayı tara, fırsat skorla (salt okunur)")
    sc.add_argument("--top", type=int, default=0)
    sc.add_argument("--details", type=int, default=3, help="kaç sembolün ayrıntılı açıklaması")
    sc.add_argument("--interval", default="")
    sc.add_argument("--max-symbols", type=int, default=0)
    sc.add_argument("--db", default="data/trading.db")
    pr = sub.add_parser("paper-run", help="Sanal parayla tam zincir (tara->risk->portföy->emir->yönet)")
    pr.add_argument("--cycles", type=int, default=1)
    pr.add_argument("--sleep", type=float, default=60.0, help="turlar arası saniye")
    pr.add_argument("--capital", type=float, default=1000.0)
    pr.add_argument("--max-symbols", type=int, default=25)
    pr.add_argument("--db", default="data/paper_run.db")
    pr.add_argument("--dry-run", action="store_true", help="emir gönderme, yalnızca risk kararlarını göster")
    pr.add_argument("--resume", action="store_true", help="DB'yi silme (paper bakiyesi bellekte olduğundan önerilmez)")
    pr.add_argument("--emergency", action="store_true", help="EMERGENCY_STOP açık başla (yeni emir yok)")
    pr.add_argument("--force-buy", default="", help="DEMO: bu sembole manuel BUY sinyali enjekte et")
    def hist_args(p):
        p.add_argument("--symbols", default="", help="virgülle ayrılmış (boşsa hacme göre en büyükler)")
        p.add_argument("--top", type=int, default=15)
        p.add_argument("--days", type=int, default=365)
        p.add_argument("--interval", default="1h")
        p.add_argument("--history-dir", default="data/history")
    fh = sub.add_parser("fetch-history", help="Geçmiş mum verisini indir/önbelleğe al (salt okunur)")
    hist_args(fh)
    rs = sub.add_parser("research", help="Backtest + ablation + walk-forward + holdout raporu")
    hist_args(rs)
    rs.add_argument("--train-days", type=int, default=90)
    rs.add_argument("--test-days", type=int, default=30)
    rs.add_argument("--holdout", type=float, default=0.2)
    rs.add_argument("--min-trades", type=int, default=10)
    rs.add_argument("--objective", default="sharpe", choices=["sharpe", "profit_factor", "return_over_dd"])
    rs.add_argument("--fee", type=float, default=0.001)
    rs.add_argument("--slippage", type=float, default=3.0, help="bps")
    rs.add_argument("--spread", type=float, default=2.0, help="yarım-spread bps")
    rs.add_argument("--capital", type=float, default=1000.0)
    rs.add_argument("--report-dir", default="reports")
    rs.add_argument("--copy-to", default="", help="raporun bir kopyasını bu dosyaya da yaz")
    rs.add_argument("--skip-ablation", action="store_true")
    rs.add_argument("--skip-wf", action="store_true")
    rs.add_argument("--skip-control", action="store_true", help="rastgele-giriş kontrolünü atla")
    rs.add_argument("--quick", action="store_true", help="küçük ızgara")
    rs.add_argument("--synthetic", type=int, default=0, help="çevrimdışı demo: N sentetik sembol")
    rg = sub.add_parser("regime-test", help="BTC/ETH SMA200 rejim filtresi testi (tek kural)")
    rg.add_argument("--days", type=int, default=3500)
    rg.add_argument("--n", type=int, default=200)
    rg.add_argument("--fee", type=float, default=0.001)
    rg.add_argument("--slippage", type=float, default=5.0, help="bps")
    rg.add_argument("--history-dir", default="data/history")
    rg.add_argument("--copy-to", default="")
    ru = sub.add_parser("run", help="7/24 koşucu (varsayılan: canlı veri + SANAL para)")
    ru.add_argument("--broker", choices=["paper", "demo"], default="paper")
    ru.add_argument("--capital", type=float, default=1000.0)
    ru.add_argument("--data-dir", default="data/forward")
    ru.add_argument("--manage-every", type=int, default=60, help="pozisyon yönetimi aralığı (sn)")
    ru.add_argument("--max-symbols", type=int, default=25)
    ru.add_argument("--max-failures", type=int, default=10)
    ru.add_argument("--max-ticks", type=int, default=0, help="test için tur sınırı (0 = sonsuz)")
    ru.add_argument("--once", action="store_true", help="CRON modu: tek tur yap ve çık (her dakika çağrılır)")
    ru.add_argument("--fresh", action="store_true", help="eski kayıtları SİL ve sıfırdan başla")
    st_ = sub.add_parser("status", help="ileri test sonuçlarını göster")
    st_.add_argument("--data-dir", default="data/forward")
    a = ap.parse_args(argv)
    if getattr(a, "copy_to", ""):
        from pathlib import Path as _P
        _P(a.copy_to).parent.mkdir(parents=True, exist_ok=True)
        fh = open(a.copy_to, "w", encoding="utf-8")
        sys.stdout, sys.stderr = _Tee(sys.stdout, fh), _Tee(sys.stderr, fh)
        print(f"[Bu çıktı canlı olarak şu dosyaya da yazılıyor: {a.copy_to}]")
    setup_logging(a.log_level)
    s = load_settings(a.config)
    try:
        return {"check": cmd_check, "capabilities": cmd_capabilities, "paper-demo": cmd_paper_demo, "scan": cmd_scan, "paper-run": cmd_paper_run, "fetch-history": cmd_fetch_history, "research": cmd_research, "regime-test": cmd_regime_test, "run": cmd_run, "status": cmd_status}[a.cmd](a, s)
    except Exception as exc:  # noqa: BLE001 — CLI temiz hata verir, çökmez
        print(f"[HATA] {type(exc).__name__}: {exc}", file=sys.stderr)
        if a.debug:
            import traceback
            traceback.print_exc()
        return 2


if __name__ == "__main__":
    sys.exit(main())
