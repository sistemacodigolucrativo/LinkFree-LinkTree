<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $isRoadmap = $page === 'roadmap';
        $pageTitle = $isRoadmap ? 'Próximas Implementações' : 'Recursos para membros';
        $description = $isRoadmap
            ? 'Conheça as novas ferramentas do LinkFree em preparação. Sem datas prometidas e sem repetir funcionalidades já disponíveis.'
            : 'Explore os recursos do LinkFree para criar sua página, personalizar links e blocos e acompanhar resultados conforme seu plano.';
        $canonical = $isRoadmap ? route('public.roadmap') : route('public.resources');
        $previewImage = route('socialPreview.site');
    @endphp
    <title>{{ $pageTitle }} | {{ config('app.name', 'LinkFree') }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="{{ \App\Services\PublicSeo::robots() }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="{{ config('app.name', 'LinkFree') }}">
    <meta property="og:title" content="{{ $pageTitle }} | {{ config('app.name', 'LinkFree') }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $previewImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }} | {{ config('app.name', 'LinkFree') }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $previewImage }}">
    @if(file_exists(base_path('assets/linkstack/images/').findFile('favicon')))
      <link rel="icon" type="image/png" href="{{ asset('assets/linkstack/images/'.findFile('favicon')) }}">
    @else
      <link rel="icon" type="image/svg+xml" href="{{ asset('assets/linkstack/images/favicon.svg') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('assets/css/linkfree-public-catalog.css') }}?v={{ filemtime(base_path('assets/css/linkfree-public-catalog.css')) }}">
</head>
<body class="lf-public-catalog">
<a class="lf-skip" href="#conteudo">Ir para o conteúdo</a>
<header class="lf-public-header">
  <div class="lf-public-wrap lf-header-content">
    <a class="lf-public-brand" href="{{ url('/') }}" aria-label="{{ config('app.name', 'LinkFree') }} — início">
      @if(file_exists(base_path('assets/linkstack/images/').findFile('avatar')))
        <img src="{{ asset('assets/linkstack/images/'.findFile('avatar')) }}" width="34" height="34" alt="">
      @else
        <img src="{{ asset('assets/linkstack/images/favicon.svg') }}" width="34" height="34" alt="">
      @endif
      <span>{{ config('app.name', 'LinkFree') }}</span>
    </a>
    <nav class="lf-public-nav" aria-label="Navegação institucional">
      <a href="{{ url('/') }}">Início</a>
      <a href="{{ route('public.resources') }}" @if(!$isRoadmap) aria-current="page" @endif>Recursos</a>
      <a href="{{ route('public.roadmap') }}" @if($isRoadmap) aria-current="page" @endif>Próximas implementações</a>
    </nav>
    <div class="lf-public-account">
      @auth
        <a class="lf-action" href="{{ route('panelIndex') }}">Meu painel</a>
      @else
        @if(Route::has('login'))
          <a href="{{ route('login') }}" class="lf-public-signin">Entrar</a>
        @endif
        @if(env('ALLOW_REGISTRATION') && !config('linkstack.single_user_mode') && Route::has('register'))
          <a class="lf-action" href="{{ route('register') }}">Criar minha página</a>
        @endif
      @endauth
    </div>
  </div>
</header>
<main id="conteudo">
  <section class="lf-catalog-hero">
    <div class="lf-public-wrap">
      <p class="lf-kicker">{{ $isRoadmap ? 'Nosso próximo capítulo' : 'Descubra o LinkFree' }}</p>
      @if($isRoadmap)
        <h1>Próximas <span>Implementações</span></h1>
        <p class="lf-catalog-lead">A evolução do LinkFree começa com experiências úteis. Aqui aparecem apenas novas ferramentas anunciadas e que ainda não estão disponíveis — sem repetir o que já funciona.</p>
        <p class="lf-small-note">Itens em desenvolvimento não representam data de lançamento nem disponibilidade garantida.</p>
      @else
        <h1>Sua presença digital, <span>no seu ritmo.</span></h1>
        <p class="lf-catalog-lead">Apresente quem você é, conecte pessoas aos seus canais e organize conteúdos em uma página personalizável. Do primeiro bloco ao acompanhamento de cliques, cada recurso ajuda você a oferecer um caminho mais claro ao visitante.</p>
        <p class="lf-small-note">Algumas funções variam conforme o plano e as configurações habilitadas para a sua conta.</p>
      @endif
    </div>
  </section>
  @if($isRoadmap)
    <section class="lf-catalog-section lf-catalog-roadmap" aria-labelledby="proximas-ferramentas">
      <div class="lf-public-wrap">
        <div class="lf-section-heading">
          <span class="lf-kicker">Em preparação</span>
          <h2 id="proximas-ferramentas">Ferramentas em desenvolvimento</h2>
          <p>Exibimos somente recursos novos mencionados no planejamento e ainda não entregues.</p>
        </div>
        @forelse($upcoming as $feature)
          <article class="lf-upcoming-card">
            <div>
              <p class="lf-roadmap-state">{{ $feature['status'] }}</p>
              <h3>{{ $feature['title'] }}</h3>
              <p>{{ $feature['description'] }}</p>
              <small>{{ $feature['note'] }}</small>
            </div>
            <div class="lf-roadmap-mark" aria-hidden="true">↗</div>
          </article>
        @empty
          <div class="lf-empty-roadmap" role="status">
            <h3>Nenhuma nova ferramenta anunciada no momento.</h3>
            <p>As funções já disponíveis não são repetidas nesta página. Quando uma nova ferramenta for confirmada, ela aparecerá aqui.</p>
          </div>
        @endforelse
      </div>
    </section>
    <section class="lf-catalog-section lf-catalog-bridge">
      <div class="lf-public-wrap lf-bridge-inner">
        <div><span class="lf-kicker">Disponível agora</span><h2>Quer conhecer o que você já pode fazer?</h2><p>Veja os recursos atuais do LinkFree, organizados por objetivo e com indicação de restrições quando necessário.</p></div>
        <a class="lf-action lf-action-outline" href="{{ route('public.resources') }}">Conhecer recursos atuais</a>
      </div>
    </section>
  @else
    <nav class="lf-group-jump lf-public-wrap" aria-label="Ir para uma categoria de recursos">
      @foreach($groups as $group)
        <a href="#{{ $group['id'] }}">{{ $group['eyebrow'] }}</a>
      @endforeach
    </nav>
    @foreach($groups as $group)
      <section class="lf-catalog-section {{ $loop->even ? 'lf-catalog-alt' : '' }}" id="{{ $group['id'] }}" aria-labelledby="heading-{{ $group['id'] }}">
        <div class="lf-public-wrap">
          <div class="lf-section-heading">
            <span class="lf-kicker">{{ $group['eyebrow'] }}</span>
            <h2 id="heading-{{ $group['id'] }}">{{ $group['title'] }}</h2>
            <p>{{ $group['description'] }}</p>
          </div>
          <div class="lf-feature-grid">
            @foreach($group['items'] as $item)
              <article class="lf-feature-tile">
                <span class="lf-feature-symbol" aria-hidden="true">✓</span>
                <h3>{{ $item['title'] }}</h3>
                <p>{{ $item['description'] }}</p>
                @if(!empty($item['note']))
                  <p class="lf-feature-note">{{ $item['note'] }}</p>
                @endif
              </article>
            @endforeach
          </div>
        </div>
      </section>
    @endforeach
    <section class="lf-catalog-section lf-catalog-bridge">
      <div class="lf-public-wrap lf-bridge-inner">
        <div><span class="lf-kicker">Em evolução</span><h2>O que estamos preparando?</h2><p>Acompanhe apenas ferramentas novas e ainda não disponíveis, sem promessas de datas ou recursos já lançados.</p></div>
        <a class="lf-action lf-action-outline" href="{{ route('public.roadmap') }}">Ver próximas implementações</a>
      </div>
    </section>
  @endif
  <section class="lf-catalog-last">
    <div class="lf-public-wrap lf-last-inner">
      <span class="lf-kicker">Comece com clareza</span>
      <h2>Um espaço para sua presença digital.</h2>
      <p>Crie sua página, organize seus canais e compartilhe seu endereço.</p>
      @auth
        <a class="lf-action" href="{{ route('panelIndex') }}">Acessar meu painel</a>
      @else
        @if(env('ALLOW_REGISTRATION') && !config('linkstack.single_user_mode') && Route::has('register'))
          <a class="lf-action" href="{{ route('register') }}">Criar minha página</a>
        @elseif(Route::has('login'))
          <a class="lf-action" href="{{ route('login') }}">Entrar no LinkFree</a>
        @endif
      @endauth
    </div>
  </section>
</main>
<footer class="lf-public-footer">
  <div class="lf-public-wrap lf-public-footer-inner">
    <p>© {{ date('Y') }} {{ config('app.name', 'LinkFree') }}</p>
    <nav aria-label="Links institucionais no rodapé">
      <a href="{{ url('/') }}">Início</a>
      <a href="{{ route('public.resources') }}">Recursos</a>
      <a href="{{ route('public.roadmap') }}">Próximas implementações</a>
      @if(env('DISPLAY_FOOTER') === true)
        @if(env('DISPLAY_FOOTER_TERMS') === true)<a href="{{ route('pagesTerms') }}">Termos</a>@endif
        @if(env('DISPLAY_FOOTER_PRIVACY') === true)<a href="{{ route('pagesPrivacy') }}">Privacidade</a>@endif
        @if(env('DISPLAY_FOOTER_CONTACT') === true)<a href="{{ route('pagesContact') }}">Contato</a>@endif
      @endif
    </nav>
  </div>
</footer>
</body>
</html>
