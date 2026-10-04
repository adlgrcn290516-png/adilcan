# BINANCE AI TRADING SYSTEM
Modüler, açıklanabilir, risk kontrollü trading platformu. **Faz 1/9 tamam** (salt-okunur; emir kodu yok).
Detay: `docs/ARCHITECTURE.md`

## Kurulum
```bash
python3 -m venv .venv && . .venv/bin/activate
pip install -r requirements.txt
cp .env.example .env      # key'leri gir (opsiyonel: market data key gerektirmez)
```
## Çalıştır
```bash
python -m pytest -q               # testler
python main.py capabilities       # ürün yetenek matrisi
python main.py check              # bağlantı + market data (+ key varsa hesap verisi), salt okunur
BINANCE_ENVIRONMENT=demo python main.py check
```
