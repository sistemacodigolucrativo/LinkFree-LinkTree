@extends('layouts.sidebar')

@section('content')
<div class="container-fluid content-inner py-0">
    <div class="card rounded-4">
        <div class="card-body p-3 p-md-4">
            <h1 class="h3 mb-2"><i class="bi bi-people" aria-hidden="true"></i> Minhas indicações</h1>
            <p class="text-muted">Compartilhe seu convite. Um cadastro novo feito pelo link será atribuído à sua conta. Não há recompensa financeira automática.</p>

            @if(session('success'))
                <div class="alert alert-success" role="status">{{ session('success') }}</div>
            @endif

            @if(!$code)
                <form action="{{ route('studio.referrals.code') }}" method="POST" class="mb-4">
                    @csrf
                    <button type="submit" class="btn btn-primary">Criar meu link de convite</button>
                </form>
            @else
                <label class="form-label fw-semibold" for="my-referral-link">Seu link de convite</label>
                <div class="input-group mb-3">
                    <input id="my-referral-link" class="form-control" type="text" readonly
                           value="{{ route('referrals.visit', ['code' => $code]) }}">
                    <button class="btn btn-outline-primary" type="button"
                            onclick="navigator.clipboard.writeText(document.getElementById('my-referral-link').value).then(() => { this.textContent = 'Copiado!'; }).catch(() => { document.getElementById('my-referral-link').select(); })"
                            aria-label="Copiar link de convite">Copiar</button>
                </div>
            @endif

            <div class="border rounded-3 p-3 mb-3">
                <span class="text-muted d-block small">Cadastros atribuídos</span>
                <strong class="fs-3">{{ number_format($total, 0, ',', '.') }}</strong>
            </div>

            <h2 class="h5">Histórico de convidados</h2>
            <p class="small text-muted">Exibimos apenas o identificador público do convidado, não seu e-mail.</p>
            <div class="table-responsive" tabindex="0" role="region" aria-label="Histórico de indicações">
                <table class="table align-middle">
                    <thead><tr><th scope="col">Convidado</th><th scope="col">Data do cadastro</th></tr></thead>
                    <tbody>
                    @forelse($history as $entry)
                        <tr>
                            <td>{{ $entry->littlelink_name ? '@' . $entry->littlelink_name : 'Conta cadastrada' }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($entry->created_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-muted">Nenhum cadastro atribuído até agora.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $history->links() }}
        </div>
    </div>
</div>
@endsection
