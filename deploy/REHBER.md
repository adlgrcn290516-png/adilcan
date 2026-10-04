# Sunucuya kurulum rehberi (bilgisayarın kapalıyken de çalışsın)

## 1. Sunucu kirala (VPS)
- Hetzner (Almanya/Finlandiya) veya DigitalOcean/Vultr (Frankfurt, Singapur). **ABD sunucusu seçme** (Binance ABD IP'lerini engelliyor).
- En küçük plan yeter: 1-2 GB RAM, Ubuntu 22.04 veya 24.04, ~4-6 € / ay.
- Kayıt olurken "SSH anahtarı" yerine şifre seçeneği varsa şifre ile de olur; sana IP adresi ve root şifresi verilir.

## 2. Bağlan (Windows)
PowerShell aç:  `ssh root@SUNUCU_IP`  (ilk seferde "yes" yaz, şifreyi gir)

## 3. Tek komutla kur
Repo herkese açıksa:
```
curl -fsSL https://raw.githubusercontent.com/adlgrcn290516-png/adilcan/claude/binance-ai-trading-system-z6oo1n/deploy/install.sh | bash
```
Repo özelse: GitHub > Settings > Developer settings > Fine-grained token (yalnızca bu repo, yalnızca "Contents: Read-only") üret ve:
```
git clone --branch claude/binance-ai-trading-system-z6oo1n https://KULLANICI:TOKEN@github.com/adlgrcn290516-png/adilcan.git /tmp/k && REPO_URL="https://KULLANICI:TOKEN@github.com/adlgrcn290516-png/adilcan.git" bash /tmp/k/deploy/install.sh
```
(Token'ı KİMSEYLE paylaşma, bana da yazma.)

## 4. Günlük kullanım (sunucuda)
- Durum:        `bash /home/trader/adilcan/deploy/durum.sh`
- Acil durdur:  `bash /home/trader/adilcan/deploy/durdur.sh`   (pozisyonları da kapat: `... durdur.sh kapat`)
- Log canlı:    `journalctl -u trading-forward -f`   (çıkmak: Ctrl+C)
- Yeniden başlat: `systemctl restart trading-forward`
- Güncelle:     `bash /home/trader/adilcan/deploy/install.sh`  (tekrar çalıştırmak güvenlidir)

## Güvenlik
- Şu an API anahtarı GEREKMİYOR (sanal para). Sunucuya anahtar koymadan çalışır.
- Gerçek para kilidi kodda kapalı; sunucuda da kapalı kalır.
- Sunucu şifresini güçlü yap; mümkünse SSH anahtarı kullan.
