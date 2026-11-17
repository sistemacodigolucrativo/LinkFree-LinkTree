@extends('layouts.sidebar')

<style>
/* Ação de pré-visualização sem o antigo card cinza. */
.theme-preview-strip {
  width:100%; max-width:1000px; margin:0 0 22px;
  padding:18px clamp(15px,3vw,26px);
  background:linear-gradient(115deg,#391a66,#6f215d 58%,#992b69);
  border-radius:16px; border:1px solid rgba(255,255,255,.18);
}
.theme-preview-strip .theme-preview-current {
  display:flex; align-items:center; justify-content:center; gap:9px;
  width:100%; min-height:50px; padding:12px 16px;
  background:#ffcc36!important; border:2px solid #b78313!important;
  border-radius:11px; color:#202020!important;
  font-weight:750; font-size:1rem; line-height:1.3;
}
.theme-preview-strip .theme-preview-current:hover,
.theme-preview-strip .theme-preview-current:active { background:#ffc01f!important; color:#171717!important; }
.theme-preview-strip .theme-preview-current:focus-visible { outline:3px solid #fff; outline-offset:3px; }
.theme-preview-strip .theme-preview-caption { color:#fff!important; line-height:1.5; }
@media(max-width:575px) {
  .theme-preview-strip { padding:14px; border-radius:12px; }
}
</style>
@section('content')

<div class="conatiner-fluid content-inner mt-n5 py-0">
    <div class="row">

     <div class="col-lg-12">
        <div class="card   rounded">
            <div class="card-body">
               <div class="row">
                   <div class="col-sm-12">

                    @foreach($pages as $page)

                    <section class='text-gray-400'>
                    <h3 class="mb-4 card-header"><i class="bi bi-brush">{{__('messages.Select a theme')}}</i></h3>
                    <div>

                        @if($errors->any())
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <svg class="bi flex-shrink-0 me-2" width="24" height="24">
                                <use xlink:href="#exclamation-triangle-fill"></use>
                            </svg>
                            <div>
                                @foreach ($errors->all() as $error)
                                    {{ $error }}
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <button type="button" class="btn btn-primary mb-5" data-bs-toggle="modal" data-bs-target="#exampleModal">
                        {{__('messages.Select theme')}}
                        </button>

                    <section class="text-gray-400"></section>
                    <div>

                        <div class="theme-preview-strip" aria-label="Pré-visualização do tema selecionado">
                          <button type="button" class="btn theme-preview-current"
                                  data-theme-preview-url="{{ route('themePreview', ['theme' => $page->theme ?: 'default']) }}">
                            <i class="bi bi-eye" aria-hidden="true"></i> Pré-visualizar tema atual
                          </button>
                          <p class="theme-preview-caption small mt-3 mb-0">Duas demonstrações com links, botões, vídeos e outros componentes, sem alterar sua página.</p>
                        </div>

                   </div>
               </div>
            </div>
         </div>
        </div>
      </div>
    </div>

    <div class="col-lg-12">
        <div class="card   rounded">
           <div class="card-body">
              <div class="row">
                  <div class="col-sm-12">
                    @if(env('ALLOW_CUSTOM_BACKGROUNDS') == true)
                    <form action="{{ route('themeBackground') }}" enctype="multipart/form-data" method="post">
                        @csrf
                        <h3 class="mb-4 card-header">{{__('messages.Custom background')}}</h3>
                        <div style="display: none;" class="form-group col-lg-8">
                            <select class="form-control" name="theme">
                                <option>{{ $page->theme }}</option>
                            </select>
                            <br>
                        </div>
                        <div class="form-group col-lg-8">
                            <figure style="max-width:1000px;max-height:562.5px;" class="figure">
                            @if(!file_exists(base_path('assets/img/background-img/'.findBackground(Auth::user()->id))))<p><i>{{__('messages.No image selected')}}</i></p>@endif
                            <img class="bd-placeholder-img figure-img img-fluid rounded" src="@if(file_exists(base_path('assets/img/background-img/'.findBackground(Auth::user()->id)))){{url('assets//img/background-img/'.findBackground(Auth::user()->id))}}@else{{url('/assets/linkstack/images/themes/no-preview.png')}}@endif"><br>
                            @if(file_exists(base_path('assets/img/background-img/'.findBackground(Auth::user()->id))))<a class="mt-3 ml-3 btn btn-primary" style="background-color:tomato!important;border-color:tomato!important;transform: scale(.9);" title="Delete background image" href="{{ url('/studio/rem-background') }}"><i class="bi bi-trash-fill"></i>{{__('messages.Remove background')}}</a><br>@endif
                            {{-- <figcaption class="figure-caption">A caption for the above image.</figcaption> --}}
                        </figure>
                            <br>
                            <br><br>
                            <div class="mb-3">
                                <input type="file" accept="image/jpeg,image/jpg,image/png,image/webp,image/gif" class="form-control form-control-lg" name="image" aria-label="Imagem de fundo"><br>
                            </div>
                        </div>
                            <button type="submit" class="btn btn-primary">{{__('messages.Apply')}}</button>
                    </form>
                    @endif
                  </div>
              </div>
           </div>
        </div>
     </div>

     @if(auth()->user()->role == 'admin')
    <div class="col-lg-12">
        <div class="card   rounded">
           <div class="card-body">
              <div class="row">
                  <div class="col-sm-12">
                    <h3 class="mb-4 card-header">{{__('messages.Manage themes')}}</h3>
                    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
                    <div class="row g-3">
                      <div class="col-12 col-lg-6">
                        <section class="border rounded p-3 h-100" aria-label="Temas GitHub">
                          <h4 class="h5">Temas GitHub</h4>
                          <p class="small text-muted">Instale temas Free e Premium/VIP ou consulte novas versões pelo repositório configurado.</p>
                          <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-primary" href="{{ route('githubThemes') }}">Selecionar temas GitHub</a>
                            <a id="github-theme-updates" class="btn btn-outline-primary" href="{{ route('githubThemes', ['updates' => 1]) }}">Verificar atualizações</a>
                          </div>
                          <p id="github-theme-status" class="small mt-2 mb-0" aria-live="polite"></p>
                        </section>
                      </div>
                      <div class="col-12 col-lg-6">
                        <section class="border rounded p-3 h-100" aria-label="Enviar temas">
                          <h4 class="h5">Enviar temas</h4>
                          <p class="small text-muted">Escolha um ZIP. O upload e a instalação começarão automaticamente.</p>
                          <form action="{{ route('editTheme') }}" method="post" enctype="multipart/form-data" id="theme-upload-form">
                            @csrf
                            <label for="field-zip" class="form-label">Arquivo ZIP do tema</label>
                            <input type="file" accept=".zip,application/zip" name="zip" class="form-control" id="field-zip" required>
                            <span id="theme-upload-status" class="small" role="status"></span>
                            <noscript><button type="submit" class="btn btn-primary mt-2">Enviar tema</button></noscript>
                          </form>
                        </section>
                      </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                      <a href="{{ route('showThemes') }}" class="btn btn-outline-danger">{{__('messages.Delete themes')}}</a>
                      <a href="https://linkstack.org/themes/" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary">{{__('messages.Download themes')}}</a>
                    </div>

                    </div>
                  </div>
              </div>
           </div>
        </div>
     </div>
     @endif

@endforeach

<script src="{{ asset('assets/external-dependencies/jquery-1.12.4.min.js') }}"></script>
</section>
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
  if (updateLink) {
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

@push('sidebar-scripts')
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{__('messages.Select a theme')}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <form action="{{ route('editTheme') }}" enctype="multipart/form-data" method="post">
                @csrf
                <select id="theme-select" style="display:none;" name="theme" data-base-url="{{ url('') }}/@<?= Auth::user()->littlelink_name ?>"><option value="default" selected></option></select>
                <div class="row">
                    <div class="col-lg-3">
                        <div class="card shadow-lg @if($page->theme == "" or $page->theme == "default") bg-primary @else bg-soft-primary @endif">
                           <div class="card-body pb-0">
                            <button type="button" aria-label="Default Theme" aria-pressed="{{ ($page->theme == '' || $page->theme == 'default') ? 'true' : 'false' }}" style="cursor:pointer;border:0;background:transparent;color:inherit;font:inherit;padding:0" onclick="setTheme('default')">
                              <div class="d-flex justify-content-between">
                                 <div>
                                    <img draggable="false" class="bd-placeholder-img bd-placeholder-img-lg img-fluid" src="{{url('assets/linkstack/images/themes/default.png')}}">
                                 </div>
                              </div>
                              <div class="text-center">
                                 <h2 class="m-3 @if($page->theme == "" or $page->theme == "default") text-white @else text-gray @endif"">Default Theme</h2>
                                 <div>
                                 </div>
                              </div>
                            </button>
                            <button type="button" class="btn btn-outline-primary mb-3 w-100" data-theme-preview-url="{{ route('themePreview', ['theme' => 'default']) }}"><i class="bi bi-eye"></i> Pré-visualizar</button>
                           </div>
                        </div>
                    </div>
                                    <?php
                                        if ($handle = opendir('themes')) {
                                            while (false !== ($entry = readdir($handle))) {
                                                if ($entry != "." && $entry != ".." && preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/D', $entry) && is_dir(base_path('themes/'.$entry))) {
                                                    unset($themeName);
                                                    if(file_exists(base_path('themes') . '/' . $entry . '/readme.md')){
                                                        $text = file_get_contents(base_path('themes') . '/' . $entry . '/readme.md');
                                                        $pattern = '/Theme Name:.*/';
                                                        preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
                                                        if(sizeof($matches) > 0) {
                                                            $themeName = substr($matches[0][0],12);
                                                        }
                                                    }
                                                    if(isset($themeName)){
                                                        ?>

                                                        <div class="col-lg-3">
                                                            <div class="card shadow-lg @if($page->theme == $entry) bg-primary @else bg-soft-primary @endif">
                                                               <div class="card-body pb-0">
                                                                <button type="button" aria-label="{{ $themeName }}" aria-pressed="{{ $page->theme == $entry ? 'true' : 'false' }}" style="cursor:pointer;border:0;background:transparent;color:inherit;font:inherit;padding:0" onclick="setTheme('{{$entry}}')">
                                                                  <div class="d-flex justify-content-between">
                                                                     <div>
                                                                        <img draggable="false" class="bd-placeholder-img bd-placeholder-img-lg img-fluid" src="{{url('themes/'.$entry.'/preview.png')}}">
                                                                     </div>
                                                                  </div>
                                                                  <div class="text-center">
                                                                     <h2 class="m-3 @if($page->theme == $entry) text-white @else text-gray @endif">{{$themeName}}</h2>
                                                                     <div>
                                                                     </div>
                                                                  </div>
                                                                </button>
                                                                <button type="button" class="btn btn-outline-primary mb-3 w-100" data-theme-preview-url="{{ route('themePreview', ['theme' => $entry]) }}"><i class="bi bi-eye"></i> Pré-visualizar</button>
                                                               </div>
                                                            </div>
                                                        </div>

                                                        <?php
                                                    }
                                                }
                                            }
                                        }
                                    ?>
                 </div>
                </form>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{__('messages.Close')}}</button>
            </div>
        </div>
    </div>
    </div>
<div class="modal fade" id="themePreviewModal" tabindex="-1" aria-labelledby="themePreviewModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="themePreviewModalTitle">Pré-visualização do tema</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex flex-wrap gap-2 mb-3" role="group" aria-label="Versões da demonstração">
          <button class="btn btn-primary" type="button" data-demo-variant="links">1. Links e botões</button>
          <button class="btn btn-outline-primary" type="button" data-demo-variant="media">2. Vídeos e mídias</button>
        </div>
        <iframe id="theme-demo-frame" title="Demonstração renderizada do tema" class="w-100 border rounded" style="height:min(72vh,760px);min-height:420px" loading="lazy" sandbox="allow-same-origin"></iframe>
        <p class="small text-muted">A demonstração usa conteúdo fictício. Componentes incompatíveis são apresentados em telas diferentes.</p>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const modalElement = document.getElementById('themePreviewModal');
  const selectionElement = document.getElementById('exampleModal');
  const frame = document.getElementById('theme-demo-frame');
  const Modal = window.bootstrap && window.bootstrap.Modal;
  const hasBootstrap5 = typeof Modal === 'function';
  const hasJqueryModal = window.jQuery && typeof jQuery.fn.modal === 'function';
  if (!modalElement || (!hasBootstrap5 && !hasJqueryModal)) return;
  function getModal(element) {
    if (hasBootstrap5) return Modal.getOrCreateInstance ? Modal.getOrCreateInstance(element) : new Modal(element);
    return {
      show: function () { jQuery(element).modal('show'); },
      hide: function () { jQuery(element).modal('hide'); }
    };
  }
  const modal = getModal(modalElement);
  const selector = getModal(selectionElement);
  const variantButtons = modalElement.querySelectorAll('[data-demo-variant]');
  let selectedUrl = '';
  let returnToSelection = false;
  function variant(value) {
    frame.src = selectedUrl + (selectedUrl.indexOf('?') === -1 ? '?' : '&') + 'variant=' + value;
    variantButtons.forEach(function (button) {
      button.classList.toggle('btn-primary', button.dataset.demoVariant === value);
      button.classList.toggle('btn-outline-primary', button.dataset.demoVariant !== value);
    });
  }
  document.querySelectorAll('[data-theme-preview-url]').forEach(function (button) {
    button.addEventListener('click', function () {
      selectedUrl = button.dataset.themePreviewUrl;
      returnToSelection = selectionElement.classList.contains('show');
      variant('links');
      if (returnToSelection) {
        selectionElement.addEventListener('hidden.bs.modal', function () { modal.show(); }, { once: true });
        selector.hide();
      } else modal.show();
    });
  });
  variantButtons.forEach(function (button) {
    button.addEventListener('click', function () { if (selectedUrl) variant(button.dataset.demoVariant); });
  });
  modalElement.addEventListener('hidden.bs.modal', function () {
    frame.removeAttribute('src');
    if (returnToSelection) selector.show();
    returnToSelection = false;
  });
});
</script>
<script>
      function setTheme(themeName) {
        const selectElement = document.getElementById('theme-select');
        selectElement.querySelector('option').value = themeName;
        selectElement.form.submit();
      }
  </script>
@endpush

@endsection
