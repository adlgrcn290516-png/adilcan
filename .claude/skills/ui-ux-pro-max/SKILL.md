---
name: "ui-ux-pro-max"
description: "Web sitesi, sayfa, bileşen veya arayüz tasarlarken/düzeltirken/denetlerken kullan: erişilebilirlik, performans, tipografi, renk, hareket, form ve SEO/GEO kuralları + teslim öncesi kontrol listesi."
---

# UI/UX Pro Max — Web Tasarım Zekâsı (Akif / Flutecnica sürümü)

Kaynak: nextlevelbuilder "ui-ux-pro-max" yeteneğinin kural seti (119 UX kuralı, 10 kategori) + Flutecnica için SEO/GEO eki. Bu sürüm arama betiği ve CSV veritabanı olmadan çalışır; kurallar aşağıya gömülüdür. Orijinal paket (search.py + data/) oturuma yüklenirse `python3 scripts/search.py "<sorgu>" --design-system` ve `--domain ux|color|typography|landing` / `--stack laravel` ile ayrıntı sorgula; 0 sonuç dönerse uydurma, bu dosyadaki varsayılanları kullan ve bunu söyle.

## Ne zaman
Sayfa/bileşen oluşturma, yeniden düzenleme, UI incelemesi, renk/tipografi/boşluk sistemi seçimi, animasyon, responsive, form, gezinme. Saf backend işlerinde kullanma.

## İş akışı
1. **Gereksinim**: ürün tipi, kitle, stil anahtar kelimeleri, yığın (composer.json → Laravel Blade; package.json → React/Next vb.). Yığını varsayma.
2. **Tasarım sistemi** (yeni sayfa/proje): desen, stil, renk tokenları, font çifti, efektler, kaçınılacaklar.
3. **Öncelik sırasıyla denetle** (1→10, aşağıdaki tablo).
4. **Tam dosya teslim et** (Akif kısmi yama değil tam dosya ister), sonra teslim öncesi kontrol listesi.

## Flutecnica tasarım sistemi (B2B endüstriyel distribütör)
- Desen: Trust & Authority + Conversion — Hero (misyon/güven) > Kanıt (logolar, sertifikalar, rakamlar) > Çözüm > Net CTA (Teklif Al / İletişim).
- Stil: Accessible & Ethical — yüksek kontrast, 16px+ gövde, klavye, görünür odak, reduced-motion.
- Renk: lacivert/gri kurumsal, güven mavisi, vurgu yalnız CTA'da. Marka: mavi #1763B6/#185FA5, lacivert #0A1A2D, altın #C9A227 (açık zeminde metin olarak #8A6A12). **Mor/menekşe ve pembe yasak.** Kırmızı yalnız logo ve hata durumu.
- Font: Space Grotesk (başlık) + Plus Jakarta Sans (gövde) + JetBrains Mono (sayısal).
- Kaçın: oyuncu tasarım, gizlenmiş kimlik bilgileri, AI mor/pembe gradyanlar, emoji ikon.

## Kural kategorileri (öncelik)
| # | Kategori | Etki | Olmazsa olmaz | Kaçın |
|---|---|---|---|---|
| 1 | Erişilebilirlik | KRİTİK | Kontrast 4.5:1 (büyük metin 3:1), alt metin, klavye, aria-label, skip-link, h1→h6 sıralı | Odak halkasını kaldırmak, etiketsiz ikon buton |
| 2 | Dokunma & Etkileşim | KRİTİK | Min 44×44px (web en az 24px), 8px aralık, yükleme geri bildirimi, cursor:pointer | Yalnız hover'a dayanmak, 0ms durum değişimi |
| 3 | Performans | YÜKSEK | WebP/AVIF, lazy-load, width/height veya aspect-ratio (CLS<0.1), font-display:swap, 3. parti script defer | Layout thrashing, CLS |
| 4 | Stil | YÜKSEK | Ürüne uygun stil, tutarlılık, SVG ikon (tek set) | Emoji ikon, karışık stil |
| 5 | Yerleşim | YÜKSEK | Mobil öncelikli, 375/768/1024/1440, yatay kaydırma yok, dvh/svh, scroll-padding (sticky header) | Zoom kapatma, sabit px genişlik |
| 6 | Tipografi & Renk | ORTA | Gövde 16px, line-height 1.5–1.75, 65–75 karakter satır, semantik tokenlar, tabular-nums | <12px metin, gri-üstü-gri, bileşende ham hex |
| 7 | Animasyon | ORTA | Yalnız transform/opacity, 150–300ms, prefers-reduced-motion, anlamlı hareket, çıkış girişten kısa | width/height animasyonu, sonsuz hareket kontrolsüz |
| 8 | Form | ORTA | Görünür label, hata alanın altında + aria-describedby, role=alert, blur'da doğrulama, doğru input type | Yalnız placeholder etiket, hatayı yalnız üstte göstermek |
| 9 | Gezinme | YÜKSEK | Öngörülebilir geri, derin bağlantı (URL state), aktif konum vurgusu, breadcrumb (3+ seviye) | Modal ile birincil akış |
| 10 | Grafik & Veri | DÜŞÜK | Lejant, tablo alternatifi, erişilebilir renk, ekran okuyucu özeti | Bilgiyi yalnız renkle vermek |

## Kritik ayrıntılar
- Otomatik dönen içerik (carousel, kayan şerit, arka plan video): **duraklat/oynat düğmesi**, hover/focus'ta dur, reduced-motion'da statik (WCAG 2.2.2). reduced-motion'da `animation-duration:.01ms` + `infinite` titretir → `animation:none` kullan.
- `display:flex/grid` veren sınıf `[hidden]`'ı ezer → `[hidden]{display:none!important}` ekle.
- `.reveal{opacity:0}` yalnız `html.js` varken uygulanmalı; JS yoksa içerik görünür kalsın.
- `<a role="listitem">` bağlantı semantiğini bozar → `ul/li` kullan.
- Modal: native `<dialog>` + showModal (odak tuzağı, Esc), kapanınca odağı tetikleyiciye döndür.
- Inline `onclick` yerine data-* + olay delegasyonu; kullanıcı girdisini innerHTML'e değil textContent'e yaz.
- Blade `@php` içinde `function` tanımlama ("Cannot redeclare" riski) → closure kullan.
- LCP görseli: `<img fetchpriority=high>` + `<link rel=preload as=image>`; video sadece masaüstü, reduced-motion kapalı ve saveData yoksa.
- Sayısal veriler için `Intl.NumberFormat('tr-TR')`, tabular-nums.
- Dokunmatik: `@media(pointer:coarse)` ile küçük düğmeleri 44px'e çıkar.

## SEO + GEO eki (Laravel Blade)
- Sayfa verisi (SSS, tablolar, kartlar) tek bir `@php` dizisinde tutulur; hem HTML hem JSON-LD bu diziden üretilir → görünür içerik ile şema daima eşleşir.
- JSON-LD'yi PHP dizisinden `json_encode(..., JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG)` ile bas.
- Sayfa başına **tek FAQPage**; tüm sorular sayfada görünür olmalı. Görünmeyen içerik şemaya girmez.
- Organization/LocalBusiness layout'ta bir kez; sayfalar `@id` ile referans verir.
- Teklifsiz/fiyatsız ürün kartlarında `itemscope Product` microdata kullanma (Search Console hatası) → ItemList JSON-LD.
- WebPage: `dateModified`, `speakable`, `breadcrumb`, `mainEntity`, `hasPart`; araçlar için `WebApplication` (isAccessibleForFree, featureList).
- GEO (yapay zekâ motorları): üstte **cevap-önce** varlık tanımı paragrafı ("X, Y'nin Türkiye yetkili distribütörüdür..."), `<dl>` ile net olgular (çap aralığı, teslimat, şube, min sipariş), HTML karşılaştırma **tablosu** (`caption`, `th scope`), "Kısa cevap:" ile başlayan açıklamalar, görünür "Son güncelleme" `<time>`.
- Olgu uydurma: yalnız kullanıcının/sitenin zaten beyan ettiği değerleri kullan; bilinmeyen hücreye "—".
- Meta keywords etkisizdir (zararsız). SearchAction sitelinks kutusu ve HowTo zengin sonuçları Google'da kaldırıldı; FAQ zengin sonucu yalnız resmi/sağlık sitelerinde — şemalar yine de AI motorları için değerli.
- Ek öneriler: `llms.txt`, sitemap'te `lastmod`, sayfa başına tek h1.

## Teslim öncesi kontrol listesi
- [ ] Emoji ikon yok (SVG, tek set)
- [ ] Tüm tıklanabilirlerde cursor:pointer ve 150–300ms hover geçişi
- [ ] Metin kontrastı ≥4.5:1 (altın/açık gri metinleri kontrol et)
- [ ] Görünür odak halkası, skip-link, klavye ile tüm akış
- [ ] prefers-reduced-motion: sonsuz animasyonlar durur, otomatik video yok
- [ ] Otomatik hareketlerde duraklat düğmesi
- [ ] 375 / 768 / 1024 / 1440'ta yatay kaydırma yok
- [ ] Görsellerde width/height veya aspect-ratio; ekran altı lazy
- [ ] Min metin 12px, gövde 15–16px
- [ ] Hata mesajları alanın yanında, role=alert, neden + çözüm
- [ ] JSON-LD geçerli, görünür içerikle eşleşiyor, tek FAQPage
- [ ] PHP `-l`, JS `node --check` ile sözdizimi doğrulandı