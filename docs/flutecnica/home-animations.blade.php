{{--
|==========================================================================
| FLUTECNICA — ANA SAYFA ANİMASYON KATMANI (v21 · kurumsal)
| Bu içerik home.blade.php'nin en altında, `@endsection`'dan önce zaten gömülüdür.
| (Ayrı partial olarak kullanmak isterseniz: @include('partials.home-animations'))
|--------------------------------------------------------------------------
| Prensipler (UI/UX Pro Max): ölçülü, anlamlı hareket · giriş 0,5–1,1 sn, etkileşim 0,2–0,35 sn
| yalnız transform/opacity (+ tek seferlik clip-path) · sonsuz yeni animasyon yok
| prefers-reduced-motion'da hiçbir şey gizlenmez/hareket etmez · JS yoksa içerik görünür
| fare efektleri yalnız hassas işaretçide · mor/pembe yok · emoji yok · kütüphane yok
|==========================================================================
--}}

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

  /* 12) Teklif formu: yeni kalem, teknik özellik satırı ve başarı onayı */
  .fl-home .rq-row{animation:fxRowIn .35s var(--ease-out)}
  .fl-home .rq-spec:not([hidden]){animation:fxOpen .3s var(--ease-out)}
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
