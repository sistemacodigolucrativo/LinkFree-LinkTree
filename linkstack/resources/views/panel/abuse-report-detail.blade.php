@extends('layouts.sidebar')

@section('content')
<div class="container-fluid content-inner py-0">
  <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Denúncia #{{ $record->id }}</h1>
    <a class="btn btn-outline-secondary" href="{{ route('admin.abuse-reports.index') }}">Voltar à central</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger" role="alert">
      @foreach($errors->all() as $error)<p class="mb-1">{{ $error }}</p>@endforeach
    </div>
  @endif

  <div class="row g-3">
    <div class="col-12 col-xl-7">
      <section class="card rounded-4 h-100">
        <div class="card-body p-3 p-md-4">
          <h2 class="h5">Dados da denúncia</h2>
          <dl>
            <dt>Situação atual</dt>
            <dd><span class="badge {{ $record->status === 'new' ? 'bg-danger' : ($record->status === 'in_review' ? 'bg-warning text-dark' : ($record->status === 'resolved' ? 'bg-success' : 'bg-secondary')) }}">{{ $statuses[$record->status] ?? $record->status }}</span></dd>
            <dt>Recebida em</dt>
            <dd>{{ \Illuminate\Support\Carbon::parse($record->created_at)->format('d/m/Y H:i') }}</dd>
            <dt>Perfil denunciado</dt>
            <dd>{{ $record->current_target_name ?? ($record->reported_handle ?: 'Sem perfil vinculado') }}
              @if($record->reported_user_id && $record->current_target_name)
                <a class="ms-1" href="{{ route('showUser', ['id' => $record->reported_user_id]) }}">Ver membro</a>
              @endif
            </dd>
            <dt>URL informada</dt>
            <dd style="overflow-wrap:anywhere">{{ $record->reported_url }}</dd>
            <dt>Motivo</dt>
            <dd>{{ $categories[$record->category] ?? $record->category }}</dd>
            <dt>Comentários enviados</dt>
            <dd class="border rounded-3 p-3" style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $record->message ?: 'Nenhum comentário enviado.' }}</dd>
            <dt>Última análise</dt>
            <dd>{{ $record->reviewer_name ?? 'Sem responsável registrado' }}
            @if($record->reviewed_at)
              · {{ \Illuminate\Support\Carbon::parse($record->reviewed_at)->format('d/m/Y H:i') }}
            @endif
            </dd>
          </dl>
        </div>
      </section>
    </div>
    <div class="col-12 col-xl-5">
      <section class="card rounded-4 mb-3">
        <div class="card-body p-3 p-md-4">
          <h2 class="h5">Registrar decisão</h2>
          <p class="small text-muted">Ação manual de moderação. Escolher uma situação não remove nem bloqueia o membro.</p>
          <form method="POST" action="{{ route('admin.abuse-reports.update', ['id' => $record->id]) }}">
            @csrf
            <label class="form-label" for="abuse-report-status">Situação</label>
            <select id="abuse-report-status" name="status" class="form-select mb-3" required>
              @foreach($statuses as $key => $label)
                <option value="{{ $key }}" @selected(old('status', $record->status) === $key)>{{ $label }}</option>
              @endforeach
            </select>
            <label class="form-label" for="abuse-report-note">Observação administrativa</label>
            <textarea id="abuse-report-note" name="note" class="form-control mb-2"
              rows="5" maxlength="2000" placeholder="Registre os fatos analisados e a justificativa. Obrigatório para resolver ou arquivar.">{{ old('note') }}</textarea>
            <button class="btn btn-primary" type="submit">Salvar decisão</button>
          </form>
        </div>
      </section>
      <section class="card rounded-4">
        <div class="card-body p-3 p-md-4">
          <h2 class="h5">Histórico da denúncia</h2>
          <ol class="list-group list-group-flush">
            @forelse($events as $event)
              <li class="list-group-item px-0">
                <strong>{{ $event->action === 'created' ? 'Recebida' : ($event->action === 'noted' ? 'Observação registrada' : 'Situação alterada') }}</strong>
                <small class="d-block text-muted">
                  {{ \Illuminate\Support\Carbon::parse($event->created_at)->format('d/m/Y H:i') }}
                  · {{ $event->actor_name ?? ($event->action === 'created' ? 'Visitante / conta não identificada' : 'Responsável não disponível') }}
                </small>
                @if($event->from_status && $event->from_status !== $event->to_status)
                  <small class="d-block">{{ $statuses[$event->from_status] ?? $event->from_status }} → {{ $statuses[$event->to_status] ?? $event->to_status }}</small>
                @endif
                @if($event->note)
                  <p class="small mb-0 mt-1" style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $event->note }}</p>
                @endif
              </li>
            @empty
              <li class="list-group-item px-0 text-muted">Nenhum evento registrado.</li>
            @endforelse
          </ol>
          <nav class="mt-3" aria-label="Páginas do histórico de denúncias">{{ $events->links() }}</nav>
        </div>
      </section>
    </div>
  </div>
</div>
@endsection
