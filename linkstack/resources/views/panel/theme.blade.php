@extends('layouts.sidebar')
@section('content')
<div class="container-fluid content-inner mt-n5 py-0">
  <div class="card rounded">
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
            <article class="border rounded p-3 h-100 d-flex flex-column">
              <h2 class="h5 mb-3" style="overflow-wrap:anywhere">{{ $theme }}</h2>
              @if($hasDemo)
                <iframe loading="lazy" sandbox="allow-same-origin" title="Prévia renderizada de {{ $theme }}" class="w-100 border rounded" style="height:310px" src="{{ route('themePreview', ['theme' => $theme]) }}"></iframe>
              @elseif($hasImage)
                <img loading="lazy" class="w-100 border rounded" style="height:310px;object-fit:contain" src="{{ asset('themes/'.$theme.'/preview.png') }}" alt="Prévia de {{ $theme }}">
              @else
                <div class="border rounded d-grid text-muted text-center align-items-center" style="height:310px">Prévia indisponível</div>
              @endif
              <div class="mt-auto pt-3">
                @if($inUse)
                  <p class="small text-muted mb-2">Tema em uso. Altere o tema dos usuários antes de excluir.</p>
                @endif
                <form class="theme-delete-form" method="post" action="{{ route('deleteTheme') }}" data-theme="{{ $theme }}">
                  @csrf
                  <input type="hidden" name="deltheme" value="{{ $theme }}">
                  <button type="submit" class="btn btn-danger w-100" @disabled($inUse)>Excluir {{ $theme }}</button>
                </form>
              </div>
            </article>
          </div>
        @empty
          <p>Nenhum tema instalado disponível para exclusão.</p>
        @endforelse
      </div>
      <a class="btn btn-outline-primary mt-4" href="{{ route('showTheme') }}"><i class="bi bi-arrow-left-short"></i> {{ __('messages.Back') }}</a>
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
@endsection
