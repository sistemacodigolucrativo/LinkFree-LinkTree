# Anotação 01 — Navegação administrativa — 25/11/2026
Base: b8babc38b1fbd98df5f523e7e82b1fcc49a673d2. PR 33.
Todos os destinos abaixo são protegidos pelo grupo administrativo; análise estática não equivale a homologação.
| Menu | Método e rota | Controller / destino | Diagnóstico |
|---|---|---|---|
| Visão geral | GET /admin/overview | AdminController::index / panel/admin-overview | Coerente |
| Usuários | GET /admin/users | AdminController::users / panel/users | Coerente |
| Novo usuário | GET e POST /admin/new-user | showNewUser / panel.new-user; createNewUser / edição | Corrigido: confirmação antes de persistir |
| Indicações | GET /admin/referrals | ReferralController::adminIndex / panel.referrals | Coerente |
| Selo | GET /admin/verification | VerificationController::admin / panel.verification | Coerente |
| Denúncias | GET /admin/reports | AbuseReportController::index / panel.abuse-reports | Coerente |
| Site | GET /admin/site | AdminController::showSite / panel/site | Coerente |
| Rodapé | GET /admin/pages | AdminController::showSitePage / panel/pages | Coerente |
| Temas | GET /admin/theme | AdminController::showThemes / panel/theme | Coerente |
| GitHub | GET /admin/github-themes | GithubThemesController::index / panel.github-themes | Coerente |
| Blocos | GET /admin/linktype | LinkTypeController::index / admin.linktype.index | Coerente |
| Configuração | GET /admin/config | AdminController::showConfig / panel/config-editor | Coerente |
| Backups | GET /admin/backups | AdminController::showBackups / panel.backups | Coerente; download opcional |
| Avançadas | GET /admin/advanced-config | showFileEditor / config-editor | Corrigido destino |
| Ambiente | GET /admin/env | showFileEditor / config-editor | Destino distinto sem valores brutos; alteração de acesso ao editor requer revisão |
| PHP | GET /admin/phpinfo | phpinfo / panel/phpinfo | Não validado quanto a exposição de dados |
| Atualizações | GET /update | closure / panel.maintenance-operations | Restrição intencional preservada |
Indicadores legados de temas e backup corrigidos na sidebar. POST de criação mantém CSRF, limite de usuários e política VIP. Sem mudanças no banco ou deploy.
Testes: primeira execução PR 33 teve cinco checks aprovados, regressão falhou por expectativas antigas de GET 405 e formulário POST na sidebar; assertions adaptadas para exigir GET sem persistência, autorização e POST com CSRF. Resultado final deve ser consultado nos checks do head da PR.
Etapas 1–2 completas; 3 análise estática parcial (views e efeitos não integralmente homologados); 4 pendente validação autenticada responsiva; 5 correções em branch; 6 este relatório; 7 CI pendente; 8 PR aberta sem merge. Não liberar deploy antes de checks e revisão da preservação funcional do editor de ambiente. H3-C permanece pausado.
