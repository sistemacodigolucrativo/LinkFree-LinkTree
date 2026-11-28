@extends('layouts.sidebar')
@push('sidebar-stylesheets')
<link rel="stylesheet" href="{{ asset('assets/css/linkfree-admin-management.css') }}?v={{ filemtime(base_path('assets/css/linkfree-admin-management.css')) }}">
@endpush
@section('content')
<div class="container-fluid content-inner py-2 lf-admin-themes-page">
  <div class="card"><div class="card-body">
    <h1 class="h3">Temas GitHub</h1>
    <p>Selecione temas do repositório configurado. As categorias Premium e VIP são equivalentes neste catálogo.</p>
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @foreach($errors->all() as $error)<div class="alert alert-danger" role="alert">{{ $error }}</div>@endforeach
    @if($catalogueError)<div class="alert alert-warning" role="alert">{{ $catalogueError }}</div>@endif
    <details class="border rounded p-3 mb-3" @if($catalogueError || !$repository) open @endif>
      <summary class="fw-bold" style="cursor:pointer">Configurar repositório e credenciais</summary>
      <form method="post" action="{{ route('githubThemes.configure') }}" class="mt-3">
        @csrf
        <div class="mb-3">
          <label class="form-label" for="theme-repository">URL do repositório GitHub</label>
          <input id="theme-repository" name="repository" type="url" class="form-control" value="https://github.com/{{ $repository }}" required>
        </div>
        <div class="mb-3">
          <label class="form-label" for="theme-visibility">Visibilidade do repositório</label>
          <select id="theme-visibility" name="private" class="form-select">
            <option value="0" @selected(!$private)>Público</option>
            <option value="1" @selected($private)>Privado</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label" for="theme-token">Token de leitura (somente repositório privado)</label>
          <input id="theme-token" name="token" type="password" class="form-control" autocomplete="new-password" aria-describedby="theme-token-help">
          <small id="theme-token-help" class="text-muted">Use a permissão Contents: read. {{ $hasToken ? 'Token configurado; deixe vazio para mantê-lo.' : '' }}</small>
        </div>
        <button class="btn btn-primary" type="submit">Validar e salvar</button>
      </form>
    </details>

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
      @if($updatesEnabled)<a class="btn {{ $checkingUpdates && count($updates) ? 'btn-success' : 'btn-outline-primary' }}" href="{{ route('githubThemes', ['updates' => 1]) }}">
        Verificar atualizações @if($checkingUpdates && count($updates)) ({{ count($updates) }}) @endif
      </a>@else <span class="text-muted">Atualizador de temas desativado.</span> @endif
      <a class="btn btn-outline-secondary" href="{{ route('showThemes') }}">Voltar ao gerenciamento</a>
    </div>

    @if($checkingUpdates)
      <section class="border rounded p-3 mb-4" aria-label="Resultado das atualizações">
        <h2 class="h5">Resultado da verificação</h2>
        @if($catalogueError)
          <p>Não foi possível concluir a verificação. Confira as credenciais e tente novamente.</p>
        @elseif(count($updates))
          <p>{{ count($updates) }} tema(s) com atualização disponível:</p>
          <ul class="mb-0">
            @foreach($updates as $update)
              <li><a href="{{ route('githubThemes', ['category' => $update['category']]) }}">{{ $update['name'] }} — {{ $update['category'] }}</a></li>
            @endforeach
          </ul>
        @else
          <p class="mb-0">Todos os temas reconhecidos pelo GitHub estão atualizados.</p>
        @endif
      </section>
    @endif

    <nav class="d-flex flex-wrap gap-2 mb-3" aria-label="Categorias de temas">
      @foreach(config('theme-repository.categories') as $key => $label)
        <a class="btn {{ $category === $key ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('githubThemes', ['category' => $key]) }}">{{ $label }}</a>
      @endforeach
    </nav>
    <div class="row g-3">
      @forelse($themes as $theme)
        <div class="col-12 col-sm-6 col-xl-4">
          <article class="lf-theme-card d-flex flex-column">
            <img loading="lazy" class="lf-theme-preview" src="{{ route('githubThemes.thumbnail', [$category, $theme['name']]) }}" alt="Pré-visualização do tema {{ $theme['name'] }}">
            <div class="lf-theme-card-body">
              <h2 class="h5" style="overflow-wrap:anywhere">{{ $theme['name'] }}</h2>
              <p>
                @if($theme['current']) Atualizado
                @elseif($theme['update_available'] && $updatesEnabled) <span class="text-success fw-bold">Atualização disponível</span>
                @elseif($theme['installed'] && !$updatesEnabled) Atualizador desativado
                @else Disponível para instalação
                @endif
              </p>
              <form class="mt-auto" method="post" action="{{ route('githubThemes.install', [$category, $theme['name']]) }}">
                @csrf
                <button type="submit" class="btn btn-primary" @disabled($theme['current'] || ($theme['installed'] && !$updatesEnabled))>{{ $theme['update_available'] ? 'Atualizar' : ($theme['current'] ? 'Instalado' : 'Instalar') }}</button>
              </form>
            </div>
          </article>
        </div>
      @empty
        <div class="col-12"><p>Nenhum pacote com thumbnail disponível nesta categoria.</p></div>
      @endforelse
    </div>
  </div></div>
</div>
@endsection
