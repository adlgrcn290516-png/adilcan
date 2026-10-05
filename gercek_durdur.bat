@echo off
chcp 65001 >nul
cd /d "%USERPROFILE%\adilcan"
if not exist data\live mkdir data\live
echo CLOSE> data\live\STOP
echo ACIL DURDURMA verildi: yeni alim yok ve acik pozisyonlar kapatilacak.
echo Robot penceresi acik olmali, kapatma emri bir dakika icinde calisir.
pause
