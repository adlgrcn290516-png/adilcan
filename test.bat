@echo off
chcp 65001 >nul
setlocal
set PYTHONUTF8=1
set PYTHONIOENCODING=utf-8
set "BRANCH=claude/binance-ai-trading-system-z6oo1n"
set "TARGET=%USERPROFILE%\adilcan"
set "LOG=%USERPROFILE%\Desktop\sonuc.txt"
cd /d "%TARGET%"
if errorlevel 1 ( echo Proje klasoru yok. Once kur.bat calistir. & pause & exit /b 1 )

echo [1/3] Son surum indiriliyor...
git pull origin %BRANCH%
".venv\Scripts\python.exe" -m pip install -r requirements.txt -q

echo [2/3] Testler ve demo calistiriliyor...
echo ===== TESTLER ===== > "%LOG%"
".venv\Scripts\python.exe" -m pytest -q >> "%LOG%" 2>&1
echo ===== BAGLANTI ===== >> "%LOG%"
".venv\Scripts\python.exe" main.py check >> "%LOG%" 2>&1
echo ===== PAPER DEMO (sanal para) ===== >> "%LOG%"
".venv\Scripts\python.exe" main.py paper-demo >> "%LOG%" 2>&1

echo [3/3] Bitti.
echo ==================================================
echo  Sonuc masaustunde: sonuc.txt  (icindekini bana yapistir)
echo ==================================================
type "%LOG%"
pause
