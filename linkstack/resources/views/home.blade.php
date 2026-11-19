


<!doctype html>
@include('layouts.lang')
  <head>
    <meta charset="utf-8">
    @php $GLOBALS['themeName'] = config('advanced-config.home_theme'); @endphp
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      @if(env('CUSTOM_META_TAGS') == 'true' and config('advanced-config.title') != '')
      <title>{{ config('advanced-config.title') }}</title>
      @else
      <title>{{ config('app.name') }}</title>
      @endif

<!--#### BEGIN Meta Tags social media preview images  ####-->
  <!-- This shows a preview for title, description and avatar image of users profiles if shared on social media sites -->

    <!-- Facebook Meta Tags -->
    <meta property="og:url" content="{{ url('') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{env('APP_NAME')}}">
    <meta property="og:description" content='@if($message->home_message == "default"){!!strip_tags(__('messages.HOME.MESSAGE'))!!}@else{!!$message->home_message!!}@endif'>
    @if(file_exists(base_path("assets/linkstack/images/").findFile('avatar')))
    <meta property="og:image" content="{{ asset('assets/linkstack/images/'.findFile('avatar')) }}">
    @else
    <meta property="og:image" content="{{ asset('assets/linkstack/images/logo.svg') }}">
    @endif

    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta property="twitter:domain" content="{{ url('') }}">
    <meta property="twitter:url" content="{{ url('') }}">
    <meta name="twitter:title" content="{{env('APP_NAME')}}">
    <meta name="twitter:description" content='@if($message->home_message == "default"){!!strip_tags(__('messages.HOME.MESSAGE'))!!}@else{!!$message->home_message!!}@endif'>
    @if(file_exists(base_path("assets/linkstack/images/").findFile('avatar')))
    <meta name="twitter:image" content="{{ asset('assets/linkstack/images/'.findFile('avatar')) }}">
    @else
    <meta name="twitter:image" content="{{ asset('assets/linkstack/images/logo.svg') }}">
    @endif

<!--#### END Meta Tags social media preview images  ####-->

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
.lf-visual-section{padding:28px 0 88px}.lf-visual{position:relative;overflow:hidden;border:1px solid #334735;border-radius:26px;background:#101c12;box-shadow:0 24px 70px #0007}.lf-visual img{display:block;width:100%;height:auto;aspect-ratio:3/2;object-fit:cover}.lf-visual figcaption{position:absolute;left:24px;bottom:24px;max-width:290px;padding:12px 16px;color:white;font-size:.88rem;line-height:1.4;border:1px solid #ffffff30;border-radius:14px;background:#0b160dd9;backdrop-filter:blur(10px)}@media(max-width:767px){.lf-visual-section{padding:10px 0 55px}.lf-visual{border-radius:18px}.lf-visual figcaption{position:static;max-width:none;border:0;border-radius:0;background:#142017;font-size:.8rem}.lf-visual img{aspect-ratio:4/3;object-position:center}}
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
<nav class="lf-links" id="lf-navigation" aria-label="Navegação principal"><a href="#recursos">Recursos</a><a href="#como-funciona">Como funciona</a><a href="#duvidas">Dúvidas</a><div class="lf-actions">
@auth<a class="lf-button" href="{{ url('dashboard') }}">Meu painel</a>@else
@if(Route::has('login'))<a href="{{ route('login') }}">Entrar</a>@endif
@if((env('ALLOW_REGISTRATION')) and !config('linkstack.single_user_mode'))<a class="lf-button" href="{{ route('register') }}">Criar minha página</a>@endif
@endauth</div></nav></div></header>
<main>
<section class="lf-hero"><div class="lf-container lf-grid"><div class="lf-reveal"><span class="lf-eyebrow">Sua presença em um só lugar</span><h1>Um link. <span>Todos os seus caminhos.</span></h1><p class="lf-lead">Reúna suas redes sociais, contatos e conteúdos em uma página com a sua cara. Compartilhe um único link e facilite a conexão com quem importa.</p><div class="lf-cta">
@auth<a class="lf-button" href="{{ url('dashboard') }}">Acessar meu painel</a>@else
@if((env('ALLOW_REGISTRATION')) and !config('linkstack.single_user_mode'))<a class="lf-button" href="{{ route('register') }}">Criar minha página</a>@elseif(Route::has('login'))<a class="lf-button" href="{{ route('login') }}">Entrar</a>@endif
@endauth<a class="lf-button alt" href="#como-funciona">Como funciona</a></div><p class="lf-note">Uma página para compartilhar onde você estiver.</p></div><div class="lf-reveal lf-phone-wrap"><div class="lf-phone"><iframe title="Exemplo de página de links" src="{{ url('/demo-page') }}?preview=home" loading="lazy"></iframe></div></div></div></section>
<section class="lf-visual-section"><div class="lf-container"><figure class="lf-visual lf-reveal"><img src="{{ asset('assets/linkstack/images/linkfree-apresentacao.webp') }}" alt="Ilustração de uma página de links em um celular, conectando diferentes conteúdos" loading="lazy" width="1200" height="800"><figcaption>Uma página para reunir seus links e compartilhar o que importa.</figcaption></figure></div></section>
<section class="lf-section" id="recursos"><div class="lf-container"><div class="lf-heading lf-reveal"><span class="lf-eyebrow">Tudo começa com um link</span><h2>Seu mundo, organizado do seu jeito</h2><p>Apresente o que você faz e ajude as pessoas a encontrarem seus conteúdos, produtos e canais de contato.</p></div><div class="lf-cards">
<article class="lf-card lf-reveal"><span class="lf-icon">↗</span><h3>Seus links reunidos</h3><p>Adicione seus destinos importantes em uma única página, pronta para compartilhar na bio e onde mais quiser.</p></article>
<article class="lf-card lf-reveal"><span class="lf-icon">✦</span><h3>Uma página com sua identidade</h3><p>Escolha um tema e personalize a apresentação para criar uma página que combine com você.</p></article>
<article class="lf-card lf-reveal"><span class="lf-icon">▦</span><h3>Mais formas de se conectar</h3><p>Organize links, textos e informações de contato para orientar seus visitantes.</p></article>
</div></div></section>
<section class="lf-section alt" id="como-funciona"><div class="lf-container lf-split"><div class="lf-reveal"><span class="lf-eyebrow">Simples de começar</span><h2>Da sua ideia para o mundo</h2><p>Crie um ponto de encontro para sua presença digital e mantenha seus links organizados em um só lugar.</p></div><div class="lf-steps">
<div class="lf-step lf-reveal"><span class="lf-number">01</span><div><strong>Crie sua conta</strong><p>Comece pelo cadastro e acesse seu painel.</p></div></div>
<div class="lf-step lf-reveal"><span class="lf-number">02</span><div><strong>Monte sua página</strong><p>Adicione seus links e personalize a apresentação.</p></div></div>
<div class="lf-step lf-reveal"><span class="lf-number">03</span><div><strong>Compartilhe seu link</strong><p>Coloque sua página na bio, nas redes e onde seu público estiver.</p></div></div>
</div></div></section>
<section class="lf-section" id="duvidas"><div class="lf-container"><div class="lf-heading lf-reveal"><span class="lf-eyebrow">Perguntas frequentes</span><h2>Quer saber mais?</h2></div><div class="lf-faq lf-reveal">
<details><summary>O que é uma árvore de links?</summary><p>É uma página que reúne vários links em um só endereço, facilitando o acesso aos seus conteúdos e contatos.</p></details>
<details><summary>Posso personalizar minha página?</summary><p>Sim. No painel, você pode escolher um tema e organizar os links e informações da sua página.</p></details>
<details><summary>Onde posso compartilhar minha página?</summary><p>Você pode compartilhar o endereço na bio das redes sociais, em mensagens ou em qualquer lugar que aceite links.</p></details>
</div></div></section>
<section class="lf-section alt"><div class="lf-container"><div class="lf-final lf-reveal"><span class="lf-eyebrow">Pronto para começar?</span><h2>Seu próximo clique começa aqui.</h2><p>Reúna seus links e apresente tudo o que importa em uma página.</p><div class="lf-cta">@auth<a class="lf-button" href="{{ url('dashboard') }}">Acessar meu painel</a>@else @if((env('ALLOW_REGISTRATION')) and !config('linkstack.single_user_mode'))<a class="lf-button" href="{{ route('register') }}">Criar minha página</a>@elseif(Route::has('login'))<a class="lf-button" href="{{ route('login') }}">Entrar</a>@endif @endauth</div></div></div></section>
</main>
          <!-- Footer Section Start -->
          <footer class="lf-footer">
            <div class="footer-body lf-container">
                <ul class="left-panel list-inline mb-0 p-0">
                  @if(env('DISPLAY_FOOTER') === true)
                    @if(env('DISPLAY_FOOTER_HOME') === true)<li class="list-inline-item"><a class="list-inline-item" href="@if(str_replace('"', "", EnvEditor::getKey('HOME_FOOTER_LINK')) === "" ){{ url('') }}@else{{ str_replace('"', "", EnvEditor::getKey('HOME_FOOTER_LINK')) }}@endif">{{footer('Home')}}</a></li>@endif
                    @if(env('DISPLAY_FOOTER_TERMS') === true)<li class="list-inline-item"><a class="list-inline-item" href="{{ url('') }}/pages/{{ strtolower(footer('Terms')) }}">{{footer('Terms')}}</a></li>@endif
                    @if(env('DISPLAY_FOOTER_PRIVACY') === true)<li class="list-inline-item"><a class="list-inline-item" href="{{ url('') }}/pages/{{ strtolower(footer('Privacy')) }}">{{footer('Privacy')}}</a></li>@endif
                    @if(env('DISPLAY_FOOTER_CONTACT') === true)<li class="list-inline-item"><a class="list-inline-item" href="{{ url('') }}/pages/{{ strtolower(footer('Contact')) }}">{{footer('Contact')}}</a></li>@endif
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
