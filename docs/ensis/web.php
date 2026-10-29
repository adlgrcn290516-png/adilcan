<?php
Route::group(['prefix' => 'admin', 'namespace' => 'Admin'], function (){
    Route::redirect('/','/admin/login'); // bir yere gelen istekleri başka yere yönlendirme

    // admin giriş çıkış
    Route::match(['get','post'],'/login', 'AuthController@login')->name('admin.login');
    Route::get('/logout', 'AuthController@logout')->name('admin.logout');

    Route::group(['middleware' => 'admin'], function(){
        Route::get('/home', 'HomeController@index')->name('admin.home');
        Route::get('/katalog_talep', 'HomeController@katalog_talep')->name('admin.katalog_talep');



        // kullnıcı kontrolü
        Route::group(['prefix' => 'user'], function (){
           Route::match(['get','post'],'/', 'UserController@index')->name('admin.user');
           Route::get('/new', 'UserController@form')->name('admin.user.new');
           Route::get('/edit/{id}', 'UserController@form')->name('admin.user.edit');
           Route::post('/save/{id?}', 'UserController@save')->name('admin.user.save');
           Route::get('/delete/{id}', 'UserController@delete')->name('admin.user.delete');
        });

        Route::group(['prefix' => 'category'], function (){
            Route::match(['get','post'],'/', 'CategoryController@index')->name('admin.category');
            Route::get('/new', 'CategoryController@form')->name('admin.category.new');
            Route::get('/edit/{id}', 'CategoryController@form')->name('admin.category.edit');
            Route::post('/save/{id?}', 'CategoryController@save')->name('admin.category.save');
            Route::get('/delete/{id}', 'CategoryController@delete')->name('admin.category.delete');
            Route::post('/position', 'CategoryController@position')->name('admin.category.position');
            Route::get('/productorder/{id}', 'CategoryController@productorder')->name('admin.category.productorder');
            Route::get('/categoryorder', 'CategoryController@categoryorder')->name('admin.category.categoryorder');
        });

        Route::group(['prefix' => 'brand'], function (){
            Route::match(['get','post'],'/', 'BrandController@index')->name('admin.brand');
            Route::get('/new', 'BrandController@form')->name('admin.brand.new');
            Route::get('/edit/{id}', 'BrandController@form')->name('admin.brand.edit');
            Route::post('/save/{id?}', 'BrandController@save')->name('admin.brand.save');
            Route::get('/delete/{id}', 'BrandController@delete')->name('admin.brand.delete');
            Route::post('/position', 'BrandController@position')->name('admin.brand.position');
        });

        Route::group(['prefix' => 'lang'], function (){
            Route::match(['get','post'],'/', 'LanguageController@index')->name('admin.lang');
            Route::get('/new', 'LanguageController@form')->name('admin.lang.new');
            Route::get('/edit/{id}', 'LanguageController@form')->name('admin.lang.edit');
            Route::post('/save/{id?}', 'LanguageController@save')->name('admin.lang.save');
            Route::get('/delete/{id}', 'LanguageController@delete')->name('admin.lang.delete');
            Route::get('/item/{id?}', 'LanguageController@item')->name('admin.lang.item');
            Route::post('/item/save', 'LanguageController@itemsave')->name('admin.lang.item.save');
        });

        Route::group(['prefix' => 'langitem'], function (){
            Route::get('/', 'LanguageItemController@index')->name('admin.langitem');
            Route::post('/', 'LanguageItemController@update')->name('admin.langitem.update');
            Route::get('/new', 'LanguageItemController@form')->name('admin.langitem.new');
            Route::post('/save/{id?}', 'LanguageItemController@save')->name('admin.langitem.save');
            Route::get('/delete/{id}', 'LanguageItemController@delete')->name('admin.langitem.delete');
        });

        Route::group(['prefix' => 'product'], function (){
            Route::match(['get','post'],'/', 'ProductController@index')->name('admin.product');
            Route::get('/new', 'ProductController@form')->name('admin.product.new');
            Route::get('/edit/{id}', 'ProductController@form')->name('admin.product.edit');
            Route::post('/save/{id?}', 'ProductController@save')->name('admin.product.save');
            Route::get('/delete/{id}', 'ProductController@delete')->name('admin.product.delete');
            Route::get('/filedelete', 'ProductController@filedelete')->name('admin.product.filedelete');
            Route::post('/pdfdelete', 'ProductController@pdfdelete')->name('admin.product.pdfdelete');
            Route::post('/position', 'ProductController@position')->name('admin.product.position');

            Route::post('/feature', 'ProductController@feature')->name('admin.product.feature');
            Route::get('/featuredelete', 'ProductController@featuredelete')->name('admin.product.featuredelete');
            Route::post('/video', 'ProductController@video')->name('admin.product.video');
            Route::get('/videodelete', 'ProductController@videodelete')->name('admin.product.videodelete');
        });

        Route::group(['prefix' => 'setting'], function (){
            Route::get('/', 'SettingController@index')->name('admin.setting');
            Route::post('/', 'SettingController@update')->name('admin.setting.update');
            Route::get('/new', 'SettingController@form')->name('admin.setting.new');
            Route::post('/save/{id?}', 'SettingController@save')->name('admin.setting.save');
            Route::get('/delete/{id}', 'SettingController@delete')->name('admin.setting.delete');
        });

        Route::group(['prefix' => 'menu'], function (){
            Route::match(['get','post'],'/', 'MenuController@index')->name('admin.menu');
            Route::get('/new/{group_id}', 'MenuController@new')->name('admin.menu.new');
            Route::get('/edit/{id}', 'MenuController@form')->name('admin.menu.edit');
            Route::post('/save/{id?}', 'MenuController@save')->name('admin.menu.save');
            Route::get('/delete/{id}', 'MenuController@delete')->name('admin.menu.delete');
            Route::get('/select', 'MenuController@select')->name('admin.menu.select');
            Route::post('/position', 'MenuController@position')->name('admin.menu.position');
        });

        Route::group(['prefix' => 'menugroup'], function (){
            Route::match(['get','post'],'/', 'MenuGroupController@index')->name('admin.menugroup');
            Route::get('/new', 'MenuGroupController@form')->name('admin.menugroup.new');
            Route::get('/edit/{id}', 'MenuGroupController@form')->name('admin.menugroup.edit');
            Route::post('/save/{id?}', 'MenuGroupController@save')->name('admin.menugroup.save');
            Route::get('/delete/{id}', 'MenuGroupController@delete')->name('admin.menugroup.delete');
        });

        Route::group(['prefix' => 'slider'], function (){
            Route::match(['get','post'],'/', 'SliderController@index')->name('admin.slider');
            Route::get('/new', 'SliderController@form')->name('admin.slider.new');
            Route::get('/edit/{id}', 'SliderController@form')->name('admin.slider.edit');
            Route::post('/save/{id?}', 'SliderController@save')->name('admin.slider.save');
            Route::get('/delete/{id}', 'SliderController@delete')->name('admin.slider.delete');
            Route::post('/position', 'SliderController@position')->name('admin.slider.position');
        });

        Route::group(['prefix' => 'content'], function (){
            Route::match(['get','post'],'list/{post_type?}', 'ContentController@index')->name('admin.content.list');
            Route::get('/new/{post_type?}', 'ContentController@new')->name('admin.content.new');
            Route::get('/edit/{id}', 'ContentController@form')->name('admin.content.edit');
            Route::post('/save/{id?}', 'ContentController@save')->name('admin.content.save');
            Route::get('/delete/{id}', 'ContentController@delete')->name('admin.content.delete');
            Route::get('/filedelete', 'ContentController@filedelete')->name('admin.content.filedelete');
            Route::post('/position', 'ContentController@position')->name('admin.content.position');
        });

        Route::group(['prefix' => 'feature'], function (){
            Route::match(['get','post'],'/', 'FeatureController@index')->name('admin.feature');
            Route::get('/new', 'FeatureController@form')->name('admin.feature.new');
            Route::get('/edit/{id}', 'FeatureController@form')->name('admin.feature.edit');
            Route::post('/save/{id?}', 'FeatureController@save')->name('admin.feature.save');
            Route::get('/delete/{id}', 'FeatureController@delete')->name('admin.feature.delete');

            Route::post('/feature', 'FeatureController@feature')->name('admin.feature.feature');
            Route::get('/featuredelete', 'FeatureController@featuredelete')->name('admin.feature.featuredelete');
        });

        Route::group(['prefix' => 'color'], function (){
            Route::match(['get','post'],'/', 'ColorController@index')->name('admin.color');
            Route::get('/new', 'ColorController@form')->name('admin.color.new');
            Route::get('/edit/{id}', 'ColorController@form')->name('admin.color.edit');
            Route::post('/save/{id?}', 'ColorController@save')->name('admin.color.save');
            Route::get('/delete/{id}', 'ColorController@delete')->name('admin.color.delete');
        });

        Route::group(['prefix' => 'order'], function (){
            Route::match(['get','post'],'/', 'OrderController@index')->name('admin.order');
            Route::get('/new', 'OrderController@form')->name('admin.order.new');
            Route::get('/edit/{id}', 'OrderController@form')->name('admin.order.edit');
            Route::post('/save/{id?}', 'OrderController@save')->name('admin.order.save');
            Route::get('/delete/{id}', 'OrderController@delete')->name('admin.order.delete');
        });

        Route::group(['prefix' => 'seller'], function (){
            Route::match(['get','post'],'/', 'SellerController@index')->name('admin.seller');
            Route::get('/new', 'SellerController@form')->name('admin.seller.new');
            Route::get('/edit/{id}', 'SellerController@form')->name('admin.seller.edit');
            Route::post('/save/{id?}', 'SellerController@save')->name('admin.seller.save');
            Route::get('/delete/{id}', 'SellerController@delete')->name('admin.seller.delete');
            Route::get('/filedelete', 'SellerController@filedelete')->name('admin.seller.filedelete');
            Route::post('/position', 'SellerController@position')->name('admin.seller.position');
        });

        Route::group(['prefix' => 'reservation'], function (){
            Route::match(['get','post'],'/', 'ReservationController@index')->name('admin.reservation');
            Route::get('/new', 'ReservationController@form')->name('admin.reservation.new');
            Route::get('/edit/{id}', 'ReservationController@form')->name('admin.reservation.edit');
            Route::post('/save/{id?}', 'ReservationController@save')->name('admin.reservation.save');
            Route::get('/delete/{id}', 'ReservationController@delete')->name('admin.reservation.delete');
        });

        Route::group(['prefix' => 'vcard'], function (){
            Route::match(['get','post'],'/', 'VcardController@index')->name('admin.vcard');
            Route::get('/new', 'VcardController@form')->name('admin.vcard.new');
            Route::get('/edit/{id}', 'VcardController@form')->name('admin.vcard.edit');
            Route::post('/save/{id?}', 'VcardController@save')->name('admin.vcard.save');
            Route::get('/delete/{id}', 'VcardController@delete')->name('admin.vcard.delete');
        });


        Route::get('/comment', 'ProductController@comment')->name('admin.product.comment');
        Route::get('/comment/active/{id}', 'ProductController@comment_active')->name('admin.product.comment.active');
        Route::get('/comment/delete/{id}', 'ProductController@comment_delete')->name('admin.product.comment.delete');

    });

});

/*
|--------------------------------------------------------------------------
| Site (ön yüz) rotaları — Ensis Pnömatik
|--------------------------------------------------------------------------
| SIRA ÖNEMLİ: En alttaki /{slug?} rotası her adresi yakalar; yeni bir rota
| eklenecekse mutlaka onun ÜSTÜNE eklenmeli. (/hesaplamalar 404 vermesinin nedeni
| buydu: adres catch-all'a düşüyor, SiteController@content içerik bulamayıp 404 dönüyordu.)
|   /                    → ana sayfa (home.blade.php)
|   /category/{slug}     → kategori sayfası
|   /product/{slug}      → ürün detay sayfası
|   /hesaplamalar        → teknik hesaplamalar (resources/views/hesaplamalar.blade.php)
|   /{slug}              → içerik sayfaları: iletisim, urunlerimiz (categories.blade.php),
|                          blogs, catalog_send …  (SiteController@content)
*/

// Eski / hatalı adreslerden doğru adrese kalıcı (301) yönlendirmeler — 404 olmasın
Route::redirect('/category/pnomatik-otomasyon', '/category/pomatik-otomasyon', 301);

// KVKK aydınlatma metni ve çerez politikası (resources/views/kvkk.blade.php)
Route::view('/kvkk-aydinlatma-metni', 'kvkk')->name('kvkk');
Route::redirect('/cerez-politikasi', '/kvkk-aydinlatma-metni#cerez', 301);

// Hakkımızda sayfası (resources/views/hakkimizda.blade.php)
Route::view('/hakkimizda', 'hakkimizda')->name('hakkimizda');

// Teknik hesaplamalar + muadil ürün kodu bulucu (resources/views/hesaplamalar.blade.php)
Route::view('/hesaplamalar', 'hesaplamalar')->name('hesaplamalar');
// Yaygın yazım / eski adresler → /hesaplamalar (301)
Route::redirect('/hesaplama', '/hesaplamalar', 301);
Route::redirect('/hesaplayici', '/hesaplamalar', 301);
Route::redirect('/muadil', '/hesaplamalar#muadil', 301);
Route::redirect('/muadil-urun-kodu', '/hesaplamalar#muadil', 301);

Route::get('/', 'HomeController@index')->name('home');
Route::get('/lang/{slug?}', 'SiteController@lang')->name('lang');
Route::get('/category/{slug}', 'CategoryController@index')->name('category');
Route::get('/product/{slug}', 'ProductController@index')->name('product');
Route::post('/katalog_talep', 'SiteController@katalog_talep')->name('katalog_talep');
Route::post('/hemen_basvur', 'SiteController@hemen_basvur')->name('hemen_basvur');
Route::post('/contact_email', 'SiteController@contact_email')->name('contact_email');
Route::post('/human_email', 'SiteController@human_email')->name('human_email');
Route::get('/{slug?}', 'SiteController@content')->name('content');
