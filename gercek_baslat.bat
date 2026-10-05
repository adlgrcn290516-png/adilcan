@echo off
chcp 65001 >nul
set PYTHONUTF8=1
cd /d "%USERPROFILE%\adilcan"
echo ============================================================
echo  GERCEK PARA ROBOTU - Binance hesabinda GERCEK emirler gider.
echo  Butce 20 USDT. Toplam kayip 5 USDT e ulasirsa robot kendini durdurur.
echo  Borsada stop emri YOK. Bilgisayar acik ve internet bagli kalmali.
echo  Durdurmak icin: gercek_durdur.bat veya bu pencerede Ctrl+C
echo ============================================================
set /p ONAY=Devam icin EVET yaz ve Enter bas: 
if /I not "%ONAY%"=="EVET" (echo Iptal edildi. & pause & exit /b)
set TRADING_MODE=live
set LIVE_TRADING_CONFIRM=I_UNDERSTAND_REAL_MONEY_WILL_BE_USED
:dongu
".venv\Scripts\python.exe" main.py --log-level WARNING run --broker live --capital 20 --max-loss 5 --data-dir data\live
echo Robot durdu. 30 sn sonra yeniden baslar. Kapatmak icin Ctrl+C
timeout /t 30 >nul
goto dongu
