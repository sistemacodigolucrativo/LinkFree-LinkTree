@extends('layouts.sidebar')
@section('content')
<div class="container-fluid content-inner py-3 px-3 px-md-4">
  <nav aria-label="Navegação de configurações" class="mb-3">
    <a class="btn btn-outline-primary" href="{{ route('showConfig') }}">Voltar às configurações</a>
  </nav>
  <header class="mb-3">
    <h1 class="h2 mb-2">{{ $sectionTitle }}</h1>
    <p class="text-muted mb-0">{{ $sectionDescription }}</p>
  </header>
  <div class="card rounded-3"><div class="card-body p-3 p-md-4">
    @if($section === 'diagnostics')
      @include('components.config.diagnose')
    @else
      @include('components.config.config', ['section' => $section])
    @endif
  </div></div>
</div>
@endsection
