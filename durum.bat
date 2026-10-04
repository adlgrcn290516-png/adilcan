@echo off
chcp 65001 >nul
set PYTHONUTF8=1
cd /d "%USERPROFILE%\adilcan"
".venv\Scripts\python.exe" main.py --log-level WARNING status --data-dir data\forward
echo.
if exist data\forward\heartbeat.json (echo Nabiz dosyasi: & type data\forward\heartbeat.json) else echo Nabiz dosyasi yok - kosucu hic calismamis.
echo.
pause
