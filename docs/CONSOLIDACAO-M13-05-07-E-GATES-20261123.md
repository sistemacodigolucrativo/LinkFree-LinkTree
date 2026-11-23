# LinkFree — Consolidação de M13 (pendências 05–07)

Data-base: 23/11/2026. **Estado atual: integrado à main e publicado na VPS; homologação funcional 01–10 pendente.**

A fonte operacional atual é o [registro de produção M13](STATUS-PRODUCAO-M13-20261123.md). As referências às branches e PRs abaixo são histórico de implementação, não mudanças ainda aguardando merge.

## Integração confirmada

- Repositório: `sistemacodigolucrativo/LinkFree-LinkTree`.
- `main` publicada: `3f20bf95e75fb93cb6abfef3aaa388c9798b664d`.
- PR #11: indicações; integrada ao histórico da main.
- PR #12: selo verificado; branch empilhada, posteriormente **encerrada sem merge individual**, pois o conteúdo foi incluído na PR de consolidação.
- PR #13: denúncias; branch empilhada, posteriormente **encerrada sem merge individual**, pois o conteúdo foi incluído na PR de consolidação.
- PR #14: consolidação completa 05–07 e deploy protegido; **integrada à main** no SHA acima.
- Publicação via `bash /root/atualizar-linkstack.sh --deploy-m13` confirmada pelo log fornecido pelo operador; 23 arquivos de aplicação publicados.

## Evidências técnicas disponíveis

| Item | Estado comprovado | Limitação |
|---|---|---|
| 01 — Schema/consentimento | Migração de consentimento presente; SQLite íntegro; novas migrações 000002–000004 concluídas | Regressão funcional da base pendente |
| 02 — Backup/deploy | Backup SQLite antes de migrar e publicação com SHA correto | Exercício de restauração ainda pendente |
| 03 — Permissões/persistência | Inspeção read-only do PHP-FPM, storage e banco aprovada | Testes de gravação real pendentes |
| 04 — Cadastro/consentimento | Código e formulário público publicados; CSRF e checkbox visíveis | Cadastro real e OAuth externo pendentes |
| 05 — Indicações | Código + tabelas `referral_codes` e `referrals` publicados | Exercícios com convite e históricos pendentes |
| 06 — Selo verificado | Código, colunas em `users` e tabelas de solicitação/auditoria publicados | Fluxo admin/membro e selo legado pendentes |
| 07 — Denúncias | Código e tabelas `abuse_reports` e `abuse_report_events` publicados | Recebimento/moderação reais pendentes |
| 08–10 | Homologação técnica pública/read-only aprovada | Bateria funcional, UX e aceite final não executados |

A homologação automática após deploy apontou **0 falhas e 0 avisos**. Os workflows estáticos do GitHub para o commit de integração também foram aprovados. Esses resultados **não demonstram** funcionamento dos fluxos autenticados.

## Bloqueio histórico de migrations: resolvido na publicação M13

Antes da PR #14, `scripts/atualizar-linkstack.sh` admitia automaticamente apenas a migração 000001; as migrations M13 eram bloqueadas. A PR #14 incluiu modo explícito `--deploy-m13`, allowlist nominal 000002–000004, backup SQLite e verificações pós-migração.

O operador executou preflight `--check`, depois `--deploy-m13`. O relatório informou:

- Migrações 000002, 000003 e 000004: `DONE`.
- Código publicado: 23 arquivos.
- SHA: `3f20bf95e75f`.
- Backup SQLite: `/var/backups/linkfree-deploy/20261123-231449-jORt0u/sqlite-before-deploy.sqlite`.
- Smoke automatizado read-only: aprovado com 0 falhas e 0 revisões manuais.

**Não há necessidade de repetir a migração M13.** Mantenha o backup protegido. Nova alteração de schema deverá ser planejada separadamente.

## Riscos e trabalho ainda pendentes

1. **Selo legado:** VIP não concede selo automaticamente; identificar e revisar contas que usavam o mecanismo anterior.
2. **LGPD/denúncias:** política de retenção, acesso, eventuais pedidos de titulares e tratamento de texto livre.
3. **Cadastro e OAuth:** cadastro real, sessão de convite/consentimento e login de contas existentes.
4. **Concorrência e SQLite:** transações, integridade após exclusões, idempotência e acesso simultâneo.
5. **UX/segurança:** painel admin/membro autenticado, badges, exibição pública, temas, dispositivos e acessibilidade.
6. **Recuperação:** teste de restauração em ambiente isolado, sem substituição do banco de produção.

## Próxima fase

Executar a [matriz de homologação conjunta 01–10](MATRIZ-HOMOLOGACAO-01-10-20261123.md) no momento definido pelo responsável. Registrar falhas e evidências; distinguir verificações técnicas já aprovadas de cenários funcionais ainda não executados. Não declarar todas as pendências encerradas apenas porque o código está publicado.

**Atualização deste arquivo é documental; não representa novo deploy, merge de código ou execução de testes funcionais.**
