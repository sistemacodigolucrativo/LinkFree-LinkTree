# Diagnóstico: falha no envio de e-mail pelo SMTP integrado

- Investigação: 27/11/2026, aproximadamente 21:43–21:45 UTC.
- Repositório: `main` no commit `bb9a686a1663a8d4e5840ad8a0c25276e10ab16a`.
- Produção: `/srv/linkfree` não é um checkout Git; o commit implantado não pôde ser comprovado. Os arquivos `config/mail.php` e `app/Http/Controllers/AdminController.php` da VPS são byte a byte idênticos aos respectivos arquivos em `linkstack/` nesse commit.
- Escopo: inspeção e testes de resolução DNS; nenhum envio de e-mail, autenticação SMTP, alteração de produção ou reinício de serviço.

## Sintoma e evidências

O administrador relatou “Failed to send test email.” após selecionar o servidor integrado. O access log do Nginx registra quatro requisições `POST /send-test-email` em 27/11/2026 às 21:33:01, 21:34:11, 21:34:24 e 21:34:31 UTC, todas com HTTP 302. Esse status é compatível com o redirecionamento do controlador, mas não distingue sucesso de falha e não identifica por si só a tentativa relatada.

O `.env` seleciona `MAIL_MAILER=built-in` e não havia cache de configuração Laravel em `bootstrap/cache/config.php` na inspeção. O transporte integrado em `config/mail.php` usa SMTP na porta 587, com STARTTLS, e host `mail.llc.ovh`. A escolha na interface é salva pelo controlador como `built-in`.

Às 21:44 UTC, a resolução de `mail.llc.ovh` na VPS retornou `NXDOMAIN` para consultas A e AAAA. Uma consulta A independente ao resolvedor público 1.1.1.1 também retornou `NXDOMAIN`. Na mesma VPS, `linkfree.website` e `github.com` resolveram normalmente. Isso comprova um impedimento atual de resolução do host configurado, não uma falha geral demonstrada do DNS da VPS. Sem endereço não foi possível testar TCP, STARTTLS, certificado ou autenticação; não se pode concluir se funcionariam após a correção do DNS.

Em `AdminController::SendTestMail`, a exceção `Exception` é capturada e substituída pela mensagem genérica, sem `report` ou registro explícito. O arquivo `storage/logs/laravel.log` contém registros até 23/11/2026 20:02:12, anteriores às requisições; as buscas nos logs atuais de Nginx e PHP-FPM não encontraram referências a SMTP ou à rota de teste. Os horários do Laravel são apresentados como registrados, sem presumir o fuso da aplicação. A ausência de registro não prova que nenhuma exceção tenha ocorrido.

## Diagnóstico e confiança

**Confirmado, confiança alta:** o host atualmente configurado para o SMTP integrado não resolve; uma tentativa que utilize essa configuração e precise resolvê-lo não poderá estabelecer conexão SMTP. O tratamento genérico oculta a exceção original na interface e não a registra nesse caminho.

**Não comprovado:** a exceção exata das tentativas anteriores e o estado do DNS no instante delas. Portanto, o `NXDOMAIN` atual é uma causa técnica fortemente consistente com o sintoma, mas a correlação histórica permanece uma hipótese. Autenticação, TLS, disponibilidade do servidor e aceitação de remetente não foram verificados.

## Recomendações

1. Confirmar com o fornecedor do SMTP integrado se o serviço e o nome de host ainda são válidos. Obter endpoint e credenciais atuais por canal confiável; avaliar um provedor SMTP alternativo se o serviço estiver descontinuado. Qualquer mudança exige processo próprio de aprovação e validação.
2. Em alteração futura, registrar exceções de forma segura e sanitizada, sem expor credenciais ou dados pessoais, preservando uma mensagem adequada ao administrador. Cobrir o caminho de erro com testes.
3. Há credenciais SMTP incorporadas ao arquivo de configuração analisado. Avaliar sua exposição e validade com o responsável pelo serviço, e planejar sua substituição ou revogação e armazenamento seguro. Não publicar os valores em registros ou relatórios. Não foram feitas alterações de credenciais nesta investigação.
4. Após uma correção autorizada, verificar DNS, conexão TCP, STARTTLS e certificado; somente então realizar envio de teste com autorização, inspecionando logs sanitizados e entrega.

## Status e pendências

Diagnóstico parcial: impedimento DNS atual comprovado; causa exata da falha histórica e funcionamento após eventual correção continuam pendentes. Não houve implementação, deploy, alteração de configuração ou novo envio de e-mail. Nginx e PHP-FPM estavam ativos durante a inspeção.
