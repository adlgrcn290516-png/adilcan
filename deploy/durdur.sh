#!/usr/bin/env bash
# Kullanım: bash durdur.sh        -> yeni emir açma (pozisyonlar yönetilmeye devam eder)
#           bash durdur.sh kapat  -> ayrıca tüm (sanal) pozisyonları kapat
D=/home/trader/adilcan/data/forward
mkdir -p $D
if [ "${1:-}" = "kapat" ]; then echo CLOSE > $D/STOP; else echo dur > $D/STOP; fi
chown trader:trader $D/STOP
echo "Acil durdurma verildi."
