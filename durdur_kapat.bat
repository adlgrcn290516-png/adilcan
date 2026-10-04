@echo off
cd /d "%USERPROFILE%\adilcan"
if not exist data\forward mkdir data\forward
echo CLOSE> data\forward\STOP
echo ACIL DURDURMA + TUM POZISYONLARI KAPAT verildi (sanal para).
pause
