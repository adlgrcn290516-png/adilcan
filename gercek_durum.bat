@echo off
chcp 65001 >nul
set PYTHONUTF8=1
cd /d "%USERPROFILE%\adilcan"
".venv\Scripts\python.exe" main.py --log-level WARNING status --data-dir data\live
echo.
if exist data\live\heartbeat.json (echo Nabiz dosyasi: & type data\live\heartbeat.json)
echo.
echo ===== LOGUN SON 40 SATIRI =====
if exist data\live\runner.log powershell -NoProfile -Command "Get-Content data\live\runner.log -Tail 40 -Encoding UTF8"
echo.
pause
