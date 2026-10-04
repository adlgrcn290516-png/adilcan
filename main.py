"""BINANCE AI TRADING SYSTEM — CLI.   Faz 1: check | capabilities   Faz 2: paper-demo   Faz 3: scan"""
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


def main(argv=None) -> int:
    ap = argparse.ArgumentParser(prog="main.py")
    ap.add_argument("--config", default="config.yaml")
    ap.add_argument("--log-level", default="INFO")
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
    a = ap.parse_args(argv)
    setup_logging(a.log_level)
    s = load_settings(a.config)
    try:
        return {"check": cmd_check, "capabilities": cmd_capabilities, "paper-demo": cmd_paper_demo, "scan": cmd_scan}[a.cmd](a, s)
    except Exception as exc:  # noqa: BLE001 — CLI temiz hata verir, çökmez
        print(f"[HATA] {type(exc).__name__}: {exc}", file=sys.stderr)
        return 2


if __name__ == "__main__":
    sys.exit(main())
