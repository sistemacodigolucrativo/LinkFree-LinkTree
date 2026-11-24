@extends('layouts.sidebar')

@section('content')
<div class="container-fluid content-inner py-0">
    <div class="card rounded-4">
        <div class="card-body p-3 p-md-4">
            <h1 class="h3 mb-2"><i class="bi bi-people-fill" aria-hidden="true"></i> Administração de indicações</h1>
            <p class="text-muted">Acompanhe os cadastros atribuídos por link de convite. Somente administradores podem acessar esta página.</p>

            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4"><div class="border rounded-3 p-3 h-100"><span class="d-block text-muted small">Total de indicações</span><strong class="fs-3">{{ number_format($total, 0, ',', '.') }}</strong></div></div>
                <div class="col-12 col-md-4"><div class="border rounded-3 p-3 h-100"><span class="d-block text-muted small">Últimos 30 dias</span><strong class="fs-3">{{ number_format($last30Days, 0, ',', '.') }}</strong></div></div>
                <div class="col-12 col-md-4"><div class="border rounded-3 p-3 h-100"><span class="d-block text-muted small">Indicadores ativos</span><strong class="fs-3">{{ number_format($activeInviters, 0, ',', '.') }}</strong></div></div>
            </div>

            <form action="{{ route('admin.referrals') }}" method="GET" class="row g-2 align-items-end mb-3">
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="referrer-id" class="form-label">ID do indicador</label>
                    <input id="referrer-id" name="referrer_id" type="number" min="1" step="1" inputmode="numeric"
                           class="form-control" value="{{ $ownerId }}" placeholder="Todos os indicadores">
                </div>
                <div class="col-auto"><button class="btn btn-primary" type="submit">Filtrar</button></div>
                <div class="col-auto"><a class="btn btn-outline-secondary" href="{{ route('admin.referrals') }}">Limpar</a></div>
            </form>

            <div class="table-responsive" tabindex="0" role="region" aria-label="Histórico administrativo de indicações">
                <table class="table table-striped align-middle">
                    <thead><tr><th scope="col">Data</th><th scope="col">Indicador</th><th scope="col">Convidado</th><th scope="col">Código</th></tr></thead>
                    <tbody>
                    @forelse($history as $entry)
                        <tr>
                            <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($entry->created_at)->format('d/m/Y H:i') }}</td>
                            <td>
                                <strong>{{ $entry->referrer_name ?? 'Conta removida' }}</strong>
                                <span class="small text-muted d-block">ID {{ $entry->referrer_user_id ?? '—' }}</span>
                                @if($entry->referrer_email)<span class="small text-muted">{{ $entry->referrer_email }}</span>@endif
                            </td>
                            <td>
                                <strong>{{ $entry->invited_name ?? 'Conta removida' }}</strong>
                                <span class="small text-muted d-block">ID {{ $entry->invited_user_id }}</span>
                                @if($entry->invited_email)<span class="small text-muted">{{ $entry->invited_email }}</span>@endif
                            </td>
                            <td><code style="color:inherit;font-weight:600">{{ $entry->referral_code }}</code></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">Não há indicações registradas para este filtro.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $history->links() }}
        </div>
    </div>
</div>
@endsection
