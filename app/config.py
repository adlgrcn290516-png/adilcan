"""Konfigürasyon: config.yaml + .env. Secret'lar yalnızca ortam değişkenlerinden okunur."""
from __future__ import annotations

import os
from enum import Enum
from pathlib import Path

import yaml
from dotenv import load_dotenv
from pydantic import BaseModel, Field, SecretStr, model_validator

LIVE_CONFIRM_PHRASE = "I_UNDERSTAND_REAL_MONEY_WILL_BE_USED"


class Mode(str, Enum):
    BACKTEST = "backtest"
    PAPER = "paper"
    LIVE = "live"


class Environment(str, Enum):
    PROD = "prod"
    DEMO = "demo"
    TESTNET = "testnet"


class HttpCfg(BaseModel):
    timeout_ms: int = 10000
    retries: int = 4
    backoff_base_s: float = 1.0
    backoff_max_s: float = 30.0
    recv_window_ms: int = 5000


class RateLimitCfg(BaseModel):
    max_weight_per_min: int = 1200
    safety_ratio: float = Field(0.7, gt=0, le=1)


class UniverseCfg(BaseModel):
    quote_asset: str = "USDT"
    min_quote_volume_24h: float = 5_000_000
    max_symbols: int = 40            # hacme göre ilk N sembol taranır (rate-limit dostu)
    exclude_bases: list[str] = [     # stablecoin / fiat: trade edilecek "fırsat" değil
        "USDC", "FDUSD", "TUSD", "USDP", "DAI", "BUSD", "USDE", "USD1", "PYUSD", "AEUR", "EUR", "EURI", "XUSD", "RLUSD"]


class ScoringCfg(BaseModel):
    """Bileşen ağırlıkları KOD İÇİNDE DEĞİL burada. Pozitif ağırlıklar toplamı 1'e normalize edilir;
    risk_weight ceza katsayısıdır (ceza = risk_weight * risk_score)."""
    trend_weight: float = 0.25
    momentum_weight: float = 0.20
    volume_weight: float = 0.15
    breakout_weight: float = 0.15
    volatility_weight: float = 0.05
    liquidity_weight: float = 0.20
    risk_weight: float = 0.25
    buy_score_threshold: float = 65.0
    max_risk_score: float = 70.0
    min_strategy_votes: int = 2          # AI Composite: en az kaç strateji BUY demeli
    min_vote_confidence: float = 0.5


class StrategyCfg(BaseModel):
    atr_stop_mult: float = 2.0
    rr1: float = 1.5                     # TP1 = risk * rr1
    rr2: float = 3.0                     # TP2 = risk * rr2
    momentum_min_pct: float = 1.5        # 12 bar getiri eşiği
    breakout_lookback: int = 20
    volume_breakout_mult: float = 2.0
    meanrev_z: float = -2.0
    meanrev_rsi: float = 30.0
    squeeze_pctile: float = 0.2


class ScannerCfg(BaseModel):
    interval: str = "1h"
    kline_limit: int = 300
    min_bars: int = 60
    book_depth: int = 20
    top_n_report: int = 10


class RiskCfg(BaseModel):
    max_open_positions: int = 5
    max_position_size_pct: float = 0.20
    max_total_exposure_pct: float = 0.60
    max_daily_loss_pct: float = 0.03
    max_drawdown_pct: float = 0.15
    max_risk_per_trade_pct: float = 0.01
    emergency_stop: bool = False
    # --- piyasa koşulu limitleri ---
    max_spread_pct: float = 0.15            # %
    min_depth_quote: float = 50_000         # mid ±%1 içindeki bid+ask (quote)
    max_order_depth_pct: float = 5.0        # emir notional'ı derinliğin en fazla %X'i
    max_atr_pct: float = 6.0                # aşırı volatilite
    max_candle_atr: float = 4.0             # son mum aralığı > X*ATR => ani hareket (haber/şok) riski
    max_correlation: float = 0.85           # açık pozisyonlarla getiri korelasyonu
    correlation_reduce: float = 0.5         # korelasyon yüksekse boyut çarpanı
    max_api_errors: int = 5                 # ardışık API hatası -> yeni emir yok
    min_cash_reserve_pct: float = 0.05


class PositionMgmtCfg(BaseModel):
    breakeven_at_r: float = 1.0            # +1R'de stop -> giriş + tampon
    breakeven_buffer_pct: float = 0.25     # komisyon+slippage tamponu (%)
    partial_tp_pct: float = 0.5            # TP1'de pozisyonun bu kadarı satılır
    trail_start_r: float = 1.5             # +1.5R'den sonra trailing başlar
    trail_dist_r: float = 1.0              # trailing mesafesi = 1R (ilk risk mesafesi)


class Settings(BaseModel):
    mode: Mode = Mode.PAPER
    environment: Environment = Environment.PROD
    http: HttpCfg = HttpCfg()
    rate_limit: RateLimitCfg = RateLimitCfg()
    universe: UniverseCfg = UniverseCfg()
    scoring: ScoringCfg = ScoringCfg()
    strategy: StrategyCfg = StrategyCfg()
    scanner: ScannerCfg = ScannerCfg()
    risk: RiskCfg = RiskCfg()
    position: PositionMgmtCfg = PositionMgmtCfg()
    api_key: SecretStr | None = None
    api_secret: SecretStr | None = None
    live_confirm: str = ""

    @model_validator(mode="after")
    def _safety(self) -> "Settings":
        if self.mode is Mode.LIVE:
            if self.environment is not Environment.PROD:
                raise ValueError("LIVE mod yalnızca BINANCE_ENVIRONMENT=prod ile kullanılabilir")
            if self.live_confirm != LIVE_CONFIRM_PHRASE:
                raise ValueError(
                    "LIVE mod kilitli: LIVE_TRADING_CONFIRM=%s gerekli (bilinçli onay)" % LIVE_CONFIRM_PHRASE)
            if not (self.api_key and self.api_secret):
                raise ValueError("LIVE mod için BINANCE_API_KEY/SECRET gerekli")
        return self

    @property
    def has_credentials(self) -> bool:
        return bool(self.api_key and self.api_secret)

    @property
    def live_armed(self) -> bool:
        return self.mode is Mode.LIVE  # doğrulama _safety'de yapıldı


def load_settings(config_path: str | Path = "config.yaml", env_file: str | Path | None = ".env") -> Settings:
    if env_file and Path(env_file).exists():
        load_dotenv(env_file, override=False)
    raw: dict = {}
    p = Path(config_path)
    if p.exists():
        raw = yaml.safe_load(p.read_text()) or {}
    env = os.environ
    if env.get("TRADING_MODE"):
        raw["mode"] = env["TRADING_MODE"].lower()
    if env.get("BINANCE_ENVIRONMENT"):
        raw["environment"] = env["BINANCE_ENVIRONMENT"].lower()
    if env.get("BINANCE_API_KEY"):
        raw["api_key"] = env["BINANCE_API_KEY"]
    if env.get("BINANCE_API_SECRET"):
        raw["api_secret"] = env["BINANCE_API_SECRET"]
    raw["live_confirm"] = env.get("LIVE_TRADING_CONFIRM", "")
    return Settings.model_validate(raw)
