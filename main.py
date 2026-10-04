"""BINANCE AI TRADING SYSTEM — CLI.   Faz 1: check | capabilities   Faz 2: paper-demo   Faz 3: scan   Faz 4: paper-run   Faz 5: fetch-history, research"""
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
        return {"check": cmd_check, "capabilities": cmd_capabilities, "paper-demo": cmd_paper_demo, "scan": cmd_scan, "paper-run": cmd_paper_run, "fetch-history": cmd_fetch_history, "research": cmd_research}[a.cmd](a, s)
    except Exception as exc:  # noqa: BLE001 — CLI temiz hata verir, çökmez
        print(f"[HATA] {type(exc).__name__}: {exc}", file=sys.stderr)
        if a.debug:
            import traceback
            traceback.print_exc()
        return 2


if __name__ == "__main__":
    sys.exit(main())
