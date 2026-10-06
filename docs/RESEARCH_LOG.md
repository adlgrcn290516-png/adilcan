# Araştırma Defteri (tüm denemeler kayıt altında; çoklu-deneme etkisini sayabilmek için)

Kural: Sonuç görülmeden kabul kriteri sabitlenir. Kriter geçilmezse hipotez elenmiş sayılır; sonradan gevşetilmez.

| # | Hipotez | Veri | Sonuç | Karar |
|---|---------|------|-------|-------|
| 1 | AI Composite (6 strateji + skor), 1 saatlik | 15 coin, 1 yıl (2025-10→2026-10) | OOS −%40.6, holdout −%13; brüt beklenti −%0.27/işlem; rastgele girişle fark ≈ 0 | **ELENDİ** |
| 2 | Aynı sistem, 1 saatlik, çok yıl | 15 coin, 4 yıl | OOS −%96, holdout −%49; işlem başı −%0.56 vs rastgele −%0.53 | **ELENDİ** |
| 3 | Aynı sistem, günlük mum | 14 coin, 2019→2026 | 2/4 kriter: OOS PF 1.11 (<1.2), OOS beklenti rastgeleden +0.16 puan (<0.30); holdout +%13 | **KALDI** (hayat belirtisi, kanıt değil) |
| 4 | Aynı sistem, 4 saatlik mum | 15 coin, ~7 yıl | Rapor iletilmedi; aile #1-#3 ile elendi, tekrar gerekmiyor | — |
| 5 | Tek-kural rejim filtresi: BTC/ETH kapanış > SMA200 → tut, değilse nakit | BTC, ETH günlük, 2018-03→2026-10 (8.6 yıl) | 3/6 kriter. BTC: Sharpe 0.77 vs 0.69, MaxDD %64 vs %77 (gereken ≤%46), kaydırma %86 (<90). ETH: Sharpe 0.77 vs 0.58, MaxDD %74 vs %90, kaydırma %92. 2022'de filtre %0, tutmak −%64/−%67 | **KALDI** (2022 koruması gözlendi; yalnızca 2 ayı rejimi = doğrulanamaz) |

Notlar
- Mutlak getiriler, bar-içi kötümser varsayım nedeniyle ölçülen ~−%0.13/işlem sapma taşır; adil ölçüt aynı motorda sinyal-vs-rastgele.
- #3'te risk motorunun ATR%6 limiti 1 saatlik mumlara göre ayarlıydı ve günlükte sinyallerin %63'ünü reddetti (zaman dilimine ölçeklenmemiş);
  düzeltmek yeni bir deneme olacağından yapılmadı.
- Her sembol listesi BUGÜNKÜ hacme göre seçildi (hayatta kalma yanlılığı); tutma kıyasları iyimsercedir.
| 6 | **İLERİ TEST**: AI Composite (1 saatlik) canlı veri + sanal para, 7/24 | gerçek zamanlı, 25 coin | (devam ediyor) — kriter: ≥100 kapanan pozisyon VE getiri>0 VE PF≥1.2 | — |

Sonuç (5 deneme): yön tahmini yapan sistemler elendi; rejim filtresi kriterleri geçmedi. Kanıtlanmış avantaj YOK. Faz 6 (Futures/kaldıraç) yapılmayacak.
Lecture: eşikler öncül bilgiyle kalibre edilmeli; #5'te MaxDD eşiği (%60) iddialıydı — sonradan gevşetilmedi.

Beklenti (dürüst): #6'da stratejinin backtest'te kaybettiği için kaybetmesini bekliyoruz; ileri test, hiç görülmemiş gerçek zamanlı veride bunu doğrulamak/çürütmek için.

| 7 | **Fibonacci geri çekilme + destek + MACD/RSI** (long-only, tek strateji, ayar YOK): EMA50>EMA200 ve eğim>0; bacak >=3 ATR; fiyat %38.2-%61.8 bölgesinde; MACD hist artıyor ve RSI 30-55; stop = swing dibi - 0.25 ATR; hedefler rr1/rr2 | 15 coin, 1 saatlik, 4 yıl (holdout son %20) | **0/4 kriter geçti.** Holdout öncesi (2022-10→2025-12, 1109 işlem): getiri −%70, PF 0.67, işlem başı −%0.64 (brüt −%0.34); rastgele girişten −0.10 puan KÖTÜ; her yıl negatif. Holdout (2025-12→2026-10, 255 işlem): −%22, PF 0.67; aynı dönemde sepeti tutmak +%44.6. Kriterler (sabit): holdout öncesi getiri>0 ve PF>=1.2; işlem>=100; işlem başı beklenti rastgeleden >=+0.30 puan iyi; holdout getiri>0, PF>=1.1, işlem>=20 | **ELENDİ** |

Not (#7): MACD, RSI, EMA ve 48 bar destek/direnç AI Composite içinde zaten vardı; Fibonacci yoktu. Yeni olan: nedensel swing/pivot yapısı (core/swings.py, 5 bar onay gecikmeli) + Fib bölgesi. Bu strateji AI Composite'e OY VERMEZ (canlı/sanal robotlar etkilenmez), bağımsız araştırılır.
