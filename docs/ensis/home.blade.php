{{--
|--------------------------------------------------------------------------
| Ensis Pnömatik — Ana Sayfa (home.blade.php)  v4.1  — sadece Türkçe · açık tema · Laravel 5.6 / PHP 7.2 uyumlu
| v4.1: header'da santral + cep (WhatsApp) numarası (masaüstünde iki satırlı blok, tablet/telefonda ara/WhatsApp simgeleri, menüde iki numara)
|       · "Grup şirketimiz" bölümü yeniden tasarlandı (eşit 2x2 kartlar, koyu kurumsal Flutecnica kartı, büyük logo, özet bilgiler)
| v4.0: görsel cila (sayfa sonunda "v4.0 CİLA" CSS'i + küçük JS) · kaydırma çubuğu · bölüm başlık aksanı · marka filigranı
|       · hero'da fare ışığı ve teknik resim paftasında hafif eğim (yalnız fare, "hareketi azalt"ta kapalı) · hesaplayıcıda canlı kesit görseli
|       · "Hesaplamalar" bağlantıları /hesaplamalar sayfasına gider (routes/web.php içinde Route::view('/hesaplamalar','hesaplamalar') tanımlı olmalı; yoksa 404 verir)
|       · .gf (marka filtresi) düğmelerinde tanımsız --font değişkeni düzeltildi
| v3.9: bölüm aralıkları kısaldı · Tox rengi yeşil · popüler ürün grupları yenilendi (teknik resim zeminli kartlar, marka filtresi)
| v3.8: menüden "Tüm Ürünler" kaldırıldı (Ürünler menüsündeki "Tüm ürün grupları" linki duruyor)
| v3.7: KVKK çerez bandı + Google Consent Mode v2 (izin olmadan analiz çerezi yok) · footer'da KVKK linkleri
| v3.6: giriş v3.4'e döndü · hero'daki silindir kartı yerine kalemle çizilen teknik resim paftası (antetli), çizimden sonra mil/taşıyıcı çalışır
| v3.5: intro teknik resim gibi çiziliyor (kalem, pafta çerçevesi, antet); 7 sn güvenlik kapanışı
| v3.4: giriş animasyonu (siyah-beyaz teknik çizim: pnömatik silindir + milsiz silindir), oturumda bir kez
| v3.3: yeni koyu footer (teklif şeridi, simgeli iletişim, açık/kapalı rozeti) — master ile aynı
| v3.2: Sertifikalarımız linkleri kaldırıldı · tıklanabilir çözüm alanları · destek kartı · marka logoları kartlarda · teklif bandında telefon/WhatsApp
| v3.1: kanonik adres https://www (tüm site ile aynı) · temiz marka logoları (uploads/brands)
| v3.0: GA4 (G-FBNSJL14WJ) + dönüşüm olayları · www'siz kanonik adres · WebPage/HowTo/ItemList şemaları
|       · tablette Teklif butonu · dokunmatikte açılan menü · 44px dokunma hedefleri · NAP'lı "Kısaca Ensis"
|--------------------------------------------------------------------------
| - NOT (Laravel 5.6): Blade echo icinde ' or ' gecen ifadeler parantez icine alinmali.
| - Tek başına çalışır (layout gerektirmez); ileride layouts/app.blade.php'ye bölünecek.
| - Controller'dan $brands / $faqs gönderilirse onları kullanır, yoksa aşağıdaki varsayılanlar.
| - SEO: title, description, canonical, OG/Twitter, JSON-LD
|   (Organization, LocalBusiness, WebSite, ItemList, FAQPage)
| - GEO: geo.region / geo.position / ICBM + "Kısaca Ensis" yapay zekâ özet bloğu
--}}
@php

    $site = [
        'name'      => 'Ensis Pnömatik',
        'legal'     => 'Ensis Pnömatik Sanayi ve Ticaret Ltd. Şti.',
        'founded'   => 2007,
        'phone'     => '0224 443 54 20',
        'phoneIntl' => '+902244435420',
        'gsm'       => '0554 592 15 44',
        'whatsapp'  => '905545921544',
        'email'     => 'info@ensispnomatik.com',
        'street'    => 'Üçevler Mah. Nilüfer Ticaret Merkezi 56. Sokak No:12',
        'district'  => 'Nilüfer',
        'city'      => 'Bursa',
        'lat'       => 40.209806,
        'lng'       => 28.933755,
        'logo'      => asset('assets/img/logo/logo.png'),
        'og'        => asset('assets/img/og/ensis-og.jpg'),
        'map'       => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3046.966592555061!2d28.933755275831203!3d40.20980606820763!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14ca1144640b37a3%3A0xde744c21cee86215!2sEnsis%20Pn%C3%B6matik%20San.Tic.Ltd.%C5%9Eti.!5e0!3m2!1str!2str!4v1716747410349!5m2!1str!2str',
        'hours'     => 'Hafta içi 08.30–18.00 · Cumartesi 08.30–13.00',
        'mapLink'   => 'https://www.google.com/maps/search/?api=1&query=Ensis+Pn%C3%B6matik+Nil%C3%BCfer+Bursa',
    ];
    $years = (int) date('Y') - $site['founded'];

    $brands = $brands ?? [
        [
            'name' => 'Univer', 'slug' => 'univer', 'code' => 'UNV', 'color' => '#0a6cff',
            'origin' => 'İtalya',
            'desc' => 'Pnömatik otomasyon, otomotiv hatları ve modüler takımlama sistemlerinde geniş seri yelpazesi.',
            'cats' => [
                ['pomatik-otomasyon', 'Pnömatik Otomasyon'],
                ['otomotiv', 'Otomotiv'],
                ['moduler-takimlama-sistemi', 'Modüler Takımlama'],
                ['elektrik-otomasyonu', 'Elektrik Otomasyonu'],
                ['muhendislik', 'Mühendislik'],
            ],
        ],
        [
            'name' => 'Medan', 'slug' => 'medan', 'code' => 'MDN', 'color' => '#00a86b',
            'origin' => 'Almanya',
            'desc' => 'ISO silindirler, rotsuz silindirler, tutucular ve elektrikli doğrusal aktüatörler.',
            'cats' => [
                ['pnomatik-silindirler', 'Pnömatik Silindirler'],
                ['elektrikli-dogrusal-aktuatorler', 'Elektrikli Doğrusal Aktüatörler'],
                ['pnomatik-valfler', 'Pnömatik Valfler'],
                ['pisco-baglanti-parcalari', 'Pisco Bağlantı Parçaları'],
                ['pnomatik-filtreler-ve-regulatorler', 'Filtre ve Regülatörler'],
            ],
        ],
        [
            'name' => 'Mindman', 'slug' => 'mindman', 'code' => 'MDM', 'color' => '#ff6b2c',
            'origin' => 'Tayvan',
            'desc' => 'Yön kontrol vanaları, hava hazırlama, vakum bileşenleri, sensörler ve kol sonu aletleri.',
            'cats' => [
                ['yon-kontrol-vanalari', 'Yön Kontrol Vanaları'],
                ['hava-aritma-unitesi', 'Hava Hazırlama Ünitesi'],
                ['hava-silindirleri', 'Hava Silindirleri'],
                ['vakum-bilesenleri', 'Vakum Bileşenleri'],
                ['kol-sonu-aletleri-eoat', 'Kol Sonu Aletleri (EOAT)'],
            ],
        ],
        [
            'name' => 'Tox-Pressotechnik', 'slug' => 'tox-presso-technik', 'code' => 'TOX', 'color' => '#16a34a',
            'origin' => 'Almanya',
            'desc' => 'Pres tahrikleri, presler, perçinleme ve birleştirme araçları ile proses izleme sistemleri.',
            'cats' => [
                ['suruculer', 'Pres Tahrikleri'],
                ['presler', 'Presler'],
                ['masalar', 'Maşalar'],
                ['kontroller-ve-izleme', 'Kontrol ve İzleme'],
                ['percinleme-araclari', 'Perçinleme Araçları'],
            ],
        ],
    ];

    // Popüler ürün grupları — kategorilere iç link (SEO); görseller eski sitenin uploads/category klasöründen
    $groups = [
        ['slug' => 'iso-silindirler',                      'name' => 'ISO Pnömatik Silindirler',       'brand' => 'Medan', 'img' => '335-1713903999.jpg'],
        ['slug' => 'pnomatik-rotsuz-silindirler',          'name' => 'Rotsuz Silindirler',             'brand' => 'Medan', 'img' => '334-1713901329.jpg'],
        ['slug' => 'kilavuzlu-silindirler',                'name' => 'Kılavuzlu Silindirler',          'brand' => 'Univer', 'img' => '11-1711367368.jpg'],
        ['slug' => 'standart-tabanli-kompakt-silindirler', 'name' => 'Kompakt Silindirler',            'brand' => 'Univer', 'img' => '4-1711357041.jpg'],
        ['slug' => 'paslanmaz-celik-silindirler',          'name' => 'Paslanmaz Çelik Silindirler',    'brand' => 'Univer', 'img' => '6-1711358253.jpg'],
        ['slug' => 'pnomatik-doner-aktuatorler',           'name' => 'Pnömatik Döner Aktüatörler',     'brand' => 'Medan', 'img' => '338-1713906843.jpg'],
        ['slug' => 'yon-kontrol-vanalari',                 'name' => 'Solenoid ve Yön Kontrol Valfleri', 'brand' => 'Mindman', 'img' => '142-1712151424.jpg'],
        ['slug' => 'hava-aritma-unitesi',                  'name' => 'Şartlandırıcı (Hava Hazırlama)', 'brand' => 'Mindman', 'img' => '168-1712223461.jpg'],
        ['slug' => 'basinc-regulatorleri',                 'name' => 'Basınç Regülatörleri',           'brand' => 'Medan', 'img' => '360-1713975971.jpg'],
        ['slug' => 'vakum-bilesenleri',                    'name' => 'Vakum Bileşenleri',              'brand' => 'Mindman', 'img' => '275-1712925865.jpg'],
        ['slug' => 'tutucular-pnomatik-silindirler',       'name' => 'Pnömatik Tutucular (Gripper)',   'brand' => 'Medan', 'img' => '339-1713909198.jpg'],
        ['slug' => 'pisco-baglanti-parcalari',             'name' => 'Pnömatik Bağlantı Parçaları',    'brand' => 'Pisco', 'img' => '345-1713912093.jpg'],
    ];

    // Sektörler — yerel SEO; ürün etiketleri kategori sayfalarına iç link
    $sectors = [
        ['icon' => 'car',   'name' => 'Otomotiv ve Yan Sanayi', 'desc' => 'Kaynak fikstürleri, montaj hatları ve pres uygulamalarında güvenilir sıkıştırma ve konumlama.',
         'tags' => [['sikistirma-silindirleri', 'Sıkıştırma silindirleri'], ['kilavuzlu-silindirler', 'Kılavuzlu silindirler'], ['presler', 'TOX presler']]],
        ['icon' => 'wash',  'name' => 'Beyaz Eşya', 'desc' => 'Montaj ve test istasyonlarında hızlı, tekrarlı ve hassas hareket.',
         'tags' => [['standart-tabanli-kompakt-silindirler', 'Kompakt silindirler'], ['yon-kontrol-vanalari', 'Yön kontrol valfleri'], ['vakum-bilesenleri', 'Vakum']]],
        ['icon' => 'box',   'name' => 'Ambalaj ve Paketleme', 'desc' => 'Kutulama, etiketleme ve paletleme hatlarında kavrama ve transfer.',
         'tags' => [['vakum-bilesenleri', 'Vakum bileşenleri'], ['pnomatik-rotsuz-silindirler', 'Rotsuz silindirler'], ['tutucular-pnomatik-silindirler', 'Tutucular']]],
        ['icon' => 'thread','name' => 'Tekstil', 'desc' => 'Kesintisiz çalışan makinelerde dayanıklı silindir ve temiz, kuru hava.',
         'tags' => [['iso-silindirler', 'ISO silindirler'], ['hava-aritma-unitesi', 'Şartlandırıcılar'], ['pisco-baglanti-parcalari', 'Bağlantı parçaları']]],
        ['icon' => 'food',  'name' => 'Gıda ve İçecek', 'desc' => 'Hijyen gerektiren ortamlarda korozyona dayanıklı paslanmaz çözümler.',
         'tags' => [['paslanmaz-celik-silindirler', 'Paslanmaz silindirler'], ['hava-aritma-unitesi', 'Hava hazırlama'], ['vakum-bilesenleri', 'Vakum']]],
        ['icon' => 'wood',  'name' => 'Mobilya ve Ahşap İşleme', 'desc' => 'CNC, presleme ve kenar bantlama makinelerinde güçlü ve kontrollü hareket.',
         'tags' => [['pnomatik-doner-aktuatorler', 'Döner aktüatörler'], ['basinc-regulatorleri', 'Basınç regülatörleri'], ['vakum-bilesenleri', 'Vakum']]],
    ];
    $sectorIcons = [
        'car'    => '<path d="M3 14l2.2-5.2A2 2 0 0 1 7 7.6h10a2 2 0 0 1 1.8 1.2L21 14v3a1 1 0 0 1-1 1h-1.2M5.2 18H4a1 1 0 0 1-1-1v-3h18"/><circle cx="7.5" cy="18" r="1.8"/><circle cx="16.5" cy="18" r="1.8"/><path d="M9.3 18h5.4"/>',
        'wash'   => '<rect x="5" y="3" width="14" height="18" rx="2"/><circle cx="12" cy="13" r="4"/><path d="M8 6h2"/>',
        'box'    => '<path d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5v-9Z"/><path d="M3 7.5 12 12l9-4.5M12 12v9"/>',
        'thread' => '<rect x="6" y="3" width="12" height="3.5" rx="1"/><rect x="6" y="17.5" width="12" height="3.5" rx="1"/><path d="M8 6.5v11M16 6.5v11M8 9.5l8 2M8 13.5l8-2M8 16l8-1.5"/>',
        'food'   => '<path d="M7 3v8a2 2 0 0 0 4 0V3M9 11v10M16 3c-2 1.5-2 6 0 7v11"/>',
        'wood'   => '<path d="M7 3h10v8H7z"/><path d="M5 11h14v3H5zM6.5 14v7M17.5 14v7"/>',
    ];

    // Pnömatik rehber — kısa teknik anlatımlar (GEO; FAQPage şemasına da eklenir)
    $guide = [
        ['ISO 15552 standardı nedir?',
         'ISO 15552, profil gövdeli pnömatik silindirlerin bağlantı ölçülerini (montaj delikleri, rot ucu dişi, hava bağlantı portları) belirleyen uluslararası standarttır ve Ø32–Ø320 mm çapları kapsar. Bu standarda uygun silindirler farklı üreticiler arasında birbirinin yerine takılabilir. Mini silindirler için ISO 6432 (Ø8–Ø25), kompakt silindirler için ISO 21287 standardı kullanılır.'],
        ['Pnömatik silindir çapı nasıl seçilir?',
         'Önce taşınacak yük ve gerekli kuvvet belirlenir. Teorik kuvvet F = P × A formülüyle bulunur; örneğin 6 bar basınçta Ø50 silindirin itme kuvveti yaklaşık 1.178 N\'dur. Sürtünme ve hız kayıpları nedeniyle hareketli uygulamalarda yükün, teorik kuvvetin yaklaşık %50–70\'ini geçmeyecek şekilde çap seçilmesi önerilir.'],
        ['Şartlandırıcı (hava hazırlama ünitesi) neden gereklidir?',
         'Kompresör havası toz, su ve yağ kalıntısı taşır. Şartlandırıcının filtresi bu kirleri tutar, regülatörü basıncı sabit tutar, gerekiyorsa yağlayıcısı sisteme kontrollü yağ verir. Temiz ve kuru hava, conta ömrünü uzatır ve valf arızalarını belirgin şekilde azaltır.'],
        ['Tek etkili ve çift etkili silindir arasındaki fark nedir?',
         'Tek etkili silindirde hava yalnızca bir yönde kuvvet üretir, geri dönüşü içteki yay sağlar; kısa stroklu ve düşük hava tüketimli işler için uygundur. Çift etkili silindirde hava her iki yönde de çalışır; iki yönde kuvvet gereken ve uzun stroklu uygulamalarda tercih edilir.'],
        ['5/2 ve 3/2 valf ne anlama gelir?',
         'İlk rakam valfin bağlantı (port) sayısını, ikinci rakam konum sayısını gösterir. 5/2 valf beş bağlantılı ve iki konumludur; çift etkili silindirleri sürmek için kullanılır. 3/2 valf üç bağlantılı ve iki konumludur; tek etkili silindirlerde ve basınç açma-kapama işlerinde kullanılır.'],
    ];

    // Kuvvet hesaplayıcı — silindir çapı => ISO standart rot çapı (mm)
    $bores = [8 => 4, 10 => 4, 12 => 6, 16 => 6, 20 => 8, 25 => 10, 32 => 12, 40 => 16, 50 => 20, 63 => 20, 80 => 25, 100 => 25, 125 => 32, 160 => 40, 200 => 40, 250 => 50, 320 => 63];

    // Logo şeridi — logolar eski sitenin uploads klasöründen; Mindman logosu yüklenmeli
    // Logo şeridi — temiz logo (uploads/brands/{dosya}.svg veya .png) yüklenmişse o kullanılır,
    // yoksa eski görsel (uploads/contents/...jpg) kırpılarak gösterilir; o da yoksa marka adı yazılır.
    $partners = [
        ['name' => 'Univer',            'file' => 'univer',     'old' => 'uploads/contents/3-1714936885-4.jpg', 'url' => 'https://www.univer-group.com/en/'],
        ['name' => 'Medan',             'file' => 'medan',      'old' => 'uploads/contents/3-1714936885-2.jpg', 'url' => 'https://www.medan-gmbh.com/en/'],
        ['name' => 'Mindman',           'file' => 'mindman',    'old' => null,                                  'url' => 'https://www.mindman.com.tw/'],
        ['name' => 'TOX PRESSOTECHNIK', 'file' => 'tox',        'old' => 'uploads/contents/3-1714936885-3.jpg', 'url' => 'https://tox.com/'],
        ['name' => 'Flutecnica',        'file' => 'flutecnica', 'old' => 'uploads/contents/3-1714936885-1.jpg', 'url' => 'https://www.flutecnica.com/', 'fit' => true],
    ];
    $pubFile = function ($rel) {
        $roots = array_filter([isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : null, public_path(), base_path('../httpdocs')]);
        foreach ($roots as $r) if (@is_file(rtrim($r, '/') . '/' . $rel)) return true;
        return false;
    };
    $brandLogo = function ($file) use ($pubFile) {
        foreach (['svg', 'png', 'webp'] as $ext) {
            $rel = 'uploads/brands/' . $file . '.' . $ext;
            if ($pubFile($rel)) return $rel;
        }
        return null;
    };
    foreach ($partners as $k => $p) {
        $clean = $brandLogo($p['file']);
        $partners[$k]['logo']  = $clean ?: $p['old'];
        $partners[$k]['clean'] = (bool) $clean || !empty($p['fit']);   // temiz logo veya geniş logo: kırpmadan sığdır
    }
    $flLogo = $brandLogo('flutecnica');

    $areas = [
        ['cyl',    'Pnömatik',            'Silindir, valf, bağlantı ve hava hazırlama bileşenleri.',          'category/pnomatik-silindirler',           'Pnömatik silindirler'],
        ['drop',   'Hidrolik',            'Endüstriyel hidrolik uygulamalar için bileşen tedariki.',          'iletisim#form',                           'Teklif isteyin'],
        ['vac',    'Vakum Sistemleri', 'Vakum jeneratörleri, vantuzlar ve kavrama çözümleri.',               'category/vakum-bilesenleri',              'Vakum bileşenleri'],
        ['chip',   'Otomasyon',           'Elektrikli aktüatörler, sensörler ve kontrol ekipmanları.',        'category/elektrikli-dogrusal-aktuatorler', 'Elektrikli aktüatörler'],
        ['linear', 'Doğrusal Hareket',  'Kılavuzlu silindirler, kızaklar ve doğrusal aktüatörler.',          'category/kilavuzlu-silindirler',          'Kılavuzlu silindirler'],
        ['shield', 'Pres Emniyet',      'Pres tahrikleri, birleştirme teknolojisi ve proses izleme.',        'category/tox-presso-technik',             'TOX pres çözümleri'],
    ];

    $faqs = $faqs ?? [
        ['Ensis Pnömatik hangi markaları temsil ediyor?',
         'Univer (İtalya), Medan (Almanya), Mindman (Tayvan) ve Tox-Pressotechnik (Almanya) markalarının pnömatik, otomasyon ve pres teknolojisi ürünlerini sunuyoruz.'],
        ['Ensis Pnömatik nerede bulunuyor?',
         'Merkezimiz Bursa Nilüfer\'de, Üçevler Mahallesi\'ndeki Nilüfer Ticaret Merkezi\'ndedir.'],
        ['Çalışma saatleriniz nedir?',
         'Hafta içi 08.30–18.00, cumartesi 08.30–13.00 saatleri arasında açığız; pazar günü kapalıyız.'],
        ['Nasıl teklif alabilirim?',
         'Teklif formunu doldurabilir, 0224 443 54 20 numarasını arayabilir, WhatsApp hattımıza yazabilir ya da info@ensispnomatik.com adresine ürün kodu veya teknik ihtiyacınızı gönderebilirsiniz.'],
        ['Ürün seçiminde teknik destek veriyor musunuz?',
         'Evet. Uygulamanızın basınç, kuvvet, strok ve ortam koşullarına göre doğru seri ve aksesuar seçiminde destek veriyoruz.'],
        ['Ensis ile Flutecnica arasındaki ilişki nedir?',
         'Flutecnica Akışkan Teknolojileri kardeş şirketimizdir. Krom kaplı mil, honlu boru ve hidrolik-pnömatik bileşen ihtiyaçlarınız için flutecnica.com\'u ziyaret edebilirsiniz.'],
    ];

    $seo = [
        'title' => 'Pnömatik Silindir, Valf ve Otomasyon | Ensis Pnömatik Bursa',
        'desc'  => 'Bursa Nilüfer\'de 2007\'den beri pnömatik silindir, solenoid valf, şartlandırıcı, vakum ve doğrusal hareket ürünleri; Univer, Medan, Mindman ve Tox tedariki.',
    ];
    // Kanonik adres sabit: https + www'siz (Search Console mülkü, sitemap ve .htaccess ile aynı)
    $base      = 'https://www.ensispnomatik.com';   // tek kanonik adres: https + www (master, sitemap, robots ile aynı)
    $canonical = $base . '/';
    $updated   = '2026-10-19';   // içerik güncellendikçe değiştirin (şemada ve "Kısaca Ensis"te görünür)
    $ogAlt     = 'Ensis Pnömatik — Bursa pnömatik silindir, valf ve otomasyon';
    $site['logo'] = preg_replace('~^https?://www\.~i', 'https://', $site['logo']);
    $site['og']   = preg_replace('~^https?://www\.~i', 'https://', $site['og']);

    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => $base . '/#organization',
                'name' => $site['name'],
                'legalName' => $site['legal'],
                'url' => $base . '/',
                'logo' => $site['logo'],
                'foundingDate' => (string) $site['founded'],
                'email' => $site['email'],
                'telephone' => $site['phoneIntl'],
                'sameAs' => ['https://www.flutecnica.com'],
                'brand' => array_map(function ($b) { return ['@type' => 'Brand', 'name' => $b['name']]; }, $brands),
                'contactPoint' => [
                    '@type' => 'ContactPoint',
                    'telephone' => $site['phoneIntl'],
                    'email' => $site['email'],
                    'contactType' => 'sales',
                    'areaServed' => 'TR',
                    'availableLanguage' => 'Turkish',
                ],
                'hasOfferCatalog' => [
                    '@type' => 'OfferCatalog',
                    'name' => 'Pnömatik ve otomasyon ürün grupları',
                    'itemListElement' => array_map(function ($g) use ($base) {
                        return ['@type' => 'OfferCatalog', 'name' => $g['name'], 'url' => $base . '/category/' . $g['slug']];
                    }, $groups),
                ],
            ],
            [
                '@type' => 'LocalBusiness',
                '@id' => $base . '/#localbusiness',
                'name' => $site['legal'],
                'image' => $site['og'],
                'url' => $base . '/',
                'telephone' => $site['phoneIntl'],
                'email' => $site['email'],
                'parentOrganization' => ['@id' => $base . '/#organization'],
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $site['street'],
                    'addressLocality' => $site['district'],
                    'addressRegion' => $site['city'],
                    'addressCountry' => 'TR',
                ],
                'geo' => ['@type' => 'GeoCoordinates', 'latitude' => $site['lat'], 'longitude' => $site['lng']],
                'hasMap' => $site['mapLink'],
                'openingHoursSpecification' => [
                    ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], 'opens' => '08:30', 'closes' => '18:00'],
                    ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => 'Saturday', 'opens' => '08:30', 'closes' => '13:00'],
                ],
                'areaServed' => [
                    ['@type' => 'City', 'name' => 'Bursa'],
                    ['@type' => 'Country', 'name' => 'Türkiye'],
                ],
                'contactPoint' => [
                    ['@type' => 'ContactPoint', 'telephone' => $site['phoneIntl'], 'contactType' => 'sales', 'areaServed' => 'TR', 'availableLanguage' => 'Turkish'],
                    ['@type' => 'ContactPoint', 'telephone' => '+' . $site['whatsapp'], 'contactType' => 'customer service', 'areaServed' => 'TR', 'availableLanguage' => 'Turkish', 'description' => 'WhatsApp'],
                ],
                'knowsAbout' => ['Pnömatik', 'Pnömatik silindir', 'ISO 15552', 'ISO 6432', 'ISO 21287', 'Solenoid valf', '5/2 valf', '3/2 valf', 'Şartlandırıcı', 'Vakum bileşenleri', 'Hidrolik', 'Otomasyon', 'Doğrusal hareket', 'Pres emniyet'],
                'brand' => array_map(function ($b) { return ['@type' => 'Brand', 'name' => $b['name']]; }, $brands),
            ],
            [
                '@type' => 'WebSite',
                '@id' => $base . '/#website',
                'url' => $base . '/',
                'name' => $site['name'],
                'description' => $seo['desc'],
                'inLanguage' => 'tr-TR',
                'publisher' => ['@id' => $base . '/#organization'],
            ],
            [
                '@type' => 'WebPage',
                '@id' => $base . '/#webpage',
                'url' => $canonical,
                'name' => $seo['title'],
                'description' => $seo['desc'],
                'inLanguage' => 'tr-TR',
                'isPartOf' => ['@id' => $base . '/#website'],
                'about' => ['@id' => $base . '/#organization'],
                'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => $site['og'], 'width' => 1200, 'height' => 630],
                'datePublished' => '2024-05-01',
                'dateModified' => $updated,
            ],
            [
                '@type' => 'ItemList',
                'name' => 'Temsil edilen markalar',
                'itemListElement' => array_map(function ($b, $i) use ($base) {
                    return [
                        '@type' => 'ListItem', 'position' => $i + 1, 'name' => $b['name'],
                        'url' => $base . '/category/' . $b['slug'],
                    ];
                }, $brands, array_keys($brands)),
            ],
            [
                '@type' => 'ItemList',
                'name' => 'Popüler pnömatik ürün grupları',
                'itemListElement' => array_map(function ($g, $i) use ($base) {
                    return ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $g['name'], 'url' => $base . '/category/' . $g['slug']];
                }, $groups, array_keys($groups)),
            ],
            [
                '@type' => 'HowTo',
                'name' => 'Ensis Pnömatik\'ten nasıl teklif alınır?',
                'inLanguage' => 'tr-TR',
                'step' => [
                    ['@type' => 'HowToStep', 'position' => 1, 'name' => 'İhtiyacı paylaşın', 'text' => 'Ürün kodu, teknik resim ya da uygulama bilgisini telefon, WhatsApp, e-posta veya teklif formuyla iletin.'],
                    ['@type' => 'HowToStep', 'position' => 2, 'name' => 'Teknik seçim', 'text' => 'Uygun seri ve aksesuarlar teknik ekiple birlikte belirlenir.'],
                    ['@type' => 'HowToStep', 'position' => 3, 'name' => 'Teklif', 'text' => 'Fiyat ve termin bilgisiyle net teklif hazırlanır.'],
                    ['@type' => 'HowToStep', 'position' => 4, 'name' => 'Teslimat', 'text' => 'Sipariş takipli şekilde teslim edilir.'],
                ],
            ],
            [
                '@type' => 'FAQPage',
                '@id' => $base . '/#sss',
                'inLanguage' => 'tr-TR',
                'isPartOf' => ['@id' => $base . '/#webpage'],
                'mainEntity' => array_map(function ($f) {
                    return [
                        '@type' => 'Question', 'name' => $f[0],
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
                    ];
                }, array_merge($faqs, $guide)),
            ],
            [
                '@type' => 'WebApplication',
                'name' => 'Pnömatik silindir kuvvet hesaplayıcı',
                'url' => $base . '/#hesaplayici',
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Any',
                'inLanguage' => 'tr-TR',
                'description' => 'Silindir çapı, rot çapı ve basınca göre pnömatik silindirin itme ve çekme kuvvetini ve hava tüketimini hesaplar.',
                'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'TRY'],
                'publisher' => ['@id' => $base . '/#organization'],
            ],
        ],
    ];

    $icons = [
        'cyl'    => '<rect x="2" y="8" width="13" height="8" rx="1.5"/><path d="M15 12h7M20 10v4M6 8v8"/>',
        'drop'   => '<path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11Z"/><path d="M9 15a3 3 0 0 0 3 3"/>',
        'vac'    => '<path d="M12 3v7M8 10h8l3 7H5l3-7Z"/><path d="M4 20h16"/>',
        'chip'   => '<rect x="6" y="6" width="12" height="12" rx="2"/><path d="M9 2v4M15 2v4M9 18v4M15 18v4M2 9h4M2 15h4M18 9h4M18 15h4"/>',
        'linear' => '<path d="M2 16h20M2 20h20"/><rect x="7" y="6" width="8" height="6" rx="1"/><path d="M18 9h4M20 7l2 2-2 2"/>',
        'shield' => '<path d="M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/>',
    ];
@endphp
<!DOCTYPE html>
<html lang="tr" dir="ltr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
{{-- Google Analytics 4 (Ensis Pnömatik) — ana sayfa master şablonu kullanmadığı için burada --}}
{{-- KVKK / çerez onayı: Google Consent Mode v2 — izin verilene kadar analiz çerezleri KAPALI (Analytics kodundan ÖNCE gelmeli) --}}
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  (function () {
    var c = null; try { c = localStorage.getItem('ensisCerez'); } catch (e) {}
    gtag('consent', 'default', {
      ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied',
      analytics_storage: c === 'all' ? 'granted' : 'denied',
      functionality_storage: 'granted', security_storage: 'granted', wait_for_update: 500
    });
  })();
</script>
<link rel="preconnect" href="https://www.googletagmanager.com">
<script async src="https://www.googletagmanager.com/gtag/js?id=G-FBNSJL14WJ"></script>
<script>
  gtag('js', new Date());
  gtag('config', 'G-FBNSJL14WJ');
</script>
<script>(function (d) {
  d.classList.add('js');
  // Giriş animasyonu: oturumda bir kez; arama motoru botları, PageSpeed ve "hareketi azalt" kullananlar görmez
  try {
    if (!sessionStorage.getItem('ensisIntro') && !/bot|crawl|spider|slurp|lighthouse|pagespeed|headless/i.test(navigator.userAgent)
        && !(window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches)) { d.classList.add('intro'); setTimeout(function () { d.classList.remove('intro'); }, 7000); }
  } catch (e) {}
})(document.documentElement)</script>
<title>{{ $seo['title'] }}</title>
<meta name="description" content="{{ $seo['desc'] }}">
<meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
<link rel="canonical" href="{{ $canonical }}">
<link rel="alternate" hreflang="tr" href="{{ $canonical }}">
<link rel="alternate" hreflang="x-default" href="{{ $canonical }}">
<meta name="author" content="{{ $site['legal'] }}">

{{-- GEO --}}
<meta name="geo.region" content="TR-16">
<meta name="geo.country" content="TR">
<meta name="geo.placename" content="Nilüfer, Bursa">
<meta name="geo.position" content="{{ $site['lat'] }};{{ $site['lng'] }}">
<meta name="ICBM" content="{{ $site['lat'] }}, {{ $site['lng'] }}">

{{-- Open Graph / Twitter --}}
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $site['name'] }}">
<meta property="og:locale" content="tr_TR">
<meta property="og:title" content="{{ $seo['title'] }}">
<meta property="og:description" content="{{ $seo['desc'] }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $site['og'] }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $ogAlt }}">
<meta property="og:updated_time" content="{{ $updated }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo['title'] }}">
<meta name="twitter:description" content="{{ $seo['desc'] }}">
<meta name="twitter:image" content="{{ $site['og'] }}">
<meta name="twitter:image:alt" content="{{ $ogAlt }}">

<meta name="theme-color" content="#0a6cff">
<meta name="format-detection" content="telephone=no">
<meta name="apple-mobile-web-app-title" content="Ensis">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

<link rel="preload" href="{{ asset('assets/fonts/inter-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('assets/fonts/inter-latin-ext-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>

<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

<style>
/* fontlar: public/assets/fonts (yerel, Türkçe karakter destekli) */
@font-face{font-family:'Inter';font-style:normal;font-display:swap;font-weight:100 900;src:url('{{ asset('assets/fonts/inter-latin-ext-wght-normal.woff2') }}') format('woff2');unicode-range:U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF}
@font-face{font-family:'Inter';font-style:normal;font-display:swap;font-weight:100 900;src:url('{{ asset('assets/fonts/inter-latin-wght-normal.woff2') }}') format('woff2');unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}
@font-face{font-family:'JetBrains Mono';font-style:normal;font-display:swap;font-weight:100 900;src:url('{{ asset('assets/fonts/jetbrains-mono-latin-ext-wght-normal.woff2') }}') format('woff2');unicode-range:U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF}
@font-face{font-family:'JetBrains Mono';font-style:normal;font-display:swap;font-weight:100 900;src:url('{{ asset('assets/fonts/jetbrains-mono-latin-wght-normal.woff2') }}') format('woff2');unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}
:root{
  --bg:#ffffff; --bg-2:#f4f8fd; --bg-3:#eaf2fc; --panel:#ffffff;
  --line:#e3eaf3; --line-2:#cfd9e6;
  --text:#0b1b2e; --muted:#4a5b70; --dim:#8090a4;
  --accent:#0a6cff; --accent-2:#00b4d8; --accent-3:#ff6b2c; --ok:#10b981;
  --accent-soft:rgba(10,108,255,.08);
  --grad:linear-gradient(115deg,#0a6cff 0%,#1f8bff 45%,#00b4d8 100%);
  --shadow-sm:0 1px 2px rgba(11,27,46,.05),0 2px 10px rgba(11,27,46,.05);
  --shadow:0 14px 34px -14px rgba(11,27,46,.20);
  --shadow-lg:0 34px 70px -28px rgba(10,70,170,.35);
  --radius:16px; --radius-lg:24px;
  --gutter:clamp(16px,4vw,40px); --max:1240px;
  --hdr:72px;
  --ease:cubic-bezier(.2,.7,.2,1);
}
*,*::before,*::after{box-sizing:border-box}
html{-webkit-text-size-adjust:100%;scroll-behavior:smooth;scroll-padding-top:calc(var(--hdr) + 12px)}
body{margin:0;background:var(--bg);color:var(--text);font:400 16px/1.65 Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;-webkit-font-smoothing:antialiased;overflow-x:hidden}
body.lock{overflow:hidden}
img,svg{display:block;max-width:100%}
a{color:inherit;text-decoration:none}
button{font:inherit;color:inherit;background:none;border:0;cursor:pointer}
h1,h2,h3,h4{margin:0;line-height:1.12;letter-spacing:-.025em;font-weight:700}
p{margin:0}
::selection{background:rgba(10,108,255,.18)}
:focus-visible{outline:2px solid var(--accent);outline-offset:3px;border-radius:6px}
.skip{position:absolute;left:-999px;top:8px;z-index:100;background:var(--accent);color:#fff;padding:8px 14px;border-radius:8px}
.skip:focus{left:8px}
.wrap{width:100%;max-width:var(--max);margin:0 auto;padding:0 var(--gutter)}
.sec{position:relative;width:100%;padding:clamp(34px,4.2vw,54px) 0}
.sec.alt{background:var(--bg-2)}
.sec{content-visibility:auto;contain-intrinsic-size:auto 900px}
.eyebrow{display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border-radius:999px;background:var(--accent-soft);color:var(--accent);font:600 12px/1 "JetBrains Mono",monospace;letter-spacing:.06em;text-transform:uppercase}
.eyebrow::before{content:"";width:6px;height:6px;border-radius:50%;background:var(--accent);box-shadow:0 0 0 3px rgba(10,108,255,.18)}
.h2{font-size:clamp(28px,3.8vw,46px);margin-top:12px;max-width:20ch}
.lead{color:var(--muted);font-size:clamp(16px,1.6vw,19px);max-width:60ch;margin-top:12px}
.grad{background:linear-gradient(100deg,#0b1b2e 0%,#0b1b2e 35%,#0a6cff 70%,#00b4d8 100%);-webkit-background-clip:text;background-clip:text;color:transparent}

.grad-b{background:linear-gradient(100deg,#0a6cff 0%,#00b4d8 100%);-webkit-background-clip:text;background-clip:text;color:transparent}
.cyl-head{margin-top:14px}
/* buttons */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:46px;padding:0 22px;border-radius:999px;font-weight:600;font-size:15px;transition:transform .2s var(--ease),box-shadow .2s,background .2s,border-color .2s,color .2s;white-space:nowrap}
.btn svg{width:16px;height:16px}
.btn-p{background:var(--grad);color:#fff;box-shadow:0 10px 24px -10px rgba(10,108,255,.65)}
.btn-p:hover{transform:translateY(-2px);box-shadow:0 16px 30px -10px rgba(10,108,255,.7)}
.btn-g{background:#fff;color:var(--text);border:1px solid var(--line-2);box-shadow:var(--shadow-sm)}
.btn-g:hover{border-color:var(--accent);color:var(--accent);transform:translateY(-1px)}
.btn-w{background:#fff;color:#0a3d91}
.btn-w:hover{transform:translateY(-2px);box-shadow:0 12px 26px -10px rgba(0,0,0,.35)}

/* header */
.hdr{position:fixed;inset:0 0 auto;z-index:50;height:var(--hdr);background:rgba(255,255,255,.72);backdrop-filter:saturate(180%) blur(14px);-webkit-backdrop-filter:saturate(180%) blur(14px);border-bottom:1px solid transparent;transition:box-shadow .25s,border-color .25s,background .25s}
.hdr.is-scrolled{background:rgba(255,255,255,.92);border-color:var(--line);box-shadow:0 8px 24px -16px rgba(11,27,46,.25)}
.hdr .wrap{height:100%;display:flex;align-items:center;gap:24px}
.logo{display:flex;align-items:center;flex-shrink:0}
.logo img{height:38px;width:auto}
.nav{display:flex;align-items:center;gap:2px;margin:0 0 0 8px;padding:0}
.nav>li{list-style:none;position:relative}
.nav a,.nav .dd-btn{white-space:nowrap;display:flex;align-items:center;gap:6px;height:42px;padding:0 12px;border-radius:10px;color:var(--muted);font-size:15px;font-weight:500;transition:color .2s,background .2s}
.nav a:hover,.nav .dd-btn:hover,.nav li:focus-within>.dd-btn{color:var(--accent);background:var(--bg-3)}
.dd-btn svg{width:12px;height:12px;flex-shrink:0;opacity:.7}
.dd{position:absolute;top:calc(100% + 10px);left:-12px;min-width:250px;padding:8px;background:#fff;border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow-lg);opacity:0;visibility:hidden;transform:translateY(-6px);transition:.2s var(--ease)}
.nav li:hover>.dd,.nav li:focus-within>.dd,.nav li.open>.dd{opacity:1;visibility:visible;transform:none}
.dd-btn[aria-expanded="true"]{color:var(--accent);background:var(--bg-3)}
.dd-btn[aria-expanded="true"] svg{transform:rotate(180deg)}
.dd-btn svg{transition:transform .2s}
.dd a{height:auto;padding:10px 12px;display:block;color:var(--text)}
.dd small{display:block;color:var(--dim);font-size:12.5px;font-weight:400;margin-top:2px}
.dd-mega{left:0;right:0;top:calc(100% - 6px);min-width:0;padding:16px}
.dd-mega::before{content:"";position:absolute;left:0;right:0;top:-16px;height:16px}
.hdr .wrap{position:relative}
.nav>li.has-mega{position:static}
.mg-cols{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}
.mg-col{display:flex;flex-direction:column;gap:2px;padding:6px;border-radius:12px;background:var(--bg-2)}
.nav .dd .mg-brand{display:flex;align-items:center;gap:8px;flex-wrap:wrap;height:auto;padding:10px;white-space:normal;font-weight:700;font-size:15.5px;color:var(--text);border-radius:9px}
.mg-brand i{width:8px;height:8px;border-radius:50%;background:var(--c);box-shadow:0 0 0 4px color-mix(in srgb,var(--c) 18%,transparent);flex-shrink:0}
.nav .dd .mg-brand small{display:inline;margin:0 0 0 auto;font:600 11px/1 "JetBrains Mono",monospace;color:var(--dim)}
.nav .dd .mg-link{display:block;height:auto;padding:8px 10px;white-space:normal;line-height:1.35;font-size:14px;color:var(--muted);border-radius:9px}
.nav .dd .mg-brand:hover,.nav .dd .mg-link:hover{background:#fff;color:var(--accent)}
.nav .dd .mg-all{display:inline-flex;align-items:center;gap:6px;height:auto;margin-top:10px;padding:10px 12px;color:var(--accent);font-weight:700;font-size:14.5px}
.nav .dd .mg-all svg{width:16px;height:16px}
.nav .dd .mg-all:hover{background:var(--bg-3)}
.hdr-cta{margin-left:auto;display:flex;align-items:center;gap:10px}
.nav-toggle{display:none;width:46px;height:46px;border-radius:12px;border:1px solid var(--line-2);background:#fff;align-items:center;justify-content:center}
.nav-toggle span,.nav-toggle span::before,.nav-toggle span::after{display:block;width:18px;height:2px;border-radius:2px;background:var(--text);position:relative;transition:.25s var(--ease)}
.nav-toggle span::before,.nav-toggle span::after{content:"";position:absolute;left:0}
.nav-toggle span::before{top:-6px}.nav-toggle span::after{top:6px}
.nav-toggle[aria-expanded="true"] span{background:transparent}
.nav-toggle[aria-expanded="true"] span::before{top:0;transform:rotate(45deg)}
.nav-toggle[aria-expanded="true"] span::after{top:0;transform:rotate(-45deg)}

/* drawer */
.drawer{position:fixed;inset:var(--hdr) 0 0;z-index:49;background:#fff;padding:12px var(--gutter) calc(32px + env(safe-area-inset-bottom));overflow-y:auto;opacity:0;visibility:hidden;transform:translateY(-8px);transition:.25s var(--ease)}
.drawer.open{opacity:1;visibility:visible;transform:none}
.drawer a,.drawer summary{display:flex;align-items:center;justify-content:space-between;min-height:54px;border-bottom:1px solid var(--line);font-size:17px;font-weight:500;cursor:pointer;list-style:none}
.drawer summary::-webkit-details-marker{display:none}
.drawer summary svg{transition:transform .2s}
.drawer details[open] summary{color:var(--accent)}
.drawer details[open] summary svg{transform:rotate(180deg)}
.drawer details a{font-size:15.5px;color:var(--muted);padding-left:14px;min-height:48px;font-weight:400}
.drawer .btn{width:100%;margin-top:22px;border-bottom:0;justify-content:center}

/* hero */
.hero{position:relative;min-height:auto;display:flex;align-items:center;padding:calc(var(--hdr) + 40px) 0 48px;overflow:hidden;isolation:isolate;background:linear-gradient(180deg,#f3f8ff 0%,#fff 70%)}
.hero::before{content:"";position:absolute;inset:-10%;z-index:-2;background:
  radial-gradient(40% 45% at 78% 30%,rgba(10,108,255,.22),transparent 70%),
  radial-gradient(35% 40% at 92% 75%,rgba(0,180,216,.20),transparent 70%),
  radial-gradient(30% 35% at 8% 20%,rgba(0,180,216,.12),transparent 70%),
  radial-gradient(25% 30% at 40% 95%,rgba(255,107,44,.10),transparent 70%);
  animation:drift 18s ease-in-out infinite alternate}
@keyframes drift{to{transform:translate3d(-2%,2%,0) scale(1.04)}}
.hero::after{content:"";position:absolute;inset:0;z-index:-1;background-image:linear-gradient(rgba(10,60,140,.06) 1px,transparent 1px),linear-gradient(90deg,rgba(10,60,140,.06) 1px,transparent 1px);background-size:56px 56px;mask-image:radial-gradient(70% 60% at 60% 40%,#000 25%,transparent 80%);-webkit-mask-image:radial-gradient(70% 60% at 60% 40%,#000 25%,transparent 80%)}
.hero-grid{display:grid;grid-template-columns:1.05fr .95fr;gap:clamp(28px,4vw,64px);align-items:center}
.pill{display:inline-flex;align-items:center;gap:10px;padding:6px 14px 6px 6px;border:1px solid var(--line);border-radius:999px;font-size:13.5px;color:var(--muted);background:#fff;box-shadow:var(--shadow-sm)}
.pill b{background:var(--grad);color:#fff;font-weight:600;padding:4px 10px;border-radius:999px;font-size:12px}
.hero .h1-kw{margin-top:20px;font-size:clamp(15px,1.5vw,18px);line-height:1.4;font-weight:700;letter-spacing:0;color:#0a3d91}
.hero .display{font-size:clamp(36px,5.6vw,72px);line-height:1.08;margin-top:10px;letter-spacing:-.035em;font-weight:800}
.lead strong{color:var(--text);font-weight:600}
.hero .lead{margin-top:22px}
.hero-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:26px}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:32px;max-width:660px}
.stat{padding:16px;background:#fff;border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow-sm)}
.stat strong{display:block;font-size:clamp(24px,2.6vw,32px);font-weight:800;letter-spacing:-.02em;background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.stat span{font-size:13px;color:var(--muted)}
.hero-art{position:relative}
.cyl-card{position:relative;border:1px solid var(--line);border-radius:var(--radius-lg);background:#fff;padding:clamp(18px,3vw,32px);box-shadow:var(--shadow-lg)}
.cyl-card::before{content:"";position:absolute;inset:0 0 auto;height:4px;border-radius:var(--radius-lg) var(--radius-lg) 0 0;background:var(--grad)}
.cyl-head{display:flex;justify-content:space-between;font:600 11px/1 "JetBrains Mono",monospace;color:var(--dim);letter-spacing:.06em;text-transform:uppercase}
.cyl-head i{font-style:normal;color:var(--ok)}
.cyl{width:100%;height:auto;margin:22px 0}
.cyl .stroke{animation:stroke 4.8s var(--ease) infinite}
.cyl .p-in{animation:portA 4.8s linear infinite}
.cyl .p-out{animation:portB 4.8s linear infinite}
.cyl .flow{stroke-dasharray:4 8;animation:flow 1s linear infinite}
@keyframes stroke{0%,8%{transform:translateX(0)}42%,58%{transform:translateX(150px)}92%,100%{transform:translateX(0)}}
@keyframes portA{0%,8%{opacity:1}42%,100%{opacity:.2}}
@keyframes portB{0%,50%{opacity:.2}58%,92%{opacity:1}100%{opacity:.2}}
@keyframes flow{to{stroke-dashoffset:-24}}
.cyl-spec{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
.cyl-spec div{background:var(--bg-2);border-radius:12px;padding:12px 14px}
.cyl-spec small{display:block;font:600 10.5px/1 "JetBrains Mono",monospace;color:var(--dim);text-transform:uppercase;letter-spacing:.06em}
.cyl-spec b{display:block;margin-top:6px;font-weight:700;font-size:15px}
.float{position:absolute;display:flex;align-items:center;gap:10px;padding:10px 14px;background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:var(--shadow);font-size:13.5px;font-weight:600;z-index:2;animation:bob 6s ease-in-out infinite}
.float i{width:30px;height:30px;border-radius:9px;display:grid;place-items:center;font-style:normal;color:#fff;font-size:15px}
.float.f1{top:-18px;left:-22px}.float.f1 i{background:linear-gradient(135deg,#10b981,#34d399)}
.float.f2{bottom:-20px;right:-14px;animation-delay:-3s}.float.f2 i{background:linear-gradient(135deg,#ff6b2c,#ffa057)}
@keyframes bob{50%{transform:translateY(-8px)}}

/* logo şeridi */
.strip{position:relative;background:#fff;border-top:1px solid var(--line);border-bottom:1px solid var(--line);padding:22px 0;overflow:hidden;mask-image:linear-gradient(90deg,transparent,#000 6%,#000 94%,transparent);-webkit-mask-image:linear-gradient(90deg,transparent,#000 6%,#000 94%,transparent)}
.strip-track{display:flex;gap:18px;width:max-content;animation:marq 38s linear infinite}
.strip:hover .strip-track,.strip:focus-within .strip-track{animation-play-state:paused}
.logo-tile{flex:0 0 auto;display:grid;place-items:center;width:250px;height:110px;padding:10px 16px;overflow:hidden;background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow-sm);transition:transform .25s var(--ease),border-color .25s,box-shadow .25s}
.logo-tile:hover,.logo-tile:focus-visible{transform:translateY(-3px);border-color:var(--accent);box-shadow:var(--shadow)}
.logo-tile{position:relative}
.logo-tile img{position:absolute;top:10px;left:16px;width:calc(100% - 32px);height:calc(100% - 20px);max-width:none;object-fit:cover;object-position:center;transform:scale(1.15);mix-blend-mode:multiply}
.logo-tile .logo-txt{font-size:24px;font-weight:800;letter-spacing:-.02em;color:var(--text)}
.logo-tile.is-clean img{position:static;width:100%;height:100%;max-height:76px;object-fit:contain;transform:none;mix-blend-mode:multiply}
.logo-txt{font-weight:800;font-size:17px;line-height:1.15;letter-spacing:-.01em;color:var(--text);text-align:center}
@keyframes marq{to{transform:translateX(-50%)}}

/* brands */
.sec-head{display:flex;justify-content:space-between;align-items:flex-end;gap:24px;flex-wrap:wrap}
.brands{display:grid;grid-template-columns:repeat(2,1fr);gap:18px;margin-top:30px}
.brand{--c:var(--accent);position:relative;display:flex;flex-direction:column;border:1px solid var(--line);border-radius:var(--radius-lg);background:#fff;padding:clamp(24px,3vw,36px);overflow:hidden;box-shadow:var(--shadow-sm);transition:border-color .3s,transform .3s var(--ease),box-shadow .3s}
.brand::after{content:"";position:absolute;inset:0 0 auto;height:5px;background:var(--c)}
.brand::before{content:"";position:absolute;inset:0;background:radial-gradient(420px 220px at var(--mx,85%) 0%,rgba(10,108,255,.10),transparent 70%);background:radial-gradient(420px 220px at var(--mx,85%) 0%,color-mix(in srgb,var(--c) 16%,transparent),transparent 70%);opacity:.6;transition:opacity .3s;pointer-events:none}
.brand:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg);border-color:transparent}
.brand:hover::before{opacity:1}
.brand>*{position:relative}
.brand-top{display:flex;justify-content:space-between;align-items:center;font:600 12px/1 "JetBrains Mono",monospace;color:var(--dim);letter-spacing:.05em;text-transform:uppercase}
.brand-top .code{display:inline-block;margin-right:8px;padding:6px 9px;border-radius:8px;color:#fff;background:var(--c)}
.brand-logo{display:block;width:auto;max-width:150px;height:auto;max-height:44px;object-fit:contain}
.brand h3{font-size:clamp(28px,3vw,38px);margin-top:26px;font-weight:800}
.brand h3 a:hover{color:var(--c)}
.brand p{color:var(--muted);margin-top:10px;max-width:46ch}
.chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:24px}
.chip{display:inline-flex;align-items:center;min-height:36px;padding:0 13px;border:1px solid var(--line);border-radius:999px;font-size:13.5px;color:var(--muted);background:var(--bg-2);transition:.2s}
.chip:hover{color:#fff;background:var(--c);border-color:var(--c)}
.brand-link{margin-top:auto;padding-top:28px;display:inline-flex;align-items:center;gap:8px;font-weight:600;color:var(--c)}
.brand-link svg{width:16px;height:16px;transition:transform .2s}
.brand:hover .brand-link svg{transform:translateX(4px)}


/* popüler ürün grupları */
.gf{display:flex;flex-wrap:wrap;gap:8px;margin-top:22px}
.gf button{--c:var(--accent);display:inline-flex;align-items:center;gap:8px;min-height:38px;padding:0 15px;border:1px solid var(--line);border-radius:999px;background:#fff;font:600 13.5px/1 Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:var(--muted);cursor:pointer;transition:color .2s,border-color .2s,background .2s,box-shadow .2s}
.gf button i{width:8px;height:8px;border-radius:50%;background:var(--c)}
.gf button:hover{border-color:var(--c);color:var(--text)}
.gf button[aria-pressed="true"]{background:var(--text);border-color:var(--text);color:#fff;box-shadow:0 8px 18px -10px rgba(11,27,46,.6)}
.gf button[aria-pressed="true"] i{box-shadow:0 0 0 2px rgba(255,255,255,.35)}
.groups{list-style:none;margin:22px 0 0;padding:0;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.groups li[hidden]{display:none}
.groups a{--c:var(--accent);position:relative;display:flex;flex-direction:column;height:100%;padding:8px;background:#fff;border:1px solid var(--line);border-radius:20px;box-shadow:var(--shadow-sm);overflow:hidden;transition:transform .3s var(--ease),border-color .3s,box-shadow .3s}
.groups a::after{content:"";position:absolute;left:0;bottom:0;height:3px;width:0;background:var(--c);transition:width .4s var(--ease)}
.g-img{position:relative;display:block;aspect-ratio:4/3;border-radius:14px;overflow:hidden;isolation:isolate;
  background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 300' fill='none' stroke='%236f97c8' stroke-width='.8'%3E %3Cg opacity='.55'%3E %3Crect x='22' y='24' width='96' height='40' rx='3'/%3E%3Cpath d='M118 34h26v20h-26M30 24v40M110 24v40M144 44h22'/%3E %3Cpath d='M22 76h96M22 72v8M118 72v8' stroke-dasharray='0'/%3E%3Cpath d='M26 76l-4-2v4zM114 76l4-2v4z' fill='%236f97c8'/%3E %3Ccircle cx='338' cy='62' r='34'/%3E%3Ccircle cx='338' cy='62' r='20'/%3E%3Ccircle cx='338' cy='62' r='5'/%3E %3Cpath d='M296 62h84M338 20v84' stroke-dasharray='6 3 1 3'/%3E %3Cpath d='M290 240h86M290 236v8M376 236v8'/%3E%3Cpath d='M294 240l-4-2v4zM372 240l4-2v4z' fill='%236f97c8'/%3E %3Crect x='24' y='214' width='70' height='52'/%3E%3Cpath d='M24 240h70M59 214v52M38 228l14 12-14 12M80 228l-14 12 14 12'/%3E %3Cpath d='M110 252h40M150 246l10 6-10 6M110 252v-26h22'/%3E %3Cpath d='M0 150h400M200 0v300' stroke-dasharray='10 4 2 4' opacity='.6'/%3E %3Cpath d='M246 270h50M246 276h34M246 282h42' opacity='.7'/%3E %3Cpath d='M30 112h44M30 118h30' opacity='.7'/%3E %3C/g%3E%3C/svg%3E") center/100% 100% no-repeat,radial-gradient(58% 55% at 50% 55%,rgba(255,255,255,.95),rgba(255,255,255,0) 78%),linear-gradient(rgba(10,108,255,.07) 1px,transparent 1px) 0 0/16px 16px,linear-gradient(90deg,rgba(10,108,255,.07) 1px,transparent 1px) 0 0/16px 16px,linear-gradient(160deg,#eef4fb 0%,#dce8f6 100%)}
.g-img img{position:relative;z-index:1;width:100%;height:100%;object-fit:contain;padding:18px;mix-blend-mode:multiply;transition:transform .5s var(--ease)}
.g-img.full img{object-fit:cover;padding:0;mix-blend-mode:normal}
.g-img.noimg::after{content:"";position:absolute;inset:0;margin:auto;width:56px;height:56px;border-radius:16px;background:var(--grad);opacity:.18}
.gp-tag{position:absolute;z-index:2;left:10px;top:10px;display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.92);box-shadow:0 2px 8px rgba(11,27,46,.08);font:700 10.5px/1 "JetBrains Mono",monospace;letter-spacing:.03em;color:var(--text);-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px)}
.gp-tag i{width:7px;height:7px;border-radius:50%;background:var(--c)}
.g-no{position:absolute;z-index:2;right:12px;top:12px;font:700 11px/1 "JetBrains Mono",monospace;color:rgba(11,27,46,.35)}
.g-body{flex:1;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 8px 8px}
.groups b{display:block;font-weight:700;font-size:15.5px;line-height:1.3;letter-spacing:-.01em}
.groups small{display:block;margin-top:5px;font-size:12.5px;color:var(--dim)}
.g-go{flex-shrink:0;display:grid;place-items:center;width:36px;height:36px;border-radius:50%;background:var(--bg-2);color:var(--c);transition:background .25s,color .25s,transform .25s}
.groups svg{width:16px;height:16px}
.groups a:hover{transform:translateY(-4px);border-color:transparent;box-shadow:0 22px 40px -22px rgba(11,27,46,.35),0 0 0 1.5px var(--c)}
.groups a:hover::after{width:100%}
.groups a:hover img{transform:scale(1.06)}
.groups a:hover .g-go{background:var(--c);color:#fff;transform:translateX(2px)}
.c-item:nth-child(5) .ic{background:linear-gradient(135deg,#f59e0b,#fbbf24)}
.c-note{display:block;font-style:normal;font-size:13px;color:var(--dim);margin-top:2px}
.open-badge{display:inline-block;margin-left:6px;padding:3px 7px;border-radius:999px;font-style:normal;font-size:10px;letter-spacing:.04em;vertical-align:1px}
.open-badge.on{background:rgba(16,185,129,.14);color:#059669}
.open-badge.off{background:var(--bg-3);color:var(--dim)}
/* ===== çerez bandı (KVKK) ===== */
.ck{position:fixed;left:16px;right:16px;bottom:16px;z-index:9000;display:flex;justify-content:center;pointer-events:none;font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
.ck[hidden]{display:none}
.ck-in{pointer-events:auto;display:flex;align-items:center;gap:18px 24px;width:100%;max-width:980px;padding:18px 20px;border:1px solid #e3eaf3;border-radius:18px;background:rgba(255,255,255,.97);-webkit-backdrop-filter:blur(12px);backdrop-filter:blur(12px);box-shadow:0 24px 60px -24px rgba(11,27,46,.45);animation:ckIn .45s cubic-bezier(.2,.7,.2,1)}
@keyframes ckIn{from{opacity:0;transform:translateY(16px)}}
.ck-txt{flex:1;min-width:0}
.ck-txt b{display:block;font-size:15.5px;font-weight:700;color:#0b1b2e}
.ck-txt p{margin:4px 0 0;font-size:14px;line-height:1.55;color:#4a5b70}
.ck-txt a{color:#0a6cff;font-weight:600;text-decoration:underline;text-underline-offset:2px}
.ck-acts{display:flex;gap:10px;flex-shrink:0}
.ck-b{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0 18px;border-radius:999px;font:600 14.5px/1 Inter,system-ui,sans-serif;cursor:pointer;white-space:nowrap;transition:transform .2s,box-shadow .2s,border-color .2s}
.ck-no{background:#fff;color:#0b1b2e;border:1px solid #cfd9e6}
.ck-no:hover{border-color:#0a6cff;color:#0a6cff}
.ck-yes{border:0;color:#fff;background:linear-gradient(115deg,#0a6cff 0%,#1f8bff 45%,#00b4d8 100%);box-shadow:0 10px 24px -10px rgba(10,108,255,.65)}
.ck-yes:hover{transform:translateY(-1px)}
.ck-b:focus-visible{outline:2px solid #0a6cff;outline-offset:3px}
@media (max-width:768px){
  .ck{left:10px;right:10px;bottom:calc(70px + env(safe-area-inset-bottom))}
  .ck-in{flex-direction:column;align-items:stretch;padding:16px}
  .ck-acts .ck-b{flex:1}
}
/* formlardaki KVKK onay kutusu */
.kvkk-ok{display:flex;align-items:flex-start;gap:10px;margin-top:14px;font-size:13.5px;line-height:1.5;color:#4a5b70;cursor:pointer}
.kvkk-ok input{flex-shrink:0;width:20px;height:20px;margin:1px 0 0;accent-color:#0a6cff;cursor:pointer}
.kvkk-ok a{color:#0a6cff;font-weight:600;text-decoration:underline;text-underline-offset:2px}
/* ===== hero: teknik resim paftası (kalem çizer, sonra mil/taşıyıcı çalışır) ===== */
.hs-card{position:relative;margin:0;padding:clamp(10px,1.4vw,16px);border:1px solid var(--line);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);background:#fbfbf8;
  background-image:linear-gradient(rgba(17,17,17,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(17,17,17,.045) 1px,transparent 1px),linear-gradient(rgba(17,17,17,.02) 1px,transparent 1px),linear-gradient(90deg,rgba(17,17,17,.02) 1px,transparent 1px);
  background-size:80px 80px,80px 80px,16px 16px,16px 16px}
.hs-card::before{content:"";position:absolute;inset:0 0 auto;height:4px;border-radius:var(--radius-lg) var(--radius-lg) 0 0;background:var(--grad)}
.hs{display:block;width:100%;height:auto;overflow:visible}
.hs .thin{stroke-width:1.3}
.hs .frame{stroke-width:1.8}.hs .frame.thin{stroke-width:.9}
.hs .hid{stroke-width:1.3;stroke:#7a7a7a;stroke-dasharray:7 6}
.hs .cl{stroke:#9a9a9a;stroke-width:1.1;stroke-dasharray:24 6 4 6}
.hs .dim{stroke:#555;stroke-width:1.4}
.hs text{fill:#111;stroke:none;transition:opacity .45s}
.hs-lbl{font:700 22px/1 "JetBrains Mono",ui-monospace,monospace;letter-spacing:.12em}
.hs-dim{font:600 18px/1 "JetBrains Mono",ui-monospace,monospace;letter-spacing:.05em;fill:#444!important}
.tb-t{font:600 12px/1 "JetBrains Mono",ui-monospace,monospace;letter-spacing:.12em;fill:#777!important}
.tb-v{font:700 16px/1 "JetBrains Mono",ui-monospace,monospace;letter-spacing:.03em}
.tb-logo{filter:brightness(0);transition:opacity .5s}
.hs-pen{transition:opacity .4s}
.hs-pen.off,.hs:not(.drawing) .hs-pen{opacity:0}
/* JS varsa çizim gizli başlar; JS çalışmazsa 4 sn sonra kendiliğinden görünür */
.js .hs:not(.armed) .ln,.js .hs:not(.armed) text,.js .hs:not(.armed) .tb-logo{opacity:0;animation:hsShow .01s linear 4s forwards}
.hs.armed text,.hs.armed .tb-logo{opacity:0}
.hs.armed text.on,.hs.armed .tb-logo.on{opacity:1}
@keyframes hsShow{to{opacity:1}}
.hs-rod,.hs-car{transition:transform 1s cubic-bezier(.6,0,.2,1)}
.hs.live .hs-rod{animation:hsRod 4.8s cubic-bezier(.6,0,.2,1) infinite}
.hs.live .hs-car{animation:hsCar 6s cubic-bezier(.6,0,.2,1) infinite}
@keyframes hsRod{0%,8%{transform:translateX(0)}42%,58%{transform:translateX(110px)}92%,100%{transform:translateX(0)}}
@keyframes hsCar{0%,6%{transform:translateX(0)}44%,56%{transform:translateX(450px)}94%,100%{transform:translateX(0)}}
.hs-cap{display:flex;justify-content:space-between;align-items:center;margin:10px 4px 0;font:600 11px/1 "JetBrains Mono",monospace;color:var(--dim);letter-spacing:.06em;text-transform:uppercase}
.hs-cap i{font-style:normal;color:var(--ok)}
@media (max-width:700px){ .hs .tb,.hs .frame{display:none} .hs-lbl{font-size:26px} .hs-dim{font-size:22px} .hs .ln{stroke-width:2.8} .hs .thin,.hs .hid,.hs .dim{stroke-width:1.7} }
@media (prefers-reduced-motion:reduce){ .hs.live .hs-rod,.hs.live .hs-car{animation:none} }
/* ===== intro ===== */
.intro-ov{display:none}
html.intro body{overflow:hidden}
html.intro .intro-ov{position:fixed;inset:0;z-index:9999;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:clamp(10px,2vh,22px);padding:24px 6px;overflow:hidden;background:#fff;
  background-image:linear-gradient(rgba(17,17,17,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(17,17,17,.045) 1px,transparent 1px);background-size:28px 28px;
  transition:transform .7s cubic-bezier(.7,0,.3,1),opacity .7s;
  animation:itFailsafe .01s linear 7s forwards}
@keyframes itFailsafe{to{visibility:hidden;pointer-events:none}}
html.intro .intro-ov.out{transform:translateY(-100%);opacity:.6}
.intro-art{width:min(980px,96vw);height:auto;max-height:64vh}
.intro-art .ln{stroke-dasharray:1;stroke-dashoffset:1;animation:itDraw 1.25s cubic-bezier(.6,0,.2,1) forwards}
.intro-art .thin{stroke-width:1.2}
.intro-art .hid{stroke-width:1.2;stroke:#777}
.intro-art .cl{stroke:#9a9a9a;stroke-width:1;animation-duration:.9s}
.intro-art .dim{stroke:#555;stroke-width:1.2;animation-delay:.75s}
.intro-art .hid{animation-delay:.45s}
.intro-art .it-car .ln,.intro-art .it-rod .ln{animation-delay:.3s}
.intro-art text{fill:#111;stroke:none;opacity:0;animation:itFade .5s ease forwards}
.it-lbl{font:700 15px/1 "JetBrains Mono",ui-monospace,monospace;letter-spacing:.14em;animation-delay:.2s!important}
.it-dim{font:600 13px/1 "JetBrains Mono",ui-monospace,monospace;letter-spacing:.06em;fill:#444!important;animation-delay:1.1s!important}
.it-rod{animation:itRod 1.5s cubic-bezier(.6,0,.2,1) 1.35s forwards}
.it-car{animation:itCar 1.6s cubic-bezier(.6,0,.2,1) 1.35s forwards}
.intro-brand{display:flex;flex-direction:column;align-items:center;gap:8px;opacity:0;transform:translateY(8px);animation:itUp .6s ease 2.1s forwards}
.intro-brand img{height:clamp(30px,5vw,40px);width:auto;filter:brightness(0)}
.intro-brand span{font:600 12.5px/1.3 "JetBrains Mono",ui-monospace,monospace;letter-spacing:.12em;text-transform:uppercase;color:#444;text-align:center}
.intro-skip{position:absolute;top:max(16px,env(safe-area-inset-top));right:16px;display:inline-flex;align-items:center;gap:6px;min-height:44px;padding:0 18px;border:1px solid #111;border-radius:999px;background:#fff;color:#111;font:600 14px/1 Inter,system-ui,sans-serif;cursor:pointer;transition:background .2s,color .2s}
.intro-skip:hover{background:#111;color:#fff}
.intro-skip span{font-size:18px;line-height:1}
.intro-bar{position:absolute;left:0;bottom:0;height:3px;width:100%;background:#111;transform-origin:left;transform:scaleX(0);animation:itBar 3.4s linear forwards}
@keyframes itDraw{to{stroke-dashoffset:0}}
@keyframes itFade{to{opacity:1}}
@keyframes itRod{0%{transform:translateX(0)}100%{transform:translateX(110px)}}
@keyframes itCar{0%{transform:translateX(0)}100%{transform:translateX(450px)}}
@keyframes itUp{to{opacity:1;transform:none}}
@keyframes itBar{to{transform:scaleX(1)}}
@media (max-width:560px){ .intro-art{width:calc(100vw - 12px);max-height:60vh} .it-lbl{font-size:26px;letter-spacing:.08em} .it-dim{font-size:22px} .intro-art .ln{stroke-width:2.6} .intro-art .thin,.intro-art .hid,.intro-art .dim{stroke-width:1.6} .intro-brand span{font-size:11px;max-width:30ch} }

/* sektörler */
.sectors .chips{margin-top:16px}
.sectors .chip{background:#fff}
.sectors .area:nth-child(n){--c:var(--accent)}
.sectors .area:nth-child(1){--c:#0a6cff}.sectors .area:nth-child(2){--c:#00b4d8}.sectors .area:nth-child(3){--c:#f59e0b}
.sectors .area:nth-child(4){--c:#e11d48}.sectors .area:nth-child(5){--c:#10b981}.sectors .area:nth-child(6){--c:#ff6b2c}

/* kuvvet hesaplayıcı */
.calc{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:30px;align-items:start}
.calc-in{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin:0;padding:clamp(20px,3vw,28px);background:#fff;border:1px solid var(--line);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm)}
.calc-in label{display:flex;flex-direction:column;gap:8px;font-size:13.5px;font-weight:600;color:var(--muted)}
.calc-in label.full{grid-column:1/-1;flex-direction:row;flex-wrap:wrap;align-items:center;column-gap:6px}
.calc-in label.full input[type=range]{flex:1 1 100%}
.calc-in select,.calc-in input[type=number]{height:48px;width:100%;padding:0 14px;border:1px solid var(--line-2);border-radius:12px;background:#fff;color:var(--text);font:600 16px/1 Inter,system-ui,sans-serif;transition:border-color .2s,box-shadow .2s}
.calc-in select:focus,.calc-in input[type=number]:focus{outline:0;border-color:var(--accent);box-shadow:0 0 0 4px rgba(10,108,255,.12)}
.calc-in input[type=range]{width:100%;height:28px;accent-color:var(--accent);cursor:pointer}
.calc-in output{display:inline-block;margin-left:2px;padding:3px 9px;border-radius:999px;background:var(--accent-soft);color:var(--accent);font:700 13px/1.2 "JetBrains Mono",monospace}
.calc-hint{grid-column:1/-1;margin:0;font-size:13px;color:var(--dim)}
.calc-out{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.res{padding:20px;border-radius:var(--radius);color:#fff;box-shadow:0 14px 28px -16px rgba(11,27,46,.45)}
.res small{display:block;font:600 11px/1.2 "JetBrains Mono",monospace;text-transform:uppercase;letter-spacing:.05em;opacity:.9}
.res b{display:block;margin-top:8px;font-size:clamp(24px,2.6vw,32px);font-weight:800;letter-spacing:-.02em;line-height:1.1}
.res span{display:block;margin-top:4px;font-size:13px;opacity:.88}
.res.r1{background:linear-gradient(135deg,#0a6cff,#3d8bff)}
.res.r2{background:linear-gradient(135deg,#0096c7,#00b4d8)}
.res.r3{background:linear-gradient(135deg,#059669,#10b981)}
.res.r4{background:linear-gradient(135deg,#f0581c,#ff8a4c)}
.calc-out .btn{grid-column:1/-1;margin-top:4px}
.calc-note{grid-column:1/-1;margin:0;font-size:13px;color:var(--dim)}
/* areas */
.areas{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:30px}
.area{--c:var(--accent);background:#fff;border:1px solid var(--line);border-radius:var(--radius-lg);padding:clamp(24px,3vw,32px);box-shadow:var(--shadow-sm);transition:transform .3s var(--ease),box-shadow .3s}
.area:nth-child(1){--c:#0a6cff}.area:nth-child(2){--c:#00b4d8}.area:nth-child(3){--c:#10b981}
.area:nth-child(4){--c:#ff6b2c}.area:nth-child(5){--c:#f59e0b}.area:nth-child(6){--c:#e11d48}
.area{position:relative;display:flex;flex-direction:column;overflow:hidden;color:var(--text)}
.area::before{content:"";position:absolute;inset:0 0 auto;height:4px;background:var(--c);transform:scaleX(0);transform-origin:left;transition:transform .35s var(--ease)}
.area::after{content:"";position:absolute;right:-60px;top:-60px;width:180px;height:180px;border-radius:50%;background:var(--c);opacity:.06;transition:opacity .3s,transform .4s var(--ease)}
.area:hover{transform:translateY(-4px);box-shadow:var(--shadow);border-color:transparent}
.area:hover::before{transform:scaleX(1)}
.area:hover::after{opacity:.12;transform:scale(1.15)}
.area-go{display:inline-flex;align-items:center;gap:6px;margin-top:auto;padding-top:18px;font-size:14.5px;font-weight:600;color:var(--c)}
.area-go svg{width:15px;height:15px;transition:transform .2s}
.area:hover .area-go svg{transform:translateX(4px)}
.area .ic{width:52px;height:52px;border-radius:14px;display:grid;place-items:center;color:#fff;background:var(--c);box-shadow:0 10px 20px -10px var(--c)}
.area .ic svg{width:24px;height:24px}
.area h3{font-size:20px;margin-top:22px}
.area p{color:var(--muted);font-size:15px;margin-top:8px}

/* why + steps */
.split{display:grid;grid-template-columns:.9fr 1.1fr;gap:clamp(24px,5vw,72px);align-items:start}
.why{display:grid;gap:14px;margin:8px 0 0;padding:0}
@media (min-width:1025px){ .split>div:first-child{position:sticky;top:calc(var(--hdr) + 24px)} }
.help{display:grid;grid-template-columns:auto 1fr;gap:14px 16px;align-items:center;margin-top:26px;padding:20px;border:1px solid var(--line);border-radius:var(--radius-lg);background:linear-gradient(135deg,#fff 0%,#f3f8ff 100%);box-shadow:var(--shadow-sm)}
.help-ic{display:grid;place-items:center;width:48px;height:48px;border-radius:14px;background:var(--grad);color:#fff;box-shadow:0 10px 20px -10px rgba(10,108,255,.7)}
.help-ic svg{width:24px;height:24px}
.help b{display:block;font-size:16.5px;font-weight:700}
.help small{display:block;margin-top:2px;font-size:13.5px;color:var(--muted)}
.help-acts{grid-column:1/-1;display:flex;flex-wrap:wrap;gap:10px}
.help-acts .btn{flex:1 1 160px;min-height:46px}
.why li{transition:border-color .2s,transform .2s var(--ease),box-shadow .2s}
.why li:hover{border-color:rgba(10,108,255,.35);transform:translateX(4px);box-shadow:var(--shadow)}
.why li{list-style:none;display:grid;grid-template-columns:auto 1fr;gap:18px;padding:22px;border:1px solid var(--line);border-radius:var(--radius);background:#fff;box-shadow:var(--shadow-sm)}
.why .n{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;background:var(--grad);color:#fff;font:700 13px/1 "JetBrains Mono",monospace}
.why h3{font-size:18px}
.why p{color:var(--muted);font-size:15px;margin-top:6px}
.steps{position:relative;display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-top:40px;counter-reset:s}
.steps::before{content:"";position:absolute;left:23px;right:calc(25% - 4px);top:22px;height:2px;background:linear-gradient(90deg,#0a6cff,#00b4d8,#10b981,#ff6b2c);opacity:.35}
.step{position:relative;padding:0 6px}
.step::before{counter-increment:s;content:"0" counter(s);position:relative;display:grid;place-items:center;width:46px;height:46px;border-radius:50%;background:#fff;border:2px solid var(--accent);color:var(--accent);font:700 13px/1 "JetBrains Mono",monospace;box-shadow:0 0 0 6px var(--bg)}
.step:nth-child(2)::before{border-color:#00b4d8;color:#00b4d8}
.step:nth-child(3)::before{border-color:#10b981;color:#10b981}
.step:nth-child(4)::before{border-color:#ff6b2c;color:#ff6b2c}
.step h3{font-size:18px;margin-top:18px}
.step p{color:var(--muted);font-size:15px;margin-top:6px}

/* facts + faq */
.facts{position:relative;border:1px solid var(--line);border-radius:var(--radius-lg);background:#fff;padding:clamp(22px,3vw,32px);box-shadow:var(--shadow);overflow:hidden}
.facts::before{content:"";position:absolute;inset:0 auto 0 0;width:5px;background:var(--grad)}
.facts dl{display:grid;grid-template-columns:auto 1fr;margin:18px 0 0}
.facts dt,.facts dd{padding:12px 0;border-top:1px solid var(--line);margin:0;font-size:15px}
.facts dd a{color:var(--accent)}
.facts dt{color:var(--dim);padding-right:20px;font:600 12px/1.9 "JetBrains Mono",monospace;text-transform:uppercase;letter-spacing:.04em}
.faq{margin-top:22px;display:grid;gap:12px}
.faq details{background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:0 22px;box-shadow:var(--shadow-sm);transition:border-color .2s}
.faq details[open]{border-color:rgba(10,108,255,.35)}
.faq summary{display:flex;justify-content:space-between;align-items:center;gap:16px;min-height:64px;padding:16px 0;font-size:17px;font-weight:600;cursor:pointer;list-style:none}
.faq summary::-webkit-details-marker{display:none}
.faq summary:hover{color:var(--accent)}
.faq summary i{flex-shrink:0;width:30px;height:30px;border-radius:50%;background:var(--accent-soft);position:relative}
.faq summary i::before,.faq summary i::after{content:"";position:absolute;left:50%;top:50%;width:11px;height:2px;border-radius:2px;background:var(--accent);transform:translate(-50%,-50%);transition:transform .25s}
.faq summary i::after{transform:translate(-50%,-50%) rotate(90deg)}
.faq details[open] summary i::after{transform:translate(-50%,-50%) rotate(0)}
.faq details p{color:var(--muted);padding:0 0 22px}

/* cta */
.cta{text-align:center;overflow:hidden;background:var(--grad);color:#fff}
.cta::before,.cta::after{content:"";position:absolute;border-radius:50%;pointer-events:none}
.cta::before{width:520px;height:520px;right:-160px;top:-220px;background:rgba(255,255,255,.12)}
.cta::after{width:380px;height:380px;left:-140px;bottom:-200px;background:rgba(255,255,255,.08)}
.btn-wrap{white-space:normal;text-align:left;line-height:1.35;min-height:46px;height:auto;padding:12px 22px;max-width:100%}
.cta .wrap{position:relative;z-index:1}
.cta-alt{display:flex;flex-wrap:wrap;justify-content:center;align-items:center;gap:8px 14px;margin-top:22px;font-size:15px;color:rgba(255,255,255,.75)}
.cta-alt a{display:inline-flex;align-items:center;gap:8px;min-height:44px;color:#fff;font-weight:600;border-bottom:1px solid rgba(255,255,255,.35);transition:border-color .2s}
.cta-alt a:hover{border-color:#fff}
.cta-alt svg{width:17px;height:17px}
.cta .eyebrow{background:rgba(255,255,255,.18);color:#fff}
.cta .eyebrow::before{background:#fff;box-shadow:0 0 0 3px rgba(255,255,255,.3)}
.cta .h2{margin:16px auto 0;max-width:20ch;color:#fff}
.cta .grad{background:none;-webkit-text-fill-color:#fff;color:#fff}
.cta .lead{margin:16px auto 0;color:rgba(255,255,255,.88)}
.cta .hero-actions{justify-content:center}
.cta .btn-p{background:#fff;color:#0a4fc4;box-shadow:0 14px 30px -12px rgba(0,0,0,.35)}
.cta .btn-g{background:transparent;color:#fff;border-color:rgba(255,255,255,.6);box-shadow:none}
.cta .btn-g:hover{background:rgba(255,255,255,.12);color:#fff;border-color:#fff}

/* contact */
.contact{display:grid;grid-template-columns:.9fr 1.1fr;gap:20px;margin-top:30px}
.c-list{display:grid;gap:12px;font-style:normal}
.c-item{display:flex;gap:16px;align-items:center;background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:18px 20px;box-shadow:var(--shadow-sm);transition:transform .2s var(--ease),border-color .2s}
a.c-item:hover{transform:translateX(4px);border-color:var(--accent)}
.c-item .ic{width:46px;height:46px;flex-shrink:0;border-radius:13px;display:grid;place-items:center;color:#fff;background:var(--grad)}
.c-item:nth-child(2) .ic{background:linear-gradient(135deg,#16a34a,#25d366)}
.c-item:nth-child(3) .ic{background:linear-gradient(135deg,#ff6b2c,#ffa057)}
.c-item:nth-child(4) .ic{background:linear-gradient(135deg,#e11d48,#fb7185)}
.c-item .ic svg{width:20px;height:20px}
.c-item small{display:block;font:600 11px/1 "JetBrains Mono",monospace;color:var(--dim);text-transform:uppercase;letter-spacing:.06em}
.c-item b{display:block;font-weight:600;margin-top:6px}
.map{position:relative;min-height:400px;border:1px solid var(--line);border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow);background:
  linear-gradient(rgba(10,60,140,.06) 1px,transparent 1px) 0 0/40px 40px,linear-gradient(90deg,rgba(10,60,140,.06) 1px,transparent 1px) 0 0/40px 40px,linear-gradient(135deg,#eaf4ff,#e6fbff)}
.map iframe{position:absolute;inset:0;width:100%;height:100%;border:0}
.map-btn{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;color:var(--accent);font-size:15px;font-weight:600}
.map-pin{width:62px;height:62px;border-radius:50%;display:grid;place-items:center;background:var(--grad);color:#fff;box-shadow:0 0 0 12px rgba(10,108,255,.12),0 0 0 26px rgba(10,108,255,.06);animation:pulse 2.4s ease-in-out infinite}
.map-pin svg{width:26px;height:26px}
@keyframes pulse{50%{box-shadow:0 0 0 16px rgba(10,108,255,.10),0 0 0 34px rgba(10,108,255,.04)}}

/* footer */
/* ===== footer (koyu, tüm sitede aynı) ===== */
.ftr{position:relative;isolation:isolate;overflow:hidden;background:linear-gradient(180deg,#0b1b2e 0%,#071322 100%);color:rgba(226,236,248,.74);padding:0 0 calc(26px + env(safe-area-inset-bottom));font-size:14.5px}
.ftr::before{content:"";position:absolute;inset:0;z-index:-1;pointer-events:none;background:radial-gradient(40% 60% at 90% 0%,rgba(10,108,255,.22),transparent 70%),radial-gradient(35% 50% at 0% 100%,rgba(0,180,216,.12),transparent 70%)}
.ftr::after{content:"";position:absolute;left:0;right:0;top:0;height:3px;background:var(--grad)}
.ftr a{color:inherit;transition:color .2s}
.ftr a:hover{color:#fff}
.f-cta{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:18px 28px;padding:34px 0;border-bottom:1px solid rgba(255,255,255,.1)}
.f-cta b{display:block;color:#fff;font-size:clamp(20px,2.2vw,26px);font-weight:800;letter-spacing:-.02em}
.f-cta p{margin-top:6px;color:rgba(226,236,248,.7);font-size:15px}
.f-cta-acts{display:flex;flex-wrap:wrap;gap:10px}
.f-cta-acts .btn{min-height:48px}
.ftr .btn-fw{background:#fff;color:#0a3d91}
.ftr .btn-fw:hover{color:#0a3d91;transform:translateY(-2px)}
.ftr .btn-fo{background:rgba(255,255,255,.06);color:#fff;border:1px solid rgba(255,255,255,.22)}
.ftr .btn-fo:hover{background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.5);color:#fff}
.ftr .btn-fwa{background:rgba(37,211,102,.12);color:#5ee6a0;border:1px solid rgba(37,211,102,.4)}
.ftr .btn-fwa:hover{background:rgba(37,211,102,.2);color:#fff}
.ftr .btn svg{width:17px;height:17px}
.f-grid{display:grid;grid-template-columns:1.45fr 1fr .9fr 1fr;gap:36px;padding:44px 0 8px}
.f-logo{display:inline-block}
.f-logo img{height:36px;width:auto;filter:brightness(0) invert(1)}
.f-about p{margin-top:16px;max-width:40ch;line-height:1.7}
.f-nap{display:grid;gap:12px;margin-top:22px;font-style:normal}
.f-nap>a,.f-nap>span{display:flex;gap:12px;align-items:flex-start;line-height:1.5}
.f-nap>a>span,.f-nap>span>span{display:block;padding-top:5px}
.f-nap .f-ic{display:grid;place-items:center;flex-shrink:0;width:32px;height:32px;border-radius:10px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);color:#5cc8ff}
.f-nap .f-ic svg{width:16px;height:16px}
.f-nap em{font-style:normal;display:block;font-size:12.5px;color:rgba(226,236,248,.55)}
.f-grid h4{margin:6px 0 18px;font:700 11.5px/1 "JetBrains Mono",monospace;color:#5cc8ff;text-transform:uppercase;letter-spacing:.1em}
.f-grid ul{list-style:none;margin:0;padding:0;display:grid;gap:11px}
.f-grid ul a{display:inline-flex;align-items:center}
.f-grid ul a::before{content:"";width:0;height:2px;margin-right:0;border-radius:2px;background:#5cc8ff;transition:width .2s,margin .2s}
.f-grid ul a:hover::before{width:10px;margin-right:8px}
.f-grid ul small{margin-left:6px}
.f-grid ul small{color:rgba(226,236,248,.5);font-size:12px}
.ftr .open-badge{margin-left:8px}
.ftr .open-badge.on{background:rgba(16,185,129,.18);color:#4ade80}
.ftr .open-badge.off{background:rgba(255,255,255,.08);color:rgba(226,236,248,.6)}
.f-bot{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-top:36px;padding-top:20px;border-top:1px solid rgba(255,255,255,.1);font-size:13px;color:rgba(226,236,248,.5)}
.f-legal a{color:rgba(226,236,248,.75)}
.f-legal a:hover{color:#fff}
.f-top{display:inline-flex;align-items:center;gap:8px;min-height:40px;padding:0 14px;border-radius:999px;border:1px solid rgba(255,255,255,.18);color:rgba(226,236,248,.8);font-size:13px;font-weight:600}
.f-top:hover{border-color:#fff;color:#fff}
.f-top svg{width:14px;height:14px}
.ftr :focus-visible{outline-color:#5cc8ff}

/* floating + mobile bar */
.wa{position:fixed;right:max(20px,env(safe-area-inset-right));bottom:max(20px,env(safe-area-inset-bottom));z-index:40;width:56px;height:56px;border-radius:50%;background:#25d366;display:grid;place-items:center;box-shadow:0 14px 30px -8px rgba(37,211,102,.6);transition:transform .2s}
.wa:hover{transform:scale(1.08)}
.wa svg{width:28px;height:28px;color:#fff}
.mbar{display:none}

/* reveal */
.js .rv{opacity:0;transform:translateY(20px);transition:opacity .7s var(--ease),transform .7s var(--ease)}
.js .rv.in{opacity:1;transform:none}

/* ===== responsive ===== */
@media (max-width:1180px){
  .nav a,.nav .dd-btn{padding:0 9px;font-size:14.5px}
  .hdr-cta .btn-g{display:none}
}
@media (min-width:1025px) and (max-width:1120px){
  .nav a,.nav .dd-btn{padding:0 7px;font-size:14px}
}
@media (max-width:1024px){ /* tablet */
  .calc{grid-template-columns:1fr}
  .groups{grid-template-columns:repeat(3,1fr)}
  .nav,.hdr-cta .btn-g{display:none}
  .hdr-cta .btn-p{min-height:42px;padding:0 16px;font-size:14px}
  .nav-toggle{display:inline-flex}
  .hero{min-height:auto}
  .hero-grid{grid-template-columns:1fr}
  .hero-art{max-width:640px;margin-top:16px}
  .areas{grid-template-columns:repeat(2,1fr)}
  .split{grid-template-columns:1fr}
  .steps{grid-template-columns:repeat(2,1fr);row-gap:28px}
  .steps::before{display:none}
  .contact{grid-template-columns:1fr}
  .f-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:28px}
  .f-about{grid-column:1/-1}
  .f-about{grid-column:1/-1}
}
@media (max-width:768px){
  :root{--hdr:64px}
  .sec{padding:30px 0}
  .hero{padding:calc(var(--hdr) + 28px) 0 36px}
  .logo img{height:34px}
  .hdr-cta .btn-p{display:none}
  .sec-head .btn{width:100%}
  .brands{grid-template-columns:1fr}
  .groups{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
  .groups a{padding:6px;border-radius:16px}
  .g-img{border-radius:11px}
  .g-body{padding:10px 4px 4px;gap:8px}
  .groups b{font-size:14px}
  .groups small{font-size:11.5px}
  .g-go{width:30px;height:30px}
  .g-no{display:none}
  .gp-tag{left:6px;top:6px;padding:5px 8px;font-size:9.5px}
  .g-img img{padding:10px}
  .gf{flex-wrap:nowrap;overflow-x:auto;margin-right:calc(var(--gutter) * -1);padding-right:var(--gutter);scrollbar-width:none}
  .gf::-webkit-scrollbar{display:none}
  .gf button{flex-shrink:0}
  .stats{grid-template-columns:repeat(2,1fr)}
  .float.f1{left:8px;top:-20px}.float.f2{right:8px;bottom:-18px}
  .hero-art{margin-top:28px}
  .wa{display:none}
  .mbar{display:grid;grid-template-columns:repeat(3,1fr);position:fixed;left:0;right:0;bottom:0;z-index:45;background:rgba(255,255,255,.96);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border-top:1px solid var(--line);box-shadow:0 -8px 24px -12px rgba(11,27,46,.2);padding-bottom:env(safe-area-inset-bottom)}
  .mbar a{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;min-height:60px;font-size:12px;font-weight:600;color:var(--muted)}
  .mbar a+a{border-left:1px solid var(--line)}
  .mbar svg{width:21px;height:21px;color:var(--accent)}
  .mbar a.wa-m svg{color:#16a34a}
  .mbar a.q{background:var(--grad);color:#fff}
  .mbar a.q svg{color:#fff}
  body{padding-bottom:calc(60px + env(safe-area-inset-bottom))}
}
@media (max-width:560px){
  .areas{grid-template-columns:1fr}
  .calc-in{grid-template-columns:1fr 1fr;gap:12px}
  .res{padding:16px}
  .steps{grid-template-columns:1fr}
  .f-grid{grid-template-columns:1fr;gap:28px}
  .f-cta-acts{width:100%}
  .f-cta-acts .btn{flex:1 1 100%}
  .f-bot{flex-direction:column;align-items:flex-start}
  .cyl-spec{grid-template-columns:1fr 1fr}
  .cyl-spec div:last-child{grid-column:1/-1}
  .hero-actions .btn{flex:1 1 100%}
  .facts dl{grid-template-columns:1fr}
  .facts dd{border-top:0;padding-top:2px}
  .map{min-height:300px}
  .float{font-size:12px;padding:8px 10px}
  .float i{width:24px;height:24px;font-size:12px}
  .why li{padding:18px}
  .logo-tile{width:180px;height:80px;padding:8px 12px;border-radius:14px}
  .logo-tile .logo-txt{font-size:19px}
  .strip-track{gap:12px}
}
@media (max-width:390px){
  .hero .display{font-size:34px}
  .logo img{height:30px}
  .stat{padding:12px}
}
/* tablet dikey (iPad vb.) */
@media (min-width:769px) and (max-width:1024px){
  .hero-art{margin-inline:auto;width:100%}
  .hero{padding-bottom:56px}
  .stats{max-width:none}
  .brand h3{margin-top:18px}
}
/* geniş ekran */
@media (min-width:1600px){
  :root{--max:1360px}
}
/* dokunmatik ekranlar: en az 44px dokunma hedefi */
@media (pointer:coarse){
  .chip{min-height:44px;padding:0 15px}
  .f-grid ul{gap:2px}
  .f-grid ul a{display:inline-flex;align-items:center;min-height:44px}
  .faq summary{min-height:68px}
  .brand-link,.mg-all{min-height:44px}
}
/* yatay telefon: kısa ekranda hero sığsın */
@media (max-height:500px) and (orientation:landscape){
  .hero{padding-top:calc(var(--hdr) + 16px)}
  .hero-art{display:none}
}
@media (prefers-reduced-motion:reduce){
  *,*::before,*::after{animation:none!important;transition:none!important}
  html{scroll-behavior:auto}
  .js .rv{opacity:1;transform:none}
}

/* =====================================================================
   v4.0 CİLA — mevcut yapıya dokunmadan görsel katman
   (hareket olanlar "hareketi azalt" ayarında kapalıdır; bkz. yukarıdaki genel kural)
   ===================================================================== */
/* kaydırma ilerleme çubuğu */
.sp{position:fixed;left:0;right:0;top:0;height:3px;z-index:60;pointer-events:none}
.sp i{display:block;height:100%;transform-origin:left center;transform:scaleX(0);background:var(--grad);will-change:transform}

/* bölüm başlıkları: gradyan aksan çizgisi */
.h2::after{content:"";display:block;width:56px;height:4px;margin-top:16px;border-radius:4px;background:var(--grad)}
.cta .h2::after{margin-inline:auto;background:#fff;opacity:.9}

/* bölümler: ince üst ayraç; alternatif bölümlerde nokta deseni */
.sec:not(.cta)::before{content:"";position:absolute;left:var(--gutter);right:var(--gutter);top:0;height:1px;max-width:calc(var(--max) - 2 * var(--gutter));margin-inline:auto;background:linear-gradient(90deg,transparent,var(--line-2) 18%,var(--line-2) 82%,transparent)}
.sec.alt{background:radial-gradient(rgba(10,108,255,.07) 1px,transparent 1.2px) 0 0/22px 22px,var(--bg-2)}

/* marka kartları: büyük filigran kod */
.brand>.brand-wm{position:absolute;right:-4px;bottom:-22px;margin:0;font:800 clamp(86px,12vw,150px)/1 Inter,system-ui,sans-serif;letter-spacing:-.06em;color:var(--c);opacity:.06;pointer-events:none;user-select:none;transition:opacity .3s,transform .5s var(--ease)}
.brand:hover>.brand-wm{opacity:.1;transform:translateX(-6px)}

/* birincil düğmeler: parlama (yalnız fare) */
@media (hover:hover) and (pointer:fine){
  .btn-p,.btn-w{position:relative;overflow:hidden}
  .btn-p::after,.btn-w::after{content:"";position:absolute;inset:0;pointer-events:none;background:linear-gradient(105deg,transparent 38%,rgba(255,255,255,.34) 50%,transparent 62%);transform:translateX(-130%);transition:transform .7s var(--ease)}
  .btn-p:hover::after,.btn-w:hover::after{transform:translateX(130%)}
}

/* kart ızgaralarında kademeli giriş */
.brands .rv:nth-child(2),.areas .rv:nth-child(2),.groups li:nth-child(4n+2),.steps .rv:nth-child(2),.why li:nth-child(2){transition-delay:.06s}
.areas .rv:nth-child(3),.groups li:nth-child(4n+3),.steps .rv:nth-child(3),.why li:nth-child(3){transition-delay:.12s}
.areas .rv:nth-child(4),.groups li:nth-child(4n+4),.steps .rv:nth-child(4),.why li:nth-child(4){transition-delay:.18s}
.areas .rv:nth-child(5){transition-delay:.06s}.areas .rv:nth-child(6){transition-delay:.12s}

/* hero: fare ışığı ve teknik resim paftasında hafif eğim (JS; yalnız fare) */
.hero-spot{position:absolute;left:0;top:0;z-index:-1;width:520px;height:520px;margin:-260px 0 0 -260px;border-radius:50%;pointer-events:none;background:radial-gradient(circle,rgba(10,108,255,.17),rgba(0,180,216,.07) 45%,transparent 70%);opacity:0;transition:opacity .5s;will-change:transform}
.hero-spot.on{opacity:1}
.hs-card.tilt{transition:transform .2s ease-out;will-change:transform}

/* hesaplayıcı: canlı silindir kesiti */
.calc-viz{grid-column:1/-1;display:flex;align-items:center;gap:18px;margin:0;padding:14px 18px;border:1px solid var(--line);border-radius:var(--radius);background:#fff;box-shadow:var(--shadow-sm)}
.calc-viz svg{width:92px;height:92px;flex:0 0 92px}
.calc-viz figcaption{font-size:13.5px;line-height:1.5;color:var(--muted)}
.calc-viz figcaption b{display:block;color:var(--text);font:700 15px/1.3 "JetBrains Mono",monospace}
.cv-bore{fill:#e8f1fd;stroke:#0a6cff;stroke-width:2;transition:r .35s var(--ease)}
.cv-rod{fill:#0a6cff;fill-opacity:.85;transition:r .35s var(--ease)}
.cv-cl{stroke:#0a6cff;stroke-opacity:.35;stroke-width:1;stroke-dasharray:6 3 1 3}
/* =====================================================================
   v4.1 — header iletişim bloğu + "Grup şirketimiz" bölümü
   ===================================================================== */
/* header: santral + cep (masaüstü) */
.hdr-tel{display:flex;flex-direction:column;gap:2px;padding:5px 12px;border:1px solid var(--line);border-radius:14px;background:#fff;box-shadow:var(--shadow-sm)}
.hdr-tel a{display:flex;align-items:center;gap:9px;min-height:26px;color:var(--text);transition:color .2s}
.hdr-tel a:hover{color:var(--accent)}
.hdr-tel svg{flex:0 0 17px;width:17px;height:17px;color:var(--accent)}
.hdr-tel a+a svg{color:#16a34a}
.hdr-tel span{display:flex;align-items:baseline;gap:8px;white-space:nowrap}
.hdr-tel b{font-size:14px;font-weight:700;letter-spacing:-.01em}
.hdr-tel small{font:600 10px/1 "JetBrains Mono",monospace;letter-spacing:.05em;text-transform:uppercase;color:var(--dim)}
.hdr-ico{display:none;gap:8px}
.hdr-ico .hi{display:grid;place-items:center;width:44px;height:44px;border:1px solid var(--line-2);border-radius:12px;background:#fff;color:var(--accent);box-shadow:var(--shadow-sm);transition:border-color .2s,transform .2s}
.hdr-ico .hi:hover{border-color:var(--accent);transform:translateY(-1px)}
.hdr-ico .hi svg{width:20px;height:20px}
.hdr-ico .hi-wa{color:#16a34a;border-color:rgba(37,211,102,.45);background:rgba(37,211,102,.08)}
@media (max-width:1180px){ .hdr-tel{display:none} .hdr-ico{display:flex} }
@media (max-width:768px){ .hdr-ico .hi-wa{display:none} .hdr-ico .hi{width:46px;height:46px} }
/* menü (drawer): iki numara satırı */
.drawer a>span{font:600 12px/1 "JetBrains Mono",monospace;letter-spacing:.05em;text-transform:uppercase;color:var(--dim)}
.drawer a>b{font-weight:700}

/* grup şirketi */
.grp{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,.85fr);gap:20px;margin-top:30px;align-items:stretch}
.grp-main{display:flex;flex-direction:column;gap:22px;padding:clamp(22px,3vw,36px);border:1px solid var(--line);border-radius:var(--radius-lg);background:#fff;box-shadow:var(--shadow-sm)}
.grp-main .lead{margin:0;max-width:62ch}
.grp-main .lead b{color:var(--text)}
.grp-feat{list-style:none;margin:auto 0 0;padding:0;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.grp-feat li{display:flex;gap:14px;align-items:flex-start;padding:16px;border:1px solid var(--line);border-radius:16px;background:var(--bg-2);transition:border-color .2s,transform .2s,background .2s}
.grp-feat li:hover{border-color:rgba(10,108,255,.4);background:#fff;transform:translateY(-2px)}
.grp-feat i{display:grid;place-items:center;flex:0 0 44px;width:44px;height:44px;border-radius:12px;background:#fff;border:1px solid var(--line);color:var(--accent)}
.grp-feat svg{width:22px;height:22px}
.grp-feat b{display:block;font-size:15.5px;line-height:1.3}
.grp-feat small{display:block;margin-top:3px;font-size:13px;line-height:1.45;color:var(--muted)}
.grp-card{position:relative;isolation:isolate;overflow:hidden;display:flex;flex-direction:column;align-items:flex-start;gap:18px;padding:clamp(22px,3vw,34px);border-radius:var(--radius-lg);color:#fff;background:linear-gradient(150deg,#0b1b2e 0%,#0d2a52 55%,#0a4cb0 100%);box-shadow:var(--shadow-lg)}
.grp-card::before{content:"";position:absolute;inset:0;z-index:-1;opacity:.1;background-image:linear-gradient(#fff 1px,transparent 1px),linear-gradient(90deg,#fff 1px,transparent 1px);background-size:32px 32px;-webkit-mask-image:linear-gradient(135deg,transparent 15%,#000);mask-image:linear-gradient(135deg,transparent 15%,#000)}
.grp-card::after{content:"";position:absolute;right:-70px;bottom:-70px;z-index:-1;width:240px;height:240px;border-radius:50%;background:radial-gradient(circle,rgba(0,180,216,.35),transparent 70%)}
.grp-logo{position:relative;overflow:hidden;display:grid;place-items:center;width:100%;min-height:132px;padding:14px 22px;border-radius:18px;background:#fff;box-shadow:0 18px 40px -18px rgba(0,0,0,.5);transition:transform .25s var(--ease)}
.grp-logo:hover{transform:translateY(-3px)}
.grp-logo img{display:block;width:100%;height:auto;max-height:84px;object-fit:contain;mix-blend-mode:multiply}
.grp-logo.is-jpg{min-height:0;padding:10px}.grp-logo.is-jpg img{max-height:150px;width:auto;max-width:100%;margin:0 auto}
.grp-logo-txt{font-size:30px;font-weight:800;letter-spacing:-.02em;color:#0a3d91}
.grp-slogan{margin:0;color:rgba(255,255,255,.85);font-size:15px;line-height:1.5}
.grp-facts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;width:100%;margin:0}
.grp-facts div{padding:12px;border:1px solid rgba(255,255,255,.16);border-radius:12px;background:rgba(255,255,255,.06)}
.grp-facts dt{font:600 10.5px/1.2 "JetBrains Mono",monospace;letter-spacing:.06em;text-transform:uppercase;color:#9cc6ff}
.grp-facts dd{margin:6px 0 0;font-weight:700;font-size:15px;line-height:1.25}
.grp-acts{display:flex;flex-wrap:wrap;gap:10px;width:100%;margin-top:auto}
.grp-acts .btn{flex:1 1 180px}
.grp-acts .btn-o{background:transparent;color:#fff;border:1px solid rgba(255,255,255,.4)}
.grp-acts .btn-o:hover{background:rgba(255,255,255,.1);border-color:#fff}
@media (max-width:900px){ .grp{grid-template-columns:minmax(0,1fr)} }
@media (max-width:560px){ .grp-feat{grid-template-columns:minmax(0,1fr)} .grp-facts div{padding:10px} .grp-facts dd{font-size:14px} .grp-logo{min-height:112px} }
</style>
</head>
<body>
{{-- ================= INTRO (oturumda bir kez; botlara ve "hareketi azalt" kullananlara gösterilmez) ================= --}}
<div class="intro-ov" id="intro" aria-hidden="true">
  <button type="button" class="intro-skip" tabindex="-1">Geç <span>›</span></button>
  <svg class="intro-art" viewBox="60 40 860 500" fill="none" stroke="#111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    {{-- ---------- 1) Pnömatik silindir (ISO 15552) ---------- --}}
    <text class="it-lbl" x="150" y="62">PNÖMATİK SİLİNDİR · ISO 15552</text>
    <line class="ln cl" pathLength="1" x1="110" y1="165" x2="900" y2="165"/>
    <rect class="ln" pathLength="1" x="150" y="110" width="40" height="110" rx="4"/>
    <rect class="ln" pathLength="1" x="190" y="120" width="330" height="90"/>
    <rect class="ln" pathLength="1" x="520" y="110" width="40" height="110" rx="4"/>
    <line class="ln thin" pathLength="1" x1="158" y1="127" x2="552" y2="127"/>
    <line class="ln thin" pathLength="1" x1="158" y1="203" x2="552" y2="203"/>
    <rect class="ln" pathLength="1" x="162" y="96" width="16" height="14"/>
    <rect class="ln" pathLength="1" x="532" y="96" width="16" height="14"/>
    <g class="it-rod">
      <rect class="ln hid" pathLength="1" x="212" y="126" width="22" height="78"/>
      <line class="ln hid" pathLength="1" x1="234" y1="153" x2="560" y2="153"/>
      <line class="ln hid" pathLength="1" x1="234" y1="177" x2="560" y2="177"/>
      <rect class="ln" pathLength="1" x="560" y="152" width="90" height="26"/>
      <rect class="ln" pathLength="1" x="650" y="155" width="30" height="20"/>
      <path class="ln thin" pathLength="1" d="M656 155v20M662 155v20M668 155v20M674 155v20"/>
    </g>
    <path class="ln dim" pathLength="1" d="M190 252H520M190 244v16M520 244v16M190 252l10-5v10zM520 252l-10-5v10z"/>
    <text class="it-dim" x="355" y="276">STROK 150</text>
    <path class="ln dim" pathLength="1" d="M118 120V210M110 120h16M110 210h16"/>
    <text class="it-dim" x="118" y="106" text-anchor="middle">Ø63</text>

    {{-- ---------- 2) Milsiz (rotsuz) silindir ---------- --}}
    <text class="it-lbl" x="150" y="340">MİLSİZ (ROTSUZ) SİLİNDİR</text>
    <line class="ln cl" pathLength="1" x1="100" y1="430" x2="900" y2="430"/>
    <rect class="ln" pathLength="1" x="150" y="405" width="700" height="50" rx="6"/>
    <line class="ln thin" pathLength="1" x1="170" y1="415" x2="830" y2="415"/>
    <line class="ln thin" pathLength="1" x1="170" y1="445" x2="830" y2="445"/>
    <rect class="ln" pathLength="1" x="126" y="398" width="24" height="64" rx="3"/>
    <rect class="ln" pathLength="1" x="850" y="398" width="24" height="64" rx="3"/>
    <rect class="ln" pathLength="1" x="131" y="384" width="14" height="14"/>
    <rect class="ln" pathLength="1" x="855" y="384" width="14" height="14"/>
    <g class="it-car">
      <rect class="ln" pathLength="1" x="200" y="372" width="160" height="40" rx="5"/>
      <circle class="ln thin" pathLength="1" cx="222" cy="392" r="5"/>
      <circle class="ln thin" pathLength="1" cx="338" cy="392" r="5"/>
      <rect class="ln hid" pathLength="1" x="236" y="418" width="88" height="24"/>
    </g>
    <path class="ln dim" pathLength="1" d="M200 500H650M200 492v16M650 492v16M200 500l10-5v10zM650 500l-10-5v10z"/>
    <text class="it-dim" x="425" y="524">STROK 450</text>
  </svg>
  <div class="intro-brand">
    <img src="{{ $site['logo'] }}" alt="" width="170" height="38">
    <span>Pnömatik ve otomasyon çözümleri · 2007'den beri</span>
  </div>
  <i class="intro-bar"></i>
</div>
<a class="skip" href="#main">İçeriğe geç</a>
<div class="sp" aria-hidden="true"><i id="sp-i"></i></div>

{{-- ================= HEADER ================= --}}
<header class="hdr" id="hdr">
  <div class="wrap">
    <a href="{{ url('/') }}" class="logo" aria-label="{{ $site['name'] }} — Ana sayfa">
      <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" width="150" height="34" fetchpriority="high">
    </a>

    <nav aria-label="Ana menü">
      <ul class="nav">
        <li>
          <button class="dd-btn" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="dd-kurumsal">Kurumsal
            <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.6"><path d="m3 4.5 3 3 3-3"/></svg></button>
          <div class="dd" id="dd-kurumsal">
            <a href="{{ url('hakkimizda') }}">Hakkımızda<small>2007'den bugüne</small></a>
            <a href="{{ url('politikalarimiz') }}">Politikalarımız</a>
            <a href="{{ url('partner-markalarimiz') }}">Partner Markalarımız</a>
          </div>
        </li>
        <li class="has-mega">
          <button class="dd-btn" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="dd-urunler">Ürünler
            <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.6"><path d="m3 4.5 3 3 3-3"/></svg></button>
          <div class="dd dd-mega" id="dd-urunler">
            <div class="mg-cols">
              @foreach ($brands as $b)
                <div class="mg-col" style="--c: {{ $b['color'] }}">
                  <a class="mg-brand" href="{{ url('category/' . $b['slug']) }}"><i aria-hidden="true"></i>{{ $b['name'] }}<small>{{ $b['origin'] }}</small></a>
                  @foreach (array_slice($b['cats'], 0, 4) as $c)
                    <a class="mg-link" href="{{ url('category/' . $c[0]) }}">{{ $c[1] }}</a>
                  @endforeach
                </div>
              @endforeach
            </div>
            <a class="mg-all" href="{{ url('urunlerimiz') }}">Tüm ürün grupları <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4"/></svg></a>
          </div>
        </li>
        <li><a href="{{ url('catalog_send') }}">Katalog</a></li>
        <li><a href="{{ url('hesaplamalar') }}">Hesaplamalar</a></li>
        <li><a href="{{ url('blogs') }}">Blog</a></li>
        <li><a href="{{ url('iletisim') }}">İletişim</a></li>
      </ul>
    </nav>

    <div class="hdr-cta">
      <div class="hdr-tel" aria-label="Telefon numaraları">
        <a href="tel:{{ $site['phoneIntl'] }}" title="Santral"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg><span><b>{{ $site['phone'] }}</b><small>Santral</small></span></a>
        <a href="tel:+{{ $site['whatsapp'] }}" title="Cep ve WhatsApp"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="7" y="2.5" width="10" height="19" rx="2.4"/><path d="M11 18.5h2"/></svg><span><b>{{ $site['gsm'] }}</b><small>Cep · WhatsApp</small></span></a>
      </div>
      <div class="hdr-ico">
        <a class="hi" href="tel:{{ $site['phoneIntl'] }}" aria-label="Santrali ara: {{ $site['phone'] }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg></a>
        <a class="hi hi-wa" href="https://wa.me/{{ $site['whatsapp'] }}" target="_blank" rel="noopener" aria-label="WhatsApp: {{ $site['gsm'] }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 20l1.3-3.8A8 8 0 1 1 8 19.3L4 20Z"/></svg></a>
      </div>
      <a href="{{ url('iletisim') }}#form" class="btn btn-p">Teklif İste</a>
      <button class="nav-toggle" aria-expanded="false" aria-controls="drawer" aria-label="Menüyü aç"><span></span></button>
    </div>
  </div>
</header>

{{-- Mobile / tablet drawer --}}
<nav class="drawer" id="drawer" aria-label="Mobil menü" aria-hidden="true">
  <details>
    <summary>Kurumsal <svg width="14" height="14" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.6"><path d="m3 4.5 3 3 3-3"/></svg></summary>
    <a href="{{ url('hakkimizda') }}">Hakkımızda</a>
    <a href="{{ url('politikalarimiz') }}">Politikalarımız</a>
    <a href="{{ url('partner-markalarimiz') }}">Partner Markalarımız</a>
  </details>
  <details>
    <summary>Ürünler <svg width="14" height="14" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.6"><path d="m3 4.5 3 3 3-3"/></svg></summary>
    @foreach ($brands as $b)
      <a href="{{ url('category/' . $b['slug']) }}">{{ $b['name'] }}</a>
    @endforeach
    <a href="{{ url('urunlerimiz') }}">Tüm ürün grupları</a>
  </details>
  <a href="{{ url('catalog_send') }}">Katalog</a>
  <a href="{{ url('hesaplamalar') }}">Hesaplamalar</a>
  <a href="{{ url('blogs') }}">Blog</a>
  <a href="{{ url('iletisim') }}">İletişim</a>
  <a href="tel:{{ $site['phoneIntl'] }}"><span>Santral</span><b>{{ $site['phone'] }}</b></a>
  <a href="tel:+{{ $site['whatsapp'] }}"><span>Cep · WhatsApp</span><b>{{ $site['gsm'] }}</b></a>
  <a href="{{ url('iletisim') }}#form" class="btn btn-p">Teklif İste</a>
</nav>

<main id="main">

{{-- ================= HERO ================= --}}
<section class="hero" aria-labelledby="hero-title">
  <div class="wrap hero-grid">
    <div>
      <span class="pill rv"><b>2007'den beri</b> Nilüfer · Bursa</span>
      <h1 id="hero-title" class="h1-kw rv">Bursa pnömatik silindir, valf ve otomasyon malzemeleri</h1>
      <p class="display rv">Pnömatik ve otomasyonda <span class="grad-b">doğru parça, doğru seri.</span></p>
      <p class="lead rv">Ensis Pnömatik; Univer, Medan, Mindman ve Tox-Pressotechnik markalarında <strong>pnömatik silindir</strong>, <strong>solenoid valf</strong>, <strong>şartlandırıcı</strong>, vakum ve doğrusal hareket ürünlerini Bursa Nilüfer'den teknik destekle tedarik eder.</p>
      <div class="hero-actions rv">
        <a href="#markalar" class="btn btn-p">Markaları İncele
          <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 8h10M9 4l4 4-4 4"/></svg></a>
        <a href="{{ url('iletisim') }}#form" class="btn btn-g">Teklif İste</a>
      </div>
      <div class="stats rv" role="list">
        <div class="stat" role="listitem"><strong data-count="{{ $years }}">{{ $years }}</strong><span>yıllık deneyim</span></div>
        <div class="stat" role="listitem"><strong data-count="{{ count($brands) }}">{{ count($brands) }}</strong><span>dünya markası</span></div>
        <div class="stat" role="listitem"><strong data-count="370" data-suffix="+">370+</strong><span>ürün grubu</span></div>
        <div class="stat" role="listitem"><strong data-count="{{ count($areas) }}">{{ count($areas) }}</strong><span>çözüm alanı</span></div>
      </div>
    </div>

    <div class="hero-art rv">
      <figure class="hs-card">
        <svg class="hs" id="hs" viewBox="0 0 1000 720" fill="none" stroke="#111" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" role="img" aria-labelledby="hs-title">
          <title id="hs-title">Pnömatik silindir (ISO 15552, Ø63, strok 150) ve milsiz silindir (strok 450) teknik çizimi — Ensis Pnömatik</title>
          <rect class="ln frame" x="10" y="10" width="980" height="700"/>
          <rect class="ln frame thin" x="20" y="20" width="960" height="680"/>
          <g transform="translate(-30,10)">
            {{-- pnömatik silindir --}}
            <line class="ln cl" x1="110" y1="165" x2="900" y2="165"/>
            <rect class="ln" x="150" y="110" width="40" height="110" rx="4"/>
            <rect class="ln" x="190" y="120" width="330" height="90"/>
            <rect class="ln" x="520" y="110" width="40" height="110" rx="4"/>
            <rect class="ln" x="162" y="96" width="16" height="14"/>
            <rect class="ln" x="532" y="96" width="16" height="14"/>
            <line class="ln thin" x1="158" y1="127" x2="552" y2="127"/>
            <line class="ln thin" x1="158" y1="203" x2="552" y2="203"/>
            <g class="hs-rod">
              <rect class="ln hid" x="212" y="126" width="22" height="78"/>
              <line class="ln hid" x1="234" y1="153" x2="560" y2="153"/>
              <line class="ln hid" x1="234" y1="177" x2="560" y2="177"/>
              <rect class="ln" x="560" y="152" width="90" height="26"/>
              <rect class="ln" x="650" y="155" width="30" height="20"/>
              <path class="ln thin" d="M656 155v20M662 155v20M668 155v20M674 155v20"/>
            </g>
            <path class="ln dim" d="M118 120V210M110 120h16M110 210h16"/>
            <path class="ln dim" d="M190 254H520M190 246v16M520 246v16M190 254l10-5v10zM520 254l-10-5v10z"/>
            {{-- milsiz (rotsuz) silindir --}}
            <line class="ln cl" x1="110" y1="450" x2="900" y2="450"/>
            <rect class="ln" x="150" y="425" width="700" height="50" rx="6"/>
            <rect class="ln" x="126" y="418" width="24" height="64" rx="3"/>
            <rect class="ln" x="850" y="418" width="24" height="64" rx="3"/>
            <rect class="ln" x="131" y="404" width="14" height="14"/>
            <rect class="ln" x="855" y="404" width="14" height="14"/>
            <line class="ln thin" x1="170" y1="435" x2="830" y2="435"/>
            <line class="ln thin" x1="170" y1="465" x2="830" y2="465"/>
            <g class="hs-car">
              <rect class="ln" x="200" y="392" width="160" height="40" rx="5"/>
              <circle class="ln thin" cx="222" cy="412" r="5"/>
              <circle class="ln thin" cx="338" cy="412" r="5"/>
              <rect class="ln hid" x="236" y="438" width="88" height="24"/>
            </g>
            <path class="ln dim" d="M200 522H650M200 514v16M650 514v16M200 522l10-5v10zM650 522l-10-5v10z"/>
            {{-- yazılar --}}
            <text class="hs-lbl" x="150" y="66">PNÖMATİK SİLİNDİR · ISO 15552</text>
            <text class="hs-lbl" x="150" y="360">MİLSİZ (ROTSUZ) SİLİNDİR</text>
            <text class="hs-dim" x="118" y="102" text-anchor="middle">Ø63</text>
            <text class="hs-dim" x="355" y="284">STROK 150</text>
            <text class="hs-dim" x="425" y="552">STROK 450</text>
          </g>
          {{-- antet --}}
          <g class="tb">
            <rect class="ln" x="20" y="604" width="960" height="96"/>
            <path class="ln thin" d="M300 604V700M560 604V700M770 604V700M560 652H980"/>
            <image class="tb-logo" href="{{ $site['logo'] }}" x="44" y="630" width="200" height="44" preserveAspectRatio="xMinYMid meet"/>
            <text class="tb-t" x="318" y="630">PARÇA</text>
            <text class="tb-v" x="318" y="658">PNÖMATİK SİLİNDİR</text>
            <text class="tb-v" x="318" y="682">MİLSİZ SİLİNDİR</text>
            <text class="tb-t" x="578" y="626">ÖLÇEK</text>
            <text class="tb-v" x="578" y="644">1 : 2</text>
            <text class="tb-t" x="788" y="626">TARİH</text>
            <text class="tb-v" x="788" y="644">{{ date('d.m.Y') }}</text>
            <text class="tb-t" x="578" y="674">ÇİZEN</text>
            <text class="tb-v" x="578" y="692">ENSİS TEKNİK</text>
            <text class="tb-t" x="788" y="674">PAFTA</text>
            <text class="tb-v" x="788" y="692">A3 · 1/1</text>
          </g>
          {{-- çizim kalemi --}}
          <g class="hs-pen" id="hs-pen" transform="translate(-300,-300)" aria-hidden="true">
            <g transform="rotate(-35)">
              <path d="M0 0 7 -18H-7Z" fill="#111" stroke="none"/>
              <path d="M-7 -18 -10 -27H10L7 -18Z" fill="#e8e1cf" stroke="#111" stroke-width="1.3"/>
              <rect x="-10" y="-117" width="20" height="90" rx="2" fill="#fff" stroke="#111" stroke-width="1.5"/>
              <line x1="-3" y1="-112" x2="-3" y2="-31" stroke="#111" stroke-width=".9"/>
              <rect x="-10" y="-130" width="20" height="13" rx="2" fill="#111" stroke="none"/>
            </g>
          </g>
        </svg>
        <figcaption class="hs-cap"><span>Teknik çizim</span><i>● çalışıyor</i></figcaption>
      </figure>
    </div>
  </div>
</section>

{{-- ================= LOGO ŞERİDİ ================= --}}
<section class="strip" aria-label="Çözüm ortaklarımız">
  <div class="strip-track">
    @for ($i = 0; $i < 4; $i++)
      @foreach ($partners as $p)
        <a class="logo-tile{{ $p['clean'] ? ' is-clean' : '' }}" href="{{ $p['url'] }}" target="_blank" rel="noopener" title="{{ $p['name'] }} resmi web sitesi" @if ($i > 0) aria-hidden="true" tabindex="-1" @endif>
          @if ($p['logo'])
            <img src="{{ asset($p['logo']) }}" alt="{{ $p['name'] }} logosu" width="200" height="70" loading="lazy" decoding="async" onerror="this.outerHTML='&lt;span class=&quot;logo-txt&quot;&gt;'+this.alt.replace(' logosu','')+'&lt;/span&gt;'">
          @else
            <span class="logo-txt">{{ $p['name'] }}</span>
          @endif
        </a>
      @endforeach
    @endfor
  </div>
</section>

{{-- ================= BRANDS ================= --}}
<section class="sec" id="markalar" aria-labelledby="brands-title">
  <div class="wrap">
    <div class="sec-head">
      <div>
        <span class="eyebrow rv">Temsil ettiğimiz markalar</span>
        <h2 id="brands-title" class="h2 rv">Dört üretici, tek teknik muhatap.</h2>
      </div>
      <a href="{{ url('urunlerimiz') }}" class="btn btn-g rv">Tüm ürün grupları</a>
    </div>

    <div class="brands">
      @foreach ($brands as $b)
        <article class="brand rv" style="--c: {{ $b['color'] }}">
          @php($bLogo = $brandLogo($b['slug'] === 'tox-presso-technik' ? 'tox' : $b['slug']))
          <span class="brand-wm" aria-hidden="true">{{ $b['code'] }}</span>
          <div class="brand-top"><span><b class="code">{{ $b['code'] }}</b>{{ $b['origin'] }}</span>
            @if ($bLogo)<img class="brand-logo" src="{{ asset($bLogo) }}" alt="{{ $b['name'] }} logosu" width="140" height="44" loading="lazy" decoding="async">@else<span>0{{ $loop->iteration }}</span>@endif
          </div>
          <h3><a href="{{ url('category/' . $b['slug']) }}">{{ $b['name'] }}</a></h3>
          <p>{{ $b['desc'] }}</p>
          <div class="chips">
            @foreach ($b['cats'] as $c)
              <a class="chip" href="{{ url('category/' . $c[0]) }}">{{ $c[1] }}</a>
            @endforeach
          </div>
          <a class="brand-link" href="{{ url('category/' . $b['slug']) }}" aria-label="{{ $b['name'] }} ürünlerini incele">
            Tüm {{ $b['name'] }} ürünleri
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 8h10M9 4l4 4-4 4"/></svg>
          </a>
        </article>
      @endforeach
    </div>
  </div>
</section>

{{-- ================= POPÜLER ÜRÜN GRUPLARI ================= --}}
<section class="sec alt" id="urun-gruplari" aria-labelledby="groups-title">
  <div class="wrap">
    <div class="sec-head">
      <div>
        <span class="eyebrow rv">Popüler ürün grupları</span>
        <h2 id="groups-title" class="h2 rv">En çok aranan pnömatik ürünler.</h2>
      </div>
      <a href="{{ url('urunlerimiz') }}" class="btn btn-g rv">Tüm kategoriler</a>
    </div>
    <?php
      $gColors = ['Pisco' => '#7c3aed'];
      foreach ($brands as $bb) $gColors[$bb['name']] = $bb['color'];
      $gBrands = array_values(array_unique(array_map(function ($x) { return $x['brand']; }, $groups)));
    ?>
    <div class="gf" role="group" aria-label="Markaya göre filtrele">
      <button type="button" aria-pressed="true" data-gf="*">Tümü</button>
      @foreach ($gBrands as $gb)
        <button type="button" aria-pressed="false" data-gf="{{ $gb }}" style="--c: {{ isset($gColors[$gb]) ? $gColors[$gb] : '#0a6cff' }}"><i aria-hidden="true"></i>{{ $gb }}</button>
      @endforeach
    </div>
    <ul class="groups" id="groups-list">
      @foreach ($groups as $gi => $g)
        <li class="rv" data-b="{{ $g['brand'] }}">
          <a href="{{ url('category/' . $g['slug']) }}" style="--c: {{ isset($gColors[$g['brand']]) ? $gColors[$g['brand']] : '#0a6cff' }}">
            <span class="g-img{{ $g['brand'] === 'Univer' ? ' full' : '' }}">
              <span class="gp-tag"><i aria-hidden="true"></i>{{ $g['brand'] }}</span>
              <span class="g-no" aria-hidden="true">{{ sprintf('%02d', $gi + 1) }}</span>
              <img src="{{ asset('uploads/category/' . $g['img']) }}" alt="{{ $g['name'] }} - {{ $g['brand'] }}" width="400" height="300" loading="lazy" decoding="async" onerror="this.parentNode.classList.add('noimg');this.remove()">
            </span>
            <span class="g-body"><span><b>{{ $g['name'] }}</b><small>Ürünleri incele</small></span><span class="g-go" aria-hidden="true"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 8h10M9 4l4 4-4 4"/></svg></span></span>
          </a>
        </li>
      @endforeach
    </ul>
    <script>
    (function () {
      var bar = document.querySelector('.gf'), items = document.querySelectorAll('#groups-list > li');
      if (!bar) return;
      bar.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('button[data-gf]') : null;
        if (!btn) return;
        var v = btn.getAttribute('data-gf');
        Array.prototype.forEach.call(bar.querySelectorAll('button'), function (x) { x.setAttribute('aria-pressed', x === btn ? 'true' : 'false'); });
        Array.prototype.forEach.call(items, function (li) { li.hidden = !(v === '*' || li.getAttribute('data-b') === v); if (!li.hidden) li.classList.add('in'); });
      });
    })();
    </script>
  </div>
</section>

{{-- ================= SOLUTION AREAS ================= --}}
<section class="sec" aria-labelledby="areas-title">
  <div class="wrap">
    <span class="eyebrow rv">Çözüm alanları</span>
    <h2 id="areas-title" class="h2 rv">Hattınızın hareket eden her noktası için.</h2>
    <div class="areas">
      @foreach ($areas as $a)
        <a class="area rv" href="{{ url($a[3]) }}">
          <div class="ic">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">{!! $icons[$a[0]] ?? $icons['shield'] !!}</svg>
          </div>
          <h3>{{ $a[1] }}</h3>
          <p>{{ $a[2] }}</p>
          <span class="area-go">{{ $a[4] }} <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4"/></svg></span>
        </a>
      @endforeach
    </div>
  </div>
</section>

{{-- ================= SEKTÖRLER ================= --}}
<section class="sec alt" id="sektorler" aria-labelledby="sectors-title">
  <div class="wrap">
    <span class="eyebrow rv">Sektörler</span>
    <h2 id="sectors-title" class="h2 rv">Bursa ve Marmara sanayisinin ihtiyaçlarına göre.</h2>
    <p class="lead rv">Farklı sektörlerdeki makine ve hatlar için doğru pnömatik bileşenleri seçiyor, tedarik ediyoruz.</p>
    <div class="areas sectors">
      @foreach ($sectors as $sc)
        <article class="area rv">
          <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">{!! $sectorIcons[$sc['icon']] !!}</svg></div>
          <h3>{{ $sc['name'] }}</h3>
          <p>{{ $sc['desc'] }}</p>
          <div class="chips">
            @foreach ($sc['tags'] as $tg)
              <a class="chip" href="{{ url('category/' . $tg[0]) }}">{{ $tg[1] }}</a>
            @endforeach
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>

{{-- ================= WHY + PROCESS ================= --}}
<section class="sec" aria-labelledby="why-title">
  <div class="wrap">
    <div class="split">
      <div>
        <span class="eyebrow rv">Neden Ensis</span>
        <h2 id="why-title" class="h2 rv">Katalog kodundan çalışan hatta.</h2>
        <p class="lead rv">Ürünü satmakla kalmıyor, uygulamanıza doğru seriyi birlikte seçiyoruz.</p>
        <div class="help rv">
          <span class="help-ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 13a8 8 0 0 1 16 0"/><path d="M4 13v3a2 2 0 0 0 2 2h1v-6H6a2 2 0 0 0-2 1ZM20 13v3a2 2 0 0 1-2 2h-1v-6h1a2 2 0 0 1 2 1Z"/><path d="M17 18c0 1.7-2 3-5 3"/></svg></span>
          <div>
            <b>Teknik destek hattı</b>
            <small>{{ $site['hours'] }}</small>
          </div>
          <div class="help-acts">
            <a href="tel:{{ $site['phoneIntl'] }}" class="btn btn-p">{{ $site['phone'] }}</a>
            <a href="https://wa.me/{{ $site['whatsapp'] }}" target="_blank" rel="noopener" class="btn btn-g">WhatsApp</a>
          </div>
        </div>
      </div>
      <ul class="why">
        <li class="rv"><span class="n">01</span><div><h3>Orijinal ürün, yetkili tedarik</h3><p>Temsil ettiğimiz markaların orijinal ürünleri ve belgeleri.</p></div></li>
        <li class="rv"><span class="n">02</span><div><h3>Uygulamaya göre teknik seçim</h3><p>Basınç, kuvvet, strok ve ortam koşullarına göre doğru seri ve aksesuar.</p></div></li>
        <li class="rv"><span class="n">03</span><div><h3>Muadil ve alternatif çözümler</h3><p>Mevcut ürün kodunuza karşılık gelen seriyi bulmanıza yardımcı oluyoruz.</p></div></li>
        <li class="rv"><span class="n">04</span><div><h3>Bursa'dan hızlı ve yakın destek</h3><p>Nilüfer'deki merkezimizde, sanayinin kalbinde ulaşılabilir bir ekip.</p></div></li>
      </ul>
    </div>

    <div class="steps">
      <div class="step rv"><h3>İhtiyacı paylaşın</h3><p>Ürün kodu, teknik resim ya da uygulama bilgisi.</p></div>
      <div class="step rv"><h3>Teknik seçim</h3><p>Uygun seri ve aksesuarları birlikte belirliyoruz.</p></div>
      <div class="step rv"><h3>Teklif</h3><p>Fiyat ve termin bilgisiyle net teklif.</p></div>
      <div class="step rv"><h3>Teslimat</h3><p>Siparişiniz takipli şekilde size ulaşır.</p></div>
    </div>
  </div>
</section>

{{-- ================= KUVVET HESAPLAYICI ================= --}}
<section class="sec alt" id="hesaplayici" aria-labelledby="calc-title">
  <div class="wrap">
    <span class="eyebrow rv">Mühendislik aracı</span>
    <h2 id="calc-title" class="h2 rv">Pnömatik silindir kuvvet hesaplayıcı</h2>
    <p class="lead rv">Silindir çapını, basıncı ve stroku girin; itme ve çekme kuvvetini, hava tüketimini anında görün.</p>
    <div class="calc rv">
      <form class="calc-in" onsubmit="return false" aria-label="Hesaplama değerleri">
        <label>Silindir çapı (Ø mm)
          <select id="c-bore">
            @foreach ($bores as $bd => $rd)
              <option value="{{ $bd }}" data-rod="{{ $rd }}" @if ($bd == 63) selected @endif>Ø{{ $bd }} mm</option>
            @endforeach
          </select>
        </label>
        <label>Rot çapı (mm)
          <input id="c-rod" type="number" min="0" step="1" inputmode="numeric" value="20">
        </label>
        <label class="full">Çalışma basıncı <output id="c-p-out" for="c-p">6 bar</output>
          <input id="c-p" type="range" min="1" max="10" step="0.5" value="6">
        </label>
        <label>Strok (mm)
          <input id="c-stroke" type="number" min="1" step="1" inputmode="numeric" value="100">
        </label>
        <label>Çevrim / dakika
          <input id="c-cyc" type="number" min="0" step="1" inputmode="numeric" value="10">
        </label>
        <p class="calc-hint">Rot çapı, seçilen çapın ISO standart değeriyle otomatik gelir; dilerseniz değiştirebilirsiniz.</p>
      </form>
      <div class="calc-out" aria-live="polite">
        <figure class="calc-viz" aria-hidden="true">
          <svg viewBox="0 0 120 120"><line class="cv-cl" x1="4" y1="60" x2="116" y2="60"/><line class="cv-cl" x1="60" y1="4" x2="60" y2="116"/><circle class="cv-bore" id="v-bore" cx="60" cy="60" r="40"/><circle class="cv-rod" id="v-rod" cx="60" cy="60" r="13"/></svg>
          <figcaption><b id="v-t">Ø63 / rot Ø20</b><span id="v-a">Piston alanı 31,2 cm² · halka alanı 28 cm²</span></figcaption>
        </figure>
        <div class="res r1"><small>İtme kuvveti</small><b id="r-push">1.870 N</b><span id="r-push-kg">≈ 191 kgf</span></div>
        <div class="res r2"><small>Çekme kuvveti</small><b id="r-pull">1.682 N</b><span id="r-pull-kg">≈ 171 kgf</span></div>
        <div class="res r3"><small>Pratik itme (%85 verim)</small><b id="r-prac">1.590 N</b><span>yaklaşık değer</span></div>
        <div class="res r4"><small>Hava tüketimi</small><b id="r-air">41</b><span>NL/dk</span></div>
        <a class="btn btn-p" id="r-quote" href="{{ url('iletisim') }}#form" data-base="{{ url('iletisim') }}">Bu silindir için teklif iste
          <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 8h10M9 4l4 4-4 4"/></svg></a>
        <p class="calc-note">Sonuçlar teorik değerlerdir; sürtünme, hız ve yük koşulları gerçek kuvveti etkiler. Kesin seçim için teknik ekibimize danışın.</p>
      </div>
    </div>
    <a href="{{ url('hesaplamalar') }}" class="btn btn-g btn-wrap rv" style="margin-top:22px">Tüm hesaplamalar: muadil kod bulucu, çap seçimi, hava tüketimi, hidrolik, burkulma
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4"/></svg></a>
  </div>
</section>

{{-- ================= PNÖMATİK REHBER ================= --}}
<section class="sec" id="rehber" aria-labelledby="guide-title">
  <div class="wrap split">
    <div>
      <span class="eyebrow rv">Pnömatik rehber</span>
      <h2 id="guide-title" class="h2 rv">Doğru seçim için bilmeniz gerekenler.</h2>
      <p class="lead rv">Pnömatik sistem kurarken ve yenilerken en sık karşılaşılan teknik soruların kısa ve net cevapları.</p>
      <a href="#hesaplayici" class="btn btn-g rv" style="margin-top:22px">Kuvveti hesaplayıcıyla bulun
        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 8h10M9 4l4 4-4 4"/></svg></a>
    </div>
    <div class="faq guide">
      @foreach ($guide as $gd)
        <details class="rv" @if ($loop->first) open @endif>
          <summary>{{ $gd[0] }}<i aria-hidden="true"></i></summary>
          <p>{{ $gd[1] }}</p>
        </details>
      @endforeach
    </div>
  </div>
</section>

{{-- ================= FACTS (GEO) + FAQ ================= --}}
<section class="sec alt" id="sss" aria-labelledby="faq-title">
  <div class="wrap split">
    <aside class="facts rv" aria-labelledby="facts-title">
      <span class="eyebrow" id="facts-title">Kısaca Ensis</span>
      <dl>
        <dt>Unvan</dt><dd>{{ $site['legal'] }}</dd>
        <dt>Kuruluş</dt><dd>{{ $site['founded'] }}</dd>
        <dt>Merkez</dt><dd>{{ $site['street'] }}, {{ $site['district'] }}/{{ $site['city'] }}</dd>
        <dt>Çalışma</dt><dd>Hafta içi 08.30–18.00, Cumartesi 08.30–13.00, Pazar kapalı</dd>
        <dt>Markalar</dt><dd>{{ implode(', ', array_column($brands, 'name')) }}</dd>
        <dt>Alanlar</dt><dd>{{ implode(', ', array_column($areas, 1)) }}</dd>
        <dt>Telefon</dt><dd><a href="tel:{{ $site['phoneIntl'] }}">{{ $site['phone'] }}</a></dd>
        <dt>WhatsApp</dt><dd><a href="https://wa.me/{{ $site['whatsapp'] }}" rel="noopener" target="_blank">{{ $site['gsm'] }}</a></dd>
        <dt>E-posta</dt><dd><a href="mailto:{{ $site['email'] }}">{{ $site['email'] }}</a></dd>
        <dt>Grup</dt><dd>Flutecnica Akışkan Teknolojileri</dd>
        <dt>Güncelleme</dt><dd><time datetime="{{ $updated }}">{{ \Carbon\Carbon::parse($updated)->format('d.m.Y') }}</time></dd>
      </dl>
    </aside>
    <div>
      <span class="eyebrow rv">Sık sorulanlar</span>
      <h2 id="faq-title" class="h2 rv">Merak edilenler.</h2>
      <div class="faq">
        @foreach ($faqs as $f)
          <details class="rv" @if ($loop->first) open @endif>
            <summary>{{ $f[0] }}<i aria-hidden="true"></i></summary>
            <p>{{ $f[1] }}</p>
          </details>
        @endforeach
      </div>
    </div>
  </div>
</section>

{{-- ================= GROUP ================= --}}
<section class="sec" aria-labelledby="group-title">
  <div class="wrap">
    <span class="eyebrow rv">Grup şirketimiz</span>
    <h2 id="group-title" class="h2 rv">Akışkan teknolojilerinde iki güçlü adres.</h2>
    <div class="grp rv">
      <div class="grp-main">
        <p class="lead">Kardeş şirketimiz <b>Flutecnica Akışkan Teknolojileri</b>; krom kaplı miller, honlu borular, hidrolik-pnömatik bileşenler ve hassas hareket ürünlerinde uzmandır. Silindir imalatından otomasyona kadar ihtiyaçlarınızı grup olarak karşılıyoruz.</p>
        <ul class="grp-feat">
          <li><i aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 12h18"/><path d="M5 9h14v6H5z"/></svg></i><span><b>Krom kaplı miller</b><small>Silindir ve doğrusal hareket için</small></span></li>
          <li><i aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4.5"/></svg></i><span><b>Honlu borular</b><small>Hidrolik ve pnömatik gövdeler</small></span></li>
          <li><i aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 8h10v8H4zM14 11h6v2h-6"/></svg></i><span><b>Hidrolik-pnömatik bileşenler</b><small>Silindir parçaları ve aksesuarlar</small></span></li>
          <li><i aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 17h18M6 17V9l6-4 6 4v8"/><path d="M10 13h4"/></svg></i><span><b>Hassas hareket</b><small>Lineer kızak ve rulman çözümleri</small></span></li>
        </ul>
      </div>
      <aside class="grp-card" aria-label="Flutecnica Akışkan Teknolojileri">
        <span class="g-tag">Grup şirketi</span>
        <a class="grp-logo{{ $flLogo ? '' : ' is-jpg' }}" href="https://www.flutecnica.com" target="_blank" rel="noopener" aria-label="Flutecnica web sitesi">
          <img src="{{ asset($flLogo ?: 'uploads/contents/3-1714936885-1.jpg') }}" alt="Flutecnica Akışkan Teknolojileri logosu" width="260" height="90" loading="lazy" decoding="async" onerror="this.outerHTML='&lt;b class=&quot;grp-logo-txt&quot;&gt;Flutecnica&lt;/b&gt;'">
        </a>
        <p class="grp-slogan">Akışkan teknolojilerinde hassas çözümler</p>
        <dl class="grp-facts">
          <div><dt>Stok</dt><dd>Ø4–200 mm</dd></div>
          <div><dt>Sevkiyat</dt><dd>24–48 saat</dd></div>
          <div><dt>Şube</dt><dd>Bursa · İstanbul</dd></div>
        </dl>
        <div class="grp-acts">
          <a href="https://www.flutecnica.com" class="btn btn-w" rel="noopener" target="_blank">flutecnica.com'u ziyaret et
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M5 11 11 5M6 5h5v5"/></svg></a>
          <a href="tel:+902244136161" class="btn btn-o">0224 413 61 61</a>
        </div>
      </aside>
    </div>
  </div>
</section>

{{-- ================= CTA ================= --}}
<section class="sec cta" aria-labelledby="cta-title">
  <div class="wrap">
    <span class="eyebrow rv">Teklif &amp; katalog</span>
    <h2 id="cta-title" class="h2 grad rv">Ürün kodunu gönderin, gerisini biz halledelim.</h2>
    <p class="lead rv">Teklif formu, telefon veya WhatsApp üzerinden aynı gün dönüş hedefliyoruz.</p>
    <div class="hero-actions rv">
      <a href="{{ url('iletisim') }}#form" class="btn btn-p">Teklif İste</a>
      <a href="{{ url('catalog_send') }}" class="btn btn-g">Katalog Talep Et</a>
    </div>
    <div class="cta-alt rv">
      <a href="tel:{{ $site['phoneIntl'] }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>{{ $site['phone'] }}</a>
      <span aria-hidden="true">·</span>
      <a href="https://wa.me/{{ $site['whatsapp'] }}" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 20l1.3-3.8A8 8 0 1 1 8 19.3L4 20Z"/></svg>WhatsApp {{ $site['gsm'] }}</a>
    </div>
  </div>
</section>

{{-- ================= CONTACT ================= --}}
<section class="sec alt" id="iletisim" aria-labelledby="contact-title">
  <div class="wrap">
    <span class="eyebrow rv">İletişim</span>
    <h2 id="contact-title" class="h2 rv">Nilüfer Ticaret Merkezi'ndeyiz.</h2>
    <div class="contact">
      <address class="c-list rv" style="font-style:normal">
        <a class="c-item" href="tel:{{ $site['phoneIntl'] }}">
          <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg></span>
          <span><small>Telefon</small><b>{{ $site['phone'] }}</b></span>
        </a>
        <a class="c-item" href="https://wa.me/{{ $site['whatsapp'] }}" rel="noopener" target="_blank">
          <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 20l1.3-3.8A8 8 0 1 1 8 19.3L4 20Z"/></svg></span>
          <span><small>WhatsApp</small><b>{{ $site['gsm'] }}</b></span>
        </a>
        <a class="c-item" href="mailto:{{ $site['email'] }}">
          <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span>
          <span><small>E-posta</small><b>{{ $site['email'] }}</b></span>
        </a>
        <a class="c-item" href="{{ $site['mapLink'] }}" rel="noopener" target="_blank">
          <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 21s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/></svg></span>
          <span><small>Bursa Merkez</small><b>{{ $site['street'] }}, {{ $site['district'] }}/{{ $site['city'] }}</b></span>
        </a>
        <div class="c-item">
          <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
          <span><small>Çalışma saatleri <i class="open-badge" data-open-badge hidden></i></small><b>{{ $site['hours'] }}</b><em class="c-note">Pazar kapalı</em></span>
        </div>
      </address>
      <div class="map rv" data-map="{{ $site['map'] }}">
        <button class="map-btn" type="button">
          <span class="map-pin"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 21s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/></svg></span>
          Haritayı yükle
        </button>
      </div>
    </div>
  </div>
</section>

</main>

{{-- ================= FOOTER ================= --}}
<footer class="ftr">
  <div class="wrap">
    <div class="f-cta">
      <div>
        <b>Doğru parçayı birlikte bulalım.</b>
        <p>Ürün kodu ya da uygulama bilgisiyle yazın; teklifinizi hızlıca hazırlayalım.</p>
      </div>
      <div class="f-cta-acts">
        <a href="{{ url('iletisim') }}#form" class="btn btn-fw">Teklif İste</a>
        <a href="tel:{{ $site['phoneIntl'] }}" class="btn btn-fo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>{{ $site['phone'] }}</a>
        <a href="https://wa.me/{{ $site['whatsapp'] }}" rel="noopener" target="_blank" class="btn btn-fwa"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 20l1.3-3.8A8 8 0 1 1 8 19.3L4 20Z"/></svg>WhatsApp</a>
      </div>
    </div>

    <div class="f-grid">
      <div class="f-about">
        <a href="{{ url('/') }}" class="f-logo" aria-label="{{ $site['name'] }} — Ana sayfa"><img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" width="150" height="34" loading="lazy"></a>
        <p>2007'den beri pnömatik, hidrolik, vakum, otomasyon, doğrusal hareket ve pres emniyet sistemlerinde dünya markalarıyla Bursa'dan hizmet veriyoruz.</p>
        <address class="f-nap">
          <a href="{{ $site['mapLink'] }}" rel="noopener" target="_blank"><i class="f-ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/></svg></i><span>{{ $site['street'] }}, {{ $site['district'] }}/{{ $site['city'] }}</span></a>
          <a href="mailto:{{ $site['email'] }}"><i class="f-ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></i><span>{{ $site['email'] }}</span></a>
          <span><i class="f-ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></i><span>{{ $site['hours'] }}<i class="open-badge" data-open-badge hidden></i><em>Pazar kapalı</em></span></span>
        </address>
      </div>
      <nav aria-label="Ürün grupları">
        <h4>Ürün grupları</h4>
        <ul>
          @foreach (array_slice($groups, 0, 6) as $g)
            <li><a href="{{ url('category/' . $g['slug']) }}">{{ $g['name'] }}</a></li>
          @endforeach
          <li><a href="{{ url('urunlerimiz') }}">Tüm kategoriler →</a></li>
        </ul>
      </nav>
      <nav aria-label="Markalar">
        <h4>Markalar</h4>
        <ul>
          @foreach ($brands as $b)
            <li><a href="{{ url('category/' . $b['slug']) }}">{{ $b['name'] }} <small>{{ $b['origin'] }}</small></a></li>
          @endforeach
          <li><a href="https://www.flutecnica.com" rel="noopener" target="_blank">Flutecnica <small>grup şirketimiz</small></a></li>
        </ul>
      </nav>
      <nav aria-label="Kurumsal">
        <h4>Kurumsal</h4>
        <ul>
          <li><a href="{{ url('hakkimizda') }}">Hakkımızda</a></li>
          <li><a href="{{ url('politikalarimiz') }}">Politikalarımız</a></li>
          <li><a href="{{ url('partner-markalarimiz') }}">Partner Markalarımız</a></li>
          <li><a href="{{ url('catalog_send') }}">Katalog talebi</a></li>
          <li><a href="{{ url('hesaplamalar') }}">Teknik hesaplamalar</a></li>
          <li><a href="{{ url('blogs') }}">Blog</a></li>
          <li><a href="{{ url('iletisim') }}">İletişim</a></li>
        </ul>
      </nav>
    </div>

    <div class="f-bot">
      <span>© {{ date('Y') }} {{ $site['legal'] }} · Tüm marka adları ilgili sahiplerine aittir.</span>
      <span class="f-legal"><a href="{{ url('kvkk-aydinlatma-metni') }}">KVKK Aydınlatma Metni</a> · <a href="{{ url('kvkk-aydinlatma-metni') }}#cerez">Çerez Politikası</a> · <a href="#" data-ck-open>Çerez tercihleri</a></span>
      <a href="#" class="f-top" onclick="window.scrollTo({top:0,behavior:'smooth'});return false;">Başa dön <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M8 13V3M4 7l4-4 4 4"/></svg></a>
    </div>
  </div>
</footer>

{{-- Floating WhatsApp (desktop/tablet) --}}
<a class="wa" href="https://wa.me/{{ $site['whatsapp'] }}" rel="noopener" target="_blank" aria-label="WhatsApp">
  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1s-.5-.1-.7.1-.8 1-1 1.2-.4.2-.7.1a8 8 0 0 1-2.4-1.5 9 9 0 0 1-1.6-2c-.2-.3 0-.5.1-.6l.5-.5.3-.5v-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6a1.1 1.1 0 0 0-.8.4 3.4 3.4 0 0 0-1 2.5 6 6 0 0 0 1.2 3.1 13.4 13.4 0 0 0 5.2 4.6c1.9.8 2.7.9 3.6.7a3 3 0 0 0 2-1.4 2.5 2.5 0 0 0 .2-1.4c-.1-.2-.3-.3-.6-.4ZM12 21.8a9.8 9.8 0 0 1-5-1.4l-.4-.2-3.7 1 1-3.6-.2-.4A9.8 9.8 0 1 1 12 21.8Zm0-21.6A11.8 11.8 0 0 0 1.9 18L.2 24l6.1-1.6A11.8 11.8 0 1 0 12 .2Z"/></svg>
</a>

{{-- Mobile action bar --}}
<nav class="mbar" aria-label="Hızlı iletişim">
  <a href="tel:{{ $site['phoneIntl'] }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>Ara</a>
  <a class="wa-m" href="https://wa.me/{{ $site['whatsapp'] }}" rel="noopener" target="_blank"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 20l1.3-3.8A8 8 0 1 1 8 19.3L4 20Z"/></svg>WhatsApp</a>
  <a class="q" href="{{ url('iletisim') }}#form"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5M10 13h6M10 17h6"/></svg>Teklif</a>
</nav>

<script>
(() => {
  const hdr = document.getElementById('hdr');
  const onScroll = () => hdr.classList.toggle('is-scrolled', window.scrollY > 8);
  onScroll(); window.addEventListener('scroll', onScroll, { passive: true });

  // drawer
  const btn = document.querySelector('.nav-toggle'), drawer = document.getElementById('drawer');
  const setOpen = (o) => { btn.setAttribute('aria-expanded', o); btn.setAttribute('aria-label', o ? 'Menüyü kapat' : 'Menüyü aç'); drawer.setAttribute('aria-hidden', !o); drawer.classList.toggle('open', o); document.body.classList.toggle('lock', o); if (o) hdr.classList.add('is-scrolled'); else onScroll(); };
  btn.addEventListener('click', () => setOpen(btn.getAttribute('aria-expanded') !== 'true'));
  drawer.querySelectorAll('a').forEach(a => a.addEventListener('click', () => setOpen(false)));
  // masaüstü açılır menüler: dokunmatik ekran ve Safari için tıklayınca aç/kapat
  const ddBtns = document.querySelectorAll('.dd-btn');
  const closeDd = except => ddBtns.forEach(b => { if (b !== except) { b.setAttribute('aria-expanded', 'false'); b.parentNode.classList.remove('open'); } });
  ddBtns.forEach(b => b.addEventListener('click', e => {
    e.stopPropagation();
    const o = b.getAttribute('aria-expanded') !== 'true';
    closeDd(b); b.setAttribute('aria-expanded', o); b.parentNode.classList.toggle('open', o);
  }));
  document.addEventListener('click', e => { if (!e.target.closest('.nav')) closeDd(null); });

  document.addEventListener('keydown', e => { if (e.key === 'Escape') { setOpen(false); closeDd(null); } });
  const mq = window.matchMedia('(min-width:1025px)'), onMq = e => { if (e.matches) setOpen(false); };
  if (mq.addEventListener) mq.addEventListener('change', onMq); else if (mq.addListener) mq.addListener(onMq);

  // Analytics dönüşüm olayları: telefon, WhatsApp, e-posta, teklif, katalog
  const track = (name, params) => { try { if (window.gtag) gtag('event', name, params || {}); } catch (e) {} };
  document.addEventListener('click', e => {
    const a = e.target.closest('a'); if (!a) return;
    const h = a.getAttribute('href') || '';
    if (h.indexOf('tel:') === 0) track('telefon_tikla', { link_url: h });
    else if (h.indexOf('wa.me') > -1) track('whatsapp_tikla', { link_url: h });
    else if (h.indexOf('mailto:') === 0) track('eposta_tikla', { link_url: h });
    else if (h.indexOf('#form') > -1) track('teklif_tikla', { link_text: (a.textContent || '').trim().slice(0, 60) });
    else if (h.indexOf('catalog_send') > -1) track('katalog_tikla');
  });

  // reveal on scroll
  const els = document.querySelectorAll('.rv');
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } }), { rootMargin: '0px 0px -8% 0px' });
    els.forEach(el => io.observe(el));
  } else els.forEach(el => el.classList.add('in'));

  // counters
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!reduce) document.querySelectorAll('[data-count]').forEach(el => {
    const end = +el.dataset.count, suf = el.dataset.suffix || '';
    const io = new IntersectionObserver(([e]) => {
      if (!e.isIntersecting) return; io.disconnect();
      const t0 = performance.now(), d = 1200;
      const step = now => { const p = Math.min((now - t0) / d, 1); el.textContent = Math.round(end * (1 - Math.pow(1 - p, 3))) + suf; if (p < 1) requestAnimationFrame(step); };
      requestAnimationFrame(step);
    });
    io.observe(el);
  });

  // brand card glow follows cursor
  document.querySelectorAll('.brand').forEach(c => c.addEventListener('pointermove', e => {
    const r = c.getBoundingClientRect(); c.style.setProperty('--mx', ((e.clientX - r.left) / r.width * 100) + '%');
  }));

  // kuvvet hesaplayıcı
  const cb = document.getElementById('c-bore');
  if (cb) {
    const $ = id => document.getElementById(id);
    const rod = $('c-rod'), pr = $('c-p'), st = $('c-stroke'), cy = $('c-cyc'), q = $('r-quote');
    const tr = (n, d) => n.toLocaleString('tr-TR', { maximumFractionDigits: d || 0 });
    const setRod = () => { rod.value = cb.options[cb.selectedIndex].getAttribute('data-rod'); };
    const run = () => {
      const D = +cb.value, d = Math.max(0, Math.min(+rod.value || 0, D - 1)), P = +pr.value, S = Math.max(0, +st.value || 0), C = Math.max(0, +cy.value || 0);
      const A = Math.PI * D * D / 4, Ar = Math.PI * (D * D - d * d) / 4;
      const push = P * 0.1 * A, pull = P * 0.1 * Ar;
      const air = (A + Ar) * S * 1e-6 * ((P + 1.013) / 1.013) * C;
      $('c-p-out').textContent = tr(P, 1) + ' bar';
      $('r-push').textContent = tr(push) + ' N';  $('r-push-kg').textContent = '≈ ' + tr(push / 9.81) + ' kgf';
      $('r-pull').textContent = tr(pull) + ' N';  $('r-pull-kg').textContent = '≈ ' + tr(pull / 9.81) + ' kgf';
      $('r-prac').textContent = tr(push * 0.85) + ' N';
      $('r-air').textContent = tr(air, 1);
      q.href = q.getAttribute('data-base') + '?not=' + encodeURIComponent('Ø' + D + ' x ' + S + ' mm pnömatik silindir, ' + tr(P, 1) + ' bar') + '#form';
      // canlı kesit görseli: silindir ve rot çapı oranı
      const vb = $('v-bore'), vr = $('v-rod');
      if (vb && vr) {
        const R = 16 + 38 * Math.sqrt(D / 320), rr = R * (d / D);
        vb.style.setProperty('r', R.toFixed(1) + 'px'); vr.style.setProperty('r', rr.toFixed(1) + 'px');
        vb.setAttribute('r', R.toFixed(1)); vr.setAttribute('r', rr.toFixed(1));
        $('v-t').textContent = 'Ø' + D + ' / rot Ø' + d;
        $('v-a').textContent = 'Piston alanı ' + tr(A / 100, 1) + ' cm² · halka alanı ' + tr(Ar / 100, 1) + ' cm²';
      }
    };
    let used = false;
    const onUse = () => { if (!used) { used = true; track('hesaplayici_kullan'); } };
    cb.addEventListener('change', () => { setRod(); run(); onUse(); });
    [rod, pr, st, cy].forEach(el => el.addEventListener('change', onUse));
    [rod, pr, st, cy].forEach(el => el.addEventListener('input', run));
    setRod(); run();
  }

  // çalışma saatleri: şu an açık mı? (İstanbul saati)
  const badges = document.querySelectorAll('[data-open-badge]');
  if (badges.length) try {
    const parts = new Intl.DateTimeFormat('en-GB', { timeZone: 'Europe/Istanbul', weekday: 'short', hour: '2-digit', minute: '2-digit', hour12: false }).formatToParts(new Date());
    const get = t => (parts.find(x => x.type === t) || {}).value;
    const day = get('weekday'), m = (+get('hour') % 24) * 60 + (+get('minute'));
    const wk = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'].indexOf(day) > -1;
    const open = (wk && m >= 510 && m < 1080) || (day === 'Sat' && m >= 510 && m < 780);
    badges.forEach(function (badge) {
      badge.textContent = open ? 'Şu an açık' : 'Şu an kapalı';
      badge.className = 'open-badge ' + (open ? 'on' : 'off');
      badge.hidden = false;
    });
  } catch (e) {}

  // map: load on demand (PageSpeed)
  const map = document.querySelector('[data-map]');
  if (map) map.querySelector('button').addEventListener('click', () => {
    const f = document.createElement('iframe');
    f.src = map.dataset.map; f.loading = 'lazy'; f.referrerPolicy = 'no-referrer-when-downgrade';
    f.title = 'Ensis Pnömatik konum haritası';
    map.innerHTML = ''; map.appendChild(f);
  }, { once: true });
})();
  // ---------- intro: ~3.5 sn sonra veya "Geç"/Esc/tıklama ile kapanır ----------
  (function () {
    var root = document.documentElement, ov = document.getElementById('intro');
    if (!ov || !root.classList.contains('intro')) { if (ov) ov.remove(); return; }
    try { sessionStorage.setItem('ensisIntro', '1'); } catch (e) {}
    var done = false;
    var close = function () {
      if (done) return; done = true;
      ov.classList.add('out');
      setTimeout(function () { root.classList.remove('intro'); ov.remove(); document.dispatchEvent(new Event('ensis:intro-done')); }, 720);
      document.removeEventListener('keydown', onKey);
    };
    var onKey = function (e) { if (e.key === 'Escape' || e.key === 'Enter' || e.key === ' ') close(); };
    ov.addEventListener('click', close);
    document.addEventListener('keydown', onKey);
    setTimeout(close, 3500);
  })();
  // ---------- hero teknik resmi: kalem sırayla çizer (giriş bittikten sonra), sonra mil ve taşıyıcı çalışır ----------
  (function () {
    var svg = document.getElementById('hs'); if (!svg) return;
    var pen = document.getElementById('hs-pen'), root = document.documentElement;
    var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
    try {
      if (window.matchMedia && matchMedia('(max-width:700px)').matches) svg.setAttribute('viewBox', '50 40 900 560');
      var lines = [].slice.call(svg.querySelectorAll('.ln')).filter(function (el) { return el.getClientRects().length; });
      var lens = lines.map(function (el) { var L = 0; try { L = el.getTotalLength(); } catch (e) {} return L || 1; });
      var showAll = function () {
        svg.querySelectorAll('text').forEach(function (t) { t.classList.add('on'); });
        var lg = svg.querySelector('.tb-logo'); if (lg) lg.classList.add('on');
      };
      lines.forEach(function (el, i) { if (!el.classList.contains('hid')) { el.style.strokeDasharray = lens[i] + ' ' + lens[i]; el.style.strokeDashoffset = lens[i]; } else el.style.opacity = 0; });
      svg.classList.add('armed');
      var finish = function () {
        lines.forEach(function (el) { if (el.classList.contains('hid')) el.style.opacity = 1; else { el.style.strokeDasharray = ''; el.style.strokeDashoffset = ''; } });
        showAll(); svg.classList.remove('drawing');
        setTimeout(function () { svg.classList.add('live'); }, 400);
      };
      if (reduce) { finish(); return; }
      var started = false;
      var start = function () {
        if (started) return; started = true;
        svg.classList.add('drawing');
        var DRAW = 2800, total = lens.reduce(function (a, b) { return a + Math.sqrt(b); }, 0), starts = [], acc = 0;
        lens.forEach(function (L) { starts.push(acc); acc += Math.sqrt(L) / total * DRAW; });
        var sm = svg.getScreenCTM ? svg.getScreenCTM() : null, inv = sm ? sm.inverse() : null, t0 = performance.now();
        setTimeout(function () { svg.querySelectorAll('.hs-lbl').forEach(function (t) { t.classList.add('on'); }); }, 300);
        setTimeout(function () { svg.querySelectorAll('.hs-dim').forEach(function (t) { t.classList.add('on'); }); }, 1900);
        var step = function (now) {
          var t = now - t0;
          for (var i = 0; i < lines.length; i++) {
            var d = (i + 1 < lines.length ? starts[i + 1] : DRAW) - starts[i];
            var p = Math.max(0, Math.min(1, (t - starts[i]) / d)), el = lines[i];
            if (el.classList.contains('hid')) { if (p > 0) el.style.opacity = 1; }
            else el.style.strokeDashoffset = lens[i] * (1 - p);
            if (p > 0 && p < 1 && pen && inv) {
              try {
                var pt = el.getPointAtLength(lens[i] * p).matrixTransform(el.getScreenCTM()).matrixTransform(inv);
                pen.setAttribute('transform', 'translate(' + pt.x.toFixed(1) + ',' + pt.y.toFixed(1) + ')');
              } catch (e) {}
            }
          }
          if (t < DRAW + 80) requestAnimationFrame(step); else finish();
        };
        requestAnimationFrame(step);
      };
      if (root.classList.contains('intro')) {
        document.addEventListener('ensis:intro-done', function () { setTimeout(start, 150); });
        setTimeout(start, 7300);   // giriş güvenlik kapanışıyla kalktıysa
      } else setTimeout(start, 350);
    } catch (e) { svg.classList.remove('armed'); [].forEach.call(svg.querySelectorAll('.ln'), function (el) { el.style.strokeDasharray = ''; el.style.strokeDashoffset = ''; el.style.opacity = ''; }); }
  })();
</script>
{{-- v4.0 CİLA: kaydırma çubuğu · hero fare ışığı · teknik resim paftasında hafif eğim (yalnız fare; "hareketi azalt"ta kapalı) --}}
<script>
(function () {
  var bar = document.getElementById('sp-i'), tick = false;
  var RM = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var FINE = window.matchMedia && matchMedia('(hover: hover) and (pointer: fine)').matches;
  function prog() {
    if (!bar) return;
    var d = document.documentElement, m = d.scrollHeight - d.clientHeight;
    bar.style.transform = 'scaleX(' + (m > 0 ? Math.min(1, Math.max(0, (window.pageYOffset || d.scrollTop) / m)) : 0).toFixed(4) + ')';
  }
  window.addEventListener('scroll', function () {
    if (tick) return; tick = true;
    requestAnimationFrame(function () { tick = false; prog(); });
  }, { passive: true });
  prog();
  if (RM || !FINE) return;

  var hero = document.querySelector('.hero');
  if (hero) {
    var spot = document.createElement('div');
    spot.className = 'hero-spot'; spot.setAttribute('aria-hidden', 'true');
    hero.insertBefore(spot, hero.firstChild);
    var sRaf = 0, sx = 0, sy = 0;
    hero.addEventListener('pointermove', function (e) {
      var r = hero.getBoundingClientRect();
      sx = e.clientX - r.left; sy = e.clientY - r.top;
      spot.classList.add('on');
      if (sRaf) return;
      sRaf = requestAnimationFrame(function () { sRaf = 0; spot.style.transform = 'translate3d(' + sx.toFixed(0) + 'px,' + sy.toFixed(0) + 'px,0)'; });
    });
    hero.addEventListener('pointerleave', function () { spot.classList.remove('on'); });
  }
  var card = document.querySelector('.hs-card');
  if (card) {
    var tRaf = 0, rx = 0, ry = 0;
    card.addEventListener('pointerenter', function () { card.classList.add('tilt'); });
    card.addEventListener('pointermove', function (e) {
      var r = card.getBoundingClientRect();
      rx = -((e.clientY - r.top) / r.height - 0.5) * 4;
      ry = ((e.clientX - r.left) / r.width - 0.5) * 5;
      if (tRaf) return;
      tRaf = requestAnimationFrame(function () {
        tRaf = 0;
        card.style.transform = 'perspective(1100px) rotateX(' + rx.toFixed(2) + 'deg) rotateY(' + ry.toFixed(2) + 'deg)';
      });
    });
    card.addEventListener('pointerleave', function () { card.style.transform = ''; });
  }
})();
</script>
{{-- ================= ÇEREZ BANDI (KVKK) ================= --}}
<div class="ck" id="ck" role="dialog" aria-modal="false" aria-labelledby="ck-t" aria-describedby="ck-d" hidden>
  <div class="ck-in">
    <div class="ck-txt">
      <b id="ck-t">Çerez tercihiniz</b>
      <p id="ck-d">Sitenin çalışması için zorunlu çerezler kullanılır. İzin verirseniz ziyaret istatistikleri için Google Analytics çerezleri de etkinleşir. Ayrıntılar <a href="{{ url('kvkk-aydinlatma-metni') }}#cerez">Çerez Politikası</a>'nda.</p>
    </div>
    <div class="ck-acts">
      <button type="button" class="ck-b ck-no" data-ck="necessary">Yalnızca zorunlu</button>
      <button type="button" class="ck-b ck-yes" data-ck="all">Kabul et</button>
    </div>
  </div>
</div>
<script>
(function () {
  var box = document.getElementById('ck'); if (!box) return;
  var KEY = 'ensisCerez', get = function () { try { return localStorage.getItem(KEY); } catch (e) { return null; } };
  var apply = function (v) {
    if (typeof gtag === 'function') gtag('consent', 'update', { analytics_storage: v === 'all' ? 'granted' : 'denied' });
  };
  var open = function () { box.hidden = false; };
  var choose = function (v) {
    try { localStorage.setItem(KEY, v); } catch (e) {}
    apply(v); box.hidden = true;
  };
  [].forEach.call(box.querySelectorAll('[data-ck]'), function (b) { b.addEventListener('click', function () { choose(b.getAttribute('data-ck')); }); });
  [].forEach.call(document.querySelectorAll('[data-ck-open]'), function (a) { a.addEventListener('click', function (e) { e.preventDefault(); open(); }); });
  if (!get()) {
    // giriş animasyonu varsa bitince göster
    var show = function () { setTimeout(open, 600); };
    if (document.documentElement.classList.contains('intro')) { document.addEventListener('ensis:intro-done', show); setTimeout(open, 7600); }
    else show();
  }
})();
</script>
</body>
</html>
