"""Exponential backoff + basit weight tabanlı rate-limit koruması."""
from __future__ import annotations

import logging
import random
import threading
import time
from typing import Callable, TypeVar

log = logging.getLogger(__name__)
T = TypeVar("T")


class NonRetryableError(Exception):
    """Tekrar denenmemesi gereken hata (geçersiz istek, auth, IP ban...)."""


class RateLimitBanned(NonRetryableError):
    """HTTP 418: IP geçici olarak banlı. Beklemek yerine çağıran katman API'yi durdurmalı."""

    def __init__(self, retry_after: int | None):
        self.retry_after = retry_after
        super().__init__(f"Binance IP ban (418); retry_after={retry_after}s — API çağrıları durduruldu")


# NOT: binance_common hatalarında `status_code` HTTP kodu DEĞİL, Binance hata kodudur (örn. -1121).
# Bu yüzden sınıf adına göre sınıflandırıyoruz.
_RETRYABLE_NAMES = {"TooManyRequestsError", "ServerError", "NetworkError"}
_FATAL_NAMES = {"BadRequestError", "UnauthorizedError", "ForbiddenError", "NotFoundError",
                "ClientError", "RequiredError"}


def _classify(exc: Exception) -> str:
    """'ban' | 'retry' | 'fatal'"""
    if isinstance(exc, NonRetryableError):
        return "fatal"
    names = {c.__name__ for c in type(exc).__mro__}
    if "RateLimitBanError" in names:
        return "ban"
    if names & _FATAL_NAMES:
        return "fatal"
    return "retry"  # SDK'nın bilinen retryable hataları + timeout/bağlantı/bilinmeyen ağ hataları


def retry_call(fn: Callable[[], T], *, retries: int = 4, base: float = 1.0, cap: float = 30.0,
               sleep: Callable[[float], None] = time.sleep, what: str = "call") -> T:
    attempt = 0
    while True:
        try:
            return fn()
        except Exception as exc:  # noqa: BLE001
            kind = _classify(exc)
            if kind == "ban":
                raise RateLimitBanned(getattr(exc, "retry_after", None)) from exc
            attempt += 1
            if kind == "fatal" or attempt > retries:
                raise
            delay = min(cap, base * 2 ** (attempt - 1))
            hinted = getattr(exc, "retry_after", None)
            if "TooManyRequestsError" in {c.__name__ for c in type(exc).__mro__}:
                delay = max(delay, float(hinted) if hinted else 5.0)  # Retry-After'a uy
            else:
                delay *= 0.5 + random.random() / 2  # jitter
            log.warning("%s hata (%s: %s) -> %.1fs sonra tekrar %d/%d",
                        what, type(exc).__name__, exc, delay, attempt, retries)
            sleep(delay)


class WeightLimiter:
    """Dakikalık REQUEST_WEIGHT bütçesi; eşik aşılırsa bekletir."""

    def __init__(self, max_weight_per_min: int, safety_ratio: float = 0.7,
                 clock: Callable[[], float] = time.monotonic,
                 sleep: Callable[[float], None] = time.sleep):
        self.budget = int(max_weight_per_min * safety_ratio)
        self._clock, self._sleep = clock, sleep
        self._events: list[tuple[float, int]] = []
        self._lock = threading.Lock()

    def acquire(self, weight: int = 1) -> None:
        while True:
            with self._lock:
                now = self._clock()
                self._events = [(t, w) for t, w in self._events if now - t < 60]
                used = sum(w for _, w in self._events)
                if used + weight <= self.budget or not self._events:
                    self._events.append((now, weight))
                    return
                wait = 60 - (now - self._events[0][0])
            log.warning("Rate-limit koruması: %.1fs bekleniyor (kullanılan=%d/%d)", wait, used, self.budget)
            self._sleep(max(wait, 0.1))

    def sync_from_header(self, used_weight_1m: int) -> None:
        """Binance'in döndürdüğü gerçek kullanımı yerel sayaca yansıt."""
        with self._lock:
            local = sum(w for _, w in self._events)
            if used_weight_1m > local:
                self._events.append((self._clock(), used_weight_1m - local))
