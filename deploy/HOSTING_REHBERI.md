# Kendi sitende (panel.flutecnica.com barındırması) çalıştırma

Bu bir WEB sitesi için tasarlanmış barındırmadır; sistemimiz bir web uygulaması DEĞİL, arka planda çalışan bir programdır.
Çalışıp çalışmayacağı barındırma türüne bağlı. Aşağıdaki kontrol listesini yap:

## A) Önce şunları öğren (panelde / destek yazışmasında)
1. **SSH / Terminal erişimi var mı?** (cPanel'de "Terminal", Plesk'te "SSH Terminal", ya da ssh bilgisi)
2. **Python 3.10 veya üstü var mı?**  Terminalde: `python3 --version`
3. **Cron Jobs (Zamanlanmış Görevler) var mı ve her dakika çalıştırmaya izin veriyor mu?**
4. **Dışarıya bağlantı (api.binance.com) açık mı?**  Terminalde: `curl -s -o /dev/null -w "%{http_code}\n" https://api.binance.com/api/v3/ping`  → 200 çıkmalı.
5. Kaynak sınırı: paylaşımlı barındırmalar uzun süren ve bellek yiyen işlemleri öldürebilir (bu sistem ~200-300 MB kullanır).

## B) Sonuca göre yol
- **SSH + Python + 200 dönüyor, cron var** -> "Cron modu" (aşağıda). En uygunu.
- **VPS/özel sunucu (root erişimi)** -> `deploy/REHBER.md` (sürekli çalışan servis).
- **SSH yok veya Python yok veya binance 451/403 veriyor** -> bu barındırmada çalışmaz; ayrı küçük sunucu gerekir.

## C) Cron modu kurulumu (SSH ile bağlandıktan sonra)
```
cd ~
git clone --branch claude/binance-ai-trading-system-z6oo1n https://github.com/adlgrcn290516-png/adilcan.git
cd adilcan
python3 -m venv .venv && .venv/bin/pip install -r requirements.txt
.venv/bin/python -m pytest -q          # testler geçmeli
```
Cron Jobs ekranında **her dakika** için şu komutu ekle (yol: kendi ev dizinin):
```
* * * * * cd $HOME/adilcan && .venv/bin/python main.py --log-level WARNING run --once --data-dir data/forward >> data/cron.log 2>&1
```
- Her çağrı tek tur yapar: açık pozisyonları yönetir; saat başında tam tarama yapar. Üst üste binmeyi kilit dosyası engeller.
- Durum:        `cd ~/adilcan && .venv/bin/python main.py status --data-dir data/forward`
- Acil durdur:  `echo dur > ~/adilcan/data/forward/STOP`  (pozisyonları da kapat: `echo CLOSE > ...`)
- Log:          `tail -n 40 ~/adilcan/data/forward/runner.log`

## Güvenlik (ÖNEMLİ)
- Bu klasörü **web'e açık dizine (public_html) koyma**: veritabanı ve log herkese açık olabilir. Ev dizinine (public_html DIŞINA) kur.
- API anahtarı şu an gerekmiyor; hosting'e anahtar koyma.
- Gerçek para kilidi kodda kapalıdır ve kapalı kalır.
