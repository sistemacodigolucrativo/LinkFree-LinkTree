<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Nova denúncia recebida</title>
</head>
<body style="font-family:Arial,sans-serif;line-height:1.5;margin:24px">
  <h1 style="font-size:22px">Nova denúncia recebida</h1>
  <p>Uma denúncia foi registrada na central administrativa.</p>
  <p><strong>Protocolo:</strong> #{{ $formData['report_id'] }}</p>
  <p><strong>URL denunciada:</strong> {{ $formData['url'] }}</p>
  <p><strong>Motivo:</strong> {{ $formData['report-type'] }}</p>
  <p><strong>Descrição:</strong></p>
  <p style="white-space:pre-wrap">{{ $formData['message'] ?: 'Sem comentários adicionais.' }}</p>
  <p><a href="{{ route('admin.abuse-reports.show', ['id' => $formData['report_id']]) }}">Abrir denúncia na central administrativa</a></p>
  <p>O tratamento deve ser feito na área administrativa autenticada. Nenhuma medida contra o perfil é automática.</p>
</body>
</html>
