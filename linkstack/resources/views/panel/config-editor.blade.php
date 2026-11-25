@extends('layouts.sidebar')
@section('content')
<div class="container-fluid content-inner py-3 px-3 px-md-4">
  @if(request()->routeIs('showEnvEditor') || request()->has('alternative-config'))
    <div class="card rounded-3"><div class="card-body p-3 p-md-4">
      <h1 class="h3 mb-2">Variáveis de ambiente</h1>
      <p class="text-muted">Somente campos permitidos são editáveis; credenciais e chaves protegidas não são exibidas.</p>
      @if(!config('linkstack.single_user_mode'))
        @include('components.config.alternative-config')
      @else
        <p>Editor indisponível no modo de usuário único.</p>
      @endif
      <a class="btn btn-outline-primary mt-3" href="{{ route('showConfig') }}">Voltar às configurações</a>
    </div></div>
  @elseif(request()->routeIs('showFileEditor'))
    <div class="card rounded-3"><div class="card-body p-3 p-md-4">
      <h1 class="h3 mb-2">{{ __('messages.Advanced Config') }}</h1>
      @include('components.config.advanced-config')
      <a class="btn btn-outline-primary mt-3" href="{{ route('showConfig') }}">Voltar às configurações</a>
    </div></div>
  @else
    <header class="mb-4">
      <h1 class="h2 mb-2">Configurações administrativas</h1>
      <p class="text-muted mb-0">Cada área de configuração possui sua própria página.</p>
    </header>
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3" aria-label="Áreas de configuração">
      @foreach($configSections as $key => $metadata)
        <div class="col">
          <a class="card h-100 text-decoration-none rounded-3" href="{{ route('admin.config.section', ['section' => $key]) }}" aria-label="Abrir {{ $metadata[0] }}">
            <div class="card-body p-3 p-md-4">
              <h2 class="h5 mb-2">{{ $metadata[0] }}</h2>
              <p class="text-muted mb-0">{{ $metadata[1] }}</p>
            </div>
          </a>
        </div>
      @endforeach
    </div>
    <p class="text-muted mt-4">Ferramentas já existentes em Sistema (Backups, Ambiente ENV, Editor avançado e Informações PHP) continuam nos seus acessos atuais.</p>
  @endif
</div>
@if(request()->routeIs('showConfig'))
<script>
(function () {
  // Compatibilidade de links antigos: /admin/config#1..#5 e âncoras de seção.
  const legacy = {
    '#1': @json(route('admin.config.section', ['section' => 'application'])),
    '#2': @json(route('showFileEditor')),
    '#3': @json(url('/backup')),
    '#4': @json(route('showBackups')),
    '#5': @json(route('admin.config.section', ['section' => 'diagnostics'])),
    '#Application': @json(route('admin.config.section', ['section' => 'application'])),
    '#Panel-settings': @json(route('admin.config.section', ['section' => 'panel'])),
    '#Security': @json(route('admin.config.section', ['section' => 'security'])),
    '#Advanced': @json(route('admin.config.section', ['section' => 'advanced'])),
    '#SMTP': @json(route('admin.config.section', ['section' => 'smtp'])),
    '#Footer': @json(route('admin.config.section', ['section' => 'footer'])),
    '#Debug': @json(route('admin.config.section', ['section' => 'debug'])),
    '#Language': @json(route('admin.config.section', ['section' => 'language']))
  };
  const destination = legacy[window.location.hash];
  if (destination) window.location.replace(destination);
})();
</script>
@endif
@endsection
