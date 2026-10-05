"""Gerçek para kilidi (kod tarafı).

Kullanıcı 20 USDT'lik GERÇEK deneme için açık onay verdi (oturum kaydı). Kilit bu yüzden True.
Ama gerçek emir yine de ancak şunlarla çıkar: TRADING_MODE=live + LIVE_TRADING_CONFIRM cümlesi (config.py)
ve gercek_*.bat dosyalarında kullanıcının yazdığı EVET. Ek olarak bütçe ve toplam kayıp sigortası vardır (app/live.py).
Kapatmak için bu sabiti False yap.
"""
REAL_MONEY_ENABLED = True
