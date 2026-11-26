<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  @php
    $marketingTitle = trim($__env->yieldContent('page-title'));
    $marketingDescription = trim($__env->yieldContent('page-description'));
    $marketingCanonical = url()->current();
    $marketingImage = route('socialPreview.site');
  @endphp
  <title>{{ $marketingTitle }} | {{ config('app.name', 'LinkFree') }}</title>
  <meta name="description" content="{{ $marketingDescription }}">
  <meta name="robots" content="{{ \App\Services\PublicSeo::robots() }}">
  <link rel="canonical" href="{{ $marketingCanonical }}">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="{{ config('app.name', 'LinkFree') }}">
  <meta property="og:locale" content="{{ \App\Services\PublicSeo::locale() }}">
  <meta property="og:title" content="{{ $marketingTitle }}">
  <meta property="og:description" content="{{ $marketingDescription }}">
  <meta property="og:url" content="{{ $marketingCanonical }}">
  <meta property="og:image" content="{{ $marketingImage }}">
  <meta property="og:image:type" content="image/jpeg">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="Prévia do LinkFree">
  @if(str_starts_with($marketingImage, 'https://'))
    <meta property="og:image:secure_url" content="{{ $marketingImage }}">
  @endif
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="{{ $marketingCanonical }}">
  <meta name="twitter:title" content="{{ $marketingTitle }}">
  <meta name="twitter:description" content="{{ $marketingDescription }}">
  <meta name="twitter:image" content="{{ $marketingImage }}">
  @if(file_exists(base_path('assets/linkstack/images/').findFile('favicon')))
    <link rel="icon" type="image/png" href="{{ asset('assets/linkstack/images/'.findFile('favicon')) }}">
  @else
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/linkstack/images/favicon.svg') }}">
  @endif
  <link rel="stylesheet" href="{{ asset('assets/css/linkfree-public-pages.css') }}?v={{ filemtime(base_path('assets/css/linkfree-public-pages.css')) }}">
</head>
<body>
<a class="lfp-skip" href="#conteudo">Pular para o conteúdo</a>
<header class="lfp-header">
  <div class="lfp-container lfp-nav">
    <a class="lfp-logo" href="{{ Route::has('home') ? route('home') : url('/') }}">
      <img src="{{ asset('assets/linkstack/images/favicon.svg') }}" alt="" width="31" height="31">
      {{ config('app.name', 'LinkFree') }}
    </a>
    <nav class="lfp-links" aria-label="Navegação pública">
      <a href="{{ Route::has('home') ? route('home') : url('/') }}">Início</a>
      <a href="{{ route('public.features') }}" @if(request()->routeIs('public.features')) aria-current="page" @endif>Recursos</a>
      <a href="{{ route('public.roadmap') }}" @if(request()->routeIs('public.roadmap')) aria-current="page" @endif>Próximas implementações</a>
      @auth
        <a class="lfp-pill" href="{{ route('panelIndex') }}">Meu painel</a>
      @else
        @if(Route::has('login'))<a href="{{ route('login') }}">Entrar</a>@endif
        @if(env('ALLOW_REGISTRATION') && !config('linkstack.single_user_mode'))
          <a class="lfp-pill" href="{{ route('register') }}">Criar minha página</a>
        @endif
      @endauth
    </nav>
  </div>
</header>
<main id="conteudo">
  @yield('content')
</main>
<footer class="lfp-footer">
  <div class="lfp-container lfp-footer-inner">
    <span>© {{ date('Y') }} {{ config('app.name', 'LinkFree') }}. Uma página para reunir sua presença online.</span>
    <nav class="lfp-footer-nav" aria-label="Links do rodapé">
      <a href="{{ Route::has('home') ? route('home') : url('/') }}">Início</a>
      <a href="{{ route('public.features') }}">Recursos</a>
      <a href="{{ route('public.roadmap') }}">Próximas implementações</a>
    </nav>
  </div>
</footer>
</body>
</html>
