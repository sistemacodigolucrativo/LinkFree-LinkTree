@extends('layouts.sidebar')

@section('content')
@php
    // A complete phpinfo() response includes environment, request and server values.
    // Only explicitly approved, non-sensitive diagnostic fields are shown here.
    $phpDiagnostics = [
        'Versão do PHP' => PHP_VERSION,
        'Interface PHP (SAPI)' => PHP_SAPI,
        'Fuso horário' => date_default_timezone_get(),
        'Limite de memória' => ini_get('memory_limit'),
        'Tamanho máximo de upload' => ini_get('upload_max_filesize'),
        'Tamanho máximo de POST' => ini_get('post_max_size'),
        'Tempo máximo de execução' => ini_get('max_execution_time'),
    ];
    $phpExtensions = get_loaded_extensions();
    sort($phpExtensions);
@endphp
<div class="container-fluid content-inner py-4" id="php-diagnostics">
    <h1>Informações sobre o PHP</h1>
    <p>Diagnóstico limitado a informações não sensíveis. Variáveis de ambiente, credenciais, cabeçalhos, cookies e detalhes completos do servidor não são exibidos nem exportados.</p>
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a class="btn btn-secondary" href="{{ route('showConfig') }}">Voltar às configurações</a>
        <button class="btn btn-primary" type="button" id="download-php-diagnostics">Baixar diagnóstico</button>
    </div>
    <div class="card"><div class="card-body">
        <dl class="row">
            @foreach($phpDiagnostics as $label => $value)
                <dt class="col-12 col-md-4">{{ $label }}</dt>
                <dd class="col-12 col-md-8">{{ $value }}</dd>
            @endforeach
            <dt class="col-12 col-md-4">Extensões carregadas</dt>
            <dd class="col-12 col-md-8">{{ implode(', ', $phpExtensions) }}</dd>
        </dl>
    </div></div>
</div>
<script>
document.getElementById('download-php-diagnostics').addEventListener('click', function () {
    const panel = document.getElementById('php-diagnostics');
    const rows = Array.from(panel.querySelectorAll('dt')).map(function (term) {
        return term.textContent.trim() + ': ' + (term.nextElementSibling?.textContent.trim() || '');
    });
    const blob = new Blob([rows.join('\n') + '\n'], {type: 'text/plain;charset=utf-8'});
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'php-diagnostics.txt';
    link.click();
    URL.revokeObjectURL(url);
});
</script>
@endsection
