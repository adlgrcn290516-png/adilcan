"""Tek-kural rejim filtresi testi: kapanış > SMA(n) ise tut, değilse nakit. (n=200 kanonik; AYARLANMADI.)

Look-ahead yok: pozisyon kararı bar i KAPANIŞINDA verilir (yalnızca <= i verisi), bar i+1 AÇILIŞINDA uygulanır.
Nakit getirisi 0 varsayılır (kötümser). Maliyet her geçişte komisyon+kayma.
Kontrol: pozisyon serisini DAİRESEL KAYDIRMA (aynı maruziyet, aynı geçiş sayısı, zamanlama bozulur).
"""
from __future__ import annotations

import math
from dataclasses import dataclass

import numpy as np

DAY_MS = 86_400_000


def sma(x: np.ndarray, n: int) -> np.ndarray:
    out = np.full(len(x), np.nan)
    if len(x) >= n:
        cs = np.cumsum(np.insert(x, 0, 0.0))
        out[n - 1:] = (cs[n:] - cs[:-n]) / n
    return out


def positions(close: np.ndarray, n: int = 200) -> np.ndarray:
    """p[i] = True: bar i kapanışında karar 'tut' (bar i+1 açılışından itibaren)."""
    s = sma(close, n)
    return np.where(np.isnan(s), False, close > s)


def strategy_returns(open_: np.ndarray, close: np.ndarray, p: np.ndarray, cost: float) -> np.ndarray:
    """r[i]: gün i'de kazanılan getiri. p[i-1] kararı gün i'ye uygulanır (açılıştan)."""
    N = len(close)
    r = np.zeros(N)
    prev = np.concatenate([[False], p[:-1]])           # p[i-1]
    prev2 = np.concatenate([[False, False], p[:-2]])   # p[i-2]: gün i-1 boyunca tutuluyor muydu
    hold = prev & prev2
    enter = prev & ~prev2
    exit_ = ~prev & prev2
    cc = np.concatenate([[np.nan], close[1:] / close[:-1] - 1])
    r = np.where(hold, cc, r)
    r = np.where(enter, close / open_ - 1 - cost, r)
    oc = np.concatenate([[np.nan], open_[1:] / close[:-1] - 1])
    r = np.where(exit_, oc - cost, r)
    r[0] = 0.0
    return np.nan_to_num(r)


def stats(r: np.ndarray) -> dict:
    eq = np.cumprod(1 + r)
    peak = np.maximum.accumulate(eq)
    dd = float(((peak - eq) / peak).max())
    sd = r.std(ddof=1)
    years = len(r) / 365
    total = float(eq[-1] - 1)
    cagr = (eq[-1]) ** (1 / years) - 1 if years > 0 and eq[-1] > 0 else float("nan")
    return {"total": total, "cagr": float(cagr), "maxdd": dd, "sharpe": float(r.mean() / sd * math.sqrt(365)) if sd > 0 else 0.0,
            "calmar": float(cagr / dd) if dd > 0 and cagr == cagr else float("nan")}


@dataclass
class AssetResult:
    symbol: str
    hold: dict
    filt: dict
    exposure: float
    switches: int
    shift_pctile_sharpe: float
    shift_pctile_dd: float
    by_year: dict
    n_days: int
    start_ts: int


def evaluate_asset(symbol: str, ts: np.ndarray, open_: np.ndarray, close: np.ndarray, n: int = 200,
                   fee: float = 0.001, slip_bps: float = 5.0, n_shifts: int = 1000, seed: int = 7) -> AssetResult:
    cost = fee + slip_bps / 10_000
    p = positions(close, n)
    start = n  # SMA oturduktan sonra; hold ve filtre AYNI başlangıçtan kıyaslanır
    r_f = strategy_returns(open_, close, p, cost)[start:]
    r_h = np.concatenate([[np.nan], close[1:] / close[:-1] - 1])[start:]
    r_h = np.nan_to_num(r_h)
    f_stats, h_stats = stats(r_f), stats(r_h)
    region = p[start - 1:]  # kararların tamamı (start-1'den)
    switches = int((np.diff(region.astype(int)) != 0).sum())
    rng = np.random.default_rng(seed)
    sh, dd = [], []
    L = len(region)
    for _ in range(n_shifts):
        k = int(rng.integers(30, L - 30))
        ps = p.copy()
        ps[start - 1:] = np.roll(region, k)
        rs = strategy_returns(open_, close, ps, cost)[start:]
        st = stats(rs)
        sh.append(st["sharpe"])
        dd.append(st["maxdd"])
    years = np.array([int(1970 + t / (365.25 * DAY_MS)) for t in ts[start:]])
    by_year = {}
    for y in sorted(set(years.tolist())):
        m = years == y
        by_year[y] = (float(np.prod(1 + r_f[m]) - 1), float(np.prod(1 + r_h[m]) - 1))
    return AssetResult(symbol, h_stats, f_stats, float(region.mean()), switches,
                       float((np.array(sh) < f_stats["sharpe"]).mean()), float((np.array(dd) > f_stats["maxdd"]).mean()),
                       by_year, len(r_f), int(ts[start]))


CRITERIA = ("Sharpe(filtre) >= Sharpe(tutmak)", "MaxDD(filtre) <= 0.60 x MaxDD(tutmak)",
            "Dairesel-kaydırma kontrolünde filtre Sharpe'ı >= %90'lık dilim")


def verdict(results: list[AssetResult]) -> tuple[list[str], bool]:
    lines, ok_all = [], True
    for r in results:
        c = [r.filt["sharpe"] >= r.hold["sharpe"], r.filt["maxdd"] <= 0.60 * r.hold["maxdd"], r.shift_pctile_sharpe >= 0.90]
        for name, ok in zip(CRITERIA, c):
            lines.append(f"  [{'GEÇTİ' if ok else 'KALDI'}] {r.symbol}: {name}")
        ok_all &= all(c)
    lines.append("  >>> SONUÇ: " + ("TÜM kriterler her iki varlıkta geçti (yine de ileri-test gerekir)." if ok_all else
                                    "KALDI — rejim filtresi, sabitlediğimiz kriterlere göre kanıtlanamadı."))
    return lines, ok_all


def report(results: list[AssetResult], n: int, fee: float, slip_bps: float) -> str:
    L = ["=" * 78, f"REJİM FİLTRESİ TESTİ — kapanış > SMA({n}) ise tut, değilse NAKİT (nakit getirisi 0)", "=" * 78,
         f"Maliyet: komisyon %{fee * 100:.2f} + kayma {slip_bps} bps / geçiş | karar: bar kapanışı, uygulama: sonraki bar AÇILIŞI",
         f"Parametre n={n} KANONİK değerdir, bu veriye göre AYARLANMADI. Tek kural, iki varlık (BTC, ETH).", ""]
    pc = lambda x: f"{x * 100:.1f}%"  # noqa: E731
    for r in results:
        yrs = (r.n_days / 365)
        L += [f"--- {r.symbol} | {yrs:.1f} yıl | başlangıç {time_iso(r.start_ts)} | maruziyet {pc(r.exposure)} | geçiş sayısı {r.switches} ---",
              f"  {'':<12}{'Toplam':>10}{'CAGR':>9}{'MaxDD':>9}{'Sharpe':>8}{'Calmar':>8}",
              f"  {'TUTMAK':<12}{pc(r.hold['total']):>10}{pc(r.hold['cagr']):>9}{pc(r.hold['maxdd']):>9}{r.hold['sharpe']:>8.2f}{r.hold['calmar']:>8.2f}",
              f"  {'FİLTRE':<12}{pc(r.filt['total']):>10}{pc(r.filt['cagr']):>9}{pc(r.filt['maxdd']):>9}{r.filt['sharpe']:>8.2f}{r.filt['calmar']:>8.2f}",
              f"  Dairesel-kaydırma kontrolü: filtre Sharpe'ı rastgele zamanlamaların %{r.shift_pctile_sharpe * 100:.0f}'inden iyi; "
              f"MaxDD'si %{r.shift_pctile_dd * 100:.0f}'inden düşük",
              "  Yıllara göre (filtre | tutmak):"]
        L.append("    " + "  ".join(f"{y}: {pc(a)}|{pc(b)}" for y, (a, b) in r.by_year.items()))
        L.append("")
    L.append("KARAR KRİTERLERİ (sonuçlar görülmeden ÖNCE sabitlendi; her iki varlıkta hepsi gerekli)")
    lines, _ = verdict(results)
    L += lines
    L += ["", "SINIRLAMALAR:",
          "  - BTC ve ETH, kripto tarihinin en büyük 'hayatta kalanları': tutma sonuçları İYİMSERDİR; kıyas zor bir eşiktir.",
          "  - Örneklem boğa ağırlıklı; ayı rejimi sayısı az (2018, 2022). Birkaç rejim = az bağımsız gözlem.",
          "  - Nakit getirisi 0 kabul edildi (Binance Earn gibi getiri eklenmedi): filtre için kötümser.",
          "  - Vergi, çekim, stabilcoin riski, borsa riski hesaba katılmadı.",
          "  - Bu bir kâr garantisi DEĞİLDİR."]
    return "\n".join(L)


def time_iso(ms: int) -> str:
    import time
    return time.strftime("%Y-%m-%d", time.gmtime(ms / 1000))
