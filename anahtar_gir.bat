@echo off
chcp 65001 >nul
set PYTHONUTF8=1
cd /d "%USERPROFILE%\adilcan"
echo ============================================================
echo  BINANCE ANAHTAR GIRISI - sadece bu bilgisayarda .env dosyasina yazilir.
echo  Bu islem EMIR GONDERMEZ. Sadece hesabi OKUMAYI test eder.
echo ============================================================
echo.
set /p K=API Key yapistir ve Enter bas: 
set /p S=Secret Key yapistir ve Enter bas: 
> .env echo BINANCE_API_KEY=%K%
>> .env echo BINANCE_API_SECRET=%S%
>> .env echo BINANCE_ENVIRONMENT=prod
>> .env echo TRADING_MODE=paper
>> .env echo LIVE_TRADING_CONFIRM=
echo.
echo .env yazildi. Hesap okuma testi basliyor...
echo.
".venv\Scripts\python.exe" main.py check
echo.
pause
