@extends('layouts.sidebar')
@section('content')
<div class="container py-4">
    <h1>{{ $operation === 'backup' ? 'Backup da instalação' : 'Atualização da instalação' }}</h1>
    <p>As operações de manutenção do LinkFree devem seguir o procedimento controlado, com autorização, backup e plano de recuperação. A execução automática pelo navegador legado está desabilitada por segurança.</p>
    <a class="btn btn-primary" href="{{ route('showBackups') }}">Consultar backups existentes</a>
    <a class="btn btn-secondary" href="{{ route('panelIndex') }}">Voltar ao painel</a>
</div>
@endsection
