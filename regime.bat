@echo off
rem SABIT BASLATICI: bu dosya degismez. Asil is scripts\run_regime.bat icinde.
setlocal
set "BRANCH=claude/binance-ai-trading-system-z6oo1n"
cd /d "%USERPROFILE%\adilcan"
if errorlevel 1 goto nofolder
echo Son surum indiriliyor...
git pull origin %BRANCH%
call "%USERPROFILE%\adilcan\scripts\run_regime.bat"
pause
exit /b 0
:nofolder
echo Proje klasoru yok. Once kur.bat calistir.
pause
