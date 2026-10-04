"""ML modelleri için takılabilir arayüz. İlk sürümde MODEL YOK (NullModel): 'AI' süsü yok, deterministik skor var.

İleride bir model eğitilince PredictiveModel'i uygulayıp AIComposite'e verirsin; walk-forward ile
doğrulanmadan canlıda kullanılmaz.
"""
from __future__ import annotations

from abc import ABC, abstractmethod

from app.core.features import Features


class PredictiveModel(ABC):
    @property
    @abstractmethod
    def is_trained(self) -> bool: ...

    @abstractmethod
    def predict_probability(self, f: Features) -> float | None:
        """P(TP1, stop'tan önce vurulur) in [0,1]."""

    @abstractmethod
    def predict_expected_return(self, f: Features) -> float | None:
        """Beklenen getiri (%)."""

    @abstractmethod
    def predict_risk(self, f: Features) -> float | None:
        """Risk skoru [0,100]."""


class NullModel(PredictiveModel):
    is_trained = False

    def predict_probability(self, f): return None
    def predict_expected_return(self, f): return None
    def predict_risk(self, f): return None
