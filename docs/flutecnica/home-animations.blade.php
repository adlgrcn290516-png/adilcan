{{--
|==========================================================================
| FLUTECNICA — ANA SAYFA ANİMASYON KATMANI (home-animations.blade.php)
| Ekleme tek satır: home.blade.php içinde, en alttaki `@endsection`'dan hemen önce
|     @include('partials.home-animations')
| (dosyayı resources/views/partials/ altına koyun; mevcut HTML/CSS/JS'e dokunmaz)
|--------------------------------------------------------------------------
| UI/UX Pro Max kuralları: yalnız transform/opacity · 150–600 ms · anlamlı hareket
| prefers-reduced-motion'da hiçbir şey gizlenmez/hareket etmez · sonsuz animasyon yok
| JS yoksa içerik görünür kalır (.js sınıfı yalnız JS varken eklenir)
| Mor/pembe yok · emoji yok · kütüphane yok
|==========================================================================
--}}

<div class="fx-progress" aria-hidden="true"><span id="fx-progress-bar"></span></div>

<style>
/* Kaydırma ilerleme çubuğu (transform ile; layout tetiklemez) */
.fx-progress{position:fixed;top:0;left:0;right:0;height:3px;z-index:1090;pointer-events:none}
.fx-progress span{display:block;height:100%;transform-origin:left center;transform:scaleX(0);background:linear-gradient(90deg,#5B7FA6,#1D426A,#C8B98E);will-change:transform}

/* Hepsi hareket azaltma KAPALIYKEN çalışır */
@media (prefers-reduced-motion:no-preference){

  html{scroll-behavior:smooth}

  /* 1) Hero başlığı: satır satır giriş (h1'in tek parça animasyonu devre dışı) */
  .fl-home .hero--signature .hero__main > .hero__title{animation:none}
  .fl-home .hero--signature .hero__title-line{animation:fxLine .7s var(--ease-out) both}
  .fl-home .hero--signature .hero__title-line:nth-child(1){animation-delay:.08s}
  .fl-home .hero--signature .hero__title-line:nth-child(2){animation-delay:.2s}
  .fl-home .hero--signature .hero__title-line:nth-child(3){animation-delay:.32s}

  /* 2) Hero görsel/video: kaydırmada hafif parallax (JS değişkeni --fx-py) */
  .fl-home .hero__media.fx-par :is(.hero__poster,video){transform:translate3d(0,var(--fx-py,0px),0) scale(1.08);will-change:transform}

  /* 3) Kademeli giriş (JS .fx-st ve .fx-in sınıflarını ekler) */
  .js .fl-home .fx-st{
    opacity:0;transform:translateY(14px);
    transition:opacity .55s var(--ease-out),transform .55s var(--ease-out),box-shadow .3s var(--ease-out),border-color .3s;
    transition-delay:calc(var(--i,0) * 70ms),calc(var(--i,0) * 70ms),0s,0s;
  }
  .js .fl-home .fx-st--fade{transform:none;transition:opacity .5s var(--ease-out);transition-delay:calc(var(--i,0) * 60ms)}
  .js .fl-home .fx-in > .fx-st{opacity:1;transform:none}

  /* 4) Birincil düğmeler: parlama süpürmesi (yalnız fare ile) */
  @media (hover:hover) and (pointer:fine){
    .fl-home :is(.fl-btn-primary,.hero__card-cta,.cta-vid__btn-main){position:relative;overflow:hidden}
    .fl-home :is(.fl-btn-primary,.hero__card-cta,.cta-vid__btn-main)::before{
      content:'';position:absolute;inset:0;pointer-events:none;
      background:linear-gradient(105deg,transparent 38%,rgba(255,255,255,.30) 50%,transparent 62%);
      transform:translateX(-130%);transition:transform .65s var(--ease-out)}
    .fl-home :is(.fl-btn-primary,.hero__card-cta,.cta-vid__btn-main):hover::before{transform:translateX(130%)}
    /* Araç / ürün okları: hafif itme */
    .fl-home .eng__tool-arrow,.fl-home .prod-card__arrow{transition:transform .25s var(--ease-out),background .25s}
    .fl-home .eng__tool:hover .eng__tool-arrow{transform:translateX(3px)}
    .fl-home .prod-card:hover .prod-card__arrow{transform:translateX(2px)}
  }

  /* 5) SSS: cevap açılırken yumuşak giriş */
  .fl-home .faq__item[open] .faq__a{animation:fxOpen .3s var(--ease-out)}

  /* 6) Teklif formu: yeni kalem ve başarı onayı */
  .fl-home .rq-row{animation:fxRowIn .35s var(--ease-out)}
  .fl-home .rq__done:not([hidden]) .rq__done-ico{animation:fxPop .5s cubic-bezier(.34,1.56,.64,1)}
  .fl-home .rq__done:not([hidden]) > *:not(.rq__done-ico){animation:fxOpen .4s var(--ease-out) both}

  @keyframes fxLine{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:none}}
  @keyframes fxOpen{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}
  @keyframes fxRowIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
  @keyframes fxPop{from{opacity:0;transform:scale(.5)}to{opacity:1;transform:none}}
}
</style>

<script>
(function () {
'use strict';
var RM  = window.matchMedia('(prefers-reduced-motion: reduce)');
var DSK = window.matchMedia('(min-width: 992px)');

function ready(fn) { document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', fn) : fn(); }

ready(function () {
    var root = document.querySelector('.fl-home');
    if (!root) return;

    /* ── Kaydırma ilerleme çubuğu + hero parallax (tek rAF) ── */
    var bar   = document.getElementById('fx-progress-bar');
    var media = root.querySelector('.hero--signature .hero__media');
    var hero  = root.querySelector('.hero--signature');
    var ticking = false;

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
    function onScroll() {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(function () { ticking = false; progress(); parallax(); });
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    progress(); parallax();

    /* ── Kademeli giriş: hareket azaltma açıksa veya IntersectionObserver yoksa hiçbir şey gizlenmez ── */
    if (RM.matches || !('IntersectionObserver' in window)) return;

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
