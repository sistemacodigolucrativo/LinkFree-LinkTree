@extends('layouts.public-marketing')

@section('page-title', 'Recursos para criar sua presença digital')
@section('page-description', 'Conheça as ferramentas do LinkFree: perfil personalizado, links e blocos, temas, indicadores, convites e selo verificado conforme seu plano.')

@section('content')
<section class="lfp-hero">
  <div class="lfp-container">
    <span class="lfp-kicker">Recursos do LinkFree</span>
    <h1>Mais do que um link. <em>Seu espaço na internet.</em></h1>
    <p class="lfp-intro">Crie um ponto de encontro para sua marca, reúna conteúdos, facilite contatos e apresente tudo com identidade própria. Conheça os recursos que ajudam a organizar sua presença digital em uma única página.</p>
    <div class="lfp-actions">
      @auth
        <a class="lfp-pill" href="{{ route('panelIndex') }}">Acessar meu painel</a>
      @else
        @if(env('ALLOW_REGISTRATION') && !config('linkstack.single_user_mode'))
          <a class="lfp-pill" href="{{ route('register') }}">Criar minha página</a>
        @elseif(Route::has('login'))
          <a class="lfp-pill" href="{{ route('login') }}">Entrar</a>
        @endif
      @endauth
      <a class="lfp-pill ghost" href="#identidade">Explorar recursos</a>
    </div>
    <p class="lfp-note">Alguns recursos variam conforme o plano e as configurações do sistema. Não há promessa de alcance ou resultados automáticos.</p>
  </div>
</section>
@foreach(\App\Services\PublicFeatureCatalog::groups() as $group)
  <section class="lfp-section" id="{{ $group['id'] }}" aria-labelledby="titulo-{{ $group['id'] }}">
    <div class="lfp-container">
      <div class="lfp-section-head">
        <span class="lfp-kicker">{{ $group['eyebrow'] }}</span>
        <h2 id="titulo-{{ $group['id'] }}">{{ $group['title'] }}</h2>
        <p>{{ $group['description'] }}</p>
      </div>
      <div class="lfp-grid">
        @foreach($group['items'] as $item)
          <article class="lfp-card">
            <h3>{{ $item['title'] }}</h3>
            <p>{{ $item['description'] }}</p>
            @if(!empty($item['note']))
              <span class="lfp-feature-tag">{{ $item['note'] }}</span>
            @endif
          </article>
        @endforeach
      </div>
    </div>
  </section>
@endforeach
<div class="lfp-container">
  <p class="lfp-disclosure">A disponibilidade efetiva dos recursos depende do tipo de conta, da configuração da instância e das permissões aplicáveis. O relatório de origem dos visitantes é destinado a contas VIP; solicitações de selo dependem de aprovação administrativa. As indicações não concedem remuneração automática.</p>
  <section class="lfp-final" aria-labelledby="lfp-features-final">
    <h2 id="lfp-features-final">Sua página pode fazer muito mais por sua apresentação.</h2>
    <p>Organize os caminhos que levam ao seu conteúdo e facilite a próxima conexão com seu público.</p>
    <div class="lfp-actions" style="justify-content:center">
      @auth
        <a class="lfp-pill" href="{{ route('panelIndex') }}">Abrir meu painel</a>
      @else
        @if(env('ALLOW_REGISTRATION') && !config('linkstack.single_user_mode'))
          <a class="lfp-pill" href="{{ route('register') }}">Começar agora</a>
        @elseif(Route::has('login'))
          <a class="lfp-pill" href="{{ route('login') }}">Entrar</a>
        @endif
      @endauth
      <a class="lfp-pill ghost" href="{{ route('public.roadmap') }}">Acompanhar próximas implementações</a>
    </div>
  </section>
</div>
@endsection
