@extends('layouts.master')

{{--
|==========================================================================
| FLUTECNICA — ANA SAYFA (home.blade.php)
| Sürüm: 2026-10 · SIGNATURE UI/UX + SEO / GEO (AI arama) uyumu · rev. 20
|--------------------------------------------------------------------------
| • Tüm sayfa verisi (SSS, karşılaştırma tablosu, ürün kartları, schema)
|   tek bir PHP veri bloğundan beslenir → görünen içerik ile JSON-LD daima aynı.
| • JSON-LD PHP dizisinden json_encode ile üretilir (JSON_HEX_TAG → güvenli).
| • Organization / LocalBusiness düğümü layouts.master içindedir;
|   bu sayfa yalnızca "@id" ile ona referans verir (çift tanım yok).
| • rev. 19: O-Ring / Keçe Yuva hesaplayıcı kaldırıldı (HTML, CSS, JS, JSON-LD).
|   Ürün karşılaştırma tablosu yeniden tasarlandı (sabit sütun genişliği,
|   mobil/tablet kart görünümü, GEO "kısa cevap" paragrafı).
|   Teklif formuna sunucu doğrulamalı "işlem sorusu" (spam koruması) eklendi:
|   alanlar `cq` (şifreli token) ve `cevap` — POST /teklif-iste tarafında
|   doğrulanmalıdır (bkz. teslim notu).
| • rev. 21: Kurumsal animasyon katmanı (sayfa sonunda "v21 ANİMASYON KATMANI"): hero maske açılışı, sayaç, fare ışığı, 3D konsol, bölüm çizgileri vb.
|   Yalnız transform/opacity, prefers-reduced-motion'da kapalı, JS yoksa
|   içerik görünür. Mevcut HTML/CSS/JS değişmedi.
|==========================================================================
--}}

@php
    /* ── Temel ── */
    $siteUrl     = 'https://www.flutecnica.com';
    $pageUrl     = $siteUrl . '/';
    $pageUpdated = '2026-10-26';
    $pageTitle   = 'Sert Krom Kaplı Mil, İndüksiyonlu Mil | NIMET Türkiye | Flutecnica';
    $pageDesc    = 'NIMET Türkiye yetkili distribütörü Flutecnica: sert krom kaplı mil, CK45/CF53 indüksiyonlu mil, H8 honlanmış boru. Ø4–200 mm stok, özel kesim, 24–48 saatte sevkiyat.';

    /* ── Hızlı bilgiler (GEO: yapay zekâ motorlarının alıntıladığı net olgular) ── */
    $flowSteps = [
        ['t' => 'Stok',     'big' => 'Ø4–200', 'unit' => 'mm',   'k' => 'Stok çap aralığı', 'v' => 'NIMET krom kaplı mil ve honlanmış boru; Ø4 mm – Ø200 mm arası Bursa ve İstanbul stoklarında'],
        ['t' => 'Kesim',    'big' => '1',      'unit' => 'adet', 'k' => 'Minimum sipariş',  'v' => 'Makine ölçüsüne göre özel boy kesim; stoktaki ürünlerde tek adet sipariş'],
        ['t' => 'Sevkiyat', 'big' => '2',      'unit' => 'şube', 'k' => 'Şubeler',          'v' => 'Bursa (merkez, Nilüfer) ve İstanbul şubelerinden sevkiyat'],
        ['t' => 'Teslim',   'big' => '24–48',  'unit' => 'saat', 'k' => 'Teslimat',         'v' => 'Türkiye geneli 24–48 saat · Marmara Bölgesi aynı gün'],
    ];
    $keyFacts = [
        ['k' => 'Yetkili marka',   'v' => 'NIMET — Türkiye yetkili distribütörü (EU sertifikalı üretim)'],
        ['k' => 'Çözüm ortakları', 'v' => 'NIMET, Univer, TOX Pressotechnik, Medan'],
    ];

    /* ── Ürün karşılaştırma tablosu (yalnızca sayfada zaten beyan edilen değerler) ── */
    $specRows = [
        ['urun' => 'Sert Krom Kaplı Mil',            'url' => url('/sert-krom-kapli-mil'),                         'tol' => 'ISO f7',     'sert' => 'Krom kaplama min. 20 µm', 'ra' => 'Ra ≤ 0,20 µm', 'kul' => 'Hidrolik/pnömatik silindir piston mili (keçe teması)'],
        ['urun' => 'CK45 İndüksiyonlu Krom Kaplı Mil','url' => url('/induksiyonlu-krom-kaplamali-mil'),            'tol' => 'ISO f7',     'sert' => '58 ± 3 HRC',              'ra' => 'Ra ≤ 0,20 µm', 'kul' => 'Darbe / aşınma riski yüksek silindir milleri, rulman yürüyüşü'],
        ['urun' => 'CF53 İndüksiyonlu Kromlu Mil',   'url' => url('/cf53-induksiyonlu-kromlu-mil'),                'tol' => 'ISO h7',     'sert' => '62 ± 2 HRC',              'ra' => '—',            'kul' => 'Rulman yürüyüşlü lineer sistemler'],
        ['urun' => 'Honlanmış Boru (H8)',            'url' => url('/honlanmis-boru-hidrolik-silindir-borusu-h8'),  'tol' => 'ISO H8 (iç)', 'sert' => '—',                      'ra' => 'Ra ≤ 0,4 µm (iç)', 'kul' => 'Hidrolik silindir gövdesi'],
        ['urun' => 'Krom Kaplı Rod Boru',            'url' => url('/krom-kapli-rod-boru'),                         'tol' => 'ISO f7 / f8','sert' => 'Krom kaplı (içi boş)',    'ra' => 'Ra ≤ 0,20 µm', 'kul' => 'Uzun stroklu silindirler — dolu mile göre %25–35 daha hafif'],
    ];

    /* GEO: tablonun üstünde alıntılanabilir kısa cevap (yalnız sayfada beyan edilen olgular) */
    $specAnswer = 'Keçe temaslı hidrolik silindir piston milinde sert krom kaplı mil veya CK45 indüksiyonlu krom kaplı mil (ISO f7, Ra ≤ 0,20 µm) seçilir. Mil üzerinde rulman yürüyecekse indüksiyonlu mil kullanılır: CK45 (58 ± 3 HRC) ya da CF53 (62 ± 2 HRC, ISO h7). Silindir gövdesi için H8 honlanmış boru, uzun stroklu silindirlerde ağırlığı %25–35 azaltmak için krom kaplı rod boru tercih edilir.';

    /* ── SSS (tek kaynak: hem görünür liste hem FAQPage şeması) ── */
    $faqs = [
        ['id' => 'sss-mil-farki',     'q' => 'Sert krom kaplı mil, CK45 indüksiyonlu krom kaplı mil ve CF53 indüksiyonlu kromlu mil arasındaki fark nedir?',
         'a' => 'Sert krom kaplı mil ve CK45 indüksiyonlu krom kaplı mil (58±3 HRC) ISO f7 toleransla keçe temasındaki piston mili olarak kullanılır; minimum 20 mikron krom kaplama ve Ra ≤ 0.20 μm yüzey kalitesiyle hidrolik silindirlerde tercih edilir. CF53 indüksiyonlu mil ise ISO h7 tolerans ve 62±2 HRC sertlikle rulman yürüyüşlü lineer sistemler için tasarlanmıştır.'],
        ['id' => 'sss-stok',          'q' => 'Hangi çap aralığında ve boylarda stok bulunuyor?',
         'a' => 'NIMET marka stoklarımız Ø4 mm\'den Ø200 mm\'ye kadar geniş çap aralığını kapsar. Standart boyların dışında makine ölçüsüne göre özel kesim de yapılır. Bursa merkez ve İstanbul şubemizden 24–48 saat içinde teslimat sağlanır.'],
        ['id' => 'sss-honlanmis',     'q' => 'Honlanmış boru (H8) nedir ve nerede kullanılır?',
         'a' => 'Honlanmış boru, iç yüzeyi honlama yöntemiyle işlenmiş ISO H8 toleranslı hidrolik silindir borusudur. Ra ≤ 0.4 μm iç yüzey kalitesi ve çapraz honlama dokusuyla piston keçesinin sızdırmazlığını ve ömrünü artırır; hidrolik silindir gövdesi üretiminde kullanılır.'],
        ['id' => 'sss-rulman',        'q' => 'Mil üzerinde rulman yürüyecekse hangi ürünü seçmeliyim?',
         'a' => 'Mil üzerinde rulman yürüyecekse sert krom kaplı mil yerine indüksiyonlu mil (CK45 veya CF53) tercih edilmelidir. İndüksiyon sertleştirme yüzeyde 58–62 HRC sertlik sağlayarak rulman baskısına ve aşınmaya dayanır.'],
        ['id' => 'sss-rekrom',        'q' => 'Krom kaplama yenilenebilir (re-krom) mi?',
         'a' => 'Evet. Kaplama zamanla incelir veya çizilirse mil yüzeyi taşlanıp yeniden krom kaplanabilir. Bu işlem mili tamamen yenilemeye göre daha ekonomiktir.'],
        ['id' => 'sss-siparis',       'q' => 'Minimum sipariş miktarı var mı ve teslimat ne kadar sürer?',
         'a' => 'Stoktaki ürünler için tek adet sipariş kabul edilir. Marmara Bölgesi\'ne aynı gün, Türkiye geneline 24–48 saat içinde teslimat yapılır. Özel boyut ve seri üretim talepleri için teknik ekibimizle iletişime geçebilirsiniz.'],
        ['id' => 'sss-rod-boru',      'q' => 'Krom kaplı rod boru ile dolu mil arasındaki fark nedir?',
         'a' => 'Krom kaplı rod boru, içi boş (boru) formda krom kaplı bir mildir ve dolu mile göre %25–35 daha hafiftir. Uzun stroklu hidrolik silindirlerde ağırlığı ve eylemsizliği azaltmak için tercih edilir; ISO f7/f8 tolerans ve Ra ≤ 0.20 μm yüzeyle keçe uyumludur.'],
        ['id' => 'sss-distributor',   'q' => 'Flutecnica NIMET\'in yetkili distribütörü müdür?',
         'a' => 'Evet. Flutecnica, NIMET\'in Türkiye yetkili distribütörüdür. Tüm mil ve borular Avrupa (EU) sertifikalı NIMET üretimi olup kalite belgeleriyle birlikte stoktan sevk edilir.'],
        ['id' => 'sss-f7-h8',         'q' => 'f7 mil toleransı ile H8 delik toleransı arasındaki fark nedir?',
         'a' => 'f7 küçük harfle gösterilen bir mil (şaft) toleransıdır ve sapması nominal ölçünün altında kalır (boşluklu geçme eğilimi). H8 ise büyük harfle gösterilen bir delik toleransıdır ve sapması nominal ölçünün üstündedir. H8/f7 kombinasyonu klasik boşluklu (hareketli) geçme oluşturur.'],
        ['id' => 'sss-harf',          'q' => 'Tolerans sınıfında küçük harf mi büyük harf mi kullanılır?',
         'a' => 'ISO 286\'da küçük harfler (d, e, f, g, h, js, k, m, n, p) mil/şaft toleranslarını; büyük harfler (D, E, F, G, H, JS, K, M, N, P) delik toleranslarını ifade eder. Geçme analizi yapabilmek için en az bir mil ve bir delik sınıfı gerekir.'],
        ['id' => 'sss-gecme-turleri', 'q' => 'Boşluklu, geçiş ve sıkı geçme arasındaki fark nedir?',
         'a' => 'Boşluklu geçmede delik daima milden büyüktür (hareket serbestliği). Sıkı geçmede mil daima delikten büyüktür (pres/montaj kuvveti gerekir). Geçiş geçmesinde tolerans aralığına göre hem boşluk hem sıkılık oluşabilir.'],
    ];

    /* ── Görsel yardımcı (fonksiyon yerine closure → "Cannot redeclare" riski yok) ── */
    $flPublicImg = function ($rel) {
        $root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
        if ($root !== '' && @is_file($root . '/' . $rel)) return asset($rel);
        if (function_exists('public_path') && @is_file(public_path($rel))) return asset($rel);
        return '';
    };
    $toxCardImg = $flPublicImg('assets/img/tox/tox-card.webp')
               ?: $flPublicImg('assets/img/tox/tox-card.jpg')
               ?: $flPublicImg('assets/img/tox/hero.jpg')
               ?: '';

    /* ── Ürün kartı teknik özellik çizgileri (hover'da açılır). Anahtar: ürün adında geçen parça.
          Yalnız sitede zaten beyan edilen değerler. Sıra önemli: özel olan önce. ── */
    $cardSpecMap = [
        'cf53'      => [['Tolerans', 'ISO h7'], ['Sertlik', '62 ± 2 HRC'], ['Kullanım', 'Rulman yürüyüşü'], ['Tip', 'İndüksiyonlu']],
        'ndüksiyon' => [['Tolerans', 'ISO f7'], ['Sertlik', '58 ± 3 HRC'], ['Yüzey', 'Ra ≤ 0,20 µm'], ['Malzeme', 'CK45']],
        'honlanm'   => [['İç tolerans', 'ISO H8'], ['İç yüzey', 'Ra ≤ 0,4 µm'], ['Kullanım', 'Silindir gövdesi'], ['Marka', 'NIMET']],
        'rod boru'  => [['Tolerans', 'ISO f7 / f8'], ['Yüzey', 'Ra ≤ 0,20 µm'], ['Ağırlık', '%25–35 hafif'], ['Yapı', 'İçi boş']],
        'sert krom' => [['Tolerans', 'ISO f7'], ['Krom', 'min. 20 µm'], ['Yüzey', 'Ra ≤ 0,20 µm'], ['Çap', 'Ø4–200 mm']],
        'milsiz'    => [['Tip', 'Milsiz silindir'], ['Hareket', 'Kompakt lineer'], ['Tahrik', 'Pnömatik']],
        'tox'       => [['Birleştirme', 'Clinching'], ['Pres', 'Servo / pnömohidrolik'], ['Kontrol', '%100 izlenebilir']],
        'özel'      => [['Ölçü', 'Özel boy / çap'], ['Adet', 'Tekil ve seri'], ['Destek', 'Teknik ekip']],
    ];
    $specFor = function ($name) use ($cardSpecMap) {
        $n = mb_strtolower((string) $name, 'UTF-8');
        foreach ($cardSpecMap as $key => $rows) {
            if (mb_strpos($n, $key) !== false) return $rows;
        }
        return [];
    };

    $customCards = [[
        'before' => 'milsiz',   // DB'de adında "milsiz" geçen üründen ÖNCE eklenir
        'name'   => 'TOX® PRESSOTECHNIK — Perçinsiz Birleştirme',
        'short'  => 'Clinching, pnömohidrolik tahrik, servo pres, robot maşaları ve %100 izlenebilir proses kontrol sistemleri.',
        'url'    => url('/tox-pressotechnik'),
        'img'    => $toxCardImg,
        'imgAlt' => 'TOX PRESSOTECHNIK perçinsiz birleştirme (clinching) ve pres sistemleri',
        'badge'  => 'TOX',
        'stock'  => 'Yetkili Tedarik',
    ]];
    $homeCards = [];
    foreach ((isset($products) && is_iterable($products)) ? $products : [] as $p) {
        $pName = mb_strtolower($p->name ?? '');
        foreach ($customCards as $ci => $cc) {
            if ($cc !== null && $cc['before'] && mb_strpos($pName, mb_strtolower($cc['before'])) !== false) {
                $homeCards[] = ['type' => 'custom', 'data' => $cc];
                $customCards[$ci] = null;
            }
        }
        $homeCards[] = ['type' => 'db', 'data' => $p];
    }
    foreach ($customCards as $cc) { if ($cc !== null) $homeCards[] = ['type' => 'custom', 'data' => $cc]; }

    /* ItemList — kartlardan otomatik; DB boşsa sabit liste */
    $itemList = [];
    foreach ($homeCards as $idx => $hc) {
        if ($hc['type'] === 'custom') {
            $itemList[] = ['name' => $hc['data']['name'], 'url' => $hc['data']['url']];
        } elseif (!empty($hc['data']->slug)) {
            $itemList[] = ['name' => $hc['data']->name ?? '', 'url' => route('content', $hc['data']->slug)];
        }
    }
    if (empty($itemList)) {
        foreach ($specRows as $sr) $itemList[] = ['name' => $sr['urun'], 'url' => $sr['url']];
        $itemList[] = ['name' => 'TOX® PRESSOTECHNIK — Perçinsiz Birleştirme ve Pres Sistemleri', 'url' => url('/tox-pressotechnik')];
    }

    /* ── Çözüm ortakları (emoji yerine monogram) ── */
    $tPartners = [
        ['url' => 'https://www.nimet.ro/',            'name' => 'NIMET',  'mono' => 'N', 'desc' => 'Avrupa Sertifikalı Kromlu Mil'],
        ['url' => 'https://www.univer-group.com/en/', 'name' => 'Univer', 'mono' => 'U', 'desc' => 'Pnömatik & Akışkan Sistemler'],
        ['url' => 'https://tox.com/en-us',            'name' => 'TOX',    'mono' => 'T', 'desc' => 'Pres & Bağlantı Teknolojileri'],
        ['url' => 'https://www.medan-gmbh.com/en/',   'name' => 'Medan',  'mono' => 'M', 'desc' => 'Hidrolik Bileşenler'],
    ];
    $tImages = isset($images) && is_iterable($images) ? collect($images)->values() : collect();
    $kurlar  = [['code' => 'USD', 'label' => 'USD / TRY', 'prefix' => 'usd'], ['code' => 'EUR', 'label' => 'EUR / TRY', 'prefix' => 'eur']];

    /* ── JSON-LD yardımcı dizileri (ok fonksiyonu yok → PHP 7.x uyumlu) ── */
    $itemListLd = [];
    foreach (array_values($itemList) as $i => $it) {
        $itemListLd[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $it['name'], 'url' => $it['url']];
    }
    $faqLd = [];
    foreach ($faqs as $f) {
        $faqLd[] = ['@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']]];
    }

    /* Türkçe tarih (Carbon sürümünden bağımsız) */
    $trAylar = ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
    $pu = explode('-', $pageUpdated);
    $pageUpdatedTr = (int) $pu[2] . ' ' . $trAylar[(int) $pu[1]] . ' ' . $pu[0];

    /* Teklif süreci adımları: görünür liste ve HowTo şeması aynı diziden üretilir */
    $rqSteps = [
        ['t' => 'Ürünü ve ölçüyü seçin', 'd' => 'Mil, honlanmış boru veya rod boru için ürünü seçin. Mil için çapı, boru için dış ve iç çapı milimetre olarak girin.'],
        ['t' => 'Boy ve adedi yazın',    'd' => 'Kesim boyunu (mm) ve adedi girin; gerekirse tolerans, malzeme veya yüzey notu ekleyin. Tek talepte 10 kaleme kadar ölçü eklenebilir.'],
        ['t' => 'İletişim bilgisi bırakın', 'd' => 'Ad, telefon ve e-posta bilginizi girin, kısa işlem sorusunu yanıtlayıp KVKK onayını verin.'],
        ['t' => 'Teklifi e-postayla alın', 'd' => 'Talebiniz doğrudan teknik ekibimize ulaşır; stok ve tolerans teyidiyle hazırlanan teklif e-posta adresinize iletilir.'],
    ];
    $rqAnswer = 'Flutecnica\'dan teklif almak için ürünü seçin, mil için çapı; boru için dış ve iç çapı, boyu ve adedi girip iletişim bilginizi bırakın. Talebiniz teknik ekibimize ulaşır; stok teyidiyle hazırlanan teklif e-posta adresinize iletilir. Mil stok çap aralığı Ø4–200 mm, minimum sipariş 1 adet (stoktakiler), teslimat 24–48 saattir.';
    $rqStepsLd = [];
    foreach ($rqSteps as $ri => $rs) {
        $rqStepsLd[] = ['@type' => 'HowToStep', 'position' => $ri + 1, 'name' => $rs['t'], 'text' => $rs['d'], 'url' => $pageUrl . '#olcu-sorgula'];
    }

    /* Spam koruması: işlem sorusu. Cevap şifreli token içinde taşınır (oturum gerekmez);
       sunucu POST /teklif-iste'de `cq` + `cevap` alanlarını doğrulamalıdır (tek kullanımlık, 3 sn – 2 saat). */
    $rqCapA = random_int(2, 9);
    $rqCapB = random_int(2, 9);
    $rqCapToken = encrypt(['s' => $rqCapA + $rqCapB, 't' => time(), 'n' => bin2hex(random_bytes(8))]);

    /* ── JSON-LD @graph ── */
    $schema = [
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@type' => 'WebSite', '@id' => $siteUrl . '/#website', 'url' => $pageUrl,
                'name' => 'Flutecnica', 'alternateName' => 'Flutecnica Akışkan Teknolojileri',
                'publisher' => ['@id' => $siteUrl . '/#organization'], 'inLanguage' => 'tr-TR',
            ],
            [
                '@type' => 'WebPage', '@id' => $pageUrl . '#webpage', 'url' => $pageUrl,
                'name' => $pageTitle, 'description' => $pageDesc, 'inLanguage' => 'tr-TR',
                'keywords' => ['sert krom kaplı mil', 'indüksiyonlu mil', 'CK45', 'CF53', 'H8 honlanmış boru', 'krom kaplı rod boru', 'ISO tolerans', 'NIMET Türkiye'],
                'datePublished' => '2020-01-01', 'dateModified' => $pageUpdated,
                'isPartOf' => ['@id' => $siteUrl . '/#website'],
                'about'    => ['@id' => $siteUrl . '/#organization'],
                'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => $siteUrl . '/assets/img/logo/og-cover.jpg'],
                'breadcrumb' => ['@id' => $pageUrl . '#breadcrumb'],
                'mainEntity' => ['@id' => $pageUrl . '#urun-listesi'],
                'hasPart'    => [['@id' => $pageUrl . '#iso-tolerans-araci'], ['@id' => $pageUrl . '#faqpage'], ['@id' => $pageUrl . '#teklif-nasil-alinir']],
                'speakable'  => ['@type' => 'SpeakableSpecification', 'cssSelector' => ['#hero-title', '.hero__desc', '.compare__answer', '#olcu-sorgula .rq__answer']],
            ],
            [
                '@type' => 'BreadcrumbList', '@id' => $pageUrl . '#breadcrumb',
                'itemListElement' => [['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => $pageUrl]],
            ],
            [
                '@type' => 'ItemList', '@id' => $pageUrl . '#urun-listesi', 'name' => 'Flutecnica Ürün Portföyü',
                'numberOfItems' => count($itemList),
                'itemListElement' => $itemListLd,
            ],
            [
                '@type' => 'FAQPage', '@id' => $pageUrl . '#faqpage', 'inLanguage' => 'tr-TR',
                'mainEntity' => $faqLd,
            ],
            [
                '@type' => 'WebApplication', '@id' => $pageUrl . '#iso-tolerans-araci',
                'name' => 'ISO Tolerans Karşılaştırma Aracı', 'url' => $pageUrl . '#tolerans-karsilastirma',
                'applicationCategory' => 'EngineeringApplication', 'operatingSystem' => 'Web',
                'browserRequirements' => 'JavaScript', 'inLanguage' => 'tr-TR', 'isAccessibleForFree' => true,
                'isPartOf' => ['@id' => $siteUrl . '/#website'], 'provider' => ['@id' => $siteUrl . '/#organization'],
                'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'TRY'],
                'featureList' => ['ISO 286 üst/alt sapma', 'Tolerans genişliği (IT)', 'Geçme (fit) analizi', 'Sıcaklık ve malzeme genleşmesi', 'Paylaşılabilir bağlantı ve PDF çıktı'],
                'description' => 'ISO 286 standardına göre mil ve delik tolerans sınıflarını (f7, h7, h8, H7, H8, g6, k6 vb.) karşılaştıran ücretsiz online araç. Üst/alt sapma, tolerans genişliği, ölçü sınırları ve geçme (fit) analizi hesaplar.',
            ],
            [
                '@type' => 'HowTo', '@id' => $pageUrl . '#tolerans-nasil-hesaplanir',
                'name' => 'ISO tolerans değerleri nasıl karşılaştırılır?', 'inLanguage' => 'tr-TR',
                'tool' => ['@id' => $pageUrl . '#iso-tolerans-araci'],
                'step' => [
                    ['@type' => 'HowToStep', 'position' => 1, 'name' => 'Nominal çapı girin', 'text' => 'Hesaplama yapılacak nominal çapı milimetre cinsinden girin (örn. 50 mm).', 'url' => $pageUrl . '#tolerans-karsilastirma'],
                    ['@type' => 'HowToStep', 'position' => 2, 'name' => 'Tolerans sınıflarını ekleyin', 'text' => 'Karşılaştırmak istediğiniz tolerans sınıflarını yazıp ekleyin (örn. f7, h7, H8). En fazla 12 sınıf eklenebilir.', 'url' => $pageUrl . '#tolerans-karsilastirma'],
                    ['@type' => 'HowToStep', 'position' => 3, 'name' => 'Sonuçları inceleyin', 'text' => 'Üst/alt sapma, tolerans genişliği ve ölçü sınırlarını tablo ile, bantları grafik ile; mil-delik çiftleri için geçme türünü (boşluklu, geçiş, sıkı) görüntüleyin.', 'url' => $pageUrl . '#tolerans-karsilastirma'],
                ],
            ],
            [
                '@type' => 'HowTo', '@id' => $pageUrl . '#teklif-nasil-alinir',
                'name' => 'Flutecnica\'dan ölçüye göre teklif nasıl alınır?', 'inLanguage' => 'tr-TR',
                'description' => $rqAnswer,
                'step' => $rqStepsLd,
            ],
        ],
    ];

    /* ── Product düğümleri: yalnız karşılaştırma tablosunda görünen değerlerden üretilir (görünen içerik = şema) ──
       Fiyat/Offer bilerek yok: sayfada fiyat yayınlanmıyor. Ürün görseli DB kartlarından geliyorsa `image` eklenebilir. */
    $brandLd = ['@type' => 'Brand', 'name' => 'NIMET'];
    $mfrLd   = ['@type' => 'Organization', 'name' => 'NIMET', 'url' => 'https://www.nimet.ro/'];
    foreach ($specRows as $pi => $sr) {
        $props = [['Tolerans', $sr['tol']], ['Sertlik / kaplama', $sr['sert']], ['Yüzey pürüzlülüğü', $sr['ra']]];
        $propLd = [];
        foreach ($props as $pp) {
            if ($pp[1] !== '—') $propLd[] = ['@type' => 'PropertyValue', 'name' => $pp[0], 'value' => $pp[1]];
        }
        $propLd[] = ['@type' => 'PropertyValue', 'name' => 'Stok çap aralığı', 'value' => 'Ø4–200 mm'];
        $schema['@graph'][] = [
            '@type'        => 'Product',
            '@id'          => $pageUrl . '#urun-' . ($pi + 1),
            'name'         => $sr['urun'],
            'url'          => $sr['url'],
            'description'  => $sr['urun'] . ' — tipik kullanım: ' . $sr['kul'] . '. NIMET Türkiye yetkili distribütörü Flutecnica stoğundan, ölçüye kesim ve 24–48 saatte sevkiyatla.',
            'brand'        => $brandLd,
            'manufacturer' => $mfrLd,
            'category'     => 'Hidrolik ve pnömatik silindir mili / borusu',
            'additionalProperty' => $propLd,
            'isRelatedTo'  => ['@id' => $pageUrl . '#urun-listesi'],
        ];
    }
    $schemaJson = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_PRETTY_PRINT);
@endphp

@section('title', $pageTitle)
@section('description', $pageDesc)
@section('keywords', 'sert krom kaplı mil, indüksiyonlu mil, cf53 mil, honlanmış boru h8, krom kaplı rod boru, milsiz silindir, TOX Pressotechnik, clinching, perçinsiz birleştirme, NIMET Türkiye distribütörü, ISO tolerans hesaplama')
@section('robots', 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1')
@section('og_image', 'https://www.flutecnica.com/assets/img/logo/og-cover.jpg')
@section('canonical', 'https://www.flutecnica.com/')

@section('head')
{{-- NOT: layouts.master bu bölümü iki kez basıyor (bkz. teslim notu). Bu yüzden
     burada yalnızca tekrarında zararsız, hafif etiketler var; stil ve JSON-LD
     içerik bölümünde, sayfada TEK kez basılır. --}}
<script>document.documentElement.classList.add('js');</script>
<link rel="preload" as="image" href="{{ asset('assets/video.jpg') }}" fetchpriority="high">
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;700;800&display=swap" onload="this.onload=null;this.rel='stylesheet'">
@endsection

@section('content')
{{-- Yapısal veri: sayfada tek kez --}}
<script type="application/ld+json">{!! $schemaJson !!}</script>

<style>
/* ══════════════════════════════════════════════════════════
   FLUTECNICA 2026 — PREMIUM ENDÜSTRİYEL TEMA (UI/UX Pro Max)
   Desen: Trust & Authority + Conversion · Stil: Accessible & Ethical
   Kurallar: kontrast ≥4.5:1 · dokunma ≥44px (coarse) · min 12px etiket
             SVG ikon (emoji yok) · reduced-motion · mor/pembe yok
   rev. 19: O-Ring katmanı kaldırıldı; karşılaştırma tablosu ve teklif
            formu spam doğrulaması güncellendi.
══════════════════════════════════════════════════════════ */
:root{
    /* Marka */
    --blue:#1D426A; --blue-dk:#11273D; --blue-metal:#2A5585; --sky:#5B7FA6; --sky-lt:#AFC3DA;
    --blue-soft:#EDF1F7; --blue-line:#C3D0E0;
    --yellow:#C8B98E; --yellow-dk:#A89A70; --yellow-text:#6B5E3A; /* altın metin: açık zeminde ≥4.5:1 */
    --green:#1F7A52; --red:#B23A2E;
    /* Yüzeyler */
    --bg-page:#F7F7FF; --bg-white:#FFFFFF; --bg-light:#EDEDF7; --bg-subtle:#F7F7FF; --bg-border:#D8D8E6;
    --navy:#0A1828; --navy-2:#11273D;
    /* Metin (tümü beyaz/bg-page üzerinde AA) */
    --text-primary:#0A1828; --text-secondary:#3A4A5C; --text-muted:#56677A; --text-light:#5C6B7C;
    /* Tipografi */
    --ff:'Plus Jakarta Sans',system-ui,-apple-system,'Segoe UI',sans-serif;
    --ff-head:'Space Grotesk','Plus Jakarta Sans',system-ui,sans-serif;
    --ff-mono:'JetBrains Mono',ui-monospace,'SFMono-Regular',Consolas,monospace;
    /* Hareket */
    --tr:.25s cubic-bezier(.22,.61,.36,1);
    --ease-out:cubic-bezier(.16,1,.3,1);
    /* Ölçek */
    --r-sm:10px; --r-md:14px; --r-lg:18px; --r-xl:24px;
    --z-base:1; --z-over:10; --z-modal:1000;
    --chrome:linear-gradient(180deg,#FCFCFF 0%,#EDEDF7 38%,#B7C4D4 52%,#F0F0FA 70%,#FCFCFF 100%);
    --blue-grad:linear-gradient(135deg,var(--blue-metal),var(--blue-dk));
    --gold-grad:linear-gradient(135deg,var(--yellow-dk),var(--yellow-text));
    --shadow:0 1px 2px rgba(10,24,40,.05),0 2px 8px rgba(10,24,40,.05);
    --shadow-md:0 8px 20px -10px rgba(10,24,40,.18),0 3px 8px -4px rgba(10,24,40,.10);
    --shadow-lg:0 22px 48px -18px rgba(10,24,40,.30),0 8px 18px -10px rgba(10,24,40,.16);
    --shadow-blue:0 14px 34px -12px rgba(29,66,106,.45);
    --focus:0 0 0 3px #fff,0 0 0 6px var(--blue);
}
/* Kapsam: yalnız ana sayfa içeriği (#main-content.fl-home). Tema/layout'a sızmaz. */
:where(.fl-home),:where(.fl-home) *,:where(.fl-home) *::before,:where(.fl-home) *::after{box-sizing:border-box}
html{scroll-padding-top:96px}
.fl-home{font-family:var(--ff);color:var(--text-primary);background:var(--bg-page);font-size:16px;line-height:1.6;-webkit-font-smoothing:antialiased;overflow-x:clip}
:where(.fl-home) [hidden]{display:none !important}
:where(.fl-home) :is(img,video,svg){max-width:100%}
:where(.fl-home) :is(a,button,summary){cursor:pointer;touch-action:manipulation}
.fl-home :where(a,button,input,select,summary,textarea,[tabindex]):focus-visible{outline:none;box-shadow:var(--focus);border-radius:8px}
.sr-only{position:absolute !important;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
.num{font-variant-numeric:tabular-nums}

/* Reveal — yalnız JS varken; JS yoksa içerik görünür kalır */
.js .reveal{opacity:0;transform:translateY(22px);transition:opacity .7s var(--ease-out),transform .7s var(--ease-out)}
.js .reveal.is-in{opacity:1;transform:none}

/* Bölüm başlıkları */
.section-tag{display:inline-flex;align-items:center;gap:10px;font-size:12.5px;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:var(--blue);margin-bottom:10px}
.section-tag::before{content:'';width:28px;height:3px;background:linear-gradient(90deg,var(--sky),var(--blue-metal));border-radius:2px;flex-shrink:0}
.section-title{font-family:var(--ff-head);font-size:clamp(30px,5vw,56px);font-weight:700;line-height:1.06;color:var(--text-primary);letter-spacing:-.03em;text-wrap:balance}
.section-title span{background:var(--blue-grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.section-title .yel{background:var(--gold-grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.section-head-deco{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap}
.section-index{font-family:var(--ff-head);font-size:clamp(38px,6.5vw,72px);font-weight:700;line-height:.8;color:transparent;-webkit-text-stroke:1.5px var(--bg-border);letter-spacing:-.04em;user-select:none;opacity:.7}
.section-lead{font-size:15px;color:var(--text-secondary);margin-top:12px;margin-bottom:20px;line-height:1.7;max-width:72ch}

/* Butonlar */
.fl-btn-primary{display:inline-flex;align-items:center;justify-content:center;gap:9px;min-height:48px;background:var(--blue-grad);color:#fff;font-size:14px;font-weight:700;padding:14px 28px;border-radius:100px;text-decoration:none;box-shadow:var(--shadow-blue);transition:transform var(--tr),box-shadow var(--tr);position:relative;overflow:hidden}
.fl-btn-primary:hover{color:#fff;transform:translateY(-2px);box-shadow:0 22px 44px -14px rgba(29,66,106,.6)}
.fl-btn-primary svg{width:15px;height:15px;transition:transform .2s;flex-shrink:0}
.fl-btn-primary:hover svg{transform:translateX(3px)}
.fl-btn-outline{display:inline-flex;align-items:center;justify-content:center;gap:9px;min-height:48px;background:transparent;color:var(--blue);font-size:14px;font-weight:700;padding:13px 26px;border-radius:100px;text-decoration:none;border:1.5px solid var(--blue-line);transition:background var(--tr),color var(--tr),border-color var(--tr),transform var(--tr)}
.fl-btn-outline:hover{border-color:var(--blue);background:var(--blue);color:#fff;transform:translateY(-2px)}
.fl-btn-outline--light{color:rgba(237,241,247,.92);border-color:rgba(237,241,247,.34)}
.fl-btn-outline--light:hover{background:rgba(255,255,255,.12);border-color:#fff;color:#fff}

/* Medya duraklat/oynat düğmesi (otomatik hareket kontrolü — WCAG 2.2.2) */
.media-toggle{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-width:44px;min-height:44px;padding:0 14px;border-radius:100px;border:1px solid rgba(255,255,255,.35);background:rgba(10,24,40,.55);color:#fff;font-size:12.5px;font-weight:700;backdrop-filter:blur(6px);transition:background var(--tr)}
.media-toggle:hover{background:rgba(10,24,40,.8)}
.media-toggle svg{width:14px;height:14px;fill:currentColor}
.media-toggle--dark{border-color:var(--bg-border);background:var(--bg-white);color:var(--text-secondary);backdrop-filter:none}
.media-toggle--dark:hover{background:var(--blue-soft);color:var(--blue)}

/* ── HERO ── */
.hero{position:relative;min-height:clamp(540px,84vh,900px);min-height:clamp(540px,84svh,900px);display:flex;flex-direction:column;justify-content:flex-end;overflow:hidden;background:var(--navy);border-radius:0 0 28px 28px}
.hero::after{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--sky) 0%,var(--blue-metal) 50%,transparent 100%);z-index:var(--z-over);pointer-events:none}
.hero__media{position:absolute;inset:0;z-index:1}
.hero__poster,.hero__media video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block}
.hero__media video{opacity:0;transition:opacity .8s ease}
.hero__media video.is-playing{opacity:.9}
.hero__media::after{content:'';position:absolute;inset:0;background:radial-gradient(1200px 620px at 82% 14%,rgba(91,127,166,.20),transparent 60%),radial-gradient(1100px 600px at 6% 92%,rgba(29,66,106,.42),transparent 60%),linear-gradient(to right,rgba(10,24,40,.95) 0%,rgba(10,24,40,.80) 55%,rgba(10,24,40,.45) 100%),linear-gradient(to top,rgba(10,24,40,.92) 0%,transparent 58%)}
.hero__grid{position:absolute;inset:0;z-index:1;pointer-events:none;opacity:.10;background-image:linear-gradient(rgba(175,195,218,1) 1px,transparent 1px),linear-gradient(90deg,rgba(175,195,218,1) 1px,transparent 1px);background-size:50px 50px;mask-image:radial-gradient(ellipse 92% 88% at 32% 64%,black,transparent);-webkit-mask-image:radial-gradient(ellipse 92% 88% at 32% 64%,black,transparent)}
.hero__glow{position:absolute;z-index:1;width:480px;height:480px;right:-120px;top:-120px;border-radius:50%;background:radial-gradient(circle,rgba(91,127,166,.18),transparent 65%);pointer-events:none}
.hero__body{position:relative;z-index:2;width:100%;padding-bottom:56px}
.hero__eyebrow{display:inline-flex;align-items:center;gap:9px;font-size:11.5px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:var(--sky-lt);background:rgba(91,127,166,.10);border:1px solid rgba(91,127,166,.30);padding:8px 15px;border-radius:100px;margin-bottom:20px;backdrop-filter:blur(6px)}
.hero__eyebrow .dot{width:7px;height:7px;border-radius:50%;background:var(--sky);box-shadow:0 0 10px var(--sky);animation:hpulse 2s ease-in-out infinite}
@keyframes hpulse{0%,100%{box-shadow:0 0 0 0 rgba(91,127,166,.55)}50%{box-shadow:0 0 0 6px rgba(91,127,166,0)}}
.hero__title{font-family:var(--ff-head);font-size:clamp(31px,6vw,68px);font-weight:700;line-height:1.05;letter-spacing:-.035em;color:#fff;margin-bottom:20px;text-wrap:balance;max-width:18ch}
.hero__title .blue{background:var(--chrome);-webkit-background-clip:text;background-clip:text;color:transparent;filter:drop-shadow(0 2px 8px rgba(0,0,0,.3))}
.hero__title .yel{background:linear-gradient(120deg,var(--sky-lt),#5B7FA6);-webkit-background-clip:text;background-clip:text;color:transparent}
.hero__desc{font-size:16px;color:rgba(237,241,247,.82);line-height:1.75;max-width:58ch;margin-bottom:30px}
.hero__actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:34px}
.hero__stats{display:flex;align-items:center;flex-wrap:wrap;row-gap:14px}
.hero__stat{display:flex;flex-direction:column-reverse;gap:5px;padding-right:30px;margin-right:30px;border-right:1px solid rgba(237,241,247,.16)}
.hero__stat:last-child{border-right:0;margin-right:0;padding-right:0}
.hero__stat-num{font-family:var(--ff-head);font-size:clamp(22px,2.9vw,28px);font-weight:700;color:#fff;letter-spacing:-.02em;line-height:1}
.hero__stat-num .yel{color:var(--sky)}
.hero__stat-num small{font-size:.6em;font-weight:700}
.hero__stat-lbl{font-size:11.5px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:rgba(237,241,247,.72)}
.hero__media-ctrl{position:absolute;right:clamp(12px,3vw,32px);bottom:96px;z-index:3}

/* ── TICKER (duraklatılabilir, reduced-motion'da statik) ── */
.hero-ticker{position:relative;z-index:2;overflow:hidden;background:var(--bg-white);border-top:1px solid var(--bg-border);height:72px;display:flex;align-items:center;box-shadow:var(--shadow-md)}
.hero-ticker__viewport{flex:1;overflow:hidden;height:100%;display:flex;align-items:center;position:relative}
.hero-ticker__viewport::before,.hero-ticker__viewport::after{content:'';position:absolute;top:0;bottom:0;width:70px;z-index:3;pointer-events:none}
.hero-ticker__viewport::before{left:0;background:linear-gradient(to right,var(--bg-white),transparent)}
.hero-ticker__viewport::after{right:0;background:linear-gradient(to left,var(--bg-white),transparent)}
.hero-ticker__track{display:flex;align-items:center;white-space:nowrap;animation:ticker-go 40s linear infinite}
.hero-ticker:hover .hero-ticker__track,.hero-ticker:focus-within .hero-ticker__track,.hero-ticker.is-paused .hero-ticker__track{animation-play-state:paused}
@keyframes ticker-go{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}
.hero-ticker__ctrl{flex-shrink:0;padding:0 12px;border-left:1px solid var(--bg-border);height:100%;display:flex;align-items:center;background:var(--bg-white);z-index:4}
.t-sep{display:inline-flex;align-items:center;padding:0 14px;color:var(--bg-border);flex-shrink:0}
.t-sep svg{width:10px;height:10px;fill:currentColor}
.t-partner{display:inline-flex;align-items:center;gap:12px;padding:0 24px;height:72px;flex-shrink:0;border-right:1px solid var(--bg-border);text-decoration:none;transition:background .2s}
.t-partner:hover{background:var(--bg-subtle)}
.t-partner__ico{width:52px;height:38px;border-radius:9px;background:var(--bg-subtle);border:1px solid var(--bg-border);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-family:var(--ff-head);font-weight:700;font-size:17px;color:var(--blue);transition:background .2s,border-color .2s}
.t-partner:hover .t-partner__ico{background:var(--blue-soft);border-color:var(--blue-line)}
.t-partner__ico img{max-width:44px;max-height:28px;object-fit:contain}
.t-partner__body{display:flex;flex-direction:column;gap:2px}
.t-partner__name{font-family:var(--ff-head);font-size:14px;font-weight:700;color:var(--text-primary)}
.t-partner__desc{font-size:11px;font-weight:600;color:var(--text-muted);letter-spacing:.04em;text-transform:uppercase}
.t-kur{display:inline-flex;align-items:center;gap:10px;padding:0 18px;height:72px;flex-shrink:0;border-right:1px solid var(--bg-border);background:var(--bg-subtle)}
.t-kur__code-badge{font-family:var(--ff-mono);font-size:12px;font-weight:800;color:#fff;background:var(--navy);border-radius:7px;padding:4px 7px;line-height:1}
.t-kur__head{display:flex;flex-direction:column;justify-content:center}
.t-kur__code{font-size:11px;font-weight:700;color:var(--text-secondary);letter-spacing:.06em;text-transform:uppercase;line-height:1.3}
.t-kur__source{font-size:10.5px;font-weight:700;color:var(--blue);letter-spacing:.04em;text-transform:uppercase;line-height:1.3}
.t-kur__grid{display:grid;grid-template-columns:1fr 1fr;gap:2px 12px;margin:0}
.t-kur__cell{display:flex;flex-direction:column}
.t-kur__lbl{font-size:10.5px;font-weight:700;color:var(--text-light);letter-spacing:.04em;text-transform:uppercase;line-height:1.25}
.t-kur__val{font-family:var(--ff-mono);font-size:12.5px;font-weight:700;color:var(--blue);line-height:1.25;font-variant-numeric:tabular-nums;margin:0}
.t-kur__val.buy{color:var(--green)}.t-kur__val.sell{color:var(--red)}
.t-kur__val.kur-loading{color:var(--text-light);animation:kur-pulse 1.2s ease-in-out infinite}
@keyframes kur-pulse{0%,100%{opacity:.4}50%{opacity:.9}}

/* ── KARŞILAŞTIRMA TABLOSU (temel; ayrıntılı görünüm "v19" bloğunda) ── */
.spec-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;border:1px solid var(--bg-border);border-radius:var(--r-md);margin-top:12px}
.spec{width:100%;border-collapse:separate;border-spacing:0;min-width:600px;font-size:13.5px}
.spec th{background:var(--navy);color:#fff;font-family:var(--ff-head);font-size:12px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;text-align:left;padding:12px 13px;white-space:nowrap}
.spec td,.spec tbody th{padding:12px 13px;border-top:1px solid var(--bg-border);vertical-align:top;color:var(--text-secondary);background:var(--bg-white)}
.spec tbody th{background:var(--bg-subtle);color:var(--text-primary);font-family:var(--ff);font-size:13.5px;text-transform:none;letter-spacing:0;white-space:normal}
.spec tbody th a{color:var(--blue);font-weight:700;text-decoration:none}
.spec tbody th a:hover{text-decoration:underline}
.spec td.mono{font-family:var(--ff-mono);font-weight:700;color:var(--text-primary);white-space:nowrap}

/* ── ÜRÜNLER ── */
.products{padding:clamp(22px,3vw,36px) 0 clamp(8px,1.2vw,14px);background:var(--bg-page);position:relative;overflow:hidden}
.products__head{margin-bottom:16px;position:relative;z-index:2}
.prod-card{background:var(--bg-white);border-radius:var(--r-lg);border:1px solid var(--bg-border);overflow:hidden;display:flex;flex-direction:column;height:100%;position:relative;transition:transform .4s var(--ease-out),box-shadow .4s,border-color .4s;box-shadow:var(--shadow)}
.prod-card::after{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--blue-metal),var(--sky));transform:scaleX(0);transform-origin:left;transition:transform .45s var(--ease-out);z-index:4}
.prod-card::before{content:'';position:absolute;inset:0;border-radius:var(--r-lg);background:radial-gradient(600px circle at var(--mx,50%) var(--my,50%),rgba(91,127,166,.10),transparent 55%);opacity:0;transition:opacity .4s;pointer-events:none;z-index:0}
.prod-card:hover,.prod-card:focus-within{transform:translateY(-6px);border-color:var(--blue-line);box-shadow:var(--shadow-lg)}
.prod-card:hover::before{opacity:1}
.prod-card:hover::after,.prod-card:focus-within::after{transform:scaleX(1)}
.prod-card__img{aspect-ratio:2/1;overflow:hidden;position:relative;flex-shrink:0;background:linear-gradient(150deg,var(--bg-light) 0%,var(--bg-subtle) 100%)}
.prod-card__img img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .65s var(--ease-out)}
.prod-card:hover .prod-card__img img{transform:scale(1.05)}
.prod-card__badge{position:absolute;top:12px;right:12px;z-index:3;background:rgba(255,255,255,.94);color:var(--blue);font-size:11px;font-weight:800;letter-spacing:.06em;padding:4px 10px;border-radius:7px;border:1px solid var(--blue-line)}
.prod-card__badge--brand{background:var(--blue-grad);color:#fff;border-color:transparent}
.prod-card__stock{position:absolute;bottom:12px;left:12px;z-index:3;display:flex;align-items:center;gap:6px;background:rgba(255,255,255,.94);border:1px solid var(--bg-border);border-radius:7px;padding:5px 10px;font-size:11px;font-weight:700;color:var(--text-secondary)}
.prod-card__stock svg{width:12px;height:12px;fill:var(--green)}
.prod-card__body{padding:18px 18px 16px;display:flex;flex-direction:column;position:static;flex:1}
.prod-card__title{font-family:var(--ff-head);font-size:16.5px;font-weight:700;color:var(--text-primary);line-height:1.32;letter-spacing:-.01em;margin-bottom:8px}
.prod-card__title a{color:inherit;text-decoration:none}
.prod-card__title a::after{content:'';position:absolute;inset:0;z-index:1} /* tüm kart tıklanabilir, tek odak noktası */
.prod-card__short{font-size:13.5px;color:var(--text-muted);line-height:1.6;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:14px}
.prod-card__foot{display:flex;align-items:center;justify-content:space-between;gap:8px;padding-top:12px}
.prod-card__more{display:inline-flex;align-items:center;gap:6px;font-size:13.5px;font-weight:700;color:var(--blue)}
.prod-card__arrow{width:32px;height:32px;border-radius:9px;background:var(--blue-soft);border:1px solid var(--blue-line);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .3s var(--ease-out)}
.prod-card__arrow svg{width:11px;height:11px;fill:var(--blue);transition:fill .2s}
.prod-card:hover .prod-card__arrow{background:var(--blue-grad);border-color:transparent;transform:rotate(-45deg)}
.prod-card:hover .prod-card__arrow svg{fill:#fff}

/* ── MÜHENDİSLİK ARAÇLARI ── */
.eng-tools{padding:clamp(12px,1.8vw,20px) 0 clamp(18px,2.4vw,28px);background:var(--bg-page);position:relative;overflow:hidden}
.eng-tools::before{content:'';position:absolute;inset:0;pointer-events:none;background-image:linear-gradient(var(--bg-border) 1px,transparent 1px),linear-gradient(90deg,var(--bg-border) 1px,transparent 1px);background-size:58px 58px;opacity:.32;mask-image:radial-gradient(ellipse 80% 80% at 50% 50%,black,transparent);-webkit-mask-image:radial-gradient(ellipse 80% 80% at 50% 50%,black,transparent)}
.eng-tools .container{position:relative;z-index:2}
.eng__tools-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;list-style:none}
.eng__tool{display:flex;align-items:center;gap:12px;min-height:64px;padding:14px 15px;border-radius:var(--r-md);background:var(--bg-white);border:1px solid var(--bg-border);text-decoration:none;position:relative;overflow:hidden;height:100%;transition:transform .35s var(--ease-out),box-shadow .35s,border-color .35s;box-shadow:var(--shadow)}
.eng__tool::before{content:'';position:absolute;inset:0;background:radial-gradient(300px circle at var(--mx,50%) var(--my,50%),rgba(91,127,166,.10),transparent 60%);opacity:0;transition:opacity .3s}
.eng__tool:hover{border-color:var(--blue-line);transform:translateY(-3px);box-shadow:var(--shadow-md)}
.eng__tool:hover::before{opacity:1}
.eng__tool-ico{width:40px;height:40px;border-radius:11px;background:var(--blue-soft);border:1px solid var(--blue-line);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .3s;position:relative;z-index:1}
.eng__tool:hover .eng__tool-ico{background:var(--blue-grad);border-color:transparent}
.eng__tool-ico svg{width:18px;height:18px;fill:var(--blue);transition:fill .3s}
.eng__tool:hover .eng__tool-ico svg{fill:#fff}
.eng__tool-text{flex:1;min-width:0;position:relative;z-index:1}
.eng__tool-name{font-size:14px;font-weight:700;color:var(--text-primary);line-height:1.35}
.eng__tool-cat{font-size:11.5px;font-weight:700;color:var(--text-muted);letter-spacing:.04em;text-transform:uppercase;margin-top:3px}
.eng__tool-arrow{width:24px;height:24px;border-radius:6px;background:var(--blue-soft);border:1px solid var(--blue-line);display:flex;align-items:center;justify-content:center;flex-shrink:0;position:relative;z-index:1;transition:background .25s}
.eng__tool-arrow svg{width:10px;height:10px;fill:var(--blue)}
.eng__tool:hover .eng__tool-arrow{background:var(--blue-grad);border-color:transparent}
.eng__tool:hover .eng__tool-arrow svg{fill:#fff}
.eng__footer,.pdf__foot{margin-top:12px;padding:13px 17px;border-radius:var(--r-md);background:var(--blue-soft);border:1px solid var(--blue-line);display:flex;align-items:center;gap:11px}
.pdf__foot{background:var(--bg-light);border-color:var(--bg-border)}
.eng__footer svg,.pdf__foot svg{width:18px;height:18px;fill:var(--blue);flex-shrink:0}
.eng__footer-text,.pdf__foot-text{font-size:13.5px;color:var(--text-secondary);line-height:1.5;font-weight:500}
.eng__footer-text strong,.pdf__foot-text strong{color:var(--text-primary)}
.legal-note{font-size:12.5px;color:var(--text-muted);margin-top:14px;line-height:1.7;max-width:110ch}

/* ── PDF DÖKÜMANLAR ── */
.pdf-docs{padding:clamp(16px,2.2vw,26px) 0;background:var(--bg-page);position:relative;overflow:hidden}
.pdf__grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;list-style:none}
.pdf__card{background:var(--bg-white);border-radius:var(--r-md);border:1px solid var(--bg-border);padding:18px;display:flex;flex-direction:column;gap:14px;height:100%;position:relative;overflow:hidden;transition:transform .35s var(--ease-out),box-shadow .35s,border-color .35s;box-shadow:var(--shadow)}
.pdf__card:hover{transform:translateY(-4px);border-color:var(--blue-line);box-shadow:var(--shadow-md)}
.pdf__card-top{display:flex;align-items:flex-start;gap:12px}
.pdf__ico{width:44px;height:50px;border-radius:10px;background:linear-gradient(160deg,var(--blue-soft),#fff);border:1px solid var(--blue-line);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.pdf__ico svg{width:20px;height:20px;fill:var(--blue)}
.pdf__info{flex:1;min-width:0}
.pdf__name{font-size:14.5px;font-weight:700;color:var(--text-primary);line-height:1.35;margin-bottom:4px}
.pdf__desc{font-size:12.5px;color:var(--text-muted);line-height:1.5}
.pdf__meta{display:flex;gap:6px;flex-wrap:wrap}
.pdf__meta span{display:inline-flex;align-items:center;font-size:11px;font-weight:700;color:var(--text-secondary);background:var(--bg-subtle);padding:3px 8px;border-radius:6px;border:1px solid var(--bg-border)}
.pdf__acts{display:flex;gap:8px;margin-top:auto}
.pdf__btn-v,.pdf__btn-d{flex:1;display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:44px;font-size:13px;font-weight:700;padding:10px;border-radius:10px;text-decoration:none;transition:background .2s,color .2s,border-color .2s,transform .2s;white-space:nowrap;border:none;font-family:inherit}
.pdf__btn-v{border:1px solid var(--bg-border);background:var(--bg-white);color:var(--text-secondary)}
.pdf__btn-v svg{width:13px;height:13px;fill:var(--text-muted)}
.pdf__btn-v:hover{background:var(--navy);color:#fff;border-color:var(--navy)}
.pdf__btn-v:hover svg{fill:#fff}
.pdf__btn-d{background:var(--blue-grad);color:#fff;box-shadow:var(--shadow-blue)}
.pdf__btn-d svg{width:13px;height:13px;fill:#fff}
.pdf__btn-d:hover{color:#fff;transform:translateY(-1px)}

/* PDF MODAL — native <dialog> (odak tuzağı, Esc, üst katman) */
.pdfM{border:none;padding:0;background:transparent;width:min(920px,94vw);height:min(86vh,1000px);max-width:none;max-height:none;margin:auto}
.pdfM::backdrop{background:rgba(10,24,40,.86);backdrop-filter:blur(6px)}
.pdfM[open]{display:flex}
.pdfM__box{background:var(--bg-white);border-radius:var(--r-lg);width:100%;height:100%;display:flex;flex-direction:column;overflow:hidden;box-shadow:var(--shadow-lg);animation:pdfUp .28s var(--ease-out)}
@keyframes pdfUp{from{transform:translateY(16px) scale(.98);opacity:0}to{transform:none;opacity:1}}
.pdfM__head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 16px 12px 22px;background:var(--navy);color:#fff;flex-shrink:0}
.pdfM__head h2{font-family:var(--ff-head);font-size:15px;font-weight:700;display:flex;align-items:center;gap:9px;min-width:0;color:#fff}
.pdfM__head h2 span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.pdfM__head h2 svg{width:17px;height:17px;fill:var(--sky);flex-shrink:0}
.pdfM__ctrl{display:flex;align-items:center;gap:8px;flex-shrink:0}
.pdfM__dl{display:inline-flex;align-items:center;gap:6px;min-height:40px;font-size:13px;font-weight:700;padding:8px 16px;border-radius:9px;background:var(--blue-grad);color:#fff;text-decoration:none}
.pdfM__dl svg{width:12px;height:12px;fill:#fff}
.pdfM__dl:hover{color:#fff}
.pdfM__x{width:44px;height:44px;border-radius:50%;background:rgba(255,255,255,.12);border:none;color:#fff;display:flex;align-items:center;justify-content:center;transition:background .2s}
.pdfM__x svg{width:18px;height:18px;fill:#fff}
.pdfM__x:hover{background:var(--blue-metal)}
.pdfM__body{flex:1;overflow:hidden}
.pdfM__body iframe{width:100%;height:100%;border:none}

/* ── HARİTA ── */
.vid-map{padding:clamp(16px,2.2vw,26px) 0;background:var(--bg-light)}
.vid-map__grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:stretch}
.vid-map__map{border-radius:var(--r-lg);overflow:hidden;border:1px solid var(--bg-border);min-height:300px;display:flex;flex-direction:column;box-shadow:var(--shadow-md);background:var(--bg-white)}
.vid-map__map-inner{flex:1;min-height:280px;background:var(--bg-subtle)}
.vid-map__map-inner iframe{width:100%;height:100%;min-height:280px;border:none;display:block}
.vid-map__map-footer{padding:15px 17px;display:flex;align-items:flex-start;gap:12px;border-top:1px solid var(--bg-border)}
.vid-map__map-ico{width:36px;height:36px;border-radius:10px;background:var(--blue-soft);border:1px solid var(--blue-line);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.vid-map__map-ico svg{width:17px;height:17px;fill:var(--blue)}
.vid-map__map-addr{flex:1;min-width:0;font-style:normal}
.vid-map__map-name{font-family:var(--ff-head);font-size:15px;font-weight:700;color:var(--text-primary);margin-bottom:4px}
.vid-map__map-detail{font-size:13.5px;color:var(--text-secondary);line-height:1.8}
.vid-map__map-detail a{color:var(--blue);text-decoration:none;font-weight:600;display:inline-block;padding:2px 0}
.vid-map__map-detail a:hover{text-decoration:underline}

/* ── HAKKIMIZDA ── */
.about{padding:clamp(16px,2.2vw,26px) 0;background:var(--bg-page)}
.about__img-wrap{border-radius:var(--r-lg);overflow:hidden;box-shadow:var(--shadow-lg);position:relative;aspect-ratio:10/7;background:var(--bg-light)}
.about__img-wrap img{width:100%;height:100%;object-fit:cover;display:block}
.about__body{padding-left:clamp(0px,5vw,44px)}
.about__body p{font-size:15.5px;color:var(--text-secondary);line-height:1.8;margin-bottom:24px;max-width:65ch}

/* ── SSS ── */
.faq{padding:clamp(16px,2.2vw,26px) 0;background:var(--bg-page)}
.faq__list{margin-top:18px;display:flex;flex-direction:column;gap:11px}
.faq__item{background:var(--bg-white);border:1px solid var(--bg-border);border-radius:var(--r-md);overflow:hidden;box-shadow:var(--shadow);transition:border-color .25s,box-shadow .25s}
.faq__item[open]{border-color:var(--blue-line);box-shadow:var(--shadow-md)}
.faq__q{list-style:none;display:flex;align-items:center;justify-content:space-between;gap:14px;min-height:56px;padding:16px 20px;font-family:var(--ff-head);font-size:16px;font-weight:600;color:var(--text-primary);line-height:1.45;letter-spacing:-.01em}
.faq__q::-webkit-details-marker{display:none}
.faq__q::marker{content:''}
.faq__q h3{font:inherit;color:inherit;margin:0}
.faq__icon{position:relative;width:18px;height:18px;flex-shrink:0}
.faq__icon::before,.faq__icon::after{content:'';position:absolute;background:var(--blue);border-radius:2px;transition:transform .28s,opacity .28s}
.faq__icon::before{top:8px;left:0;width:18px;height:2px}
.faq__icon::after{top:0;left:8px;width:2px;height:18px}
.faq__item[open] .faq__q{color:var(--blue)}
.faq__item[open] .faq__icon::after{transform:rotate(90deg);opacity:0}
.faq__a{padding:0 20px 18px;font-size:15px;color:var(--text-secondary);line-height:1.75;max-width:80ch}

/* ═══ ISO TOLERANS KARŞILAŞTIRMA — PRO KONSOL ═══ */
.tolc{padding:clamp(16px,2.4vw,28px) 0;background:var(--bg-page);position:relative;overflow:hidden}
.tolc__sub code{font-family:var(--ff-mono);font-size:13px;background:var(--blue-soft);color:var(--blue-dk);padding:1px 7px;border-radius:6px;border:1px solid var(--blue-line);font-weight:700}
.tolc__panel{background:linear-gradient(180deg,var(--bg-white),var(--bg-subtle));border:1px solid var(--bg-border);border-radius:var(--r-xl);padding:clamp(18px,2.4vw,26px);box-shadow:var(--shadow-lg);position:relative;overflow:hidden}
.tolc__panel::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,var(--blue-metal),var(--sky),var(--green),var(--yellow),var(--red));opacity:.85}
.tolc__panel-grid{display:grid;grid-template-columns:240px 1fr;gap:20px;align-items:start}
.tolc__field{display:flex;flex-direction:column;gap:8px;min-width:0}
.tolc__field label,.tolc__field .tolc__label{font-size:12px;font-weight:800;color:var(--text-secondary);letter-spacing:.08em;text-transform:uppercase;display:flex;align-items:center;gap:7px}
.tolc__field label svg{width:14px;height:14px;fill:var(--blue);flex-shrink:0}
.tolc__field--dia input{width:100%;font-family:var(--ff-head);font-size:22px;font-weight:700;color:var(--text-primary);padding:12px 16px;min-height:56px;border:1.5px solid var(--bg-border);border-radius:var(--r-md);background:var(--bg-white);transition:border-color .2s,box-shadow .2s;letter-spacing:-.02em}
.tolc__field--dia input:focus{outline:none;border-color:var(--blue-metal);box-shadow:0 0 0 4px rgba(42,85,133,.18)}
.tolc__help{font-size:12.5px;color:var(--text-muted);line-height:1.5}
.tolc__dia-presets,.tolc__quick{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
.tolc__dia-preset,.tolc__quick-btn{font-family:var(--ff-mono);font-size:12.5px;font-weight:700;min-height:36px;min-width:44px;border-radius:8px;padding:6px 11px;transition:background .18s,color .18s,border-color .18s,transform .18s}
.tolc__dia-preset{color:var(--text-secondary);background:var(--bg-white);border:1px solid var(--bg-border)}
.tolc__dia-preset:hover{border-color:var(--blue-line);color:var(--blue);background:var(--blue-soft)}
.tolc__quick-lbl{font-size:11.5px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--text-light);margin-right:2px}
.tolc__quick-btn{color:var(--blue-dk);background:var(--blue-soft);border:1px solid var(--blue-line)}
.tolc__quick-btn:hover{background:var(--blue);color:#fff;border-color:var(--blue);transform:translateY(-1px)}
.tolc__chipbox{display:flex;flex-wrap:wrap;align-items:center;gap:8px;min-height:56px;padding:8px 11px;border:1.5px solid var(--bg-border);border-radius:var(--r-md);background:var(--bg-white);transition:border-color .2s,box-shadow .2s;cursor:text}
.tolc__chipbox.is-focus{border-color:var(--blue-metal);box-shadow:0 0 0 4px rgba(42,85,133,.15)}
.tolc__chiplist{display:contents;list-style:none}
.tolc-chip{display:inline-flex;align-items:center;gap:8px;font-family:var(--ff-head);font-size:14px;font-weight:700;color:#fff;padding:5px 6px 5px 12px;border-radius:9px;box-shadow:0 3px 8px -3px rgba(10,24,40,.35),inset 0 1px 0 rgba(255,255,255,.25);animation:chipIn .22s var(--ease-out)}
@keyframes chipIn{from{opacity:0;transform:scale(.88) translateY(4px)}to{opacity:1;transform:none}}
.tolc-chip__x{width:26px;height:26px;border-radius:50%;background:rgba(255,255,255,.22);border:none;color:#fff;display:flex;align-items:center;justify-content:center;transition:background .18s;flex-shrink:0}
.tolc-chip__x svg{width:12px;height:12px;fill:#fff}
.tolc-chip__x:hover{background:rgba(255,255,255,.42)}
.tolc__chipinput{flex:1;min-width:120px;min-height:36px;border:none;outline:none;background:transparent;font-family:var(--ff-head);font-size:16px;font-weight:600;color:var(--text-primary);padding:6px 2px}
.tolc__chipinput::placeholder{color:var(--text-light);font-weight:500}
.tolc__panel-foot{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-top:20px;padding-top:18px;border-top:1px dashed var(--bg-border)}
.tolc__btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;min-height:48px;background:var(--blue-grad);color:#fff;font-size:14px;font-weight:800;padding:13px 30px;border:none;border-radius:100px;box-shadow:var(--shadow-blue);transition:transform .2s,box-shadow .2s;font-family:inherit}
.tolc__btn svg{width:15px;height:15px;transition:transform .2s}
.tolc__btn:hover{transform:translateY(-2px);box-shadow:0 22px 44px -14px rgba(29,66,106,.6)}
.tolc__btn:hover svg{transform:translateX(3px)}
.tolc__btn:active{transform:translateY(0)}
.tolc__btn--ghost{background:transparent;color:var(--text-secondary);box-shadow:none;border:1.5px solid var(--bg-border);padding:12px 22px;font-weight:700}
.tolc__btn--ghost svg{fill:var(--text-muted)}
.tolc__btn--ghost:hover{border-color:var(--red);color:var(--red);background:rgba(178,58,46,.05);transform:none;box-shadow:none}
.tolc__btn--ghost:hover svg{fill:var(--red);transform:none}
.tolc__count{margin-left:auto;font-size:13px;font-weight:700;color:var(--text-muted)}
.tolc__count b{font-family:var(--ff-mono);color:var(--blue);font-size:14px}
.tolc__err{margin-top:14px;font-size:13.5px;font-weight:600;color:var(--red);background:rgba(178,58,46,.07);border:1px solid rgba(178,58,46,.25);padding:12px 15px;border-radius:var(--r-sm);line-height:1.5;display:flex;align-items:flex-start;gap:9px}
.tolc__err svg{width:16px;height:16px;fill:var(--red);flex-shrink:0;margin-top:2px}

.tolc__results{margin-top:24px;display:flex;flex-direction:column;gap:18px;animation:tolcFade .4s var(--ease-out)}
@keyframes tolcFade{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}
.tolc__block{background:var(--bg-white);border:1px solid var(--bg-border);border-radius:var(--r-xl);padding:clamp(16px,2.2vw,24px);box-shadow:var(--shadow-md);position:relative;overflow:hidden}
.tolc__block-title{font-family:var(--ff-head);font-size:16.5px;font-weight:700;color:var(--text-primary);margin-bottom:18px;display:flex;align-items:center;gap:11px;flex-wrap:wrap;letter-spacing:-.01em}
.tolc__block-title .ico{width:32px;height:32px;border-radius:9px;background:var(--blue-grad);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:var(--shadow-blue)}
.tolc__block-title .ico svg{width:16px;height:16px;fill:#fff}
.tolc__block-title small{font-family:var(--ff-mono);font-size:12.5px;font-weight:700;color:var(--blue);background:var(--blue-soft);padding:3px 10px;border-radius:7px;border:1px solid var(--blue-line)}
.tolc__block-title small:empty{display:none}
.tolc__table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;border-radius:var(--r-md);border:1px solid var(--bg-border)}
.tolc__table{width:100%;border-collapse:separate;border-spacing:0;min-width:680px;font-size:14px}
.tolc__table thead th{background:var(--navy);color:#fff;font-family:var(--ff-head);font-size:12px;font-weight:700;text-align:left;padding:13px 15px;white-space:nowrap;letter-spacing:.06em;text-transform:uppercase}
.tolc__table thead th .th-sub{display:block;font-size:11px;font-weight:600;color:var(--sky-lt);letter-spacing:.02em;margin-top:2px;text-transform:none;font-family:var(--ff-mono)}
.tolc__table tbody td,.tolc__table tbody th{padding:13px 15px;border-bottom:1px solid var(--bg-border);color:var(--text-primary);white-space:nowrap;vertical-align:middle;background:var(--bg-white);text-align:left;font-weight:inherit}
.tolc__table tbody tr:last-child td,.tolc__table tbody tr:last-child th{border-bottom:none}
.tolc__table tbody tr:nth-child(even) td,.tolc__table tbody tr:nth-child(even) th{background:var(--bg-subtle)}
.tolc__table tbody tr:hover td,.tolc__table tbody tr:hover th{background:var(--blue-soft)}
.tolc__table tbody th{position:sticky;left:0;z-index:2;border-right:1px solid var(--bg-border)}
.tolc__cell-class{display:flex;align-items:center;gap:10px;font-family:var(--ff-head);font-weight:700;color:var(--text-primary);font-size:16px}
.tolc__cell-class .d{width:14px;height:14px;border-radius:5px;flex-shrink:0;box-shadow:0 2px 5px -1px rgba(10,24,40,.3)}
.tolc__num{font-family:var(--ff-mono);font-weight:800;color:var(--text-primary);font-size:14.5px;letter-spacing:-.02em;font-variant-numeric:tabular-nums}
.tolc__num.pos{color:var(--green)}.tolc__num.neg{color:var(--red)}.tolc__num.zero{color:var(--text-muted)}
.tolc__sub-mm{display:block;font-size:11.5px;color:var(--text-light);font-weight:700;margin-top:2px;font-family:var(--ff-mono)}
.tolc__table td.tolc__col-tol .tolc__num{color:var(--blue);font-size:16px}
.tolc__table td.tolc__col-tol{background:linear-gradient(180deg,rgba(29,66,106,.07),rgba(29,66,106,.03)) !important}
.tolc__tag{font-size:11.5px;font-weight:800;padding:5px 11px;border-radius:7px;letter-spacing:.04em;text-transform:uppercase;display:inline-flex;align-items:center;gap:5px}
.tolc__tag::before{content:'';width:6px;height:6px;border-radius:50%}
.tolc__tag--mil{background:var(--blue-soft);color:var(--blue-dk);border:1px solid var(--blue-line)}
.tolc__tag--mil::before{background:var(--blue)}
.tolc__tag--delik{background:rgba(31,122,82,.12);color:var(--green);border:1px solid rgba(31,122,82,.35)}
.tolc__tag--delik::before{background:var(--green)}

.tolc-plot{position:relative;padding:8px 0 4px}
.tolc-zero{position:absolute;top:0;bottom:30px;width:0;border-left:2px dashed var(--text-secondary);z-index:3;opacity:.55}
.tolc-zero__tag{position:absolute;top:-4px;left:50%;transform:translateX(-50%);font-family:var(--ff-mono);font-size:11px;font-weight:800;color:#fff;background:var(--navy);padding:3px 9px;border-radius:6px;white-space:nowrap}
.tolc-row{margin-bottom:14px}
.tolc-row:last-child{margin-bottom:0}
.tolc-row__label{display:flex;align-items:center;gap:9px;margin-bottom:7px;flex-wrap:wrap}
.tolc-row__dot{width:13px;height:13px;border-radius:5px;flex-shrink:0}
.tolc-row__name{font-family:var(--ff-head);font-size:15px;font-weight:700;color:var(--text-primary)}
.tolc-row__type{font-size:11px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--text-secondary);background:var(--bg-subtle);border:1px solid var(--bg-border);padding:3px 9px;border-radius:6px}
.tolc-row__vals{margin-left:auto;font-family:var(--ff-mono);font-size:12.5px;font-weight:700;color:var(--text-secondary);background:var(--bg-subtle);padding:3px 10px;border-radius:7px;border:1px solid var(--bg-border)}
.tolc-row__track{position:relative;height:34px;background:repeating-linear-gradient(90deg,var(--bg-subtle) 0,var(--bg-subtle) 1px,transparent 1px,transparent 10%);border:1px solid var(--bg-border);border-radius:9px;overflow:hidden}
.tolc-bar{position:absolute;top:4px;bottom:4px;left:0;border-radius:6px;min-width:5px;transform-origin:left center;transform:scaleX(0);transition:transform .7s var(--ease-out);box-shadow:inset 0 1px 0 rgba(255,255,255,.45);display:flex;align-items:center;justify-content:center}
.tolc-bar.is-drawn{transform:scaleX(1)}
.tolc-bar__lbl{font-family:var(--ff-mono);font-size:11px;font-weight:800;color:#fff;text-shadow:0 1px 2px rgba(0,0,0,.45);white-space:nowrap;padding:0 6px}
.tolc-bar:not(.show-lbl) .tolc-bar__lbl{display:none}
.tolc-axis{position:relative;height:22px;margin-top:6px;font-family:var(--ff-mono);font-size:11.5px;font-weight:700;color:var(--text-muted)}
.tolc-axis span{position:absolute;top:0;white-space:nowrap}
.tolc-axis .ax-lo{left:0}.tolc-axis .ax-hi{right:0}

.tolc__cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:13px}
.tolc__fit{border:1px solid var(--bg-border);border-radius:var(--r-md);padding:16px 17px 16px 21px;background:var(--bg-white);position:relative;overflow:hidden;box-shadow:var(--shadow)}
.tolc__fit::before{content:'';position:absolute;top:0;left:0;bottom:0;width:5px}
.tolc__fit--bos::before{background:var(--green)}
.tolc__fit--gec::before{background:var(--yellow)}
.tolc__fit--siki::before{background:var(--red)}
.tolc__fit-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;padding-bottom:11px;border-bottom:1px solid var(--bg-border)}
.tolc__fit-pair{font-family:var(--ff-head);font-size:16px;font-weight:700;color:var(--text-primary)}
.tolc__fit-badge{font-size:11px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;padding:5px 11px;border-radius:100px;color:#fff;white-space:nowrap}
.tolc__fit--bos .tolc__fit-badge{background:var(--green)}
.tolc__fit--gec .tolc__fit-badge{background:#7A5E0F}
.tolc__fit--siki .tolc__fit-badge{background:var(--red)}
.tolc__fit-rows{display:flex;flex-direction:column;gap:8px}
.tolc__fit-line{display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:13px}
.tolc__fit-line span{color:var(--text-muted);font-weight:600}
.tolc__fit-line b{font-family:var(--ff-mono);color:var(--text-primary);font-weight:800;font-size:13.5px}
.tolc__xsec{margin:10px 0;background:var(--bg-white);border:1px solid var(--bg-border);border-radius:var(--r-sm);padding:6px}
.tolc__xsec svg{display:block;width:100%;height:auto}
.tolc__fit-temp{margin-top:9px;padding-top:9px;border-top:1px dashed var(--bg-border)}
.tolc__fit-temp .tolc__fit-line span{color:var(--blue)}
.tolc__fit-temp .tolc__fit-line b{color:var(--blue-dk)}
.tolc__empty{text-align:center;padding:28px 18px;color:var(--text-secondary);font-size:14px;line-height:1.65;background:var(--bg-subtle);border:1px dashed var(--bg-border);border-radius:var(--r-md)}
.tolc__empty > svg{width:38px;height:38px;fill:var(--blue-line);margin-bottom:8px}
.tolc__empty .tag-mil{color:var(--blue);font-weight:800}
.tolc__empty .tag-delik{color:var(--green);font-weight:800}
.tolc__empty-btn{display:inline-flex;align-items:center;gap:7px;margin-top:14px;min-height:44px;font-size:13px;font-weight:800;color:#fff;background:var(--green);border:none;border-radius:100px;padding:9px 18px;font-family:inherit}
.tolc__empty-btn svg{width:13px;height:13px;fill:#fff}
.tolc__note{margin-top:18px;display:flex;align-items:flex-start;gap:11px;font-size:13px;color:var(--text-muted);line-height:1.6;background:var(--bg-light);border:1px solid var(--bg-border);border-radius:var(--r-md);padding:13px 16px}
.tolc__note svg{width:16px;height:16px;fill:var(--blue);flex-shrink:0;margin-top:2px}
.tolc__note strong{color:var(--text-secondary)}

.tolc__seo{margin-top:16px}
.tolc__guide{background:var(--bg-white);border:1px solid var(--bg-border);border-radius:var(--r-xl);padding:clamp(18px,2.4vw,30px);box-shadow:var(--shadow-md)}
.tolc__guide-title{position:relative;margin:0 0 14px;padding-left:14px;font-family:var(--ff-head);font-size:clamp(20px,2vw,26px);font-weight:700;letter-spacing:-.02em;color:var(--text-primary)}
.tolc__guide-title::before{content:'';position:absolute;left:0;top:4px;bottom:4px;width:4px;border-radius:3px;background:linear-gradient(180deg,#5B7FA6,#1D426A)}
.tolc__guide-answer{max-width:96ch;margin:0;padding:14px 18px;border:1px solid var(--blue-line);border-left:4px solid var(--blue);border-radius:12px;background:var(--blue-soft);font-size:15px;line-height:1.7;color:var(--text-secondary)}
.tolc__guide-answer strong{color:var(--text-primary)}
.tolc__guide-grid{display:grid;grid-template-columns:minmax(0,1fr);gap:22px;margin-top:22px;align-items:start}
.tolc__guide-h{margin:0 0 12px;font:700 12px/1.3 var(--ff-mono);letter-spacing:.12em;text-transform:uppercase;color:var(--text-muted)}
.tolc__classes{display:grid;gap:10px;margin:0;padding:0;list-style:none}
.tolc__classes li{padding:12px 14px;border:1px solid var(--bg-border);border-radius:12px;background:var(--bg-subtle);transition:border-color .2s,background .2s}
.tolc__classes li:hover{border-color:var(--blue-line);background:#fff}
.tolc__cls-top{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:6px}
.tolc__cls-k{font:800 17px/1.2 var(--ff-mono);letter-spacing:.02em;color:var(--blue-dk)}
.tolc__classes p{margin:0;font-size:13.5px;line-height:1.6;color:var(--text-secondary)}
@media(min-width:1200px){.tolc__guide-grid{grid-template-columns:minmax(0,.78fr) minmax(0,1.22fr);gap:clamp(22px,2.6vw,36px)}}
@media(min-width:576px) and (max-width:1199.98px){.tolc__classes{grid-template-columns:repeat(2,minmax(0,1fr))}}
.tolc__seo-note{background:var(--blue-soft);border:1px solid var(--blue-line);border-radius:var(--r-sm);padding:11px 14px;font-size:14px;margin-top:10px}

.tolc__presets,.tolc__goal{margin-top:18px;padding-top:16px;border-top:1px dashed var(--bg-border)}
.tolc__presets-lbl,.tolc__goal-lbl{font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--text-light);margin-bottom:10px;display:block}
.tolc__presets-grid,.tolc__goal-grid{display:flex;gap:10px;flex-wrap:wrap}
.tolc__preset{display:flex;flex-direction:column;gap:3px;align-items:flex-start;background:var(--bg-white);border:1.5px solid var(--bg-border);border-radius:var(--r-md);padding:11px 15px;min-width:140px;min-height:56px;transition:border-color .2s,transform .2s,box-shadow .2s;font-family:inherit;text-align:left}
.tolc__preset:hover{border-color:var(--blue-line);transform:translateY(-2px);box-shadow:var(--shadow-md)}
.tolc__preset-name{font-family:var(--ff-head);font-size:14px;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:7px}
.tolc__preset-name::before{content:'';width:9px;height:9px;border-radius:50%}
.tolc__preset--bos .tolc__preset-name::before{background:var(--green)}
.tolc__preset--gec .tolc__preset-name::before{background:var(--yellow)}
.tolc__preset--siki .tolc__preset-name::before{background:var(--red)}
.tolc__preset-pair{font-family:var(--ff-mono);font-size:12px;font-weight:700;color:var(--blue)}
.tolc__goal-btn{display:flex;align-items:center;gap:8px;min-height:44px;background:var(--bg-white);border:1.5px solid var(--bg-border);border-radius:100px;padding:9px 16px;font-size:13.5px;font-weight:700;color:var(--text-secondary);transition:background .2s,color .2s,border-color .2s;font-family:inherit}
.tolc__goal-btn svg{width:16px;height:16px;fill:var(--text-muted);transition:fill .2s}
.tolc__goal-btn:hover,.tolc__goal-btn[aria-pressed="true"]{border-color:var(--blue-line);color:var(--blue);background:var(--blue-soft)}
.tolc__goal-btn:hover svg,.tolc__goal-btn[aria-pressed="true"] svg{fill:var(--blue)}
.tolc__goal-out{margin-top:12px;background:var(--blue-soft);border:1px solid var(--blue-line);border-radius:var(--r-sm);padding:12px 15px;font-size:14px;color:var(--text-secondary);line-height:1.6}
.tolc__goal-out:empty{display:none}
.tolc__goal-out b{font-family:var(--ff-mono);color:var(--blue-dk)}

.tolc__toolbar{display:flex;gap:9px;flex-wrap:wrap}
.tolc__tool-btn{display:inline-flex;align-items:center;gap:7px;min-height:44px;font-size:13px;font-weight:700;color:var(--text-secondary);background:var(--bg-white);border:1.5px solid var(--bg-border);border-radius:100px;padding:9px 16px;text-decoration:none;transition:background .2s,color .2s,border-color .2s;font-family:inherit}
.tolc__tool-btn svg{width:15px;height:15px;fill:var(--text-muted);transition:fill .2s}
.tolc__tool-btn:hover{border-color:var(--blue-line);color:var(--blue);background:var(--blue-soft)}
.tolc__tool-btn:hover svg{fill:var(--blue)}
.tolc__tool-btn.is-ok{border-color:var(--green);color:var(--green);background:rgba(31,122,82,.08)}
.tolc__tool-btn.is-ok svg{fill:var(--green)}
.tolc__tool-btn--cta{background:var(--blue-grad);color:#fff;border-color:transparent;box-shadow:var(--shadow-blue)}
.tolc__tool-btn--cta svg{fill:#fff}
.tolc__tool-btn--cta:hover{color:#fff;background:var(--blue-grad)}
.tolc__tool-btn--cta:hover svg{fill:#fff}

.tolc__disclaimer{margin-top:20px;background:linear-gradient(180deg,#FFF8F0,#FFFFFF);border:1px solid #F0D9B8;border-left:4px solid var(--yellow);border-radius:var(--r-md);padding:16px 18px;display:flex;align-items:flex-start;gap:13px}
.tolc__disclaimer svg{width:22px;height:22px;fill:var(--yellow-dk);flex-shrink:0;margin-top:1px}
.tolc__disclaimer-body{font-size:13px;color:var(--text-secondary);line-height:1.7}
.tolc__disclaimer-body strong{color:var(--text-primary)}
.tolc__disclaimer-body > strong:first-child{display:block;font-family:var(--ff-head);font-size:14px;margin-bottom:4px}

.tolc__adv{margin-top:16px;border:1px solid var(--bg-border);border-radius:var(--r-md);overflow:hidden;background:var(--bg-white)}
.tolc__adv summary{list-style:none;font-family:var(--ff-head);font-size:14px;font-weight:700;color:var(--text-primary);padding:13px 16px;min-height:50px;display:flex;align-items:center;gap:9px}
.tolc__adv summary::-webkit-details-marker{display:none}
.tolc__adv summary::after{content:'+';margin-left:auto;font-size:20px;color:var(--blue);transition:transform .25s}
.tolc__adv[open] summary::after{transform:rotate(45deg)}
.tolc__adv summary svg{width:17px;height:17px;fill:var(--blue)}
.tolc__adv-body{padding:0 16px 16px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.tolc__adv-field{display:flex;flex-direction:column;gap:6px}
.tolc__adv-field label{font-size:12px;font-weight:800;letter-spacing:.05em;text-transform:uppercase;color:var(--text-secondary)}
.tolc__adv-field input,.tolc__adv-field select{font-family:var(--ff);font-size:16px;font-weight:600;color:var(--text-primary);padding:10px 12px;min-height:46px;border:1.5px solid var(--bg-border);border-radius:var(--r-sm);background:var(--bg-subtle)}
.tolc__adv-field input:focus,.tolc__adv-field select:focus{outline:none;border-color:var(--blue-metal);background:var(--bg-white);box-shadow:0 0 0 3px rgba(42,85,133,.15)}
.tolc__adv-note{grid-column:1/-1;font-size:13px;color:var(--text-muted);line-height:1.55}

.tolc__lib-table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;border:1px solid var(--bg-border);border-radius:var(--r-md);background:var(--bg-white)}
.tolc__lib-table{width:100%;border-collapse:separate;border-spacing:0;font-size:14px}
.tolc__lib-table th{background:var(--bg-light);color:var(--text-secondary);font-family:var(--ff-mono);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;text-align:left;padding:11px 14px;white-space:nowrap}
.tolc__lib-table td{padding:11px 14px;border-top:1px solid var(--bg-border);color:var(--text-secondary);vertical-align:middle;line-height:1.5}
.tolc__lib-table tbody tr{transition:background .2s}
.tolc__lib-table tbody tr:hover td{background:var(--blue-soft)}
.tolc__lib-pair{font-family:var(--ff-mono);font-weight:800;color:var(--blue-dk);font-size:14.5px;white-space:nowrap}
.tolc__fitpill{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:100px;border:1px solid;font-size:12px;font-weight:800;white-space:nowrap}
.tolc__fitpill::before{content:'';width:7px;height:7px;border-radius:50%;background:currentColor}
.tolc__fitpill--bos{color:var(--green);background:rgba(31,122,82,.10);border-color:rgba(31,122,82,.30)}
.tolc__fitpill--gec{color:#7A5E0F;background:rgba(200,185,142,.22);border-color:rgba(168,154,112,.5)}
.tolc__fitpill--siki{color:var(--red);background:rgba(178,58,46,.09);border-color:rgba(178,58,46,.30)}
.tolc__lib-act{text-align:right;white-space:nowrap}
.tolc__lib-apply{display:inline-flex;align-items:center;gap:5px;min-height:40px;font-size:12.5px;font-weight:700;color:var(--blue);background:var(--bg-white);border:1.5px solid var(--blue-line);border-radius:100px;padding:7px 14px;white-space:nowrap;font-family:inherit;transition:background .2s,color .2s,border-color .2s}
.tolc__lib-apply:hover{background:var(--blue);border-color:var(--blue);color:#fff}
@media(max-width:767.98px){
  .tolc__lib-table-wrap{border:0;border-radius:0;background:transparent;overflow:visible}
  .tolc__lib-table,.tolc__lib-table tbody{display:block}
  .tolc__lib-table thead{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}
  .tolc__lib-table tbody{display:grid;gap:10px}
  .tolc__lib-table tr{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:6px 10px;align-items:center;padding:12px 14px;border:1px solid var(--bg-border);border-radius:14px;background:var(--bg-white)}
  .tolc__lib-table td{display:block;padding:0;border:0;background:transparent !important}
  .tolc__lib-table td:nth-child(2){justify-self:end}
  .tolc__lib-table td:nth-child(3){grid-column:1/-1;font-size:13.5px}
  .tolc__lib-table .tolc__lib-act{grid-column:1/-1;text-align:left}
  .tolc__lib-apply{width:100%;justify-content:center}
}

/* ── CTA + VİDEO ── */
.cta-vid{padding:clamp(20px,2.6vw,32px) 0;background:var(--navy);position:relative;overflow:hidden}
.cta-vid::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--sky) 0%,var(--blue-metal) 50%,transparent 100%);z-index:5}
.cta-vid::after{content:'';position:absolute;inset:0;pointer-events:none;opacity:.06;background-image:linear-gradient(rgba(175,195,218,1) 1px,transparent 1px),linear-gradient(90deg,rgba(175,195,218,1) 1px,transparent 1px);background-size:42px 42px}
.cta-vid__grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:stretch;position:relative;z-index:2}
.cta-vid__video{border-radius:var(--r-lg);overflow:hidden;border:1px solid rgba(175,195,218,.18);position:relative;min-height:340px;box-shadow:var(--shadow-md);background:var(--navy-2)}
.cta-vid__video video{width:100%;height:100%;object-fit:cover;display:block;min-height:340px}
.cta-vid__video .media-toggle{position:absolute;right:12px;bottom:12px}
.cta-vid__box{background:rgba(237,241,247,.04);border-radius:var(--r-lg);border:1px solid rgba(175,195,218,.18);padding:clamp(32px,4vw,52px) clamp(24px,3vw,38px);display:flex;flex-direction:column;justify-content:center;position:relative;overflow:hidden}
.cta-vid__eyebrow{font-size:12px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:var(--sky);margin-bottom:13px;display:inline-flex;align-items:center;gap:8px}
.cta-vid__eyebrow::before{content:'';width:16px;height:2px;background:var(--sky);border-radius:2px}
.cta-vid__title{font-family:var(--ff-head);font-size:clamp(26px,3.4vw,40px);font-weight:700;color:#EDF2F7;line-height:1.08;letter-spacing:-.025em;margin-bottom:13px}
.cta-vid__title .yel{background:linear-gradient(120deg,var(--sky-lt),#5B7FA6);-webkit-background-clip:text;background-clip:text;color:transparent}
.cta-vid__desc{font-size:15px;color:rgba(237,241,247,.78);line-height:1.75;margin-bottom:28px}
.cta-vid__btn-main{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:50px;font-size:14px;font-weight:700;padding:15px 28px;border-radius:100px;text-decoration:none;background:var(--chrome);color:var(--navy);box-shadow:var(--shadow-md);transition:transform .25s,box-shadow .25s;margin-bottom:14px;border:1px solid rgba(255,255,255,.7);align-self:flex-start}
.cta-vid__btn-main svg{width:16px;height:16px;flex-shrink:0}
.cta-vid__btn-main:hover{color:var(--navy);transform:translateY(-2px);box-shadow:var(--shadow-lg)}
.cta-vid__phones{display:grid;grid-template-columns:1fr 1fr;gap:9px;max-width:460px}
.cta-vid__phone{display:flex;align-items:center;justify-content:center;gap:8px;min-height:48px;font-size:14px;font-weight:700;padding:12px 14px;border-radius:12px;text-decoration:none;background:rgba(237,241,247,.06);color:rgba(237,241,247,.88);border:1px solid rgba(237,241,247,.18);transition:border-color .25s,background .25s,color .25s;white-space:nowrap}
.cta-vid__phone svg{width:14px;height:14px;fill:currentColor;flex-shrink:0}
.cta-vid__phone:hover{border-color:var(--sky);color:#fff;background:rgba(91,127,166,.12)}

/* ── RESPONSIVE (375 / 768 / 1024 / 1440) ── */
@media(max-width:991px){
  .hero{border-radius:0 0 20px 20px}
  .section-index{display:none}
  .about__body{padding-left:0;margin-top:26px}
  .vid-map__grid,.cta-vid__grid{grid-template-columns:1fr}
  .tolc__panel-grid{grid-template-columns:1fr;gap:18px}
  .tolc__adv-body{grid-template-columns:1fr 1fr}
}
@media(max-width:767px){
  .hero__title{font-size:clamp(28px,8vw,44px)}
  .hero-ticker{height:62px}.t-partner,.t-kur{height:62px}.t-partner{padding:0 16px}.t-kur{padding:0 12px}
  .tolc__cards{grid-template-columns:1fr 1fr}
  .tolc-row__track{height:30px}
}
@media(max-width:575px){
  .hero__actions{flex-direction:column;align-items:stretch;gap:10px}
  .fl-btn-primary,.fl-btn-outline{width:100%}
  .t-partner__desc{display:none}
  .cta-vid__phones{grid-template-columns:1fr}
  .tolc__panel{border-radius:var(--r-lg);padding:16px}
  .tolc__btn{flex:1}
  .tolc__count{margin-left:0;width:100%;text-align:center;order:3}
  .tolc-row__vals{margin-left:0;width:100%}
  .tolc__cards,.tolc__adv-body{grid-template-columns:1fr}
}
/* Dokunmatik cihazlar: tüm hedefler ≥44px */
@media(pointer:coarse){
  .tolc__dia-preset,.tolc__quick-btn{min-height:44px;padding:8px 13px}
  .tolc__lib-apply,.pdfM__dl{min-height:44px}
}
/* Yalnız fare kullanıcılarına hover efekti */
@media(hover:none){.prod-card:hover,.eng__tool:hover,.pdf__card:hover{transform:none}}

/* Hareket azaltma — sonsuz animasyonları durdur, ticker'ı kaydırılabilir yap */
@media(prefers-reduced-motion:reduce){
  html{scroll-behavior:auto}
  *,*::before,*::after{animation:none !important;transition-duration:.01ms !important;transition-delay:0s !important}
  .js .reveal{opacity:1;transform:none}
  .hero-ticker__viewport{overflow-x:auto;scrollbar-width:thin}
  .hero-ticker__track [data-dup]{display:none}
  .hero-ticker__ctrl{display:none}
  .tolc-bar{transform:scaleX(1)}
}

/* YAZDIRMA / PDF — yalnız araç sonuçları */
@media print{
  body *{visibility:hidden !important}
  #tolc-print-area,#tolc-print-area *{visibility:visible !important}
  #tolc-print-area{position:absolute;inset:0;width:100%}
  .tolc__toolbar{display:none !important}
  .tolc__block{box-shadow:none;border:1px solid #ccc;break-inside:avoid}
  .tolc-bar{print-color-adjust:exact;-webkit-print-color-adjust:exact;transform:scaleX(1) !important}
  .tolc__disclaimer{break-inside:avoid}
}

/* ═══ GÖRSEL İNCELTME KATMANI (v2) ═══ */
/* Boyutsuz satır içi ikonlar asla dev görünmesin */
:where(a,button) > svg:not([width]):not([class]){width:1em;height:1em;flex-shrink:0}

/* Bölüm başlık ölçeği */
.section-title{font-size:clamp(28px,3.6vw,46px);line-height:1.1}
.section-index{font-size:clamp(40px,5vw,64px);opacity:.55}
.section-head-deco{margin-bottom:6px}
.section-lead{font-size:16px;margin-top:14px;margin-bottom:26px}

/* HERO: iki kolon + sağda ürün kartı */
.hero__body{padding-top:clamp(64px,8vw,112px);padding-bottom:clamp(48px,5vw,72px)}
.hero__layout{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(300px,.65fr);gap:clamp(28px,4vw,64px);align-items:end}
.hero__title{font-size:clamp(34px,4.8vw,60px);max-width:20ch;line-height:1.06}
.hero__card{position:relative;background:linear-gradient(160deg,rgba(255,255,255,.10),rgba(255,255,255,.03));border:1px solid rgba(160,200,240,.22);border-radius:22px;padding:22px;backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);box-shadow:0 30px 60px -30px rgba(0,0,0,.6),inset 0 1px 0 rgba(255,255,255,.12)}
.hero__card::before{content:'';position:absolute;inset:-1px;border-radius:22px;padding:1px;background:linear-gradient(140deg,rgba(91,127,166,.55),transparent 40%,transparent 70%,rgba(200,185,142,.45));-webkit-mask:linear-gradient(#000 0 0) content-box,linear-gradient(#000 0 0);-webkit-mask-composite:xor;mask-composite:exclude;pointer-events:none}
.hero__card-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px}
.hero__card-tag{font-size:12px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:var(--sky-lt)}
.hero__card-live{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:#8EE0B5}
.hero__card-live::before{content:'';width:7px;height:7px;border-radius:50%;background:#34D399;box-shadow:0 0 0 3px rgba(52,211,153,.2)}
.hero__card-list{display:flex;flex-direction:column;gap:6px}
.hero__card-list a{display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:48px;padding:10px 12px;border-radius:12px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);color:#E6EEF7;transition:background .2s,border-color .2s,transform .2s}
.hero__card-list a:hover{background:rgba(91,127,166,.12);border-color:rgba(91,127,166,.35);transform:translateX(3px);color:#fff}
.hero__card-list .n{font-size:14px;font-weight:600;line-height:1.3}
.hero__card-list .t{font-family:var(--ff-mono);font-size:12px;font-weight:700;color:var(--sky-lt);background:rgba(91,127,166,.12);border:1px solid rgba(91,127,166,.25);padding:3px 8px;border-radius:7px;white-space:nowrap}
.hero__card-cta{margin-top:14px;display:flex;align-items:center;justify-content:center;gap:8px;min-height:48px;border-radius:12px;background:var(--chrome);color:var(--navy);font-weight:800;font-size:14px;border:1px solid rgba(255,255,255,.7);transition:transform .2s,box-shadow .2s}
.hero__card-cta:hover{color:var(--navy);transform:translateY(-2px);box-shadow:0 14px 30px -12px rgba(0,0,0,.5)}
.hero__card-cta svg{width:16px;height:16px}
.hero__media-ctrl{bottom:88px}

/* Araç kartları: isim/kategori alt alta */
.eng__tool-name,.eng__tool-cat{display:block}
.eng__tool-name{font-size:14.5px}

/* Kartlarda daha yumuşak derinlik */
.pdf__card,.eng__tool,.prod-card,.faq__item{border-color:#D6E1EE}

@media(max-width:991px){
  .hero__layout{grid-template-columns:1fr}
  .hero__card{display:none}
}
@media(max-width:575px){
  .hero__body{padding-top:48px;padding-bottom:36px}
  .hero__stats{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  .hero__stat{border:0;margin:0;padding:0}
}

/* ═══ TEMA KALKANI — assets/css/style.css + Bootstrap ile çakışmaları nötrle ═══
   Bu blok içerik bölümünde, tema CSS'inden SONRA yüklenir. */
/* 1) Tipografi: tema p/h için DM Sans, gri renk, 25px satır ve 15px alt boşluk basıyor */
:where(.fl-home) :is(p,li,dd,dt,td,th,span,label,small,address){font-family:inherit}
:where(.fl-home) p{font-size:inherit;line-height:inherit;color:inherit;margin:0;font-weight:inherit}
:where(.fl-home) :is(h1,h2,h3,h4,h5,h6){font-family:var(--ff-head);color:var(--text-primary);font-weight:700;line-height:1.25;margin:0;transition:none}
:where(.fl-home) :is(ul,ol){margin:0;padding:0;list-style:none}
:where(.fl-home) :is(dl,dd,figure,blockquote){margin:0}
:where(.fl-home) a{text-decoration:none}
.fl-home .tolc__seo-list{list-style:disc;padding-left:22px}
.fl-home .tolc__seo-body p{margin-bottom:12px}

/* 2) Tema: a,button{display:inline-flex;min-height:48px;min-width:48px;align-items:center} !important */
.fl-home .eng__tool,
.fl-home .hero__card-list a,
.fl-home .hero__card-cta,
.fl-home .cta-vid__phone{display:flex !important}
.fl-home .eng__tool{width:100%}
.fl-home .tolc__preset{display:flex !important;align-items:flex-start !important}
.fl-home .prod-card__title a,
.fl-home .spec tbody th a,
.fl-home .vid-map__map-detail a,
.fl-home .tolc__seo-body a{display:inline !important;min-height:0 !important;min-width:0 !important}
.fl-home .tolc-chip__x{min-height:26px !important;min-width:26px !important;justify-content:center}
.fl-home .tolc__dia-preset,.fl-home .tolc__quick-btn{min-height:36px !important;min-width:44px !important;justify-content:center}
.fl-home .tolc__lib-apply,.fl-home .pdfM__dl{min-height:40px !important}
.fl-home .media-toggle,.fl-home .pdfM__x{min-height:44px !important;min-width:44px !important;justify-content:center}
.fl-home .t-partner{min-height:0 !important}
@media(pointer:coarse){.fl-home .tolc__dia-preset,.fl-home .tolc__quick-btn,.fl-home .tolc__lib-apply,.fl-home .pdfM__dl{min-height:44px !important}}
/* tema: button:focus{border:0} → kenarlı butonlar odakta kaymasın */
.fl-home :is(.tolc__btn--ghost,.tolc__preset,.tolc__goal-btn,.tolc__tool-btn):focus{border-style:solid;border-width:1.5px}
.fl-home :is(.tolc__dia-preset,.tolc__quick-btn,.pdf__btn-v):focus{border-style:solid;border-width:1px}
.fl-home .media-toggle:focus{border-style:solid;border-width:1px}

/* 3) Tema: img{height:auto !important} → kaplama (cover) görseller */
.fl-home .hero__poster,
.fl-home .prod-card__img img,
.fl-home .about__img-wrap img{height:100% !important;width:100%;object-fit:cover}
.fl-home .hero__poster{content-visibility:visible !important}

/* ═══ IZGARA DENGESİ + TABLET/MOBİL (v3) ═══ */
/* Mühendislik araçları: 10 öğe → 5×2 (masaüstü), 2 sütun (tablet), kompakt 2'li kutucuk (mobil) */
.fl-home .eng__tools-grid{grid-template-columns:repeat(5,minmax(0,1fr));gap:14px}
.fl-home .eng__tools-grid > li{display:flex;min-width:0}
.fl-home .eng__tool{min-height:72px}
/* PDF: 7 öğe → katalog kartı 2 sütun kaplar (4 sütunda 2 tam satır) */
.fl-home .pdf__grid{grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.fl-home .pdf__grid > li{display:flex;min-width:0}
.fl-home .pdf__grid > li > .pdf__card{width:100%}
.fl-home .pdf__item--featured{grid-column:span 2}
.fl-home .pdf__item--featured .pdf__card{background:linear-gradient(135deg,#11273D,#11273D);border-color:transparent;color:#fff}
.fl-home .pdf__item--featured .pdf__name{color:#fff;font-size:17px}
.fl-home .pdf__item--featured .pdf__desc{color:rgba(237,241,247,.8);font-size:13.5px}
.fl-home .pdf__item--featured .pdf__ico{background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.25)}
.fl-home .pdf__item--featured .pdf__ico svg{fill:#AFC3DA}
.fl-home .pdf__item--featured .pdf__meta span{background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.2);color:#EDF1F7}
.fl-home .pdf__item--featured .pdf__btn-v{background:transparent;color:#fff;border-color:rgba(255,255,255,.35)}
.fl-home .pdf__item--featured .pdf__btn-v svg{fill:#fff}
.fl-home .pdf__item--featured .pdf__btn-d{background:var(--chrome);color:var(--navy)}
.fl-home .pdf__item--featured .pdf__btn-d svg{fill:var(--navy)}
.fl-home .pdf__item--featured .pdf__acts{max-width:420px}

@media(max-width:1199px){
  .fl-home .eng__tools-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
  .fl-home .pdf__grid{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media(max-width:991px){
  .fl-home .hero__title{max-width:none}
  .fl-home .section-lead{font-size:15px}
}
@media(max-width:575px){
  /* Araçlar: 2'li kompakt kutucuk */
  .fl-home .eng__tools-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
  .fl-home .eng__tool{flex-direction:column;align-items:flex-start;gap:10px;padding:14px;min-height:112px}
  .fl-home .eng__tool-arrow{display:none}
  .fl-home .eng__tool-name{font-size:14px}
  /* PDF: tek sütun */
  .fl-home .pdf__grid{grid-template-columns:minmax(0,1fr)}
  .fl-home .pdf__item--featured{grid-column:auto}
}

/* ═════════════════════════════════════════════════════════════
   v4 — PROFESYONEL GÖRSEL KATMAN
   Ritim: beyaz / açık gri bantlar · kurumsal mavi vurgu · yumuşak derinlik
═════════════════════════════════════════════════════════════ */
.fl-home{
  --surface:#FFFFFF; --surface-2:#F7F7FF; --ink:#0A1828; --ink-2:#3A4A5C;
  --line:#DEDEEA; --line-2:#EBEBF5;
  --e1:0 1px 2px rgba(10,24,40,.04),0 1px 3px rgba(10,24,40,.06);
  --e2:0 2px 4px rgba(10,24,40,.04),0 8px 24px -8px rgba(10,24,40,.12);
  --e3:0 4px 8px rgba(10,24,40,.04),0 24px 48px -16px rgba(10,24,40,.22);
  --radius:16px;
  background:var(--surface-2);
}
.fl-home .eng-tools::before{opacity:.18}

/* — Bölüm başlığı sistemi — */
.fl-home .section-tag{font-size:12px;letter-spacing:.18em;color:var(--blue);margin-bottom:14px;gap:10px}
.fl-home .section-tag::before{width:22px;height:2px;background:var(--yellow)}
.fl-home .section-title{font-size:clamp(30px,3.4vw,44px);font-weight:700;letter-spacing:-.025em;line-height:1.1;color:var(--ink)}
.fl-home .section-title span,.fl-home .section-title .yel{background:linear-gradient(120deg,#2A5585 0%,#11273D 100%);-webkit-background-clip:text;background-clip:text;color:transparent}
.fl-home .section-index{font-size:clamp(44px,5vw,68px);-webkit-text-stroke:1px #C9D6E5;opacity:.7}
.fl-home .section-head-deco{margin-bottom:4px;align-items:flex-end}
.fl-home .section-lead{font-size:16.5px;line-height:1.7;color:var(--ink-2);max-width:64ch;margin:16px 0 34px}

/* — HERO — */
.fl-home .hero{border-radius:0 0 32px 32px}
.fl-home .hero__title{letter-spacing:-.035em}
.fl-home .hero__eyebrow{background:rgba(91,127,166,.08);border-color:rgba(175,195,218,.28)}
.fl-home .fl-btn-primary{padding:14px 26px;box-shadow:0 10px 30px -10px rgba(42,85,133,.7),inset 0 1px 0 rgba(255,255,255,.18)}
.fl-home .hero__stats{padding-top:22px;border-top:1px solid rgba(237,241,247,.12);max-width:560px}
.fl-home .hero__card{border-radius:20px;padding:20px;background:linear-gradient(160deg,rgba(255,255,255,.11),rgba(255,255,255,.035))}
.fl-home .hero__card-list a{border-radius:11px}
/* Kayan şerit → hero içinde yüzen cam kart */
.fl-home .hero-ticker{margin:0 clamp(12px,3vw,40px) clamp(14px,2vw,24px);border-radius:16px;height:68px;border:1px solid rgba(255,255,255,.7);background:rgba(255,255,255,.96);box-shadow:0 20px 40px -20px rgba(0,0,0,.55);overflow:hidden}
.fl-home .hero-ticker__viewport::before{background:linear-gradient(to right,#fff,transparent)}
.fl-home .hero-ticker__viewport::after{background:linear-gradient(to left,#fff,transparent)}
.fl-home .t-partner,.fl-home .t-kur{height:68px;border-right-color:var(--line-2)}
.fl-home .t-kur{background:transparent}
.fl-home .hero-ticker__ctrl{border-left-color:var(--line-2)}

/* — KARŞILAŞTIRMA TABLOSU — (ayrıntı: v19 bloğu) */
.fl-home .spec-wrap{border-color:var(--line);border-radius:14px;margin-top:16px}

/* — ÜRÜN KARTLARI — */
.fl-home .prod-card{border-radius:18px;border:1px solid var(--line);box-shadow:var(--e1)}
.fl-home .prod-card:hover,.fl-home .prod-card:focus-within{transform:translateY(-6px);box-shadow:var(--e3);border-color:#D3DCE8}
.fl-home .prod-card__img{aspect-ratio:16/10;background:linear-gradient(150deg,#F0F0FA,#F7FAFD)}
.fl-home .prod-card__img::after{content:'';position:absolute;inset:auto 0 0 0;height:45%;background:linear-gradient(to top,rgba(10,24,40,.28),transparent);pointer-events:none;z-index:1}
.fl-home .prod-card__stock{z-index:3;border:0;background:rgba(255,255,255,.95);box-shadow:0 2px 8px rgba(0,0,0,.12);font-size:11.5px}
.fl-home .prod-card__badge{border:0;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.fl-home .prod-card__body{padding:20px 20px 18px}
.fl-home .prod-card__title{font-size:17px;line-height:1.3;margin-bottom:8px}
.fl-home .prod-card__short{font-size:14px;line-height:1.6;color:var(--ink-2)}
.fl-home .prod-card__more{font-size:14px}

/* — MÜHENDİSLİK ARAÇLARI — */
.fl-home .eng__tool{border-radius:14px;border-color:var(--line);box-shadow:var(--e1);padding:16px}
.fl-home .eng__tool:hover{box-shadow:var(--e2);border-color:#D3DCE8;transform:translateY(-3px)}
.fl-home .eng__tool-ico{width:42px;height:42px;border-radius:12px;background:linear-gradient(160deg,#EDF1F7,#E1EDFA);border-color:#CFE0F4}
.fl-home .eng__tool-name{font-size:15px;font-weight:700;color:var(--ink)}
.fl-home .eng__tool-cat{font-size:11px;letter-spacing:.07em;color:var(--text-muted)}
.fl-home .eng__tool-arrow{background:transparent;border:0}
.fl-home .eng__footer{border-radius:14px;background:#fff;border-color:var(--line);box-shadow:var(--e1)}
.fl-home .eng__footer svg{fill:#1F7A52}

/* — PDF — */
.fl-home .pdf__card{border-radius:16px;border-color:var(--line);box-shadow:var(--e1);padding:20px}
.fl-home .pdf__card:hover{box-shadow:var(--e2);border-color:#D3DCE8}
.fl-home .pdf__item--featured .pdf__card{position:relative;overflow:hidden;box-shadow:var(--e3)}
.fl-home .pdf__item--featured .pdf__card::after{content:'';position:absolute;right:-60px;top:-60px;width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,rgba(91,127,166,.28),transparent 65%);pointer-events:none}
.fl-home .pdf__foot{border-radius:14px;background:var(--surface-2);border-color:var(--line)}

/* — ARAÇ PANELİ — */
.fl-home .tolc__panel{border-radius:22px;border-color:var(--line);box-shadow:var(--e3);background:#fff}
.fl-home .tolc__block{border-radius:20px;border-color:var(--line);box-shadow:var(--e2)}

/* — SSS: iki sütun — */
.fl-home .faq__layout{display:grid;grid-template-columns:minmax(0,.8fr) minmax(0,1.2fr);gap:clamp(28px,5vw,72px);align-items:start}
.fl-home .faq__aside{position:sticky;top:110px}
.fl-home .faq__aside .section-lead{margin-bottom:26px}
.fl-home .faq__help{background:linear-gradient(150deg,#11273D,#0A1828);border-radius:20px;padding:24px;color:#EDF1F7;box-shadow:var(--e3);position:relative;overflow:hidden}
.fl-home .faq__help::after{content:'';position:absolute;right:-50px;bottom:-50px;width:180px;height:180px;border-radius:50%;background:radial-gradient(circle,rgba(91,127,166,.22),transparent 65%)}
.fl-home .faq__help-title{font-family:var(--ff-head);font-size:18px;font-weight:700;color:#fff;margin-bottom:6px}
.fl-home .faq__help-text{font-size:14.5px;color:rgba(237,241,247,.78);line-height:1.6;margin-bottom:18px}
.fl-home .faq__help-acts{display:flex;flex-wrap:wrap;gap:10px;align-items:center;position:relative;z-index:1}
.fl-home .faq__help-tel{display:inline-flex !important;align-items:center;gap:8px;color:#fff;font-weight:700;font-size:14px;padding:0 14px;border-radius:100px;border:1px solid rgba(255,255,255,.25)}
.fl-home .faq__help-tel svg{width:14px;height:14px;fill:currentColor}
.fl-home .faq__help-tel:hover{color:#fff;background:rgba(255,255,255,.08)}
.fl-home .faq__list{margin-top:0;gap:10px}
.fl-home .faq__item{border-radius:14px;border-color:var(--line);box-shadow:var(--e1);background:#fff}
.fl-home .faq__item[open]{border-color:#C3D0E0;box-shadow:var(--e2)}
.fl-home .faq__q{gap:14px;padding:18px 20px;font-size:16px}
.fl-home .faq__num{font-family:var(--ff-mono);font-size:12px;font-weight:700;color:var(--text-light);flex-shrink:0;width:22px}
.fl-home .faq__q h3{flex:1}
.fl-home .faq__item[open] .faq__num{color:var(--blue)}
.fl-home .faq__a{padding:0 20px 20px 56px;color:var(--ink-2)}

/* — KONUM & CTA — */
.fl-home .vid-map__map{border-radius:20px;border-color:var(--line);box-shadow:var(--e2)}
.fl-home .cta-vid{border-radius:32px 32px 0 0}
.fl-home .cta-vid__box,.fl-home .cta-vid__video{border-radius:22px}
.fl-home .cta-vid__title{font-size:clamp(28px,3.4vw,44px)}

/* — Responsive — */
@media(max-width:991px){
  .fl-home .faq__layout{grid-template-columns:minmax(0,1fr)}
  .fl-home .faq__aside{position:static}
  .fl-home .hero-ticker{margin:0 0 14px;border-radius:14px}
}
@media(max-width:575px){
  .fl-home .faq__a{padding-left:20px}
  .fl-home .faq__num{display:none}
  .fl-home .hero{border-radius:0 0 22px 22px}
  .fl-home .hero-ticker{height:60px}
  .fl-home .t-partner,.fl-home .t-kur{height:60px}
  .fl-home .section-lead{font-size:15.5px;margin-bottom:24px}
}

/* ═════════════════════════════════════════════════════════════
   v5 — ÜRÜN IZGARASI
═════════════════════════════════════════════════════════════ */
.fl-home .eng__footer,.fl-home .pdf__foot{margin-top:14px}
.fl-home .legal-note{margin-top:10px}
.fl-home .tolc__seo{margin-top:14px}

/* ── ÜRÜN PORTFÖYÜ ── */
.fl-home .products{position:relative;overflow:hidden;background:
  radial-gradient(900px 380px at 100% 0%,rgba(42,85,133,.07),transparent 70%),
  radial-gradient(700px 320px at 0% 100%,rgba(200,185,142,.06),transparent 70%),#fff}
.fl-home .products__head{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;flex-wrap:wrap;margin-bottom:28px}
.fl-home .products__intro{max-width:720px}
.fl-home .products__intro .section-lead{margin-bottom:0}
.fl-home .products__acts{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.fl-home .products__link{display:inline-flex !important;align-items:center;gap:8px;min-height:48px;padding:0 18px;border-radius:100px;border:1.5px solid var(--line);background:#fff;color:var(--ink);font-size:14px;font-weight:700;transition:border-color .2s,color .2s,background .2s}
.fl-home .products__link svg{width:16px;height:16px;fill:var(--blue)}
.fl-home .products__link:hover{border-color:var(--blue);color:var(--blue)}

.fl-home .prod-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px}
.fl-home .prod-grid__item{display:flex;min-width:0}
.fl-home .prod-grid__item > .prod-card{width:100%}
/* kademeli giriş */
.fl-home .prod-grid__item:nth-child(4n+2){transition-delay:.05s}
.fl-home .prod-grid__item:nth-child(4n+3){transition-delay:.1s}
.fl-home .prod-grid__item:nth-child(4n+4){transition-delay:.15s}

.fl-home .prod-card{border-radius:18px;background:#fff;border:1px solid var(--line);box-shadow:var(--e1);transition:transform .35s var(--ease-out),box-shadow .35s,border-color .35s}
.fl-home .prod-card:hover,.fl-home .prod-card:focus-within{transform:translateY(-6px);box-shadow:var(--e3);border-color:#C3D0E0}
.fl-home .prod-card::after{height:3px;background:linear-gradient(90deg,var(--blue-metal),var(--sky),var(--yellow))}
.fl-home .prod-card__img{aspect-ratio:16/10;margin:8px 8px 0;border-radius:12px;overflow:hidden;background:linear-gradient(150deg,#E8EFF7,#F6F9FC)}
.fl-home .prod-card__img img{transition:transform .7s var(--ease-out)}
.fl-home .prod-card:hover .prod-card__img img{transform:scale(1.06)}
.fl-home .prod-card__badge{top:10px;right:10px;font-family:var(--ff-mono);font-size:11px;padding:4px 9px;border-radius:8px;background:rgba(10,24,40,.72);color:#fff;backdrop-filter:blur(4px)}
.fl-home .prod-card__badge--brand{background:var(--blue-grad)}
.fl-home .prod-card__stock{bottom:10px;left:10px;border-radius:100px;padding:5px 10px 5px 8px}
.fl-home .prod-card__body{padding:16px 18px 18px;display:flex;flex-direction:column;flex:1}
.fl-home .prod-card__title{font-size:16.5px;line-height:1.32;margin-bottom:6px}
.fl-home .prod-card__short{font-size:13.5px;line-height:1.6;color:var(--ink-2);margin-bottom:14px;-webkit-line-clamp:3}
.fl-home .prod-card__foot{margin-top:auto;padding-top:12px;border-top:1px solid var(--line-2);display:flex;align-items:center;justify-content:space-between}
.fl-home .prod-card__more{font-size:13.5px;font-weight:700;color:var(--blue)}
.fl-home .prod-card__arrow{width:30px;height:30px;border-radius:50%;background:var(--blue-soft);border:0}
.fl-home .prod-card:hover .prod-card__arrow{transform:none;background:var(--blue)}

@media(max-width:1199px){ .fl-home .prod-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:18px} }
@media(max-width:991px){
  .fl-home .prod-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
  .fl-home .products__head{align-items:flex-start}
}
/* Mobil: yatay kompakt kart (görsel solda) */
@media(max-width:575px){
  .fl-home .prod-grid{grid-template-columns:minmax(0,1fr);gap:12px}
  .fl-home .prod-card{flex-direction:row;border-radius:16px}
  .fl-home .prod-card__img{flex:0 0 38%;aspect-ratio:auto;min-height:100%;margin:8px 0 8px 8px;border-radius:10px}
  .fl-home .prod-card__badge{display:none}
  .fl-home .prod-card__stock{bottom:6px;left:6px;font-size:10.5px;padding:3px 8px 3px 6px}
  .fl-home .prod-card__body{padding:12px 14px}
  .fl-home .prod-card__title{font-size:15px}
  .fl-home .prod-card__short{font-size:13px;-webkit-line-clamp:2;margin-bottom:8px}
  .fl-home .prod-card__foot{padding-top:8px}
  .fl-home .prod-card__arrow{width:26px;height:26px}
  .fl-home .products__acts{width:100%}
  .fl-home .products__acts > *{flex:1 1 auto;justify-content:center;white-space:nowrap;padding-left:14px;padding-right:14px;font-size:13.5px}
  .fl-home .prod-card:hover,.fl-home .prod-card:focus-within{transform:none}
}

/* ═════════════════════════════════════════════════════════════
   v19 — ÜRÜN KARŞILAŞTIRMA TABLOSU (yeniden tasarım)
   Masaüstü: sabit sütun genişlikli tablo · Tablet/mobil: ürün kartları
   ═════════════════════════════════════════════════════════════ */
.fl-home .compare__answer{max-width:92ch;margin:0 0 20px;padding:14px 18px;border:1px solid var(--blue-line);border-left:4px solid var(--blue);border-radius:12px;background:var(--blue-soft);font-size:15px;line-height:1.7;color:var(--ink-2);text-wrap:pretty}
.fl-home .compare__answer strong{color:var(--ink)}
.fl-home .fx-table{background:#fff;border:1px solid #DEDEEA;border-radius:24px;box-shadow:0 2px 4px rgba(10,24,40,.03),0 18px 40px -22px rgba(17,39,61,.28);padding:clamp(18px,2.4vw,30px)}
.fl-home .fx-table__head{display:flex;align-items:baseline;justify-content:space-between;gap:6px 20px;flex-wrap:wrap}
.fl-home .fx-table__title{position:relative;padding-left:14px;font-size:clamp(18px,1.6vw,21px);color:var(--ink)}
.fl-home .fx-table__title::before{content:'';position:absolute;left:0;top:3px;bottom:3px;width:4px;border-radius:3px;background:linear-gradient(180deg,#5B7FA6,#1D426A)}
.fl-home .fx-table__meta{font:700 11.5px/1.4 var(--ff-mono);letter-spacing:.06em;text-transform:uppercase;color:var(--text-muted)}
.fl-home .fx-table .spec-wrap{margin-top:16px;border:1px solid var(--line);border-radius:16px;overflow-x:auto}
.fl-home .fx-table .spec{width:100%;min-width:0;table-layout:fixed;border-collapse:separate;border-spacing:0}
.fl-home .fx-table .spec col.c-urun{width:27%}
.fl-home .fx-table .spec col.c-tol{width:12%}
.fl-home .fx-table .spec col.c-sert{width:18%}
.fl-home .fx-table .spec col.c-ra{width:14%}
.fl-home .fx-table .spec col.c-kul{width:29%}
.fl-home .fx-table .spec thead th{background:#11273D;color:#F7F7FF;font-size:11.5px;letter-spacing:.08em;padding:15px 16px;white-space:normal}
.fl-home .fx-table .spec thead th:first-child{padding-left:20px}
.fl-home .fx-table .spec td,.fl-home .fx-table .spec tbody th{padding:16px;vertical-align:middle;font-size:14px;line-height:1.5;overflow-wrap:anywhere;transition:background .2s}
.fl-home .fx-table .spec tbody th{background:#fff;padding-left:20px;width:auto}
.fl-home .fx-table .spec td.mono{white-space:normal;font-size:13px;color:var(--ink)}
.fl-home .fx-table .spec tbody tr:nth-child(even) :is(td,th){background:#F9F9FF}
.fl-home .fx-table .spec tbody tr:hover :is(td,th){background:#EDF1F7}
.fl-home .fx-table .spec tbody th a{display:flex !important;align-items:baseline;gap:10px;font-size:15px;font-weight:700;color:var(--ink);line-height:1.35}
.fl-home .fx-table .spec tbody th a:hover{color:#1D426A;text-decoration:none}
.fl-home .fx-num{flex:0 0 auto;font-family:var(--ff-mono);font-size:12px;font-weight:700;color:#1D426A}
.fl-home .fx-name{min-width:0}
.fl-home .fx-pill{display:inline-block;padding:4px 10px;border-radius:8px;background:#EDF1F7;border:1px solid #C3D0E0;color:#11273D;font-family:var(--ff-mono);font-size:12.5px;font-weight:700;white-space:nowrap}
.fl-home .fx-na{color:var(--text-light);font-family:var(--ff-mono)}
.fl-home .fx-table__note{margin:14px 2px 0;font-size:12.5px;line-height:1.6;color:var(--text-muted)}
@media (min-width:992px){
  .fl-home .fx-table .spec tbody tr:hover th{box-shadow:inset 3px 0 0 #1D426A}
}
@media (max-width:991.98px){
  /* Tablet + mobil: tablo → ürün kartları (başlık + etiketli satırlar) */
  .fl-home .fx-table .spec-wrap{overflow:visible;border:0;border-radius:0}
  .fl-home .fx-table .spec{display:block;table-layout:auto}
  .fl-home .fx-table .spec colgroup{display:none}
  .fl-home .fx-table .spec thead{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}
  .fl-home .fx-table .spec tbody{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
  .fl-home .fx-table .spec tr{display:flex;flex-direction:column;border:1px solid var(--line);border-radius:14px;overflow:hidden;background:#fff}
  .fl-home .fx-table .spec tbody th{display:block;background:var(--bg-subtle) !important;border-top:0;padding:13px 15px}
  .fl-home .fx-table .spec td{display:grid;grid-template-columns:112px minmax(0,1fr);gap:10px;align-items:baseline;padding:10px 15px;background:#fff !important;min-width:0;white-space:normal}
  .fl-home .fx-table .spec td::before{content:attr(data-label);font-family:var(--ff);font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--text-muted)}
  .fl-home .fx-table .spec td.is-na{display:none}
  .fl-home .fx-pill{justify-self:start}
}
@media (max-width:575.98px){
  .fl-home .fx-table .spec tbody{grid-template-columns:minmax(0,1fr)}
  .fl-home .fx-table{padding:18px 14px;border-radius:20px}
  .fl-home .fx-table .spec td,.fl-home .fx-table .spec tbody th{padding:10px 13px}
  .fl-home .compare__answer{font-size:14.5px;padding:12px 14px}
}

/* ═════════════════════════════════════════════════════════════
   v10 — TÜM SAYFA: tek palet + ortak efekt dili
   #0A1828 gece · #11273D lacivert · #1D426A çelik · #5B7FA6 gri-mavi · #F7F7FF krem
   ═════════════════════════════════════════════════════════════ */
.fl-home{--steel:#1D426A;--steel-2:#5B7FA6;--cream:#F7F7FF;--night:#0A1828;--navy-s:#11273D;
  --band:#F7F7FF;
  --lift:0 4px 8px rgba(10,24,40,.04),0 22px 44px -20px rgba(17,39,61,.30)}

/* Bölüm başlıkları: hepsi aynı imza */
.fl-home .section-tag{color:var(--steel)}
.fl-home .section-tag::before{background:linear-gradient(90deg,var(--steel-2),var(--steel))}
.fl-home .section-title span,.fl-home .section-title .yel{background:linear-gradient(120deg,var(--steel) 0%,var(--navy-s) 100%);-webkit-background-clip:text;background-clip:text;color:transparent}
.fl-home .section-index{-webkit-text-stroke-color:#C3D0E0}

/* Kartlar: ortak hover (hafif kalkış + çelik çerçeve) */
.fl-home :is(.prod-card,.pdf__card,.eng__tool,.faq__item){transition:transform .3s var(--ease-out),box-shadow .3s var(--ease-out),border-color .3s}
@media (hover:hover){
  .fl-home :is(.prod-card,.pdf__card,.eng__tool):hover{transform:translateY(-4px);box-shadow:var(--lift);border-color:var(--steel-2)}
  .fl-home .faq__item:hover{border-color:var(--steel-2)}
}
.fl-home :is(.prod-card,.pdf__card,.eng__tool):focus-within{border-color:var(--steel)}

/* Koyu yüzeyler: aynı çelik ışıltısı */
.fl-home .faq__help,.fl-home .pdf__item--featured .pdf__card{background:radial-gradient(90% 80% at 100% 0%,rgba(91,127,166,.38) 0%,rgba(91,127,166,0) 60%),linear-gradient(150deg,#183452 0%,#11273D 55%,#0A1828 100%)}
.fl-home .cta-vid{background:radial-gradient(70% 90% at 90% 0%,rgba(91,127,166,.28) 0%,rgba(91,127,166,0) 60%),linear-gradient(180deg,#11273D 0%,#0A1828 100%)}

/* Tolerans aracı üst şeridi: palet içinde */
.fl-home .tolc__panel::before{background:linear-gradient(90deg,var(--steel-2),var(--steel),var(--cream))}

/* Odak halkası da çelik */
.fl-home :focus-visible{outline:2px solid var(--steel);outline-offset:3px}

/* ═════════════════════════════════════════════════════════════
   v11/v12 — ÜRÜN KARTI: hover'da teknik özellik çizgileri (blueprint)
   ═════════════════════════════════════════════════════════════ */
.fl-home .prod-card__ph{display:block;width:100%;height:100%}
.fl-home .prod-spec{position:absolute;z-index:2;top:8px;left:8px;right:8px;aspect-ratio:16/10;margin:0;border-radius:12px;overflow:hidden;pointer-events:none;
  background:linear-gradient(90deg,rgba(10,24,40,.50) 0%,rgba(10,24,40,.22) 55%,rgba(10,24,40,.06) 100%);opacity:0;visibility:hidden;transition:opacity .35s ease,visibility 0s linear .45s}
.fl-home .prod-spec::before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(247,247,255,.05) 1px,transparent 1px),linear-gradient(90deg,rgba(247,247,255,.05) 1px,transparent 1px);background-size:18px 18px}
.fl-home .prod-spec__head{position:absolute;top:10px;left:12px;font-family:var(--ff-mono);font-size:10px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#F7F7FF;opacity:.85;text-shadow:0 1px 3px rgba(10,24,40,.8)}
.fl-home .prod-spec__row{position:absolute;left:14px;right:auto;display:flex;align-items:center;gap:0}
.fl-home .prod-spec__row--1{top:22%}.fl-home .prod-spec__row--2{top:40%}.fl-home .prod-spec__row--3{top:58%}.fl-home .prod-spec__row--4{top:76%}
.fl-home .prod-spec__dot{flex:0 0 auto;width:9px;height:9px;border-radius:50%;background:#F7F7FF;box-shadow:0 0 0 3px rgba(247,247,255,.22),0 0 12px rgba(247,247,255,.55);transform:scale(0);transition:transform .3s cubic-bezier(.34,1.56,.64,1)}
.fl-home .prod-spec__line{flex:0 0 40px;width:40px;min-width:40px;height:1.5px;margin:0 6px;background:linear-gradient(90deg,#F7F7FF,rgba(175,195,218,.9));transform:scaleX(0);transform-origin:left center;transition:transform .45s cubic-bezier(.16,1,.3,1)}
.fl-home .prod-spec__tag{flex:0 0 auto;display:inline-flex;align-items:baseline;justify-content:space-between;gap:10px;width:176px;margin:0;padding:4px 9px;border-radius:7px;background:rgba(10,24,40,.78);border:1px solid rgba(247,247,255,.45);box-shadow:0 4px 12px -4px rgba(10,24,40,.6);white-space:nowrap;opacity:0;transform:translateX(8px);transition:opacity .3s ease,transform .35s cubic-bezier(.16,1,.3,1)}
.fl-home .prod-spec__k{flex:0 0 auto;font-size:9.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#AFC3DA}
.fl-home .prod-spec__v{flex:0 1 auto;text-align:right;overflow:hidden;text-overflow:ellipsis;font-family:var(--ff-mono);font-size:11.5px;font-weight:700;color:#fff}

/* Açık hali: hover (fare), klavye odağı ya da dokunmatikte düğme */
.fl-home .prod-card.is-spec .prod-spec{opacity:1;visibility:visible;transition:opacity .3s ease,visibility 0s}
.fl-home .prod-card.is-spec .prod-spec__dot{transform:scale(1)}
.fl-home .prod-card.is-spec .prod-spec__line{transform:scaleX(1)}
.fl-home .prod-card.is-spec .prod-spec__tag{opacity:1;transform:none}
@media (hover:hover) and (pointer:fine){
  .fl-home .prod-card:is(:hover,:focus-within) .prod-spec{opacity:1;visibility:visible;transition:opacity .3s ease,visibility 0s}
  .fl-home .prod-card:is(:hover,:focus-within) .prod-spec__dot{transform:scale(1)}
  .fl-home .prod-card:is(:hover,:focus-within) .prod-spec__line{transform:scaleX(1)}
  .fl-home .prod-card:is(:hover,:focus-within) .prod-spec__tag{opacity:1;transform:none}
  .fl-home .prod-card:hover .prod-card__img img{transform:scale(1.02)}
}
/* Sıralı giriş: nokta → çizgi → etiket, satır satır */
.fl-home .prod-spec__row--1 .prod-spec__dot{transition-delay:.05s}.fl-home .prod-spec__row--1 .prod-spec__line{transition-delay:.12s}.fl-home .prod-spec__row--1 .prod-spec__tag{transition-delay:.3s}
.fl-home .prod-spec__row--2 .prod-spec__dot{transition-delay:.13s}.fl-home .prod-spec__row--2 .prod-spec__line{transition-delay:.2s}.fl-home .prod-spec__row--2 .prod-spec__tag{transition-delay:.38s}
.fl-home .prod-spec__row--3 .prod-spec__dot{transition-delay:.21s}.fl-home .prod-spec__row--3 .prod-spec__line{transition-delay:.28s}.fl-home .prod-spec__row--3 .prod-spec__tag{transition-delay:.46s}
.fl-home .prod-spec__row--4 .prod-spec__dot{transition-delay:.29s}.fl-home .prod-spec__row--4 .prod-spec__line{transition-delay:.36s}.fl-home .prod-spec__row--4 .prod-spec__tag{transition-delay:.54s}
/* Kapanırken gecikme yok: hızlı ve eşzamanlı */
.fl-home .prod-card:not(.is-spec):not(:hover):not(:focus-within) :is(.prod-spec__dot,.prod-spec__line,.prod-spec__tag){transition-delay:0s;transition-duration:.2s}

/* Dokunmatik / klavye düğmesi */
.fl-home .prod-spec__btn{position:absolute;z-index:4;top:18px;left:18px;display:inline-flex !important;align-items:center !important;gap:6px;min-height:32px !important;min-width:0 !important;padding:0 11px;border-radius:999px;border:1px solid rgba(255,255,255,.6);background:rgba(10,24,40,.62);color:#fff;font-size:11.5px;font-weight:700;letter-spacing:.02em;cursor:pointer;-webkit-backdrop-filter:blur(4px);backdrop-filter:blur(4px);transition:background .2s}
.fl-home .prod-spec__btn svg{width:13px;height:13px;fill:currentColor}
.fl-home .prod-spec__btn[aria-expanded="true"]{background:#F7F7FF;color:#11273D;border-color:#F7F7FF}
@media (hover:hover) and (pointer:fine){
  .fl-home .prod-spec__btn{opacity:0;transition:opacity .2s}
  .fl-home .prod-spec__btn:focus-visible{opacity:1}
}
@media (pointer:coarse){.fl-home .prod-spec__btn{min-height:40px !important}}

/* Açıkken rozetler çekilir, çizgilerle çakışmasın */
.fl-home .prod-card__stock{transition:opacity .2s}
.fl-home .prod-card.is-spec .prod-card__stock{opacity:0}
@media (hover:hover) and (pointer:fine){.fl-home .prod-card:is(:hover,:focus-within) .prod-card__stock{opacity:0}}
@media (max-width:1199.98px){.fl-home .prod-spec__tag{width:164px}.fl-home .prod-spec__v{font-size:11px}}

/* Küçük telefon: kart yatay → özellikler kartın altında akordeon gibi açılır */
@media (max-width:575.98px){
  .fl-home .prod-card{flex-wrap:wrap;height:auto;align-content:flex-start}
  .fl-home .prod-card__img{min-height:0 !important;align-self:stretch}
  .fl-home .prod-spec{position:relative;inset:auto;flex:0 0 calc(100% - 16px);order:3;aspect-ratio:auto;margin:0 8px 8px;border-radius:12px;display:none;flex-direction:column;gap:7px;padding:34px 12px 12px;background:linear-gradient(135deg,#11273D,#0A1828)}
  .fl-home .prod-card.is-spec .prod-spec{display:flex}
  .fl-home .prod-spec__head{top:11px;left:12px}
  .fl-home .prod-spec__row{position:relative;top:auto !important;right:auto;left:0 !important}
  .fl-home .prod-spec__btn{top:12px;left:12px;padding:0 9px;font-size:11px}
  .fl-home .prod-spec__tag{width:min(230px,calc(100% - 70px))}
}
@media (prefers-reduced-motion:reduce){
  .fl-home .prod-spec,.fl-home .prod-spec *{transition-duration:.01s !important;transition-delay:0s !important}
}

/* ═════════════════════════════════════════════════════════════
   v13/v14/v15 — Bant ritmi · sıkı bölüm aralığı · hizalama
   ═════════════════════════════════════════════════════════════ */
.fl-home{--surface-2:#F7F7FF;--bg-page:#F7F7FF;--bg-subtle:#F7F7FF;background:#F7F7FF}
.fl-home :is(.compare,.pdf-docs,.vid-map){background:#F7F7FF}
.fl-home :is(.products,.eng-tools,.tolc,.faq,.about){background:#FFFFFF}
.fl-home :is(.products,.compare,.eng-tools,.pdf-docs,.tolc,.faq,.vid-map,.about){border-top:1px solid #ECECF6}
.fl-home .compare .section-lead{margin-bottom:26px}
.fl-home .compare .fx-table{margin-top:0}

/* Bölümler arası mesafe */
.fl-home :is(.products,.compare,.eng-tools,.pdf-docs,.tolc,.faq,.vid-map,.about){padding-top:clamp(28px,3.2vw,46px) !important;padding-bottom:clamp(28px,3.2vw,46px) !important}
.fl-home .cta-vid{margin-top:0 !important;padding-top:clamp(32px,3.8vw,52px) !important;padding-bottom:clamp(32px,3.8vw,52px) !important}
.fl-home .section-head-deco{margin-bottom:0}
.fl-home .section-tag{margin-bottom:8px}
.fl-home .section-lead{margin:10px 0 22px !important}
.fl-home .products__intro .section-lead{margin-bottom:0 !important}
.fl-home .products__head{margin-bottom:20px}

/* Yazı hizası ve akışı: tüm sayfada sola hizalı, dengeli satır kırılımı */
.fl-home :is(h1,h2,h3,.section-title,.prod-card__title,.pdf__name,.faq__q,.fx-table__title){text-wrap:balance}
.fl-home :is(p,li,dd,.section-lead){text-wrap:pretty}
.fl-home :is(.section-head-deco,.section-head-deco > div,.products__intro,.faq__aside){text-align:left}
.fl-home :is(.section-lead,.about p){max-width:64ch}
.fl-home :is(p,li,dd,td){hyphens:manual}
/* Ürün kartları: başlıklar 2 satır yer ayırır, açıklamalar 3 satırda kesilir → alt çizgiler aynı hizada */
.fl-home .prod-card__title{min-height:calc(2 * 1.3em);line-height:1.3;display:flex;align-items:flex-start}
.fl-home .prod-card__short{display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;min-height:calc(3 * 1.55em);line-height:1.55}
/* Katalog kartları: başlık ve açıklama yüksekliği sabit */
.fl-home .pdf__name{min-height:calc(2 * 1.3em);line-height:1.3}
.fl-home .pdf__desc{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:calc(2 * 1.5em);line-height:1.5}
.fl-home .pdf__item--featured :is(.pdf__name,.pdf__desc){min-height:0}
/* Hesaplama araçları: isimler tek satır, kategori etiketleri aynı hizada */
.fl-home .eng__tool-text{min-width:0}
.fl-home .eng__tool-name{display:block;font-size:14.5px;line-height:1.3;letter-spacing:-.01em;text-wrap:balance}
.fl-home .eng__tool{align-items:center}
.fl-home .eng__tool-cat{display:block;margin-top:3px}
@media (max-width:575.98px){
  .fl-home .prod-card__title{min-height:0}
  .fl-home .prod-card__short{min-height:0}
}

/* ═════════════════════════════════════════════════════════════
   v16 — FLUTECNICA SIGNATURE
   "Precision Engineering / Editorial Luxury" hero + responsive polish
   ═════════════════════════════════════════════════════════════ */
.fl-home{
  --sig-steel:#C9D6E5;
  --sig-steel-2:#8EA8C4;
  --sig-ice:#EEF5FC;
  --sig-gold:#C8B98E;
  --sig-ink:#07131F;
  --sig-panel:rgba(8,24,40,.72);
}

/* Genel ritim: daha editoryal başlık ve yüzey dengesi */
.fl-home .container{position:relative}
.fl-home .section-title{font-size:clamp(32px,4vw,52px);letter-spacing:-.035em;line-height:1.02}
.fl-home .section-tag{font-family:var(--ff-mono);font-size:11px;letter-spacing:.19em}
.fl-home :is(.fx-table,.eng__tool,.pdf__card,.faq__item,.tolc__panel){
  box-shadow:0 1px 0 rgba(255,255,255,.95) inset,0 18px 48px -36px rgba(10,24,40,.42);
}
.fl-home :is(.products,.eng-tools,.tolc,.faq){background-image:linear-gradient(180deg,rgba(255,255,255,.94),rgba(247,247,255,.72))}

/* HERO — sinematik ama kurumsal */
.fl-home .hero.hero--signature{
  min-height:min(960px,94svh);
  border-radius:0 0 34px 34px;
  isolation:isolate;
  background:#07131F;
  box-shadow:0 30px 80px -48px rgba(7,19,31,.9);
}
.fl-home .hero.hero--signature::after{
  height:1px;
  background:linear-gradient(90deg,transparent 2%,rgba(175,195,218,.75) 18%,rgba(200,185,142,.9) 50%,rgba(175,195,218,.55) 82%,transparent 98%);
}
.fl-home .hero--signature .hero__media{background:#07131F}
.fl-home .hero--signature .hero__poster,
.fl-home .hero--signature .hero__media video{
  filter:saturate(.95) contrast(1.05) brightness(1);
  transform:scale(1.015);
}
.fl-home .hero--signature .hero__media video.is-playing{opacity:1}
.fl-home .hero--signature .hero__media::after{
  /* Video görünsün: yalnız metnin durduğu sol taraf ve alt kenar koyulaştırılır */
  background:
    linear-gradient(90deg,rgba(4,14,24,.80) 0%,rgba(5,18,31,.55) 40%,rgba(6,20,34,.16) 68%,rgba(4,14,24,.06) 100%),
    linear-gradient(0deg,rgba(4,14,24,.72) 0%,rgba(4,14,24,0) 42%,rgba(4,14,24,.22) 100%);
}
.fl-home .hero--signature .hero__grid{
  opacity:.085;
  background-size:64px 64px;
  background-image:
    linear-gradient(rgba(175,195,218,.9) 1px,transparent 1px),
    linear-gradient(90deg,rgba(175,195,218,.9) 1px,transparent 1px);
  mask-image:linear-gradient(to right,black 0 72%,transparent 98%);
  -webkit-mask-image:linear-gradient(to right,black 0 72%,transparent 98%);
}
.fl-home .hero__edge{position:absolute;z-index:1;pointer-events:none;border-color:rgba(175,195,218,.25)}
.fl-home .hero__edge--a{top:94px;right:clamp(18px,4vw,68px);width:72px;height:72px;border-top:1px solid;border-right:1px solid}
.fl-home .hero__edge--b{bottom:132px;left:clamp(18px,4vw,68px);width:46px;height:46px;border-bottom:1px solid;border-left:1px solid}

.fl-home .hero--signature .hero__body{
  z-index:3;
  padding-top:clamp(92px,11vh,138px);
  padding-bottom:clamp(32px,5vh,58px);
  display:flex;align-items:center;flex:1;
}
.fl-home .hero--signature .hero__layout{
  width:100%;
  display:grid;
  grid-template-columns:minmax(0,1.08fr) minmax(360px,.72fr);
  gap:clamp(42px,6vw,92px);
  align-items:center;
}
.fl-home .hero__eyebrow-row{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:20px}
.fl-home .hero--signature .hero__eyebrow{
  margin:0;
  min-height:36px;
  padding:8px 14px;
  border-color:rgba(175,195,218,.25);
  background:rgba(8,24,40,.46);
  box-shadow:inset 0 1px 0 rgba(255,255,255,.05);
  font-family:var(--ff-mono);
  letter-spacing:.135em;
  font-size:10.5px;
}
.fl-home .hero__since{
  display:inline-flex;align-items:center;min-height:36px;padding:0 12px;
  border-left:1px solid rgba(175,195,218,.26);
  font-family:var(--ff-mono);font-size:10px;font-weight:800;letter-spacing:.16em;color:rgba(237,245,252,.64)
}
.fl-home .hero--signature .hero__title{
  max-width:12.7ch;
  margin:0 0 22px;
  font-size:clamp(44px,5.4vw,78px);
  font-weight:650;
  line-height:.94;
  letter-spacing:-.055em;
  text-wrap:balance;
}
.fl-home .hero__title-line{display:block;color:#fff}
.fl-home .hero__title-line + .hero__title-line{margin-top:.08em}
.fl-home .hero--signature .hero__title .blue{
  background:linear-gradient(105deg,#FFFFFF 0%,#DCE8F3 38%,#8EA8C4 72%,#DCE8F3 100%);
  -webkit-background-clip:text;background-clip:text;color:transparent;
}
.fl-home .hero--signature .hero__title .yel{
  background:linear-gradient(105deg,#E5DDC7 0%,#C8B98E 46%,#F2E9CF 100%);
  -webkit-background-clip:text;background-clip:text;color:transparent;
}
.fl-home .hero--signature .hero__desc{
  max-width:64ch;margin:0 0 19px;
  font-size:clamp(15px,1.15vw,17px);line-height:1.74;
  color:rgba(237,245,252,.78);
}
.fl-home .hero--signature .hero__desc strong{color:#fff;font-weight:750}
.fl-home .hero__proofs{
  display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:0 0 24px!important;padding:0!important
}
.fl-home .hero__proof{
  display:inline-flex;align-items:center;gap:7px;min-height:34px;padding:7px 10px;
  border-radius:9px;border:1px solid rgba(175,195,218,.15);
  background:rgba(255,255,255,.035);
  color:rgba(237,245,252,.78);
  font-size:11.5px;font-weight:650;letter-spacing:.01em
}
.fl-home .hero__proof svg{width:13px;height:13px;fill:#8DD4B0;flex:0 0 auto}
.fl-home .hero--signature .hero__actions{gap:10px;margin:0 0 28px}
.fl-home .hero--signature .hero__primary{
  min-height:54px;padding:15px 26px;border-radius:12px;
  background:linear-gradient(135deg,#2A5585 0%,#1D426A 58%,#163453 100%);
  box-shadow:0 18px 36px -17px rgba(43,96,150,.85),inset 0 1px 0 rgba(255,255,255,.24)
}
.fl-home .hero--signature .hero__secondary{
  min-height:54px;padding:14px 22px;border-radius:12px;
  background:rgba(255,255,255,.035);border-color:rgba(237,245,252,.23);backdrop-filter:blur(8px)
}
.fl-home .hero--signature .hero__stats{
  display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;
  max-width:660px;padding:0;border:0
}
.fl-home .hero--signature .hero__stat{
  min-width:0;margin:0;padding:13px 14px;border:1px solid rgba(175,195,218,.14);border-radius:12px;
  background:rgba(7,19,31,.35);backdrop-filter:blur(8px)
}
.fl-home .hero--signature .hero__stat-num{font-size:clamp(20px,2vw,27px)}
.fl-home .hero--signature .hero__stat-lbl{font-size:9.5px;letter-spacing:.12em;color:rgba(237,245,252,.56)}
.fl-home .hero--signature .hero__stat-num small{color:rgba(237,245,252,.68);font-size:.48em;letter-spacing:.02em}

/* Sağdaki ürün konsolu */
.fl-home .hero--signature .hero__card{
  display:block;
  position:relative;padding:17px;border-radius:22px;
  background:linear-gradient(155deg,rgba(12,35,57,.80),rgba(5,18,30,.66));
  border:1px solid rgba(175,195,218,.20);
  box-shadow:0 34px 70px -30px rgba(0,0,0,.78),inset 0 1px 0 rgba(255,255,255,.08);
  backdrop-filter:blur(18px) saturate(120%);-webkit-backdrop-filter:blur(18px) saturate(120%);
  overflow:hidden
}
.fl-home .hero--signature .hero__card::before{
  content:'';position:absolute;inset:0;padding:1px;border-radius:22px;pointer-events:none;
  background:linear-gradient(145deg,rgba(238,245,252,.34),rgba(175,195,218,.03) 42%,rgba(200,185,142,.25) 100%);
  -webkit-mask:linear-gradient(#000 0 0) content-box,linear-gradient(#000 0 0);
  -webkit-mask-composite:xor;mask-composite:exclude
}
.fl-home .hero__card-kicker{
  display:flex;justify-content:space-between;gap:12px;padding:0 2px 12px;margin-bottom:3px;
  border-bottom:1px solid rgba(175,195,218,.12);
  font-family:var(--ff-mono);font-size:9px;letter-spacing:.16em;color:rgba(237,245,252,.35)
}
.fl-home .hero__card-kicker b{font-weight:700;color:rgba(200,185,142,.68)}
.fl-home .hero--signature .hero__card-head{align-items:flex-start;margin:12px 2px 10px}
.fl-home .hero--signature .hero__card-tag{font-size:11px;margin:0;color:#fff;letter-spacing:.14em}
.fl-home .hero__card-sub{font-size:11.5px!important;line-height:1.45!important;color:rgba(237,245,252,.52)!important;margin-top:4px!important}
.fl-home .hero--signature .hero__card-live{
  flex:0 0 auto;padding:5px 8px;border-radius:999px;background:rgba(31,122,82,.10);
  border:1px solid rgba(87,196,139,.18);font-family:var(--ff-mono);font-size:9.5px;text-transform:uppercase;letter-spacing:.08em
}
.fl-home .hero--signature .hero__card-list{gap:5px}
.fl-home .hero--signature .hero__card-list li{min-width:0}
.fl-home .hero--signature .hero__card-list a{
  display:grid!important;grid-template-columns:27px minmax(0,1fr) auto 20px;align-items:center;gap:9px;
  min-height:57px;padding:8px 10px;border-radius:11px;
  background:rgba(255,255,255,.025);border-color:rgba(175,195,218,.085)
}
.fl-home .hero--signature .hero__card-list a:hover,
.fl-home .hero--signature .hero__card-list a:focus-visible{
  transform:translateX(3px);background:rgba(91,127,166,.12);border-color:rgba(175,195,218,.25)
}
.fl-home .hero__card-no{
  font-family:var(--ff-mono);font-size:9.5px;color:rgba(237,245,252,.30);letter-spacing:.08em
}
.fl-home .hero__card-name{display:block;min-width:0}
.fl-home .hero--signature .hero__card-list .n{
  display:block;font-size:12.8px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#F6FAFF
}
.fl-home .hero__card-list .s{
  display:block;margin-top:2px;font-size:9.8px;font-weight:500;color:rgba(237,245,252,.43);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis
}
.fl-home .hero--signature .hero__card-list .t{
  font-size:9.5px;padding:3px 6px;border-radius:6px;background:rgba(142,168,196,.08);color:#C9D6E5
}
.fl-home .hero__card-arrow{display:flex;align-items:center;justify-content:center;opacity:.42}
.fl-home .hero__card-arrow svg{width:11px;height:11px;fill:#fff}
.fl-home .hero__card-foot{display:grid;grid-template-columns:1fr 1fr;gap:7px;margin-top:10px}
.fl-home .hero__card-metric{padding:9px 10px;border-radius:9px;background:rgba(255,255,255,.025);border:1px solid rgba(175,195,218,.08)}
.fl-home .hero__card-metric span,.fl-home .hero__card-metric strong{display:block}
.fl-home .hero__card-metric span{font-family:var(--ff-mono);font-size:8.5px;letter-spacing:.12em;text-transform:uppercase;color:rgba(237,245,252,.37)}
.fl-home .hero__card-metric strong{margin-top:3px;font-size:11.5px;color:rgba(237,245,252,.83)}
.fl-home .hero--signature .hero__card-cta{
  margin-top:8px;min-height:47px;border-radius:10px;
  background:linear-gradient(105deg,#F2F5F8,#C9D6E5);color:#07131F;font-size:12.5px
}
.fl-home .hero__card-note{margin:8px 2px 0!important;font-size:9.5px!important;line-height:1.45!important;color:rgba(237,245,252,.34)!important}

/* Hero ticker: marka şeridi */
.fl-home .hero--signature .hero-ticker{
  z-index:4;margin:0 clamp(12px,2.8vw,42px) clamp(14px,2.3vw,28px);
  height:66px;border-radius:14px;border:1px solid rgba(255,255,255,.76);
  background:rgba(255,255,255,.965);box-shadow:0 24px 54px -28px rgba(0,0,0,.8)
}
.fl-home .hero-ticker__label{
  align-self:stretch;display:flex;flex-direction:column;justify-content:center;gap:1px;
  flex:0 0 auto;padding:0 18px;border-right:1px solid var(--line-2);background:#F7F7FF
}
.fl-home .hero-ticker__label span{font-family:var(--ff-mono);font-size:8px;letter-spacing:.17em;color:#75869A}
.fl-home .hero-ticker__label strong{font-family:var(--ff-head);font-size:11px;letter-spacing:.08em;color:#11273D}
.fl-home .hero--signature .t-partner,.fl-home .hero--signature .t-kur{height:66px}
.fl-home .hero--signature .t-partner{padding:0 19px}
.fl-home .hero--signature .t-partner__ico{width:46px;height:34px}
.fl-home .hero--signature .t-partner__desc{font-size:9.7px}
.fl-home .hero--signature .hero__media-ctrl{bottom:104px}

/* Ürün kartları — daha rafine yüzey */
.fl-home .prod-card{border-radius:20px;box-shadow:0 12px 36px -30px rgba(10,24,40,.65)}
.fl-home .prod-card__img{overflow:hidden}
.fl-home .prod-card__img::before{
  content:'';position:absolute;inset:0;z-index:2;pointer-events:none;
  box-shadow:inset 0 0 0 1px rgba(255,255,255,.20)
}
.fl-home .prod-card__body{padding:21px}
.fl-home .prod-card__title{font-size:17.5px;letter-spacing:-.018em}
.fl-home .prod-card:hover,.fl-home .prod-card:focus-within{transform:translateY(-5px);box-shadow:0 28px 54px -30px rgba(10,24,40,.42)}

/* Tablet */
@media (max-width:1199.98px){
  .fl-home .hero--signature .hero__layout{grid-template-columns:minmax(0,1.05fr) minmax(330px,.75fr);gap:34px}
  .fl-home .hero--signature .hero__title{font-size:clamp(42px,5.6vw,62px)}
  .fl-home .hero__card-list .s{display:none}
  .fl-home .hero--signature .hero__card-list a{min-height:52px}
}
@media (max-width:991.98px){
  .fl-home .hero.hero--signature{min-height:auto}
  .fl-home .hero--signature .hero__body{padding-top:clamp(80px,10vw,112px);padding-bottom:28px}
  .fl-home .hero--signature .hero__layout{grid-template-columns:1fr;gap:26px}
  .fl-home .hero--signature .hero__main{max-width:780px}
  .fl-home .hero--signature .hero__title{max-width:13.8ch;font-size:clamp(45px,8.2vw,68px)}
  .fl-home .hero--signature .hero__desc{max-width:68ch}
  .fl-home .hero--signature .hero__card{
    display:block!important;max-width:none;padding:14px;border-radius:18px
  }
  .fl-home .hero--signature .hero__card-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:6px}
  .fl-home .hero--signature .hero__card-list a{grid-template-columns:24px minmax(0,1fr) auto 16px}
  .fl-home .hero__card-foot{display:none}
  .fl-home .hero__card-note{display:none}
  .fl-home .hero--signature .hero__card-cta{max-width:360px;margin-left:auto}
  .fl-home .hero--signature .hero__media-ctrl{bottom:92px}
  .fl-home .hero-ticker__label{display:none}
}

/* Telefon */
@media (max-width:767.98px){
  .fl-home .hero.hero--signature{border-radius:0 0 22px 22px}
  .fl-home .hero--signature .hero__media::after{
    background:
      linear-gradient(180deg,rgba(4,14,24,.55) 0%,rgba(4,14,24,.68) 46%,rgba(4,14,24,.88) 100%)
  }
  .fl-home .hero--signature .hero__grid{background-size:46px 46px;opacity:.07}
  .fl-home .hero__edge{display:none}
  .fl-home .hero--signature .hero__body{padding-top:74px;padding-bottom:22px}
  .fl-home .hero__eyebrow-row{margin-bottom:15px}
  .fl-home .hero__since{display:none}
  .fl-home .hero--signature .hero__eyebrow{font-size:9.5px;letter-spacing:.10em;padding:7px 10px}
  .fl-home .hero--signature .hero__title{font-size:clamp(39px,12.4vw,58px);line-height:.96;max-width:none;margin-bottom:18px}
  .fl-home .hero--signature .hero__desc{font-size:14.5px;line-height:1.68;margin-bottom:15px}
  .fl-home .hero__proofs{gap:6px;margin-bottom:19px!important}
  .fl-home .hero__proof{font-size:10.5px;min-height:31px;padding:6px 8px}
  .fl-home .hero--signature .hero__actions{display:grid;grid-template-columns:1fr;gap:8px;margin-bottom:18px}
  .fl-home .hero--signature .hero__actions a{width:100%;min-height:50px;border-radius:11px}
  .fl-home .hero--signature .hero__stats{gap:6px}
  .fl-home .hero--signature .hero__stat{padding:10px 9px;border-radius:10px}
  .fl-home .hero--signature .hero__stat-lbl{font-size:8.3px;letter-spacing:.08em}
  .fl-home .hero--signature .hero__stat-num{font-size:18px}
  .fl-home .hero--signature .hero__card{display:none!important}
  .fl-home .hero--signature .hero__media-ctrl{right:10px;bottom:73px}
  .fl-home .hero--signature .hero-ticker{
    height:58px;margin:0 0 10px;border-radius:0;background:#fff;border-left:0;border-right:0
  }
  .fl-home .hero--signature .hero-ticker__viewport{overflow-x:auto;overscroll-behavior-inline:contain;scrollbar-width:none}
  .fl-home .hero--signature .hero-ticker__viewport::-webkit-scrollbar{display:none}
  .fl-home .hero--signature .hero-ticker__track{animation:none!important;transform:none!important;width:max-content}
  .fl-home .hero--signature .hero-ticker [data-dup]{display:none!important}
  .fl-home .hero--signature .hero-ticker__ctrl{display:none}
  .fl-home .hero--signature .t-partner,.fl-home .hero--signature .t-kur{height:58px}
  .fl-home .hero--signature .t-partner{padding:0 14px}
  .fl-home .hero--signature .t-partner__ico{width:40px;height:30px}
  .fl-home .hero--signature .t-partner__desc{display:none}
  .fl-home .hero--signature .t-kur{padding:0 13px}
  .fl-home .section-title{font-size:clamp(30px,9vw,42px)}
}
@media (max-width:430px){
  .fl-home .hero--signature .hero__title{font-size:clamp(37px,11.7vw,50px)}
  .fl-home .hero__proofs{display:grid;grid-template-columns:1fr 1fr}
  .fl-home .hero__proof:last-child{grid-column:1/-1}
  .fl-home .hero--signature .hero__stats{grid-template-columns:repeat(3,minmax(0,1fr))}
  .fl-home .hero--signature .hero__stat{padding:9px 7px}
  .fl-home .hero--signature .hero__stat-num{font-size:16.5px}
}
@media (max-width:360px){
  .fl-home .hero--signature .hero__stats{grid-template-columns:1fr}
  .fl-home .hero__proofs{grid-template-columns:1fr}
  .fl-home .hero__proof:last-child{grid-column:auto}
}

/* Hareket azaltma tercihine tam saygı */
@media (prefers-reduced-motion:reduce){
  .fl-home .hero--signature .hero__poster,.fl-home .hero--signature .hero__media video{transform:none}
}

/* Büyük ekranlarda içerik kontrollü genişlikte kalsın */
@media (min-width:1440px){
  .fl-home .hero--signature .hero__layout{grid-template-columns:minmax(0,1.12fr) minmax(390px,.68fr)}
  .fl-home .hero--signature .hero__title{font-size:78px}
}

/* ═════════════════════════════════════════════════════════════
   v17 — KONTROLLÜ GELİŞTİRME (kütüphanesiz; yalnızca CSS + mevcut IntersectionObserver)
   ═════════════════════════════════════════════════════════════ */

/* 1) Sade kademeli giriş: yalnız opaklık + 14px, tek sefer */
.js .reveal{transform:translateY(14px);transition:opacity .6s var(--ease-out),transform .6s var(--ease-out)}
.js .eng__tools-grid.reveal,.js .pdf__grid.reveal{opacity:1;transform:none;transition:none}
.js .eng__tools-grid > li,.js .pdf__grid > li{opacity:0;transform:translateY(12px);transition:opacity .5s var(--ease-out),transform .5s var(--ease-out)}
.js .eng__tools-grid.is-in > li,.js .pdf__grid.is-in > li{opacity:1;transform:none}
.js .eng__tools-grid.is-in > li:nth-child(5n+2),.js .pdf__grid.is-in > li:nth-child(4n+2){transition-delay:.05s}
.js .eng__tools-grid.is-in > li:nth-child(5n+3),.js .pdf__grid.is-in > li:nth-child(4n+3){transition-delay:.1s}
.js .eng__tools-grid.is-in > li:nth-child(5n+4),.js .pdf__grid.is-in > li:nth-child(4n+4){transition-delay:.15s}
.js .eng__tools-grid.is-in > li:nth-child(5n+5){transition-delay:.2s}

/* 2) Bölüm etiketi çizgisi görünür olunca soldan çizilir */
.js .reveal .section-tag::before{transform:scaleX(0);transform-origin:left center;transition:transform .7s var(--ease-out) .15s}
.js .reveal.is-in .section-tag::before{transform:scaleX(1)}

/* 3) Hero girişi: yalnız CSS, hareket azaltmada kapalı */
@media (prefers-reduced-motion:no-preference){
  .fl-home .hero--signature .hero__main > *{animation:flIn .6s var(--ease-out) both}
  .fl-home .hero--signature .hero__main > :nth-child(2){animation-delay:.05s}
  .fl-home .hero--signature .hero__main > :nth-child(3){animation-delay:.1s}
  .fl-home .hero--signature .hero__main > :nth-child(4){animation-delay:.15s}
  .fl-home .hero--signature .hero__main > :nth-child(5){animation-delay:.2s}
  .fl-home .hero--signature .hero__main > :nth-child(6){animation-delay:.25s}
  .fl-home .hero--signature .hero__card{animation:flIn .7s var(--ease-out) .15s both}
}
@keyframes flIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}

/* 4) Düğmeler: basma geri bildirimi */
.fl-home :is(.fl-btn-primary,.fl-btn-outline,.cta-vid__btn-main,.hero__card-cta,.tolc__btn):active{transform:translateY(0) scale(.985)}

/* 5) Ürün kartı: her zaman görünen kritik değerler (dokunmatikte de) */
.fl-home .prod-keys{display:grid;grid-template-columns:1fr 1fr;margin:0 0 14px;border-top:1px solid var(--line-2)}
.fl-home .prod-keys div{display:flex;flex-direction:column;gap:3px;padding-top:10px;min-width:0}
.fl-home .prod-keys div + div{padding-left:14px;border-left:1px solid var(--line-2)}
.fl-home .prod-keys dt{font:700 10.5px/1.2 var(--ff-mono);letter-spacing:.08em;text-transform:uppercase;color:var(--text-muted)}
.fl-home .prod-keys dd{margin:0;font:700 13.5px/1.35 var(--ff-mono);color:var(--ink);overflow-wrap:anywhere}

/* 6) Teklif talebi — kompakt, eşit aralıklı ve hizalı (tüm kırılımlarda 16 px ritim) */
.fl-home .rq{background:linear-gradient(180deg,#F4F7FB 0%,#fff 38%);border-bottom:1px solid var(--line-2);padding:clamp(26px,3.2vw,44px) 0;scroll-margin-top:84px}
.fl-home .rq__head{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);column-gap:clamp(18px,3vw,40px);align-items:center;max-width:none;margin:0 0 16px}
.fl-home .rq__head .rq__kicker,.fl-home .rq__head .rq__title{grid-column:1}
.fl-home .rq__kicker{margin:0 0 10px;font:700 10.5px/1 var(--ff-mono);letter-spacing:.16em;text-transform:uppercase;color:var(--sky-lt)}
.fl-home .rq__head .rq__kicker{display:inline-flex;align-items:center;gap:10px;margin:0 0 8px;color:var(--blue)}
.fl-home .rq__head .rq__kicker::before{content:"";width:28px;height:2px;background:currentColor;opacity:.7}
.fl-home .rq__title{margin:0;font:700 clamp(22px,2.4vw,30px)/1.15 var(--ff-head);letter-spacing:-.03em;color:var(--ink)}
.fl-home .rq__title span{color:var(--blue)}
.fl-home .rq__answer{grid-column:2;grid-row:1/span 2;align-self:end;margin:0;max-width:none;padding-left:clamp(14px,1.8vw,22px);border-left:2px solid var(--line);font-size:clamp(13px,1.05vw,14px);line-height:1.6;color:var(--ink-2);text-wrap:pretty}
.fl-home .rq__steps{list-style:none;margin:0 0 16px;padding:0;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0;border:1px solid var(--line);border-radius:14px;background:#fff;overflow:hidden}
.fl-home .rq__step{position:relative;display:grid;grid-template-columns:auto minmax(0,1fr);align-content:start;column-gap:9px;row-gap:5px;padding:clamp(12px,1.5vw,16px) clamp(14px,1.6vw,18px);border-left:1px solid var(--line-2)}
.fl-home .rq__step:first-child{border-left:0}
.fl-home .rq__step::after{content:"";position:absolute;left:0;top:0;height:3px;width:0;background:var(--blue);transition:width .5s ease}
.fl-home .rq__step:hover::after,.fl-home .rq__step:focus-within::after{width:100%}
.fl-home .rq__step-no{display:block;margin:0;align-self:baseline;font:700 11px/1 var(--ff-mono);letter-spacing:.16em;color:var(--blue)}
.fl-home .rq__step-t{display:block;margin:0;align-self:baseline;font:700 14.5px/1.3 var(--ff-head);letter-spacing:-.01em;color:var(--ink)}
.fl-home .rq__step-d{grid-column:1/-1;margin:0;font-size:12.5px;line-height:1.5;color:var(--text-muted);text-wrap:pretty}

.fl-home .rq__wrap{display:grid;grid-template-columns:minmax(0,.5fr) minmax(0,1.9fr);gap:16px;align-items:stretch}
.fl-home .rq__info{position:static;display:flex;flex-direction:column;padding:16px;border-radius:14px;color:#fff;background:radial-gradient(90% 70% at 100% 0%,rgba(91,127,166,.3) 0%,rgba(91,127,166,0) 60%),linear-gradient(150deg,#183452 0%,#11273D 55%,#0A1828 100%)}
.fl-home .rq__art{display:block;width:100%;max-width:460px;height:auto;margin:0 0 8px;padding:6px 10px;border:1px solid rgba(175,195,218,.2);border-radius:12px;background:rgba(10,24,40,.38)}
.fl-home .rq__art path,.fl-home .rq__art circle{fill:none;stroke-linecap:round;stroke-linejoin:round}
.fl-home .rq__art-l{stroke:rgba(210,224,240,.8);stroke-width:1.5}
.fl-home .rq__art-h{stroke:rgba(175,195,218,.4);stroke-width:1}
.fl-home .rq__art-c{stroke:rgba(143,177,214,.55);stroke-width:1;stroke-dasharray:14 4 3 4}
.fl-home .rq__art-d{stroke:rgba(143,177,214,.75);stroke-width:1}
.fl-home .rq__facts{flex:1;display:flex;flex-direction:column;margin:0;border-top:1px solid rgba(175,195,218,.24)}
.fl-home .rq__facts div{flex:1;display:flex;justify-content:space-between;align-items:center;gap:12px;padding:7px 0;border-bottom:1px solid rgba(175,195,218,.14)}
.fl-home .rq__facts dt{font-size:12.5px;color:var(--sky-lt)}
.fl-home .rq__facts dd{margin:0;text-align:right;font:700 12.5px/1.35 var(--ff-mono);color:#fff}
.fl-home .rq__facts dd small{display:block;font:400 11px/1.3 var(--ff);color:var(--sky-lt)}
.fl-home .rq__trust-line{display:flex;align-items:flex-start;gap:8px;margin:10px 0 12px;font-size:12.5px;line-height:1.45;color:rgba(237,241,247,.88)}
.fl-home .rq__trust-line svg{flex:0 0 15px;width:15px;height:15px;margin-top:1px;fill:#8FD1B0}
.fl-home .rq__tel{list-style:none;margin:auto 0 0;padding:10px 0 0;border-top:1px solid rgba(175,195,218,.22);display:grid;gap:4px}
.fl-home .rq__tel li{display:flex;justify-content:space-between;align-items:baseline;gap:12px;font-size:12.5px;color:var(--sky-lt)}
.fl-home .rq__tel a{font:700 14px var(--ff-mono);color:#fff;white-space:nowrap}
.fl-home .rq__tel a:hover{text-decoration:underline}

.fl-home .rq__card{background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:var(--e2)}
.fl-home .rq__sec{margin:0;padding:14px 16px;border:0;border-bottom:1px solid var(--line-2);min-width:0}
.fl-home .rq__sec legend{float:left;width:100%;margin:0 0 10px;padding:0;font:700 11px/1 var(--ff-mono);letter-spacing:.12em;text-transform:uppercase;color:var(--ink)}
.fl-home .rq__sec legend + *{clear:both}
.fl-home .rq__sec legend span{margin-right:8px;color:var(--text-light)}
.fl-home .rq-grid{display:grid;gap:10px}
.fl-home .rq-grid--r{grid-template-columns:repeat(12,minmax(0,1fr))}
.fl-home .rq-grid--r .rq-f{grid-column:span 2}
.fl-home .rq-grid--r .rq-f--urun{grid-column:span 6}
.fl-home .rq-row[data-tip=boru] .rq-f--urun{grid-column:span 4}
.fl-home .rq-row[data-tip=ozel] :is(.rq-f--boy,.rq-f--adet){grid-column:span 3}
.fl-home .rq-grid--r .rq-f--not{grid-column:1/-1}
.fl-home .rq-grid--c{grid-template-columns:repeat(12,minmax(0,1fr))}
.fl-home .rq-grid--c .rq-f{grid-column:span 3}
.fl-home .rq-grid--c .rq-f--wide{grid-column:1/-1}
.fl-home .rq-f{min-width:0}
.fl-home .rq-f label{display:block;margin:0 0 4px;font-size:12px;font-weight:600;color:var(--text-muted)}
.fl-home .rq-f :is(input[type=text],input[type=number],input[type=email],input[type=tel],select,textarea){width:100%;min-height:40px;padding:8px 11px;border:1.5px solid var(--bg-border);border-radius:var(--r-sm);background:#fff;font:500 16px var(--ff);color:var(--ink);transition:border-color .2s,box-shadow .2s}
.fl-home .rq-f input[type=number]{font-family:var(--ff-mono);font-variant-numeric:tabular-nums}
.fl-home .rq-f textarea{min-height:48px;resize:vertical;line-height:1.5}
.fl-home .rq-f :is(input,select,textarea):focus{outline:none;border-color:var(--blue-metal);box-shadow:0 0 0 3px rgba(42,85,133,.14)}
.fl-home .rq-f.is-err :is(input,select,textarea){border-color:var(--red)}
.fl-home .rq-e{display:block;margin-top:4px;font-size:12px;font-weight:600;color:var(--red)}
.fl-home .rq-row{margin-bottom:8px;padding:10px 12px 12px;background:#fff;border:1px solid var(--line);border-radius:12px;transition:border-color .3s,box-shadow .3s}
.fl-home .rq-row.is-new{border-color:var(--blue-line);box-shadow:0 0 0 3px rgba(42,85,133,.12)}
.fl-home .rq-row__head{display:flex;align-items:center;gap:12px;margin-bottom:8px;min-height:24px}
.fl-home .rq-row__no{flex:0 0 auto;font:700 11px/1 var(--ff-mono);letter-spacing:.1em;text-transform:uppercase;color:var(--text-muted)}
.fl-home .rq-row__hint{flex:1 1 auto;min-width:0;margin:0;font-size:12px;line-height:1.4;color:var(--text-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.fl-home .rq-row__hint.is-ok{color:var(--green)}
.fl-home .rq-row__hint.is-warn{color:#7A5E0F}
.fl-home .rq-row__btn{flex:0 0 auto;min-height:28px;padding:2px 8px;border:0;border-radius:6px;background:transparent;font:600 12.5px var(--ff);color:var(--blue);cursor:pointer}
.fl-home .rq-row__btn:hover{background:var(--blue-soft)}
.fl-home .rq-row__del{color:var(--red)}
.fl-home .rq-row__del:hover{background:rgba(178,58,46,.08)}
.fl-home .rq__add{display:inline-flex;align-items:center;gap:8px;min-height:40px;padding:0 14px;border:1.5px dashed var(--blue-line);border-radius:10px;background:#fff;font:700 13px var(--ff);color:var(--blue);cursor:pointer;transition:border-color .2s,background .2s}
.fl-home .rq__add span{font-size:18px;line-height:1}
.fl-home .rq__add small{font:500 11px var(--ff-mono);color:var(--text-muted)}
.fl-home .rq__add:hover{border-color:var(--blue);background:var(--blue-soft)}
.fl-home .rq__add:disabled{opacity:.5;cursor:not-allowed}
.fl-home .rq__foot{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:10px 18px;padding:12px 16px}
.fl-home .rq__check{display:flex;align-items:flex-start;gap:10px;font-size:12.5px;line-height:1.5;color:var(--ink-2);cursor:pointer}
.fl-home .rq__check input{flex:0 0 18px;width:18px;height:18px;margin-top:1px;accent-color:var(--blue)}
.fl-home .rq__check.is-err{color:var(--red)}
.fl-home .rq__status{grid-column:1/-1;margin:0;padding:10px 12px;border:1px solid rgba(178,58,46,.3);border-radius:10px;background:rgba(178,58,46,.06);font-size:13.5px;font-weight:600;line-height:1.5;color:var(--red)}
.fl-home .rq__submit{min-height:46px;padding:0 24px;font-size:14px;white-space:nowrap}
.fl-home .rq__submit:disabled{opacity:.65;cursor:progress}

/* 6b) Spam koruması: işlem sorusu (sunucu doğrulamalı) */
.fl-home .rq__cap{grid-column:1/-1;display:flex;align-items:center;flex-wrap:wrap;gap:6px 12px;padding:10px 12px;border:1px dashed var(--blue-line);border-radius:10px;background:var(--blue-soft)}
.fl-home .rq__cap.is-err{border-color:var(--red);background:rgba(178,58,46,.05)}
.fl-home .rq__cap label{margin:0;font-size:13px;font-weight:700;color:var(--ink);white-space:nowrap}
.fl-home .rq__cap label b{padding:0 4px;font:800 16px var(--ff-mono);letter-spacing:.04em;color:var(--blue-dk)}
.fl-home .rq__cap.rq-f input[type=text]{width:84px;min-height:40px;padding:6px 8px;text-align:center;font:700 16px var(--ff-mono)}
.fl-home .rq__cap-help{flex:1 1 200px;min-width:0;font-size:12px;line-height:1.45;color:var(--text-muted)}
.fl-home .rq__cap .rq-e{flex:1 0 100%;margin-top:0}

.fl-home .rq__done{padding:clamp(24px,3.5vw,40px);text-align:left;outline:0}
.fl-home .rq__done-ico{display:flex;align-items:center;justify-content:center;width:44px;height:44px;margin-bottom:14px;border-radius:50%;background:rgba(31,122,82,.12)}
.fl-home .rq__done-ico svg{width:22px;height:22px;fill:var(--green)}
.fl-home .rq__done h3{margin:0 0 8px;font:700 24px/1.15 var(--ff-head);letter-spacing:-.02em}
.fl-home .rq__done p{margin:0 0 10px;font-size:14.5px;line-height:1.6;color:var(--ink-2)}
.fl-home .rq__ref{display:inline-block;padding:7px 12px;border:1px solid var(--blue-line);border-radius:10px;background:var(--blue-soft);font:700 17px var(--ff-mono) !important;letter-spacing:.04em;color:var(--blue-dk) !important}
.fl-home .rq__sum{margin:14px 0 18px;border-top:1px solid var(--line)}
.fl-home .rq__sum li{padding:10px 0;border-bottom:1px solid var(--line-2);font-size:14px;color:var(--ink-2)}
.fl-home .rq__sum b{color:var(--ink)}
.fl-home .rq__sum span{font-family:var(--ff-mono);font-size:13px;color:var(--ink)}
.fl-home .rq__done-note a{font-weight:700;color:var(--blue)}
.fl-home .rq__noscript{margin:0;padding:18px;font-size:14.5px}
.fl-home .rq-hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}

@media (max-width:1100px){
  .fl-home .rq__steps{grid-template-columns:repeat(2,minmax(0,1fr))}
  .fl-home .rq__step:nth-child(3){border-left:0}
  .fl-home .rq__step:nth-child(n+3){border-top:1px solid var(--line-2)}
}
@media (max-width:991.98px){
  .fl-home .rq__head{grid-template-columns:1fr}
  .fl-home .rq__answer{grid-column:1;grid-row:auto;margin-top:12px;padding-left:0;border-left:0}
  .fl-home .rq__wrap{grid-template-columns:1fr}
  .fl-home .rq__tel{margin-top:0}
  .fl-home .rq__art{max-width:none}
  .fl-home .rq__facts div{flex:none}
}
@media (min-width:576px) and (max-width:991.98px){
  .fl-home .rq__info{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);column-gap:20px;align-items:start}
  .fl-home .rq__art{grid-column:1;grid-row:1;align-self:center;margin:0}
  .fl-home .rq__trust-line{grid-column:1;grid-row:2;margin:12px 0 0}
  .fl-home .rq__facts{grid-column:2;grid-row:1}
  .fl-home .rq__tel{grid-column:2;grid-row:2;margin-top:12px}
}
@media (max-width:575.98px){
  .fl-home .rq__steps{grid-template-columns:1fr}
  .fl-home .rq__step{border-left:0;border-top:1px solid var(--line-2);padding:12px 14px}
  .fl-home .rq__step:first-child{border-top:0}
}
@media (prefers-reduced-motion:reduce){.fl-home .rq__step::after{transition:none}}

/* 7) ISO tolerans aracı: "Sonuçlara git" bağlantısı (otomatik kaydırma yok) */
.fl-home .tolc__jump{display:inline-flex;align-items:center;gap:6px;min-height:44px;font-size:13.5px;font-weight:800;color:var(--blue)}
.fl-home .tolc__jump:hover{text-decoration:underline}

/* 8) Mobil/tablet performansı: bulanıklık (backdrop-filter) kapalı */
@media (max-width:991.98px){
  .fl-home *,.fl-home *::before,.fl-home *::after{-webkit-backdrop-filter:none !important;backdrop-filter:none !important}
}
@media (max-width:767.98px){
  .fl-home .rq-grid--r .rq-f,.fl-home .rq-row[data-tip=boru] .rq-f--urun,.fl-home .rq-row[data-tip=ozel] :is(.rq-f--boy,.rq-f--adet){grid-column:span 6}
  .fl-home .rq-grid--r .rq-f--urun,.fl-home .rq-row[data-tip=boru] .rq-f--urun{grid-column:1/-1}
  .fl-home .rq-grid--r .rq-f--not{grid-column:1/-1}
  .fl-home .rq-grid--c .rq-f{grid-column:span 6}
  .fl-home .rq-grid--c .rq-f--wide{grid-column:1/-1}
  .fl-home .rq__foot{grid-template-columns:1fr}
  .fl-home .rq__submit{width:100%}
}
@media (max-width:575.98px){
  .fl-home .rq__add{width:100%;justify-content:center}
  .fl-home .rq-grid--c .rq-f{grid-column:1/-1}
  .fl-home .rq-row__head{flex-wrap:wrap;row-gap:4px}
  .fl-home .rq-row__no{margin-right:auto}
  .fl-home .rq-row__hint{order:5;flex:1 0 100%;white-space:normal}
  .fl-home .rq-row[data-tip=mil] .rq-grid--r .rq-f:not(.rq-f--urun):not(.rq-f--not){grid-column:span 4}
  .fl-home .prod-keys{grid-template-columns:1fr}
  .fl-home .prod-keys div{flex-direction:row;justify-content:space-between;align-items:baseline;gap:10px}
  .fl-home .prod-keys div + div{padding-left:0;border-left:0;margin-top:8px;border-top:1px solid var(--line-2)}
  .fl-home .prod-keys dd{font-size:13px}
  .fl-home .rq__cap{flex-direction:column;align-items:flex-start}
  .fl-home .rq__cap.rq-f input[type=text]{width:100%;max-width:140px}
}
@media (prefers-reduced-motion:reduce){
  .js .eng__tools-grid > li,.js .pdf__grid > li{opacity:1;transform:none}
  .js .reveal .section-tag::before{transform:none}
}

/* ═════════════════════════════════════════════════════════════
   v18 — Geniş ekran yerleşimi + koyu zeminde odak görünürlüğü
   ═════════════════════════════════════════════════════════════ */
/* Büyük monitörler: içerik alanı ekranla birlikte genişler; okuma satırları ch ile sınırlı kalır */
@media (min-width:1600px){
  .fl-home .container{max-width:1480px}
  .fl-home .prod-grid{gap:24px}
  .fl-home .eng__tools-grid{gap:16px}
}
@media (min-width:1920px){.fl-home .container{max-width:1600px}}
.fl-home .fx-table__note a{display:inline !important;min-height:0 !important;color:var(--blue);font-weight:700;text-decoration:underline;text-underline-offset:2px}
/* Koyu yüzeylerde çelik mavisi odak halkası görünmüyordu → açık renk */
.fl-home :is(.hero,.cta-vid,.faq__help,.rq__info,.pdf__item--featured) :focus-visible{outline-color:#F7F7FF}
</style>

@php
    /* Tekrar kullanılan ikonlar (tek stil: Material dolgu 24px) */
    $icArrow   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
    $icChevron = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6z"/></svg>';
    $icPause   = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>';
    $icCheck   = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>';
    $icStar    = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 16.8l-6.2 4.5 2.4-7.4L2 9.4h7.6z"/></svg>';
    $icPhone   = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/></svg>';
@endphp

<div id="main-content" class="fl-home">

{{-- ══ HERO — SIGNATURE INDUSTRIAL EXPERIENCE ══ --}}
<section class="hero hero--signature" aria-labelledby="hero-title">
    <div class="hero__media" aria-hidden="true">
        <img class="hero__poster" src="{{ asset('assets/video.jpg') }}" alt="" width="1920" height="1080" fetchpriority="high" decoding="async">
        <video id="hero-video" playsinline loop muted preload="none" tabindex="-1">
            <source data-src="{{ asset('assets/nimet3.mp4') }}" type="video/mp4">
        </video>
    </div>
    <div class="hero__grid" aria-hidden="true"></div>
    <div class="hero__glow" aria-hidden="true"></div>
    <div class="hero__edge hero__edge--a" aria-hidden="true"></div>
    <div class="hero__edge hero__edge--b" aria-hidden="true"></div>

    <div class="container hero__body">
        <div class="hero__layout">
            <div class="hero__main">
                <div class="hero__eyebrow-row">
                    <p class="hero__eyebrow"><span class="dot" aria-hidden="true"></span>NIMET Yetkili Türkiye Distribütörü</p>
                    <span class="hero__since">BURSA · İSTANBUL</span>
                </div>

                <h1 class="hero__title" id="hero-title">
                    <span class="hero__title-line">Sert Krom Kaplı Mil<span class="sr-only">,</span></span>
                    <span class="hero__title-line blue">İndüksiyonlu Mil<span class="sr-only"> ve</span></span>
                    <span class="hero__title-line yel">H8 Honlanmış Boru</span>
                </h1>

                <p class="hero__desc"><strong>Flutecnica</strong>, NIMET'in Türkiye yetkili distribütörüdür. CK45 ve CF53 dahil hassas mil ve hidrolik boru çözümlerini <strong>Ø4–200 mm</strong> aralığında stoktan, ölçüye kesim desteğiyle ve Türkiye geneline <strong>24–48 saatte</strong> sevk eder.</p>

                <ul class="hero__proofs" aria-label="Flutecnica güven ve hizmet avantajları">
                    <li class="hero__proof">{!! $icCheck !!}<span>EU sertifikalı NIMET üretimi</span></li>
                    <li class="hero__proof">{!! $icCheck !!}<span>Stokta 1 adet sipariş</span></li>
                    <li class="hero__proof">{!! $icCheck !!}<span>Teknik ürün seçim desteği</span></li>
                </ul>

                <div class="hero__actions">
                    <a href="#urunler" class="fl-btn-primary hero__primary">Ürünleri Keşfet {!! $icArrow !!}</a>
                    <a href="#olcu-sorgula" class="fl-btn-outline fl-btn-outline--light hero__secondary">Ölçünü Gönder · Teklif Al</a>
                </div>

                <dl class="hero__stats">
                    <div class="hero__stat">
                        <dt class="hero__stat-lbl">Stok çap aralığı</dt>
                        <dd class="hero__stat-num">Ø4<span class="yel">–</span>200<small> mm</small></dd>
                    </div>
                    <div class="hero__stat">
                        <dt class="hero__stat-lbl">Türkiye geneli</dt>
                        <dd class="hero__stat-num">24<span class="yel">–</span>48<small> saat</small></dd>
                    </div>
                    <div class="hero__stat">
                        <dt class="hero__stat-lbl">Sevkiyat noktası</dt>
                        <dd class="hero__stat-num">2<small> şube</small></dd>
                    </div>
                </dl>
            </div>

            {{-- Ürün seçim konsolu: masaüstü + tablet --}}
            <aside class="hero__card" aria-label="Stoktaki ana ürün grupları">
                <div class="hero__card-kicker" aria-hidden="true">
                    <span>FLUTECNICA</span><b>PRECISION / 01</b>
                </div>
                <div class="hero__card-head">
                    <div>
                        <p class="hero__card-tag">Hızlı ürün seçimi</p>
                        <p class="hero__card-sub">Uygulamaya göre doğru mil / boruya geçin</p>
                    </div>
                    <span class="hero__card-live">Stok aktif</span>
                </div>

                <ul class="hero__card-list">
                    @foreach($specRows as $ri => $r)
                    <li>
                        <a href="{{ $r['url'] }}">
                            <span class="hero__card-no" aria-hidden="true">{{ str_pad($ri + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="hero__card-name">
                                <span class="n">{{ $r['urun'] }}</span>
                                <span class="s">{{ $r['kul'] }}</span>
                            </span>
                            <span class="t">{{ $r['tol'] }}</span>
                            <span class="hero__card-arrow" aria-hidden="true">{!! $icChevron !!}</span>
                        </a>
                    </li>
                    @endforeach
                </ul>

                <div class="hero__card-foot">
                    <div class="hero__card-metric"><span>Yüzey</span><strong>Ra ≤ 0,20 µm*</strong></div>
                    <div class="hero__card-metric"><span>Kesim</span><strong>Özel boya göre</strong></div>
                </div>
                <a class="hero__card-cta" href="#olcu-sorgula">Çap · boy · adet gönder {!! $icArrow !!}</a>
                <p class="hero__card-note">*Ürün tipine göre teknik değer değişebilir; ürün sayfasındaki spesifikasyonu esas alın.</p>
            </aside>
        </div>
    </div>

    <div class="hero__media-ctrl">
        <button type="button" class="media-toggle" id="hero-video-toggle" aria-pressed="false" hidden>
            {!! $icPause !!}<span>Videoyu durdur</span>
        </button>
    </div>

    {{-- Çözüm ortakları + TCMB kurları --}}
    <div class="hero-ticker" id="hero-ticker" role="region" aria-label="Çözüm ortakları ve TCMB döviz kurları">
        <div class="hero-ticker__label" aria-hidden="true">
            <span>ENDÜSTRİYEL</span>
            <strong>EKOSİSTEM</strong>
        </div>
        <div class="hero-ticker__viewport">
            <div class="hero-ticker__track">
                @foreach([false, true] as $isDup)
                    @foreach($tPartners as $ti => $tp)
                        @php $tImg = $tImages->get($ti); @endphp
                        <a href="{{ $tp['url'] }}" class="t-partner" target="_blank" rel="noopener noreferrer"
                           @if($isDup) data-dup tabindex="-1" aria-hidden="true" @endif>
                            <span class="t-partner__ico" aria-hidden="true">
                                @if($tImg && is_object($tImg) && isset($tImg->route))
                                    <img src="{{ asset($tImg->route) }}" alt="" width="44" height="28" loading="lazy" decoding="async">
                                @else{{ $tp['mono'] }}@endif
                            </span>
                            <span class="t-partner__body">
                                <span class="t-partner__name">{{ $tp['name'] }}</span>
                                <span class="t-partner__desc">{{ $tp['desc'] }}</span>
                            </span>
                            @unless($isDup)<span class="sr-only">(yeni sekmede açılır)</span>@endunless
                        </a>
                        <span class="t-sep" aria-hidden="true" @if($isDup) data-dup @endif>{!! $icStar !!}</span>
                    @endforeach
                    @foreach($kurlar as $kur)
                        <div class="t-kur" @if($isDup) data-dup aria-hidden="true" @endif>
                            <span class="t-kur__code-badge" aria-hidden="true">{{ $kur['code'] }}</span>
                            <span class="t-kur__head">
                                <span class="t-kur__code">{{ $kur['label'] }}</span>
                                <span class="t-kur__source">TCMB</span>
                            </span>
                            <dl class="t-kur__grid">
                                <div class="t-kur__cell"><dt class="t-kur__lbl">Alış</dt><dd class="t-kur__val buy kur-loading" data-kur="{{ $kur['prefix'] }}-fb">···</dd></div>
                                <div class="t-kur__cell"><dt class="t-kur__lbl">Efk. Alış</dt><dd class="t-kur__val buy kur-loading" data-kur="{{ $kur['prefix'] }}-eb">···</dd></div>
                                <div class="t-kur__cell"><dt class="t-kur__lbl">Satış</dt><dd class="t-kur__val sell kur-loading" data-kur="{{ $kur['prefix'] }}-fs">···</dd></div>
                                <div class="t-kur__cell"><dt class="t-kur__lbl">Efk. Satış</dt><dd class="t-kur__val sell kur-loading" data-kur="{{ $kur['prefix'] }}-es">···</dd></div>
                            </dl>
                        </div>
                        <span class="t-sep" aria-hidden="true" @if($isDup) data-dup @endif>{!! $icStar !!}</span>
                    @endforeach
                @endforeach
            </div>
        </div>
        <div class="hero-ticker__ctrl">
            <button type="button" class="media-toggle media-toggle--dark" id="ticker-toggle" aria-pressed="false" aria-label="Kayan şeridi durdur">{!! $icPause !!}</button>
        </div>
    </div>
</section>

{{-- ══ TEKLİF TALEBİ — ölçü kalemleri + iletişim; e-posta sunucudan gider (POST /teklif-iste) ══ --}}
@php
    $rqProducts = [
        ['ad' => 'Sert Krom Kaplı Mil',             'tip' => 'mil'],
        ['ad' => 'CK45 İndüksiyonlu Krom Kaplı Mil', 'tip' => 'mil'],
        ['ad' => 'CF53 İndüksiyonlu Kromlu Mil',    'tip' => 'mil'],
        ['ad' => 'Honlanmış Boru (H8)',             'tip' => 'boru'],
        ['ad' => 'Krom Kaplı Rod Boru',             'tip' => 'boru'],
        ['ad' => 'Diğer / özel ürün',               'tip' => 'ozel'],
    ];
@endphp
<section class="rq" id="olcu-sorgula" aria-labelledby="rq-h">
    <div class="container">

        <header class="rq__head reveal">
            <p class="rq__kicker">Ölçüye göre teklif</p>
            <h2 class="rq__title" id="rq-h">Ölçünüzü girin, <span>teklifi e-postayla alın</span></h2>
            <p class="rq__answer">{{ $rqAnswer }}</p>
        </header>

        <ol class="rq__steps reveal" aria-label="Teklif alma adımları">
            @foreach($rqSteps as $ri => $rs)
            <li class="rq__step">
                <span class="rq__step-no" aria-hidden="true">{{ str_pad($ri + 1, 2, '0', STR_PAD_LEFT) }}</span>
                <h3 class="rq__step-t">{{ $rs['t'] }}</h3>
                <p class="rq__step-d">{{ $rs['d'] }}</p>
            </li>
            @endforeach
        </ol>

        <div class="rq__wrap">

            <aside class="rq__info reveal" aria-label="Stok, teslimat ve iletişim">
                <svg class="rq__art" viewBox="0 26 420 84" role="img" aria-label="Krom kaplı milin yandan ve kesit teknik çizimi" focusable="false">
                    <defs><pattern id="rqHatch" width="7" height="7" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><path d="M0 0V7" stroke="rgba(175,195,218,.38)" stroke-width="1"/></pattern></defs>
                    <path class="rq__art-c" d="M8 66H292M352 24V108M300 66H404"/>
                    <path class="rq__art-l" d="M26 44H252L262 52V80L252 88H26L16 80V52Z"/>
                    <path class="rq__art-h" d="M30 53H246M30 79H200"/>
                    <path class="rq__art-d" d="M278 44V88M272 44H284M272 88H284"/>
                    <circle class="rq__art-l" cx="352" cy="66" r="36"/>
                    <circle class="rq__art-l" cx="352" cy="66" r="30" fill="url(#rqHatch)"/>
                </svg>
                <dl class="rq__facts">
                    <div><dt>Mil stok çap aralığı</dt><dd>Ø4–200 mm</dd></div>
                    <div><dt>Minimum sipariş</dt><dd>1 adet <small>(stoktakiler)</small></dd></div>
                    <div><dt>Teslimat</dt><dd>24–48 saat <small>Marmara aynı gün</small></dd></div>
                </dl>
                <p class="rq__trust-line">{!! $icCheck !!} NIMET yetkili Türkiye distribütörü · Bursa ve İstanbul stok</p>
                <ul class="rq__tel" aria-label="Telefon">
                    <li><span>İstanbul</span><a href="tel:+905059344354">0505 934 43 54</a></li>
                    <li><span>Bursa</span><a href="tel:+905414594354">0541 459 43 54</a></li>
                </ul>
            </aside>

            <div class="rq__card reveal">
                <form id="rq-form" action="{{ url('/teklif-iste') }}" method="post" novalidate>
                    @csrf
                    <div class="rq-hp" aria-hidden="true"><label>Web sitesi<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                    <input type="hidden" name="ts" value="{{ encrypt(time()) }}">
                    {{-- Spam koruması: şifreli, tek kullanımlık işlem sorusu tokenı (sunucu doğrular) --}}
                    <input type="hidden" name="cq" id="rq-cq" value="{{ $rqCapToken }}">

                    <div id="rq-body">
                        <fieldset class="rq__sec">
                            <legend><span>01</span> Ölçü kalemleri</legend>
                            <div id="rq-rows"></div>
                            <button type="button" class="rq__add" id="rq-add"><span aria-hidden="true">+</span> Ölçü kalemi ekle <small id="rq-count">1 / 10</small></button>
                        </fieldset>

                        <fieldset class="rq__sec">
                            <legend><span>02</span> İletişim ve not</legend>
                            <div class="rq-grid rq-grid--c">
                                <div class="rq-f"><label for="rq-ad">Ad soyad *</label><input id="rq-ad" type="text" autocomplete="name" maxlength="120"></div>
                                <div class="rq-f"><label for="rq-firma">Firma</label><input id="rq-firma" type="text" autocomplete="organization" maxlength="160"></div>
                                <div class="rq-f"><label for="rq-tel">Telefon *</label><input id="rq-tel" type="tel" inputmode="tel" autocomplete="tel" maxlength="40" placeholder="05xx xxx xx xx"></div>
                                <div class="rq-f"><label for="rq-email">E-posta *</label><input id="rq-email" type="email" inputmode="email" autocomplete="email" maxlength="160" placeholder="ornek@firma.com"></div>
                                <div class="rq-f rq-f--wide"><label for="rq-mesaj">Not (tolerans, malzeme, yüzey, kullanım yeri; opsiyonel)</label><textarea id="rq-mesaj" rows="2" maxlength="2000"></textarea></div>
                            </div>
                        </fieldset>

                        <div class="rq__foot">
                            {{-- İnsan doğrulaması: basit işlem sorusu --}}
                            <div class="rq-f rq__cap" id="rq-cap">
                                <label for="rq-cevap">Robot değilim: <b id="rq-cap-q">{{ $rqCapA }} + {{ $rqCapB }}</b> = ? *</label>
                                <input id="rq-cevap" type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="off" maxlength="3" aria-describedby="rq-cap-help">
                                <span class="rq__cap-help" id="rq-cap-help">Spam talepleri engellemek için işlemin sonucunu yazın.</span>
                            </div>
                            <label class="rq__check"><input type="checkbox" id="rq-kvkk"><span>Kişisel verilerimin, teklif talebimin değerlendirilmesi amacıyla işlenmesini kabul ediyorum. *</span></label>
                            <p class="rq__status" id="rq-status" role="alert" hidden></p>
                            <button type="submit" class="fl-btn-primary rq__submit" id="rq-submit">Teklif talebini gönder {!! $icArrow !!}</button>
                        </div>
                    </div>

                    <div class="rq__done" id="rq-done" tabindex="-1" hidden>
                        <div class="rq__done-ico" aria-hidden="true">{!! $icCheck !!}</div>
                        <h3>Talebiniz alındı</h3>
                        <p>Teklif talebiniz teknik ekibimize iletildi. Referans numaranız:</p>
                        <p class="rq__ref" id="rq-ref"></p>
                        <ul class="rq__sum" id="rq-sum"></ul>
                        <p class="rq__done-note">Acil durumlarda İstanbul <a href="tel:+905059344354">0505 934 43 54</a> veya Bursa <a href="tel:+905414594354">0541 459 43 54</a> numarasını arayabilirsiniz.</p>
                        <button type="button" class="fl-btn-outline" id="rq-new">Yeni talep oluştur</button>
                    </div>
                </form>

                <noscript><p class="rq__noscript">Teklif formu için JavaScript gerekir. Lütfen <a href="{{ url('/iletisim') }}">iletişim sayfamızı</a> kullanın veya İstanbul 0505 934 43 54 / Bursa 0541 459 43 54 numarasını arayın.</p></noscript>

                <template id="rq-row-tpl">
                    <div class="rq-row" data-row>
                        <div class="rq-row__head">
                            <span class="rq-row__no" data-no>Kalem 1</span>
                            <p class="rq-row__hint" data-hint role="status"></p>
                            <button type="button" class="rq-row__btn" data-notebtn>+ Not</button>
                            <button type="button" class="rq-row__btn rq-row__del" data-del aria-label="Bu kalemi kaldır">Kaldır</button>
                        </div>
                        <div class="rq-grid rq-grid--r">
                            <div class="rq-f rq-f--urun"><label data-for="urun">Ürün *</label>
                                <select data-k="urun">
                                    @foreach($rqProducts as $rp)
                                    <option value="{{ $rp['ad'] }}" data-tip="{{ $rp['tip'] }}">{{ $rp['ad'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="rq-f" data-show="mil"><label data-for="cap">Çap (mm) *</label><input data-k="cap" type="number" inputmode="decimal" min="0" step="0.1" placeholder="50"></div>
                            <div class="rq-f" data-show="boru"><label data-for="dis_cap">Dış çap (mm) *</label><input data-k="dis_cap" type="number" inputmode="decimal" min="0" step="0.1" placeholder="60"></div>
                            <div class="rq-f" data-show="boru"><label data-for="ic_cap">İç çap (mm) *</label><input data-k="ic_cap" type="number" inputmode="decimal" min="0" step="0.1" placeholder="50"></div>
                            <div class="rq-f rq-f--boy"><label data-for="boy">Boy (mm) *</label><input data-k="boy" type="number" inputmode="numeric" min="0" step="1" placeholder="1500"></div>
                            <div class="rq-f rq-f--adet"><label data-for="adet">Adet *</label><input data-k="adet" type="number" inputmode="numeric" min="1" step="1" value="1"></div>
                            <div class="rq-f rq-f--not" data-notebox hidden><label data-for="not" data-not>Not (opsiyonel)</label><input data-k="not" type="text" maxlength="300" placeholder="tolerans, malzeme, yüzey…"></div>
                        </div>
                    </div>
                </template>
            </div>

        </div>
    </div>
</section>

{{-- ══ ÜRÜNLER ══ --}}
<section class="products" id="urunler" aria-labelledby="prod-heading">
    <div class="container">
        <div class="products__head reveal">
            <div class="products__intro">
                <p class="section-tag">Ürün Portföyümüz</p>
                <h2 class="section-title" id="prod-heading">Akışkan Teknolojileri <span class="yel">Çözümleri</span></h2>
                <p class="section-lead">NIMET krom kaplı mil ve hidrolik borudan TOX® pres sistemlerine kadar {{ count($homeCards) }} ürün grubu; Bursa ve İstanbul stoklarından ölçüye kesilerek sevk edilir.</p>
            </div>
            <div class="products__acts">
                <a href="#dokumanlar" class="products__link">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13z"/></svg>
                    Teknik Katalog (PDF)
                </a>
                <a href="#olcu-sorgula" class="fl-btn-primary">Teklif İste {!! $icArrow !!}</a>
            </div>
        </div>

        <ul class="prod-grid" aria-label="Ürün grupları">
            @foreach($homeCards as $i => $item)
                @php
                    $no = $i + 1;
                    if ($item['type'] === 'custom') {
                        $c = $item['data'];
                        $card = ['name' => $c['name'], 'short' => $c['short'], 'url' => $c['url'], 'img' => $c['img'], 'alt' => $c['imgAlt'], 'badge' => $c['badge'], 'brand' => true, 'stock' => $c['stock']];
                    } else {
                        $p = $item['data'];
                        $card = [
                            'name'  => $p->name ?? '',
                            'short' => !empty($p->short) ? \Illuminate\Support\Str::limit(strip_tags($p->short), 120) : '',
                            'url'   => !empty($p->slug) ? route('content', $p->slug) : '#',
                            'img'   => !empty($p->image) ? asset('uploads/content/' . $p->image) : '',
                            'alt'   => ($p->name ?? 'Ürün') . ' — NIMET, Flutecnica stok',
                            'badge' => str_pad($no, 2, '0', STR_PAD_LEFT), 'brand' => false, 'stock' => 'Stokta',
                        ];
                    }
                @endphp
                <li class="prod-grid__item reveal">
                    <article class="prod-card">
                        @php $cSpecs = $specFor($card['name']); @endphp
                        <div class="prod-card__img">
                            @if($card['img'])
                            <img src="{{ $card['img'] }}" alt="{{ $card['alt'] }}" loading="lazy" decoding="async" width="640" height="400">
                            @else
                            <svg class="prod-card__ph" viewBox="0 0 320 200" role="img" aria-label="{{ $card['alt'] }}" preserveAspectRatio="xMidYMid slice">
                                <defs>
                                    <linearGradient id="phRod{{ $no }}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#F7F8FA"/><stop offset=".35" stop-color="#CBD7E5"/><stop offset=".55" stop-color="#5B7FA6"/><stop offset=".8" stop-color="#DFE6EF"/><stop offset="1" stop-color="#9DB4CE"/></linearGradient>
                                </defs>
                                <rect width="320" height="200" fill="#EDF1F7"/>
                                <g transform="rotate(-14 160 100)"><rect x="-20" y="84" width="360" height="34" rx="17" fill="url(#phRod{{ $no }})"/><ellipse cx="340" cy="101" rx="7" ry="17" fill="#CBD7E5"/></g>
                            </svg>
                            @endif
                            <span class="prod-card__badge {{ $card['brand'] ? 'prod-card__badge--brand' : '' }}" aria-hidden="true">{{ $card['badge'] }}</span>
                            <span class="prod-card__stock">{!! $icCheck !!}{{ $card['stock'] }}</span>
                        </div>
                        @if(count($cSpecs))
                        <div class="prod-spec" id="pspec-{{ $no }}" role="group" aria-label="{{ $card['name'] }} teknik özellikleri">
                            <span class="prod-spec__head" aria-hidden="true">Teknik özellikler</span>
                            @foreach($cSpecs as $si => $sp)
                            <div class="prod-spec__row prod-spec__row--{{ $si + 1 }}">
                                <span class="prod-spec__dot" aria-hidden="true"></span>
                                <span class="prod-spec__line" aria-hidden="true"></span>
                                <span class="prod-spec__tag"><span class="prod-spec__k">{{ $sp[0] }}</span> <span class="prod-spec__v">{{ $sp[1] }}</span></span>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" class="prod-spec__btn" aria-expanded="false" aria-controls="pspec-{{ $no }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17v2h6v-2H3zM3 5v2h10V5H3zm10 16v-2h8v-2h-8v-2h-2v6h2zM7 9v2H3v2h4v2h2V9H7zm14 4v-2H11v2h10zm-6-4h2V7h4V5h-4V3h-2v6z"/></svg>
                            <span>Özellikler</span>
                        </button>
                        @endif
                        <div class="prod-card__body">
                            <h3 class="prod-card__title"><a href="{{ $card['url'] }}">{{ $card['name'] }}</a></h3>
                            @if($card['short'])<p class="prod-card__short">{{ $card['short'] }}</p>@endif
                            @if(count($cSpecs))
                            <dl class="prod-keys" aria-label="Kritik teknik değerler">
                                @foreach(array_slice($cSpecs, 0, 2) as $kp)
                                <div><dt>{{ $kp[0] }}</dt><dd>{{ $kp[1] }}</dd></div>
                                @endforeach
                            </dl>
                            @endif
                            <div class="prod-card__foot" aria-hidden="true">
                                <span class="prod-card__more">Detayları İncele</span>
                                <span class="prod-card__arrow">{!! $icChevron !!}</span>
                            </div>
                        </div>
                    </article>
                </li>
            @endforeach
        </ul>
    </div>
</section>

{{-- ══ ÜRÜN KARŞILAŞTIRMA (rev. 19: yeniden tasarlandı) ══ --}}
<section class="compare" id="urun-karsilastirma" aria-labelledby="compare-heading">
    <div class="container">
        <div class="section-head-deco reveal">
            <div>
                <p class="section-tag">Teknik Karşılaştırma</p>
                <h2 class="section-title" id="compare-heading">Ürün <span class="yel">Karşılaştırmaları</span></h2>
                <p class="section-lead">Tolerans, sertlik ve yüzey kalitesine göre hangi mil veya borunun uygulamanıza uyduğunu tek tabloda görün.</p>
            </div>
            <span class="section-index" aria-hidden="true">03</span>
        </div>

        {{-- GEO: yapay zekâ motorlarının alıntılayabileceği kısa cevap --}}
        <p class="compare__answer reveal"><strong>Kısa cevap:</strong> {{ $specAnswer }}</p>

        <div class="fx-table reveal">
            <div class="fx-table__head">
                <h3 class="fx-table__title" id="spec-caption">Mil ve boru seçim tablosu</h3>
                <p class="fx-table__meta">{{ count($specRows) }} ürün · tolerans · sertlik · yüzey</p>
            </div>
            <div class="spec-wrap" role="region" aria-labelledby="spec-caption" tabindex="0">
                <table class="spec">
                    <caption class="sr-only">NIMET mil ve boru ürünlerinin tolerans, sertlik, yüzey pürüzlülüğü ve kullanım alanı karşılaştırması</caption>
                    <colgroup><col class="c-urun"><col class="c-tol"><col class="c-sert"><col class="c-ra"><col class="c-kul"></colgroup>
                    <thead>
                        <tr><th scope="col">Ürün</th><th scope="col">Tolerans</th><th scope="col">Sertlik / Kaplama</th><th scope="col">Yüzey</th><th scope="col">Tipik kullanım</th></tr>
                    </thead>
                    <tbody>
                        @foreach($specRows as $ri => $r)
                        <tr>
                            <th scope="row"><a href="{{ $r['url'] }}"><span class="fx-num" aria-hidden="true">{{ str_pad($ri + 1, 2, '0', STR_PAD_LEFT) }}</span><span class="fx-name">{{ $r['urun'] }}</span></a></th>
                            <td data-label="Tolerans"><span class="fx-pill">{{ $r['tol'] }}</span></td>
                            <td data-label="Sertlik / Kaplama" @if($r['sert'] === '—') class="is-na" @endif>@if($r['sert'] === '—')<span class="fx-na" title="Bu üründe sertlik/kaplama değeri belirtilmemiştir">—</span>@else{{ $r['sert'] }}@endif</td>
                            <td data-label="Yüzey" class="mono @if($r['ra'] === '—') is-na @endif">@if($r['ra'] === '—')<span class="fx-na" title="Bu üründe yüzey değeri belirtilmemiştir">—</span>@else{{ $r['ra'] }}@endif</td>
                            <td data-label="Kullanım">{{ $r['kul'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="fx-table__note">Değerler ilgili ürün sayfalarındaki teknik spesifikasyonla aynıdır. Mil üzerinde rulman yürüyecekse sert krom yerine indüksiyonlu mil (CK45 veya CF53) seçin; kesin seçim için <a href="#olcu-sorgula">ölçünüzü gönderin</a>.</p>
        </div>
    </div>
</section>

{{-- ══ MÜHENDİSLİK ARAÇLARI ══ --}}
@php
$engTools = [
    ['href' => url('/iso-mil-ve-delik-toleranslari-hesaplama-araci'), 'name' => 'ISO Tolerans',       'cat' => 'Mil & Delik', 'icon' => 'M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58a.49.49 0 00.12-.61l-1.92-3.32a.49.49 0 00-.6-.22l-2.39.96a7.03 7.03 0 00-1.62-.94l-.36-2.54a.48.48 0 00-.48-.41h-3.84a.47.47 0 00-.47.41l-.36 2.54a7.3 7.3 0 00-1.62.94l-2.39-.96a.48.48 0 00-.59.22L2.74 8.87a.47.47 0 00.12.61l2.03 1.58a7.12 7.12 0 000 1.88l-2.03 1.58a.49.49 0 00-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.35 1.05.63 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54a7.3 7.3 0 001.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32a.47.47 0 00-.12-.61l-2.01-1.58zM12 15.6a3.6 3.6 0 110-7.2 3.6 3.6 0 010 7.2z'],
    ['href' => url('/hidrolik-silindir-donanim-hesaplayici'),          'name' => 'Silindir Donanım',   'cat' => 'Hidrolik',    'icon' => 'M20 7H4c-1.1 0-2 .9-2 2v6c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-10 7H6v-4h4v4zm8 0h-6v-4h6v4z'],
    ['href' => url('/hidrolik-silindir-kuvvet-hesaplama'),             'name' => 'Kuvvet Hesaplama',   'cat' => 'Hidrolik',    'icon' => 'M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z'],
    ['href' => url('/kromlu-mil-burkulma-hesabi'),                     'name' => 'Burkulma Hesabı',    'cat' => 'Yapısal',     'icon' => 'M3 17v2h6v-2H3zM3 5v2h10V5H3zm10 16v-2h8v-2h-8v-2h-2v6h2zM7 9v2H3v2h4v2h2V9H7zm14 4v-2H11v2h10zm-6-4h2V7h4V5h-4V3h-2v6z'],
    ['href' => url('/celik-mil-ve-boru-agirlik-hesaplama'),            'name' => 'Ağırlık Hesaplama',  'cat' => 'Malzeme',     'icon' => 'M20.57 14.86L22 13.43 20.57 12 17 15.57 8.43 7 12 3.43 10.57 2 9.14 3.43 7.71 2 5.57 4.14 4.14 2.71 2.71 4.14l1.43 1.43L2 7.71l1.43 1.43L2 10.57 3.43 12 7 8.43 15.57 17 12 20.57 13.43 22l1.43-1.43L16.29 22l2.14-2.14 1.43 1.43 1.43-1.43-1.43-1.43L22 16.29z'],
    ['href' => url('/boru-patlama-basinci-hesaplama'),                 'name' => 'Patlama Basıncı',    'cat' => 'Güvenlik',    'icon' => 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z'],
    ['href' => url('/sertlik-cevrim-tablosu'),                         'name' => 'Sertlik Çevrim',     'cat' => 'Malzeme',     'icon' => 'M19 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V5a2 2 0 00-2-2zM7 17l3-8 2 5 2-3 3 6H7z'],
    ['href' => url('/mobil-vinc-hesaplayici'),                         'name' => 'Vinç Hesaplayıcı',   'cat' => 'Kaldırma',    'icon' => 'M17 11H7V9l-4 3 4 3v-2h10v2l4-3-4-3v2zm-5 7c-3.87 0-7-3.13-7-7h2c0 2.76 2.24 5 5 5s5-2.24 5-5h2c0 3.87-3.13 7-7 7zM12 3C8.13 3 5 6.13 5 10h2c0-2.76 2.24-5 5-5s5 2.24 5 5h2c0-3.87-3.13-7-7-7z'],
    ['href' => route('tools.mil-secici'),                              'name' => 'Akıllı Mil Seçici',  'cat' => 'Seçim Rehberi', 'icon' => 'M11 5H5v6h6V5zm0 8H5v6h6v-6zm8-8h-6v6h6V5zm-6 8h2v2h-2zm2 2h2v2h-2zm2-2h2v2h-2zm-4 4h2v2h-2zm4 0h2v2h-2z'],
    ['href' => route('celik.karsilastirma'),                           'name' => 'Çelik Karşılaştırma','cat' => 'Malzeme Analizi', 'icon' => 'M10 3H4a1 1 0 00-1 1v16a1 1 0 001 1h6V3zm2 18h8a1 1 0 001-1V4a1 1 0 00-1-1h-8v18zM5 7h3v2H5V7zm9 0h5v2h-5V7zm0 4h5v2h-5v-2z'],
];
@endphp
<section class="eng-tools" id="muhendislik-alani" aria-labelledby="eng-heading">
    <div class="container">
        <div class="section-head-deco reveal">
            <div>
                <p class="section-tag">Mühendislik Araçları</p>
                <h2 class="section-title" id="eng-heading">Teknik Hesaplama <span class="yel">Alanı</span></h2>
            </div>
            <span class="section-index" aria-hidden="true">04</span>
        </div>
        <p class="section-lead reveal">Hidrolik silindir, mil ve boru tasarımı için {{ count($engTools) }} ücretsiz online hesaplama aracı. Tümü tarayıcıda çalışır; kurulum ya da üyelik gerektirmez.</p>
        <ul class="eng__tools-grid reveal">
            @foreach($engTools as $tool)
            <li>
                <a href="{{ $tool['href'] }}" class="eng__tool">
                    <span class="eng__tool-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="{{ $tool['icon'] }}"/></svg></span>
                    <span class="eng__tool-text"><span class="eng__tool-name">{{ $tool['name'] }}</span><span class="eng__tool-cat">{{ $tool['cat'] }}</span></span>
                    <span class="eng__tool-arrow" aria-hidden="true">{!! $icChevron !!}</span>
                </a>
            </li>
            @endforeach
        </ul>
        <div class="eng__footer reveal">
            {!! $icCheck !!}
            <p class="eng__footer-text"><strong>Ücretsiz &amp; Online</strong> — Tüm hesaplama araçları tarayıcıdan çalışır, kurulum gerektirmez.</p>
        </div>
        <p class="legal-note">İşbu hesaplama modülleri ön bilgilendirme amaçlıdır; sonuçların doğruluğu konusunda Flutecnica garanti vermez ve kullanım kaynaklı zararlardan sorumlu tutulamaz. Tüm algoritma ve teknik içeriklerin telif hakları münhasıran Flutecnica Akışkan Teknolojileri'ne ait olup; yazılı izin olmaksızın kopyalanması 5846 sayılı kanun uyarınca yasaktır. Her hakkı saklıdır.</p>
    </div>
</section>

{{-- ══ PDF DÖKÜMANLAR ══ --}}
@php
$pdfDocs = [
    ['name' => 'Teknik Ürün Kataloğu 2026-2027', 'desc' => 'Tüm ürünlerin teknik özellikleri, çap tabloları ve tolerans değerleri', 'badge' => '2026',  'previewUrl' => '/uploads/photos/teknik-katalog-2026-2027.pdf', 'previewRoute' => null, 'downloadRoute' => 'teknik.katalog.pdf.indir'],
    ['name' => 'CK45 İndüksiyonlu Krom Kaplı Mil', 'desc' => 'CK45 malzeme teknik veri sayfası, tolerans ve yüzey kalitesi',        'badge' => 'NIMET', 'previewUrl' => null, 'previewRoute' => 'ck45pdf',              'downloadRoute' => 'ck45pdf.indir'],
    ['name' => 'CF53 İndüksiyonlu Mil',            'desc' => 'CF53 malzeme teknik özellikleri, sertlik ve kimyasal bileşim',       'badge' => 'NIMET', 'previewUrl' => null, 'previewRoute' => 'cf53.teknik.pdf',      'downloadRoute' => 'cf53.teknik.pdf.indir'],
    ['name' => 'Sert Krom Kaplı Mil',              'desc' => 'Krom kaplama kalınlıkları, çap tablosu ve uygulama alanları',        'badge' => 'NIMET', 'previewUrl' => null, 'previewRoute' => 'kromlumil.pdf',        'downloadRoute' => 'kromlumil.pdf.indir'],
    ['name' => 'H8 Honlanmış Boru',                'desc' => 'Hidrolik silindir borusu H8 tolerans, iç yüzey ve basınç değerleri', 'badge' => 'NIMET', 'previewUrl' => '/h8-boru-teknik-pdf', 'previewRoute' => null, 'downloadRoute' => 'h8borpdf.indir'],
    ['name' => 'Krom Kaplı Rod Boru',              'desc' => 'Rod boru teknik özellikleri, dış krom kaplama ve çap listesi',       'badge' => 'NIMET', 'previewUrl' => null, 'previewRoute' => 'rodboru.pdf',          'downloadRoute' => 'rodboru.pdf.indir'],
    ['name' => 'Kalite Sertifikaları',             'desc' => 'ISO 9001, TSE, CE belgeleri ve NIMET kalite sertifikaları',          'badge' => '2025',  'previewUrl' => null, 'previewRoute' => 'kalite.sertifika.pdf', 'downloadRoute' => 'kalite.sertifika.pdf.indir'],
];
$pdfSvgDoc = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13zm-3 3h4v2h-4v-2zm6 4H8v-2h8v2zm0 4H8v-2h8v2z"/></svg>';
$pdfSvgEye = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17a5 5 0 110-10 5 5 0 010 10zm0-8a3 3 0 100 6 3 3 0 000-6z"/></svg>';
$pdfSvgDl  = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>';
@endphp
<section class="pdf-docs" id="dokumanlar" aria-labelledby="pdf-heading">
    <div class="container">
        <div class="section-head-deco reveal">
            <div>
                <p class="section-tag">Teknik Dökümanlar</p>
                <h2 class="section-title" id="pdf-heading">Katalog &amp; <span class="yel">Teknik</span> Bilgiler</h2>
            </div>
            <span class="section-index" aria-hidden="true">05</span>
        </div>
        <p class="section-lead reveal">Teknik kataloglarımızı, ürün veri sayfalarımızı ve kalite sertifikalarımızı PDF olarak görüntüleyebilir veya indirebilirsiniz.</p>
        <ul class="pdf__grid reveal">
            @foreach($pdfDocs as $pi => $doc)
            @php
                $previewUrl = $doc['previewUrl'] ?? ($doc['previewRoute'] ? route($doc['previewRoute']) : '#');
                $dlUrl      = $doc['downloadRoute'] ? route($doc['downloadRoute']) : $previewUrl;
            @endphp
            <li class="{{ $pi === 0 ? 'pdf__item--featured' : '' }}">
                <article class="pdf__card">
                    <div class="pdf__card-top">
                        <div class="pdf__ico">{!! $pdfSvgDoc !!}</div>
                        <div class="pdf__info">
                            <h3 class="pdf__name">{{ $doc['name'] }}</h3>
                            <p class="pdf__desc">{{ $doc['desc'] }}</p>
                        </div>
                    </div>
                    <div class="pdf__meta"><span>PDF</span><span>{{ $doc['badge'] }}</span></div>
                    <div class="pdf__acts">
                        <a href="{{ $previewUrl }}" class="pdf__btn-v js-pdf-preview" target="_blank" rel="noopener"
                           data-title="{{ $doc['name'] }}" data-download="{{ $dlUrl }}"
                           aria-label="{{ $doc['name'] }} PDF önizle">{!! $pdfSvgEye !!}Önizle</a>
                        <a href="{{ $dlUrl }}" class="pdf__btn-d" download aria-label="{{ $doc['name'] }} PDF indir">{!! $pdfSvgDl !!}İndir</a>
                    </div>
                </article>
            </li>
            @endforeach
        </ul>
        <div class="pdf__foot reveal">
            {!! $pdfSvgDoc !!}
            <p class="pdf__foot-text"><strong>PDF formatında</strong> — Tüm dökümanlar tarayıcıda görüntülenebilir veya cihazınıza indirilebilir.</p>
        </div>
    </div>
</section>

{{-- PDF MODAL — native <dialog> --}}
<dialog class="pdfM" id="pdfMO" aria-labelledby="pdfMTitle">
    <div class="pdfM__box">
        <div class="pdfM__head">
            <h2>{!! $pdfSvgDoc !!}<span id="pdfMTitle">PDF Önizleme</span></h2>
            <div class="pdfM__ctrl">
                <a id="pdfMDl" class="pdfM__dl" href="#" download>{!! $pdfSvgDl !!}İndir</a>
                <button type="button" class="pdfM__x" id="pdfMClose" aria-label="Önizlemeyi kapat"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
            </div>
        </div>
        <div class="pdfM__body"><iframe id="pdfMF" src="about:blank" title="PDF önizleme"></iframe></div>
    </div>
</dialog>

{{-- ══ ISO TOLERANS KARŞILAŞTIRMA (İNTERAKTİF) ══ --}}
<section class="tolc" id="tolerans-karsilastirma" aria-labelledby="tolc-heading">
    <div class="container">
        <div class="section-head-deco reveal">
            <div>
                <p class="section-tag">İnteraktif Mühendislik Aracı</p>
                <h2 class="section-title" id="tolc-heading">ISO Tolerans <span class="yel">Karşılaştırma</span></h2>
            </div>
            <span class="section-index" aria-hidden="true">06</span>
        </div>
        <p class="section-lead tolc__sub reveal">Nominal çapı girin, karşılaştırmak istediğiniz tolerans sınıflarını ekleyin (örn. <code>f7</code>, <code>h7</code>, <code>H8</code>, <code>g6</code>; en fazla 12). ISO 286'ya göre üst/alt sapma, tolerans genişliği ve ölçü sınırları anında hesaplanır, <strong>bant grafiğiyle</strong> karşılaştırılır; mil–delik çiftleri için <strong>geçme (fit) analizi</strong> yapılır.</p>

        <div class="tolc__panel reveal">
            <div class="tolc__panel-grid">
                <div class="tolc__field tolc__field--dia">
                    <label for="tolc-dia">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm0 18a8 8 0 110-16 8 8 0 010 16zm-1-13h2v6h-2zm0 8h2v2h-2z"/></svg>
                        Nominal Çap (mm)
                    </label>
                    <input type="number" id="tolc-dia" min="1" max="500" step="0.1" value="50" inputmode="decimal" autocomplete="off" aria-describedby="tolc-dia-help">
                    <span class="tolc__help" id="tolc-dia-help">1–500 mm arası</span>
                    <div class="tolc__dia-presets" role="group" aria-label="Hazır çaplar">
                        @foreach([20, 50, 80, 120] as $d)
                            <button type="button" class="tolc__dia-preset" data-dia="{{ $d }}">Ø{{ $d }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="tolc__field tolc__chipwrap">
                    <label for="tolc-chipinput">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5h18v2H3zm0 6h18v2H3zm0 6h12v2H3z"/></svg>
                        Tolerans Sınıfları
                    </label>
                    <div class="tolc__chipbox" id="tolc-chipbox">
                        <ul id="tolc-chiplist" class="tolc__chiplist" aria-label="Eklenen tolerans sınıfları"></ul>
                        <input type="text" id="tolc-chipinput" class="tolc__chipinput" list="tolc-classes"
                               autocomplete="off" autocapitalize="off" spellcheck="false"
                               placeholder="Yaz ve Enter'a bas (örn. f7)" aria-describedby="tolc-chip-help">
                    </div>
                    <span class="tolc__help" id="tolc-chip-help">Küçük harf = mil (f7, h7, k6), büyük harf = delik (H7, H8). Enter, boşluk veya virgül ile ekleyin.</span>
                    <div class="tolc__quick" role="group" aria-label="Hızlı ekle">
                        <span class="tolc__quick-lbl" aria-hidden="true">Hızlı ekle:</span>
                        @foreach(['f7','f8','g6','h6','h7','h8','H7','H8','k6','m6','n6'] as $qc)
                            <button type="button" class="tolc__quick-btn" data-add="{{ $qc }}" aria-label="{{ $qc }} ekle">{{ $qc }}</button>
                        @endforeach
                    </div>
                    <datalist id="tolc-classes">
                        @foreach(['f7','f8','g6','g7','h6','h7','h8','h9','h11','e8','e9','d9','d10','d11','js6','js7','k6','m6','n6','p6','H7','H8','H9','H11','F7','F8','G7','E8','E9','D9','JS7','K7','M7','N7','P7'] as $o)
                            <option value="{{ $o }}">
                        @endforeach
                    </datalist>
                </div>
            </div>

            <div class="tolc__panel-foot">
                <button type="button" id="tolc-go" class="tolc__btn">Karşılaştır {!! $icArrow !!}</button>
                <button type="button" id="tolc-clear" class="tolc__btn tolc__btn--ghost">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                    Temizle
                </button>
                <p class="tolc__count" aria-live="polite"><b id="tolc-count">0</b> tolerans sınıfı ekli</p>
                <a href="#tolc-results" class="tolc__jump" id="tolc-jump" hidden>Sonuçlara git ↓</a>
            </div>
            <div class="tolc__err" id="tolc-err" role="alert" hidden></div>

            <div class="tolc__presets">
                <span class="tolc__presets-lbl" id="tolc-presets-lbl">Hazır geçme şablonları (tek tık)</span>
                <div class="tolc__presets-grid" role="group" aria-labelledby="tolc-presets-lbl">
                    <button type="button" class="tolc__preset tolc__preset--bos" data-preset="H8,f7"><span class="tolc__preset-name">Boşluklu</span><span class="tolc__preset-pair">H8 / f7 · hareketli</span></button>
                    <button type="button" class="tolc__preset tolc__preset--gec" data-preset="H7,k6"><span class="tolc__preset-name">Geçiş</span><span class="tolc__preset-pair">H7 / k6 · hassas</span></button>
                    <button type="button" class="tolc__preset tolc__preset--siki" data-preset="H7,n6"><span class="tolc__preset-name">Sıkı (Pres)</span><span class="tolc__preset-pair">H7 / n6 · sabit</span></button>
                    <button type="button" class="tolc__preset tolc__preset--bos" data-preset="H7,g6"><span class="tolc__preset-name">Kayar</span><span class="tolc__preset-pair">H7 / g6 · az boşluk</span></button>
                </div>
            </div>

            <div class="tolc__goal">
                <span class="tolc__goal-lbl" id="tolc-goal-lbl">Ne istiyorsunuz? — amaca göre geçme önerisi</span>
                <div class="tolc__goal-grid" role="group" aria-labelledby="tolc-goal-lbl">
                    <button type="button" class="tolc__goal-btn" aria-pressed="false" data-fit="H9,e9" data-desc="Serbest dönen, yağ filmli yataklama. Bol boşluklu çalışma; düşük sürtünme, kolay montaj.">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 6a6 6 0 110 12 6 6 0 010-12m0-2a8 8 0 100 16 8 8 0 000-16z"/></svg>Serbest dönen</button>
                    <button type="button" class="tolc__goal-btn" aria-pressed="false" data-fit="H8,f7" data-desc="Hareketli / kayar geçme. Hidrolik piston mili–keçe temasında en yaygın seçim.">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9h16v2H4zm0 4h10v2H4z"/></svg>Hareketli (kayar)</button>
                    <button type="button" class="tolc__goal-btn" aria-pressed="false" data-fit="H7,g6" data-desc="Hassas kayar geçme. Az boşluk, iyi merkezleme; sık sök-tak edilen hassas parçalar.">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l3 7h7l-5.5 4.5L18 21l-6-4-6 4 1.5-7.5L2 9h7z"/></svg>Hassas konum</button>
                    <button type="button" class="tolc__goal-btn" aria-pressed="false" data-fit="H7,k6" data-desc="Geçiş geçmesi. Kama/pim ile sabitlenen, merkezlemesi yüksek montajlar (rulman bileziği vb.).">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17h18v2H3zm0-7h18v2H3zm0-7h18v2H3z"/></svg>Sabit (kamalı)</button>
                    <button type="button" class="tolc__goal-btn" aria-pressed="false" data-fit="H7,p6" data-desc="Sıkı / pres geçme. Kalıcı, sökülmeyen montaj; ısıl büzdürme/pres kuvveti gerekir.">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4h12v4H6zm2 6h8v10H8z"/></svg>Kalıcı (pres)</button>
                </div>
                <p class="tolc__goal-out" id="tolc-goal-out" aria-live="polite"></p>
            </div>

            <details class="tolc__adv" id="tolc-adv">
                <summary>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 13V5a3 3 0 00-6 0v8a5 5 0 106 0zm-4-8a1 1 0 012 0h-1v1h1v2h-1v1h1v2h-2V5z"/></svg>
                    Çalışma sıcaklığı &amp; malzeme genleşmesi (gelişmiş)
                </summary>
                <div class="tolc__adv-body">
                    <div class="tolc__adv-field">
                        <label for="tolc-temp">Çalışma Sıcaklığı (°C)</label>
                        <input type="number" id="tolc-temp" value="20" min="-200" max="600" step="1" inputmode="numeric">
                    </div>
                    @php $mats = ['11.5' => 'Çelik', '16' => 'Paslanmaz Çelik', '10.5' => 'Döküm Demir', '23' => 'Alüminyum', '18' => 'Bronz', '19' => 'Pirinç', '17' => 'Bakır']; @endphp
                    <div class="tolc__adv-field">
                        <label for="tolc-mat-hole">Delik Malzemesi</label>
                        <select id="tolc-mat-hole">@foreach($mats as $mv => $mn)<option value="{{ $mv }}">{{ $mn }} ({{ $mv }})</option>@endforeach</select>
                    </div>
                    <div class="tolc__adv-field">
                        <label for="tolc-mat-shaft">Mil Malzemesi</label>
                        <select id="tolc-mat-shaft">@foreach($mats as $mv => $mn)<option value="{{ $mv }}">{{ $mn }} ({{ $mv }})</option>@endforeach</select>
                    </div>
                    <p class="tolc__adv-note">Parantez içindeki değer ısıl genleşme katsayısıdır (×10⁻⁶ /°C). Montaj sıcaklığı 20 °C kabul edilir. Farklı malzemelerde sıcaklık değişimi mil–delik çiftindeki efektif boşluğu/sıkılığı değiştirir; etki geçme kartlarında gösterilir.</p>
                </div>
            </details>
        </div>

        <div id="tolc-print-area">
            <div class="tolc__results" id="tolc-results" aria-live="polite" aria-busy="false" hidden>
                <div class="tolc__toolbar">
                    <button type="button" id="tolc-share" class="tolc__tool-btn">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.9 12a3.1 3.1 0 013.1-3.1h4V7H7a5 5 0 000 10h4v-1.9H7A3.1 3.1 0 013.9 12zM8 13h8v-2H8v2zm9-6h-4v1.9h4a3.1 3.1 0 010 6.2h-4V17h4a5 5 0 000-10z"/></svg>
                        <span>Bağlantıyı Kopyala</span>
                    </button>
                    <button type="button" id="tolc-print" class="tolc__tool-btn">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 8H5a3 3 0 00-3 3v6h4v4h12v-4h4v-6a3 3 0 00-3-3zm-3 11H8v-5h8v5zm3-7a1 1 0 110-2 1 1 0 010 2zm-1-9H6v4h12V3z"/></svg>
                        <span>PDF / Yazdır</span>
                    </button>
                    <a href="#olcu-sorgula" id="tolc-quote" class="tolc__tool-btn tolc__tool-btn--cta">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 2H4a2 2 0 00-2 2v18l4-4h14a2 2 0 002-2V4a2 2 0 00-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>
                        <span>Bu Ölçüde Teklif Al</span>
                    </a>
                </div>

                <div class="tolc__block">
                    <h3 class="tolc__block-title">
                        <span class="ico" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 3h18v4H3zm0 7h8v11H3zm10 0h8v5h-8zm0 7h8v4h-8z"/></svg></span>
                        Detaylı Değerler <small id="tolc-dia-label2"></small>
                    </h3>
                    <div class="tolc__table-wrap" role="region" aria-label="Tolerans değerleri tablosu" tabindex="0">
                        <table class="tolc__table">
                            <caption class="sr-only">Seçilen tolerans sınıflarının sapma ve ölçü sınırları</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Sınıf</th><th scope="col">Tip</th>
                                    <th scope="col">Üst Sapma<span class="th-sub">ES / es</span></th>
                                    <th scope="col">Alt Sapma<span class="th-sub">EI / ei</span></th>
                                    <th scope="col">Tolerans<span class="th-sub">IT</span></th>
                                    <th scope="col">Max Ø<span class="th-sub">mm</span></th>
                                    <th scope="col">Min Ø<span class="th-sub">mm</span></th>
                                </tr>
                            </thead>
                            <tbody id="tolc-tbody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="tolc__block">
                    <h3 class="tolc__block-title">
                        <span class="ico" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 13h2v8H3zm4-6h2v14H7zm4 3h2v11h-2zm4-6h2v17h-2zm4 9h2v8h-2z"/></svg></span>
                        Tolerans Bandı Karşılaştırması <small id="tolc-dia-label"></small>
                    </h3>
                    <div class="tolc__chart" id="tolc-chart" role="img" aria-label="Tolerans bantları grafiği"></div>
                </div>

                <div class="tolc__block" id="tolc-fit-block" hidden>
                    <h3 class="tolc__block-title">
                        <span class="ico" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M17 7h-4v2h4a3 3 0 010 6h-4v2h4a5 5 0 000-10zm-6 8H7a3 3 0 010-6h4V7H7a5 5 0 000 10h4v-2zm-3-4h8v2H8z"/></svg></span>
                        Geçme (Fit) Analizi <small id="tolc-fit-count"></small>
                    </h3>
                    <div class="tolc__cards" id="tolc-cards"></div>
                </div>

                <div class="tolc__disclaimer" role="note">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
                    <div class="tolc__disclaimer-body">
                        <strong>Sorumluluk Reddi Beyanı</strong>
                        Bu araç ISO 286-1 standardına dayalı yaklaşık değerler üretir ve yalnızca <strong>ön bilgilendirme</strong> amaçlıdır; resmi bir mühendislik dökümanı veya teknik onay yerine geçmez. Yuvarlamadan kaynaklı ±1 µm sapma olabilir. Tasarım, imalat ve montaj kararlarınızda yetkili bir mühendisin onayını ve güncel resmi tolerans tablolarını esas alınız. Flutecnica, sonuçların doğruluğu, eksiksizliği veya kullanımından doğabilecek doğrudan/dolaylı zararlardan sorumlu tutulamaz.
                    </div>
                </div>
            </div>
        </div>

        <p class="tolc__note">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
            <span>Değerler ISO 286-1'e göre hesaplanır ve <strong>ön bilgilendirme amaçlıdır</strong>. Nihai imalat için resmi tolerans tablolarını esas alınız.</span>
        </p>

        {{-- Statik, taranabilir içerik (JS'siz de okunur — SEO/GEO) --}}
        @php
            $tolClasses = [
                ['k' => 'f7 · f8',        'tip' => 'mil',   'd' => 'Hidrolik silindir piston millerinde keçe temasında yaygın boşluklu geçme toleransı.'],
                ['k' => 'h7 · h8',        'tip' => 'mil',   'd' => 'Lineer kılavuz ve hassas montajlarda referans mil toleransı.'],
                ['k' => 'g6',             'tip' => 'mil',   'd' => 'Küçük boşluklu, kayar geçmeler.'],
                ['k' => 'k6 · m6 · n6',   'tip' => 'mil',   'd' => 'Geçiş ve hafif sıkı geçmeler; rulman oturma yüzeyleri.'],
                ['k' => 'H7 · H8',        'tip' => 'delik', 'd' => 'En yaygın delik referans toleransları; honlanmış hidrolik silindir borularında H8 standarttır.'],
            ];
            $tolFits = [
                ['H11,d11', 'H11/d11', 'Bol boşluklu',  'bos',  'Kaba bağlantı, geniş toleranslı montaj, ısınan parçalar'],
                ['H9,d9',   'H9/d9',   'Boşluklu',      'bos',  'Serbest dönen miller, düşük hız yataklama'],
                ['H8,f7',   'H8/f7',   'Boşluklu',      'bos',  'Genel kayar geçme; hidrolik piston mili–keçe'],
                ['H7,g6',   'H7/g6',   'Az boşluklu',   'bos',  'Hassas kayar, iyi merkezleme, sık sök-tak'],
                ['H7,h6',   'H7/h6',   'Sıfıra yakın',  'bos',  'El ile geçen hassas konumlama, kılavuz'],
                ['H7,k6',   'H7/k6',   'Geçiş',         'gec',  'Kama/pim ile sabit; rulman iç bilezik (dönen yük)'],
                ['H7,n6',   'H7/n6',   'Sıkı geçiş',    'siki', 'Pres/tokmak ile montaj; nadiren sökülür'],
                ['H7,p6',   'H7/p6',   'Sıkı (pres)',   'siki', 'Kalıcı montaj; pres kuvveti / ısıl büzdürme'],
            ];
        @endphp
        <div class="tolc__seo reveal">
            <div class="tolc__guide">
                <p class="section-tag">Hızlı Rehber</p>
                <h3 class="tolc__guide-title">ISO tolerans sınıfları ve geçme türleri</h3>
                <p class="tolc__guide-answer"><strong>Kısa cevap:</strong> ISO 286'da küçük harf (f, g, h, k, m, n, p) <strong>mil</strong>, büyük harf (F, G, H, K, M, N, P) <strong>delik</strong> toleransıdır; harf sapmanın konumunu, rakam (IT derecesi) tolerans genişliğini belirler. Bu araç <strong>üst sapma (ES/es)</strong>, <strong>alt sapma (EI/ei)</strong>, <strong>tolerans genişliği (IT)</strong> ve <strong>maksimum/minimum ölçüleri</strong> hesaplar; mil–delik çiftlerinde <strong>geçme türünü</strong> belirler.</p>

                <div class="tolc__guide-grid">
                    <div class="tolc__guide-col">
                        <h4 class="tolc__guide-h">Sık kullanılan tolerans sınıfları</h4>
                        <ul class="tolc__classes">
                            @foreach($tolClasses as $tc)
                            <li>
                                <div class="tolc__cls-top">
                                    <span class="tolc__cls-k">{{ $tc['k'] }}</span>
                                    <span class="tolc__tag {{ $tc['tip'] === 'mil' ? 'tolc__tag--mil' : 'tolc__tag--delik' }}">{{ $tc['tip'] === 'mil' ? 'Mil' : 'Delik' }}</span>
                                </div>
                                <p>{{ $tc['d'] }}</p>
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="tolc__guide-col">
                        <h4 class="tolc__guide-h">Tercih edilen geçmeler (delik esaslı — H)</h4>
                        <div class="tolc__lib-table-wrap" role="region" aria-label="Tercih edilen geçmeler" tabindex="0">
                            <table class="tolc__lib-table">
                                <caption class="sr-only">ISO delik esaslı tercih edilen geçmeler ve tipik kullanımları</caption>
                                <thead><tr><th scope="col">Geçme</th><th scope="col">Tür</th><th scope="col">Tipik kullanım</th><th scope="col"><span class="sr-only">İşlem</span></th></tr></thead>
                                <tbody>
                                    @foreach($tolFits as $lib)
                                    <tr>
                                        <td data-label="Geçme"><span class="tolc__lib-pair">{{ $lib[1] }}</span></td>
                                        <td data-label="Tür"><span class="tolc__fitpill tolc__fitpill--{{ $lib[3] }}">{{ $lib[2] }}</span></td>
                                        <td data-label="Kullanım">{{ $lib[4] }}</td>
                                        <td class="tolc__lib-act"><button type="button" class="tolc__lib-apply" data-fit="{{ $lib[0] }}" aria-label="{{ $lib[1] }} geçmesini araçta uygula">Araçta dene</button></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <p class="tolc__seo-note"><strong>İpucu:</strong> Geçme analizi için en az bir mil (küçük harf) ve bir delik (büyük harf) sınıfı ekleyin. Sonuç bağlantısını kopyalayıp ekibinizle paylaşabilirsiniz.</p>
            </div>
        </div>
    </div>
</section>

{{-- ══ SSS (görünür içerik = FAQPage şeması) ══ --}}
<section class="faq" id="sss" aria-labelledby="faq-heading">
    <div class="container">
        <div class="faq__layout">
            <aside class="faq__aside reveal">
                <p class="section-tag">Sık Sorulan Sorular</p>
                <h2 class="section-title" id="faq-heading">Teknik <span class="yel">Sorular</span> &amp; Yanıtlar</h2>
                <p class="section-lead">Mil, boru, tolerans ve teslimat hakkında en çok sorulan soruların kısa ve net yanıtları.</p>
                <div class="faq__help">
                    <p class="faq__help-title">Aradığınız yanıt burada yok mu?</p>
                    <p class="faq__help-text">Teknik ekibimiz ölçü, tolerans ve malzeme seçiminde yardımcı olur.</p>
                    <div class="faq__help-acts">
                        <a href="{{ url('/iletisim') }}" class="fl-btn-primary">Soru Sorun {!! $icArrow !!}</a>
                        <a href="tel:+902244136161" class="faq__help-tel">{!! $icPhone !!}+90 224 413 61 61</a>
                    </div>
                </div>
            </aside>
            <div class="faq__list reveal">
                @foreach($faqs as $fi => $f)
                <details class="faq__item" id="{{ $f['id'] }}" @if($fi === 0) open @endif>
                    <summary class="faq__q"><span class="faq__num" aria-hidden="true">{{ str_pad($fi + 1, 2, '0', STR_PAD_LEFT) }}</span><h3>{{ $f['q'] }}</h3><span class="faq__icon" aria-hidden="true"></span></summary>
                    <div class="faq__a"><p>{{ $f['a'] }}</p></div>
                </details>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ══ HİZMET NOKTALARI & KONUM ══ --}}
<section class="vid-map" id="konum" aria-labelledby="map-heading">
    <div class="container">
        <h2 class="sr-only" id="map-heading">Hizmet noktalarımız ve konumumuz</h2>
        <div class="vid-map__grid">
            <div class="reveal">@include('layouts.partials.turkey-map')</div>
            <div class="vid-map__map reveal">
                <div class="vid-map__map-inner">
                    <iframe src="https://www.google.com/maps?q=40.203601503426526,28.950909485942034&z=14&output=embed"
                            width="600" height="320" loading="lazy" allowfullscreen
                            referrerpolicy="no-referrer-when-downgrade" title="Flutecnica Bursa merkez konumu — Google Haritalar"></iframe>
                </div>
                <div class="vid-map__map-footer">
                    <div class="vid-map__map-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 2a7 7 0 00-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 00-7-7zm0 9.5a2.5 2.5 0 110-5 2.5 2.5 0 010 5z"/></svg></div>
                    <address class="vid-map__map-addr">
                        <p class="vid-map__map-name">Flutecnica Akışkan Teknolojileri — Bursa Merkez</p>
                        <p class="vid-map__map-detail">
                            Üçevler K.S.S., 26. Sk. No:83, Nilüfer / Bursa, Türkiye<br>
                            <a href="tel:+902244136161">+90 224 413 61 61</a> ·
                            <a href="mailto:info@flutecnica.com">info@flutecnica.com</a> ·
                            <a href="https://www.google.com/maps/dir/?api=1&destination=40.203601503426526,28.950909485942034" target="_blank" rel="noopener">Yol tarifi al<span class="sr-only"> (yeni sekmede açılır)</span> →</a>
                        </p>
                    </address>
                </div>
            </div>
        </div>
    </div>
</section>

@if(isset($about) && is_object($about))
<section class="about" id="hakkimizda" aria-labelledby="about-heading">
    <div class="container">
        <div class="row align-items-center g-4 g-lg-5">
            <div class="col-lg-6">
                <div class="about__img-wrap reveal">
                    @if(!empty($about->image))
                    <img src="{{ asset('uploads/content/' . $about->image) }}" alt="Flutecnica Bursa tesisi ve krom kaplı mil stok alanı" loading="lazy" decoding="async" width="600" height="420">
                    @endif
                </div>
            </div>
            <div class="col-lg-6">
                <div class="about__body reveal">
                    <p class="section-tag">Hakkımızda</p>
                    <h2 class="section-title" id="about-heading" style="margin-bottom:14px">{{ $about->name ?? 'Flutecnica Hakkında' }}</h2>
                    <p>{{ $about->short ?? '' }}</p>
                    <a href="{{ !empty($about->slug) ? route('content', $about->slug) : url('/hakkimizda') }}" class="fl-btn-outline">Hakkımızda devamını oku</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endif

{{-- ══ CTA + VİDEO ══ --}}
<section class="cta-vid" aria-labelledby="cta-heading">
    <div class="container">
        <div class="cta-vid__grid">
            <div class="cta-vid__video reveal">
                <video id="why-video" loop muted playsinline preload="none" poster="{{ asset('assets/video.jpg') }}" aria-label="Flutecnica üretim ve sevkiyat süreci videosu">
                    <source data-src="{{ asset('assets/uretim1.mp4') }}" type="video/mp4">
                </video>
                <button type="button" class="media-toggle" id="why-video-toggle" aria-pressed="false" hidden>{!! $icPause !!}<span>Videoyu durdur</span></button>
            </div>
            <div class="cta-vid__box reveal">
                <p class="cta-vid__eyebrow">Projeleriniz İçin Hazırız</p>
                <h2 class="cta-vid__title" id="cta-heading">Hızlı <span class="yel">Teklif</span> Alın</h2>
                <p class="cta-vid__desc">Bursa ve İstanbul şubelerimizden hızlı teslimat. Çap, boy ve adet bilgisini iletin; uzman ekibimiz 7/24 yanınızda.</p>
                <a href="#olcu-sorgula" class="cta-vid__btn-main">Teklif Formuna Git {!! $icArrow !!}</a>
                <div class="cta-vid__phones">
                    <a href="tel:+905414594354" class="cta-vid__phone">{!! $icPhone !!}0541 459 43 54</a>
                    <a href="tel:+905059344354" class="cta-vid__phone">{!! $icPhone !!}0505 934 43 54</a>
                </div>
            </div>
        </div>
    </div>
</section>

</div>{{-- /#main-content --}}

{{-- ══ SAYFA GENELİ: reveal, video, ticker, PDF dialog, kur ══ --}}
<script>
(function () {
'use strict';
var RM   = window.matchMedia('(prefers-reduced-motion: reduce)');
var FINE = window.matchMedia('(hover: hover) and (pointer: fine)');
var conn = navigator.connection || {};
var SAVE_DATA = !!conn.saveData || /(^|-)2g$/.test(conn.effectiveType || '');

var PAUSE_SVG = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>';
var PLAY_SVG  = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>';

/* Oynat/durdur düğmesi durumunu ayarla */
function setToggle(btn, playing, labelPlay, labelPause) {
    if (!btn) return;
    btn.setAttribute('aria-pressed', playing ? 'false' : 'true');
    var span = btn.querySelector('span');
    var lbl = playing ? labelPause : labelPlay;
    if (span) { btn.innerHTML = (playing ? PAUSE_SVG : PLAY_SVG) + '<span>' + lbl + '</span>'; }
    else { btn.innerHTML = playing ? PAUSE_SVG : PLAY_SVG; btn.setAttribute('aria-label', lbl); }
}

/* Veri kaynağını yükleyip videoyu başlat */
function startVideo(v, btn) {
    if (!v) return;
    v.querySelectorAll('source[data-src]').forEach(function (s) { s.src = s.dataset.src; s.removeAttribute('data-src'); });
    v.load();
    v.addEventListener('playing', function () { v.classList.add('is-playing'); setToggle(btn, true, 'Videoyu oynat', 'Videoyu durdur'); if (btn) btn.hidden = false; });
    v.addEventListener('pause',   function () { setToggle(btn, false, 'Videoyu oynat', 'Videoyu durdur'); });
    var p = v.play(); if (p && p.catch) p.catch(function () {});
    if (btn) btn.addEventListener('click', function () { v.paused ? v.play() : v.pause(); });
}

document.addEventListener('DOMContentLoaded', function () {

    /* ── Reveal ── */
    var revealEls = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && !RM.matches) {
        var ro = new IntersectionObserver(function (ents) {
            ents.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('is-in'); ro.unobserve(e.target); } });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.05 });
        revealEls.forEach(function (el) { ro.observe(el); });
    } else {
        revealEls.forEach(function (el) { el.classList.add('is-in'); });
    }

    /* ── Hero video: masaüstü, hareket azaltma kapalı ve veri tasarrufu yoksa ── */
    var hv = document.getElementById('hero-video');
    var canAutoplay = !RM.matches && !SAVE_DATA && window.innerWidth >= 768;
    if (hv && canAutoplay) {
        var go = function () { startVideo(hv, document.getElementById('hero-video-toggle')); };
        ('requestIdleCallback' in window) ? requestIdleCallback(go, { timeout: 1500 }) : setTimeout(go, 600);
    }

    /* ── Alt video: görünür olunca, ekrandan çıkınca duraklat ── */
    var wv = document.getElementById('why-video'), wvBtn = document.getElementById('why-video-toggle');
    if (wv && 'IntersectionObserver' in window) {
        if (!canAutoplay) {
            /* Otomatik oynatma yok (mobil / hareket azaltma / veri tasarrufu): kullanıcı başlatır */
            if (wvBtn) {
                wvBtn.hidden = false;
                setToggle(wvBtn, false, 'Videoyu oynat', 'Videoyu durdur');
                wvBtn.addEventListener('click', function first() {
                    wvBtn.removeEventListener('click', first);
                    startVideo(wv, wvBtn);
                });
            }
        } else {
            var started = false;
            new IntersectionObserver(function (ents) {
                ents.forEach(function (e) {
                    if (e.isIntersecting && !started) { started = true; startVideo(wv, wvBtn); }
                    else if (!e.isIntersecting && started && !wv.paused) { wv.pause(); }
                });
            }, { rootMargin: '150px' }).observe(wv);
        }
    }

    /* ── Kayan şerit duraklat ── */
    var ticker = document.getElementById('hero-ticker'), tBtn = document.getElementById('ticker-toggle');
    if (ticker && tBtn) {
        tBtn.addEventListener('click', function () {
            var paused = ticker.classList.toggle('is-paused');
            setToggle(tBtn, !paused, 'Kayan şeridi başlat', 'Kayan şeridi durdur');
        });
    }

    /* ── Kart spot ışığı: yalnız hassas işaretçide, rAF ile ── */
    if (FINE.matches && !RM.matches) {
        document.querySelectorAll('.prod-card,.eng__tool').forEach(function (c) {
            var raf = 0;
            c.addEventListener('pointermove', function (e) {
                if (raf) return;
                raf = requestAnimationFrame(function () {
                    var r = c.getBoundingClientRect();
                    c.style.setProperty('--mx', ((e.clientX - r.left) / r.width * 100).toFixed(1) + '%');
                    c.style.setProperty('--my', ((e.clientY - r.top) / r.height * 100).toFixed(1) + '%');
                    raf = 0;
                });
            });
        });
    }

    /* ── PDF önizleme (native dialog; mobil/eski tarayıcıda yeni sekme) ── */
    var dlg = document.getElementById('pdfMO'), frame = document.getElementById('pdfMF'),
        dTitle = document.getElementById('pdfMTitle'), dDl = document.getElementById('pdfMDl'), lastTrigger = null;
    var canDialog = dlg && typeof dlg.showModal === 'function';
    document.addEventListener('click', function (e) {
        var a = e.target.closest('.js-pdf-preview');
        if (!a) return;
        var mobile = window.matchMedia('(max-width: 768px)').matches || /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
        if (mobile || !canDialog) return; /* varsayılan: yeni sekmede aç */
        e.preventDefault();
        lastTrigger = a;
        frame.src = a.getAttribute('href');
        dTitle.textContent = a.dataset.title || 'PDF Önizleme';
        dDl.setAttribute('href', a.dataset.download || a.getAttribute('href'));
        dlg.showModal();
    });
    function closeDlg() { if (dlg && dlg.open) dlg.close(); }
    if (canDialog) {
        document.getElementById('pdfMClose').addEventListener('click', closeDlg);
        dlg.addEventListener('click', function (e) { if (e.target === dlg) closeDlg(); }); /* arka plana tıkla */
        dlg.addEventListener('close', function () {
            frame.src = 'about:blank';
            if (lastTrigger) { try { lastTrigger.focus({ preventScroll: true }); } catch (x) { lastTrigger.focus(); } }
        });
    }

    /* ── TCMB kurları: yalnız sekme görünürken yenile ── */
    var nf = new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 4, maximumFractionDigits: 4 });
    function setKur(code, val) {
        document.querySelectorAll('[data-kur="' + code + '"]').forEach(function (el) { el.textContent = val; el.classList.remove('kur-loading'); });
    }
    function fmt(v) { var n = parseFloat(v); return isFinite(n) && n > 0 ? nf.format(n) + ' ₺' : '—'; }
    var KEYS = ['usd-fb', 'usd-fs', 'usd-eb', 'usd-es', 'eur-fb', 'eur-fs', 'eur-eb', 'eur-es'];
    function loadKur() {
        if (document.hidden) return;
        fetch('/api/kur', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(function (d) {
                if (!d || !d.kurlar) throw new Error('empty');
                [['usd', d.kurlar.USD], ['eur', d.kurlar.EUR]].forEach(function (x) {
                    var k = x[0], o = x[1] || {};
                    setKur(k + '-fb', fmt(o.forexBuying)); setKur(k + '-fs', fmt(o.forexSelling));
                    setKur(k + '-eb', fmt(o.banknoteBuying)); setKur(k + '-es', fmt(o.banknoteSelling));
                });
            })
            .catch(function () { KEYS.forEach(function (k) { setKur(k, '—'); }); });
    }
    loadKur();
    setInterval(loadKur, 5 * 60 * 1000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) loadKur(); });
});
})();

/* Ürün kartı: dokunmatik / klavye için "Özellikler" düğmesi */
(function () {
    var btns = document.querySelectorAll('.fl-home .prod-spec__btn');
    if (!btns.length) return;
    function closeAll(except) {
        btns.forEach(function (b) {
            if (b === except) return;
            b.setAttribute('aria-expanded', 'false');
            var c = b.closest('.prod-card'); if (c) c.classList.remove('is-spec');
        });
    }
    btns.forEach(function (b) {
        b.addEventListener('click', function (e) {
            e.preventDefault(); e.stopPropagation();
            var card = b.closest('.prod-card');
            var open = b.getAttribute('aria-expanded') !== 'true';
            closeAll(b);
            b.setAttribute('aria-expanded', open ? 'true' : 'false');
            card.classList.toggle('is-spec', open);
        });
    });
    document.addEventListener('click', function (e) { if (!e.target.closest('.prod-spec__btn')) closeAll(null); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAll(null); });
})();
</script>

{{-- ══ ISO TOLERANS KARŞILAŞTIRMA — HESAPLAMA MOTORU ══ --}}
<script>
(function () {
'use strict';

/* ISO 286-1 — IT tolerans tablosu (µm). Her aralık için IT1..IT18 */
var R = [[0,3],[3,6],[6,10],[10,18],[18,30],[30,50],[50,80],[80,120],[120,180],[180,250],[250,315],[315,400],[400,500]];
var IT = [
  [0.8,1.2,2,3,4,6,10,14,25,40,60,100,140,250,400,600,1000,1400],
  [1,1.5,2.5,4,5,8,12,18,30,48,75,120,180,300,480,750,1200,1800],
  [1,1.5,2.5,4,6,9,15,22,36,58,90,150,220,360,580,900,1500,2200],
  [1.2,2,3,5,8,11,18,27,43,70,110,180,270,430,700,1100,1800,2700],
  [1.5,2.5,4,6,9,13,21,33,52,84,130,210,330,520,840,1300,2100,3300],
  [1.5,2.5,4,7,11,16,25,39,62,100,160,250,390,620,1000,1600,2500,3900],
  [2,3,5,8,13,19,30,46,74,120,190,300,460,740,1200,1900,3000,4600],
  [2.5,4,6,10,15,22,35,54,87,140,220,350,540,870,1400,2200,3500,5400],
  [3.5,5,8,12,18,25,40,63,100,160,250,400,630,1000,1600,2500,4000,6300],
  [4.5,7,10,14,20,29,46,72,115,185,290,460,720,1150,1850,2900,4600,7200],
  [6,8,12,16,23,32,52,81,130,210,320,520,810,1300,2100,3200,5200,8100],
  [7,9,13,18,25,36,57,89,140,230,360,570,890,1400,2300,3600,5700,8900],
  [8,10,15,20,27,40,63,97,155,250,400,630,970,1550,2500,4000,6300,9700]
];
var SHAFT = ['d','e','f','g','h','js','k','m','n','p'];
var HOLE  = ['D','E','F','G','H','JS','K','M','N','P'];
/* Marka uyumlu palet (mor/pembe yok); renk tek bilgi taşıyıcı değil — her yerde metin etiketi var */
var PALETTE = ['#1D426A','#1F7A52','#A89A70','#B23A2E','#0E7490','#11273D','#C2410C','#4D7C0F','#475569','#0F766E','#7A5E0F','#1E3A8A'];
/* Dokunmatik cihazda programatik odak klavyeyi açar ve sayfayı kaydırır → yalnız fare/klavyede odakla */
var FINE = window.matchMedia('(hover: hover) and (pointer: fine)');

function rangeIndex(dia){ for (var i=0;i<R.length;i++){ if (dia>R[i][0] && dia<=R[i][1]) return i; } return -1; }
function geoMean(ri){ var lo=R[ri][0]||1, hi=R[ri][1]; return Math.sqrt(lo*hi); }
function itVal(grade,ri){ if (grade<1||grade>18) return null; return IT[ri][grade-1]; }

/* Mil üst sapma (d–h) */
function esShaft(l,D){
  switch(l){
    case 'd': return Math.round(-16*Math.pow(D,0.44));
    case 'e': return Math.round(-11*Math.pow(D,0.41));
    case 'f': return Math.round(-5.5*Math.pow(D,0.41));
    case 'g': return Math.round(-2.5*Math.pow(D,0.34));
    case 'h': return 0;
  }
  return null;
}
/* Mil alt sapma (k, m, n, p) */
var P_EI = [6,12,15,18,22,26,32,37,43,50,56,62,68];
function eiShaft(l,D,grade,ri){
  switch(l){
    case 'k': return (grade>=4&&grade<=7)?Math.round(0.6*Math.cbrt(D)):0;
    case 'm': return itVal(7,ri)-itVal(6,ri);
    case 'n': return Math.round(5*Math.pow(D,0.34));
    case 'p': return P_EI[ri];
  }
  return null;
}

/* Bir sınıf için {upper, lower} (µm) */
function calc(letter,grade,dia){
  var ri=rangeIndex(dia); if (ri<0) return null;
  var D=geoMean(ri), it=itVal(grade,ri); if (it==null) return null;
  var low=letter.toLowerCase(), isHole=/[A-Z]/.test(letter[0]);
  if (!isHole){
    if (low==='js') return {upper:it/2,lower:-it/2,type:'mil'};
    if (['d','e','f','g','h'].indexOf(low)>=0){ var es=esShaft(low,D); return {upper:es,lower:es-it,type:'mil'}; }
    if (['k','m','n','p'].indexOf(low)>=0){ var ei=eiShaft(low,D,grade,ri); return {upper:ei+it,lower:ei,type:'mil'}; }
  } else {
    if (low==='js') return {upper:it/2,lower:-it/2,type:'delik'};
    if (['d','e','f','g','h'].indexOf(low)>=0){ var EI=-esShaft(low,D); return {upper:EI+it,lower:EI,type:'delik'}; }
    if (['k','m','n','p'].indexOf(low)>=0){
      var eis=eiShaft(low,D,grade,ri), delta=0, ES;
      /* ISO 286-1 Δ kuralı: K, M, N → IT8'e kadar; P → IT7'ye kadar */
      var deltaMax = (low==='p') ? 7 : 8;
      if (grade>=3 && grade<=deltaMax) delta = itVal(grade,ri)-itVal(grade-1,ri);
      if (low==='n' && grade>8) ES = 0;          /* N9 ve üzeri: ES = 0 */
      else ES = -eis + delta;
      return {upper:ES,lower:ES-it,type:'delik'};
    }
  }
  return null;
}

function parse(str){
  var m=String(str||'').trim().replace(/\s+/g,'').match(/^([A-Za-z]{1,2})([0-9]{1,2})$/);
  if (!m) return {err:'Geçersiz biçim. Örnek: f7, H8, js7'};
  var letter=m[1], grade=parseInt(m[2],10), isHole=/[A-Z]/.test(letter[0]);
  if (letter.toLowerCase()==='js') letter=isHole?'JS':'js';
  var set=isHole?HOLE:SHAFT;
  if (set.indexOf(letter)<0) return {err:'"'+m[1]+m[2]+'" desteklenmiyor. Kullanılabilir harfler — mil: d, e, f, g, h, js, k, m, n, p · delik: D, E, F, G, H, JS, K, M, N, P'};
  if (grade<1||grade>18) return {err:'IT derecesi 1–18 aralığında olmalı: "'+m[1]+m[2]+'"'};
  return {letter:letter,grade:grade,raw:letter+grade,isHole:isHole};
}

/* ── Chip durumu ── */
var MAX_CHIPS=12, chips=[];
function colorFor(i){ return PALETTE[i%PALETTE.length]; }
function el(id){ return document.getElementById(id); }
var X_SVG='<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>';

function renderChips(){
  var list=el('tolc-chiplist'); if (!list) return;
  list.innerHTML=chips.map(function(c,i){
    return '<li class="tolc-chip" style="background:'+colorFor(i)+'">'+c.raw+
      '<button type="button" class="tolc-chip__x" aria-label="'+c.raw+' sınıfını kaldır" data-i="'+i+'">'+X_SVG+'</button></li>';
  }).join('');
  var cnt=el('tolc-count'); if (cnt) cnt.textContent=chips.length;
}
function addChip(raw){
  raw=String(raw||'').trim(); if (!raw) return false;
  if (chips.length>=MAX_CHIPS){ showErr('En fazla '+MAX_CHIPS+' tolerans sınıfı karşılaştırılabilir. Birini kaldırıp tekrar deneyin.'); return false; }
  var p=parse(raw); if (p.err){ showErr(p.err); return false; }
  for (var i=0;i<chips.length;i++){ if (chips[i].raw===p.raw){ clearErr(); return false; } }
  chips.push({raw:p.raw}); clearErr(); renderChips(); return true;
}
function removeChip(i){ chips.splice(i,1); renderChips(); }
function showErr(msg){
  var e=el('tolc-err'); if (!e) return;
  e.innerHTML='<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg><span></span>';
  e.querySelector('span').textContent=msg;   /* metin olarak yaz: XSS yok */
  e.hidden=false;
}
function clearErr(){ var e=el('tolc-err'); if (e){ e.hidden=true; e.textContent=''; } }

/* ── Biçim ── */
var nf3=new Intl.NumberFormat('tr-TR',{minimumFractionDigits:3,maximumFractionDigits:3});
function fmtUm(v){ var r=Math.round(v); return (r>0?'+':(r<0?'−':''))+Math.abs(r)+' µm'; }
function fmtMm(v){ return (v>0?'+':(v<0?'−':''))+nf3.format(Math.abs(v/1000))+' mm'; }
function numCls(v){ var r=Math.round(v); return r>0?'pos':(r<0?'neg':'zero'); }
function clamp(v,a,b){ return Math.max(a,Math.min(b,v)); }

/* ── Sıcaklık / genleşme ── */
function tempInfo(dia){
  var tEl=el('tolc-temp'), hEl=el('tolc-mat-hole'), sEl=el('tolc-mat-shaft'); if (!tEl||!hEl||!sEl) return null;
  var T=parseFloat(String(tEl.value).replace(',','.')); if (!isFinite(T)) return null;
  var dT=T-20, aH=parseFloat(hEl.value), aS=parseFloat(sEl.value);
  return {T:T,shift:dia*(aH-aS)*dT/1000}; /* µm: delik − mil genleşme farkı */
}

/* ── Kesit görseli ── */
function xsecSvg(cls,l1,l2,minCl,maxCl){
  var cx=48, cy=48, RH=36, body='';
  function circle(r,fill,stroke,sw,dash){ return '<circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" fill="'+fill+'"'+(stroke?' stroke="'+stroke+'" stroke-width="'+(sw||1.4)+'"':'')+(dash?' stroke-dasharray="'+dash+'"':'')+'/>'; }
  if (cls==='bos'){ var g=clamp(Math.round(Math.abs(minCl)*0.5),4,16); body=circle(RH,'#E3F4EC','#9DB4CE',1.5)+circle(RH-g,'#1D426A'); }
  else if (cls==='siki'){ var o=clamp(Math.round(Math.abs(maxCl)*0.5),4,12); body=circle(RH+o,'#F2C9C2')+circle(RH+o,'none','#B23A2E',1.4,'3 3')+circle(RH,'#1D426A','#0A1828',1.2); }
  else { body=circle(RH,'#FFF6DD','#9DB4CE',1.5)+circle(RH-3,'#1D426A'); }
  function t(x,y,fs,fw,col,s){ return '<text x="'+x+'" y="'+y+'" font-family="JetBrains Mono,monospace" font-size="'+fs+'" font-weight="'+fw+'" fill="'+col+'">'+s+'</text>'; }
  var legend='<rect x="6" y="86" width="9" height="6" rx="1" fill="#1D426A"/>'+t(19,91,8,'600','#3A4A5C','Mil')+
    '<rect x="42" y="86" width="9" height="6" rx="1" fill="'+(cls==='siki'?'#F2C9C2':cls==='gec'?'#FFF6DD':'#E3F4EC')+'" stroke="#9DB4CE" stroke-width="0.6"/>'+t(55,91,8,'600','#3A4A5C','Delik');
  var info=t(100,32,8.5,'600','#3A4A5C',l1[0])+t(100,47,14,'800','#0A1828',l1[1])+t(100,68,8.5,'600','#3A4A5C',l2[0])+t(100,83,14,'800','#0A1828',l2[1]);
  return '<div class="tolc__xsec"><svg viewBox="0 0 200 98" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">'+body+legend+info+'</svg></div>';
}

function render(dia,items){
  var lows=items.map(function(x){return x.r.lower;}).concat([0]);
  var ups=items.map(function(x){return x.r.upper;}).concat([0]);
  var mn=Math.min.apply(null,lows), mx=Math.max.apply(null,ups);
  var span=(mx-mn)||1, pad=span*0.12, lo=mn-pad, hi=mx+pad, RR=(hi-lo)||1;
  function pct(v){ return (v-lo)/RR*100; }
  var diaTxt='Ø'+String(dia).replace('.',',')+' mm';

  /* TABLO */
  el('tolc-tbody').innerHTML=items.map(function(it){
    var tol=it.r.upper-it.r.lower, isMil=it.r.type==='mil';
    return '<tr>'+
      '<th scope="row"><span class="tolc__cell-class"><span class="d" style="background:'+it.color+'" aria-hidden="true"></span>'+it.p.raw+'</span></th>'+
      '<td><span class="tolc__tag '+(isMil?'tolc__tag--mil':'tolc__tag--delik')+'">'+(isMil?'Mil':'Delik')+'</span></td>'+
      '<td><span class="tolc__num '+numCls(it.r.upper)+'">'+fmtUm(it.r.upper)+'</span><span class="tolc__sub-mm">'+fmtMm(it.r.upper)+'</span></td>'+
      '<td><span class="tolc__num '+numCls(it.r.lower)+'">'+fmtUm(it.r.lower)+'</span><span class="tolc__sub-mm">'+fmtMm(it.r.lower)+'</span></td>'+
      '<td class="tolc__col-tol"><span class="tolc__num">'+Math.round(tol)+' µm</span><span class="tolc__sub-mm">'+nf3.format(tol/1000)+' mm</span></td>'+
      '<td><span class="tolc__num">'+nf3.format(dia+it.r.upper/1000)+'</span></td>'+
      '<td><span class="tolc__num">'+nf3.format(dia+it.r.lower/1000)+'</span></td>'+
    '</tr>';
  }).join('');
  el('tolc-dia-label').textContent='— '+diaTxt;
  el('tolc-dia-label2').textContent='— '+diaTxt;

  /* GRAFİK (transform ile animasyon — layout kaymasız) */
  var rows=items.map(function(it){
    var left=pct(it.r.lower), w=Math.max(pct(it.r.upper)-left,0.6), isMil=it.r.type==='mil';
    return '<div class="tolc-row">'+
      '<div class="tolc-row__label">'+
        '<span class="tolc-row__dot" style="background:'+it.color+'" aria-hidden="true"></span>'+
        '<span class="tolc-row__name">'+it.p.raw+'</span>'+
        '<span class="tolc-row__type">'+(isMil?'MİL':'DELİK')+'</span>'+
        '<span class="tolc-row__vals">üst '+fmtUm(it.r.upper)+' · alt '+fmtUm(it.r.lower)+'</span>'+
      '</div>'+
      '<div class="tolc-row__track"><div class="tolc-bar'+(w>14?' show-lbl':'')+'" style="left:'+left.toFixed(2)+'%;width:'+w.toFixed(2)+'%;background:'+it.color+'"><span class="tolc-bar__lbl">'+Math.round(it.r.upper-it.r.lower)+' µm</span></div></div>'+
    '</div>';
  }).join('');
  var zeroPct=clamp(pct(0),0,100);
  var chart=el('tolc-chart');
  chart.innerHTML='<div class="tolc-plot">'+
      '<div class="tolc-zero" style="left:'+zeroPct.toFixed(2)+'%" aria-hidden="true"><span class="tolc-zero__tag">0 · Nominal</span></div>'+
      rows+
      '<div class="tolc-axis" aria-hidden="true"><span class="ax-lo">'+fmtUm(lo)+'</span><span class="ax-hi">'+fmtUm(hi)+'</span></div>'+
    '</div>';
  chart.setAttribute('aria-label', diaTxt+' için tolerans bantları: '+items.map(function(it){ return it.p.raw+' '+fmtUm(it.r.lower)+' ile '+fmtUm(it.r.upper)+' arası'; }).join('; '));
  requestAnimationFrame(function(){ requestAnimationFrame(function(){
    chart.querySelectorAll('.tolc-bar').forEach(function(b){ b.classList.add('is-drawn'); });
  }); });

  /* GEÇME ANALİZİ */
  var fits='', fitN=0, ti=tempInfo(dia);
  for (var a=0;a<items.length;a++){ for (var b=a+1;b<items.length;b++){
    var x=items[a], y=items[b]; if (x.r.type===y.r.type) continue;
    var hole=(x.r.type==='delik')?x:y, shaft=(x.r.type==='mil')?x:y;
    var maxCl=hole.r.upper-shaft.r.lower, minCl=hole.r.lower-shaft.r.upper, cls, badge, l1, l2;
    if (minCl>=0){ cls='bos'; badge='Boşluklu'; l1=['Max boşluk',fmtUm(maxCl)]; l2=['Min boşluk',fmtUm(minCl)]; }
    else if (maxCl<=0){ cls='siki'; badge='Sıkı (Pres)'; l1=['Max sıkılık',fmtUm(-minCl)]; l2=['Min sıkılık',fmtUm(-maxCl)]; }
    else { cls='gec'; badge='Geçiş'; l1=['Max boşluk',fmtUm(maxCl)]; l2=['Max sıkılık',fmtUm(-minCl)]; }
    fitN++;
    var tempHtml='';
    if (ti && Math.abs(ti.shift)>=0.5){
      var nMin=minCl+ti.shift, nMax=maxCl+ti.shift, te1, te2, st;
      if (nMin>=0){ st='Boşluklu'; te1=['Çalışma max boşluk',fmtUm(nMax)]; te2=['Çalışma min boşluk',fmtUm(nMin)]; }
      else if (nMax<=0){ st='Sıkı'; te1=['Çalışma max sıkılık',fmtUm(-nMin)]; te2=['Çalışma min sıkılık',fmtUm(-nMax)]; }
      else { st='Geçiş'; te1=['Çalışma max boşluk',fmtUm(nMax)]; te2=['Çalışma max sıkılık',fmtUm(-nMin)]; }
      tempHtml='<div class="tolc__fit-temp">'+
        '<div class="tolc__fit-line"><span>'+ti.T+' °C’de boşluk değişimi</span><b>'+fmtUm(ti.shift)+'</b></div>'+
        '<div class="tolc__fit-line"><span>'+te1[0]+' ('+st+')</span><b>'+te1[1]+'</b></div>'+
        '<div class="tolc__fit-line"><span>'+te2[0]+'</span><b>'+te2[1]+'</b></div></div>';
    }
    fits+='<article class="tolc__fit tolc__fit--'+cls+'">'+
      '<div class="tolc__fit-head"><h4 class="tolc__fit-pair">'+hole.p.raw+' / '+shaft.p.raw+'</h4><span class="tolc__fit-badge">'+badge+'</span></div>'+
      xsecSvg(cls,l1,l2,minCl,maxCl)+
      '<div class="tolc__fit-rows"><div class="tolc__fit-line"><span>'+l1[0]+'</span><b>'+l1[1]+'</b></div><div class="tolc__fit-line"><span>'+l2[0]+'</span><b>'+l2[1]+'</b></div></div>'+
      tempHtml+'</article>';
  }}
  var fitBlock=el('tolc-fit-block'), fc=el('tolc-fit-count');
  if (fits){
    el('tolc-cards').innerHTML=fits; fc.textContent=fitN+' çift';
  } else {
    var hasMil=items.some(function(x){return x.r.type==='mil';}), hasHole=items.some(function(x){return x.r.type==='delik';});
    var suggest=(!hasMil&&hasHole)?'h7':'H7';
    var msg=(!hasMil&&hasHole)
      ? 'Eklenen sınıfların tümü <span class="tag-delik">delik</span>. Geçme analizi için en az bir <span class="tag-mil">mil</span> (küçük harf, örn. f7/h7) ekleyin.'
      : (hasMil&&!hasHole)
        ? 'Eklenen sınıfların tümü <span class="tag-mil">mil</span>. Geçme analizi için en az bir <span class="tag-delik">delik</span> (büyük harf, örn. H7/H8) ekleyin.'
        : 'Geçme analizi için en az bir <span class="tag-mil">mil</span> ve bir <span class="tag-delik">delik</span> sınıfı ekleyin.';
    el('tolc-cards').innerHTML='<div class="tolc__empty"><p>'+msg+'</p><button type="button" class="tolc__empty-btn" data-suggest="'+suggest+'"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>'+suggest+' ekle ve göster</button></div>';
    fc.textContent='';
  }
  fitBlock.hidden=false;
}

/* ── Çalıştır ── */
function run(){
  var resEl=el('tolc-results'); clearErr();
  var dia=parseFloat(String(el('tolc-dia').value||'').replace(',','.'));
  if (isNaN(dia)||dia<=0||dia>500){ showErr('Nominal çap 1–500 mm aralığında olmalıdır. Örnek: 50'); resEl.hidden=true; el('tolc-dia').setAttribute('aria-invalid','true'); return; }
  el('tolc-dia').removeAttribute('aria-invalid');
  if (chips.length<1){ showErr('Karşılaştırmak için en az 1 tolerans sınıfı ekleyin (örn. f7).'); resEl.hidden=true; return; }
  var items=[];
  for (var i=0;i<chips.length;i++){
    var p=parse(chips[i].raw); if (p.err){ showErr(chips[i].raw+': '+p.err); resEl.hidden=true; return; }
    var r=calc(p.letter,p.grade,dia); if (!r){ showErr(chips[i].raw+' bu çap için hesaplanamadı.'); resEl.hidden=true; return; }
    items.push({p:p,r:r,color:colorFor(i)});
  }
  resEl.setAttribute('aria-busy','true'); resEl.hidden=false;
  render(dia,items);
  resEl.setAttribute('aria-busy','false');
}
/* Kullanıcı bir şablon/geçme düğmesine bastığında sonuçlar ekranda değilse oraya kaydırılır
   (yazı alanına odaklanmadığı için mobilde klavye açılmaz). Zorlama yoksa yalnız "Sonuçlara git" bağlantısı gösterilir. */
function scrollToResults(force){
  var rb=el('tolc-results'), j=el('tolc-jump'); if (!rb) return;
  if (rb.hidden){ if (j) j.hidden=true; return; }
  var r=rb.getBoundingClientRect(), visible=(r.top < window.innerHeight*0.85 && r.bottom > 0);
  if (force && !visible){
    var rm=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    rb.scrollIntoView({block:'start',behavior:rm?'auto':'smooth'});
    if (j) j.hidden=true; return;
  }
  if (j) j.hidden=visible;
}

/* ── Paylaş / URL (derin bağlantı) ── */
function buildShareUrl(){
  return location.origin+location.pathname+'?d='+encodeURIComponent(el('tolc-dia').value)+'&t='+encodeURIComponent(chips.map(function(c){return c.raw;}).join(','))+'#tolerans-karsilastirma';
}
function applyUrlParams(){
  try{
    var q=new URLSearchParams(location.search), d=q.get('d'), t=q.get('t');
    if (t){ chips=[]; t.split(',').slice(0,MAX_CHIPS).forEach(addChip); if (d && !isNaN(parseFloat(d))) el('tolc-dia').value=d; return true; }
  }catch(e){}
  return false;
}
function setChips(list){ chips=[]; list.split(',').forEach(addChip); run(); }

/* ── INIT ── */
document.addEventListener('DOMContentLoaded',function(){
  var box=el('tolc-chipbox'), input=el('tolc-chipinput'), go=el('tolc-go'); if (!box||!input||!go) return;
  function focusInput(){ if (FINE.matches) input.focus(); }

  if (!applyUrlParams()){ ['f7','h7','H8'].forEach(addChip); }
  renderChips();

  box.addEventListener('click',function(e){
    var btn=e.target.closest('.tolc-chip__x');
    if (btn){ removeChip(parseInt(btn.getAttribute('data-i'),10)); focusInput(); return; }
    if (e.target===box) input.focus();   /* kullanıcı yazmak için kutuya dokundu */
  });
  box.addEventListener('focusin',function(){ box.classList.add('is-focus'); });
  box.addEventListener('focusout',function(){ box.classList.remove('is-focus'); });

  input.addEventListener('keydown',function(e){
    if (e.key==='Enter'||e.key===','||e.key===' '){
      e.preventDefault();
      if (input.value.trim()){ if (addChip(input.value)) input.value=''; } else run();
    } else if (e.key==='Backspace' && input.value==='' && chips.length){ removeChip(chips.length-1); }
  });
  input.addEventListener('blur',function(){ if (input.value.trim() && addChip(input.value)) input.value=''; });

  document.querySelectorAll('.tolc__quick-btn').forEach(function(b){ b.addEventListener('click',function(){ addChip(this.dataset.add); focusInput(); }); });
  document.querySelectorAll('.tolc__dia-preset').forEach(function(b){ b.addEventListener('click',function(){ el('tolc-dia').value=this.dataset.dia; run(); }); });
  document.querySelectorAll('.tolc__preset').forEach(function(b){ b.addEventListener('click',function(){ setChips(this.dataset.preset); scrollToResults(true); }); });
  document.querySelectorAll('.tolc__lib-apply').forEach(function(b){
    b.addEventListener('click',function(){
      var self=this, old=self.textContent;
      setChips(this.dataset.fit); scrollToResults(true);
      self.textContent='Uygulandı ✓';
      setTimeout(function(){ self.textContent=old; },1800);
    });
  });

  var goalOut=el('tolc-goal-out');
  document.querySelectorAll('.tolc__goal-btn').forEach(function(b){
    b.addEventListener('click',function(){
      document.querySelectorAll('.tolc__goal-btn').forEach(function(x){ x.setAttribute('aria-pressed','false'); });
      this.setAttribute('aria-pressed','true');
      setChips(this.dataset.fit);
      goalOut.innerHTML='<b></b> — <span></span>';
      goalOut.querySelector('b').textContent=this.dataset.fit.replace(',',' / ');
      goalOut.querySelector('span').textContent=this.dataset.desc;
      scrollToResults(true);
    });
  });

  go.addEventListener('click',function(){ run(); scrollToResults(true); });
  el('tolc-clear').addEventListener('click',function(){
    chips=[]; renderChips(); el('tolc-results').hidden=true; clearErr(); goalOut.textContent='';
    document.querySelectorAll('.tolc__goal-btn').forEach(function(x){ x.setAttribute('aria-pressed','false'); });
    el('tolc-jump').hidden=true;
    focusInput();
  });
  el('tolc-dia').addEventListener('keydown',function(e){ if (e.key==='Enter'){ e.preventDefault(); run(); } });
  el('tolc-cards').addEventListener('click',function(e){
    var sb=e.target.closest('.tolc__empty-btn'); if (sb && addChip(sb.dataset.suggest)) run();
  });

  /* "Bu Ölçüde Teklif Al": çap + tolerans sınıfları teklif formuna kalem olarak aktarılır */
  var quote=el('tolc-quote');
  if (quote) quote.addEventListener('click',function(e){
    if (typeof window.flRqPrefill!=='function') return;   /* form yoksa normal bağlantı gibi davran */
    e.preventDefault();
    var dia=parseFloat(String(el('tolc-dia').value||'').replace(',','.'));
    window.flRqPrefill({
      cap: isFinite(dia) && dia>0 ? dia : null,
      not: 'ISO tolerans: '+chips.map(function(c){return c.raw;}).join(', ')
    });
  });

  var shareBtn=el('tolc-share');
  shareBtn.addEventListener('click',function(){
    var url=buildShareUrl(), self=this, lbl=self.querySelector('span');
    function done(ok){
      self.classList.add('is-ok'); lbl.textContent=ok?'Kopyalandı!':'Kopyalanamadı — adres çubuğunu kullanın';
      if (!ok) try{ history.replaceState(null,'',url); }catch(e){}
      setTimeout(function(){ self.classList.remove('is-ok'); lbl.textContent='Bağlantıyı Kopyala'; },2400);
    }
    if (navigator.clipboard && navigator.clipboard.writeText){ navigator.clipboard.writeText(url).then(function(){done(true);},function(){done(false);}); }
    else { done(false); }
  });
  el('tolc-print').addEventListener('click',function(){ window.print(); });

  ['tolc-temp','tolc-mat-hole','tolc-mat-shaft'].forEach(function(id){
    var e2=el(id); if (!e2) return;
    e2.addEventListener(id==='tolc-temp'?'input':'change',function(){ if (!el('tolc-results').hidden) run(); });
  });

  run();
  if (location.search.indexOf('t=')>-1){
    var sec=el('tolerans-karsilastirma'); if (sec) setTimeout(function(){ sec.scrollIntoView(); },300);
  }
});
})();
</script>

{{-- ══ TEKLİF TALEBİ (kütüphanesiz) + ISO "Sonuçlara git" ══ --}}
<script>
(function () {
'use strict';
var form = document.getElementById('rq-form'); if (!form) return;
var rowsEl = document.getElementById('rq-rows'), tpl = document.getElementById('rq-row-tpl'),
    addBtn = document.getElementById('rq-add'), countEl = document.getElementById('rq-count'),
    submit = document.getElementById('rq-submit'), statusEl = document.getElementById('rq-status'),
    body = document.getElementById('rq-body'), done = document.getElementById('rq-done'),
    capBox = document.getElementById('rq-cap'), capQ = document.getElementById('rq-cap-q'),
    capIn = document.getElementById('rq-cevap'), capTok = document.getElementById('rq-cq');
var MAX = 10, CAP_MIN = 4, CAP_MAX = 200, uid = 0, busy = false, capFresh = true;
var FINE = window.matchMedia('(hover: hover) and (pointer: fine)');
var RM   = window.matchMedia('(prefers-reduced-motion: reduce)');

function num(v) { var n = parseFloat(String(v).replace(',', '.')); return isFinite(n) && n > 0 ? n : null; }
function fmt(n) { return String(n).replace('.', ','); }
function rows() { return Array.prototype.slice.call(rowsEl.querySelectorAll('[data-row]')); }
function f(row, k) { return row.querySelector('[data-k="' + k + '"]'); }
function tipOf(row) { var s = f(row, 'urun'); return s.options[s.selectedIndex].getAttribute('data-tip'); }

/* ── Hata gösterimi ── */
function clearErr(scope) {
    Array.prototype.forEach.call((scope || form).querySelectorAll('.rq-e'), function (e) { e.remove(); });
    Array.prototype.forEach.call((scope || form).querySelectorAll('.is-err'), function (e) { e.classList.remove('is-err'); });
}
function setErr(input, msg) {
    var box = input.closest('.rq-f') || input.closest('.rq__check');
    if (!box) return;
    box.classList.add('is-err');
    var s = document.createElement('small'); s.className = 'rq-e'; s.textContent = msg; box.appendChild(s);
}
function showStatus(msg) { statusEl.textContent = msg; statusEl.hidden = !msg; }

/* Kullanıcı alanı düzenlemeye başlayınca o alanın hatası kalkar */
['input', 'change'].forEach(function (ev) {
    form.addEventListener(ev, function (e) {
        var box = e.target.closest && e.target.closest('.rq-f, .rq__check');
        if (box && box.classList.contains('is-err')) { box.classList.remove('is-err'); var er = box.querySelector('.rq-e'); if (er) er.remove(); }
    });
});

/* ── İşlem sorusu (spam koruması): sunucu yeni soru döndürürse yenilenir ── */
function setCaptcha(c) {
    if (!c || !c.q || !c.t) return false;
    capQ.textContent = c.q; capTok.value = c.t; capIn.value = ''; capFresh = true;
    return true;
}
/* Yalnız rakam girişine izin ver (mobilde sayısal klavye açılır) */
capIn.addEventListener('input', function () { capIn.value = capIn.value.replace(/\D+/g, '').slice(0, 3); });

/* ── Kalemler ── */
function wire(row) {
    uid++;
    Array.prototype.forEach.call(row.querySelectorAll('[data-k]'), function (el) { el.id = 'rq' + uid + '-' + el.getAttribute('data-k'); });
    Array.prototype.forEach.call(row.querySelectorAll('[data-for]'), function (l) { l.setAttribute('for', 'rq' + uid + '-' + l.getAttribute('data-for')); });
    row.addEventListener('input', function () { hint(row); });
    row.addEventListener('change', function (e) { if (e.target === f(row, 'urun')) applyType(row); hint(row); });
    row.querySelector('[data-del]').addEventListener('click', function () { row.remove(); renumber(); });
    row.querySelector('[data-notebtn]').addEventListener('click', function () {
        var box = row.querySelector('[data-notebox]'); box.hidden = !box.hidden;
        if (!box.hidden) { try { f(row, 'not').focus({ preventScroll: true }); } catch (e) {} }
    });
}
function applyType(row) {
    var tip = tipOf(row);
    row.setAttribute('data-tip', tip);
    Array.prototype.forEach.call(row.querySelectorAll('[data-show]'), function (box) {
        var on = box.getAttribute('data-show').split(' ').indexOf(tip) > -1;
        box.hidden = !on;
        Array.prototype.forEach.call(box.querySelectorAll('input'), function (i) { i.disabled = !on; });
    });
    var nbox = row.querySelector('[data-notebox]'), nbtn = row.querySelector('[data-notebtn]');
    if (tip === 'ozel') { nbox.hidden = false; nbtn.hidden = true; }
    else { nbtn.hidden = false; if (!f(row, 'not').value) nbox.hidden = true; }
    row.querySelector('[data-not]').textContent = tip === 'ozel' ? 'Ürün tanımı ve ölçüler *' : 'Not (opsiyonel)';
    f(row, 'not').placeholder = tip === 'ozel' ? 'ürün, ölçü, tolerans, malzeme…' : 'tolerans, malzeme, yüzey…';
    hint(row);
}
function hint(row) {
    var h = row.querySelector('[data-hint]'), tip = tipOf(row); h.className = 'rq-row__hint';
    if (tip === 'mil') {
        var c = num(f(row, 'cap').value);
        if (c === null) { h.textContent = 'Mil çapını mm olarak girin.'; return; }
        if (c >= CAP_MIN && c <= CAP_MAX) { h.textContent = 'Ø' + fmt(c) + ' mm stok aralığında (Ø4–200); stok teklifle teyit edilir.'; h.className += ' is-ok'; }
        else { h.textContent = 'Ø' + fmt(c) + ' mm stok aralığı (Ø4–200) dışında; özel üretim olarak değerlendirilir.'; h.className += ' is-warn'; }
    } else if (tip === 'boru') {
        var d = num(f(row, 'dis_cap').value), i = num(f(row, 'ic_cap').value);
        if (d !== null && i !== null && i >= d) { h.textContent = 'İç çap, dış çaptan küçük olmalıdır.'; h.className += ' is-warn'; }
        else if (d !== null && i !== null) { h.textContent = 'Et kalınlığı: ' + fmt(Math.round((d - i) / 2 * 100) / 100) + ' mm (Ø' + fmt(d) + ' / Ø' + fmt(i) + ').'; h.className += ' is-ok'; }
        else h.textContent = 'Dış ve iç çapı birlikte girin; stok teklifle teyit edilir.';
    } else h.textContent = 'Ürünü ve ölçülerini aşağıya yazın.';
}
function renumber() {
    var all = rows();
    all.forEach(function (r, i) { r.querySelector('[data-no]').textContent = 'Kalem ' + (i + 1); r.querySelector('[data-del]').hidden = all.length === 1; });
    countEl.textContent = all.length + ' / ' + MAX;
    addBtn.disabled = all.length >= MAX;
}
function addRow() {
    if (rows().length >= MAX) return null;
    var node = tpl.content.firstElementChild.cloneNode(true);
    rowsEl.appendChild(node); wire(node); applyType(node); renumber();
    return node;
}
addBtn.addEventListener('click', function () {
    var r = addRow(); if (!r) return;
    /* Dokunmatikte klavye açılıp sayfa kaymasın: odak yalnız fare/klavyede */
    if (FINE.matches) { try { f(r, 'urun').focus({ preventScroll: true }); } catch (e) {} }
});

/* ── Dışarıdan (ISO tolerans aracı) kalem aktarımı ── */
function rowIsEmpty(row) {
    return !['cap', 'dis_cap', 'ic_cap', 'boy'].some(function (k) { var el = f(row, k); return el && el.value; }) && !f(row, 'not').value;
}
window.flRqPrefill = function (o) {
    o = o || {};
    if (!done.hidden) { rowsEl.innerHTML = ''; addRow(); done.hidden = true; body.hidden = false; }
    var all = rows(), row = all[all.length - 1];
    if (!row || !rowIsEmpty(row)) row = addRow() || row;   /* 10 kalem doluysa sonuncusu güncellenir */
    if (row) {
        var sel = f(row, 'urun'); sel.selectedIndex = 0; applyType(row);   /* ilk seçenek: Sert Krom Kaplı Mil */
        if (o.cap) f(row, 'cap').value = o.cap;
        if (o.not) { row.querySelector('[data-notebox]').hidden = false; f(row, 'not').value = String(o.not).slice(0, 300); }
        hint(row);
        row.classList.add('is-new'); setTimeout(function () { row.classList.remove('is-new'); }, 2200);
    }
    var sec = document.getElementById('olcu-sorgula');
    if (sec) sec.scrollIntoView({ block: 'start', behavior: RM.matches ? 'auto' : 'smooth' });
    if (row && FINE.matches) { try { f(row, 'boy').focus({ preventScroll: true }); } catch (e) {} }
};

/* ── Doğrulama + gönderim ── */
function collect() {
    clearErr(); showStatus('');
    var ok = true, items = [];
    rows().forEach(function (row) {
        var tip = tipOf(row), it = { urun: f(row, 'urun').value };
        function need(k, label) { var el = f(row, k), v = num(el.value); if (v === null) { setErr(el, label + ' girin'); ok = false; } else it[k] = v; }
        if (tip === 'mil') need('cap', 'Çap');
        if (tip === 'boru') { need('dis_cap', 'Dış çap'); need('ic_cap', 'İç çap'); if (it.dis_cap && it.ic_cap && it.ic_cap >= it.dis_cap) { setErr(f(row, 'ic_cap'), 'İç çap, dış çaptan küçük olmalı'); ok = false; } }
        need('boy', 'Boy'); need('adet', 'Adet');
        var nt = f(row, 'not').value.trim();
        if (tip === 'ozel' && !nt) { setErr(f(row, 'not'), 'Ürün ve ölçüleri yazın'); ok = false; }
        if (nt) it.not = nt;
        items.push(it);
    });
    var ad = document.getElementById('rq-ad'), tel = document.getElementById('rq-tel'), em = document.getElementById('rq-email'), kv = document.getElementById('rq-kvkk');
    if (!ad.value.trim()) { setErr(ad, 'Ad soyad girin'); ok = false; }
    if (!/^[0-9+()\s\-]{7,}$/.test(tel.value.trim())) { setErr(tel, 'Geçerli bir telefon girin'); ok = false; }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(em.value.trim())) { setErr(em, 'Geçerli bir e-posta girin'); ok = false; }
    if (!/^\d{1,3}$/.test(capIn.value.trim())) { setErr(capIn, 'İşlemin sonucunu yazın'); ok = false; }
    if (!kv.checked) { setErr(kv, 'Devam etmek için onay gerekli'); ok = false; }
    return ok ? {
        _token: (form.querySelector('[name=_token]') || {}).value || '',
        website: form.querySelector('[name=website]').value,
        ts: (form.querySelector('[name=ts]') || {}).value || '',
        cq: capTok.value, cevap: capIn.value.trim(),
        ad: ad.value.trim(), firma: document.getElementById('rq-firma').value.trim(),
        telefon: tel.value.trim(), email: em.value.trim(),
        mesaj: document.getElementById('rq-mesaj').value.trim(), kvkk: 1, items: items
    } : null;
}
function serverErrors(errors) {
    var shown = false;
    Object.keys(errors).forEach(function (key) {
        var msg = errors[key][0], m = key.match(/^items\.(\d+)\.(\w+)$/), el = null;
        if (m) { var row = rows()[parseInt(m[1], 10)]; el = row && f(row, m[2]); }
        else el = { ad: document.getElementById('rq-ad'), firma: document.getElementById('rq-firma'), telefon: document.getElementById('rq-tel'), email: document.getElementById('rq-email'), mesaj: document.getElementById('rq-mesaj'), kvkk: document.getElementById('rq-kvkk'), cevap: capIn }[key];
        if (el) { setErr(el, msg); shown = true; }
        else if (key === 'ts' || key === 'cq') { showStatus(msg); shown = 'ts'; }
    });
    if (shown === 'ts') return;
    showStatus(shown ? 'Lütfen işaretli alanları kontrol edin.' : 'Form gönderilemedi. Lütfen bilgileri kontrol edin.');
}
function summary(items) {
    return items.map(function (it, i) {
        var olcu = it.cap ? 'Ø' + fmt(it.cap) : (it.dis_cap ? 'Dış Ø' + fmt(it.dis_cap) + ' / İç Ø' + fmt(it.ic_cap) : '');
        return '<li><b>' + (i + 1) + '. ' + esc(it.urun) + '</b><br><span>' + [olcu, fmt(it.boy) + ' mm boy', it.adet + ' adet'].filter(Boolean).join(' · ') + '</span></li>';
    }).join('');
}
function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (busy) return;
    var payload = collect();
    if (!payload) { showStatus('Lütfen işaretli alanları kontrol edin.'); var first = form.querySelector('.is-err'); if (first) { var inp = first.querySelector('input,select,textarea'); if (inp) try { inp.focus({ preventScroll: false }); } catch (x) {} } return; }
    busy = true; submit.disabled = true; var label = submit.innerHTML; submit.textContent = 'Gönderiliyor…';
    fetch(form.getAttribute('action'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': payload._token },
        body: JSON.stringify(payload)
    }).then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return { s: r.status, j: j }; }); })
      .then(function (res) {
        /* Token her denemede tüketilir; sunucu yeni soru döndürmediyse bir sonraki istekte sayfa yenilenmelidir */
        if (!setCaptcha(res.j && res.j.captcha)) capFresh = false;
        if (res.s >= 200 && res.s < 300 && res.j.ok) {
            document.getElementById('rq-ref').textContent = res.j.ref || '';
            document.getElementById('rq-sum').innerHTML = summary(payload.items);
            body.hidden = true; done.hidden = false;
            var r2 = done.getBoundingClientRect(); if (r2.top < 0 || r2.top > window.innerHeight * 0.6) done.scrollIntoView({ block: 'nearest' });
            try { done.focus({ preventScroll: true }); } catch (x) {}
        } else if (res.s === 422 && res.j.errors) {
            serverErrors(res.j.errors);
            if (!capFresh && res.j.errors.cevap) showStatus('Doğrulama yenilenemedi. Lütfen sayfayı yenileyip tekrar deneyin.');
        }
        else if (res.s === 419) showStatus('Oturum süresi doldu. Sayfayı yenileyip tekrar deneyin.');
        else if (res.s === 429) showStatus('Kısa sürede çok fazla deneme yapıldı. Lütfen birkaç dakika sonra tekrar deneyin.');
        else showStatus('Talep gönderilemedi. Lütfen tekrar deneyin veya İstanbul 0505 934 43 54 / Bursa 0541 459 43 54 numarasını arayın.');
      })
      .catch(function () { showStatus('Bağlantı hatası. Lütfen tekrar deneyin veya İstanbul 0505 934 43 54 / Bursa 0541 459 43 54 numarasını arayın.'); })
      .then(function () { busy = false; submit.disabled = false; submit.innerHTML = label; });
});
document.getElementById('rq-new').addEventListener('click', function () {
    /* Taze işlem sorusu yoksa (sunucu yenisini göndermediyse) sayfayı #olcu-sorgula konumunda yenile */
    if (!capFresh) { location.hash = 'olcu-sorgula'; location.reload(); return; }
    rowsEl.innerHTML = ''; addRow(); document.getElementById('rq-mesaj').value = ''; clearErr(); showStatus('');
    capIn.value = '';
    document.getElementById('rq-kvkk').checked = false; done.hidden = true; body.hidden = false;
});

addRow();

/* ISO aracı: "Sonuçlara git" bağlantısı tıklanınca gizlenir */
var jump = document.getElementById('tolc-jump');
if (jump) jump.addEventListener('click', function () { jump.hidden = true; });
})();
</script>

{{-- ══ v21 — ANİMASYON KATMANI ══ --}}
<div class="fx-progress" aria-hidden="true"><span id="fx-progress-bar"></span></div>

<style>
/* Kaydırma ilerleme çubuğu (transform ile; layout tetiklemez) */
.fx-progress{position:fixed;top:0;left:0;right:0;height:3px;z-index:1090;pointer-events:none}
.fx-progress span{display:block;height:100%;transform-origin:left center;transform:scaleX(0);background:linear-gradient(90deg,#5B7FA6,#1D426A,#C8B98E);will-change:transform}
.fl-home .hero__stat-num{font-variant-numeric:tabular-nums}

/* Hepsi hareket azaltma KAPALIYKEN çalışır */
@media (prefers-reduced-motion:no-preference){

  html{scroll-behavior:smooth}

  /* 1) Hero başlığı: satır satır "maske" açılışı (tek seferlik) */
  .fl-home .hero--signature .hero__main > .hero__title{animation:none}
  .fl-home .hero--signature .hero__title-line{animation:fxMask .95s var(--ease-out) both}
  .fl-home .hero--signature .hero__title-line:nth-child(1){animation-delay:.1s}
  .fl-home .hero--signature .hero__title-line:nth-child(2){animation-delay:.24s}
  .fl-home .hero--signature .hero__title-line:nth-child(3){animation-delay:.38s}

  /* 2) Hero görsel/video: kaydırmada hafif parallax (JS değişkeni --fx-py) */
  .fl-home .hero__media.fx-par :is(.hero__poster,video){transform:translate3d(0,var(--fx-py,0px),0) scale(1.08);will-change:transform}

  /* 3) Hero: fareyi izleyen ışık (yalnız fare ile; JS .fx-spot ekler) */
  .fl-home .fx-spot{position:absolute;left:0;top:0;z-index:2;width:560px;height:560px;margin:-280px 0 0 -280px;border-radius:50%;pointer-events:none;
    background:radial-gradient(circle,rgba(142,168,196,.20) 0%,rgba(91,127,166,.08) 40%,rgba(91,127,166,0) 68%);
    opacity:0;transition:opacity .5s ease;will-change:transform}
  .fl-home .fx-spot.is-on{opacity:1}

  /* 4) Hero ürün konsolu: hafif 3D eğim (JS .fx-tilt ekler) */
  .fl-home .hero--signature .hero__card.fx-tilt{transition:transform .2s ease-out;will-change:transform}

  /* 5) Bölüm başlıkları: etiket → başlık → açıklama kademeli girer */
  .js .fl-home .reveal.is-in .section-tag{animation:fxUp .7s var(--ease-out) both}
  .js .fl-home .reveal.is-in .section-title{animation:fxUp .8s var(--ease-out) .08s both}
  .js .fl-home .reveal.is-in .section-lead{animation:fxUp .8s var(--ease-out) .16s both}
  .js .fl-home .reveal.is-in .rq__kicker{animation:fxUp .7s var(--ease-out) both}
  .js .fl-home .reveal.is-in .rq__title{animation:fxUp .8s var(--ease-out) .08s both}

  /* 6) Bölüm üst çizgisi: görünür olunca soldan çizilir (JS .fx-sec-in ekler) */
  .fl-home :is(.products,.compare,.eng-tools,.pdf-docs,.tolc,.faq,.vid-map,.about,.rq){position:relative}
  .fl-home :is(.products,.compare,.eng-tools,.pdf-docs,.tolc,.faq,.vid-map,.about,.rq)::after{
    content:'';position:absolute;left:0;top:-1px;width:100%;height:2px;z-index:3;pointer-events:none;
    background:linear-gradient(90deg,#1D426A 0%,#5B7FA6 38%,#C8B98E 62%,rgba(200,185,142,0) 100%);
    transform:scaleX(0);transform-origin:left center;transition:transform 1.1s var(--ease-out)}
  .fl-home :is(.products,.compare,.eng-tools,.pdf-docs,.tolc,.faq,.vid-map,.about,.rq).fx-sec-in::after{transform:scaleX(1)}

  /* 7) Bölüm numaraları (03, 04…): kaydırmada hafif paralaks */
  .fl-home .section-index{transform:translate3d(0,var(--fx-iy,0px),0)}

  /* 8) Ürün görselleri: görünür olunca yavaşça yerine oturur */
  .js .fl-home .prod-grid__item.is-in .prod-card__img img{animation:fxImg 1.2s var(--ease-out)}

  /* 9) Kademeli giriş (JS .fx-st ve .fx-in sınıflarını ekler) */
  .js .fl-home .fx-st{
    opacity:0;transform:translateY(14px);
    transition:opacity .55s var(--ease-out),transform .55s var(--ease-out),box-shadow .3s var(--ease-out),border-color .3s;
    transition-delay:calc(var(--i,0) * 70ms),calc(var(--i,0) * 70ms),0s,0s;
  }
  .js .fl-home .fx-st--fade{transform:none;transition:opacity .5s var(--ease-out);transition-delay:calc(var(--i,0) * 60ms)}
  .js .fl-home .fx-in > .fx-st{opacity:1;transform:none}

  /* 10) Düğmeler: parlama süpürmesi + oklar (yalnız fare ile) */
  @media (hover:hover) and (pointer:fine){
    .fl-home :is(.fl-btn-primary,.hero__card-cta,.cta-vid__btn-main){position:relative;overflow:hidden}
    .fl-home :is(.fl-btn-primary,.hero__card-cta,.cta-vid__btn-main)::before{
      content:'';position:absolute;inset:0;pointer-events:none;
      background:linear-gradient(105deg,transparent 38%,rgba(255,255,255,.30) 50%,transparent 62%);
      transform:translateX(-130%);transition:transform .65s var(--ease-out)}
    .fl-home :is(.fl-btn-primary,.hero__card-cta,.cta-vid__btn-main):hover::before{transform:translateX(130%)}
    .fl-home .eng__tool-arrow,.fl-home .prod-card__arrow{transition:transform .25s var(--ease-out),background .25s}
    .fl-home .eng__tool:hover .eng__tool-arrow{transform:translateX(3px)}
    .fl-home .prod-card:hover .prod-card__arrow{transform:translateX(2px)}
  }

  /* 11) SSS: açık kartta sol vurgu çizgisi + cevap girişi */
  .fl-home .faq__item{position:relative}
  .fl-home .faq__item::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;z-index:2;background:linear-gradient(180deg,#5B7FA6,#1D426A);transform:scaleY(0);transform-origin:top center;transition:transform .35s var(--ease-out)}
  .fl-home .faq__item[open]::before{transform:scaleY(1)}
  .fl-home .faq__item[open] .faq__a{animation:fxOpen .3s var(--ease-out)}

  /* 12) Teklif formu: yeni kalem ve başarı onayı */
  .fl-home .rq-row{animation:fxRowIn .35s var(--ease-out)}
  .fl-home .rq__done:not([hidden]) .rq__done-ico{animation:fxPop .5s cubic-bezier(.34,1.56,.64,1)}
  .fl-home .rq__done:not([hidden]) > *:not(.rq__done-ico){animation:fxOpen .4s var(--ease-out) both}

  /* 13) ISO tolerans aracı: sonuçlar her hesaplamada kademeli gelir */
  .fl-home .tolc__fit{animation:fxUp .55s var(--ease-out) both}
  .fl-home .tolc__fit:nth-child(2){animation-delay:.06s}
  .fl-home .tolc__fit:nth-child(3){animation-delay:.12s}
  .fl-home .tolc__fit:nth-child(4){animation-delay:.18s}
  .fl-home .tolc__fit:nth-child(n+5){animation-delay:.24s}
  .fl-home .tolc__table tbody tr{animation:fxFade .45s var(--ease-out) both}
  .fl-home .tolc__table tbody tr:nth-child(2){animation-delay:.05s}
  .fl-home .tolc__table tbody tr:nth-child(3){animation-delay:.1s}
  .fl-home .tolc__table tbody tr:nth-child(4){animation-delay:.15s}
  .fl-home .tolc__table tbody tr:nth-child(n+5){animation-delay:.2s}
  .fl-home .tolc-plot .tolc-row:nth-child(3) .tolc-bar{transition-delay:.06s}
  .fl-home .tolc-plot .tolc-row:nth-child(4) .tolc-bar{transition-delay:.12s}
  .fl-home .tolc-plot .tolc-row:nth-child(5) .tolc-bar{transition-delay:.18s}
  .fl-home .tolc-plot .tolc-row:nth-child(6) .tolc-bar{transition-delay:.24s}
  .fl-home .tolc-plot .tolc-row:nth-child(n+7) .tolc-bar{transition-delay:.3s}

  @keyframes fxMask{
    from{opacity:0;transform:translateY(.45em);clip-path:inset(0 0 100% 0)}
    to{opacity:1;transform:none;clip-path:inset(-.12em -.05em -.2em -.05em)}
  }
  @keyframes fxUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}
  @keyframes fxFade{from{opacity:0}to{opacity:1}}
  @keyframes fxImg{from{transform:scale(1.14)}}
  @keyframes fxOpen{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}
  @keyframes fxRowIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
  @keyframes fxPop{from{opacity:0;transform:scale(.5)}to{opacity:1;transform:none}}
}
</style>

<script>
(function () {
'use strict';
var RM   = window.matchMedia('(prefers-reduced-motion: reduce)');
var DSK  = window.matchMedia('(min-width: 992px)');
var FINE = window.matchMedia('(hover: hover) and (pointer: fine)');

function ready(fn) { document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', fn) : fn(); }

ready(function () {
    var root = document.querySelector('.fl-home');
    if (!root) return;

    var bar   = document.getElementById('fx-progress-bar');
    var hero  = root.querySelector('.hero--signature');
    var media = hero && hero.querySelector('.hero__media');
    var idxEls = root.querySelectorAll('.section-index');
    var ticking = false;

    /* ── Kaydırma: ilerleme çubuğu + hero parallax + bölüm numarası paralaksı (tek rAF) ── */
    function progress() {
        if (!bar) return;
        var d = document.documentElement;
        var max = d.scrollHeight - d.clientHeight;
        var p = max > 0 ? Math.min(1, Math.max(0, (window.pageYOffset || d.scrollTop) / max)) : 0;
        bar.style.transform = 'scaleX(' + p.toFixed(4) + ')';
    }
    function parallax() {
        if (!media || !hero) return;
        if (RM.matches || !DSK.matches) {
            if (media.classList.contains('fx-par')) { media.classList.remove('fx-par'); media.style.removeProperty('--fx-py'); }
            return;
        }
        var r = hero.getBoundingClientRect();
        if (r.bottom < 0) return;
        media.classList.add('fx-par');
        var y = Math.min(Math.max(-r.top, 0), r.height) * 0.16;
        media.style.setProperty('--fx-py', y.toFixed(1) + 'px');
    }
    function indexParallax() {
        if (RM.matches || !DSK.matches || !idxEls.length) return;
        var vh = window.innerHeight;
        Array.prototype.forEach.call(idxEls, function (el) {
            var r = el.getBoundingClientRect();
            if (r.bottom < -100 || r.top > vh + 100) return;
            var v = (r.top + r.height / 2 - vh / 2) * -0.07;
            el.style.setProperty('--fx-iy', Math.max(-22, Math.min(22, v)).toFixed(1) + 'px');
        });
    }
    function onScroll() {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(function () { ticking = false; progress(); parallax(); indexParallax(); });
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    progress(); parallax(); indexParallax();

    if (RM.matches) return;

    /* ── Hero rakamları: 0'dan değere sayar (Ø4–200, 24–48, 2); tek seferlik ── */
    (function countUp() {
        var items = [];
        Array.prototype.forEach.call(root.querySelectorAll('.hero--signature .hero__stat-num'), function (dd) {
            Array.prototype.forEach.call(dd.childNodes, function (n) {
                if (n.nodeType !== 3) return;
                var m = n.nodeValue.match(/^(\D*)(\d+)(\D*)$/);
                if (m) items.push({ n: n, pre: m[1], val: parseInt(m[2], 10), suf: m[3] });
            });
        });
        if (!items.length) return;
        function paint(e) { items.forEach(function (it) { it.n.nodeValue = it.pre + Math.round(it.val * e) + it.suf; }); }
        paint(0);
        var t0 = null, dur = 1200;
        function step(ts) {
            if (t0 === null) t0 = ts;
            var p = Math.min(1, (ts - t0) / dur);
            paint(1 - Math.pow(1 - p, 3));
            if (p < 1) requestAnimationFrame(step);
        }
        setTimeout(function () { requestAnimationFrame(step); }, 450);
        setTimeout(function () { paint(1); }, 2600);   /* sekme arka plandaysa bile son değer kesin */
    })();

    /* ── Hero: fareyi izleyen ışık + ürün konsolunda hafif 3D eğim (yalnız fare) ── */
    if (hero && FINE.matches) {
        var spot = document.createElement('div');
        spot.className = 'fx-spot'; spot.setAttribute('aria-hidden', 'true');
        hero.insertBefore(spot, hero.querySelector('.hero__body'));
        var sRaf = 0, sx = 0, sy = 0;
        hero.addEventListener('pointermove', function (e) {
            var r = hero.getBoundingClientRect();
            sx = e.clientX - r.left; sy = e.clientY - r.top;
            spot.classList.add('is-on');
            if (sRaf) return;
            sRaf = requestAnimationFrame(function () { sRaf = 0; spot.style.transform = 'translate3d(' + sx.toFixed(0) + 'px,' + sy.toFixed(0) + 'px,0)'; });
        });
        hero.addEventListener('pointerleave', function () { spot.classList.remove('is-on'); });

        var card = hero.querySelector('.hero__card');
        if (card) {
            var tRaf = 0, rx = 0, ry = 0;
            card.addEventListener('pointerenter', function () { card.style.animation = 'none'; card.classList.add('fx-tilt'); });
            card.addEventListener('pointermove', function (e) {
                var r = card.getBoundingClientRect();
                rx = -((e.clientY - r.top) / r.height - 0.5) * 5;
                ry = ((e.clientX - r.left) / r.width - 0.5) * 6;
                if (tRaf) return;
                tRaf = requestAnimationFrame(function () {
                    tRaf = 0;
                    card.style.transform = 'perspective(1000px) rotateX(' + rx.toFixed(2) + 'deg) rotateY(' + ry.toFixed(2) + 'deg)';
                });
            });
            card.addEventListener('pointerleave', function () { card.style.transform = ''; });
        }
    }

    if (!('IntersectionObserver' in window)) return;

    /* ── Bölüm üst çizgisi: görünür olunca çizilir ── */
    var secIO = new IntersectionObserver(function (ents) {
        ents.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('fx-sec-in'); secIO.unobserve(e.target); } });
    }, { threshold: 0.08 });
    Array.prototype.forEach.call(root.querySelectorAll('.products,.compare,.eng-tools,.pdf-docs,.tolc,.faq,.vid-map,.about,.rq'), function (s) { secIO.observe(s); });

    /* ── Kademeli giriş: hareket azaltma açıksa veya IntersectionObserver yoksa hiçbir şey gizlenmez ── */
    var groups = [
        ['.rq__steps',        '.rq__step',  false],
        ['.faq__list',        '.faq__item', false],
        ['.fx-table tbody',   'tr',         true ],
        ['.tolc__classes',    'li',         false],
        ['.cta-vid__phones',  'a',          false]
    ];
    var io = new IntersectionObserver(function (ents) {
        ents.forEach(function (e) {
            if (e.isIntersecting) { e.target.classList.add('fx-in'); io.unobserve(e.target); }
        });
    }, { rootMargin: '0px 0px -6% 0px', threshold: 0.08 });

    groups.forEach(function (g) {
        var parent = root.querySelector(g[0]);
        if (!parent) return;
        var kids = Array.prototype.filter.call(parent.children, function (c) { return c.matches(g[1]); });
        if (!kids.length) return;
        kids.forEach(function (k, i) {
            k.classList.add('fx-st');
            if (g[2]) k.classList.add('fx-st--fade');
            k.style.setProperty('--i', Math.min(i, 8));
        });
        io.observe(parent);
    });
});
})();
</script>

@endsection
