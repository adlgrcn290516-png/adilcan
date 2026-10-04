@echo off
chcp 65001 >nul
setlocal
set PYTHONUTF8=1
set PYTHONIOENCODING=utf-8
cd /d "%~dp0.."
set "DESK=%USERPROFILE%\Desktop"
for /f "usebackq delims=" %%D in (`powershell -NoProfile -Command "[Environment]::GetFolderPath('Desktop')"`) do set "DESK=%%D"
echo ==================================================
echo  DENEY 2: daha az islem (4 saatlik ve gunluk mumlar, uzun gecmis)
echo  Toplam 20-40 dk surebilir. Pencereyi KAPATMA.
echo  Raporlar masaustune: research_4h.txt ve research_1d.txt
echo ==================================================
".venv\Scripts\python.exe" -m pip install -r requirements.txt -q
echo.
echo [1/2] 4 saatlik mumlar...
".venv\Scripts\python.exe" main.py --log-level WARNING --debug research --top 15 --days 2500 --interval 4h --train-days 540 --test-days 120 --skip-ablation --quick --report-dir reports\h4 --copy-to "%DESK%\research_4h.txt"
echo.
echo [2/2] Gunluk mumlar...
".venv\Scripts\python.exe" main.py --log-level WARNING --debug research --top 15 --days 3000 --interval 1d --train-days 720 --test-days 180 --skip-ablation --quick --report-dir reports\d1 --copy-to "%DESK%\research_1d.txt"
echo.
echo ==================================================
echo  BITTI. Raporlar: %DESK%\research_4h.txt  ve  research_1d.txt
echo ==================================================
if exist "%DESK%\research_4h.txt" start notepad "%DESK%\research_4h.txt"
if exist "%DESK%\research_1d.txt" start notepad "%DESK%\research_1d.txt"
