@echo off
chcp 65001 >nul
cd /d "%USERPROFILE%\adilcan"
echo ============================================================
echo  CANLI AKIS - robotun yaptiklari burada aninda gorunur.
echo  Yeni satir yoksa robot beklemededir, normaldir.
echo  Taramalar saat basi, pozisyon yonetimi her dakika.
echo  Cikmak icin: Ctrl+C veya pencereyi kapat. Robot etkilenmez.
echo ============================================================
if not exist data\forward\runner.log (echo Log dosyasi yok - once forward.bat calismali. & pause & exit /b)
powershell -NoProfile -Command "Get-Content data\forward\runner.log -Tail 25 -Wait -Encoding UTF8"
pause
