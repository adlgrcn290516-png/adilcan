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


class RiskCfg(BaseModel):
    max_open_positions: int = 5
    max_position_size_pct: float = 0.20
    max_total_exposure_pct: float = 0.60
    max_daily_loss_pct: float = 0.03
    max_drawdown_pct: float = 0.15
    max_risk_per_trade_pct: float = 0.01
    emergency_stop: bool = False


class Settings(BaseModel):
    mode: Mode = Mode.PAPER
    environment: Environment = Environment.PROD
    http: HttpCfg = HttpCfg()
    rate_limit: RateLimitCfg = RateLimitCfg()
    universe: UniverseCfg = UniverseCfg()
    risk: RiskCfg = RiskCfg()
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
