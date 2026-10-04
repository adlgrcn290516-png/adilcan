@echo off
chcp 65001 >nul
setlocal
set PYTHONUTF8=1
set PYTHONIOENCODING=utf-8
cd /d "%~dp0.."
".venv\Scripts\python.exe" -m pip install -r requirements.txt -q
echo.
echo ==================================================================
echo  SUREKLI KOSUCU (sanal para, canli veri). KAPATMA: Ctrl+C
echo  Pencereyi acik birak. Cokerse 30 sn sonra kendiliginden yeniden baslar.
echo  Durum icin: durum.bat
echo  Acil durdurmak icin: durdur.bat
echo  Pozisyonlari da kapatmak icin: durdur_kapat.bat
echo ==================================================================
:loop
".venv\Scripts\python.exe" main.py --log-level WARNING --debug run --data-dir data\forward
if %errorlevel%==0 goto done
echo.
echo [UYARI] Kosucu beklenmedik sekilde durdu (kod %errorlevel%). 30 sn sonra yeniden basliyor. Iptal icin Ctrl+C
timeout /t 30 /nobreak >nul
goto loop
:done
echo Kosucu durduruldu.
