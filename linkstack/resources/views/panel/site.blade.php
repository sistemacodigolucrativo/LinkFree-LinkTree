@extends('layouts.sidebar')
@push('sidebar-stylesheets')
<link rel="stylesheet" href="{{ asset('assets/css/linkfree-admin-management.css') }}?v={{ filemtime(base_path('assets/css/linkfree-admin-management.css')) }}">
@endpush

@section('content')
<div class="container-fluid content-inner py-2 lf-site-settings-page">
  <header class="mb-3">
    <h1 class="h3 mb-2"><i class="bi bi-palette" aria-hidden="true"></i> {{ __('messages.Site Customization') }}</h1>
    <p class="text-muted mb-0">Configure a página inicial por áreas, sem percorrer um formulário interminável. As páginas pessoais de cada membro continuam independentes.</p>
  </header>

  <nav class="lf-site-settings-nav mb-3" aria-label="Seções de personalização da página pública">
    <a href="{{ route('showSite', ['section' => 'identity']) }}" class="{{ $siteSection === 'identity' ? 'is-active' : '' }}"
       @if($siteSection === 'identity') aria-current="page" @endif>Marca e identidade</a>
    @foreach($siteSections as $sectionKey => $sectionInfo)
      <a href="{{ route('showSite', ['section' => $sectionKey]) }}" class="{{ $siteSection === $sectionKey ? 'is-active' : '' }}"
         @if($siteSection === $sectionKey) aria-current="page" @endif>{{ $sectionInfo['title'] }}</a>
    @endforeach
  </nav>

  @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
  @if(session('fail'))<div class="alert alert-danger" role="alert">{{ session('fail') }}</div>@endif
  @if($errors->any())
    <div class="alert alert-danger" role="alert">
      <strong>Revise os campos informados:</strong>
      <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
  @endif

  @if($siteSection === 'identity')
    <section class="card rounded lf-site-form-card" aria-labelledby="lf-site-brand-title">
      <div class="card-body p-3 p-md-4">
        <h2 id="lf-site-brand-title" class="h4 mb-2">Marca, imagens e mensagem inicial</h2>
        <p class="text-muted mb-4">Edite os elementos já suportados pelo sistema, preservando o upload e o editor existentes.</p>
        <form action="{{ route('editSite') }}" enctype="multipart/form-data" method="post">
          @csrf
          <div class="lf-site-fields">
            <div class="lf-site-field">
              <label for="field-site-logo" class="form-label fw-semibold">{{__('messages.Site logo')}}</label>
              @if(file_exists(base_path('assets/linkstack/images/').findFile('avatar')))
                <button class="btn btn-link btn-sm ms-2 text-danger" type="submit" formaction="{{ route('delAvatar') }}" formmethod="post" onclick="return confirm('Remover logotipo?')">Remover logotipo</button>
              @endif
              <input type="file" class="form-control" id="field-site-logo" name="image" accept="image/*">
            </div>
            <div class="lf-site-field">
              <label for="field-site-favicon" class="form-label fw-semibold">{{__('messages.Favicon')}}</label>
              @if(file_exists(base_path('assets/linkstack/images/').findFile('favicon')))
                <button class="btn btn-link btn-sm ms-2 text-danger" type="submit" formaction="{{ route('delFavicon') }}" formmethod="post" onclick="return confirm('Remover favicon?')">Remover favicon</button>
              @endif
              <input type="file" class="form-control" id="field-site-favicon" name="icon" accept="image/*">
            </div>
            <div class="lf-site-field lf-site-field-wide">
              <label for="lf-site-home-message" class="form-label fw-semibold">{{ __('messages.Home message') }}</label>
              @php $existingMessage = $home_message === 'default' ? __('messages.HOME.MESSAGE') : $home_message; @endphp
              <textarea class="form-control ckeditor" id="lf-site-home-message" name="message" rows="4">{{ old('message', $existingMessage) }}</textarea>
              <p class="form-text">Esse campo também alimenta a descrição existente na página inicial. Os textos das seções são editados nas demais abas.</p>
            </div>
          </div>
          <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-check-lg" aria-hidden="true"></i> Salvar identidade</button>
        </form>
      </div>
    </section>
    <section class="card rounded lf-site-form-card mt-3" aria-labelledby="lf-site-related-title">
      <div class="card-body p-3 p-md-4">
        <h2 class="h5" id="lf-site-related-title">Outras áreas públicas</h2>
        <p class="text-muted">As páginas legais, visibilidade dos links do rodapé e temas públicos já possuem configurações próprias, que permanecem disponíveis.</p>
        <div class="d-flex flex-wrap gap-2">
          <a class="btn btn-outline-primary" href="{{ route('showSitePage') }}">Termos, privacidade e contato</a>
          <a class="btn btn-outline-primary" href="{{ route('showConfig') }}">Configurar links do rodapé</a>
          <a class="btn btn-outline-primary" href="{{ route('showThemes') }}">Temas disponíveis</a>
        </div>
      </div>
    </section>
    <script src="{{ asset('resources/ckeditor/ckeditor.js') }}"></script>
  @else
    @php $currentSiteSection = $siteSections[$siteSection]; @endphp
    <section class="card rounded lf-site-form-card" aria-labelledby="lf-site-section-title">
      <div class="card-body p-3 p-md-4">
        <h2 class="h4 mb-2" id="lf-site-section-title">{{ $currentSiteSection['title'] }}</h2>
        <p class="text-muted mb-4">{{ $currentSiteSection['description'] }}</p>
        <form method="post" action="{{ route('admin.site.content') }}">
          @csrf
          <input type="hidden" name="section" value="{{ $siteSection }}">
          <div class="lf-site-fields">
            @foreach($currentSiteSection['fields'] as $key => $definition)
              <div class="lf-site-field {{ !empty($definition['multiline']) ? 'lf-site-field-wide' : '' }}">
                @if(($definition['type'] ?? 'text') === 'checkbox')
                  <div class="form-check form-switch lf-site-visibility">
                    <input class="form-check-input" id="lf-site-{{ $key }}" type="checkbox" name="{{ $key }}" value="1"
                           @checked((bool) old($key, $siteValues[$key] ?? false))>
                    <label class="form-check-label fw-semibold" for="lf-site-{{ $key }}">{{ $definition['label'] }}</label>
                  </div>
                @else
                  <label class="form-label fw-semibold" for="lf-site-{{ $key }}">{{ $definition['label'] }}</label>
                  @if(!empty($definition['multiline']))
                    <textarea class="form-control" rows="3" id="lf-site-{{ $key }}"
                              name="{{ $key }}" maxlength="{{ $definition['max'] }}">{{ old($key, $siteValues[$key] ?? '') }}</textarea>
                  @else
                    <input class="form-control" type="text" id="lf-site-{{ $key }}"
                           name="{{ $key }}" value="{{ old($key, $siteValues[$key] ?? '') }}"
                           maxlength="{{ $definition['max'] }}">
                  @endif
                @endif
              </div>
            @endforeach
          </div>
          <div class="lf-site-actions mt-4">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Salvar esta seção</button>
            <a href="{{ url('/') }}" class="btn btn-outline-secondary" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Ver página pública</a>
          </div>
        </form>
      </div>
    </section>
    @if($siteSection === 'footer')
      <section class="card rounded lf-site-form-card mt-3">
        <div class="card-body p-3 p-md-4">
          <h3 class="h5">Conteúdos institucionais e visibilidade</h3>
          <p class="text-muted">A edição do conteúdo jurídico e as opções de exibição continuam em módulos dedicados.</p>
          <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary" href="{{ route('showSitePage') }}">Editar páginas institucionais</a>
            <a class="btn btn-outline-primary" href="{{ route('showConfig') }}">Ativar ou desativar links</a>
          </div>
        </div>
      </section>
    @endif
  @endif
</div>
@endsection
