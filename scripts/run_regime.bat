@echo off
chcp 65001 >nul
setlocal
set PYTHONUTF8=1
set PYTHONIOENCODING=utf-8
cd /d "%~dp0.."
set "DESK=%USERPROFILE%\Desktop"
for /f "usebackq delims=" %%D in (`powershell -NoProfile -Command "[Environment]::GetFolderPath('Desktop')"`) do set "DESK=%%D"
echo ==================================================
echo  REJIM FILTRESI TESTI (BTC/ETH, SMA200) - 1-3 dakika
echo  Rapor: %DESK%\regime.txt
echo ==================================================
".venv\Scripts\python.exe" -m pip install -r requirements.txt -q
".venv\Scripts\python.exe" main.py --log-level WARNING --debug regime-test --days 3500 --copy-to "%DESK%\regime.txt"
echo.
echo  BITTI. Rapor: %DESK%\regime.txt
if exist "%DESK%\regime.txt" start notepad "%DESK%\regime.txt"
