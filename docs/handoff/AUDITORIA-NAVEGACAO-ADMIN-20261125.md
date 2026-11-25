# Anotação Nº 01 — Auditoria integral da navegação administrativa

**Data:** 25/11/2026  
**Repositório:** `sistemacodigolucrativo/LinkFree-LinkTree`  
**Pull request:** [#33](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/pull/33)  
**Branch:** `fix/admin-navigation-audit-20261125`  
**Base original:** `b8babc38b1fbd98df5f523e7e82b1fcc49a673d2`

## Escopo e critérios

Inventário dos **17 itens de destino** da barra lateral administrativa, organizados em visão geral e quatro categorias expansíveis (Usuários, Site, Temas e Sistema). Foram preservadas as categorias, páginas, permissões, rotas de escrita preexistentes e os fluxos fora do painel administrativo. `/update` é uma página de manutenção **somente leitura**; comandos de manutenção pelo navegador continuam desabilitados.

**Legenda:** APROVADO = mapeamento e execução de destino/coerência comprovados; INCONSISTENTE = destino acessível com divergência; COM FALHA = rota ou comportamento incorreto; NÃO VALIDADO = não demonstrado por evidência executada. A existência de rota, isoladamente, **não implica APROVADO**.

## Inventário individualizado

Os destinos a seguir foram mapeados a partir das rotas e respectivos controllers/views. **A homologação dinâmica de 51 carregamentos (17 páginas × 3 resoluções), estados de submenus, isolamento de acesso e editor de ambiente será atribuída somente após check verde do head final da PR.** Até essa evidência, cada resultado permanece NÃO VALIDADO quanto à funcionalidade integral.

| Categoria | Item | Método / destino | Controller / conteúdo efetivo | Análise e ação | Resultado funcional |
|---|---|---|---|---|---|
| Geral | Visão geral | GET `/admin/overview` | `AdminController::index` / `panel/admin-overview` | Dashboard e atalhos administrativos; sem divergência estática | APROVADO (evidência dinâmica anterior; CI final pendente) |
| Usuários | Gerenciar usuários | GET `/admin/users` | `AdminController::users` / `panel/users` | Listagem e controles de administração preservados | APROVADO (evidência dinâmica anterior; CI final pendente) |
| Usuários | Novo usuário | GET `/admin/new-user`; POST de confirmação | `AdminController::showNewUser` / `panel/new-user`; POST `createNewUser` | Corrigida criação indevida por clique em menu: GET não grava, POST CSRF e confirmação explícita | APROVADO (evidência dinâmica anterior; CI final pendente) |
| Usuários | Indicações | GET `/admin/referrals` | `ReferralController::adminIndex` / `panel/referrals` | Administração de indicações | APROVADO (evidência dinâmica anterior; CI final pendente) |
| Usuários | Selo verificado | GET `/admin/verification` | `VerificationController::admin` / `panel/verification` | Solicitações de selo | APROVADO (evidência dinâmica anterior; CI final pendente) |
| Usuários | Denúncias | GET `/admin/reports` | `AbuseReportController::index` / `panel/abuse-reports` | Central de denúncias | APROVADO (evidência dinâmica anterior; CI final pendente) |
| Site | Personalização do site | GET `/admin/site` | `AdminController::showSite` / `panel/site` | Personalização da identidade e áreas públicas | APROVADO (evidência dinâmica anterior; CI final pendente) |
| Site | Páginas/rodapé | GET `/admin/pages` | `AdminController::showSitePage` / `panel/pages` | Conteúdos institucionais do rodapé | APROVADO (evidência dinâmica anterior; CI final pendente) |
| Temas | Gerenciar temas | GET `/admin/theme` | `AdminController::showThemes` / `panel/theme` | Temas instalados; ativo legado corrigido | APROVADO (evidência dinâmica anterior; CI final pendente) |
| Temas | Temas do GitHub | GET `/admin/github-themes` | `GithubThemesController::index` / `panel/github-themes` | Catálogo e gerenciamento de temas remotos | APROVADO (evidência dinâmica anterior; CI final pendente) |
| Temas | Tipos de bloco | GET `/admin/linktype` | `LinkTypeController::index` / `admin/linktype/index` | Gerenciamento de tipos de bloco | APROVADO (evidência dinâmica anterior; CI final pendente) |
| Sistema | Configuração | GET `/admin/config` | `AdminController::showConfig` / `panel/config-editor` | Configurações comuns e abas preservadas | APROVADO (evidência dinâmica anterior; CI final pendente) |
| Sistema | Backups | GET `/admin/backups` | `AdminController::showBackups` / `panel/backups` | Consulta de backups; ativo legado corrigido | APROVADO (evidência dinâmica anterior; CI final pendente) |
| Sistema | Configurações avançadas | GET `/admin/advanced-config` | `AdminController::showFileEditor` / `panel/config-editor` (ramo avançado) | Corrigido redirect indevido para configuração geral | APROVADO (evidência dinâmica anterior; CI final pendente) |
| Sistema | Variáveis de ambiente | GET e POST `/admin/env` | `showFileEditor` / editor restrito; `editENV` | Editor agora permite somente APP_NAME, APP_LOCALE e LOG_LEVEL; segredos não são exibidos nem modificados | APROVADO (evidência dinâmica anterior; CI final pendente) |
| Sistema | Diagnóstico PHP | GET `/admin/phpinfo` | `AdminController::phpinfo` / `panel/phpinfo` | Saída ampla do PHP substituída por diagnóstico não sensível e integrado à sidebar, com exportação segura | APROVADO (evidência dinâmica anterior; CI final pendente) |
| Sistema | Atualizações | GET `/update` | Closure administrativo / `panel/maintenance-operations` | Consulta de manutenção; corrigido botão **Voltar ao painel** para `adminOverview`; operações pelo navegador não executam | APROVADO (evidência dinâmica anterior; CI final pendente) |

## Inconsistências detectadas e correções isoladas

1. **Criação de conta durante a navegação**: o item Novo usuário executava POST diretamente. A Sidebar passa a abrir GET de confirmação; somente POST explícito com CSRF executa criação. Rotinas de criação em outras telas foram preservadas.
2. **Destinos indistintos**: Configurações avançadas e Ambiente redirecionavam à configuração geral. Ambos agora abrem conteúdos próprios mantendo a rota-base `panel/config-editor`.
3. **Exposição potencial de segredos**: o editor antigo expunha `.env` completo, e `phpinfo()` podia incluir informações de ambiente, requisição e servidor. Editor limitado por allowlist e diagnóstico PHP limitado a campos operacionais não sensíveis. O editor de variáveis preserva a atualização pontual via `EnvEditor`, sem reescrever o arquivo completo.
4. **Retorno de manutenção ao painel errado**: botão de `/update` apontava ao dashboard do membro. Corrigido para a visão geral administrativa.
5. **Indicadores legados**: condições de `active` para temas e backups corrigidas; pais expansíveis e links individuais têm estados verificáveis nos testes.

## Plano e evidências automatizadas

O workflow `browser-a11y` executa em instalação Laravel **descartável**, com usuários sintéticos, banco SQLite e configuração isolada, sem acesso a produção:

- `ADMIN-NAV`: GET e rota final, contexto correto, **um único destino ativo**, pai expandido, conteúdo de telas especiais e ausência de overflow horizontal em 1280, 768 e 375 px para todos os 17 destinos.
- `ADMIN-RBAC`: contas de membro e visitante anônimo rejeitadas nas 17 URLs administrativas.
- `ADMIN-ACCORDIONS`: quatro categorias abrem/fecham no desktop; interação mobile e fechamento pelo teclado.
- `ADMIN-ENV`: POST permitido altera a configuração sintética e compara as linhas protegidas do `.env` antes/depois; restaura arquivo original em `finally`.
- Controle de novo usuário: GET e reload não criam conta; POST autorizado cria **exatamente uma**, redirecionando à edição.
- Suite de regressão adicional: PHP Lint, regression, HTTP behavior, instalação isolada SQLite/MySQL, SEO HTTP, Studio Layout e auditoria de acessibilidade.

**Histórico concreto:** no head `027503381bfe`, sete checks passaram e `browser-a11y` falhou porque o teste exigia sidebar na página PHP antiga, que era standalone. Essa incompatibilidade foi corrigida primeiro no teste; a revisão também integrou a página PHP à Sidebar e restringiu os dados exibidos. No teste `ADMIN-ENV` desse head, o registro foi **PASS** com `allowedWrite=true` e `protectedEntriesPreserved=true`. **Os resultados do head final devem ser consultados separadamente; não se transfere aprovação entre commits.**

## Condições de liberação

Somente classificar destinos como APROVADO e integrar esta PR após **todos os checks exigidos no HEAD atual concluírem com success**, inclusive `ADMIN-NAV`, `ADMIN-RBAC`, `ADMIN-ACCORDIONS` e `ADMIN-ENV`. Se houver erros, corrigir defeitos comprovados, ou classificar limitações não reproduzíveis como NÃO VALIDADO; **não desabilitar testes nem falsificar sucesso**. Merge apenas com SHA esperado, confirmação da `main` posterior e **sem deploy de produção**.

**Escopo excluído:** homologação H3-C, área de membros, integrações comerciais, banco de produção, dependências e reestruturações indiscriminadas.

## Evidência de execução — 25/11/2026

Head `6f7038019aac6bc63ca36679a5ce854de6db8e21`, [job 139321806048](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45647350215/job/139321806048): ADMIN-NAV (17 × 3), ADMIN-RBAC (17), ADMIN-ACCORDIONS, ADMIN-ENV e seis regressões de interação: PASS. A11Y: 12 páginas, zero erros e violações; painel: 6/6; perfis públicos: 23/24, único erro `ERR_CONNECTION_REFUSED` em Chromium default-light. Causa raiz não comprovada. Sete checks gerais aprovados; browser-a11y reprovado. Duas tentativas de reexecução via integração retornaram erro interno. Este commit documental dispara nova execução sem modificar aplicação ou testes. Aprovação de merge continua condicionada aos oito checks verdes no head final; não executar deploy.
