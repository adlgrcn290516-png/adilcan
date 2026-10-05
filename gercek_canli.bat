@echo off
chcp 65001 >nul
cd /d "%USERPROFILE%\adilcan"
echo ============================================================
echo  GERCEK PARA ROBOTU CANLI AKIS - sadece izler, robota dokunmaz.
echo  Cikmak icin Ctrl+C veya pencereyi kapat.
echo ============================================================
if not exist data\live\runner.log (echo Log dosyasi yok - robot henuz baslamamis. & pause & exit /b)
powershell -NoProfile -Command "Get-Content data\live\runner.log -Tail 25 -Wait -Encoding UTF8"
pause
