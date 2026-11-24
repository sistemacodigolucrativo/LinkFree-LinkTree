# H2 — reteste independente de rollback e recuperação

24/11/2026. **Resultado: H2 permanece PARCIAL.** A correção candidata reproduziu com sucesso a restauração do caso P1 em fixture com Laravel real e passou nos testes de falhas. O candidato não foi integrado nem publicado: a versão operacional registrada continua `6e55da55e57203da2606a4b1ca1c83865c0f760d`. Além disso, o backup anterior não comprova sozinho uma imagem integral da instalação. Não houve deploy, alteração de aplicação/banco de produção ou início da H3.

## Escopo e identidade

PR [#19](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/pull/19), head `46b33169f06ca4e1902b8d5e5b83519b1bd5050f`; branch ainda aberta/draft, não integrada. Main inicial `01a0a7e1689b08cdc8912d7ed6ba440f88ceafd4`. Comparação com main: script de deploy, teste, dois workflows e documento; sem mudanças em `linkstack/` ou migrations. Consulta GitHub confirmou os três workflows de PR concluídos com sucesso. Os resultados anteriores de [H2](H2-RECUPERACAO-STAGING-20261124.md) são preservados.

## Reteste independente do candidato — TC-02-D

Foi extraído do SHA candidato o trecho real `verify_published_files`/`rollback` e seu trap, sem executar integralmente o script que tem caminhos fixos de produção. Fixture com dev, backup e cópia de staging, rede `unshare -mn` apenas loopback, sem rota externa; `/srv/linkfree`, `/var/backups/linkfree-deploy`, `/home/ubuntu` e `/run` ocultados em namespace de montagem privado. SQLite e contas exclusivamente sintéticos. Foi injetado `exit 42`; o teste executou o `rsync` real e o `runuser -u www-data -- php artisan` real dentro desse namespace.

Antes do caso normal foram executados `php artisan config:cache`, `route:cache` e `view:cache` no staging. O rollback limpou efetivamente os três caches pelo Laravel, verificando remoção dos arquivos `config.php`, `routes-v7.php` e das views compiladas. Não se usou shim para a limpeza normal. Resultado:

| Cenário | Resultado |
|---|---|
| Mesmo tamanho e mtime, conteúdo diferente | PASS: conteúdo anterior restaurado nos dois destinos, arquivos novos removidos, status original 42 |
| Metadados dos arquivos | PASS: modo 0640, UID/GID 33 e mtime anterior restaurados nos dois destinos |
| Banco | PASS: escrita sintética posterior ao backup preservada e `integrity_check=ok` |
| Backup ausente | PASS: status 74 e `ROLLBACK INCOMPLETO` |
| rsync retorna erro | PASS: status 74 e falha explícita |
| rsync retorna sucesso sem copiar | PASS: comparação de bytes detectou a divergência e retornou 74 |
| Remoção falha | PASS: status 74 |
| Limpeza de cache falha | PASS: falha de `route:clear` injetada, status 74; demais limpezas reais executadas |
| Caminho inseguro `../` | PASS: recusado e status 74 |

Nos cenários negativos, funções shell substituíram apenas a operação que deveria falhar; demais comandos e caches executaram no staging. Os sete cenários estão em [resultados sanitizados](evidencias-h2-reteste/resultados.json). Não se testou o script integral, restauração de ACLs/xattrs, todos os casos de concorrência ou rollback real na VPS; `verify_published_files` não foi exercitado como fluxo integral de publicação neste reteste. Os testes de CI do candidato cobrem cenários adicionais, mas não substituem a limitação desta execução. A correção P1 está comprovada para o caso específico no candidato; **o mecanismo antigo permanece instalado enquanto a PR #19 não for integrada e publicada por autorização posterior**.

## TC-02-B — complementação da recuperação

O manifesto privado anterior foi revalidado: 130/130 hashes corretos. Além dos 36 arquivos alterados e 7.465 de `vendor` já conferidos em H2, comparamos por blob Git **9.101 arquivos versionados não alterados pelo deploy** do SHA anterior `3f20bf95e75fb93cb6abfef3aaa388c9798b664d` com a instalação atual: zero ausentes ou divergentes. Esse total inclui `vendor`; não somar as contagens. `.env` e `config/advanced-config.php` do backup têm hashes iguais aos atuais, verificação local sem revelar conteúdo.

Uma nova cópia privada restaurou SQLite, storage e arquivos previstos no backup, código anterior do Git e, diferentemente do ensaio anterior, também `config/advanced-config.php` original do backup. A chave foi utilizada apenas na cópia local; `.env` foi reconstruído com conexões isoladas, SMTP local inoperante e sem credenciais externas. Com rede e diretórios reais ocultados antes do boot e usuário `www-data` sem privilégios, Laravel confirmou SQLite da cópia e login respondeu HTTP 200. Nenhum conteúdo de cliente foi publicado; cópia restaurada e chave foram removidas após o teste.

**Limite:** o backup é seletivo e não contém prova independente de todo arquivo não versionado, runtime, serviços e configuração histórica completa. Comparação com instalação atual corrobora arquivos versionados, sem provar que todo o estado histórico era idêntico. Boot anônimo não verifica todos os fluxos nem integrações. TC-02-B continua PARCIAL até existir plano de backup integral e ensaio de recuperação adequado; não substituir estado do banco de produção.

## Staging e decisões

O staging sintético anterior foi revalidado: banco íntegro, zero violações FK, quatro contas `@example.test`, chave diferente da chave do backup e login HTTP 200 em namespace sem rota externa e com produção/sockets ocultados. Não ficou servidor ativo; staging e launcher permanecem efêmeros em `/tmp`, exigem nova verificação de identidade e isolamento antes de uso. Não foram executados testes da H3.

TC-02-C histórico permanece bloqueado pelo log bruto M13 ausente nos locais consultados. H2 **não pode ser encerrada como recuperação integral** enquanto o mecanismo corrigido não for tratado pelo fluxo autorizado e a estratégia de recuperação/backup integral não tiver evidência suficiente. Não mesclar a PR #19, fazer deploy ou iniciar H3 por este relatório. A matriz histórica permanece intacta.
