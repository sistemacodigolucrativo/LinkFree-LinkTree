@extends('layouts.sidebar')

@section('content')
<div class="container-fluid content-inner py-3 lf-new-user-page">
  <div class="card rounded-4">
    <div class="card-body p-3 p-md-4">
      <h1 class="h4 mb-2"><i class="bi bi-person-plus" aria-hidden="true"></i> Novo usuário</h1>
      <p class="text-muted mb-3">
        Revise os dados sugeridos. <strong>Nenhum usuário é criado antes de clicar em Salvar.</strong>
        Copie a senha inicial antes de confirmar; ela não será exibida novamente.
      </p>

      @if($errors->any())
        <div class="alert alert-danger" role="alert">
          <strong>Usuário não criado. Corrija os campos:</strong>
          <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
      @endif

      <form method="POST" action="{{ route('createNewUser') }}" id="lf-admin-create-user">
        @csrf
        <div class="row g-3">
          <div class="col-12 col-md-8">
            <label for="new-user-name" class="form-label fw-semibold">Nome</label>
            <input type="text" class="form-control" id="new-user-name" name="name"
                   value="{{ old('name', $suggestedName) }}" maxlength="255" autocomplete="off" required>
          </div>
          <div class="col-12 col-md-8">
            <label for="new-user-email" class="form-label fw-semibold">E-mail</label>
            <input type="email" class="form-control" id="new-user-email" name="email"
                   value="{{ old('email', $suggestedEmail) }}" maxlength="255" autocomplete="off" required>
          </div>
          <div class="col-12 col-md-8">
            <label for="new-user-password" class="form-label fw-semibold">Senha inicial</label>
            <div class="input-group">
              <input type="password" class="form-control" id="new-user-password" name="password"
                     value="{{ $suggestedPassword }}" minlength="8" maxlength="255" autocomplete="new-password" required>
              <button class="btn btn-outline-secondary" type="button" id="lf-reveal-new-user-password"
                      aria-controls="new-user-password" aria-pressed="false">Mostrar</button>
            </div>
            <div class="form-text">Copie ou substitua a senha sugerida antes de salvar.</div>
          </div>
        </div>
        <p class="text-muted small my-3">Plano inicial: <strong>{{ $newUserRole === 'vip' ? 'VIP' : 'Usuário' }}</strong>. As demais opções podem ser editadas depois do cadastro.</p>
        <div class="d-flex gap-2 flex-wrap mt-3">
          <button type="submit" class="btn btn-success" id="lf-save-new-user">
            <i class="bi bi-check-lg" aria-hidden="true"></i> Salvar usuário
          </button>
          <a href="{{ route('showUsers') }}" class="btn btn-outline-secondary">Cancelar sem criar</a>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded',function(){
  const button=document.getElementById('lf-reveal-new-user-password');
  const field=document.getElementById('new-user-password');
  if(!button||!field)return;
  button.addEventListener('click',function(){
    const visible=field.type==='text';
    field.type=visible?'password':'text';
    button.textContent=visible?'Mostrar':'Ocultar';
    button.setAttribute('aria-pressed',String(!visible));
  });
});
</script>
@endsection
