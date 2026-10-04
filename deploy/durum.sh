#!/usr/bin/env bash
D=/home/trader/adilcan
sudo -u trader bash -c "cd $D && .venv/bin/python main.py --log-level WARNING status --data-dir data/forward"
echo; echo "=== SERVIS ==="; systemctl is-active trading-forward
echo; echo "=== NABIZ ==="; cat $D/data/forward/heartbeat.json 2>/dev/null || echo "yok"
echo; echo "=== LOGUN SON 40 SATIRI ==="; tail -n 40 $D/data/forward/runner.log 2>/dev/null
