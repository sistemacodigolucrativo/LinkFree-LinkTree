@extends('layouts.sidebar')
@section('content')
<div class="container-fluid content-inner py-0">
 <div class="card"><div class="card-body">
 <h1 class="h3">Meus Temas Privados</h1>
 <p>Temas do seu repositório GitHub. Premium e VIP representam a mesma categoria.</p>
 @if(session('success'))<p class="alert alert-success" role="status">{{ session('success') }}</p>@endif
 @foreach($errors->all() as $error)<p class="alert alert-danger" role="alert">{{ $error }}</p>@endforeach
 @if($catalogueError)<p class="alert alert-warning">{{ $catalogueError }}</p>@endif
 <details><summary>Configurar repositório e credenciais</summary>
 <form method="post" action="{{ route('githubThemes.configure') }}" class="my-3">
 @csrf
 <label for="theme-repository">URL do repositório GitHub</label>
 <input id="theme-repository" name="repository" type="url" class="form-control" value="https://github.com/{{ $repository }}" required>
 <label for="theme-visibility">Visibilidade</label>
 <select id="theme-visibility" name="private" class="form-select"><option value="0" @selected(!$private)>Público</option><option value="1" @selected($private)>Privado</option></select>
 <label for="theme-token">Token de leitura (somente para repositório privado)</label>
 <input id="theme-token" name="token" type="password" class="form-control" autocomplete="new-password" aria-describedby="theme-token-help">
 <p id="theme-token-help">Permissão mínima: Contents somente leitura para o repositório selecionado. {{ $hasToken ? 'Credencial configurada. Deixe vazio para mantê-la.' : '' }}</p>
 <button class="btn btn-primary" type="submit">Validar e salvar</button>
 </form></details>
 <nav class="d-flex flex-wrap gap-2 my-3" aria-label="Categorias de temas">
 @foreach(config('theme-repository.categories') as $key => $label)<a class="btn {{ $category === $key ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('githubThemes', ['category' => $key]) }}">{{ $label }}</a>@endforeach
 <a class="btn btn-outline-secondary" href="{{ route('githubThemes', ['category' => $category]) }}">Verificar atualizações</a>
 </nav>
 <div class="row g-3">
 @forelse($themes as $theme)
 <div class="col-12 col-sm-6 col-xl-4"><article class="card h-100 border">
 <img loading="lazy" class="card-img-top" style="aspect-ratio:16/9;object-fit:contain" src="{{ route('githubThemes.thumbnail', [$category, $theme['name']]) }}" alt="Prévia de {{ $theme['name'] }}">
 <div class="card-body"><h2 class="h5" style="overflow-wrap:anywhere">{{ $theme['name'] }}</h2>
 <p>{{ $theme['current'] ? 'Atualizado' : ($theme['installed'] ? 'Atualização disponível' : 'Disponível para instalação') }}</p>
 <form method="post" action="{{ route('githubThemes.install', [$category, $theme['name']]) }}">@csrf
 <button type="submit" class="btn btn-primary" @disabled($theme['current'])>{{ $theme['installed'] ? 'Atualizar' : 'Instalar' }}</button></form>
 </div></article></div>
 @empty<p>Nenhum pacote de tema disponível nesta categoria.</p>@endforelse
 </div>
 <a class="btn btn-secondary mt-3" href="{{ url('/studio/theme') }}">Voltar aos temas</a>
 </div></div>
</div>
@endsection
