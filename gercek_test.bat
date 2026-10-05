@echo off
chcp 65001 >nul
set PYTHONUTF8=1
cd /d "%USERPROFILE%\adilcan"
echo ============================================================
echo  GERCEK PARA TESTI - Binance hesabinda GERCEK emir gider.
echo  Yapilacak: yaklasik 6 USDT lik BTC alinacak ve hemen satilacak.
echo  Maliyet yaklasik birkac sent. Baska islem yapilmaz.
echo ============================================================
set /p ONAY=Devam icin EVET yaz ve Enter bas: 
if /I not "%ONAY%"=="EVET" (echo Iptal edildi. & pause & exit /b)
set TRADING_MODE=live
set LIVE_TRADING_CONFIRM=I_UNDERSTAND_REAL_MONEY_WILL_BE_USED
".venv\Scripts\python.exe" main.py --log-level INFO live-test --data-dir data\live
echo.
pause
