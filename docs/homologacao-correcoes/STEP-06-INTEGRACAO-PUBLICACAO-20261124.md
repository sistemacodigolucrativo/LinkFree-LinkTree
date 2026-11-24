# STEP 06 — integração, publicação e homologação final

> **Atualização operacional 24/11/2026 19:07 UTC:** deploy normal concluído na VPS, SHA `6e55da55e57203da2606a4b1ca1c83865c0f760d`; preflight e homologação read-only aprovados. Recuperação integral e aceite funcional continuam pendentes. Ver [relatório operacional](STEP-06-RELATORIO-OPERACIONAL-VPS-20261124.md). O texto posterior preserva o estado histórico anterior ao deploy.

Data: 24/11/2026. Projeto [LinkFree-LinkTree](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree).
## Estado vigente
**Integração concluída. Publicação e homologação em produção pendentes.** O operador autorizou o merge e a postergação da verificação em dispositivos físicos e inspeção humana até o aceite definitivo. Não autorizou separadamente o deploy. Nenhuma operação SSH, backup real, mudança de banco, migração ou serviço foi executada nesta sessão.
## Identidade da integração
| Campo | Evidência |
|---|---|
| PR | [#16 integrada](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/pull/16) |
| Data UTC | 2026-11-24T18:10:42Z |
| Método | merge commit, preserva 32 commits da branch |
| Main anterior | 342fce9b15cdc7a55e9bcc6f8a00278dc712d73d |
| Head pré-merge | 6afb27d9e47884c6c2741b08806d5624856c8868 |
| Merge de aplicação | [1b71b747e2c2b803eaffc6c63b64b90c408e8f4e](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/commit/1b71b747e2c2b803eaffc6c63b64b90c408e8f4e) |
| Árvore aprovada e integrada | 0e5f3ad3a17cc0ec0a20bf056e7baaa35bd458ad |
| PR #15 | draft, 361c4cb4d4da9db98039d58e10834ad8541f40a7, preservada |
O commit de documentação que contém este registro é descendente do merge; não é nova versão funcional. Registrar o SHA efetivamente publicado apenas após evidência do operador.
## Evidências
[CI pré-merge](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45453865896): HTTP 20/20, interações 6/6, 12 páginas sem violações detectadas, perfil 24/24, formulários 6/6, SQLite/MariaDB 4/4 cada. PHP, layout e regressão também verdes no head aprovado. [PHP pós-merge](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45456662353/job/138667833720) e [layout pós-merge](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45456662150/job/138667834252) aprovados. A árvore de integração corresponde exatamente à árvore testada; não repetir por conveniência. Os demais workflows possuem filtros de branch e não foram executados novamente na main. [Registro na PR](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/pull/16#issuecomment-7140278238).
## Atividades (numeração original)
| Nº | Atividade | Estado |
|---|---|---|
| 31 | Autorização de merge | Concluída |
| 32 | Integrar PR #16 | Concluída |
| 33 | Verificar main e CI | Concluída |
| 34 | Preflight e backup da VPS | Pendente |
| 35 | Autorizar deploy | Pendente |
| 36 | Publicar na VPS | Pendente |
| 37 | Homologação segura | Pendente |
| 38 | Registros finais de produção | Pendente; registro da integração já efetuado |
3/8 atividades integrais concluídas; 4/8 marcos da apresentação simplificada (inclui registro do merge, não o registro final de produção).
## Próxima execução: preflight, sem publicação
Ambiente histórico: clone /home/ubuntu/src-Projects/LinkFree-LinkTree, instalação /srv/linkfree, domínio https://linkfree.website. Último SHA de produção documentado anteriormente 3f20bf95e75fb93cb6abfef3aaa388c9798b664d, não confirmado nesta sessão. Sem acesso SSH pelo conector GitHub. Operador da VPS pode executar o bootstrap existente como root: `bash /root/atualizar-linkstack.sh --check`. Antes, verificar se é o dispatcher aprovado; se necessário reinstalar pelo procedimento versionado de scripts/instalar-deploy-unificado.sh, sem deploy. O --check busca Git e pode instalar rsync; não publica aplicação. Solicitar saída sem credenciais ou dados pessoais. Conferir alvo origin/main e conteúdo de aplicação equivalente ao merge aprovado; parar se houver novos commits funcionais não validados.
- Verificar versão instalada, ancestralidade, clone limpo, diferenças autorizadas, banco efetivo, integridade e ausência de INSTALLING/INSTALLERLOCK; resolver sessões legadas de impersonação de forma controlada.
- Backup consistente do banco (SQLite quick_check; se MariaDB, backup consistente separado), arquivos e storage privado, manifestos de versão; validar recuperação. O script cria backup antes da cópia; confirmar gates e existência de cópia recuperável antes de autorizar publicação.
- Só após preflight e autorização específica de deploy, usar o modo normal --deploy do dispatcher aprovado. Não usar --deploy-m13 nem repetir migrações anteriores; se o diff real revelar migração inesperada, interromper.
- Pós-publicação: --homologar (read-only), versão, integridade, HTTP, logs e aceitação segura. Não executar exclusões reais ou modificar contas em testes de produção. Em falha, seguir o [plano de recuperação](STEP-05-PLANO-REVISAO-DEPLOY-ROLLBACK-20261124.md); rollback de código não equivale a rollback de banco.
## Gates não encerrados
Safari/iOS físico e inspeção humana de contraste/ARIA inconclusivos: nove cenários públicos e seis formulários com itens axe incomplete; não equivalem a reprovação automática nem a WCAG integral. Matriz histórica da PR #15, OAuth/SMTP real e demais lacunas de homologação seguem separados. Não se verificou a VPS nem automação externa ao repositório. Não presumir que a main esteja em produção. Nenhuma nova migração ou dependência no diff da PR #16.
