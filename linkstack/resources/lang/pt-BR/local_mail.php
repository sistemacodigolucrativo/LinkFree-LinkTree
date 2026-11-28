<?php
return [
    'enable' => 'Usar envio local do PHP',
    'description' => 'Requer um serviço de e-mail local e um remetente do seu domínio. A aceitação local não confirma a entrega.',
    'domain' => 'Domínio do remetente',
    'hourly_limit' => 'Limite de mensagens por hora',
    'envelope' => 'Remetente do envelope',
    'envelope_from' => 'Usar o remetente configurado (-f)',
    'envelope_provider' => 'Deixar a hospedagem escolher (verifique o alinhamento)',
    'domain_error' => 'O endereço do remetente deve pertencer ao domínio configurado.',
    'accepted' => 'A mensagem foi aceita localmente. Confira a caixa de entrada e o spam; a entrega não é garantida.',
    'log_only' => 'A mensagem foi apenas registrada em log. Nenhum e-mail foi enviado.',
];
