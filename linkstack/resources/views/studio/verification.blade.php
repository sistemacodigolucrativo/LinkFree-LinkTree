@extends('layouts.sidebar')

@section('content')
<style>
.lf-verified-title-badge{display:inline-flex;align-items:center;vertical-align:middle}
.lf-verified-title-badge svg.badge{width:1em;height:1em;margin:0 .35em 0 0}
</style>
<div class="container-fluid content-inner py-0">
  <div class="card rounded-4">
    <div class="card-body p-3 p-md-4">
      <h1 class="h3 mb-2"><span class="lf-verified-title-badge" aria-hidden="true">@include('components.verify-svg')</span> {{ __('member_ui.verified_badge') }}</h1>
      <p class="text-muted">O selo confirma que o perfil recebeu aprovação administrativa. Somente o administrador pode conceder ou revogar a verificação.</p>

      @if(session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
      @endif
      @if($errors->any())
        <div class="alert alert-danger" role="alert">
          @foreach($errors->all() as $error)<p class="mb-1">{{ $error }}</p>@endforeach
        </div>
      @endif

      @if($member->verified_at)
        <div class="mb-3"><span class="badge rounded-pill bg-success px-3 py-2">Aprovado</span></div>
        <p>Seu selo foi concedido. Você pode escolher quando ele será exibido na sua página pública.</p>
        <form action="{{ route('studio.verification.visibility') }}" method="POST">
          @csrf
          <label class="form-label fw-semibold" for="verification-visible">Exibição do selo</label>
          <select id="verification-visible" class="form-select mb-3" name="visible">
            <option value="1" @selected($member->verification_visible)>Exibir selo</option>
            <option value="0" @selected(!$member->verification_visible)>Ocultar selo</option>
          </select>
          <button type="submit" class="btn btn-primary">Salvar preferência</button>
        </form>
      @else
        <p class="mb-2"><span class="badge rounded-pill {{ $application && $application->status === 'pending' ? 'bg-warning text-dark' : 'bg-secondary' }} px-3 py-2">
          {{ $application && $application->status === 'pending' ? 'Aguardando aprovação' : ($application && $application->status === 'rejected' ? 'Recusado' : ($application && $application->status === 'revoked' ? 'Revogado' : 'Não solicitado')) }}
        </span></p>
        @if($application && $application->status === 'rejected' && $application->admin_response)
          <div class="alert alert-info"><strong>Motivo da recusa:</strong> {{ $application->admin_response }}</div>
        @endif
        @if($application && $application->status === 'pending')
          <p>Seu pedido está aguardando análise do administrador. A opção de exibir ou ocultar aparecerá somente após aprovação.</p>
        @else
          <form action="{{ route('studio.verification.apply') }}" method="POST">
            @csrf
            <label class="form-label fw-semibold" for="request-message">Informações para avaliação (opcional)</label>
            <textarea id="request-message" name="message" rows="3" maxlength="700" class="form-control mb-2"
                placeholder="Explique brevemente por que seu perfil deve ser verificado. Não envie documentos nem dados sensíveis.">{{ old('message') }}</textarea>
            <button type="submit" class="btn btn-primary">Solicitar selo ao administrador</button>
          </form>
        @endif
      @endif
    </div>
  </div>
</div>
@endsection
