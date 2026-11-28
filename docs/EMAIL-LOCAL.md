# Envio de e-mail local

O transporte opcional usa o serviço de correio da própria hospedagem. A seleção dos transportes existentes continua disponível. A aceitação local significa apenas que a mensagem entrou no serviço de correio: não comprova recebimento. Não habilite este modo antes de configurar e testar o serviço da hospedagem.

No painel administrativo, em configurações de e-mail, selecione envio local, defina o domínio e um remetente válido do mesmo domínio, ajuste o limite horário e salve. O botão de teste já existente envia para o e-mail do administrador. Consulte a caixa de entrada e o spam, assim como os registros do MTA e os cabeçalhos de autenticação da mensagem recebida. Essa função não exige nova rota pública.

O valor `LOCAL_MAIL_ENVELOPE_MODE=from` solicita ao PHP que passe o remetente do envelope. Quando a hospedagem bloquear este parâmetro, selecione `provider` explicitamente no painel e confirme como o provedor define o envelope. Não há nova tentativa automática, pois o resultado de uma tentativa pode ser ambíguo e provocar mensagens duplicadas. `MAIL_REPLY_TO_ADDRESS` é opcional; use um endereço válido. O provedor pode impor limites menores que o limite configurado na aplicação. A aplicação aceita um destinatário por mensagem neste transporte e limita o tamanho total a 10 MiB.

## Hospedagem cPanel

Confirme com a hospedagem que PHP `mail()` está habilitado, que existe um MTA configurado e qual é o limite de envio por hora. No cPanel, use **Email Deliverability** para verificar SPF, DKIM e a configuração de DNS indicada pelo provedor. Alguns provedores não permitem definir o remetente do envelope pelo PHP. Consulte também suas regras para domínio e remetente. A disponibilidade e os limites dependem da hospedagem; o projeto não instala um MTA nela.

## VPS Ubuntu

O script opcional `scripts/setup-local-mail.sh` se destina a um servidor Ubuntu sem Postfix ou OpenDKIM preexistentes. Ele recusa instalações detectadas para evitar sobrescrever configuração de outra aplicação. Primeiro revise o plano:

```bash
bash scripts/setup-local-mail.sh --plan exemplo.com mail.exemplo.com IP_PUBLICO_IPV4 dmarc@exemplo.com
```

Após conferir o domínio, o IP público e o e-mail de relatórios, execute com `--install` como root. O script instala e configura Postfix para escutar somente em loopback, OpenDKIM e uma chave privada de 2048 bits. Ele informa registros DNS e o PTR necessário. Guarde o backup e acompanhe o servidor durante a operação. Se a instalação falhar, pode haver pacotes ou arquivos parcialmente configurados; inspecione antes de tentar de novo. O script não modifica o mailer selecionado na aplicação nem seu hostname de sistema.

Crie o registro A para o hostname, ajuste SPF e DKIM no DNS e solicite ao provedor da VPS o PTR/rDNS. O domínio deve ter somente um registro SPF; incorpore o IP à política existente, sem criar um segundo. Comece o DMARC com `p=none` e avalie relatórios antes de adotar `quarantine` ou `reject`. Os relatórios podem conter dados sensíveis; controle o endereço de destino e, para domínio externo, verifique a autorização DMARC necessária.

O provedor da VPS precisa permitir conexões de saída na porta TCP 25; em alguns serviços, inclusive AWS EC2, essa porta pode estar restringida por padrão. Sem liberação, o envio direto pode permanecer em fila ou falhar. DNS, PTR, reputação de IP e políticas dos destinatários também afetam entrega. Não é possível garantir recebimento em toda instalação sem verificar essas condições.

## Diagnóstico

Após salvar, envie pelo botão de teste e confira o recebimento real. Cabeçalhos `Authentication-Results` podem indicar SPF, DKIM e DMARC. Consulte a fila e logs do serviço local se o servidor aceitou mas a mensagem não chegou. Evite incluir mensagens, credenciais, chaves privadas ou endereços completos em relatos públicos. O registro da aplicação mantém o destinatário criptografado e o resultado da submissão local; consulte a política de retenção de logs da instalação.
