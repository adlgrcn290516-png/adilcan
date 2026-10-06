@echo off
chcp 65001 >nul
setlocal
set PYTHONUTF8=1
set PYTHONIOENCODING=utf-8
cd /d "%~dp0.."
set "DESK=%USERPROFILE%\Desktop"
for /f "usebackq delims=" %%D in (`powershell -NoProfile -Command "[Environment]::GetFolderPath('Desktop')"`) do set "DESK=%%D"
echo ==================================================
echo  ARASTIRMA 7: Fibonacci + destek + MACD/RSI (1 saatlik, 4 yil)
echo  10-30 dakika surebilir. Pencereyi KAPATMA.
echo  Rapor: %DESK%\sr_1h.txt
echo ==================================================
".venv\Scripts\python.exe" -m pip install -r requirements.txt -q
".venv\Scripts\python.exe" main.py --log-level WARNING --debug research --family sr --top 15 --days 1460 --interval 1h --report-dir reports\sr1h --copy-to "%DESK%\sr_1h.txt"
echo.
echo  BITTI. Rapor: %DESK%\sr_1h.txt
if exist "%DESK%\sr_1h.txt" start notepad "%DESK%\sr_1h.txt"
