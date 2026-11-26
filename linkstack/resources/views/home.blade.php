


<!doctype html>
@include('layouts.lang')
  <head>
    <meta charset="utf-8">
    @php $GLOBALS['themeName'] = config('advanced-config.home_theme'); @endphp
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
@php
    $homeTitle = \App\Services\PublicSeo::homeTitle();
    $homeDescription = \App\Services\PublicSeo::homeDescription();
    $homeCanonical = \App\Services\PublicSeo::canonical(url()->current());
    $homeVersion = \App\Services\PublicSeo::version(config('app.name', 'LinkFree'), $homeDescription, \App\Services\PublicSeo::avatar());
    $homeImage = route('socialPreview.site', ['v' => $homeVersion]);
    $homeSchema = ['@context' => 'https://schema.org', '@type' => 'WebSite',
        '@id' => $homeCanonical . '#website', 'url' => $homeCanonical,
        'name' => config('app.name', 'LinkFree'), 'description' => $homeDescription,
        'publisher' => ['@type' => 'Organization', 'name' => config('app.name', 'LinkFree'), 'url' => $homeCanonical]];
@endphp
<title>{{ $homeTitle }}</title>
<meta name="description" content="{{ $homeDescription }}">
<meta name="robots" content="{{ \App\Services\PublicSeo::robots() }}">
<link rel="canonical" href="{{ $homeCanonical }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ config('app.name', 'LinkFree') }}">
<meta property="og:locale" content="{{ \App\Services\PublicSeo::locale() }}">
<meta property="og:url" content="{{ $homeCanonical }}">
<meta property="og:title" content="{{ $homeTitle }}">
<meta property="og:description" content="{{ $homeDescription }}">
<meta property="og:image" content="{{ $homeImage }}">
@if(str_starts_with($homeImage, 'https://'))
<meta property="og:image:secure_url" content="{{ $homeImage }}">
@endif
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Prévia do LinkFree">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="{{ $homeCanonical }}">
<meta name="twitter:title" content="{{ $homeTitle }}">
<meta name="twitter:description" content="{{ $homeDescription }}">
<meta name="twitter:image" content="{{ $homeImage }}">
<meta name="twitter:image:alt" content="Prévia do LinkFree">
<script type="application/ld+json">{!! json_encode($homeSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>

      <!-- Favicon -->
      @if(file_exists(base_path("assets/linkstack/images/").findFile('favicon')))
      <link rel="icon" type="image/png" href="{{ asset('assets/linkstack/images/'.findFile('favicon')) }}">
      @else
      <link rel="icon" type="image/svg+xml" href="{{ asset('assets/linkstack/images/favicon.svg') }}">
      @endif

      <script src="{{asset('assets/js/detect-dark-mode.js')}}"></script>

      <!-- Library / Plugin Css Build -->
      <link rel="stylesheet" href="{{asset('assets/css/core/libs.min.css')}}" />

      <!-- Aos Animation Css -->
      <link rel="stylesheet" href="{{asset('assets/vendor/aos/dist/aos.css')}}" />

      @include('layouts.fonts')

      <!-- Hope Ui Design System Css -->
      <link rel="stylesheet" href="{{asset('assets/css/hope-ui.min.css?v=2.0.0')}}" />

      <!-- Custom Css -->
      <link rel="stylesheet" href="{{asset('assets/css/custom.min.css?v=2.0.0')}}" />

      <!-- Dark Css -->
      <link rel="stylesheet" href="{{asset('assets/css/dark.min.css')}}" />

      <!-- Customizer Css -->
            @if(file_exists(base_path("assets/dashboard-themes/dashboard.css")))
      <link rel="stylesheet" href="{{asset('assets/dashboard-themes/dashboard.css')}}" />
      @else
      <link rel="stylesheet" href="{{asset('assets/css/customizer.min.css')}}" />
      @endif

      <!-- RTL Css -->
      <link rel="stylesheet" href="{{asset('assets/css/rtl.min.css')}}" />


  <style>
:root{--lf-green:#a5f33d;--lf-ink:#f5f7ef;--lf-muted:#aab4ab;--lf-panel:#151c17}
body{background:#0b100d;color:var(--lf-ink);font-family:inherit}
.lf-nav{position:sticky;top:0;z-index:50;background:rgba(11,16,13,.94);border-bottom:1px solid #273129;backdrop-filter:blur(12px)}
.lf-container{width:min(1160px,calc(100% - 40px));margin:auto}.lf-nav-inner{min-height:76px;display:flex;align-items:center;gap:28px}.lf-brand{display:flex;align-items:center;gap:10px;color:white;font-size:1.35rem;font-weight:800;text-decoration:none}.lf-brand img{width:34px;height:34px;object-fit:contain}.lf-links{display:flex;align-items:center;gap:24px;margin-left:auto}.lf-links a{color:#d8e0d8;text-decoration:none}.lf-actions{display:flex;align-items:center;gap:12px}.lf-button{display:inline-flex;justify-content:center;align-items:center;padding:13px 21px;border-radius:999px;background:var(--lf-green);color:#10180d!important;text-decoration:none!important;font-weight:750;border:1px solid var(--lf-green);transition:transform .2s,box-shadow .2s}.lf-button:hover{transform:translateY(-3px);box-shadow:0 8px 30px #a5f33440}.lf-button.alt{background:transparent;color:white!important;border-color:#48564a}.lf-menu{display:none;background:transparent;border:1px solid #48564a;border-radius:9px;color:white;padding:8px 12px;margin-left:auto}
.lf-hero{position:relative;overflow:hidden;padding:100px 0 90px;background:radial-gradient(circle at 78% 32%,#263d20 0,transparent 38%)}.lf-grid{display:grid;grid-template-columns:1.05fr .95fr;gap:70px;align-items:center}.lf-eyebrow{display:inline-block;color:var(--lf-green);font-size:.8rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.lf-hero h1{font-size:clamp(2.8rem,6vw,5.2rem);line-height:1.04;letter-spacing:-.05em;color:white;margin:20px 0}.lf-hero h1 span{color:var(--lf-green)}.lf-lead{color:var(--lf-muted);font-size:1.14rem;line-height:1.7;max-width:570px}.lf-cta{display:flex;flex-wrap:wrap;gap:12px;margin-top:30px}.lf-note{color:#9dab9e;font-size:.86rem;margin-top:18px}.lf-phone{width:min(100%,310px);margin:auto;padding:10px;background:#222b23;border:1px solid #586758;border-radius:39px;box-shadow:0 30px 90px #0008;transform:rotate(3deg)}.lf-phone iframe{display:block;width:100%;height:570px;border:0;border-radius:30px;background:white}.lf-section{padding:100px 0}.lf-section.alt{background:#101713}.lf-heading{text-align:center;max-width:720px;margin:0 auto 48px}.lf-heading h2,.lf-split h2,.lf-final h2{color:white;font-size:clamp(2rem,4vw,3.2rem);letter-spacing:-.04em;line-height:1.15;margin:14px 0}.lf-heading p,.lf-card p,.lf-split p,.lf-final p{color:var(--lf-muted);line-height:1.7}.lf-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.lf-card{padding:28px;background:var(--lf-panel);border:1px solid #2b382d;border-radius:20px}.lf-card h3{color:white;font-size:1.2rem;margin:18px 0 10px}.lf-icon{display:grid;place-items:center;width:48px;height:48px;border-radius:15px;background:#263920;color:var(--lf-green);font-size:1.3rem;font-weight:800}.lf-split{display:grid;grid-template-columns:1fr 1fr;gap:70px;align-items:center}.lf-steps{display:grid;gap:14px}.lf-step{display:flex;gap:16px;align-items:flex-start;padding:20px;border:1px solid #2b382d;border-radius:18px;background:var(--lf-panel)}.lf-step strong{display:block;color:white;margin-bottom:4px}.lf-step p{margin:0}.lf-number{color:var(--lf-green);font-weight:800}.lf-faq{max-width:760px;margin:auto}.lf-faq details{border-bottom:1px solid #2b382d;padding:20px 0}.lf-faq summary{cursor:pointer;color:white;font-weight:700}.lf-faq p{color:var(--lf-muted);line-height:1.7;margin:14px 0 0}.lf-final{text-align:center;padding:70px 25px;border-radius:28px;background:radial-gradient(circle at top,#2a4722,#172319);border:1px solid #40543b}.lf-final .lf-cta{justify-content:center}.lf-footer{padding:32px 0;border-top:1px solid #2b382d;color:#aab4ab}.lf-footer .footer-body{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap}.lf-footer a{color:#c9d4c9}.lf-reveal{transition:opacity .65s ease,transform .65s ease}.js-reveal .lf-reveal{opacity:0;transform:translateY(24px)}.js-reveal .lf-reveal.visible{opacity:1;transform:none}
@media(max-width:767px){.lf-nav-inner{flex-wrap:wrap;padding:14px 0}.lf-menu{display:block}.lf-links{display:none;flex-basis:100%;margin:0;align-items:stretch;flex-direction:column;gap:16px;padding:12px 0}.lf-links.open{display:flex}.lf-actions{flex-wrap:wrap}.lf-hero{padding:65px 0}.lf-grid,.lf-split{grid-template-columns:1fr;gap:40px}.lf-phone{width:min(280px,100%);transform:none}.lf-phone iframe{height:500px}.lf-section{padding:70px 0}.lf-cards{grid-template-columns:1fr}.lf-heading{text-align:left}.lf-cta .lf-button{flex:1 1 180px}.lf-footer .footer-body{display:block}.lf-footer .right-panel{margin-top:16px}}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.01ms!important;transition-duration:.01ms!important;scroll-behavior:auto!important}.js-reveal .lf-reveal{opacity:1;transform:none}}
.lf-phone{box-sizing:border-box;aspect-ratio:310/590;overflow:hidden}.lf-phone iframe{height:100%;min-height:0}.lf-footer .footer-body{align-items:center}.lf-footer .left-panel{display:flex;flex-wrap:wrap;align-items:center;gap:8px 20px;min-width:0}.lf-footer .left-panel li,.lf-footer .left-panel a{margin:0!important}.lf-footer .left-panel a{display:inline-block;padding:6px 0;overflow-wrap:anywhere}.lf-footer .right-panel{min-width:0;overflow-wrap:anywhere}.lf-footer .right-panel svg{vertical-align:middle}
@media(max-width:767px){.lf-phone iframe{height:100%}.lf-footer .footer-body{display:flex;flex-direction:column;align-items:center;text-align:center;gap:12px}.lf-footer .left-panel{justify-content:center}.lf-footer .right-panel{margin-top:0}}

/* Apresentação e navegação da página pública */
.lf-nav-inner,.lf-grid>* ,.lf-split>*{min-width:0}.lf-brand{flex-shrink:0}.lf-brand span{overflow-wrap:anywhere}.lf-links{min-width:0}.lf-links>a{white-space:nowrap}.lf-links a:focus-visible,.lf-menu:focus-visible,.lf-footer a:focus-visible,.lf-faq summary:focus-visible{outline:2px solid #a5f33d;outline-offset:4px}.lf-section[id]{scroll-margin-top:95px}
.lf-card{position:relative;overflow:hidden;transition:transform .3s ease,border-color .3s ease,box-shadow .3s ease}.lf-card:hover{transform:translateY(-6px);border-color:#607e48;box-shadow:0 18px 38px #0004}.lf-card-art{height:120px;margin:-5px -4px 20px;display:grid;place-items:center;border-radius:15px;background:radial-gradient(circle at center,#2a4028,#17221a 70%);overflow:hidden}.lf-card-art svg{width:100%;max-width:235px;height:100%}.lf-card:hover .lf-art-float{transform:translateY(-5px)}.lf-art-float{transition:transform .4s ease}
.lf-phone-wrap{position:relative;isolation:isolate}.lf-phone-wrap:before{content:"";position:absolute;z-index:-1;inset:10% 12%;border-radius:50%;background:#89d43e;filter:blur(85px);opacity:.14;pointer-events:none}.lf-phone{animation:lf-float 6s ease-in-out infinite}.lf-step{transition:transform .3s ease,border-color .3s ease}.lf-step:hover{transform:translateX(5px);border-color:#607e48}.lf-faq summary{padding:4px 0}.lf-footer .footer-body{width:min(1160px,calc(100% - 40px));margin:0 auto;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px 24px}.lf-footer .left-panel{flex:1 1 320px}.lf-footer .right-panel{flex:0 1 auto;line-height:1.7}.lf-footer a:hover{color:var(--lf-green)}
@keyframes lf-float{0%,100%{transform:rotate(3deg) translateY(0)}50%{transform:rotate(3deg) translateY(-10px)}}
@media(max-width:1000px){.lf-nav-inner{flex-wrap:wrap;padding:14px 0;gap:12px}.lf-menu{display:block}.lf-links{display:none;flex:0 0 100%;margin:0;align-items:stretch;flex-direction:column;gap:0;padding:8px 0}.lf-links.open{display:flex}.lf-links>a{padding:12px 0}.lf-actions{padding-top:10px;gap:16px;flex-wrap:wrap}.lf-actions>a:not(.lf-button){padding:12px 0}.lf-grid{gap:35px}}
@media(max-width:767px){.lf-phone{animation:lf-float-mobile 6s ease-in-out infinite}.lf-footer .footer-body{flex-direction:column;align-items:center;text-align:center}.lf-footer .left-panel{flex:0 1 auto;justify-content:center}.lf-footer .right-panel{flex:0 1 auto}.lf-links .lf-actions{align-items:center}.lf-card-art{height:125px}}
@keyframes lf-float-mobile{0%,100%{transform:translateY(0)}50%{transform:translateY(-7px)}}
@media(prefers-reduced-motion:reduce){.lf-phone{animation:none}.lf-card,.lf-step,.lf-art-float{transition:none}}
.lf-explore-links{text-align:center;margin-top:38px}.lf-explore-links p{color:var(--lf-muted);margin:0 auto 18px;max-width:620px}.lf-roadmap-promo{display:flex;justify-content:space-between;align-items:center;gap:32px}.lf-roadmap-promo>div{max-width:720px}.lf-roadmap-promo h2{color:white;font-size:clamp(1.8rem,3.6vw,2.75rem);line-height:1.16;letter-spacing:-.04em;margin:12px 0}.lf-roadmap-promo p{color:var(--lf-muted);margin:0}.lf-roadmap-promo .lf-button{flex-shrink:0}.lf-nav-inner .lf-links{gap:16px}.lf-links>a{font-size:.92rem}@media(max-width:1130px){.lf-nav-inner{flex-wrap:wrap;padding:14px 0;gap:12px}.lf-menu{display:block}.lf-links{display:none;flex:0 0 100%;margin:0;align-items:stretch;flex-direction:column;gap:0;padding:8px 0}.lf-links.open{display:flex}.lf-links>a{padding:12px 0}.lf-actions{padding-top:10px;gap:16px;flex-wrap:wrap}}@media(max-width:767px){.lf-roadmap-promo{display:block}.lf-roadmap-promo .lf-button{margin-top:24px}.lf-explore-links .lf-button{width:100%;max-width:340px}}
</style>
  </head>

  @php
  $pages = DB::table('pages')->get();
  foreach($pages as $page){}
  @endphp

  <body class=" " data-bs-spy="scroll" data-bs-target="#elements-section" data-bs-offset="0" tabindex="0">
<header class="lf-nav"><div class="lf-container lf-nav-inner">
<a class="lf-brand" href="{{ url('/') }}">@if(file_exists(base_path("assets/linkstack/images/").findFile('avatar')))<img src="{{ asset('assets/linkstack/images/'.findFile('avatar')) }}" alt="">@else<img src="{{ asset('assets/linkstack/images/favicon.svg') }}" alt="">@endif <span>{{ config('app.name') }}</span></a>
<button class="lf-menu" type="button" aria-expanded="false" aria-controls="lf-navigation">Menu ☰</button>
<nav class="lf-links" id="lf-navigation" aria-label="Navegação principal">@if(\App\Services\PublicSiteContent::enabled('features'))<a href="#recursos">{{ \App\Services\PublicSiteContent::text('nav_features') }}</a>@endif
@if(\App\Services\PublicSiteContent::enabled('steps'))<a href="#como-funciona">{{ \App\Services\PublicSiteContent::text('nav_steps') }}</a>@endif
@if(\App\Services\PublicSiteContent::enabled('faq'))<a href="#duvidas">{{ \App\Services\PublicSiteContent::text('nav_faq') }}</a>@endif
<a href="{{ route('public.resources') }}">{{ \App\Services\PublicSiteContent::text('nav_catalog') }}</a>
<a href="{{ route('public.roadmap') }}">{{ \App\Services\PublicSiteContent::text('nav_roadmap') }}</a>
<div class="lf-actions">
@auth<a class="lf-button" href="{{ url('dashboard') }}">{{ \App\Services\PublicSiteContent::text('button_my_panel') }}</a>@else
@if(Route::has('login'))<a href="{{ route('login') }}">{{ \App\Services\PublicSiteContent::text('button_login') }}</a>@endif
@if((env('ALLOW_REGISTRATION')) and !config('linkstack.single_user_mode'))<a class="lf-button" href="{{ route('register') }}">{{ \App\Services\PublicSiteContent::text('button_register') }}</a>@endif
@endauth</div></nav></div></header>
<main>
<section class="lf-hero"><div class="lf-container lf-grid"><div class="lf-reveal"><span class="lf-eyebrow">{{ \App\Services\PublicSiteContent::text('hero_eyebrow') }}</span><h1>{{ \App\Services\PublicSiteContent::text('hero_title') }} <span>{{ \App\Services\PublicSiteContent::text('hero_accent') }}</span></h1><p class="lf-lead">{{ \App\Services\PublicSiteContent::text('hero_description') }}</p><div class="lf-cta">
@auth<a class="lf-button" href="{{ url('dashboard') }}">{{ \App\Services\PublicSiteContent::text('button_dashboard') }}</a>@else
@if((env('ALLOW_REGISTRATION')) and !config('linkstack.single_user_mode'))<a class="lf-button" href="{{ route('register') }}">{{ \App\Services\PublicSiteContent::text('button_register') }}</a>@elseif(Route::has('login'))<a class="lf-button" href="{{ route('login') }}">{{ \App\Services\PublicSiteContent::text('button_login') }}</a>@endif
@endauth
@if(\App\Services\PublicSiteContent::enabled('steps'))<a class="lf-button alt" href="#como-funciona">{{ \App\Services\PublicSiteContent::text('button_learn') }}</a>@endif</div><p class="lf-note">{{ \App\Services\PublicSiteContent::text('hero_note') }}</p></div><div class="lf-reveal lf-phone-wrap"><div class="lf-phone"><iframe title="{{ \App\Services\PublicSiteContent::text('hero_demo_label') }}" src="{{ url('/demo-page') }}?preview=home" loading="lazy"></iframe></div></div></div></section>
@if(\App\Services\PublicSiteContent::enabled('features'))
<section class="lf-section" id="recursos"><div class="lf-container"><div class="lf-heading lf-reveal"><span class="lf-eyebrow">{{ \App\Services\PublicSiteContent::text('features_eyebrow') }}</span><h2>{{ \App\Services\PublicSiteContent::text('features_title') }}</h2><p>{{ \App\Services\PublicSiteContent::text('features_intro') }}</p></div><div class="lf-cards">
<article class="lf-card lf-reveal"><div class="lf-card-art" aria-hidden="true"><svg viewBox="0 0 240 120" fill="none"><circle cx="120" cy="60" r="46" stroke="#42653b"/><g class="lf-art-float"><rect x="63" y="25" width="114" height="70" rx="14" fill="#253b28" stroke="#71945f"/><rect x="78" y="38" width="84" height="9" rx="4.5" fill="#a5f33d"/><rect x="78" y="55" width="84" height="9" rx="4.5" fill="#799c71"/><rect x="78" y="72" width="58" height="9" rx="4.5" fill="#799c71"/></g><circle cx="50" cy="34" r="4" fill="#a5f33d"/><circle cx="190" cy="85" r="3" fill="#a5f33d"/></svg></div><span class="lf-icon">↗</span><h3>{{ \App\Services\PublicSiteContent::text('feature1_title') }}</h3><p>{{ \App\Services\PublicSiteContent::text('feature1_description') }}</p></article>
<article class="lf-card lf-reveal"><div class="lf-card-art" aria-hidden="true"><svg viewBox="0 0 240 120" fill="none"><circle cx="120" cy="60" r="46" stroke="#42653b"/><g class="lf-art-float"><rect x="78" y="18" width="84" height="84" rx="16" fill="#253b28" stroke="#71945f"/><circle cx="120" cy="45" r="14" fill="#a5f33d"/><path d="M96 80c2-14 10-21 24-21s22 7 24 21" fill="#799c71"/><circle cx="167" cy="32" r="12" fill="#a5f33d"/><path d="M167 26v12m-6-6h12" stroke="#1d3018" stroke-width="2"/></g></svg></div><span class="lf-icon">✦</span><h3>{{ \App\Services\PublicSiteContent::text('feature2_title') }}</h3><p>{{ \App\Services\PublicSiteContent::text('feature2_description') }}</p></article>
<article class="lf-card lf-reveal"><div class="lf-card-art" aria-hidden="true"><svg viewBox="0 0 240 120" fill="none"><circle cx="120" cy="60" r="46" stroke="#42653b"/><g class="lf-art-float"><rect x="70" y="29" width="100" height="62" rx="14" fill="#253b28" stroke="#71945f"/><rect x="85" y="44" width="70" height="9" rx="4.5" fill="#a5f33d"/><rect x="85" y="61" width="44" height="7" rx="3.5" fill="#799c71"/><circle cx="154" cy="76" r="17" fill="#a5f33d"/><path d="M148 76h12m-5-5 5 5-5 5" stroke="#1d3018" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></g></svg></div><span class="lf-icon">▦</span><h3>{{ \App\Services\PublicSiteContent::text('feature3_title') }}</h3><p>{{ \App\Services\PublicSiteContent::text('feature3_description') }}</p></article>
</div><div class="lf-explore-links lf-reveal"><p>Seu painel reúne ainda mais possibilidades para organizar e personalizar a página.</p><a class="lf-button alt" href="{{ route('public.resources') }}">{{ \App\Services\PublicSiteContent::text('button_explore') }}</a></div></div></section>
@endif
@if(\App\Services\PublicSiteContent::enabled('steps'))
<section class="lf-section alt" id="como-funciona"><div class="lf-container lf-split"><div class="lf-reveal"><span class="lf-eyebrow">{{ \App\Services\PublicSiteContent::text('steps_eyebrow') }}</span><h2>{{ \App\Services\PublicSiteContent::text('steps_title') }}</h2><p>{{ \App\Services\PublicSiteContent::text('steps_intro') }}</p></div><div class="lf-steps">
<div class="lf-step lf-reveal"><span class="lf-number">01</span><div><strong>{{ \App\Services\PublicSiteContent::text('step1_title') }}</strong><p>{{ \App\Services\PublicSiteContent::text('step1_description') }}</p></div></div>
<div class="lf-step lf-reveal"><span class="lf-number">02</span><div><strong>{{ \App\Services\PublicSiteContent::text('step2_title') }}</strong><p>{{ \App\Services\PublicSiteContent::text('step2_description') }}</p></div></div>
<div class="lf-step lf-reveal"><span class="lf-number">03</span><div><strong>{{ \App\Services\PublicSiteContent::text('step3_title') }}</strong><p>{{ \App\Services\PublicSiteContent::text('step3_description') }}</p></div></div>
</div></div></section>
@endif
@if(\App\Services\PublicSiteContent::enabled('faq'))
<section class="lf-section" id="duvidas"><div class="lf-container"><div class="lf-heading lf-reveal"><span class="lf-eyebrow">{{ \App\Services\PublicSiteContent::text('faq_eyebrow') }}</span><h2>{{ \App\Services\PublicSiteContent::text('faq_title') }}</h2></div><div class="lf-faq lf-reveal">
<details><summary>{{ \App\Services\PublicSiteContent::text('faq1_question') }}</summary><p>{{ \App\Services\PublicSiteContent::text('faq1_answer') }}</p></details>
<details><summary>{{ \App\Services\PublicSiteContent::text('faq2_question') }}</summary><p>{{ \App\Services\PublicSiteContent::text('faq2_answer') }}</p></details>
<details><summary>{{ \App\Services\PublicSiteContent::text('faq3_question') }}</summary><p>{{ \App\Services\PublicSiteContent::text('faq3_answer') }}</p></details>
</div></div></section>
@endif
@if(count(\App\Services\MemberFeatureCatalog::upcoming()) > 0)
<section class="lf-section alt" aria-labelledby="lf-home-roadmap-title"><div class="lf-container lf-roadmap-promo lf-reveal">
  <div><span class="lf-eyebrow">Novas experiências em preparação</span><h2 id="lf-home-roadmap-title">O que vem a seguir no LinkFree?</h2><p>Conheça as ferramentas em validação. Não exibimos recursos já lançados nem anunciamos datas antes da confirmação.</p></div>
  <a class="lf-button alt" href="{{ route('public.roadmap') }}">Próximas implementações</a>
</div></section>
@endif
@if(\App\Services\PublicSiteContent::enabled('final'))
<section class="lf-section alt"><div class="lf-container"><div class="lf-final lf-reveal"><span class="lf-eyebrow">{{ \App\Services\PublicSiteContent::text('final_eyebrow') }}</span><h2>{{ \App\Services\PublicSiteContent::text('final_title') }}</h2><p>{{ \App\Services\PublicSiteContent::text('final_description') }}</p><div class="lf-cta">@auth<a class="lf-button" href="{{ url('dashboard') }}">{{ \App\Services\PublicSiteContent::text('button_dashboard') }}</a>@else @if((env('ALLOW_REGISTRATION')) and !config('linkstack.single_user_mode'))<a class="lf-button" href="{{ route('register') }}">{{ \App\Services\PublicSiteContent::text('button_register') }}</a>@elseif(Route::has('login'))<a class="lf-button" href="{{ route('login') }}">{{ \App\Services\PublicSiteContent::text('button_login') }}</a>@endif @endauth</div></div></div></section>
@endif
</main>
          <!-- Footer Section Start -->
          <footer class="lf-footer">
            <div class="footer-body lf-container">
                <ul class="left-panel list-inline mb-0 p-0">
                  @if(env('DISPLAY_FOOTER') === true)
                    @if(env('DISPLAY_FOOTER_HOME') === true)<li class="list-inline-item"><a class="list-inline-item" href="@if(str_replace('"', "", EnvEditor::getKey('HOME_FOOTER_LINK')) === "" ){{ url('') }}@else{{ str_replace('"', "", EnvEditor::getKey('HOME_FOOTER_LINK')) }}@endif">{{ \App\Services\PublicSiteContent::text('footer_home') }}</a></li>@endif
                    @if(env('DISPLAY_FOOTER_TERMS') === true)<li class="list-inline-item"><a class="list-inline-item" href="{{ url('') }}/pages/{{ strtolower(footer('Terms')) }}">{{ \App\Services\PublicSiteContent::text('footer_terms') }}</a></li>@endif
                    @if(env('DISPLAY_FOOTER_PRIVACY') === true)<li class="list-inline-item"><a class="list-inline-item" href="{{ url('') }}/pages/{{ strtolower(footer('Privacy')) }}">{{ \App\Services\PublicSiteContent::text('footer_privacy') }}</a></li>@endif
                    @if(env('DISPLAY_FOOTER_CONTACT') === true)<li class="list-inline-item"><a class="list-inline-item" href="{{ url('') }}/pages/{{ strtolower(footer('Contact')) }}">{{ \App\Services\PublicSiteContent::text('footer_contact') }}</a></li>@endif
                  @endif
                </ul>
                <div class="right-panel">
                  {{__('messages.Copyright')}} &copy; @php echo date('Y'); @endphp {{ config('app.name') }}
                  @if(env('DISPLAY_CREDIT_FOOTER') === true)
                    <span class="">
                      - {{__('messages.Made with')}}
                        <svg class="icon-15" width="15" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M15.85 2.50065C16.481 2.50065 17.111 2.58965 17.71 2.79065C21.401 3.99065 22.731 8.04065 21.62 11.5806C20.99 13.3896 19.96 15.0406 18.611 16.3896C16.68 18.2596 14.561 19.9196 12.28 21.3496L12.03 21.5006L11.77 21.3396C9.48102 19.9196 7.35002 18.2596 5.40102 16.3796C4.06102 15.0306 3.03002 13.3896 2.39002 11.5806C1.26002 8.04065 2.59002 3.99065 6.32102 2.76965C6.61102 2.66965 6.91002 2.59965 7.21002 2.56065H7.33002C7.61102 2.51965 7.89002 2.50065 8.17002 2.50065H8.28002C8.91002 2.51965 9.52002 2.62965 10.111 2.83065H10.17C10.21 2.84965 10.24 2.87065 10.26 2.88965C10.481 2.96065 10.69 3.04065 10.89 3.15065L11.27 3.32065C11.3618 3.36962 11.4649 3.44445 11.554 3.50912C11.6104 3.55009 11.6612 3.58699 11.7 3.61065C11.7163 3.62028 11.7329 3.62996 11.7496 3.63972C11.8354 3.68977 11.9247 3.74191 12 3.79965C13.111 2.95065 14.46 2.49065 15.85 2.50065ZM18.51 9.70065C18.92 9.68965 19.27 9.36065 19.3 8.93965V8.82065C19.33 7.41965 18.481 6.15065 17.19 5.66065C16.78 5.51965 16.33 5.74065 16.18 6.16065C16.04 6.58065 16.26 7.04065 16.68 7.18965C17.321 7.42965 17.75 8.06065 17.75 8.75965V8.79065C17.731 9.01965 17.8 9.24065 17.94 9.41065C18.08 9.58065 18.29 9.67965 18.51 9.70065Z" fill="currentColor"></path>
                        </svg>
                    </span> {{__('messages.by')}} <a href="https://linkstack.org/" target="_blank">LinkStack</a>.
                  @endif
                </div>
            </div>
        </footer>
        <!-- Footer Section End -->

<script>
(function(){var button=document.querySelector('.lf-menu'),nav=document.getElementById('lf-navigation');if(button&&nav){button.addEventListener('click',function(){var open=nav.classList.toggle('open');button.setAttribute('aria-expanded',String(open));});document.addEventListener('keydown',function(e){if(e.key==='Escape'&&nav.classList.contains('open')){nav.classList.remove('open');button.setAttribute('aria-expanded','false');button.focus();}});nav.querySelectorAll('a').forEach(function(a){a.addEventListener('click',function(){nav.classList.remove('open');button.setAttribute('aria-expanded','false');});});}if('IntersectionObserver' in window&&!window.matchMedia('(prefers-reduced-motion: reduce)').matches){document.documentElement.classList.add('js-reveal');var observer=new IntersectionObserver(function(entries){entries.forEach(function(entry){if(entry.isIntersecting){entry.target.classList.add('visible');observer.unobserve(entry.target);}});},{threshold:0.1});document.querySelectorAll('.lf-reveal').forEach(function(el){observer.observe(el);});}})();
</script>
    <!-- Library Bundle Script -->
    <script src="{{asset('assets/js/core/libs.min.js')}}"></script>

    <!-- External Library Bundle Script -->
    <script src="{{asset('assets/js/core/external.min.js')}}"></script>

    <!-- Widgetchart Script -->
    <script src="{{asset('assets/js/charts/widgetcharts.js')}}"></script>

    <!-- mapchart Script -->
    <script src="{{asset('assets/js/charts/vectore-chart.js')}}"></script>
    <script src="{{asset('assets/js/charts/dashboard.js')}}" ></script>

    <!-- fslightbox Script -->
    <script src="{{asset('assets/js/plugins/fslightbox.js')}}"></script>

    <!-- Settings Script -->
    <script src="{{asset('assets/js/plugins/setting.js')}}"></script>

    <!-- Slider-tab Script -->
    <script src="{{asset('assets/js/plugins/slider-tabs.js')}}"></script>

    <!-- Form Wizard Script -->
    <script src="{{asset('assets/js/plugins/form-wizard.js')}}"></script>

    <!-- AOS Animation Plugin-->
    <script src="{{asset('assets/vendor/aos/dist/aos.js')}}"></script>

    <!-- App Script -->
    <script src="{{asset('assets/js/hope-ui.js')}}" defer></script>

    <!-- Flatpickr Script -->
    <script src="{{asset('assets/vendor/flatpickr/dist/flatpickr.min.js')}}"></script>
    <script src="{{asset('assets/js/plugins/flatpickr.js')}}" defer></script>

    <script src="{{asset('assets/js/plugins/prism.mini.js')}}"></script>

  </body>
</html>
