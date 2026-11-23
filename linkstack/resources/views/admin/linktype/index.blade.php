@extends('layouts.sidebar')
@push('sidebar-stylesheets')
<link rel="stylesheet" href="{{ asset('assets/css/linkfree-admin-management.css') }}?v={{ filemtime(base_path('assets/css/linkfree-admin-management.css')) }}">
@endpush

@section('content')
<div class="container-fluid content-inner lf-block-types-page py-2">
  <div class="lf-type-hero">
    <div>
      <h1><i class="bi bi-grid-3x3-gap" aria-hidden="true"></i> Tipos de bloco</h1>
      <p class="text-muted mb-0">Gerencie os componentes disponíveis para as páginas públicas.</p>
    </div>
    <a class="btn btn-primary" href="{{ url('admin/linktype/create') }}"><i class="bi bi-plus-lg" aria-hidden="true"></i> Novo tipo de bloco</a>
  </div>
  @if(Session::has('message'))<div class="alert alert-info" role="status">{{ Session::get('message') }}</div>@endif
  @if(Session::has('success'))<div class="alert alert-success" role="status">{{ Session::get('success') }}</div>@endif
  <div class="row g-3">
    @forelse($linktype as $value)
      <div class="col-12 col-md-6 col-xl-4">
        <article class="lf-type-card" aria-labelledby="type-{{ $value->id }}">
          <div class="d-flex gap-3 align-items-start">
            <span class="lf-type-icon"><i class="{{ $value->icon }}" aria-hidden="true"></i></span>
            <div class="flex-grow-1" style="min-width:0">
              <h2 id="type-{{ $value->id }}">{{ $value->title }}</h2>
              <span class="lf-type-slug">{{ $value->typename }}</span>
            </div>
          </div>
          <p>{{ $value->description ?: 'Nenhuma descrição informada.' }}</p>
          <div class="lf-type-card-footer">
            <a class="btn btn-outline-primary" href="{{ url('admin/linktype/'.$value->id.'/edit') }}"><i class="bi bi-pencil" aria-hidden="true"></i> Editar</a>
            <form method="POST" action="{{ url('admin/linktype/'.$value->id) }}" onsubmit="return confirm('Excluir este tipo de bloco?');">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash" aria-hidden="true"></i> Excluir</button>
            </form>
          </div>
        </article>
      </div>
    @empty
      <div class="col-12"><div class="card p-4"><p class="mb-0">Nenhum tipo de bloco cadastrado.</p></div></div>
    @endforelse
  </div>
</div>
@endsection
