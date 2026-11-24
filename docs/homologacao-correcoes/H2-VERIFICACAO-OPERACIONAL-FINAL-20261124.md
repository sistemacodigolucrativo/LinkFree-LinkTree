# H2 — verificação operacional final

24/11/2026 UTC. **H2 concluída no escopo de recuperação local e preparação de staging. H3 permanece bloqueada até revisão deste relatório pelo ChatGPT.** Não se trata de homologação funcional integral nem aceite do sistema.

## Dispatcher e versão

A PR [#19](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/pull/19) foi integrada em `b0309e1cb267169f599aafa00f92066fb8d79a39`; `origin/main` verificado nesta execução estava em `521d8cc6b17e5b5e2f88aa9ea8b0655066408a2d`, descendente do merge. Clone operacional em `/home/ubuntu/src-Projects/LinkFree-LinkTree` limpo, branch `main`, origin oficial. O `fetch` atualizou apenas referências Git; a branch local continuou no SHA operacional anterior, 15 commits atrás do origin, sem reset ou merge local.

O `/root/atualizar-linkstack.sh` instalado foi comparado byte a byte com o bootstrap gerado pelo instalador versionado: idêntico. Sua lógica busca `origin/main` em cada execução, verifica branch, estado e origin, e extrai pelo SHA remoto o arquivo `scripts/atualizar-linkstack.sh`. Foi extraído **somente para cópia privada**, sem executar `--check` ou deploy; `bash -n` passou e SHA-256 `7c5be89b0c3a0d0ac831585efdcda343dc9e8392da73e4e0c34c0aff243a0012` coincide com o candidato testado `46b33169f06ca4e1902b8d5e5b83519b1bd5050f`. Assim, com a main observada e as mesmas condições de integridade/origin, o dispatcher selecionará o script corrigido na próxima execução. Ele busca a **main futura**, portanto novas alterações remotas podem mudar o script selecionado; esta verificação não congela versões futuras.

A aplicação em `/srv/linkfree` permanece registrada em `6e55da55e57203da2606a4b1ca1c83865c0f760d`; `git diff` desse SHA até a main observada em `linkstack/` vazio. Nenhum deploy, migração, escrita no banco de produção ou alteração do SHA foi realizado. nginx e php8.3-fpm ativos, Home HTTPS 200. A presença do backup quiescente, snapshot e manifesto privado foi confirmada, diretório root:root 0700; backup e restauração não foram repetidos.

## CI e evidências reaproveitadas

Os três workflows de push do merge `b0309e1` estavam concluídos com sucesso: [deploy unificado e homologação](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45483113233) (dois jobs), [schema M13 isolado](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45483113364) e [consentimento e storage](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45483113297). Resultados anteriores de [ensaio integral](H2-ENSAIO-INTEGRAL-E-BACKUP-20261124.md), [reteste independente](H2-RETESTE-INDEPENDENTE-ROLLBACK-20261124.md) e [backup quiescente e restauração](H2-BACKUP-QUIESCENTE-RESTAURACAO-20261124.md) foram reaproveitados; não se repetiu a bateria aprovada.

## Staging sintético

`/tmp/linkfree-h2-private/synthetic` permanece sob diretório 0700, UID 61050, banco SQLite próprio e configuração local de sessão/cache e SMTP inoperante `127.0.0.1:9`. Verificação SQLite somente de leitura: `quick_check=ok`, zero violações FK, quatro contas com emails `example.test`; chave de teste diferente da chave da aplicação. Essas verificações e a proveniência do seed sintético sustentam a identidade do ambiente, não são auditoria de cada byte do banco.

O launcher existente foi inspecionado e iniciado para confirmar isolamento: processo UID 61050, namespaces de rede e montagem distintos do host; diretórios da aplicação real, backups, home operacional e `/run` ocultados; nenhuma rota IPv4 externa, tentativa de conexão externa falhou, e o host não alcançou a porta 18771. Login HTTP 200 dentro do namespace. O processo foi encerrado; não há listener da instância no host. Como o staging e launcher ficam em `/tmp`, são efêmeros: conferir novamente identidade do código, isolamento e banco antes de qualquer uso posterior. Nenhum teste funcional residual da H3 foi executado.

## Parecer e limites

H2 está concluída **no escopo de recuperação local ensaiada, rollback candidato validado, backup quiescente e verificação operacional do dispatcher**. A correção está na main e será buscada dinamicamente; não se afirma que um deploy tenha sido executado ou que todo possível erro de rollback tenha sido eliminado. [Evidência sanitizada](evidencias-h2-final/resultados.json).

H3 permanece pendente de revisão deste relatório pelo ChatGPT. TC-02-C histórico permanece bloqueado por ausência do log bruto M13; a matriz histórica da PR #15 continua preservada. Recuperação completa de outro host, OAuth/SMTP reais, dispositivos físicos, acessibilidade humana e aceite formal permanecem fora da prova. A conclusão da H2 não aprova os 43 casos nem encerra a homologação final.
