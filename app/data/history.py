"""Geçmiş mum verisi: sayfalama + CSV önbellek (artımlı). Yalnızca KAPANMIŞ mumlar saklanır."""
from __future__ import annotations

import json
import logging
from pathlib import Path

import pandas as pd

from app.core.features import klines_to_df

log = logging.getLogger(__name__)
INTERVAL_MS = {"1m": 60_000, "3m": 180_000, "5m": 300_000, "15m": 900_000, "30m": 1_800_000, "1h": 3_600_000,
               "2h": 7_200_000, "4h": 14_400_000, "6h": 21_600_000, "8h": 28_800_000, "12h": 43_200_000,
               "1d": 86_400_000}


class HistoryStore:
    def __init__(self, adapter=None, root: str | Path = "data/history"):
        self.ad, self.root = adapter, Path(root)

    def path(self, symbol: str, interval: str) -> Path:
        return self.root / f"{symbol}_{interval}.csv"

    def _meta_path(self, symbol: str, interval: str) -> Path:
        return self.root / f"{symbol}_{interval}.meta.json"

    def _requested_start(self, symbol: str, interval: str) -> int | None:
        """Bu sembol için daha önce İSTENEN en eski başlangıç (yeni listelenen coinlerde veri daha geç başlar)."""
        try:
            return int(json.loads(self._meta_path(symbol, interval).read_text())["requested_start"])
        except Exception:  # noqa: BLE001
            return None

    def load(self, symbol: str, interval: str) -> pd.DataFrame | None:
        p = self.path(symbol, interval)
        return pd.read_csv(p) if p.exists() else None

    def update(self, symbol: str, interval: str, days: int, now_ms: int) -> pd.DataFrame:
        """Önbelleği günceller (yoksa `days` gün geriye indirir) ve döndürür."""
        step = INTERVAL_MS[interval]
        cached = self.load(symbol, interval)
        want_start = now_ms - days * 86_400_000
        start = want_start
        if cached is not None and len(cached):
            req = self._requested_start(symbol, interval)
            covers = int(cached["open_time"].iloc[0]) <= want_start + step or (req is not None and req <= want_start + step)
            if covers:
                start = int(cached["close_time"].iloc[-1]) + 1  # artımlı
            else:
                cached = None  # önbellek yeterince geriye gitmiyor: baştan indir
        parts = [cached] if cached is not None else []
        while start < now_ms:
            ks = self.ad.klines(symbol, interval, 1000, start_ms=start)
            if not ks:
                break
            df = klines_to_df(ks)
            parts.append(df)
            nxt = int(df["close_time"].iloc[-1]) + 1
            if nxt <= start or len(ks) < 1000:
                break
            start = nxt
        if not parts:
            return pd.DataFrame()
        full = pd.concat(parts).drop_duplicates("open_time").sort_values("open_time")
        full = full[full["close_time"] < now_ms].reset_index(drop=True)  # oluşmakta olan mum yok
        self.root.mkdir(parents=True, exist_ok=True)
        full.to_csv(self.path(symbol, interval), index=False)
        prev_req = self._requested_start(symbol, interval)
        self._meta_path(symbol, interval).write_text(json.dumps(
            {"requested_start": min(want_start, prev_req) if prev_req is not None and cached is not None else want_start}))
        log.info("%s %s: %d bar (%d yeni parça)", symbol, interval, len(full), len(parts))
        return full
