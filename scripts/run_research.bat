@echo off
chcp 65001 >nul
setlocal
set PYTHONUTF8=1
set PYTHONIOENCODING=utf-8
cd /d "%~dp0.."
rem Gercek masaustu klasorunu Windows'tan sor (OneDrive / Masaustu fark etmez)
set "DESK=%USERPROFILE%\Desktop"
for /f "usebackq delims=" %%D in (`powershell -NoProfile -Command "[Environment]::GetFolderPath('Desktop')"`) do set "DESK=%%D"
set "OUT=%DESK%\research.txt"
echo ==================================================
echo  ARASTIRMA: gecmis veri indirme + backtest + walk-forward
echo  5-15 dakika surebilir. Pencereyi KAPATMA.
echo  Rapor canli olarak su dosyaya yaziliyor:
echo  %OUT%
echo  (Dosya bittiginde kendiliginden acilir.)
echo ==================================================
".venv\Scripts\python.exe" -m pip install -r requirements.txt -q
".venv\Scripts\python.exe" main.py --log-level WARNING --debug research --top 15 --days 365 --copy-to "%OUT%"
echo.
echo ==================================================
echo  CALISMA BITTI. Rapor: %OUT%
echo  Proje klasorundeki kopya: %CD%\reports
echo ==================================================
if exist "%OUT%" start notepad "%OUT%"
