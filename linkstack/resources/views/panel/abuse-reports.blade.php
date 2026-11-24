@extends('layouts.sidebar')

@section('content')
<div class="container-fluid content-inner py-0">
  <div class="card rounded-4">
    <div class="card-body p-3 p-md-4">
      <h1 class="h3 mb-2"><i class="bi bi-flag" aria-hidden="true"></i> Central de denúncias</h1>
      <p class="text-muted">Receba, investigue e acompanhe denúncias recebidas pelo formulário público. Nenhuma denúncia aciona bloqueio ou exclusão automática.</p>

      <div class="row g-2 g-md-3 mb-4">
        <div class="col-6 col-lg"><div class="border rounded-3 p-3 h-100">
          <span class="small text-muted d-block">Todas</span><strong class="fs-4">{{ $totalCount }}</strong>
        </div></div>
        @foreach($statuses as $key => $label)
        <div class="col-6 col-lg"><div class="border rounded-3 p-3 h-100">
          <span class="small text-muted d-block">{{ $label }}</span><strong class="fs-4">{{ (int) ($counts[$key] ?? 0) }}</strong>
        </div></div>
        @endforeach
      </div>

      <form action="{{ route('admin.abuse-reports.index') }}" method="GET" class="row g-2 align-items-end mb-3">
        <div class="col-12 col-sm-6 col-md-4">
          <label for="abuse-filter-status" class="form-label">Filtrar por situação</label>
          <select name="status" id="abuse-filter-status" class="form-select">
            <option value="">Todas as situações</option>
            @foreach($statuses as $key => $label)
              <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-auto"><button class="btn btn-primary" type="submit">Filtrar</button></div>
        <div class="col-auto"><a class="btn btn-outline-secondary" href="{{ route('admin.abuse-reports.index') }}">Limpar</a></div>
      </form>

      <div class="table-responsive" tabindex="0" role="region" aria-label="Lista de denúncias">
        <table class="table table-hover align-middle">
          <thead>
          <tr>
            <th scope="col">Protocolo</th>
            <th scope="col">Registrada em</th>
            <th scope="col">Perfil denunciado</th>
            <th scope="col">Motivo</th>
            <th scope="col">Situação</th>
            <th scope="col">Ação</th>
          </tr>
          </thead>
          <tbody>
          @forelse($reports as $entry)
          <tr>
            <td>#{{ $entry->id }}</td>
            <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($entry->created_at)->format('d/m/Y H:i') }}</td>
            <td>
              <strong>{{ $entry->current_target_name ?? ($entry->reported_handle ? '@' . $entry->reported_handle : 'URL informada') }}</strong>
              <span class="small text-muted d-block" style="overflow-wrap:anywhere;max-width:20rem">{{ $entry->reported_url }}</span>
            </td>
            <td>{{ $categories[$entry->category] ?? $entry->category }}</td>
            <td><span class="badge rounded-pill {{ $entry->status === 'new' ? 'bg-danger' : ($entry->status === 'in_review' ? 'bg-warning text-dark' : ($entry->status === 'resolved' ? 'bg-success' : 'bg-secondary')) }}">
              {{ $statuses[$entry->status] ?? $entry->status }}
            </span></td>
            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.abuse-reports.show', ['id' => $entry->id]) }}">Analisar</a></td>
          </tr>
          @empty
          <tr><td colspan="6" class="text-muted">Nenhuma denúncia encontrada.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
      {{ $reports->links() }}
    </div>
  </div>
</div>
@endsection
