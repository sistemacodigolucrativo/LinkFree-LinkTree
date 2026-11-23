# LinkFree — Estado de produção M13 e evidências operacionais (23/11/2026)

> **Documento de referência atual após o deploy M13.** Consolida o log de terminal enviado pelo operador da VPS e a revisão remota do GitHub. Não é um laudo de testes funcionais autenticados.

## 1. Versão publicada e origem das evidências

- Repositório: `sistemacodigolucrativo/LinkFree-LinkTree`, branch `main`.
- Commit integrado por [PR #14](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/pull/14): `3f20bf95e75fb93cb6abfef3aaa388c9798b664d`.
- Versão anterior informada na VPS: `54add49d30a8`; versão publicada pelo atualizador: `3f20bf95e75f`.
- Os logs encaminhados pelo operador mostram `PUBLICACAO CONCLUIDA: 3f20bf95e75f` e, no smoke posterior, `OK: SHA registrado pelo deploy: 3f20bf95e75f`.
- O GitHub remoto foi verificado após o merge. A confirmação da execução na VPS **baseia-se na saída do terminal fornecida pelo operador**, não em sessão SSH executada por esta documentação.

## 2. Instalação e preflight

- O instalador atualizou o bootstrap `/root/atualizar-linkstack.sh` e preservou cópia anterior em `/root/linkfree-deploy-scripts-backup/atualizar-linkstack-20261123T231405Z.sh`.
- Executado `bash /root/atualizar-linkstack.sh --check` com a `main` em `3f20bf95e75f`.
- Resultado: 23 arquivos de código identificados, 3 migrações M13 reconhecidas, nenhum conflito reportado, `SIMULACAO CONCLUIDA`; nenhum código da aplicação modificado nessa fase.

## 3. Deploy concluído

Comando executado pelo operador: `bash /root/atualizar-linkstack.sh --deploy-m13`.

- Snapshot SQLite antes da migração: `/var/backups/linkfree-deploy/20261123-231449-jORt0u/sqlite-before-deploy.sqlite`.
- Diretório de backup informado: `/var/backups/linkfree-deploy/20261123-231449-jORt0u`.
- Migrações executadas com `DONE`:
  1. `2026_11_23_000002_create_referral_tables` — 56 ms.
  2. `2026_11_23_000003_create_profile_verification_workflow` — 52 ms.
  3. `2026_11_23_000004_create_abuse_reports_tables` — 40 ms.
- Saída: `Migrations 000002–000004 aplicadas e esquema verificado, antes de publicar código.`.
- 23 arquivos de aplicação publicados; cache de configuração, rotas e views limpo; SHA publicado `3f20bf95e75f`.
- **Não repetir** `--deploy-m13` como rotina após essa publicação: é um modo específico dessa transição, que requer migrações pendentes. Para atualizações futuras, seguir o fluxo normal com `--check` e revisão.

## 4. Homologação técnica automática após deploy

O bootstrap executou automaticamente `scripts/homologar-linkfree.sh --read-only` após a publicação. O log confirmou:

- SHA registrado no deploy correto.
- `SQLite de produção íntegro` e comprovante de consentimento presente, com colunas e migração.
- Termos e Política de Privacidade preenchidos.
- `www-data` com leitura do SQLite e acesso às áreas privadas de personalização e estatísticas.
- Storage privado sem symlinks e com arquivos legíveis.
- Backup SQLite associado ao commit encontrado e íntegro.
- Página inicial, login e registro respondendo HTTPS 200; cadastro exibindo aceite e token CSRF.
- Rota intencionalmente desconhecida respondendo 404; Studio não expôs conteúdo autenticado a visitante anônimo.

**Resultado relatado:** `0 falha(s), 0 verificação(ões) pendentes de revisão manual` — `HOMOLOGAÇÃO TÉCNICA READ-ONLY APROVADA`.

A verificação pública e read-only **não cobre** login real, criação de contas, OAuth externo, telas autenticadas ou validação funcional dos novos módulos. A aprovação técnica não equivale à homologação completa 01–10.

## 5. Estado por pendência

| Nº | Situação pós-deploy | Ainda pendente |
|---|---|---|
| 01 — Estrutura do banco/consentimento | Implantada; preflight, migrations e integridade aprovados tecnicamente | Regressões e cenários funcionais finais |
| 02 — Backup/deploy | Publicação M13 concluída; backup SQLite íntegro identificado | Exercício de restauração e testes finais |
| 03 — Persistência/permissões | Inspeção read-only aprovada | Gravação real e persistência após sessões/reloads |
| 04 — Cadastro e consentimento | Código publicado e proteção do formulário público verificada | Cadastro real e OAuth real, cenários positivos/negativos |
| 05 — Indicações | Código e migração 000002 publicados | Testes end-to-end, convite, histórico e concorrência |
| 06 — Selo verificado | Código e migração 000003 publicados | Aprovação, badge Admin, exibição/ocultação, selo legado |
| 07 — Denúncias | Código e migração 000004 publicados | Recebimento, moderação, histórico, falha SMTP e LGPD |
| 08 — Funcionalidades gerais | Escopo implementado anteriormente | Testes funcionais completos |
| 09 — Interface/compatibilidade | CI de layout aprovado para a versão publicada | Navegadores, dispositivos, temas e acessibilidade real |
| 10 — Produção | Homologação técnica pública/read-only aprovada | Homologação funcional autenticada e aceite final |

A bateria de homologação funcional 01–10 **continua não executada**, conforme a decisão do responsável de realizá-la após concluir as implementações.

## 6. Revisão e decisões ainda abertas

- **Selos antigos:** a autorização atual não decorre do plano VIP. A migração não concede selo automaticamente a perfis que o exibiam pela regra anterior; revisar casos legítimos.
- **Denúncias/LGPD:** definir prazo de retenção, tratamento de dados em textos livres e mecanismos de revisão/exclusão.
- **OAuth e sessões:** confirmar os redirecionamentos reais no cadastro de convidados.
- **Confiabilidade:** testar transações, concorrência e acessos administrativos em ambiente adequado.
- **Recuperação:** preservar e verificar a restauração do backup em ambiente isolado, sem sobrescrever dados atuais.
- **Segurança operacional:** nenhuma instrução de reset/rollback destrutivo do banco está autorizada por este documento.

## 7. Navegação documental

- [Consolidação 05–07 e riscos](CONSOLIDACAO-M13-05-07-E-GATES-20261123.md).
- [Matriz de homologação 01–10](MATRIZ-HOMOLOGACAO-01-10-20261123.md).
- [Procedimento histórico do deploy M13](PROCEDIMENTO-DEPLOY-M13-20261123.md).
- [Indicações](IMPLEMENTACAO-PENDENCIA-05-INDICACOES-20261123.md).
- [Selo verificado](IMPLEMENTACAO-PENDENCIA-06-SELO-VERIFICADO-20261123.md).
- [Denúncias](IMPLEMENTACAO-PENDENCIA-07-DENUNCIAS-20261123.md).

**Registro desta atualização:** alteração documental no GitHub; não altera PHP, migrations, SQLite, serviços ou a instalação da VPS.
