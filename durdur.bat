@echo off
cd /d "%USERPROFILE%\adilcan"
if not exist data\forward mkdir data\forward
echo dur> data\forward\STOP
echo ACIL DURDURMA verildi: yeni emir acilmaz, mevcut pozisyonlar yonetilmeye devam eder.
echo (Kosucu penceresinde 'STOP dosyasi bulundu' yazisini gorursun.)
pause
