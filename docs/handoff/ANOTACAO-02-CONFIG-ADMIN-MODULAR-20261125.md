# Anotação Nº 02 — Reestruturação das Configurações Administrativas

**Data:** 25/11/2026  
**Branch:** `feat/admin-config-modular-20261125`  
**Commit inicial:** `88f5d28b98c376bdbc8d584261ea579240d01555`  
**Status final da PR #34:** **integrada à main** em 25/11/2026; merge commit `8fad67d5036807488525ce0a313d047f062ac90e`. **Código de aplicação não publicado na VPS nesta etapa.** Homologação HTTP e Chromium aprovadas no escopo descrito abaixo; acessibilidade integral e Anotação Nº 03 permanecem separadas.

## Regra obrigatória: preservar a Sidebar

O menu lateral **não deve ser tocado**. O arquivo `linkstack/resources/views/layouts/sidebar.blade.php` não foi modificado. A organização interna da área `/admin/config` foi alterada; as categorias e os acessos da Sidebar permanecem inalterados. Como consequência, os acessos que já se repetiam entre o menu Sistema e as Configurações não são removidos do menu lateral sem nova autorização.

## Inventário das cinco abas anteriores

A página `resources/views/panel/config-editor.blade.php` agregava num mesmo card `Config`, `Advanced Config`, `Take Backup`, `All Backups` e `Diagnosis`. O primeiro grupo carregava `components.config.config` com oito seções na mesma tela. A estrutura foi substituída por hub de domínios e páginas independentes; editores existentes mantêm as rotas.

| Domínio funcional | Conteúdos identificados no código existente | Destino dedicado |
|---|---|---|
| Aplicação | ALLOW_REGISTRATION, REGISTER_AUTH, MANUAL_USER_VERIFICATION, ADMIN_EMAIL, HOME_URL, FORCE_HTTPS, APP_NAME, HIDE_VERIFICATION_CHECKMARK, ENABLE_REPORT_ICON | `/admin/config/sections/application` |
| Comportamento do painel | NOTIFY_EVENTS, NOTIFY_UPDATES, ENABLE_ADMIN_BAR_USERS | `/admin/config/sections/panel` |
| Aparência/decoração | ENABLE_BUTTON_EDITOR, USE_THEME_PREVIEW_IFRAME, ALLOW_CUSTOM_BACKGROUNDS | `/admin/config/sections/appearance` |
| Segurança | ALLOW_USER_HTML, ALLOW_CUSTOM_CODE_IN_THEMES, ENABLE_THEME_UPDATER, ALLOW_USER_EXPORT, ALLOW_USER_IMPORT | `/admin/config/sections/security` |
| Operação avançada | JOIN_BETA, MAINTENANCE_MODE, SKIP_UPDATE_BACKUP, CUSTOM_META_TAGS, ENABLE_SOCIAL_LOGIN, FORCE_ROUTE_HTTPS | `/admin/config/sections/advanced` |
| E-mail / SMTP | MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION, MAIL_FROM_ADDRESS e teste de envio | `/admin/config/sections/smtp` |
| Links do rodapé | DISPLAY_FOOTER, DISPLAY_CREDIT, DISPLAY_CREDIT_FOOTER, DISPLAY_FOOTER_HOME, HOME_FOOTER_LINK e títulos/opções de Termos, Privacidade e Contato | `/admin/config/sections/footer` |
| Depuração | APP_DEBUG, APP_ENV, LOG_LEVEL | `/admin/config/sections/debug` |
| Idioma | LOCALE | `/admin/config/sections/language` |
| Diagnóstico geral | `components.config.diagnose` | `/admin/config/sections/diagnostics` |
| Editor avançado de arquivo | `components.config.advanced-config` | `/admin/advanced-config` (inalterado) |
| Editor ENV de allowlist | `components.config.alternative-config` | `/admin/env` (inalterado) |
| Consulta de backups | `components.config.backups` | `/admin/backups` (inalterado) |
| Manutenção / backup legado | Aviso de manutenção controlada | `/backup` e `/update` (inalterados) |
| Informações PHP | `panel/phpinfo` | `/admin/phpinfo` (inalterado) |
| Conteúdo de páginas do rodapé | `AdminController::showSitePage` | `/admin/pages` (inalterado; diferente da exibição dos links) |
| Personalização do site | `AdminController::showSite` | `/admin/site` (inalterado) |

As opções do grupo Painel foram distribuídas em dois domínios, Painel e Aparência, **sem apagar controles**. O inventário do componente é verificado por teste automatizado estático.

## Modificações realizadas

1. `GET /admin/config`: hub de cartões de uma finalidade por domínio, sem a antiga sequência de abas.
2. `GET /admin/config/sections/{section}`: nova rota administrativa sob `auth`, `blocked`, `impersonate` e `admin`, com validação explícita da seção na allowlist do controlador.
3. `panel/config-section.blade.php`: página individual com título, descrição, retorno ao hub e reaproveitamento dos componentes.
4. `components/config/config.blade.php`: cada grupo agora renderiza apenas na seção correspondente; os formulários continuam usando os handlers POST e tokens CSRF existentes.
5. `AdminController::editConfig`: após POST, só retorna para uma seção quando o endereço anterior é da mesma origem e pertence à allowlist; fallback ao hub. `editAC` retorna diretamente ao editor avançado, e o teste SMTP retorna à tela SMTP.
6. Compatibilidade das antigas URLs com fragmento `/admin/config#1..#5` e âncoras `#Application`, `#SMTP`, `#Language` via redirecionamento no navegador. A rota legada `/panel/diagnose` redireciona ao diagnóstico próprio.
7. Teste estático `scripts/audit-admin-config-modules.py` adicionado à rotina de regressão, com compilação sintática do controlador.

## Limites e riscos ainda abertos

- **Não houve alteração de handlers de persistência**, modelo, banco, serviços de email ou permissões. A preservação de código não substitui o teste de gravação/restauração.
- Não executar envio de e-mail, alternância de manutenção, debug ou dados sensíveis na produção só para homologar a interface.
- As configurações que antes compartilhavam um card podem revelar dependências de JS/ordem de carga; precisam de verificação no navegador e observação de falhas.
- A navegação em tema escuro e os flashes brancos (Anotação Nº 03) **não** estão declarados resolvidos. Devem ser avaliados antes de deploy.
- A Sidebar não é alterada; redundâncias no menu Sistema continuam explicitamente fora do escopo desta etapa.

## Resultado técnico de fechamento e merge — 25/11/2026

**PR:** [#34 — configurações administrativas em telas independentes](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/pull/34). **Merge autorizado pelo responsável e confirmado no GitHub**.

**Head de código homologado:** `beb7e5cfbe5981b2f74e421c7865de47ba68e533`. **Merge na main:** `8fad67d5036807488525ce0a313d047f062ac90e`.

| Verificação | Evidência | Resultado |
|---|---|---|
| Regressão HTTP completa | [Job `http-behavior`](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45684365852/job/139452917215) | **23 PASS / 0 FAIL** |
| Navegador Chromium em fixture isolada | [Job `config-browser-smoke`](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45684365852/job/139452916796) | **24 PASS / 0 FAIL** |
| PHP syntax lint, security regression, SEO HTTP e Studio layout | Checks do head homologado | **success** |
| Instalador isolado SQLite e MySQL | Checks do head homologado | **success** |
| Acessibilidade de site inteiro (`browser-a11y`) | Check deliberadamente manual | **skipped — não homologado** |
| Integridade da Sidebar | Diff de arquivos da PR #34 | **Não modificada** |

**Cobertura do Chromium:** índice e dez páginas administrativas com títulos/formulários, navegação real, viewport desktop 1280 px no tema claro, viewport móvel 375 px no tema escuro, verificações de fundo escuro e ausência de overflow horizontal, gravação de configuração via clique real com persistência confirmada em nova consulta e negação a visitantes. O HTTP cobriu controle de acesso de administrador/membro/visitante, CSRF, rotas antigas, reautenticação necessária após mudança sintética de `APP_NAME`, persistência de toggle e demais operações da suíte.

**Correções na homologação:** conserto de sintaxe de teste Python; redução de matrizes duplicadas do Chromium para evitar timeout; conserto de corrida de navegação em formulário; um travamento isolado do Chrome no `studio-layout` passou na repetição. Nenhum dos problemas foi classificado equivocadamente como defeito confirmado da aplicação.

### Pendências distintas da autorização de merge

- A bateria global `browser-a11y` foi mantida **fora dos checks obrigatórios** por determinação anterior; não equivale à aprovação WCAG integral. Teclado, leitor de tela, dispositivos Android/iOS físicos e todos os cruzamentos de tema/resolução não foram abrangidos pelo smoke.
- O problema de flash branco no tema escuro (**Anotação Nº 03**) **não foi declarado corrigido**. Há uma inspeção de cor de fundo no início das páginas testadas, não uma aferição de todos os quadros durante transições. Exige investigação e homologação próprias.
- Nem todas as operações de gravação sensíveis (SMTP real, manutenção e atualização em produção) foram exercitadas; testes sintéticos não substituem uma homologação operacional controlada.
- **Merge não é deploy:** validar `--check`, backup e a checklist da VPS antes de publicar a nova versão; nenhum deploy foi realizado nesta etapa.

**Conclusão:** requisitos de separação interna das configurações validados nos cenários executados e **código integrado à main**. Exceções permanecem explicitamente registradas para evitar conclusão indevida da acessibilidade total e do requisito separado de transições escuras.
