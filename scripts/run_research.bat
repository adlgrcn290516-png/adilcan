@echo off
chcp 65001 >nul
setlocal
set PYTHONUTF8=1
set PYTHONIOENCODING=utf-8
cd /d "%~dp0.."
set "OUT=%USERPROFILE%\Desktop\research.txt"
echo ==================================================
echo  ARASTIRMA: gecmis veri indirme + backtest + walk-forward
echo  (5-15 dakika surebilir; pencereyi KAPATMA)
echo ==================================================
".venv\Scripts\python.exe" -m pip install -r requirements.txt -q
".venv\Scripts\python.exe" main.py --log-level WARNING research --top 15 --days 365 --copy-to "%OUT%"
echo.
echo ==================================================
echo  BITTI. Rapor masaustunde: research.txt
echo  Icindekini kopyalayip bana yapistir.
echo ==================================================
