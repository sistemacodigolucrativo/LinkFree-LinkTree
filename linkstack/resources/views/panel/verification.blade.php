@extends('layouts.sidebar')

@section('content')
<div class="container-fluid content-inner py-0">
  <div class="card rounded-4">
    <div class="card-body p-3 p-md-4">
      <h1 class="h3 mb-2"><i class="bi bi-patch-check" aria-hidden="true"></i> Solicitações de selo verificado</h1>
      <p class="text-muted">Aprovar ou recusar solicitações. Para conceder ou revogar sem solicitação, use a badge Selo na lista de membros.</p>

      @if(session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
      @endif
      @if($errors->any())
        <div class="alert alert-danger" role="alert">
          @foreach($errors->all() as $error)<p class="mb-1">{{ $error }}</p>@endforeach
        </div>
      @endif

      <div class="row g-3 mb-4">
        <div class="col-12 col-md-6"><div class="border rounded-3 p-3"><span class="small text-muted d-block">Solicitações pendentes</span><strong class="fs-3">{{ $pendingCount }}</strong></div></div>
        <div class="col-12 col-md-6"><div class="border rounded-3 p-3"><span class="small text-muted d-block">Perfis com selo concedido</span><strong class="fs-3">{{ $approvedCount }}</strong></div></div>
      </div>

      <h2 class="h5">Fila de análise</h2>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead><tr><th scope="col">Membro</th><th scope="col">Solicitação</th><th scope="col">Situação</th><th scope="col">Decisão</th></tr></thead>
          <tbody>
          @forelse($applications as $entry)
            <tr>
              <td>
                <strong>{{ $entry->member_name }}</strong>
                <span class="d-block small text-muted">{{ $entry->member_email }}</span>
                <span class="d-block small">ID {{ $entry->user_id }}</span>
              </td>
              <td>
                <span class="d-block small text-muted">{{ \Illuminate\Support\Carbon::parse($entry->requested_at)->format('d/m/Y H:i') }}</span>
                <span>{{ $entry->message ?: 'Nenhuma observação enviada.' }}</span>
              </td>
              <td>
                <span class="badge rounded-pill {{ $entry->status === 'approved' ? 'bg-success' : ($entry->status === 'pending' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                  {{ ['pending' => 'Pendente', 'approved' => 'Aprovado', 'rejected' => 'Recusado', 'revoked' => 'Revogado'][$entry->status] ?? $entry->status }}
                </span>
                @if($entry->admin_response)
                  <small class="d-block text-muted mt-1">{{ $entry->admin_response }}</small>
                @endif
              </td>
              <td>
                @if($entry->status === 'pending')
                  <form class="mb-2" action="{{ route('admin.verification.review', ['id' => $entry->id]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="decision" value="approve">
                    <button type="submit" class="btn btn-sm btn-success">Aprovar</button>
                  </form>
                  <form action="{{ route('admin.verification.review', ['id' => $entry->id]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="decision" value="reject">
                    <label class="small" for="reject-{{ $entry->id }}">Motivo da recusa</label>
                    <textarea id="reject-{{ $entry->id }}" name="response" class="form-control form-control-sm mb-2"
                              maxlength="1000" rows="2" required></textarea>
                    <button type="submit" class="btn btn-sm btn-outline-danger">Recusar</button>
                  </form>
                @else
                  <span class="small text-muted">{{ $entry->reviewer_name ?: '—' }}</span>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="text-muted">Não há solicitações de selo.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
      {{ $applications->links() }}

      <h2 class="h5 mt-4">Histórico de ações recentes</h2>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead><tr><th scope="col">Data</th><th scope="col">Membro</th><th scope="col">Responsável</th><th scope="col">Ação</th></tr></thead>
          <tbody>
          @forelse($history as $event)
            <tr>
              <td>{{ \Illuminate\Support\Carbon::parse($event->created_at)->format('d/m/Y H:i') }}</td>
              <td>{{ $event->member_name ?? 'Conta removida' }}</td>
              <td>{{ $event->actor_name ?? 'Conta removida' }}</td>
              <td>{{ ['requested' => 'Solicitou', 'approved' => 'Aprovou solicitação', 'rejected' => 'Recusou solicitação', 'approved_direct' => 'Ativou diretamente', 'revoked' => 'Revogou'][$event->action] ?? $event->action }}</td>
            </tr>
          @empty
            <tr><td colspan="4" class="text-muted">Nenhuma ação registrada.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
