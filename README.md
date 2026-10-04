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
python main.py paper-demo         # sanal parayla emir motoru demosu (gerçek emir YOK)
python main.py scan --max-symbols 25   # piyasa tara + skorla (salt okunur)
python main.py check              # bağlantı + market data (+ key varsa hesap verisi), salt okunur
BINANCE_ENVIRONMENT=demo python main.py check
```
