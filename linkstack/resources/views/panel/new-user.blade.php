@extends('layouts.sidebar')
@section('content')
<div class="container py-4">
  <h1>Novo usuário</h1>
  <p>Confirme para criar uma conta provisória e abrir sua edição. Nenhum cadastro é criado ao acessar esta página.</p>
  <form method="POST" action="{{ route('createNewUser') }}">
    @csrf
    <button type="submit" class="btn btn-primary">Criar usuário e editar dados</button>
    <a href="{{ route('showUsers') }}" class="btn btn-secondary">Cancelar</a>
  </form>
</div>
@endsection
