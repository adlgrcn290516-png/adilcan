#!/usr/bin/env bash
# Ubuntu/Debian sunucuda kurulum (root olarak çalıştır):  bash install.sh
# Özel (private) repo ise:  REPO_URL="https://<KULLANICI>:<TOKEN>@github.com/adlgrcn290516-png/adilcan.git" bash install.sh
set -euo pipefail
REPO_URL="${REPO_URL:-https://github.com/adlgrcn290516-png/adilcan.git}"
BRANCH="${BRANCH:-claude/binance-ai-trading-system-z6oo1n}"
APP_USER="trader"
APP_DIR="/home/${APP_USER}/adilcan"

echo "[1/6] Paketler..."
apt-get update -y
apt-get install -y python3 python3-venv python3-pip git ca-certificates

echo "[2/6] Kullanıcı (root DEĞİL, yetkisiz)..."
id -u "$APP_USER" >/dev/null 2>&1 || adduser --disabled-password --gecos "" "$APP_USER"

echo "[3/6] Kod indiriliyor..."
if [ -d "$APP_DIR/.git" ]; then
  sudo -u "$APP_USER" git -C "$APP_DIR" fetch origin "$BRANCH"
  sudo -u "$APP_USER" git -C "$APP_DIR" checkout "$BRANCH"
  sudo -u "$APP_USER" git -C "$APP_DIR" pull origin "$BRANCH"
else
  sudo -u "$APP_USER" git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
fi
# token'ı git config'te bırakma
sudo -u "$APP_USER" git -C "$APP_DIR" remote set-url origin "https://github.com/adlgrcn290516-png/adilcan.git"

echo "[4/6] Python ortamı..."
sudo -u "$APP_USER" bash -c "cd '$APP_DIR' && python3 -m venv .venv && .venv/bin/pip install -q --upgrade pip && .venv/bin/pip install -q -r requirements.txt"

echo "[5/6] Hızlı kontrol (testler)..."
sudo -u "$APP_USER" bash -c "cd '$APP_DIR' && .venv/bin/python -m pytest -q 2>&1 | tail -2"

echo "[6/6] Servis (sunucu açılınca otomatik başlar, çökerse yeniden başlar)..."
cat > /etc/systemd/system/trading-forward.service <<UNIT
[Unit]
Description=Trading forward test (PAPER: sanal para, canli veri)
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=${APP_USER}
WorkingDirectory=${APP_DIR}
Environment=PYTHONUTF8=1
ExecStart=${APP_DIR}/.venv/bin/python main.py --log-level WARNING run --data-dir data/forward
Restart=always
RestartSec=30

[Install]
WantedBy=multi-user.target
UNIT
systemctl daemon-reload
systemctl enable --now trading-forward
sleep 5
systemctl --no-pager --lines=5 status trading-forward || true
echo
echo "KURULDU. Durum: bash ${APP_DIR}/deploy/durum.sh   |   Acil durdur: bash ${APP_DIR}/deploy/durdur.sh"
