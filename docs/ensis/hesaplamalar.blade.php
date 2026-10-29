{{--
|--------------------------------------------------------------------------
| Ensis Pnömatik — Teknik Hesaplamalar (resources/views/hesaplamalar.blade.php)  v1.4 — kurumsal düzen: yan menü, teknik çizim, başvuru tabloları, sonucu kopyala
|--------------------------------------------------------------------------
| Adres: https://ensispnomatik.com/hesaplamalar
| v1.3: 404 düzeltmesi — sayfa artık routes/web.php içindeki  Route::view('/hesaplamalar', 'hesaplamalar')  ile açılır
|       (catch-all /{slug?} rotasından ÖNCE tanımlı olmalı; SiteController'a elseif eklemeye gerek yok)
|       · JSON-LD ve script, master'da @stack('schema') / @stack('scripts') bulunmasa da çalışsın diye @push yerine içerik bölümüne alındı
|       · @json yerine json_encode (Laravel 5.6 ile her sürümde uyumlu)
|
| 0) Muadil ürün kodu bulucu (Festo/SMC/Airtac/Camozzi/Norgren/Parker → tip, ISO, çap, strok, Ensis grubu)
| 6 hesaplayıcı (hepsi tarayıcıda çalışır, sunucuya yük bindirmez):
|  1) Pnömatik silindir kuvveti      4) Hidrolik silindir kuvvet & hız
|  2) Silindir çapı seçimi           5) Piston rodu burkulma (Euler)
|  3) Hava tüketimi & kompresör      6) Basınç / kuvvet birim çevirici
| SEO/GEO: her hesaplayıcının kendi bağlantısı (#kuvvet, #cap-secimi ...), formüller düz metin,
|          WebApplication ItemList + FAQPage şeması, BreadcrumbList (master).
| Mobil/tablet: tek sütun, kaydırılabilir sekme şeridi, 44px dokunma alanları, sayısal klavye.
--}}
@extends('layouts.master')

@section('modern', '1')

@php
    $base  = 'https://ensispnomatik.com';
    $url   = $base . '/hesaplamalar';
    $quote = url('iletisim');
    $wa    = preg_replace('/\D+/', '', (string) (config('setting.company_whatsapp') ?: '905545921544'));

    // ISO 15552 / 21287 / 6432 — silindir çapı => standart rot çapı (mm)
    $bores = [8 => 4, 10 => 4, 12 => 6, 16 => 6, 20 => 8, 25 => 10, 32 => 12, 40 => 16, 50 => 20, 63 => 20, 80 => 25, 100 => 25, 125 => 32, 160 => 40, 200 => 40, 250 => 50, 320 => 63];

    $calcs = [
        ['muadil',     'Muadil kod',        'Muadil ürün kodu bulucu',            'Festo, SMC, Airtac, Camozzi, Norgren, Parker kodlarını çözer; ürün tipini, ISO standardını, çap ve stroku bulup muadil grubu gösterir.'],
        ['kuvvet',     'Silindir kuvveti',  'Pnömatik silindir kuvvet hesaplama', 'Silindir çapı, rot çapı ve basınca göre itme ve çekme kuvvetini N ve kgf olarak hesaplar.'],
        ['cap-secimi', 'Çap seçimi',        'Pnömatik silindir çapı seçimi',      'Taşınacak yük, basınç ve hıza göre gereken minimum silindir çapını ve önerilen ISO çapını bulur.'],
        ['hava',       'Hava tüketimi',     'Hava tüketimi ve kompresör hesabı',  'Silindirlerin dakikadaki serbest hava tüketimini (Nl/dk) ve gereken kompresör kapasitesini hesaplar.'],
        ['hidrolik',   'Hidrolik silindir', 'Hidrolik silindir kuvvet ve hız hesabı', 'Basınç ve pompa debisine göre hidrolik silindirin kuvvetini, ileri/geri hızını ve strok süresini hesaplar.'],
        ['burkulma',   'Rot burkulma',      'Piston rodu burkulma hesabı (Euler)', 'Rot çapı, boy ve bağlantı şekline göre kritik burkulma yükünü, emniyetli yükü ve minimum rot çapını hesaplar.'],
        ['birim',      'Birim çevirici',    'Basınç ve kuvvet birim çevirici',    'bar, MPa, psi, kPa, kgf/cm² ve N, kN, kgf, lbf birimleri arasında anında çeviri yapar.'],
    ];

    // Muadil bulucu: ürün tipi => [Ensis ürün grubu adresi, adı, temsili görsel (uploads/category)]
    // Görseli olmayan grup için null; görsel yüklenemezse çizim gösterilir.
    $muadilCats = [
        'iso'     => [url('category/iso-silindirler'), 'ISO Pnömatik Silindirler', asset('uploads/category/335-1713903999.jpg')],
        'mini'    => [url('category/hava-silindirleri'), 'Hava Silindirleri', null],
        'inox'    => [url('category/paslanmaz-celik-silindirler'), 'Paslanmaz Çelik Silindirler', asset('uploads/category/6-1711358253.jpg')],
        'comp'    => [url('category/standart-tabanli-kompakt-silindirler'), 'Kompakt Silindirler', asset('uploads/category/4-1711357041.jpg')],
        'guide'   => [url('category/kilavuzlu-silindirler'), 'Kılavuzlu Silindirler', asset('uploads/category/11-1711367368.jpg')],
        'rodless' => [url('category/pnomatik-rotsuz-silindirler'), 'Rotsuz Silindirler', asset('uploads/category/334-1713901329.jpg')],
        'rotary'  => [url('category/pnomatik-doner-aktuatorler'), 'Döner Aktüatörler', asset('uploads/category/338-1713906843.jpg')],
        'grip'    => [url('category/tutucular-pnomatik-silindirler'), 'Pnömatik Tutucular', asset('uploads/category/339-1713909198.jpg')],
        'valve'   => [url('category/yon-kontrol-vanalari'), 'Yön Kontrol Valfleri', asset('uploads/category/142-1712151424.jpg')],
        'frl'     => [url('category/hava-aritma-unitesi'), 'Hava Hazırlama Üniteleri', asset('uploads/category/168-1712223461.jpg')],
        'vac'     => [url('category/vakum-bilesenleri'), 'Vakum Bileşenleri', asset('uploads/category/275-1712925865.jpg')],
        'fit'     => [url('category/pisco-baglanti-parcalari'), 'Bağlantı Parçaları', asset('uploads/category/345-1713912093.jpg')],
    ];

    $faq = [
        ['Festo veya SMC silindirin muadilini nasıl bulurum?',
         'Ürün etiketindeki kodu muadil bulucuya yazın. Kodun içindeki seri adından ürün tipi ve ISO standardı, rakamlardan çap ve strok okunur. Örneğin DSBC-50-200 Festo\'nun ISO 15552 silindiridir, Ø50 çap ve 200 mm stroktur. ISO 15552, ISO 6432 ve ISO 21287 silindirlerin bağlantı ölçüleri üreticiden bağımsız olduğu için aynı çap ve stroktaki muadil silindir birebir yerine takılır.'],
        ['Pnömatik silindir kuvveti nasıl hesaplanır?',
         'Kuvvet = basınç × piston alanıdır: F (N) = P (bar) × 0,1 × π × D² / 4. Çekme yönünde rot alanı düşülür: π × (D² − d²) / 4. Örneğin 6 bar\'da Ø50 silindir teorik olarak 1178 N (≈120 kgf) iter, Ø20 rotla yaklaşık 990 N çeker. Sürtünme kayıpları için sonucu yaklaşık %85 ile çarpmak gerekir.'],
        ['Silindir çapı seçerken yük oranı neden önemlidir?',
         'Yük oranı, gereken kuvvetin silindirin teorik kuvvetine oranıdır. Yavaş hareketlerde %70, normal hızlarda %50, 500 mm/s üzerindeki hızlı hareketlerde %30–40 yük oranı önerilir. Böylece silindir hızlanma, sürtünme ve basınç düşüşlerini karşılayacak kuvvet payına sahip olur.'],
        ['Pnömatik silindirin hava tüketimi nasıl hesaplanır?',
         'Bir çift strok için tüketim: Q = (A_itme + A_çekme) × strok × (P + 1,013) / 1,013. Sonuç atmosfer basıncındaki serbest hava (Nl) cinsindendir ve dakikadaki çevrim sayısıyla çarpılır. Kompresör seçerken kaçaklar ve rezerv için toplam tüketime %30 pay eklenmesi önerilir.'],
        ['Hidrolik silindir hızı nasıl hesaplanır?',
         'Hız = debi / piston alanıdır: v (mm/s) = Q (L/dk) × 16 667 / A (mm²). Geri dönüşte alan küçüldüğü için silindir daha hızlı geri gelir. Örneğin 20 L/dk debiyle Ø63 silindir yaklaşık 107 mm/s hızla ilerler.'],
        ['Piston rodunun burkulması nasıl kontrol edilir?',
         'Uzun stroklu ve itme yükü taşıyan silindirlerde rod Euler formülüyle kontrol edilir: Fk = π² × E × I / Lk². Burada I = π × d⁴ / 64, Lk ise bağlantı şekline göre serbest burkulma boyudur. Emniyetli yük için kritik yük genellikle 3,5 emniyet katsayısına bölünür.'],
        ['1 bar kaç psi ve kaç MPa eder?',
         '1 bar = 14,504 psi = 0,1 MPa = 100 kPa = 1,0197 kgf/cm². Pnömatik sistemlerde tipik işletme basıncı 6 bar, yani yaklaşık 87 psi veya 0,6 MPa\'dır.'],
    ];

    $updated = '2026-10-19';
    $tel = '0224 443 54 20'; $telIntl = '+902244435420';

    // ---- Hızlı başvuru tabloları (sunucuda hesaplanır; arama motorları ve yapay zekâ okuyabilir) ----
    $area = function ($D, $d = 0) { return M_PI * ($D * $D - $d * $d) / 4; };
    $fmt  = function ($n, $dec = 0) { return number_format($n, $dec, ',', '.'); };
    $forceBores = [12, 16, 20, 25, 32, 40, 50, 63, 80, 100, 125, 160, 200];
    $forceP     = [4, 6, 8];
    $airBores   = [16, 20, 25, 32, 40, 50, 63, 80, 100, 125];
    $hydBores   = [32, 40, 50, 63, 80, 100, 125, 160];
    $hydP       = [100, 160, 210];
    $pressRows  = [1, 2, 4, 6, 7, 8, 10, 16, 100, 160, 210, 250];

    // ---- Seri karşılık tablosu (muadil) ----
    $series = [
        ['ISO silindir',        'ISO 15552',  'DSBC, DNC',  'CP96, C96',  'SI, SC',   '60, 61', 'RA, RT', 'P1D', 'iso'],
        ['Mini yuvarlak',       'ISO 6432',   'DSNU, ESNU', 'C85',        'MI, MAL',  '24, 25', 'RM',     'P1A', 'mini'],
        ['Kompakt',             'ISO 21287',  'ADN, AEN',   'C55, CQ2',   'ACQ, SDA', '31',     '—',      '—',   'comp'],
        ['Kılavuzlu',           'Üretici',    'DFM',        'MGP',        'TCL, TCM', '—',      '—',      '—',   'guide'],
        ['Rotsuz',              'Üretici',    'DGC',        'MY1, CY3',   'RMS',      '—',      '—',      '—',   'rodless'],
        ['Tutucu (gripper)',    'Üretici',    'DHPS, HGP',  'MHZ2',       'HFZ',      '—',      '—',      '—',   'grip'],
        ['Solenoid valf',       '—',          'VUVG',       'SY, VF',     '4V',       '—',      '—',      '—',   'valve'],
        ['Şartlandırıcı (FRL)', '—',          'MS',         'AW, AC',     'GFR, GC',  '—',      '—',      '—',   'frl'],
    ];

    $brandsX = ['Festo', 'SMC', 'Airtac', 'Camozzi', 'Norgren', 'Parker'];
    // Rehberdeki seri düğmelerine basınca bulucuya yazılacak örnek kodlar
    $exCodes = [
        'DSBC' => 'DSBC-50-200-PPVA-N3', 'DNC' => 'DNC-63-250-PPV-A', 'CP96' => 'CP96SDB50-200', 'C96' => 'C96SDB50-200',
        'SI' => 'SI50X200', 'SC' => 'SC50X200', '60' => '60M2L050A0200', '61' => '61M2P050A0200', 'RA' => 'RA/8050/M/200', 'RT' => 'RT/',
        'P1D' => 'P1D-B050MS-0200', 'DSNU' => 'DSNU-25-100-PPV-A', 'ESNU' => 'ESNU-20-50-P-A', 'C85' => 'C85N25-100',
        'MI' => 'MI25X100', 'MAL' => 'MAL25X100', '24' => '24N2A25A100', '25' => '25N2A25A100', 'RM' => 'RM/8025/M/100', 'P1A' => 'P1A',
        'ADN' => 'ADN-32-50-A-P-A', 'AEN' => 'AEN-32-25-A-P-A', 'C55' => 'C55B32-50', 'CQ2' => 'CQ2B32-50D', 'ACQ' => 'ACQ32X50',
        'SDA' => 'SDA32X50', '31' => '31M2A032A0050', 'DFM' => 'DFM-25-100-P-A-GF', 'MGP' => 'MGPM20-50', 'TCL' => 'TCL20X50',
        'TCM' => 'TCM20X50', 'DGC' => 'DGC-25-500-KF-PPV-A', 'MY1' => 'MY1B25-300', 'CY3' => 'CY3B25-300', 'RMS' => 'RMS25X300',
        'DHPS' => 'DHPS-20-A', 'HGP' => 'HGP-16-A', 'MHZ2' => 'MHZ2-20D', 'HFZ' => 'HFZ20', 'VUVG' => 'VUVG-L14-M52-MT-M7-1P3',
        'SY' => 'SY5120-5DZ', 'VF' => 'VF3130-5DZ', '4V' => '4V210-08', 'MS' => 'MS6-LFR-1/2', 'AW' => 'AW30-03', 'AC' => 'AC30-03',
        'GFR' => 'GFR300-10', 'GC' => 'GC300-10',
    ];

    $breadcrumbs = [['Ana Sayfa', url('/')], ['Hesaplamalar', $url]];

    $schema = ['@context' => 'https://schema.org', '@graph' => [
        [
            '@type' => 'WebPage',
            '@id' => $url . '#webpage',
            'url' => $url,
            'name' => 'Pnömatik ve Hidrolik Hesaplamalar',
            'description' => 'Festo, SMC ve Airtac muadil ürün kodu bulucu; pnömatik silindir kuvveti, çap seçimi, hava tüketimi, hidrolik silindir hızı, rot burkulma ve birim çevirici — ücretsiz online mühendislik hesaplayıcıları.',
            'inLanguage' => 'tr-TR',
            'isPartOf' => ['@id' => $base . '/#website'],
            'publisher' => ['@id' => $base . '/#organization'],
            'mainEntity' => ['@id' => $url . '#araclar'],
            'dateModified' => $updated,
            'author' => ['@id' => $base . '/#organization'],
            'about' => [
                ['@type' => 'Thing', 'name' => 'Pnömatik silindir'],
                ['@type' => 'Thing', 'name' => 'Hidrolik silindir'],
                ['@type' => 'Thing', 'name' => 'ISO 15552'],
                ['@type' => 'Thing', 'name' => 'Basınçlı hava tüketimi'],
            ],
            'speakable' => ['@type' => 'SpeakableSpecification', 'cssSelector' => ['.hc-hero h1', '.hc-hero .lead', '.hc-faq summary']],
        ],
        [
            '@type' => 'ItemList',
            '@id' => $url . '#araclar',
            'name' => 'Teknik hesaplayıcılar',
            'itemListElement' => array_map(function ($c, $i) use ($url, $base) {
                return ['@type' => 'ListItem', 'position' => $i + 1, 'item' => [
                    '@type' => 'WebApplication',
                    '@id' => $url . '#' . $c[0],
                    'name' => $c[2],
                    'description' => $c[3],
                    'url' => $url . '#' . $c[0],
                    'applicationCategory' => 'UtilitiesApplication',
                    'operatingSystem' => 'Web',
                    'inLanguage' => 'tr-TR',
                    'isAccessibleForFree' => true,
                    'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'TRY'],
                    'provider' => ['@id' => $base . '/#organization'],
                ]];
            }, $calcs, array_keys($calcs)),
        ],
        [
            '@type' => 'FAQPage',
            '@id' => $url . '#sss',
            'mainEntity' => array_map(function ($f) {
                return ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]];
            }, $faq),
        ],
    ]];

    $icArr = '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4"/></svg>';
@endphp

@section('title', 'Pnömatik Hesaplamalar & Muadil Kod Bulucu — Silindir Kuvveti, Hava Tüketimi | Ensis')
@section('description', 'Festo, SMC, Airtac muadil kod bulucu ve ücretsiz hesaplayıcılar: silindir kuvveti, çap seçimi, hava tüketimi, hidrolik silindir hızı, rot burkulma ve bar–psi birim çevirici.')
@section('keywords', 'festo muadili, smc muadili, airtac muadili, pnömatik silindir muadil, pnömatik silindir kuvvet hesaplama, silindir çapı hesaplama, hava tüketimi hesaplama, hidrolik silindir hız hesabı, rot burkulma hesabı, bar psi çevirici')
@section('canonical', $url)

@section('content')
{{-- Yapısal veri (master'da @stack('schema') olmasa da basılır) --}}
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
<style>
.hc-hero{position:relative;overflow:hidden;isolation:isolate;padding:clamp(28px,4.5vw,56px) 0 clamp(28px,4vw,48px);background:linear-gradient(180deg,#f3f8ff 0%,#fff 100%);border-bottom:1px solid var(--line)}
.hc-hero::after{content:"";position:absolute;inset:0;z-index:-1;background-image:linear-gradient(rgba(10,60,140,.05) 1px,transparent 1px),linear-gradient(90deg,rgba(10,60,140,.05) 1px,transparent 1px);background-size:48px 48px;-webkit-mask-image:radial-gradient(70% 80% at 75% 30%,#000 20%,transparent 75%);mask-image:radial-gradient(70% 80% at 75% 30%,#000 20%,transparent 75%)}
.hc-hero-grid{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(0,.95fr);gap:clamp(24px,4vw,56px);align-items:center}
.bc{display:flex;flex-wrap:wrap;gap:6px;margin:0 0 18px;padding:0;list-style:none;font-size:13.5px;color:var(--dim)}
.bc li+li::before{content:"/";margin-right:6px;color:var(--line-2)}
.bc a{color:var(--muted)}.bc a:hover{color:var(--accent)}
.hc-hero h1{font-size:clamp(30px,4.2vw,50px);line-height:1.08;letter-spacing:-.03em;font-weight:800;margin-top:14px}
.hc-hero .lead{margin-top:16px;max-width:58ch}
.hc-trust{list-style:none;margin:22px 0 0;padding:0;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px 18px}
.hc-trust li{display:flex;gap:10px;align-items:flex-start;font-size:14.5px;line-height:1.4;color:var(--text);font-weight:600}
.hc-trust svg{width:20px;height:20px;flex-shrink:0;color:var(--accent);margin-top:1px}
.hc-trust small{display:block;font-weight:500;color:var(--muted);font-size:13px}
.hc-hero-acts{display:flex;flex-wrap:wrap;gap:10px;margin-top:24px}
.hc-draw{position:relative;border:1px solid var(--line);border-radius:var(--radius-lg);background:#fff;padding:clamp(14px,2vw,22px);box-shadow:var(--shadow-lg)}
.hc-draw::before{content:"";position:absolute;inset:0 0 auto;height:4px;border-radius:var(--radius-lg) var(--radius-lg) 0 0;background:var(--grad)}
.hc-draw svg{display:block;width:100%;height:auto}
.hc-draw-cap{display:flex;flex-wrap:wrap;justify-content:space-between;gap:6px 14px;margin-top:10px;padding-top:12px;border-top:1px dashed var(--line);font:600 12.5px/1.4 "JetBrains Mono",monospace;color:var(--muted)}
.hc-draw-cap b{color:var(--accent)}

.hc-layout{display:grid;grid-template-columns:260px minmax(0,1fr);gap:clamp(28px,3.5vw,48px);align-items:start;padding-top:clamp(20px,3vw,36px);padding-bottom:clamp(36px,5vw,64px)}
.hc-side{position:sticky;top:calc(var(--hdr) + 20px);display:grid;gap:14px;max-height:calc(100vh - var(--hdr) - 40px);overflow:auto;scrollbar-width:thin}
.hc-side-box{border:1px solid var(--line);border-radius:var(--radius);background:#fff;padding:14px;box-shadow:var(--shadow-sm)}
.hc-side-box>b{display:block;margin:2px 6px 10px;font:700 11.5px/1 "JetBrains Mono",monospace;color:var(--accent);text-transform:uppercase;letter-spacing:.08em}
.hc-side ol{list-style:none;margin:0;padding:0;display:grid;gap:2px}
.hc-side ol a{display:flex;align-items:center;gap:10px;min-height:40px;padding:6px 8px;border-radius:10px;font-size:14px;font-weight:600;color:var(--muted);transition:background .15s,color .15s}
.hc-side ol a i{flex-shrink:0;width:26px;height:26px;border-radius:8px;display:grid;place-items:center;background:var(--bg-2);font:700 11px/1 "JetBrains Mono",monospace;font-style:normal;color:var(--dim);transition:all .15s}
.hc-side ol a:hover{background:var(--bg-2);color:var(--text)}
.hc-side ol a.on{background:var(--accent-soft);color:var(--accent)}
.hc-side ol a.on i{background:var(--accent);color:#fff}
.hc-help{background:linear-gradient(135deg,#0a3d91,#0a6cff 60%,#00b4d8);border:0;color:#fff}
.hc-help>b{color:#cfe3ff!important}
.hc-help p{margin:0 6px 12px;font-size:13.5px;line-height:1.5;color:rgba(255,255,255,.9)}
.hc-help a{display:flex;align-items:center;justify-content:center;gap:8px;min-height:44px;margin-top:8px;border-radius:12px;font-weight:700;font-size:14px}
.hc-help a.w{background:#fff;color:var(--accent)}
.hc-help a.g{border:1px solid rgba(255,255,255,.5);color:#fff}
.hc-help a.g:hover{background:rgba(255,255,255,.12)}

.hc-tabs{display:none;position:sticky;top:var(--hdr);z-index:20;background:rgba(255,255,255,.94);-webkit-backdrop-filter:saturate(1.4) blur(10px);backdrop-filter:saturate(1.4) blur(10px);border-bottom:1px solid var(--line)}
.hc-tabs ul{display:flex;gap:6px;margin:0 auto;padding:10px var(--gutter);list-style:none;max-width:var(--max);overflow-x:auto;scrollbar-width:none;-webkit-overflow-scrolling:touch}
.hc-tabs ul::-webkit-scrollbar{display:none}
.hc-tabs a{display:flex;align-items:center;min-height:40px;padding:0 14px;border:1px solid var(--line);border-radius:999px;background:#fff;white-space:nowrap;font-size:14px;font-weight:600;color:var(--muted);transition:all .2s}
.hc-tabs a:hover{border-color:var(--accent);color:var(--accent)}
.hc-tabs a.on{background:var(--accent);border-color:var(--accent);color:#fff}

.hc-sec{padding:clamp(28px,3.5vw,44px) 0;border-top:1px solid var(--line);scroll-margin-top:calc(var(--hdr) + 16px);content-visibility:visible}
.hc-main>.hc-sec:first-child{border-top:0;padding-top:0}
.hc-ref{margin-top:16px}
.hc-ref h3{font-size:16px;margin:0 0 4px}
.hc-ref p{margin:0 0 10px;color:var(--muted);font-size:14px}
.hc-rt{overflow-x:auto;-webkit-overflow-scrolling:touch;border:1px solid var(--line);border-radius:14px;background:#fff}
.hc-rt table{width:100%;min-width:480px;border-collapse:collapse;font-size:14px}
.hc-rt caption{caption-side:bottom;text-align:left;padding:8px 12px;font-size:12.5px;color:var(--dim)}
.hc-rt th{background:#0b1b2e;color:#fff;font-weight:600;text-align:right;padding:10px 12px;white-space:nowrap}
.hc-rt th:first-child,.hc-rt td:first-child{text-align:left}
.hc-rt td{padding:9px 12px;border-top:1px solid var(--line);text-align:right;font-family:"JetBrains Mono",monospace;font-size:13.5px;white-space:nowrap}
.hc-rt td:first-child{font-family:inherit;font-weight:700;color:var(--text)}
.hc-rt tbody tr:nth-child(even) td{background:#f8fbff}
.hc-rt tbody tr:hover td{background:var(--accent-soft)}
.hc-rt.txt td{font-family:inherit;text-align:left;white-space:nowrap}
.hc-rt.txt th{text-align:left}
.hc-rt a{color:var(--accent);font-weight:600}
.hc-xr{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.hc-xc{display:flex;flex-direction:column;border:1px solid var(--line);border-radius:var(--radius);background:#fff;box-shadow:var(--shadow-sm);overflow:hidden;transition:border-color .2s,box-shadow .2s}
.hc-xc:hover{border-color:rgba(10,108,255,.35);box-shadow:var(--shadow)}
.hc-xc header{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 16px 10px}
.hc-xc h4{margin:0;font-size:16px;color:var(--text)}
.hc-xc header span{flex-shrink:0;padding:5px 9px;border-radius:999px;background:var(--bg-2);border:1px solid var(--line);font:700 11px/1 "JetBrains Mono",monospace;color:var(--muted);white-space:nowrap}
.hc-xc header span.iso{background:var(--accent-soft);border-color:rgba(10,108,255,.25);color:var(--accent)}
.hc-xc dl{margin:0;padding:0 16px 12px;display:grid;gap:6px;flex:1;align-content:start}
.hc-xc dl div{display:grid;grid-template-columns:76px minmax(0,1fr);align-items:center;gap:10px;padding-top:6px;border-top:1px dashed var(--line)}
.hc-xc dl div:first-child{border-top:0;padding-top:0}
.hc-xc dt{font-size:13px;font-weight:600;color:var(--dim)}
.hc-xc dd{margin:0;display:flex;flex-wrap:wrap;gap:6px}
.hc-xc dd button{min-height:32px;padding:0 10px;border:1px solid var(--line-2);border-radius:8px;background:#fff;font:700 12.5px/1 "JetBrains Mono",monospace;color:var(--text);cursor:pointer;transition:all .15s}
.hc-xc dd button:hover{border-color:var(--accent);color:var(--accent);background:var(--accent-soft)}
.hc-xc-go{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:auto;padding:12px 16px;border-top:1px solid var(--line);background:var(--bg-2);font-size:14px;font-weight:700;color:var(--accent);transition:background .2s}
.hc-xc-go small{display:block;font-size:11px;font-weight:600;color:var(--dim);text-transform:uppercase;letter-spacing:.06em;margin-right:auto;width:100%}
.hc-xc-go{flex-wrap:wrap;row-gap:2px}
.hc-xc-go svg{width:16px;height:16px;transition:transform .2s}
.hc-xc-go:hover{background:var(--accent-soft)}
.hc-xc-go:hover svg{transform:translateX(3px)}
.hc-xr-note{margin-top:10px!important;font-size:12.5px!important;color:var(--dim)!important}
@media (max-width:640px){.hc-xr{grid-template-columns:minmax(0,1fr)}.hc-prod{grid-template-columns:96px minmax(0,1fr)}}
.hc-prod{display:grid;grid-template-columns:120px minmax(0,1fr);gap:14px;align-items:center;margin-top:10px;padding:10px;border:1px solid rgba(255,255,255,.16);border-radius:14px;background:rgba(255,255,255,.06);color:#fff;transition:background .2s,border-color .2s}
.hc-prod:hover{background:rgba(255,255,255,.12);border-color:rgba(127,181,255,.5)}
.hc-prod[hidden]{display:none!important}
.hc-prod-img{position:relative;display:grid;place-items:center;aspect-ratio:4/3;border-radius:10px;background:#fff;overflow:hidden}
.hc-prod-img img{width:100%;height:100%;object-fit:contain;padding:6px}
.hc-prod-img svg{display:none;width:86%;height:auto}
.hc-prod-img.noimg img{display:none}
.hc-prod-img.noimg svg{display:block}
.hc-prod-tx small{display:block;font-size:11.5px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#9cc6ff}
.hc-prod-tx b{display:block;margin-top:4px;font-size:17px;line-height:1.25}
.hc-prod-tx em{display:block;margin-top:6px;font-style:normal;font-size:12.5px;color:rgba(255,255,255,.7)}
.hc-mini{margin:0;font-size:12.5px;line-height:1.5;color:rgba(255,255,255,.7)}
.hc-mini a{color:#fff;text-decoration:underline;text-underline-offset:2px}
.hc-disc-box{display:grid;grid-template-columns:24px minmax(0,1fr);gap:12px;margin-top:16px;padding:16px 18px;border:1px solid #f3d38b;border-left:4px solid #f59e0b;border-radius:var(--radius);background:#fffbf0;scroll-margin-top:calc(var(--hdr) + 80px)}
.hc-disc-box>svg{width:22px;height:22px;color:#d97706;margin-top:1px}
.hc-disc-box b{display:block;font-size:15px;color:#7a4b00}
.hc-disc-box p{margin:6px 0 0;font-size:13.5px;line-height:1.6;color:#5c4a1f}
.hc-copy{min-height:44px;padding:0 16px;border:1px solid rgba(255,255,255,.45);border-radius:999px;background:transparent;color:#fff;font:600 14px/1 Inter,system-ui,sans-serif;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:background .2s}
.hc-copy:hover{background:rgba(255,255,255,.12)}
.hc-copy svg{width:16px;height:16px}
.hc-author{display:flex;gap:14px;align-items:center;margin-top:28px;padding:16px 18px;border:1px solid var(--line);border-radius:var(--radius);background:var(--bg-2)}
.hc-author i{flex-shrink:0;width:48px;height:48px;border-radius:14px;display:grid;place-items:center;background:var(--grad);color:#fff;font:800 15px/1 "JetBrains Mono",monospace;font-style:normal}
.hc-author b{display:block;font-size:15px}
.hc-author span{display:block;font-size:13.5px;color:var(--muted);line-height:1.5}
.hc-band{background:linear-gradient(135deg,#0b1b2e,#0d2a52 55%,#0a3d91);color:#fff;padding:clamp(36px,5vw,64px) 0}
.hc-band-grid{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:24px 40px;align-items:center}
.hc-band h2{font-size:clamp(24px,3vw,36px);line-height:1.15;letter-spacing:-.02em;color:#fff}
.hc-band ol{list-style:none;margin:18px 0 0;padding:0;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;counter-reset:b}
.hc-band ol li{position:relative;padding:14px 14px 14px 52px;border:1px solid rgba(255,255,255,.14);border-radius:14px;background:rgba(255,255,255,.05);font-size:14.5px;line-height:1.45;color:rgba(255,255,255,.9);counter-increment:b}
.hc-band ol li::before{content:counter(b,decimal-leading-zero);position:absolute;left:14px;top:14px;width:28px;height:28px;border-radius:8px;display:grid;place-items:center;background:rgba(127,181,255,.2);color:#9cc6ff;font:700 12px/1 "JetBrains Mono",monospace}
.hc-band ol b{display:block;color:#fff}
.hc-band-acts{display:grid;gap:10px;min-width:240px}
.hc-band .btn-g{background:transparent;color:#fff;border-color:rgba(255,255,255,.45);box-shadow:none}
.hc-band .btn-g:hover{background:rgba(255,255,255,.1);color:#fff}
.hc-head{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:12px 24px;margin-bottom:22px}
.hc-head h2{font-size:clamp(24px,2.8vw,34px);line-height:1.15;letter-spacing:-.02em;margin-top:8px}
.hc-head p{color:var(--muted);max-width:60ch;margin-top:8px;font-size:16px}
.hc-card{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);border:1px solid var(--line);border-radius:var(--radius-lg);background:#fff;box-shadow:var(--shadow);overflow:hidden}
.hc-in{padding:clamp(18px,2.5vw,30px);display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px 16px;align-content:start}
.hc-in .full{grid-column:1/-1}
.hc-f{display:grid;gap:6px;min-width:0}
.hc-f[hidden],.hc-note[hidden],.hc-acts [hidden],.hc-r[hidden]{display:none!important}
.hc-f>label,.hc-f>span.l{font-size:13.5px;font-weight:600;color:var(--muted)}
.hc-u{position:relative;display:flex}
.hc-u input,.hc-u select,.hc-f select{width:100%;min-height:48px;padding:0 14px;border:1px solid var(--line-2);border-radius:12px;background:#fff;font:600 16px/1 Inter,system-ui,sans-serif;color:var(--text);-webkit-appearance:none;appearance:none;transition:border-color .2s,box-shadow .2s}
.hc-u select,.hc-f select{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%235b6b80' stroke-width='1.7'%3E%3Cpath d='m3 4.5 3 3 3-3'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center;padding-right:36px}
.hc-u input{padding-right:58px}
.hc-u em{position:absolute;right:12px;top:50%;transform:translateY(-50%);font:600 12.5px/1 "JetBrains Mono",monospace;font-style:normal;color:var(--dim);pointer-events:none}
.hc-u input:focus,.hc-u select:focus,.hc-f select:focus{outline:0;border-color:var(--accent);box-shadow:0 0 0 4px rgba(10,108,255,.12)}
.hc-range{display:flex;align-items:center;gap:12px}
.hc-range input[type=range]{flex:1;accent-color:var(--accent);min-height:32px}
.hc-range output{min-width:62px;text-align:right;font:700 15px/1 "JetBrains Mono",monospace;color:var(--accent)}
.hc-hint{font-size:12.5px;color:var(--dim);line-height:1.45}

.hc-out{position:relative;padding:clamp(18px,2.5vw,30px);background:linear-gradient(160deg,#0b1b2e 0%,#0d2a52 60%,#0a3d91 100%);color:#fff;display:flex;flex-direction:column;gap:14px}
.hc-out::before{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.04) 1px,transparent 1px);background-size:32px 32px;pointer-events:none}
.hc-out>*{position:relative}
.hc-out .t{font:700 11.5px/1 "JetBrains Mono",monospace;letter-spacing:.08em;text-transform:uppercase;color:#7fb5ff}
.hc-res{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.hc-r{padding:14px 16px;border:1px solid rgba(255,255,255,.12);border-radius:14px;background:rgba(255,255,255,.05);min-width:0}
.hc-r.big{grid-column:1/-1;background:rgba(10,108,255,.22);border-color:rgba(127,181,255,.4)}
.hc-r span{display:block;font-size:12.5px;color:rgba(255,255,255,.7)}
.hc-r b{display:block;margin-top:6px;font-size:clamp(20px,2.4vw,26px);line-height:1.15;letter-spacing:-.01em;overflow-wrap:anywhere}
.hc-r.big b{font-size:clamp(26px,3.4vw,36px)}
.hc-r small{display:block;margin-top:4px;font-size:12.5px;color:rgba(255,255,255,.65)}
.hc-note{padding:12px 14px;border-radius:12px;font-size:14px;line-height:1.5;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14)}
.hc-note.ok{background:rgba(16,185,129,.16);border-color:rgba(16,185,129,.45)}
.hc-note.warn{background:rgba(245,158,11,.16);border-color:rgba(245,158,11,.5)}
.hc-note.bad{background:rgba(239,68,68,.18);border-color:rgba(239,68,68,.5)}
.hc-acts{display:flex;flex-wrap:wrap;gap:8px;margin-top:auto;padding-top:4px}
.hc-acts .btn{flex:1 1 auto}
.hc-out .btn-g{background:transparent;color:#fff;border-color:rgba(255,255,255,.45);box-shadow:none}
.hc-out .btn-g:hover{background:rgba(255,255,255,.1);color:#fff;border-color:#fff}

.hc-formula{margin-top:16px;border:1px solid var(--line);border-radius:var(--radius);background:#fff}
.hc-formula summary{display:flex;align-items:center;justify-content:space-between;gap:10px;min-height:52px;padding:0 18px;cursor:pointer;font-weight:700;font-size:15px;list-style:none}
.hc-formula summary::-webkit-details-marker{display:none}
.hc-formula summary::after{content:"+";font:600 20px/1 "JetBrains Mono",monospace;color:var(--accent)}
.hc-formula[open] summary::after{content:"−"}
.hc-formula div{padding:0 18px 18px;color:#1f2f43;font-size:15.5px;line-height:1.7}
.hc-formula code{display:block;margin:10px 0;padding:12px 14px;border-radius:10px;background:var(--bg-2);border:1px solid var(--line);font:600 14px/1.6 "JetBrains Mono",monospace;color:var(--text);white-space:pre-wrap}
.hc-formula ul{margin:8px 0 0;padding-left:18px}

.hc-cv{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.hc-cv .hc-card{grid-template-columns:minmax(0,1fr)}
.hc-tbl{width:100%;border-collapse:collapse;font-size:15px}
.hc-tbl td{padding:10px 4px;border-top:1px solid rgba(255,255,255,.12)}
.hc-tbl tr:first-child td{border-top:0}
.hc-tbl td:last-child{text-align:right;font:700 15.5px/1.3 "JetBrains Mono",monospace;overflow-wrap:anywhere}

.hc-faq{display:grid;gap:10px;max-width:900px}
.hc-faq details{border:1px solid var(--line);border-radius:var(--radius);background:#fff}
.hc-faq summary{display:flex;justify-content:space-between;align-items:center;gap:12px;min-height:56px;padding:12px 18px;cursor:pointer;font-weight:700;font-size:16px;list-style:none}
.hc-faq summary::-webkit-details-marker{display:none}
.hc-faq summary::after{content:"+";flex-shrink:0;font:600 20px/1 "JetBrains Mono",monospace;color:var(--accent)}
.hc-faq details[open] summary::after{content:"−"}
.hc-faq p{padding:0 18px 18px;margin:0;color:var(--muted);line-height:1.7}
.hc-search{gap:8px}
.hc-search input{padding-right:14px;text-transform:uppercase;font-family:"JetBrains Mono",monospace;letter-spacing:.02em}
.hc-search .btn{flex-shrink:0;min-height:48px;padding:0 22px}
.hc-chips{display:flex;flex-wrap:wrap;gap:6px}
.hc-chips button{min-height:36px;padding:0 12px;border:1px dashed var(--line-2);border-radius:999px;background:var(--bg-2);font:600 12.5px/1 "JetBrains Mono",monospace;color:var(--muted);cursor:pointer;transition:all .2s}
.hc-chips button:hover{border-style:solid;border-color:var(--accent);color:var(--accent);background:#fff}
.hc-disc{margin-top:22px;font-size:13.5px;color:var(--dim);max-width:80ch}

@media (max-width:1240px){
  .hc-card{grid-template-columns:minmax(0,1fr)}
  .hc-out .hc-res{grid-template-columns:repeat(3,minmax(0,1fr))}
  .hc-cv .hc-out .hc-res{grid-template-columns:minmax(0,1fr)}
}
@media (max-width:1100px){
  .hc-layout{grid-template-columns:minmax(0,1fr)}
  .hc-side{display:none}
  .hc-tabs{display:block}
  .hc-sec{scroll-margin-top:calc(var(--hdr) + 66px)}
  .hc-band-grid{grid-template-columns:minmax(0,1fr)}
  .hc-band-acts{grid-template-columns:repeat(3,minmax(0,1fr));min-width:0}
}
@media (max-width:900px){
  .hc-hero-grid{grid-template-columns:minmax(0,1fr)}
  .hc-draw{max-width:620px}
  .hc-cv{grid-template-columns:minmax(0,1fr)}
  .hc-band ol{grid-template-columns:minmax(0,1fr)}
}
@media (max-width:700px){
  .hc-out .hc-res{grid-template-columns:repeat(2,minmax(0,1fr))}
  .hc-band-acts{grid-template-columns:minmax(0,1fr)}
}
@media (max-width:600px){
  .hc-search{flex-direction:column}
  .hc-trust{grid-template-columns:minmax(0,1fr)}
  .hc-hero-acts .btn{flex:1 1 100%}
  .hc-draw-cap{font-size:11.5px}
  .hc-in{grid-template-columns:minmax(0,1fr)}
  .hc-r{padding:12px 14px}
  .hc-acts .btn{flex:1 1 100%}
}
@media (max-width:359px){.hc-res{grid-template-columns:minmax(0,1fr)}}
/* v1.4 muadil alanı */
.hc-steps{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}
.hc-steps li{display:flex;align-items:center;gap:8px;min-width:0;padding:10px;border:1px solid var(--line);border-radius:12px;background:var(--bg-2);font-size:13px;font-weight:600;color:var(--text);line-height:1.25}
.hc-steps i{flex-shrink:0;width:24px;height:24px;border-radius:50%;display:grid;place-items:center;background:var(--accent);color:#fff;font:700 12px/1 "JetBrains Mono",monospace;font-style:normal}
.hc-brands{list-style:none;margin:0;padding:0;display:flex;flex-wrap:wrap;gap:6px}
.hc-brands li{padding:7px 12px;border:1px solid var(--line);border-radius:999px;background:#fff;font-size:13px;font-weight:700;color:var(--muted)}
#m-code{min-height:56px;font-size:17px}
#m-form .hc-search .btn{min-height:56px;padding:0 28px}
.hc-note.hc-empty{display:grid;justify-items:start;gap:8px;padding:22px;border-style:dashed;border-color:rgba(127,181,255,.4);background:rgba(255,255,255,.05)}
.hc-empty>svg{width:34px;height:34px;color:#7fb5ff}
.hc-empty>b{font-size:19px;letter-spacing:-.01em}
.hc-empty p{margin:0;color:rgba(255,255,255,.8);font-size:14px}
.hc-empty ul{list-style:none;margin:6px 0 0;padding:0;display:grid;gap:8px;width:100%}
.hc-empty li{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;background:rgba(255,255,255,.07);font-size:14px;font-weight:600}
.hc-empty li::before{content:"";flex-shrink:0;width:8px;height:8px;border-radius:50%;background:#00b4d8}
.hc-note.warn svg{display:none}
.hc-disc-more{margin-top:6px}
.hc-disc-more summary{display:inline-flex;align-items:center;min-height:44px;cursor:pointer;font-size:13.5px;font-weight:700;color:#7a4b00;text-decoration:underline;text-underline-offset:3px}
@media (max-width:640px){
  .hc-steps{grid-template-columns:minmax(0,1fr)}
  .hc-steps li{padding:9px 12px}
  .hc-disc-box{padding:14px;grid-template-columns:20px minmax(0,1fr);gap:10px}
  .hc-note.hc-empty{padding:18px}
}
@media print{.hc-tabs,.hc-side,.hc-band,.hc-hero-acts,.hc-copy,.hc-acts,.es-hdr,.es-ftr,.es-mbar,.es-wa{display:none!important}.hc-card{break-inside:avoid}}
</style>

<header class="hc-hero">
  <div class="wrap hc-hero-grid">
    <div>
      <nav aria-label="Sayfa yolu">
        <ol class="bc"><li><a href="{{ url('/') }}">Ana Sayfa</a></li><li aria-current="page">Hesaplamalar</li></ol>
      </nav>
      <span class="eyebrow">Mühendislik araçları</span>
      <h1>Pnömatik ve hidrolik hesaplamalar</h1>
      <p class="lead">Ensis Pnömatik teknik ekibinin günlük işte kullandığı hesapları burada topladık. Muadil kodu çözün, silindir kuvvetini ve hava tüketimini bulun, çapı seçin, rodu burkulmaya karşı kontrol edin. Sonucu tek tıkla teklif talebine dönüştürün.</p>
      <ul class="hc-trust">
        <li><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10" cy="10" r="8.2"/><path d="m6.5 10.2 2.3 2.3 4.7-4.9"/></svg><span>7 mühendislik aracı<small>Kayıt gerekmez, ücretsiz</small></span></li>
        <li><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10" cy="10" r="8.2"/><path d="m6.5 10.2 2.3 2.3 4.7-4.9"/></svg><span>ISO 15552 · 6432 · 21287<small>Standart çap ve rot ölçüleri</small></span></li>
        <li><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10" cy="10" r="8.2"/><path d="m6.5 10.2 2.3 2.3 4.7-4.9"/></svg><span>Açık formüller<small>Her sonucun hesabı sayfada</small></span></li>
        <li><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10" cy="10" r="8.2"/><path d="m6.5 10.2 2.3 2.3 4.7-4.9"/></svg><span>Teknik ekip teyidi<small>Sonucunuzu bize gönderin, birlikte seçelim</small></span></li>
      </ul>
      <div class="hc-hero-acts">
        <a class="btn btn-p" href="#muadil">Muadil kod bul {!! $icArr !!}</a>
        <a class="btn btn-g" href="#kuvvet">Kuvvet hesapla</a>
      </div>
    </div>
    <figure class="hc-draw" style="margin:0">
      <svg viewBox="0 0 540 290" role="img" aria-labelledby="hc-draw-t">
        <title id="hc-draw-t">Pnömatik silindir teknik çizimi: piston çapı D, rot çapı d, strok s ve itme kuvveti F</title>
        <defs>
          <pattern id="hcg" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M20 0H0V20" fill="none" stroke="#e6eef8" stroke-width="1"/></pattern>
          <marker id="hca" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0 10 5 0 10z" fill="#0a6cff"/></marker>
          <marker id="hct" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="8" markerHeight="8" orient="auto"><path d="M5 0v10" stroke="#5b6b80" stroke-width="1.5"/></marker>
          <linearGradient id="hcb" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#eef5ff"/><stop offset=".5" stop-color="#ffffff"/><stop offset="1" stop-color="#e3eefc"/></linearGradient>
          <linearGradient id="hcr" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f4f7fb"/><stop offset=".45" stop-color="#ffffff"/><stop offset="1" stop-color="#cfdbe9"/></linearGradient>
        </defs>
        <rect width="540" height="290" fill="url(#hcg)"/>
        <line x1="20" y1="150" x2="520" y2="150" stroke="#9fb3c8" stroke-width="1" stroke-dasharray="10 4 2 4"/>
        <rect x="78" y="104" width="258" height="92" rx="4" fill="url(#hcb)" stroke="#0b1b2e" stroke-width="1.6"/>
        <rect x="60" y="94" width="22" height="112" rx="3" fill="#dfe9f6" stroke="#0b1b2e" stroke-width="1.6"/>
        <rect x="332" y="94" width="22" height="112" rx="3" fill="#dfe9f6" stroke="#0b1b2e" stroke-width="1.6"/>
        <rect x="190" y="108" width="16" height="84" fill="#0a6cff" opacity=".9"/>
        <rect x="206" y="138" width="248" height="24" fill="url(#hcr)" stroke="#0b1b2e" stroke-width="1.4"/>
        <rect x="454" y="132" width="26" height="36" rx="3" fill="#dfe9f6" stroke="#0b1b2e" stroke-width="1.4"/>
        <circle cx="104" cy="94" r="7" fill="#fff" stroke="#0b1b2e" stroke-width="1.4"/><circle cx="310" cy="94" r="7" fill="#fff" stroke="#0b1b2e" stroke-width="1.4"/>
        <path d="M104 64v20" stroke="#00b4d8" stroke-width="2" marker-end="url(#hca)"/>
        <text x="104" y="56" text-anchor="middle" font-family="JetBrains Mono,monospace" font-size="12" font-weight="700" fill="#00a3c4">P</text>
        <line x1="36" y1="104" x2="36" y2="196" stroke="#5b6b80" stroke-width="1.2" marker-start="url(#hct)" marker-end="url(#hct)"/>
        <line x1="30" y1="104" x2="78" y2="104" stroke="#9fb3c8" stroke-width="1"/><line x1="30" y1="196" x2="78" y2="196" stroke="#9fb3c8" stroke-width="1"/>
        <text x="28" y="154" text-anchor="end" font-family="JetBrains Mono,monospace" font-size="14" font-weight="700" fill="#0b1b2e">ØD</text>
        <line x1="420" y1="104" x2="420" y2="138" stroke="#9fb3c8" stroke-width="1"/>
        <text x="420" y="98" text-anchor="middle" font-family="JetBrains Mono,monospace" font-size="13" font-weight="700" fill="#0b1b2e">Ød</text>
        <line x1="206" y1="232" x2="454" y2="232" stroke="#5b6b80" stroke-width="1.2" marker-start="url(#hct)" marker-end="url(#hct)"/>
        <line x1="206" y1="196" x2="206" y2="240" stroke="#9fb3c8" stroke-width="1"/><line x1="454" y1="168" x2="454" y2="240" stroke="#9fb3c8" stroke-width="1"/>
        <text x="330" y="256" text-anchor="middle" font-family="JetBrains Mono,monospace" font-size="13" font-weight="700" fill="#0b1b2e">s (strok)</text>
        <line x1="486" y1="150" x2="528" y2="150" stroke="#0a6cff" stroke-width="3" marker-end="url(#hca)"/>
        <text x="506" y="136" text-anchor="middle" font-family="JetBrains Mono,monospace" font-size="15" font-weight="800" fill="#0a6cff">F</text>
        <text x="198" y="90" text-anchor="middle" font-family="Inter,system-ui,sans-serif" font-size="11" fill="#5b6b80">piston</text>
      </svg>
      <figcaption class="hc-draw-cap"><span>F = P × π × D² / 4</span><span>Ø50 · 6 bar → <b>1.178 N</b></span></figcaption>
    </figure>
  </div>
</header>

<nav class="hc-tabs" aria-label="Hesaplayıcılar (kısayol)">
  <ul>
    @foreach ($calcs as $c)<li><a href="#{{ $c[0] }}" data-tab="{{ $c[0] }}">{{ $c[1] }}</a></li>@endforeach
    <li><a href="#sss" data-tab="sss">Sık sorulanlar</a></li>
  </ul>
</nav>

<div class="wrap hc-layout">
  <aside class="hc-side" aria-label="Araçlar">
    <nav class="hc-side-box" aria-label="Hesaplayıcılar">
      <b>Araçlar</b>
      <ol>
        @foreach ($calcs as $i => $c)<li><a href="#{{ $c[0] }}" data-tab="{{ $c[0] }}"><i>{{ sprintf('%02d', $i + 1) }}</i>{{ $c[1] }}</a></li>@endforeach
        <li><a href="#sss" data-tab="sss"><i>?</i>Sık sorulanlar</a></li>
      </ol>
    </nav>
    <div class="hc-side-box hc-help">
      <b>Teknik destek</b>
      <p>Hesabınızı teknik ekibimizle birlikte kontrol edelim; doğru seriyi ve teslim süresini hızlıca iletelim.</p>
      <a class="w" href="{{ $quote }}?not={{ rawurlencode('Hesaplamalar sayfasından teknik destek') }}#form">Teklif / destek iste</a>
      <a class="g" href="tel:{{ $telIntl }}">{{ $tel }}</a>
      <a class="g" href="https://wa.me/{{ $wa }}?text={{ rawurlencode('Merhaba, hesaplamalar sayfanızdan ulaşıyorum.') }}" target="_blank" rel="noopener">WhatsApp</a>
    </div>
  </aside>

  <div class="hc-main">
{{-- 0) MUADİL KOD BULUCU --}}
<section class="hc-sec" id="muadil" aria-labelledby="h-muadil">
  <div class="hc-sec-in">
    <div class="hc-head"><div><span class="eyebrow">01 · Muadil bulucu</span><h2 id="h-muadil">Muadil ürün kodu bulucu</h2><p>Elinizdeki Festo, SMC, Airtac, Camozzi, Norgren veya Parker ürününün kodunu yazın. Ürün tipini, ISO standardını, çapı ve stroku çözelim; karşılığı olan ürün grubumuzu gösterelim.</p></div></div>
    <div class="hc-card">
      <form class="hc-in" id="m-form" aria-label="Muadil kod arama" style="grid-template-columns:minmax(0,1fr)">
        <ol class="hc-steps" aria-label="Nasıl kullanılır">
          <li><i>1</i><span>Kodu yazın</span></li>
          <li><i>2</i><span>Bul'a basın</span></li>
          <li><i>3</i><span>Muadili görün</span></li>
        </ol>
        <div class="hc-f"><label for="m-code">Mevcut ürün kodu</label>
          <div class="hc-u hc-search"><input id="m-code" type="text" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="Örn. DSBC-50-200-PPVA-N3" maxlength="60"><button class="btn btn-p" type="submit">Bul</button></div></div>
        <div class="hc-f"><span class="l">Örnek kodlar</span>
          <div class="hc-chips">
            @foreach (['DSBC-50-200-PPVA-N3', 'CP96SDB63-150', 'SC50X200', 'DSNU-25-100-PPV-A', 'CDQ2B32-50D', 'RA/8063/M/250', '61M2P040A0100', 'MGPM20-50'] as $ex)
              <button type="button" data-ex="{{ $ex }}">{{ $ex }}</button>
            @endforeach
          </div></div>
        <div class="hc-f"><span class="l">Desteklenen markalar</span>
          <ul class="hc-brands">@foreach (['Festo', 'SMC', 'Airtac', 'Camozzi', 'Norgren', 'Parker'] as $bn0)<li>{{ $bn0 }}</li>@endforeach</ul></div>
        <p class="hc-hint">Kod etiket, fatura veya eski siparişlerde yazar. Tam kod yoksa seri adı ile çap ve strok da yeterli (ör. "DNC 63 x 250").</p>
      </form>
      <div class="hc-out" aria-live="polite">
        <span class="t">Sonuç</span>
        <div id="m-empty" class="hc-note hc-empty">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/></svg>
          <b>Sonuç burada görünecek</b>
          <p>Kodu yazıp <strong>Bul</strong>'a basın veya soldaki örneklerden birini seçin.</p>
          <ul><li>Ürün tipi ve ISO standardı</li><li>Çap ve strok</li><li>Ensis muadil ürün grubu</li></ul>
        </div>
        <div id="m-res" hidden>
          <div class="hc-res">
            <div class="hc-r big"><span>Ürün tipi</span><b id="m-r1">—</b><small id="m-r1s"></small></div>
            <div class="hc-r"><span>Tahmini marka</span><b id="m-r2">—</b></div>
            <div class="hc-r"><span>Standart</span><b id="m-r3">—</b></div>
            <div class="hc-r" id="m-bore-r"><span>Çap</span><b id="m-r4">—</b><small id="m-r4s"></small></div>
            <div class="hc-r" id="m-stroke-r"><span>Strok</span><b id="m-r5">—</b></div>
          </div>
          <a class="hc-prod" id="m-prod" href="{{ url('urunlerimiz') }}">
            <span class="hc-prod-img" id="m-img-w">
              <img id="m-img" src="data:image/gif;base64,R0lGODlhAQABAAAAACw=" alt="" width="160" height="120" decoding="async">
              <svg viewBox="0 0 160 120" aria-hidden="true"><rect x="22" y="44" width="76" height="32" rx="3" fill="#eef5ff" stroke="#0b1b2e" stroke-width="1.5"/><rect x="16" y="40" width="8" height="40" rx="2" fill="#dfe9f6" stroke="#0b1b2e" stroke-width="1.5"/><rect x="96" y="40" width="8" height="40" rx="2" fill="#dfe9f6" stroke="#0b1b2e" stroke-width="1.5"/><rect x="56" y="46" width="6" height="28" fill="#0a6cff"/><rect x="62" y="56" width="78" height="8" fill="#fff" stroke="#0b1b2e" stroke-width="1.3"/><rect x="138" y="52" width="8" height="16" rx="2" fill="#dfe9f6" stroke="#0b1b2e" stroke-width="1.3"/></svg>
            </span>
            <span class="hc-prod-tx"><small>Ensis muadil ürün grubu</small><b id="m-prod-t">—</b><em>Temsili görsel · ürünleri incele →</em></span>
          </a>
          <div class="hc-note" id="m-note" style="margin-top:12px"></div>
        </div>
        <div class="hc-acts">
          <a class="btn btn-w" id="m-quote" href="{{ $quote }}#form">Muadil için teklif iste {!! $icArr !!}</a>
          <a class="btn btn-g" id="m-wa" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener">WhatsApp'tan sor</a>
        </div>
        <p class="hc-mini">Sonuç, ürün kodunun otomatik yorumudur ve teknik teyit gerektirir. <a href="#muadil-ret">Sorumluluk reddi</a></p>
      </div>
    </div>
    <aside class="hc-disc-box" id="muadil-ret" role="note" aria-labelledby="muadil-ret-h">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 3 2.5 20h19L12 3Z"/><path d="M12 10v4.5M12 17.2v.3"/></svg>
      <div>
        <b id="muadil-ret-h">Sorumluluk reddi beyanı</b>
        <p>Muadil ürün kodu bulucu, girilen kodu üreticilerin genel kodlama düzenine göre otomatik olarak yorumlar. Gösterilen ürün tipi, standart, çap, strok ve ürün grubu yalnızca <strong>bilgilendirme ve ön seçim</strong> amaçlıdır; teknik teyit, katalog verisi veya yazılı teklif yerine geçmez. Görseller temsilidir.</p>
        <details class="hc-disc-more"><summary>Tam metni göster</summary>
        <p>Kesin muadil; bağlantı ölçüleri, çalışma koşulları, malzeme, aksesuar ve sertifika gereksinimleri incelenerek teknik ekibimizin yazılı teyidi ile belirlenir. Ensis Pnömatik, bu aracın sonuçlarına dayanılarak teyit alınmadan yapılan seçim, sipariş, montaj veya kullanımdan doğabilecek uyumsuzluk, arıza ya da zararlardan sorumlu tutulamaz.</p>
        <p>Festo, SMC, Airtac, Camozzi, Norgren (IMI) ve Parker ilgili sahiplerinin tescilli markalarıdır. Bu adlar yalnızca ürün tipini karşılaştırmak amacıyla anılmıştır; anılmaları söz konusu firmalarla bir ortaklık, bayilik veya onay ilişkisi bulunduğu anlamına gelmez.</p>
        </details>
      </div>
    </aside>
    <details class="hc-formula"><summary>Nasıl çalışır?</summary><div>
      <p>Üreticiler ürün kodunda seri adını, çapı ve stroku belirli bir düzende yazar. Ör. <b>DSBC-50-200</b> → Festo ISO 15552 silindir, Ø50, 200 mm strok; <b>SC50X200</b> → Airtac standart silindir, Ø50 × 200.</p>
      <ul><li><b>ISO 15552, ISO 6432 ve ISO 21287</b> silindirlerin bağlantı ölçüleri üreticiden bağımsızdır. Bu yüzden muadil silindir mevcut bağlantı elemanlarınıza birebir oturur.</li><li>Valf, şartlandırıcı ve bağlantı parçalarında bağlantı ağzı, debi ve gerilim de karşılaştırılmalıdır. Teknik ekibimiz kodu teyit edip uygun ürünü önerir.</li><li>Bu araç kodu tarayıcınızda çözer. Kodlar sunucuya kaydedilmez.</li></ul>
    </div></details>
    <div class="hc-ref">
      <h3>Seri karşılık rehberi</h3>
      <p>Yaygın üretici serilerinin ürün tipine göre karşılıkları. Bir seriye dokunun, bulucuda çözelim.</p>
      <div class="hc-xr">
        @foreach ($series as $sr)
          <article class="hc-xc">
            <header><h4>{{ $sr[0] }}</h4>@if ($sr[1] !== '—')<span class="{{ strpos($sr[1], 'ISO') === 0 ? 'iso' : '' }}">{{ $sr[1] === 'Üretici' ? 'Üretici standardı' : $sr[1] }}</span>@endif</header>
            <dl>
              @foreach ($brandsX as $bi => $bn)
                @if ($sr[$bi + 2] !== '—')
                  <div><dt>{{ $bn }}</dt><dd>@foreach (array_map('trim', explode(',', $sr[$bi + 2])) as $se)<button type="button" data-ex="{{ isset($exCodes[$se]) ? $exCodes[$se] : $se }}" title="{{ $bn }} {{ $se }} serisini çöz">{{ $se }}</button>@endforeach</dd></div>
                @endif
              @endforeach
            </dl>
            <a class="hc-xc-go" href="{{ $muadilCats[$sr[8]][0] }}"><small>Ensis muadili</small>{{ $muadilCats[$sr[8]][1] }} {!! $icArr !!}</a>
          </article>
        @endforeach
      </div>
      <p class="hc-xr-note">Seri adları ilgili üreticilerin tescilli markalarıdır; bu rehber yalnızca ürün tipini eşleştirmek içindir. Kesin muadil için kodun tamamını gönderin.</p>
    </div>
  </div>
</section>

{{-- 1) KUVVET --}}
<section class="hc-sec" id="kuvvet" aria-labelledby="h-kuvvet" data-calc="kuvvet">
  <div class="hc-sec-in">
    <div class="hc-head"><div><span class="eyebrow">02 · Pnömatik</span><h2 id="h-kuvvet">Pnömatik silindir kuvvet hesaplama</h2><p>Silindirin itme ve çekme kuvvetini teorik ve sürtünme kayıplı (gerçekçi) olarak hesaplayın.</p></div></div>
    <div class="hc-card">
      <form class="hc-in" onsubmit="return false" aria-label="Kuvvet değerleri">
        <div class="hc-f"><label for="k-D">Silindir çapı (Ø)</label>
          <div class="hc-u"><select id="k-D" data-bore>@foreach ($bores as $D => $d)<option value="{{ $D }}" data-rod="{{ $d }}" @if($D == 50) selected @endif>Ø{{ $D }} mm</option>@endforeach</select></div></div>
        <div class="hc-f"><label for="k-d">Rot çapı</label><div class="hc-u"><input id="k-d" type="number" inputmode="decimal" min="0" step="1" value="20"><em>mm</em></div></div>
        <div class="hc-f full"><span class="l">Çalışma basıncı</span>
          <div class="hc-range"><input id="k-P" type="range" min="1" max="10" step="0.5" value="6" aria-label="Çalışma basıncı (bar)"><output id="k-P-o">6 bar</output></div></div>
        <div class="hc-f full"><label for="k-e">Verim (sürtünme kaybı)</label>
          <select id="k-e"><option value="0.9">%90 — yeni silindir, iyi yağlama</option><option value="0.85" selected>%85 — genel kullanım (önerilen)</option><option value="0.75">%75 — düşük basınç / yüksek sürtünme</option></select></div>
        <p class="hc-hint full">Rot çapı, seçtiğiniz silindir çapının ISO standart değeriyle otomatik dolar; farklıysa değiştirebilirsiniz.</p>
      </form>
      <div class="hc-out" aria-live="polite">
        <span class="t">Sonuç</span>
        <div class="hc-res">
          <div class="hc-r big"><span>İtme kuvveti (gerçekçi)</span><b id="k-r1">—</b><small id="k-r1s"></small></div>
          <div class="hc-r"><span>İtme (teorik)</span><b id="k-r2">—</b><small id="k-r2s"></small></div>
          <div class="hc-r"><span>Çekme (teorik)</span><b id="k-r3">—</b><small id="k-r3s"></small></div>
          <div class="hc-r"><span>Çekme (gerçekçi)</span><b id="k-r4">—</b></div>
          <div class="hc-r"><span>Piston alanı</span><b id="k-r5">—</b></div>
        </div>
        <div class="hc-acts"><a class="btn btn-w" data-quote href="{{ $quote }}#form">Bu silindir için teklif iste {!! $icArr !!}</a></div>
      </div>
    </div>
    <details class="hc-formula"><summary>Formül ve açıklama</summary><div>
      <code>F_itme  = P × 0,1 × π × D² / 4          [N]
F_çekme = P × 0,1 × π × (D² − d²) / 4   [N]
F_gerçek = F × η</code>
      <ul><li><b>P</b>: basınç (bar) — 0,1 çarpanı bar'ı N/mm²'ye (MPa) çevirir.</li><li><b>D</b>: piston (silindir) çapı, <b>d</b>: rot çapı (mm).</li><li><b>η</b>: verim; conta sürtünmesi nedeniyle genelde 0,85 alınır. 1 kgf = 9,81 N.</li></ul>
    </div></details>
    <div class="hc-ref">
      <h3>Pnömatik silindir kuvvet tablosu (teorik itme, N)</h3>
      <p>Sürtünme kaybı dahil değildir; gerçekçi değer için yaklaşık 0,85 ile çarpın. Çekme kuvveti 6 bar'da ve ISO standart rot çapıyla verilmiştir.</p>
      <div class="hc-rt"><table>
        <thead><tr><th scope="col">Çap</th>@foreach ($forceP as $P)<th scope="col">{{ $P }} bar</th>@endforeach<th scope="col">Çekme 6 bar</th><th scope="col">Rot</th></tr></thead>
        <tbody>
          @foreach ($forceBores as $D)
            <tr><td>Ø{{ $D }}</td>@foreach ($forceP as $P)<td>{{ $fmt($P * 0.1 * $area($D)) }}</td>@endforeach<td>{{ $fmt(0.6 * $area($D, $bores[$D])) }}</td><td>Ø{{ $bores[$D] }}</td></tr>
          @endforeach
        </tbody>
      </table></div>
    </div>
  </div>
</section>

{{-- 2) ÇAP SEÇİMİ --}}
<section class="hc-sec" id="cap-secimi" aria-labelledby="h-cap" data-calc="cap-secimi">
  <div class="hc-sec-in">
    <div class="hc-head"><div><span class="eyebrow">03 · Pnömatik</span><h2 id="h-cap">Pnömatik silindir çapı seçimi</h2><p>Taşınacak yükü ve hareketin hızını girin; gereken minimum çapı ve önerilen ISO standart silindiri bulun.</p></div></div>
    <div class="hc-card">
      <form class="hc-in" onsubmit="return false" aria-label="Çap seçimi değerleri">
        <div class="hc-f"><label for="c-m">Yük (kütle)</label><div class="hc-u"><input id="c-m" type="number" inputmode="decimal" min="0" step="1" value="50"><em>kg</em></div></div>
        <div class="hc-f"><label for="c-dir">Hareket yönü</label>
          <select id="c-dir"><option value="v">Dikey — yükü kaldırma</option><option value="h">Yatay — yükü itme / kaydırma</option></select></div>
        <div class="hc-f" id="c-mu-f" hidden><label for="c-mu">Sürtünme katsayısı (μ)</label>
          <select id="c-mu"><option value="0.1">0,1 — lineer kızak / rulman</option><option value="0.2" selected>0,2 — yağlı metal yüzey</option><option value="0.4">0,4 — kuru metal yüzey</option><option value="0.6">0,6 — lastik / kauçuk</option></select></div>
        <div class="hc-f full"><label for="c-v">Hareket hızı</label>
          <select id="c-v"><option value="0.7">Yavaş (&lt; 100 mm/s) — yük oranı %70</option><option value="0.5" selected>Normal (100–500 mm/s) — %50</option><option value="0.3">Hızlı (&gt; 500 mm/s) — %30</option></select></div>
        <div class="hc-f full"><span class="l">Çalışma basıncı</span>
          <div class="hc-range"><input id="c-P" type="range" min="2" max="10" step="0.5" value="6" aria-label="Çalışma basıncı (bar)"><output id="c-P-o">6 bar</output></div></div>
        <p class="hc-hint full">Basınç olarak tesisatınızdaki en düşük basıncı girin (hat kayıpları sonrası, silindir girişinde).</p>
      </form>
      <div class="hc-out" aria-live="polite">
        <span class="t">Sonuç</span>
        <div class="hc-res">
          <div class="hc-r big"><span>Önerilen silindir</span><b id="c-r1">—</b><small id="c-r1s"></small></div>
          <div class="hc-r"><span>Gereken kuvvet</span><b id="c-r2">—</b><small id="c-r2s"></small></div>
          <div class="hc-r"><span>Hesaplanan min. çap</span><b id="c-r3">—</b></div>
          <div class="hc-r"><span>Önerilen silindirin itmesi</span><b id="c-r4">—</b></div>
          <div class="hc-r"><span>Gerçek yük oranı</span><b id="c-r5">—</b></div>
        </div>
        <div class="hc-note" id="c-note" hidden></div>
        <div class="hc-acts"><a class="btn btn-w" data-quote href="{{ $quote }}#form">Bu silindir için teklif iste {!! $icArr !!}</a></div>
      </div>
    </div>
    <details class="hc-formula"><summary>Formül ve açıklama</summary><div>
      <code>Dikey:  F_yük = m × g
Yatay:  F_yük = μ × m × g
F_gerekli = F_yük / yük oranı
D_min = √( 4 × F_gerekli / (π × P × 0,1) )</code>
      <ul><li>Yük oranı; hızlanma, sürtünme ve basınç dalgalanması için kuvvet payı bırakır. Hız arttıkça oran düşürülür.</li><li>Sonuç bir üst ISO standart çapa yuvarlanır (Ø8 … Ø320).</li><li>Uzun stroklarda rotu <a href="#burkulma">burkulma hesabıyla</a> da kontrol edin.</li></ul>
    </div></details>
  </div>
</section>

{{-- 3) HAVA TÜKETİMİ --}}
<section class="hc-sec" id="hava" aria-labelledby="h-hava" data-calc="hava">
  <div class="hc-sec-in">
    <div class="hc-head"><div><span class="eyebrow">04 · Pnömatik</span><h2 id="h-hava">Hava tüketimi ve kompresör hesabı</h2><p>Silindirlerinizin dakikada ne kadar serbest hava tükettiğini ve hangi kapasitede kompresör gerektiğini görün.</p></div></div>
    <div class="hc-card">
      <form class="hc-in" onsubmit="return false" aria-label="Hava tüketimi değerleri">
        <div class="hc-f"><label for="h-D">Silindir çapı (Ø)</label>
          <div class="hc-u"><select id="h-D" data-bore>@foreach ($bores as $D => $d)<option value="{{ $D }}" data-rod="{{ $d }}" @if($D == 63) selected @endif>Ø{{ $D }} mm</option>@endforeach</select></div></div>
        <div class="hc-f"><label for="h-d">Rot çapı</label><div class="hc-u"><input id="h-d" type="number" inputmode="decimal" min="0" value="20"><em>mm</em></div></div>
        <div class="hc-f"><label for="h-s">Strok</label><div class="hc-u"><input id="h-s" type="number" inputmode="decimal" min="0" value="200"><em>mm</em></div></div>
        <div class="hc-f"><label for="h-type">Silindir tipi</label>
          <select id="h-type"><option value="2">Çift etkili</option><option value="1">Tek etkili (yaylı)</option></select></div>
        <div class="hc-f"><label for="h-c">Çevrim sayısı</label><div class="hc-u"><input id="h-c" type="number" inputmode="decimal" min="0" value="10"><em>/dk</em></div></div>
        <div class="hc-f"><label for="h-n">Silindir adedi</label><div class="hc-u"><input id="h-n" type="number" inputmode="numeric" min="1" value="1"><em>adet</em></div></div>
        <div class="hc-f full"><span class="l">Çalışma basıncı</span>
          <div class="hc-range"><input id="h-P" type="range" min="1" max="10" step="0.5" value="6" aria-label="Çalışma basıncı (bar)"><output id="h-P-o">6 bar</output></div></div>
      </form>
      <div class="hc-out" aria-live="polite">
        <span class="t">Sonuç</span>
        <div class="hc-res">
          <div class="hc-r big"><span>Toplam hava tüketimi</span><b id="h-r1">—</b><small id="h-r1s"></small></div>
          <div class="hc-r"><span>Bir çevrimde (1 silindir)</span><b id="h-r2">—</b></div>
          <div class="hc-r"><span>Saatlik</span><b id="h-r3">—</b></div>
          <div class="hc-r big"><span>Önerilen kompresör kapasitesi (+%30 kaçak/rezerv)</span><b id="h-r4">—</b><small id="h-r4s"></small></div>
        </div>
        <div class="hc-acts"><a class="btn btn-w" href="{{ $quote }}?not={{ rawurlencode('Hava tüketimi / pnömatik sistem danışmanlığı') }}#form">Sistem için destek alın {!! $icArr !!}</a></div>
      </div>
    </div>
    <details class="hc-formula"><summary>Formül ve açıklama</summary><div>
      <code>V_çift strok = (A_itme + A_çekme) × strok × (P + 1,013) / 1,013   [Nl]
Q = V × çevrim/dk × adet                                    [Nl/dk]
Kompresör ≈ Q × 1,3</code>
      <ul><li>Sonuçlar atmosfer basıncındaki serbest hava (normal litre, Nl) cinsindendir; kompresör kataloglarındaki FAD değeriyle karşılaştırılır.</li><li>Tek etkili silindirde yalnızca itme yönü hava tüketir.</li><li>Kompresör güç tahmini kaba bir ön fikirdir (vidalı kompresör, ≈120 Nl/dk/kW, 7–8 bar). Hortum ve valf ölü hacimleri hariçtir.</li></ul>
    </div></details>
    <div class="hc-ref">
      <h3>Hava tüketimi tablosu (çift etkili, 100 mm strok başına)</h3>
      <p>Bir tam çevrimde (ileri + geri) tüketilen serbest hava, normal litre (Nl). Kendi strokunuz için değeri strok/100 ile çarpın.</p>
      <div class="hc-rt"><table>
        <thead><tr><th scope="col">Çap</th><th scope="col">4 bar</th><th scope="col">6 bar</th><th scope="col">8 bar</th></tr></thead>
        <tbody>
          @foreach ($airBores as $D)
            <tr><td>Ø{{ $D }}</td>@foreach ([4, 6, 8] as $P)<td>{{ $fmt(($area($D) + $area($D, $bores[$D])) * 100 * 1e-6 * (($P + 1.013) / 1.013), 2) }}</td>@endforeach</tr>
          @endforeach
        </tbody>
      </table></div>
    </div>
  </div>
</section>

{{-- 4) HİDROLİK --}}
<section class="hc-sec" id="hidrolik" aria-labelledby="h-hid" data-calc="hidrolik">
  <div class="hc-sec-in">
    <div class="hc-head"><div><span class="eyebrow">05 · Hidrolik</span><h2 id="h-hid">Hidrolik silindir kuvvet ve hız hesabı</h2><p>Sistem basıncı ve pompa debisine göre hidrolik silindirin kuvvetini, hızını ve strok süresini hesaplayın.</p></div></div>
    <div class="hc-card">
      <form class="hc-in" onsubmit="return false" aria-label="Hidrolik silindir değerleri">
        <div class="hc-f"><label for="y-D">Piston çapı</label><div class="hc-u"><input id="y-D" type="number" inputmode="decimal" min="1" value="63"><em>mm</em></div></div>
        <div class="hc-f"><label for="y-d">Rot (mil) çapı</label><div class="hc-u"><input id="y-d" type="number" inputmode="decimal" min="0" value="36"><em>mm</em></div></div>
        <div class="hc-f"><label for="y-P">Sistem basıncı</label><div class="hc-u"><input id="y-P" type="number" inputmode="decimal" min="0" value="160"><em>bar</em></div></div>
        <div class="hc-f"><label for="y-Q">Pompa debisi</label><div class="hc-u"><input id="y-Q" type="number" inputmode="decimal" min="0" value="20"><em>L/dk</em></div></div>
        <div class="hc-f full"><label for="y-s">Strok</label><div class="hc-u"><input id="y-s" type="number" inputmode="decimal" min="0" value="300"><em>mm</em></div></div>
      </form>
      <div class="hc-out" aria-live="polite">
        <span class="t">Sonuç</span>
        <div class="hc-res">
          <div class="hc-r big"><span>İtme kuvveti</span><b id="y-r1">—</b><small id="y-r1s"></small></div>
          <div class="hc-r"><span>Çekme kuvveti</span><b id="y-r2">—</b><small id="y-r2s"></small></div>
          <div class="hc-r"><span>Alan oranı (φ)</span><b id="y-r3">—</b></div>
          <div class="hc-r"><span>İleri hız</span><b id="y-r4">—</b><small id="y-r4s"></small></div>
          <div class="hc-r"><span>Geri hız</span><b id="y-r5">—</b><small id="y-r5s"></small></div>
        </div>
        <div class="hc-note" id="y-note" hidden></div>
        <div class="hc-acts"><a class="btn btn-w" data-quote-h href="{{ $quote }}#form">Honlanmış boru / krom mil teklifi {!! $icArr !!}</a></div>
      </div>
    </div>
    <details class="hc-formula"><summary>Formül ve açıklama</summary><div>
      <code>F = P × 0,1 × A                 [N]     (A: mm²)
v = Q × 16 667 / A              [mm/s]  (Q: L/dk)
t = strok / v                   [s]
φ = A_piston / A_halka</code>
      <ul><li>Sonuçlar teoriktir; conta sürtünmesi ve hat kayıpları için kuvvetten yaklaşık %5–10 düşülür.</li><li>Geri dönüşte yağın girdiği halka alanı küçük olduğu için silindir daha hızlı geri gelir; dönüş hattındaki debi de φ kadar artar.</li></ul>
    </div></details>
    <div class="hc-ref">
      <h3>Hidrolik silindir itme kuvveti tablosu (kN) ve hız</h3>
      <p>Teorik itme kuvveti ve 10 L/dk debideki ileri hız. Hız debiyle doğru orantılıdır; 20 L/dk için değeri 2 ile çarpın.</p>
      <div class="hc-rt"><table>
        <thead><tr><th scope="col">Piston</th>@foreach ($hydP as $P)<th scope="col">{{ $P }} bar</th>@endforeach<th scope="col">Hız @ 10 L/dk</th></tr></thead>
        <tbody>
          @foreach ($hydBores as $D)
            <tr><td>Ø{{ $D }}</td>@foreach ($hydP as $P)<td>{{ $fmt($P * 0.1 * $area($D) / 1000, 1) }}</td>@endforeach<td>{{ $fmt(10 * 16666.67 / $area($D)) }} mm/s</td></tr>
          @endforeach
        </tbody>
      </table></div>
    </div>
  </div>
</section>

{{-- 5) BURKULMA --}}
<section class="hc-sec" id="burkulma" aria-labelledby="h-bur" data-calc="burkulma">
  <div class="hc-sec-in">
    <div class="hc-head"><div><span class="eyebrow">06 · Pnömatik &amp; hidrolik</span><h2 id="h-bur">Piston rodu burkulma hesabı (Euler)</h2><p>Uzun stroklu, itme yükü taşıyan silindirlerde rodun burkulmaya karşı güvenli olup olmadığını kontrol edin.</p></div></div>
    <div class="hc-card">
      <form class="hc-in" onsubmit="return false" aria-label="Burkulma değerleri">
        <div class="hc-f"><label for="b-d">Rot çapı</label><div class="hc-u"><input id="b-d" type="number" inputmode="decimal" min="1" value="20"><em>mm</em></div></div>
        <div class="hc-f"><label for="b-L">Burkulma boyu (L)</label><div class="hc-u"><input id="b-L" type="number" inputmode="decimal" min="1" value="600"><em>mm</em></div></div>
        <div class="hc-f full"><label for="b-k">Bağlantı şekli</label>
          <select id="b-k">
            <option value="2">Bir ucu ankastre, diğeri serbest (Euler 1) — K = 2</option>
            <option value="1" selected>İki ucu mafsallı (Euler 2) — K = 1</option>
            <option value="0.7">Bir ucu ankastre, diğeri mafsallı (Euler 3) — K = 0,7</option>
            <option value="0.5">İki ucu ankastre (Euler 4) — K = 0,5</option>
          </select></div>
        <div class="hc-f"><label for="b-F">İtme yükü</label><div class="hc-u"><input id="b-F" type="number" inputmode="decimal" min="0" value="1200"><em>N</em></div></div>
        <div class="hc-f"><label for="b-S">Emniyet katsayısı</label><div class="hc-u"><input id="b-S" type="number" inputmode="decimal" min="1" step="0.5" value="3.5"><em>S</em></div></div>
        <p class="hc-hint full">L: silindir tamamen açıkken (strok sonunda) rotun yük bağlantı noktası ile silindir bağlantı noktası arası mesafe. Malzeme: çelik, E = 210 000 N/mm².</p>
      </form>
      <div class="hc-out" aria-live="polite">
        <span class="t">Sonuç</span>
        <div class="hc-res">
          <div class="hc-r big"><span>Emniyetli itme yükü</span><b id="b-r1">—</b><small id="b-r1s"></small></div>
          <div class="hc-r"><span>Kritik burkulma yükü</span><b id="b-r2">—</b></div>
          <div class="hc-r"><span>Min. rot çapı (bu yük için)</span><b id="b-r3">—</b></div>
          <div class="hc-r"><span>Serbest burkulma boyu</span><b id="b-r4">—</b></div>
          <div class="hc-r"><span>Narinlik (λ)</span><b id="b-r5">—</b></div>
        </div>
        <div class="hc-note" id="b-note" hidden></div>
        <div class="hc-acts"><a class="btn btn-w" data-quote-b href="{{ $quote }}#form">Krom kaplı mil teklifi {!! $icArr !!}</a></div>
      </div>
    </div>
    <details class="hc-formula"><summary>Formül ve açıklama</summary><div>
      <code>I   = π × d⁴ / 64                 [mm⁴]
Lk  = K × L
Fk  = π² × E × I / Lk²            [N]
F_em = Fk / S
λ   = Lk / (d / 4)</code>
      <ul><li>Euler formülü narin çubuklar için geçerlidir (çelikte yaklaşık λ &gt; 100). Daha kısa rodlarda Tetmajer bölgesi geçerlidir; Euler sonucu iyimser kalabilir.</li><li>Yanal yük, rot ucu boşluğu veya ezilmiş yatak burkulma kapasitesini ciddi biçimde düşürür.</li></ul>
    </div></details>
  </div>
</section>

{{-- 6) BİRİM ÇEVİRİCİ --}}
<section class="hc-sec" id="birim" aria-labelledby="h-bir" data-calc="birim">
  <div class="hc-sec-in">
    <div class="hc-head"><div><span class="eyebrow">07 · Genel</span><h2 id="h-bir">Basınç ve kuvvet birim çevirici</h2><p>Katalog ve etiketlerdeki farklı birimleri tek ekranda karşılaştırın.</p></div></div>
    <div class="hc-cv">
      <div class="hc-card">
        <form class="hc-in" onsubmit="return false" aria-label="Basınç çevirici">
          <div class="hc-f"><label for="u-p">Basınç</label><div class="hc-u"><input id="u-p" type="number" inputmode="decimal" value="6"></div></div>
          <div class="hc-f"><label for="u-pu">Birim</label>
            <select id="u-pu"><option value="1" selected>bar</option><option value="10">MPa</option><option value="0.01">kPa</option><option value="0.0689476">psi</option><option value="0.980665">kgf/cm²</option><option value="1.01325">atm</option></select></div>
        </form>
        <div class="hc-out" aria-live="polite"><span class="t">Karşılıkları</span><table class="hc-tbl" id="u-pt"></table></div>
      </div>
      <div class="hc-card">
        <form class="hc-in" onsubmit="return false" aria-label="Kuvvet çevirici">
          <div class="hc-f"><label for="u-f">Kuvvet</label><div class="hc-u"><input id="u-f" type="number" inputmode="decimal" value="1000"></div></div>
          <div class="hc-f"><label for="u-fu">Birim</label>
            <select id="u-fu"><option value="1" selected>N</option><option value="1000">kN</option><option value="9.80665">kgf</option><option value="9806.65">ton-kuvvet (tf)</option><option value="4.44822">lbf</option></select></div>
        </form>
        <div class="hc-out" aria-live="polite"><span class="t">Karşılıkları</span><table class="hc-tbl" id="u-ft"></table></div>
      </div>
    </div>
    <div class="hc-ref">
      <h3>Basınç dönüşüm tablosu</h3>
      <p>Pnömatikte sık kullanılan (1–10 bar) ve hidrolikte sık kullanılan (100–250 bar) basınçların karşılıkları.</p>
      <div class="hc-rt"><table>
        <thead><tr><th scope="col">bar</th><th scope="col">psi</th><th scope="col">MPa</th><th scope="col">kPa</th><th scope="col">kgf/cm²</th></tr></thead>
        <tbody>
          @foreach ($pressRows as $b)
            <tr><td>{{ $b }} bar</td><td>{{ $fmt($b * 14.5038, 1) }}</td><td>{{ $fmt($b / 10, 1) }}</td><td>{{ $fmt($b * 100) }}</td><td>{{ $fmt($b * 1.01972, 2) }}</td></tr>
          @endforeach
        </tbody>
      </table></div>
    </div>
  </div>
</section>

{{-- SSS --}}
<section class="hc-sec" id="sss" aria-labelledby="h-sss">
  <div class="hc-sec-in">
    <div class="hc-head"><div><span class="eyebrow">Sık sorulanlar</span><h2 id="h-sss">Hesaplamalarla ilgili sık sorulan sorular</h2></div></div>
    <div class="hc-faq">
      @foreach ($faq as $fi => $f)
        <details @if($fi === 0) open @endif><summary>{{ $f[0] }}</summary><p>{{ $f[1] }}</p></details>
      @endforeach
    </div>
    <p class="hc-disc">Hesaplayıcılar ön boyutlandırma içindir; sonuçlar teorik değerlerdir. Kritik uygulamalarda üretici kataloğundaki değerleri esas alın veya teknik ekibimize danışın. Uygulama örnekleri için <a href="{{ url('blogs') }}">blog rehberlerimize</a> göz atabilirsiniz.</p>
    <div class="hc-author">
      <i>EP</i>
      <div><b>Hazırlayan: Ensis Pnömatik teknik ekibi</b><span>Formüller ISO 15552, ISO 6432 ve ISO 21287 standart ölçüleri ile genel pnömatik/hidrolik mühendislik bağıntılarına dayanır. Son güncelleme: <time datetime="{{ $updated }}">{{ \Carbon\Carbon::parse($updated)->format('d.m.Y') }}</time></span></div>
    </div>
  </div>
</section>
  </div>
</div>

<section class="hc-band" aria-labelledby="hc-band-h">
  <div class="wrap hc-band-grid">
    <div>
      <span class="eyebrow" style="background:rgba(255,255,255,.1);color:#9cc6ff">Ön boyutlandırmadan siparişe</span>
      <h2 id="hc-band-h" style="margin-top:12px">Hesabınızı yaptınız; doğru ürünü birlikte seçelim.</h2>
      <ol>
        <li><b>Sonucu gönderin</b>Sonuç kartındaki teklif düğmesi, hesapladığınız ölçüyü forma otomatik yazar.</li>
        <li><b>Teknik teyit</b>Ekibimiz seri, bağlantı tipi ve aksesuarları kontrol eder.</li>
        <li><b>Teklif ve sevkiyat</b>Fiyat ve termin bilgisini en kısa sürede iletir, Bursa'dan sevk ederiz.</li>
      </ol>
    </div>
    <div class="hc-band-acts">
      <a class="btn btn-w" href="{{ $quote }}#form">Teklif iste {!! $icArr !!}</a>
      <a class="btn btn-g" href="https://wa.me/{{ $wa }}?text={{ rawurlencode('Merhaba, hesaplamalar sayfanızdan ulaşıyorum.') }}" target="_blank" rel="noopener">WhatsApp</a>
      <a class="btn btn-g" href="{{ url('urunlerimiz') }}">Ürün gruplarını incele</a>
    </div>
  </div>
</section>

{{-- Hesaplayıcı betiği (master'da @stack('scripts') olmasa da çalışsın diye içerik bölümünde) --}}
<script>
(function () {
  var $ = function (id) { return document.getElementById(id); };
  var num = function (id) { var v = parseFloat(String($(id).value).replace(',', '.')); return isFinite(v) ? v : 0; };
  var f = function (n, d) { return isFinite(n) ? n.toLocaleString('tr-TR', { maximumFractionDigits: d == null ? 0 : d }) : '—'; };
  var set = function (id, t) { var el = $(id); if (el) el.textContent = t; };
  var PI = Math.PI, BORES = {!! json_encode(array_keys($bores)) !!}, RODS = {!! json_encode($bores) !!}, QBASE = {!! json_encode($quote, JSON_UNESCAPED_SLASHES) !!};
  var quote = function (sel, text) { document.querySelectorAll(sel).forEach(function (a) { a.href = QBASE + '?not=' + encodeURIComponent(text) + '#form'; }); };
  var note = function (id, cls, html) { var n = $(id); if (!html) { n.hidden = true; return; } n.hidden = false; n.className = 'hc-note ' + cls; n.innerHTML = html; };
  var used = {};
  var track = function (k) { if (used[k]) return; used[k] = 1; try { if (window.gtag) gtag('event', 'hesaplama_kullan', { hesap: k }); } catch (e) {} };

  // ISO çap seçilince rot çapını doldur
  document.querySelectorAll('[data-bore]').forEach(function (s) {
    var rod = $(s.id.replace('-D', '-d'));
    s.addEventListener('change', function () { rod.value = s.options[s.selectedIndex].getAttribute('data-rod'); });
  });
  var rangeOut = function (id) { set(id + '-o', f(num(id), 1) + ' bar'); };

  /* 1) Kuvvet */
  function kuvvet() {
    rangeOut('k-P');
    var D = num('k-D'), d = Math.min(Math.max(num('k-d'), 0), D - 1), P = num('k-P'), e = num('k-e');
    var A = PI * D * D / 4, Ar = PI * (D * D - d * d) / 4, push = P * 0.1 * A, pull = P * 0.1 * Ar;
    set('k-r1', f(push * e) + ' N'); set('k-r1s', '≈ ' + f(push * e / 9.81) + ' kgf · ' + f(push * e / 1000, 2) + ' kN');
    set('k-r2', f(push) + ' N'); set('k-r2s', '≈ ' + f(push / 9.81) + ' kgf');
    set('k-r3', f(pull) + ' N'); set('k-r3s', '≈ ' + f(pull / 9.81) + ' kgf');
    set('k-r4', f(pull * e) + ' N');
    set('k-r5', f(A / 100, 2) + ' cm²');
    quote('#kuvvet [data-quote]', 'Ø' + D + ' pnömatik silindir, ' + f(P, 1) + ' bar, itme ≈ ' + f(push * e) + ' N');
  }

  /* 2) Çap seçimi */
  function cap() {
    rangeOut('c-P');
    var m = num('c-m'), dir = $('c-dir').value, mu = num('c-mu'), k = num('c-v'), P = num('c-P');
    $('c-mu-f').hidden = dir !== 'h';
    var Fl = (dir === 'h' ? mu : 1) * m * 9.81, Fr = Fl / k;
    var Dmin = Math.sqrt(4 * Fr / (PI * P * 0.1));
    var D = null; for (var i = 0; i < BORES.length; i++) if (BORES[i] >= Dmin) { D = BORES[i]; break; }
    set('c-r2', f(Fr) + ' N'); set('c-r2s', 'Yükün kuvveti ' + f(Fl) + ' N · oran %' + f(k * 100));
    set('c-r3', 'Ø' + f(Dmin, 1) + ' mm');
    if (!m || !P) { set('c-r1', '—'); set('c-r1s', ''); set('c-r4', '—'); set('c-r5', '—'); note('c-note'); return; }
    if (D === null) {
      set('c-r1', '> Ø320'); set('c-r1s', 'Standart pnömatik aralığın dışında');
      set('c-r4', '—'); set('c-r5', '—');
      note('c-note', 'warn', 'Bu yük için pnömatik silindir çok büyük kalıyor. Birden fazla silindir, daha yüksek basınç veya <a href="#hidrolik" style="color:#fff;text-decoration:underline">hidrolik silindir</a> düşünün.');
      return;
    }
    var Fd = P * 0.1 * PI * D * D / 4, ratio = Fl / Fd;
    set('c-r1', 'Ø' + D + ' mm'); set('c-r1s', 'ISO rot Ø' + RODS[D] + ' mm · ' + (D <= 25 ? 'ISO 6432 / 21287 mini silindir' : (D <= 100 ? 'ISO 15552 veya kompakt (ISO 21287)' : 'ISO 15552 profil / bağlantı çubuklu')));
    set('c-r4', f(Fd) + ' N'); set('c-r5', '%' + f(ratio * 100));
    note('c-note', 'ok', dir === 'v' ? 'Dikey kaldırmada çekme yönünde kuvvetin rot alanı kadar azaldığını unutmayın; yük çekme yönünde taşınıyorsa bir üst çapı kontrol edin.' : 'Yatay harekette yükü kılavuz/kızakla taşıyın; silindir rotuna yanal yük bindirmeyin.');
    quote('#cap-secimi [data-quote]', 'Ø' + D + ' pnömatik silindir, ' + f(m) + ' kg yük, ' + f(P, 1) + ' bar');
  }

  /* 3) Hava tüketimi */
  function hava() {
    rangeOut('h-P');
    var D = num('h-D'), d = Math.min(Math.max(num('h-d'), 0), D - 1), s = num('h-s'), P = num('h-P'), c = num('h-c'), n = Math.max(1, num('h-n')), dbl = $('h-type').value === '2';
    var A = PI * D * D / 4, Ar = PI * (D * D - d * d) / 4, ratio = (P + 1.013) / 1.013;
    var cyc = (A + (dbl ? Ar : 0)) * s * 1e-6 * ratio;   // Nl / çevrim
    var q = cyc * c * n, comp = q * 1.3;
    set('h-r1', f(q, 1) + ' Nl/dk'); set('h-r1s', f(q / 1000, 3) + ' Nm³/dk');
    set('h-r2', f(cyc, 2) + ' Nl'); set('h-r3', f(q * 60 / 1000, 2) + ' Nm³/h');
    set('h-r4', f(comp, 0) + ' Nl/dk'); set('h-r4s', 'Kaba tahmin: ≈ ' + f(Math.max(comp / 120, 0), 1) + ' kW vidalı kompresör (7–8 bar)');
  }

  /* 4) Hidrolik */
  function hidrolik() {
    var D = num('y-D'), d = Math.min(Math.max(num('y-d'), 0), D - 0.1), P = num('y-P'), Q = num('y-Q'), s = num('y-s');
    var A = PI * D * D / 4, Ar = PI * (D * D - d * d) / 4;
    var Fp = P * 0.1 * A, Fc = P * 0.1 * Ar, vp = Q * 16666.67 / A, vc = Q * 16666.67 / Ar;
    set('y-r1', f(Fp / 1000, 1) + ' kN'); set('y-r1s', '≈ ' + f(Fp / 9806.65, 2) + ' ton · ' + f(Fp) + ' N');
    set('y-r2', f(Fc / 1000, 1) + ' kN'); set('y-r2s', '≈ ' + f(Fc / 9806.65, 2) + ' ton');
    set('y-r3', f(A / Ar, 2));
    set('y-r4', f(vp) + ' mm/s'); set('y-r4s', s && vp ? 'Strok süresi ≈ ' + f(s / vp, 1) + ' s' : '');
    set('y-r5', f(vc) + ' mm/s'); set('y-r5s', s && vc ? 'Strok süresi ≈ ' + f(s / vc, 1) + ' s' : '');
    if (vp > 500) note('y-note', 'warn', 'İleri hız 0,5 m/s üzerinde: standart contalar için yüksek; yastıklama ve conta seçimini kontrol edin.');
    else if (P > 250) note('y-note', 'warn', '250 bar üzeri basınçta boru et kalınlığı ve contalar ağır seri seçilmelidir.');
    else note('y-note');
    quote('#hidrolik [data-quote-h]', 'Hidrolik silindir Ø' + f(D) + '/Ø' + f(d) + ' x ' + f(s) + ' mm, ' + f(P) + ' bar — honlanmış boru / krom mil');
  }

  /* 5) Burkulma */
  function burkulma() {
    var d = num('b-d'), L = num('b-L'), K = num('b-k'), F = num('b-F'), S = Math.max(1, num('b-S')), E = 210000;
    var I = PI * Math.pow(d, 4) / 64, Lk = K * L, Fk = PI * PI * E * I / (Lk * Lk), Fs = Fk / S, lam = Lk / (d / 4);
    var dmin = Math.pow(64 * S * F * Lk * Lk / (PI * PI * PI * E), 0.25);
    set('b-r1', f(Fs) + ' N'); set('b-r1s', '≈ ' + f(Fs / 9.81) + ' kgf');
    set('b-r2', f(Fk) + ' N'); set('b-r3', F ? 'Ø' + f(dmin, 1) + ' mm' : '—');
    set('b-r4', f(Lk) + ' mm'); set('b-r5', f(lam));
    if (!d || !L) { note('b-note'); return; }
    var msg = F <= Fs ? ['ok', '✓ Güvenli: ' + f(F) + ' N yük emniyetli sınırın altında (kullanım %' + f(F / Fs * 100) + ').']
                      : ['bad', '✗ Riskli: yük emniyetli sınırı aşıyor. Rot çapını en az Ø' + f(Math.ceil(dmin)) + ' mm\'ye çıkarın veya bağlantı şeklini iyileştirin.'];
    if (lam < 100) msg[1] += ' Narinlik λ = ' + f(lam) + ' < 100: Euler bu bölgede iyimserdir, Tetmajer ile kontrol önerilir.';
    note('b-note', msg[0], msg[1]);
    quote('#burkulma [data-quote-b]', 'Krom kaplı mil Ø' + f(Math.max(d, Math.ceil(dmin))) + ' mm, boy ' + f(L) + ' mm');
  }

  /* 6) Birim çevirici */
  var PU = [['bar', 1, 3], ['MPa', 10, 4], ['kPa', 0.01, 1], ['psi', 0.0689476, 2], ['kgf/cm²', 0.980665, 3], ['atm', 1.01325, 3]];
  var FU = [['N', 1, 1], ['kN', 1000, 4], ['kgf', 9.80665, 2], ['ton-kuvvet', 9806.65, 4], ['lbf', 4.44822, 2]];
  function table(id, list, base) { $(id).innerHTML = list.map(function (u) { return '<tr><td>' + u[0] + '</td><td>' + f(base / u[1], u[2]) + '</td></tr>'; }).join(''); }
  function birim() { table('u-pt', PU, num('u-p') * num('u-pu')); table('u-ft', FU, num('u-f') * num('u-fu')); }

  /* 0) Muadil kod bulucu */
  var CAT = {!! json_encode($muadilCats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}, WABASE = {!! json_encode('https://wa.me/' . $wa, JSON_UNESCAPED_SLASHES) !!};
  var ISO_B = [4, 6, 8, 10, 12, 16, 20, 25, 32, 40, 50, 63, 80, 100, 125, 160, 200, 250, 320];
  var T = {
    iso:    ['ISO pnömatik silindir', 'ISO 15552', 'iso'],
    mini:   ['Mini yuvarlak silindir', 'ISO 6432', 'mini'],
    round:  ['Yuvarlak gövdeli silindir', 'Üretici standardı', 'mini'],
    inox:   ['Paslanmaz çelik silindir', 'ISO 6432 (paslanmaz)', 'inox'],
    comp:   ['Kompakt (kısa stroklu) silindir', 'ISO 21287 / kompakt', 'comp'],
    guide:  ['Kılavuzlu silindir / kızak', 'Üretici standardı', 'guide'],
    rodless:['Rotsuz silindir', 'Üretici standardı', 'rodless'],
    rotary: ['Döner aktüatör', 'Üretici standardı', 'rotary'],
    grip:   ['Pnömatik tutucu (gripper)', 'Üretici standardı', 'grip'],
    valve:  ['Solenoid / yön kontrol valfi', 'Bağlantı ağzı ve debiye göre', 'valve'],
    frl:    ['Şartlandırıcı / filtre-regülatör', 'Bağlantı ağzı ve debiye göre', 'frl'],
    vac:    ['Vakum bileşeni', 'Vantuz çapı / ejektör debisi', 'vac'],
    fit:    ['Pnömatik bağlantı parçası (rakor)', 'Hortum çapı ve diş ölçüsü', 'fit']
  };
  var RULES = [
    // Festo
    [/^(DSBC|DNCB|DNC|DSBG|DNGU|DNG|DSBF)(?![A-Z])/, 'Festo', 'iso'],
    [/^(DSNU|DSNUP|ESNU|DSN|ESN)(?![A-Z])/, 'Festo', 'mini'],
    [/^(ADN|AEN|ADNGF|ADVU|AEVU|ADVC|AEVC|ADNP)(?![A-Z])/, 'Festo', 'comp'],
    [/^(DFM|DGST|DGSL|SLT|DFPB?|DGRF)(?![A-Z])/, 'Festo', 'guide'],
    [/^(DGC|DGPL?|SLG)(?![A-Z])/, 'Festo', 'rodless'],
    [/^(DSM|DRRD|DRVS|DSR)(?![A-Z])/, 'Festo', 'rotary'],
    [/^(DHPS|HGPT?|HGPL|DHDS|DHWS|DHRS|HGW|HGR)(?![A-Z])/, 'Festo', 'grip'],
    [/^(VUVG|VUVS|VUVE|JMFH|MFH|MEH|VSVA|VTUG|CPE\d*|VUVB)(?![A-Z])/, 'Festo', 'valve'],
    [/^(MS\d|LFR|LFRS|LR|LRP|FRC|LFMA?|MSB)(?![A-Z])/, 'Festo', 'frl'],
    [/^(QS|QSL|QSY|QST|QSM|QSLV|QSF|QSW)(?![A-Z])/, 'Festo', 'fit'],
    [/^(ESG|ESS|ESV|VAS|VASB|VN|OVEM|VAD)(?![A-Z])/, 'Festo', 'vac'],
    // SMC
    [/^(CP96|CDP96|C96|CD96|C95|CD95)/, 'SMC', 'iso'],
    [/^(C85|CD85)/, 'SMC', 'mini'],
    [/^(CQ2|CDQ2|CQS|CDQS|CQM|CDQM|C55|CD55)/, 'SMC', 'comp'],
    [/^(CJ2|CDJ2|CM2|CDM2|CG1|CDG1|CJP|CDJP)/, 'SMC', 'round'],
    [/^(MGPM|MGPL|MGPA|MGP|MGQ|MXS|MXQ|CXSM|CXS|MGG)/, 'SMC', 'guide'],
    [/^(CY1S|CY1L|CY1|CY3|MY1|MY3|CY3B|CY3R)/, 'SMC', 'rodless'],
    [/^(CRB|MSQ|CRQ2|MSUB|CRA1|CDRB|CDRA1)/, 'SMC', 'rotary'],
    [/^(MHZ2|MHZL2|MHZ|MHF2|MHS|MHL2|MHC2|MHK2|MHY2)/, 'SMC', 'grip'],
    [/^(SYJ|SY\d|VF\d|VFS|VQZ|VQC|VQ\d|SV\d|VP\d|VZ\d)/, 'SMC', 'valve'],
    [/^(AWM|AFM|AW\d|AC\d|AF\d|ITV|IR\d)/, 'SMC', 'frl'],
    [/^(KQ2|KQB2|KQG2|KJ[A-Z]|KQ)/, 'SMC', 'fit'],
    [/^(ZP\d|ZP[A-Z]|ZH\d|ZU\d|ZX|ZK2|ZQ)/, 'SMC', 'vac'],
    // Airtac
    [/^(SCD|SC|SI|SE|SAI|SU)\d/, 'Airtac', 'iso'],
    [/^(MAL|MI)\d/, 'Airtac', 'mini'],
    [/^(MAC|MAJ|MA)\d/, 'Airtac', 'inox'],
    [/^(SDAJ|SDA|ACQ|ACE|ACP)\d/, 'Airtac', 'comp'],
    [/^(TCL|TCM|TCQ|TN|TR|HLQ|HLS|HLF)\d/, 'Airtac', 'guide'],
    [/^(RMS|RMT|RMH)\d/, 'Airtac', 'rodless'],
    [/^(HRQ|ACK|MRQ)\d/, 'Airtac', 'rotary'],
    [/^(HFZ|HFK|HFR|HFY|HFT|HFP)\d/, 'Airtac', 'grip'],
    [/^(4V|4M|3V|4A|4HV|7V|4R)\d/, 'Airtac', 'valve'],
    [/^(GFR|GR|GF|GC|BFC|BFR|BC|AFC|AFR)\d/, 'Airtac', 'frl'],
    [/^(PCF|PLF|PC|PL|PU|PE|PY|PB)\d/, 'Airtac', 'fit'],
    // Camozzi
    [/^(6[0-3]|40|41)M\d/, 'Camozzi', 'iso'],
    [/^2[457]N\d/, 'Camozzi', 'mini'],
    [/^(31|32)[MR]\d/, 'Camozzi', 'comp'],
    // Norgren / IMI
    [/^(PRA|RA|RT)\//, 'Norgren (IMI)', 'iso'],
    [/^RM\//, 'Norgren (IMI)', 'mini'],
    // Parker
    [/^P1D/, 'Parker', 'iso'],
    [/^P1A/, 'Parker', 'mini'],
    // Mindman (yerli dağıtımda yaygın)
    [/^(MCQV|MCMI|MCQI|MCQA)/, 'Mindman', 'iso'],
    [/^(MCMA|MCMB|MCJA|MCMJ)/, 'Mindman', 'mini']
  ];
  function dims(c, kind) {
    var m;
    if ((m = c.match(/^(?:6[0-3]|4[01])M\d[A-Z]?(\d{3})[A-Z](\d{4})/))) return [+m[1], +m[2]];            // Camozzi 61M2P040A0100
    if ((m = c.match(/^2[457]N\d[A-Z]?(\d{2,3})[A-Z](\d{2,4})/))) return [+m[1], +m[2]];                     // Camozzi 24N2A25A100
    if ((m = c.match(/^(?:PRA|RA|RT|RM|QM|QA)\/(\d{4})\/[A-Z]*\/?(\d{1,4})?/))) return [+m[1] - 8000, m[2] ? +m[2] : null]; // Norgren RA/8050/M/200
    if ((m = c.match(/^P1[DAQ]-?[A-Z]?(\d{3})[A-Z0-9]*-(\d{4})/))) return [+m[1], +m[2]];                     // Parker P1D-B050MS-0200
    var re = /(?:^|[^0-9])(\d{1,3})(?:\s*[-X×*\/]\s*|[A-Z]{0,2}-)(\d{1,4})(?!\d)/g, x;
    while ((x = re.exec(c))) { if (ISO_B.indexOf(+x[1]) > -1) return [+x[1], +x[2]]; }
    if ((m = c.match(/(?:^|[^0-9])(\d{1,3})(?![0-9])/)) && ISO_B.indexOf(+m[1]) > -1 && ['grip', 'rotary', 'vac'].indexOf(kind) < 0) return [+m[1], null];
    return [null, null];
  }
  function muadil(ev) {
    if (ev) ev.preventDefault();
    var raw = $('m-code').value.trim(); if (!raw) return;
    var c = raw.toUpperCase().replace(/\s*[X×]\s*/g, 'X').replace(/\s+/g, '-').replace(/-+/g, '-');
    c = c.replace(/^(FESTO|SMC|AIRTAC|CAMOZZI|NORGREN|IMI|PARKER|MINDMAN)-?/, '');   // "Festo DNC 63 x 250" gibi yazımlar
    var cs = c.replace(/-/g, '');
    var hit = null;
    for (var i = 0; i < RULES.length && !hit; i++) if (RULES[i][0].test(c) || RULES[i][0].test(cs)) hit = RULES[i];
    var kind = hit ? hit[2] : null, cyl = !kind || ['iso', 'mini', 'round', 'inox', 'comp', 'guide', 'rodless'].indexOf(kind) > -1;
    var d = cyl ? dims(c, kind) : [null, null], bore = d[0], stroke = d[1];
    if (!hit && bore) kind = 'iso';
    $('m-empty').hidden = !!kind; $('m-res').hidden = !kind;
    var msg = 'Merhaba, ' + raw + ' kodlu ürünün muadili için fiyat ve termin almak istiyorum.';
    $('m-quote').href = QBASE + '?not=' + encodeURIComponent(raw + ' — muadil talebi') + '#form';
    $('m-wa').href = WABASE + '?text=' + encodeURIComponent(msg);
    try { if (window.gtag) gtag('event', 'muadil_ara', { marka: hit ? hit[1] : 'bilinmiyor', tip: kind || 'yok' }); } catch (e) {}
    if (!kind) {
      $('m-empty').className = 'hc-note warn';
      $('m-empty').innerHTML = '<b>' + raw.replace(/[<>&"]/g, '') + '</b> kodunu otomatik çözemedik. Kodu teknik ekibimize gönderin, muadilini sizin için bulalım.';
      $('m-prod').hidden = true; return;
    }
    var t = T[kind], cat = CAT[t[2]];
    set('m-r1', t[0]); set('m-r1s', hit ? hit[1] + ' · ' + (function (m) { return (m[1] || m[0]).replace(/\/$/, ''); })(c.match(hit[0]) || cs.match(hit[0])) + ' serisi' : 'Seri tanınmadı; ölçüler koddan okundu');
    set('m-r2', hit ? hit[1] : 'Bilinmiyor'); set('m-r3', t[1]);
    $('m-bore-r').hidden = !bore; $('m-stroke-r').hidden = !stroke;
    if (bore) { set('m-r4', 'Ø' + bore + ' mm'); set('m-r4s', ['iso', 'mini', 'round', 'inox', 'comp', 'guide'].indexOf(kind) > -1 ? '6 bar itme ≈ ' + f(0.6 * PI * bore * bore / 4) + ' N' : ''); }
    if (stroke) set('m-r5', stroke + ' mm');
    var isoKind = ['iso', 'mini', 'comp', 'inox'].indexOf(kind) > -1;
    var n = isoKind
      ? '✓ ' + t[1] + ' standardında bağlantı ölçüleri tüm üreticilerde aynıdır; muadil ürün mevcut bağlantılarınıza birebir oturur.'
      : (['valve', 'frl', 'fit', 'vac'].indexOf(kind) > -1
          ? 'Bu ürün grubunda bağlantı ağzı, debi' + (kind === 'valve' ? ', bobin gerilimi' : '') + ' ve montaj tipi karşılaştırılmalı; teknik ekibimiz kodu teyit ederek muadili önerir.'
          : 'Bu seri ISO standardında değil; bağlantı ölçüleri ve kurs uzunluğu teyit edilerek en yakın muadil önerilir.');
    if (isoKind && bore && ISO_B.indexOf(bore) > -1 && RODS[bore]) n += ' Standart rot Ø' + RODS[bore] + ' mm.';
    $('m-note').className = 'hc-note ' + (isoKind ? 'ok' : '');
    $('m-note').innerHTML = n + (bore && isoKind ? ' <a href="#kuvvet" data-to-kuvvet="' + bore + '" style="color:#fff;text-decoration:underline">Bu çapla kuvvet hesapla →</a>' : '');
    var prod = $('m-prod'), img = $('m-img'), iw = $('m-img-w');
    if (cat) {
      prod.hidden = false; prod.href = cat[0]; set('m-prod-t', cat[1]);
      if (cat[2]) { iw.classList.remove('noimg'); img.alt = cat[1] + ' — temsili görsel'; img.onerror = function () { iw.classList.add('noimg'); }; if (img.getAttribute('src') !== cat[2]) img.src = cat[2]; }
      else { iw.classList.add('noimg'); img.removeAttribute('src'); }
    } else prod.hidden = true;
  }
  $('m-form').addEventListener('submit', muadil);
  document.querySelectorAll('[data-ex]').forEach(function (b) { b.addEventListener('click', function () { $('m-code').value = b.getAttribute('data-ex'); muadil(); if (b.closest('.hc-xr')) document.getElementById('muadil').scrollIntoView({ behavior: 'smooth', block: 'start' }); }); });
  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('[data-to-kuvvet]'); if (!a) return;
    var sel = $('k-D'); sel.value = a.getAttribute('data-to-kuvvet'); sel.dispatchEvent(new Event('change'));
  });
  try { var qs = new URLSearchParams(location.search).get('kod'); if (qs) { $('m-code').value = qs.slice(0, 60); muadil(); } } catch (e) {}

  /* Sonucu kopyala (e-posta / WhatsApp'a yapıştırmak için) */
  var icCopy = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/></svg>';
  document.querySelectorAll('.hc-sec[id] .hc-out .hc-acts').forEach(function (acts) {
    var sec = acts.closest('.hc-sec'), btn = document.createElement('button');
    btn.type = 'button'; btn.className = 'hc-copy'; btn.innerHTML = icCopy + '<span>Sonucu kopyala</span>';
    acts.appendChild(btn);
    btn.addEventListener('click', function () {
      var L = [sec.querySelector('h2').textContent.trim()];
      sec.querySelectorAll('.hc-in .hc-f').forEach(function (fl) {
        if (fl.hidden) return;
        var lab = fl.querySelector('label,span.l'), el = fl.querySelector('input,select'); if (!lab || !el) return;
        var v = el.tagName === 'SELECT' ? el.options[el.selectedIndex].text : el.value + (fl.querySelector('em') ? ' ' + fl.querySelector('em').textContent : (el.type === 'range' ? ' bar' : ''));
        if (v.trim()) L.push('• ' + lab.textContent.trim() + ': ' + v.trim());
      });
      L.push('— Sonuç —');
      sec.querySelectorAll('.hc-out .hc-r').forEach(function (rr) {
        if (rr.hidden || rr.closest('[hidden]')) return;
        var a = rr.querySelector('span'), b = rr.querySelector('b'), sm = rr.querySelector('small');
        if (b && b.textContent.trim() !== '—') L.push('• ' + a.textContent.trim() + ': ' + b.textContent.trim() + (sm && sm.textContent ? ' (' + sm.textContent.trim() + ')' : ''));
      });
      sec.querySelectorAll('.hc-out .hc-tbl tr').forEach(function (tr) { L.push('• ' + tr.textContent.replace(/\s+/g, ' ').trim()); });
      if (sec.id === 'muadil') { var pt = $('m-prod-t'); if (!$('m-prod').hidden) L.push('• Ensis muadil grubu: ' + pt.textContent + ' — ' + $('m-prod').href); L.push('Not: Otomatik yorumdur; kesin muadil teknik teyitle belirlenir.'); }
      L.push(location.origin + location.pathname + '#' + sec.id);
      var txt = L.join('\n'), lbl = btn.querySelector('span');
      var done = function () { lbl.textContent = 'Kopyalandı ✓'; setTimeout(function () { lbl.textContent = 'Sonucu kopyala'; }, 1800); };
      if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(txt).then(done, function () {});
      else { var ta = document.createElement('textarea'); ta.value = txt; ta.style.position = 'fixed'; ta.style.opacity = '0'; document.body.appendChild(ta); ta.select(); try { document.execCommand('copy'); done(); } catch (e) {} ta.remove(); }
      try { if (window.gtag) gtag('event', 'hesap_kopyala', { hesap: sec.id }); } catch (e) {}
    });
  });

  var map = { kuvvet: kuvvet, 'cap-secimi': cap, hava: hava, hidrolik: hidrolik, burkulma: burkulma, birim: birim };
  document.querySelectorAll('[data-calc]').forEach(function (sec) {
    var k = sec.getAttribute('data-calc'), fn = map[k];
    sec.querySelectorAll('input,select').forEach(function (el) {
      el.addEventListener('input', fn); el.addEventListener('change', function () { fn(); track(k); });
    });
    fn();
  });

  // Sekme şeridi: görünen bölümü vurgula
  var tabs = document.querySelectorAll('[data-tab]');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (e) {
        if (!e.isIntersecting) return;
        tabs.forEach(function (t) {
          var on = t.getAttribute('data-tab') === e.target.id; t.classList.toggle('on', on);
          if (on && t.closest('.hc-tabs')) { var ul = t.closest('ul'); if (ul.scrollTo && ul.offsetParent) ul.scrollTo({ left: t.offsetLeft - 16, behavior: 'smooth' }); }
        });
      });
    }, { rootMargin: '-45% 0px -50% 0px' });
    document.querySelectorAll('.hc-sec[id]').forEach(function (sx) { io.observe(sx); });
  }
})();

  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('a[href="#muadil-ret"]');
    if (a) { var d = document.querySelector('.hc-disc-more'); if (d) d.open = true; }
  });
</script>
@endsection
