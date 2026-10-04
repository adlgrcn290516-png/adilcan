"""Pozisyon modeli. Stop yalnızca YUKARI taşınabilir (ratchet); asla silinemez/aşağı çekilemez."""
from __future__ import annotations

import time
from dataclasses import asdict, dataclass, field
from decimal import Decimal


@dataclass
class Position:
    symbol: str
    qty: Decimal                 # eldeki (komisyon sonrası) miktar
    entry_price: Decimal         # gerçekleşen ortalama giriş
    stop: Decimal
    tp1: Decimal
    tp2: Decimal
    initial_stop: Decimal
    cost_quote: Decimal          # alış için harcanan quote (kalan miktara orantılı azalır)
    strategy: str = "ai_composite"
    highest: Decimal = Decimal(0)
    partial_taken: bool = False
    breakeven_done: bool = False
    status: str = "OPEN"
    opened_at: float = field(default_factory=time.time)
    closed_at: float | None = None
    realized_pnl: Decimal = Decimal(0)
    exit_seq: int = 0            # çıkış emirlerinin intent_id'si için sayaç
    id: int | None = None

    def __post_init__(self):
        if self.initial_stop >= self.entry_price:
            raise ValueError("ilk stop girişin altında olmalı (stop zorunlu)")
        if self.stop < self.initial_stop:
            raise ValueError("stop ilk stop'un altına indirilemez")
        if self.highest == 0:
            self.highest = self.entry_price

    @property
    def risk_dist(self) -> Decimal:
        """İlk risk mesafesi (1R)."""
        return self.entry_price - self.initial_stop

    def raise_stop(self, new_stop: Decimal) -> bool:
        """Stop'u yalnızca yükseltir. Daha düşük/eşit değer sessizce reddedilir (False)."""
        if new_stop > self.stop and new_stop < self.highest:
            self.stop = new_stop
            return True
        return False

    def to_dict(self) -> dict:
        d = asdict(self)
        return {k: (str(v) if isinstance(v, Decimal) else v) for k, v in d.items()}

    @classmethod
    def from_dict(cls, d: dict) -> "Position":
        dec = {"qty", "entry_price", "stop", "tp1", "tp2", "initial_stop", "cost_quote", "highest", "realized_pnl"}
        kw = {k: (Decimal(v) if k in dec else v) for k, v in d.items()}
        return cls(**kw)
