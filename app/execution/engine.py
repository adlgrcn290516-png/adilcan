"""ExecutionEngine — emir yaşam döngüsü (Faz 2).

Akış: dedupe -> emergency stop -> filtre normalize -> bakiye -> write-ahead kayıt -> gönder
      (belirsiz hatada ÖNCE sorgula) -> broker'dan DOĞRULA -> kaydet.
'API cevap verdi' = 'emir gerçekleşti' DEĞİLDİR; son durum her zaman get_order ile okunur.
"""
from __future__ import annotations

import logging
import time
from decimal import Decimal
from typing import Callable

from app.config import Settings
from app.data.db import OrderRepository
from app.exchange.models import SymbolInfo
from app.execution.broker import Broker
from app.execution.filters import normalize
from app.execution.types import OrderRejected, OrderRequest, OrderState, OrderType, Side, Status
from app.utils.retry import RateLimitBanned, _classify

log = logging.getLogger(__name__)
MARKET_BUY_BUFFER = Decimal("1.005")  # market alımda fiyat kayması payı (bakiye ön-kontrolü)


class ExecutionEngine:
    def __init__(self, broker: Broker, symbols: dict[str, SymbolInfo], repo: OrderRepository, settings: Settings,
                 *, verify_attempts: int = 6, verify_interval_s: float = 0.5,
                 sleep: Callable[[float], None] = time.sleep):
        self.broker, self.symbols, self.repo, self.s = broker, symbols, repo, settings
        self.emergency_stop = settings.risk.emergency_stop
        self.verify_attempts, self.verify_interval, self._sleep = verify_attempts, verify_interval_s, sleep

    # ---- yerel ön kontroller ----
    def _check_balance(self, req: OrderRequest, info: SymbolInfo) -> None:
        if req.side is Side.BUY:
            ref = req.price if req.type is OrderType.LIMIT else req.ref_price * MARKET_BUY_BUFFER
            need, asset = req.quantity * ref, info.quote
        else:
            need, asset = req.quantity, info.base
        have = self.broker.free_balance(asset)
        if have < need:
            raise OrderRejected(f"yetersiz bakiye {asset}: gerekli {need:.8f}, mevcut {have}")

    def place(self, req: OrderRequest) -> OrderState:
        # 1) aynı niyet daha önce işlendi mi? (restart sonrası da korur: DB'de UNIQUE)
        prev = self.repo.get_by_intent(req.intent_key)
        if prev is not None:
            log.warning("DUPLICATE intent=%s -> yeni emir GÖNDERİLMEDİ (mevcut durum=%s)", req.intent_key, prev.status.value)
            return self._refresh(prev) if not prev.status.terminal else prev
        # 2) emergency stop: yeni pozisyon açan (BUY) emir yok; SELL (kapatma) serbest
        if self.emergency_stop and req.side is Side.BUY:
            raise OrderRejected("EMERGENCY_STOP aktif: yeni emir açılamaz")
        info = self.symbols.get(req.symbol)
        if info is None:
            raise OrderRejected(f"bilinmeyen sembol {req.symbol}")
        # 3) filtreler + bakiye
        req = normalize(req, info)
        self._check_balance(req, info)
        # 4) write-ahead: göndermeden ÖNCE kaydet (çökme olursa tekrar gönderilmez)
        cid = req.client_order_id
        pending = OrderState(cid, req.symbol, req.side, req.type, Status.UNKNOWN, req.quantity, price=req.price,
                             reason="gönderim başladı")
        self.repo.insert(req.intent_key, pending, self.broker.name)
        # 5) gönder (belirsiz hatada önce sorgula)
        sent = self._send(req, pending)
        # 6) doğrula
        final = self._verify(sent, req)
        self.repo.update(final)
        log.info("ORDER %s %s %s qty=%s exec=%s avg=%s status=%s verified=%s",
                 final.symbol, final.side.value, final.type.value, final.orig_qty, final.executed_qty,
                 final.avg_price, final.status.value, final.verified)
        return final

    def _send(self, req: OrderRequest, pending: OrderState) -> OrderState:
        cid = req.client_order_id
        for attempt in (1, 2):
            try:
                return self.broker.submit(req)
            except RateLimitBanned:
                pending.status, pending.reason = Status.REJECTED, "IP ban (418)"
                self.repo.update(pending)
                raise
            except Exception as exc:  # noqa: BLE001
                kind = _classify(exc)
                if kind == "fatal":  # borsa net olarak reddetti (örn. -2010)
                    pending.status, pending.reason = Status.REJECTED, f"{type(exc).__name__}: {exc}"
                    self.repo.update(pending)
                    log.error("ORDER REJECTED %s: %s", cid, pending.reason)
                    return pending
                # belirsiz (timeout/5xx): emir borsaya ulaşmış olabilir -> ÖNCE sorgula
                log.warning("submit belirsiz hata (%s); get_order ile kontrol ediliyor", type(exc).__name__)
                found = self.broker.get_order(req.symbol, cid)
                if found is not None:
                    return found
                if attempt == 2:
                    pending.status, pending.reason = Status.UNKNOWN, f"gönderim doğrulanamadı: {exc}"
                    self.repo.update(pending)
                    return pending
                self._sleep(self.verify_interval)  # emir yok -> bir kez daha dene
        return pending

    def _verify(self, sent: OrderState, req: OrderRequest) -> OrderState:
        if sent.status in (Status.REJECTED, Status.UNKNOWN) and not sent.order_id:
            return sent
        last = sent
        for i in range(self.verify_attempts):
            truth = self.broker.get_order(req.symbol, req.client_order_id)
            if truth is None:
                last = OrderState(**{**sent.__dict__, "status": Status.UNKNOWN, "reason": "broker emri bulamadı"})
            else:
                truth.verified = True
                last = truth
                # MARKET: terminal olana kadar; LIMIT: var olduğu doğrulanınca yeter
                if truth.status.terminal or req.type is OrderType.LIMIT:
                    return truth
            if i < self.verify_attempts - 1:
                self._sleep(self.verify_interval)
        return last

    def _refresh(self, st: OrderState) -> OrderState:
        truth = self.broker.get_order(st.symbol, st.client_order_id)
        if truth is not None:
            truth.verified = True
            self.repo.update(truth)
            return truth
        return st

    def cancel(self, symbol: str, client_order_id: str) -> OrderState | None:
        st = self.broker.cancel(symbol, client_order_id)
        if st is not None:
            self.repo.update(st)
        return st
