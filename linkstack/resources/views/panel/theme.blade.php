@extends('layouts.sidebar')
@push('sidebar-stylesheets')
<link rel="stylesheet" href="{{ asset('assets/css/linkfree-admin-management.css') }}?v={{ filemtime(base_path('assets/css/linkfree-admin-management.css')) }}">
@endpush
@section('content')
<div class="container-fluid content-inner py-2 lf-admin-themes-page">

<section class="card rounded mb-3" id="admin-theme-management" aria-label="Gerenciamento administrativo de temas">
<div class="card-body">
<h1 class="h3 mb-3"><i class="bi bi-brush"></i> {{__('messages.Nav Admin Theme Manager')}}</h1>
<div class="row g-3">
<div class="col-12 col-lg-6"><section class="border rounded p-3 h-100" aria-label="Temas GitHub">
<h2 class="h5 mb-2"><i class="bi bi-github"></i> {{__('messages.Nav Admin GitHub Themes')}}</h2>
<p class="small text-muted">Instale temas Free e Premium/VIP ou consulte novas versões pelo repositório configurado.</p>
<div class="d-flex flex-wrap gap-2">
<a class="btn btn-primary" href="{{ route('githubThemes') }}">Selecionar temas GitHub</a>
@if(filter_var(env('ENABLE_THEME_UPDATER', true), FILTER_VALIDATE_BOOLEAN))<a id="github-theme-updates" class="btn btn-outline-primary" href="{{ route('githubThemes', ['updates' => 1]) }}">Verificar atualizações</a>@else<span class="text-muted">Atualizador de temas desativado.</span>@endif
</div><p id="github-theme-status" class="small mt-2 mb-0" aria-live="polite"></p>
</section></div>
<div class="col-12 col-lg-6"><section class="border rounded p-3 h-100" aria-label="Enviar temas">
<h2 class="h5 mb-2">Enviar temas</h2>
<p class="small text-muted">Escolha um ZIP. O upload e a instalação começarão automaticamente.</p>
<form action="{{ route('editTheme') }}" method="post" enctype="multipart/form-data" id="theme-upload-form">
@csrf
<label for="field-zip" class="form-label">Arquivo ZIP do tema</label>
<input type="file" accept=".zip,application/zip" name="zip" class="form-control" id="field-zip" required>
<span id="theme-upload-status" class="small" role="status"></span>
<noscript><button type="submit" class="btn btn-primary mt-2">Enviar tema</button></noscript>
</form></section></div>
</div>
<a href="https://linkstack.org/themes/" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary mt-3">{{__('messages.Download themes')}}</a>
</div></section>

<section class="card rounded-4 mb-3" aria-labelledby="lf-theme-admin-options">
 <div class="card-body p-3 p-md-4">
  <h2 class="h5" id="lf-theme-admin-options">{{ __('messages.lf_theme_admin_title') }}</h2>
  <p class="text-muted">{{ __('messages.lf_theme_admin_help') }}</p>
  <form method="post" action="{{ route('admin.theme-options') }}">
   @csrf
   <div class="row g-3">
    @php $lfAllowed = \App\Services\ThemeOptions::permissions(); @endphp
    @foreach(\App\Services\ThemeOptions::fields() as $field => $definition)
    <div class="col-12 col-sm-6 col-lg-4">
     <div class="form-check form-switch border rounded-3 p-3 ps-5 h-100">
      <input class="form-check-input" type="checkbox" id="lf-admin-{{ $field }}"
       name="{{ $field }}" value="1" @checked($lfAllowed[$field] ?? false)>
      <label class="form-check-label" for="lf-admin-{{ $field }}">{{ __('messages.'.$definition['label']) }}</label>
     </div>
    </div>
    @endforeach
   </div>
   <button type="submit" class="btn btn-primary mt-3">{{ __('messages.Save') }}</button>
  </form>
 </div>
</section>

  <div class="card rounded" id="admin-installed-themes">
    <div class="card-body">
      <h1 class="h3 mb-2"><i class="bi bi-brush"></i> {{ __('messages.Delete a theme') }}</h1>
      <p class="text-muted">Visualize cada tema antes de excluir. Temas em uso não podem ser removidos.</p>
      @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
      @foreach($errors->all() as $error)<div class="alert alert-danger" role="alert">{{ $error }}</div>@endforeach
      @php
        $directories = glob(base_path('themes/*'), GLOB_ONLYDIR) ?: [];
        $themes = [];
        foreach ($directories as $directory) {
          $name = basename($directory);
          if (preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/D', $name) && !is_link($directory)) $themes[] = $name;
        }
        sort($themes, SORT_NATURAL | SORT_FLAG_CASE);
      @endphp
      <div class="row g-3">
        @forelse($themes as $theme)
          @php
            $inUse = \App\Models\User::where('theme', $theme)->exists();
            $hasDemo = is_file(base_path('themes/'.$theme.'/config.php'));
            $hasImage = is_file(base_path('themes/'.$theme.'/preview.png'));
          @endphp
          <div class="col-12 col-sm-6 col-xl-4">
            <article class="lf-theme-card d-flex flex-column">
              @if($hasImage)
                <img loading="lazy" class="lf-theme-preview" src="{{ asset('themes/'.$theme.'/preview.png') }}" alt="Pré-visualização do tema {{ $theme }}">
              @elseif($hasDemo)
                <iframe loading="lazy" sandbox="allow-same-origin" title="Prévia renderizada de {{ $theme }}" class="lf-theme-preview" src="{{ route('themePreview', ['theme' => $theme]) }}"></iframe>
              @else
                <div class="lf-theme-preview lf-theme-preview-empty" role="img" aria-label="Prévia indisponível"><i class="bi bi-image" aria-hidden="true"></i> Prévia indisponível</div>
              @endif
              <div class="lf-theme-card-body">
                <h2 class="lf-theme-card-title">{{ $theme }}</h2>
                <p class="lf-theme-card-meta">{{ $inUse ? 'Em uso por um ou mais usuários' : 'Tema instalado' }}</p>
                <div class="lf-theme-actions">
                  @if($hasDemo)
                    <a class="btn btn-outline-primary" href="{{ route('themePreview', ['theme' => $theme]) }}" target="_blank" rel="noopener noreferrer">Abrir prévia</a>
                  @endif
                  <form class="theme-delete-form flex-grow-1" method="post" action="{{ route('deleteTheme') }}" data-theme="{{ $theme }}">
                    @csrf
                    <input type="hidden" name="deltheme" value="{{ $theme }}">
                    <button type="submit" class="btn btn-danger w-100" @disabled($inUse)>Excluir</button>
                  </form>
                </div>
                @if($inUse)<p class="lf-theme-card-meta mb-0">Para excluir, primeiro altere o tema dos usuários.</p>@endif
              </div>
            </article>
          </div>
        @empty
          <p>Nenhum tema instalado disponível para exclusão.</p>
        @endforelse
      </div>
      <a class="btn btn-outline-primary mt-4" href="{{ route('githubThemes') }}"><i class="bi bi-arrow-left-short"></i> {{ __('messages.Back') }}</a>
    </div>
  </div>
</div>
<script>
document.querySelectorAll('.theme-delete-form').forEach(function (form) {
  form.addEventListener('submit', function (event) {
    if (!window.confirm('Excluir definitivamente o tema "' + form.dataset.theme + '"? Esta ação não pode ser desfeita.')) event.preventDefault();
  });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const file = document.getElementById('field-zip');
  const form = document.getElementById('theme-upload-form');
  if (file && form) {
    file.addEventListener('change', function () {
      if (!file.files || !file.files.length) return;
      if (!/\.zip$/i.test(file.files[0].name)) {
        file.setCustomValidity('Selecione um arquivo ZIP.');
        file.reportValidity();
        return;
      }
      file.setCustomValidity('');
      document.getElementById('theme-upload-status').textContent = 'Enviando e instalando tema...';
      if (form.requestSubmit) form.requestSubmit();
      else form.submit();
    });
  }
  const updateLink = document.getElementById('github-theme-updates');
  const status = document.getElementById('github-theme-status');
  if (updateLink && status) {
    fetch(@json(route('githubThemes.status')), { credentials: 'same-origin' })
      .then(function (response) { if (!response.ok) throw new Error(); return response.json(); })
      .then(function (result) {
        if (result.count > 0) {
          updateLink.classList.remove('btn-outline-primary');
          updateLink.classList.add('btn-success');
          updateLink.textContent = 'Verificar atualizações (' + result.count + ')';
          status.textContent = result.count + ' tema(s) com atualização disponível.';
        } else {
          status.textContent = 'Nenhuma atualização disponível.';
        }
      }).catch(function () { status.textContent = 'Não foi possível consultar as atualizações agora.'; });
  }
});
</script>
@endsection
