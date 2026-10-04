"""AI Composite: 6 stratejinin oyları + açıklanabilir composite skor (+ opsiyonel ML modeli).

'AI' burada deterministik, açıklanabilir bir birleştiricidir. Model verilmezse (NullModel) hiçbir
gizli tahmin kullanılmaz.
"""
from __future__ import annotations

from typing import Mapping

from app.config import ScoringCfg, StrategyCfg
from app.core.features import Features
from app.core.ml import NullModel, PredictiveModel
from app.core.scoring import score
from app.strategies.base import Signal, SignalResult, Strategy
from app.strategies.library import CLASSIC


class AIComposite(Strategy):
    name = "ai_composite"

    def __init__(self, cfg: StrategyCfg | None = None, scoring: ScoringCfg | None = None,
                 strategies: list[Strategy] | None = None, model: PredictiveModel | None = None):
        super().__init__(cfg)
        self.scoring = scoring or ScoringCfg()
        self.strategies = strategies or [cls(self.cfg) for cls in CLASSIC]
        self.model = model or NullModel()

    def evaluate(self, f: Features) -> SignalResult:
        return self.decide(f, {s.name: s.evaluate(f) for s in self.strategies})

    def decide(self, f: Features, outputs: Mapping[str, SignalResult]) -> SignalResult:
        """Karar mantığı, hazır strateji çıktılarından. Backtest çıktıları önbellekleyip eşik/ağırlık
        varyantlarını yeniden kullanır; canlıdaki evaluate() ile AYNI koddur."""
        sc = score(f, self.scoring)
        results = [outputs[s.name] for s in self.strategies]
        buys = [r for r in results if r.signal is Signal.BUY and r.confidence >= self.scoring.min_vote_confidence]
        sells = [r for r in results if r.signal is Signal.SELL and r.confidence >= 0.55]
        details = {
            "score": sc.composite, "contributions": [c.__dict__ for c in sc.contributions],
            "votes": [{"strategy": r.strategy, "signal": r.signal.value, "confidence": r.confidence,
                       "reason": r.reason} for r in results],
        }
        risk = sc.risk
        if self.model.is_trained:
            mr = self.model.predict_risk(f)
            risk = max(risk, mr) if mr is not None else risk

        if sc.composite >= self.scoring.buy_score_threshold and len(buys) >= self.scoring.min_strategy_votes \
                and risk <= self.scoring.max_risk_score and not sells:
            best = max(buys, key=lambda r: r.confidence)
            conf = 0.5 * (sum(r.confidence for r in buys) / len(buys)) + 0.5 * sc.composite / 100
            if self.model.is_trained:
                p = self.model.predict_probability(f)
                conf = 0.5 * conf + 0.5 * p if p is not None else conf
            why = f"Score {sc.composite:.0f}: " + ", ".join(sc.explain()) + " | oylar: " + ", ".join(r.strategy for r in buys)
            res = SignalResult(self.name, Signal.BUY, min(1.0, conf), best.entry_price, best.stop_loss,
                               best.take_profit, best.take_profit_2, best.expected_return, risk, why, details)
            res.validate()
            return res
        if sells:
            r = max(sells, key=lambda x: x.confidence)
            return SignalResult(self.name, Signal.SELL, r.confidence, f.price, risk_score=risk,
                                reason=f"çıkış oyu: {r.strategy} ({r.reason})", details=details)
        why = f"Score {sc.composite:.0f}"
        if sc.composite < self.scoring.buy_score_threshold:
            why += f" < eşik {self.scoring.buy_score_threshold:.0f}" + (f" ({len(buys)} strateji BUY dedi ama skor yetersiz)" if buys else "")
        else:
            if len(buys) < self.scoring.min_strategy_votes:
                why += f" eşik üstü ama yalnızca {len(buys)} strateji onayı (min {self.scoring.min_strategy_votes})"
            elif risk > self.scoring.max_risk_score:
                why += f" eşik üstü ama risk {risk:.0f} > {self.scoring.max_risk_score:.0f}"
        return SignalResult(self.name, Signal.HOLD, 0.0, risk_score=risk, reason=why, details=details)
