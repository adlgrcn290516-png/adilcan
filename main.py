"""BINANCE AI TRADING SYSTEM — CLI.   Faz 1: check | capabilities | scan-data"""
from __future__ import annotations

import argparse
import sys

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


def main(argv=None) -> int:
    ap = argparse.ArgumentParser(prog="main.py")
    ap.add_argument("--config", default="config.yaml")
    ap.add_argument("--log-level", default="INFO")
    sub = ap.add_subparsers(dest="cmd", required=True)
    sub.add_parser("check", help="Bağlantı + market data + account data doğrulaması (salt okunur)")
    sub.add_parser("capabilities", help="Ürün yetenek matrisi")
    a = ap.parse_args(argv)
    setup_logging(a.log_level)
    s = load_settings(a.config)
    try:
        return {"check": cmd_check, "capabilities": cmd_capabilities}[a.cmd](a, s)
    except Exception as exc:  # noqa: BLE001 — CLI temiz hata verir, çökmez
        print(f"[HATA] {type(exc).__name__}: {exc}", file=sys.stderr)
        return 2


if __name__ == "__main__":
    sys.exit(main())
