@echo off
chcp 65001 >nul
setlocal
set PYTHONUTF8=1
set PYTHONIOENCODING=utf-8
set "LOG=%USERPROFILE%\Desktop\sonuc.txt"
cd /d "%~dp0.."

echo [1/3] Paketler kontrol ediliyor...
".venv\Scripts\python.exe" -m pip install -r requirements.txt -q

echo [2/3] Testler, baglanti, paper demo ve tarama calistiriliyor (1-2 dk surebilir)...
echo ===== TESTLER ===== > "%LOG%"
".venv\Scripts\python.exe" -m pytest -q >> "%LOG%" 2>&1
echo ===== BAGLANTI ===== >> "%LOG%"
".venv\Scripts\python.exe" main.py check >> "%LOG%" 2>&1
echo ===== PAPER DEMO (sanal para) ===== >> "%LOG%"
".venv\Scripts\python.exe" main.py paper-demo >> "%LOG%" 2>&1
echo ===== TARAMA (salt okunur) ===== >> "%LOG%"
".venv\Scripts\python.exe" main.py scan --max-symbols 25 >> "%LOG%" 2>&1

echo ===== PAPER-RUN: tam zincir, sanal para (BTC icin MANUEL demo sinyali) ===== >> "%LOG%"
".venv\Scripts\python.exe" main.py paper-run --max-symbols 25 --cycles 1 --force-buy BTCUSDT >> "%LOG%" 2>&1
echo ===== PAPER-RUN: EMERGENCY_STOP acik (yeni emir OLMAMALI) ===== >> "%LOG%"
".venv\Scripts\python.exe" main.py paper-run --max-symbols 10 --cycles 1 --force-buy BTCUSDT --emergency >> "%LOG%" 2>&1

echo [3/3] Bitti.
echo ==================================================
echo  Sonuc masaustunde: sonuc.txt  (icindekini bana yapistir)
echo ==================================================
type "%LOG%"
