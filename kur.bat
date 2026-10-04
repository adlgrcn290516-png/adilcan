@echo off
chcp 65001 >nul
setlocal
set PYTHONUTF8=1
set PYTHONIOENCODING=utf-8
set "REPO=https://github.com/adlgrcn290516-png/adilcan.git"
set "BRANCH=claude/binance-ai-trading-system-z6oo1n"
set "TARGET=%USERPROFILE%\adilcan"
set "LOG=%USERPROFILE%\Desktop\sonuc.txt"

echo ==================================================
echo  BINANCE AI TRADING SYSTEM - KURULUM (Faz 1)
echo  Bu kurulum emir gondermez, sadece okur.
echo ==================================================
echo.

echo [1/6] Python kontrol ediliyor...
set "PY="
py -3 --version >nul 2>&1 && set "PY=py -3"
if not defined PY ( python --version >nul 2>&1 && set "PY=python" )
if not defined PY (
  echo Python bulunamadi, winget ile kuruluyor...
  winget install -e --id Python.Python.3.12 --accept-package-agreements --accept-source-agreements
  echo.
  echo Python kuruldu. Bu pencereyi KAPAT ve kur.bat dosyasini TEKRAR calistir.
  pause
  exit /b 0
)
%PY% --version

echo [2/6] Git kontrol ediliyor...
git --version >nul 2>&1
if errorlevel 1 (
  echo Git bulunamadi, winget ile kuruluyor...
  winget install -e --id Git.Git --accept-package-agreements --accept-source-agreements
  echo.
  echo Git kuruldu. Bu pencereyi KAPAT ve kur.bat dosyasini TEKRAR calistir.
  pause
  exit /b 0
)

echo [3/6] Proje indiriliyor: %TARGET%
if exist "%TARGET%\.git" (
  git -C "%TARGET%" fetch origin %BRANCH%
  git -C "%TARGET%" checkout %BRANCH%
  git -C "%TARGET%" pull origin %BRANCH%
) else (
  echo (GitHub giris penceresi acilirsa hesabinla giris yap)
  git clone --branch %BRANCH% %REPO% "%TARGET%"
)
if errorlevel 1 ( echo HATA: proje indirilemedi. & pause & exit /b 1 )
cd /d "%TARGET%"

echo [4/6] Sanal ortam ve paketler kuruluyor (birkac dakika surebilir)...
if not exist ".venv\Scripts\python.exe" %PY% -m venv .venv
if errorlevel 1 ( echo HATA: venv olusmadi. & pause & exit /b 1 )
".venv\Scripts\python.exe" -m pip install --upgrade pip -q
".venv\Scripts\python.exe" -m pip install -r requirements.txt -q
if errorlevel 1 ( echo HATA: paket kurulumu basarisiz. & pause & exit /b 1 )

echo [5/6] Testler calistiriliyor...
echo ===== TESTLER ===== > "%LOG%"
".venv\Scripts\python.exe" -m pytest -q >> "%LOG%" 2>&1
echo ===== YETENEK MATRISI ===== >> "%LOG%"
".venv\Scripts\python.exe" main.py capabilities >> "%LOG%" 2>&1

echo [6/6] Binance baglanti kontrolu (sadece okuma)...
echo ===== BAGLANTI KONTROLU ===== >> "%LOG%"
".venv\Scripts\python.exe" main.py check >> "%LOG%" 2>&1

echo.
echo ==================================================
echo  BITTI. Sonuc dosyasi masaustunde: sonuc.txt
echo  O dosyayi ac, icindekini kopyalayip bana yapistir.
echo ==================================================
type "%LOG%"
echo.
pause
