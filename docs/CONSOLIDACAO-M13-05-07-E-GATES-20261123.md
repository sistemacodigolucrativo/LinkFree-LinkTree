# LinkFree — Consolidação de M13 (pendências 05–07)

Data: 2026-11-23. Estado: **código reunido para revisão; NÃO autorizado para produção**.

## Evidências e origem

- main na revisão: 54add49d30a878fb8efedfaec193c7af607a5a0d (produção conforme último log do operador).
- PR #11 (indicações): implementation/linkfree-indicacoes-05-20261123 → main.
- PR #12 (selo verificado): implementation/linkfree-selo-verificado-06-20261123 → branch da PR #11.
- PR #13 (denúncias): implementation/linkfree-denuncias-07-20261123 → branch da PR #12.
- Branch unificada de preparação: integration/linkfree-pendencias-05-07-pre-homologacao-20261123, criada a partir do código completo da PR #13, commit e3c17ae6aa1ba23c16115ce5b089d67370596880.
- Diff acumulado anterior à preparação: 30 arquivos, 11 commits em relação à main.
- Workflows mais recentes da PR #13 (SHA e3c17ae6aa1b): PHP syntax lint e auditoria automatizada de layout aprovados.
- As três PRs empilhadas não foram integradas à main e **nenhuma migração nova foi aplicada na VPS**.

## Situação de cada pendência

| Nº | Estado verificável |
|---|---|
| 01–03 | Verificações técnicas read-only da VPS aprovadas no SHA 54add49d30a8; testes finais não executados |
| 04 | Implementação e testes isolados anteriores; cadastro real e OAuth real da VPS ainda não comprovados |
| 05 | Código de indicações implementado na PR #11; sem deploy |
| 06 | Código de selo verificado implementado na PR #12; sem deploy |
| 07 | Código de denúncias implementado na PR #13; sem deploy |
| 08–10 | Homologação funcional, UI/compatibilidade e produção pendentes |

**Não inferir homologação funcional a partir de PHP lint, CI estático ou HTTP público.**

## Bloqueio P0 — deploy e migrações

O script scripts/atualizar-linkstack.sh da main autoriza automaticamente só a migração de consentimento 000001. Todas as demais mudanças em database/migrations/* são recusadas por segurança. Portanto, o comando de deploy **não pode publicar 05–07 como estão**.

Migrações obrigatórias:
1. 2026_11_23_000002_create_referral_tables.php: referral_codes, referrals.
2. 2026_11_23_000003_create_profile_verification_workflow.php: users.verified_at, users.verification_visible, verification_requests, verification_audit_events.
3. 2026_11_23_000004_create_abuse_reports_tables.php: abuse_reports e abuse_report_events.

**Resolução necessária antes de publicação:** revisar e aprovar alteração específica e restrita do fluxo de deploy para admitir nominalmente as três migrations, depois de validar backup consistente e restauração em ambiente isolado. Exigir: origem e commit conhecidos; banco SQLite correto; integridade; permissão www-data; migrations verificadas em cópia; backup SQLite online e storage; execução aditiva antes de copiar os novos controllers; verificação do esquema; publicação e smoke; falha fechada. Nunca contornar o bloqueio removendo migrations ou atualizando banco de produção manualmente.

Incluído `scripts/preflight-m13-readonly.sh` para inspeção **somente de leitura** do esquema já existente. Um resultado pendente (exit code 3) é esperado na VPS antes das migrations. O script não prepara nem executa deploy.

## Riscos P1 / decisões necessárias

- Selo: plano VIP não concede mais selo; decidir como tratar perfis historicamente exibidos como verificados. A migração NÃO concede aprovação retroativa.
- Denúncias: reter dados apenas pelo período definido, ajustar política LGPD e acesso/retenção de históricos.
- OAuth: verificar sessão de convite e consentimento entre os redirecionamentos reais.
- SQLite: confirmar FKs, concorrência e transações no banco isolado.
- Interface: verificar badge de selo Livewire, visualização pública, modo escuro/claro, telas autenticadas e responsividade.

## Sequência de liberação recomendada

1. Revisão da PR de integração e validação dos critérios de `docs/MATRIZ-HOMOLOGACAO-01-10-20261123.md`.
2. Fechamento do P0 de deploy em branch/PR separada e validação em SQLite isolado, com backup restaurável.
3. Aprovação de política de migração de selo antigo e retenção das denúncias.
4. Preparar contas, evidências e ambiente de staging.
5. Após autorização: merge controlado, deploy com migrações coordenadas, checks técnicos e testes funcionais 01–10 (primeiro staging e depois produção).
6. Aprovar somente com resultados e evidências; nenhum rollback destrutivo de consentimentos ou dados de usuários.

**Nesta fase, não executar merge, deploy, migrações ou testes autenticados na VPS.**
