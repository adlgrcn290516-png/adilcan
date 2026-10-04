# BINANCE AI TRADING SYSTEM — Mimari

## Doğrulanmış temel kararlar (repo kaynaklarından)
| Konu | Bulgu | Kaynak |
|---|---|---|
| SDK | Resmi SDK artık **modüler** (`binance-sdk-spot`, `-alpha`, `-derivatives-trading-usds-futures`, `-convert`, `-algo`). `binance-futures-connector-python` **DEPRECATED** → kullanılmıyor. | connector README, futures-connector README |
| Spot ortamları | PROD `api.binance.com`, TESTNET `testnet.binance.vision`, DEMO `demo-api.binance.com` | spot-api-docs `testnet/` ve `demo-mode/`, `binance_common/constants.py` |
| Futures ortamları | PROD `fapi.binance.com`, TESTNET ve DEMO (`demo-fapi.binance.com`) sabitleri SDK'da var | `constants.py` (emir endpointleri Faz 6'da metod metod doğrulanacak) |
| **Alpha** | SDK'da yalnızca market-data: `aggregated_trades, full_depth, get_exchange_info, klines, ticker, token_list`. Emir/hesap yok, test ortamı yok → **DATA_ONLY / UNSUPPORTED_FOR_TRADING** | `clients/alpha/.../rest_api.py` |
| Convert | Quote/limit emir var; orderbook/kline yok → tarama kaynağı değil | `clients/convert` |
| Algo | Spot/Futures TWAP, Futures VP → *execution aracı*, sinyal kaynağı değil | `clients/algo` |
| Weight | exchangeInfo 20, depth 5/25/50/250, klines 2, ticker24hr 2 (sembol) / 80 (hepsi), account 20, openOrders 6/80 | `rest-api.md` |
| SDK hata modeli | `status_code` = **Binance hata kodu**, HTTP kodu değil → retry sınıf adına göre | `binance_common/errors.py` |
| Freqtrade | Bağımlılık YOK; yalnızca fikir referansı (strategy interface, backtest, protections) | — |

## Katmanlar ve ana döngü
```
Market Data → Universe Filter → Feature Calc → Strategy Engine → Composite Score
 → Risk Engine → Portfolio Manager → Execution Engine → Order Verification
 → Position Mgmt → Logging → Dashboard
```
```
app/
  config.py            Settings (yaml + .env), LIVE kilidi
  exchange/            capabilities.py (CapabilityMatrix) · base.py · models.py · spot.py  [Faz1]
                       futures.py [F6] · alpha.py convert.py algo.py [F7]
  data/                kline/orderbook cache, SQLite repository'leri [F2-3]
  core/                scanner, signal engine, strategy engine [F3]
  strategies/          trend, momentum, breakout, volume_breakout, mean_reversion, vol_breakout [F3]
  risk/ portfolio/     risk motoru (APPROVE/REDUCE/REJECT), pozisyon boyutu, emergency stop [F4]
  execution/           filtre kontrolü, idempotent emir (clientOrderId), Binance'ten doğrulama [F2]
  backtest/ paper/     aynı Strategy arayüzü, look-ahead yok, walk-forward [F2,F5]
  dashboard/           [F8]
  utils/               logging (secret maskeleme), retry (backoff), WeightLimiter
```
## Güvenlik ilkeleri
1. `BinanceSpotAdapter` (veri) emir metodu içermez (test ile kilitli). Emirler yalnızca `execution/` altındaki `Broker`'lardan geçer. `SpotBinanceBroker` PROD'da `execution/safety.py::REAL_MONEY_ENABLED=False` olduğu sürece **oluşturulamaz** (yalnızca demo/testnet). Bu bayrak config/env ile açılamaz.
1b. SDK `float`'ı `str()` ile yollar (`0.00001`→`1e-05`); emirlerde miktar/fiyat her zaman düz string (testle kilitli).
1c. Emir yaşam döngüsü: dedupe(DB UNIQUE intent) → emergency stop → filtre → bakiye → write-ahead kayıt → gönder (belirsiz hatada önce `get_order`) → `get_order` ile doğrula.
2. `TRADING_MODE=live` ancak: `BINANCE_ENVIRONMENT=prod` + API key + `LIVE_TRADING_CONFIRM=I_UNDERSTAND_REAL_MONEY_WILL_BE_USED`. Aksi halde `Settings` oluşmaz.
3. Secret'lar `SecretStr`, `.env` gitignore'da, loglar imza/anahtar maskeler.
4. Ürün `trading=YES` değilse `require_trading()` PermissionError verir (Alpha vb.).
5. 418 ban → API durdurulur (uyuyup denenmez); 429 → `Retry-After`'a uyulur; ağ/5xx → exponential backoff + jitter.

## Faz 4 — Risk & Portföy
- `risk/engine.py`: strateji-bağımsız karar **APPROVE / REDUCE / REJECT** + tüm nedenlerin listesi. Kontroller: emergency stop,
  günlük zarar, drawdown (**kilitlenir**, elle sıfırlanır), açık pozisyon sayısı, API sağlığı, zorunlu stop, spread, likidite,
  volatilite, ani hareket (son mum > X ATR), korelasyon, bakiye+rezerv, toplam maruziyet, minimum emir.
- Boyut: `qty = equity*risk% / (entry-stop)`, `min(risk_qty, max_position_size)`; sonra maruziyet/bakiye/derinlik/korelasyon ile AZALTILIR.
- `portfolio/manager.py`: giriş (canlı ask'a taşınan 1R), bakiye FARKINDAN gerçek miktar, stop/BE/kısmi TP/TP2/trailing
  (stop yalnızca YUKARI), acil durdurma (+opsiyonel hepsini kapat), yeniden başlatmada DB'den yükleme + `reconcile()`.
- `core/orchestrator.py`: ana döngü; önce mevcut pozisyonlar yönetilir, sonra yeni girişler.
- Test ile yakalanıp düzeltilen hatalar: (1) kısmi TP1 ve TP2 çıkışları aynı intent kimliğini kullanıp çift sayılıyordu,
  (2) başarılı fiyat sorgusu API-hata sayacını yanlışlıkla sıfırlıyordu, (3) BE/trailing sonrası stop girişin üstüne çıkınca
  pozisyon yeniden yüklenemiyordu.

### LIVE ÖN KOŞULLARI (hepsi sağlanmadan canlı YOK)
1. **Borsa-tarafı stop** (STOP_LOSS_LIMIT / OCO). Şu an stop yazılım stop'u: bot kapalıyken çalışmaz. (Faz 9)
2. Gerçek komisyon/PnL için `myTrades` ile uzlaştırma (RESULT yanıtı komisyon vermez). (Faz 9)
3. Paper bakiyesinin kalıcılığı + tek-süreç kilidi. (Faz 9)
4. Backtest + walk-forward sonuçları ve testnet/demo'da uzun süreli paper çalışma. (Faz 5+)
5. Senin açık onayın; `safety.REAL_MONEY_ENABLED` elle True yapılır.
