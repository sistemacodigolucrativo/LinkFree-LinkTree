# LinkFree — Progresso das Anotações Nº 01 a Nº 09

**Data:** 25/11/2026. **Origem:** main `844979bdbd4cc1bfeb676cedb32342eb1c5c2d3c`.  
**Branch de implementação:** `feat/admin-workflow-ux-paginations-20261125`.  
**Estado desta versão:** alterações de código implementadas em branch, com testes ainda a verificar pelo CI. Sem merge nem deploy.

## Metodologia e reaproveitamento

- A homologação em produção anterior confirmou o desaparecimento do flash branco após o deploy `844979b`. Não modificar novamente o bootstrap de tema sem evidência.
- [PR #35](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/pull/35) já tinha: **6/6** transições, **24/24** telas config Chromium, **23/23** HTTP; são evidências reutilizáveis **somente** para código/escopo que não mudou.
- A presente branch modifica `AdminController`, Sidebar, views, serviço de personalização, tabela Livewire, históricos e lista de links. Reexecutar **HTTP, Chromium administrativo focado, sintaxe e regressões diretamente envolvidas**. Não dar como validado com resultados antigos nos módulos modificados.
- Reutilizar `UserTable`/Rappasoft em vez de reinventar estado de busca, colunas e paginação. Reutilizar `Page`, `PublicSiteContent`, as rotas existentes e mecanismos de sessão flash/Bootstrap toast.
- Não alterar dados reais de produção: todos os testes com mutações utilizam o ambiente descartável SQLite/contas sintéticas no localhost `127.0.0.1:18765`.

## Estado por anotação (implementação proposta, CI pendente)

| Nº | Resultado da auditoria | Mudança localizada | Validação exigida |
|---|---|---|---|
| 01 | Layout ativava `@view-transition{navigation:auto}` também no Admin, podendo cruzar visualmente duas capturas da Sidebar durante a navegação | Opt-out de view transition **apenas no contexto admin**, sem excluir menus, e teste de Sidebar única | Navegar entre rotas, desktop/mobile, confirmar no Opera real após deploy |
| 02 | Título de Gerenciar Usuários dentro do card ocupava espaço mobile | Slot Blade `mobile-navbar-title`, título entre seta/sino; heading no conteúdo apenas desktop | 375px + 1280px, sem overflow |
| 03 | Busca e seleção de colunas pertencem ao renderer Rappasoft da UserTable | Renderer **exclusivo** do componente: busca acima do card; dropdown slim e registros por página no rodapé com `Showing results` do pacote | Busca, colunas, paginação e Livewire após filtros |
| 04 | Ação no rodapé era formulário POST para criação, antes de abrir o editor | Link `GET showNewUser` com ícone verde pequeno no topo do card | Ícone presente e acionável, sem escrita na base |
| 05 | Rota `GET /admin/new-user` mostrava confirmação intermediária | GET agora mostra o formulário real; atalhos de sidebar, Visão geral e Gerenciar Usuários apontam ao GET | Acesso direito, RBAC, sem criação no GET |
| 06 | POST `createNewUser` gravava usuário antes da edição | Formulário pré-preenchido no GET, `POST` com validação, CSRF, credenciais obrigatórias, role controlada pelo servidor; criação apenas após **Salvar** | GET/reload não alteram DB; POST inválido/CSRF bloqueado; POST válido grava uma vez, e-mail duplicado rejeitado |
| 07 | Fila de selos, indicações e lista admin já usam paginação; **histórico de selos** tinha `limit(30)`; histórico de eventos de denúncia usava `get()`; links do membro usavam `paginate(99999)` | Adicionada paginação dos históricos, com parâmetro `history_page` independente, e links do Studio 20/página preservando o `data-per-page` da ordenação | Paginação, estabilidade de filtros e reordenação; demais listas existentes preservadas |
| 08 | Site usava `?section` mas exibia nav e editor na mesma tela; mensagens flash eram alertas inline | `/admin/site` vira hub; `?section=...` vira tela exclusiva com botão X sticky; salvar com sucesso retorna ao hub e mostra toast bootstrap | Navegar, fechar, salvar, persistir, mensagens de erro/sucesso e Sidebar sem overlay |
| 09 | `/admin/pages` efetivamente edita conteúdo de **Termos, Privacidade, Contato**; `/admin/site?section=footer` edita **rótulos de links**, `/admin/config/sections/footer` define **exibição/destinos** | Mantidas rotas distintas e labels claros: Sidebar `Páginas institucionais`, site `Textos dos links do rodapé`; acesso aos 3 módulos preservado | Confirmar 3 destinos distintos e ausência de funções excluídas |

## Inspeção de listagens e exceções

| Listagem | Situação |
|---|---|
| Admin → Gerenciar Usuários | Livewire Rappasoft já paginava; preservado, controles reorganizados |
| Admin → Indicações | `paginate(20)` + query string já implementado; não duplicar |
| Membros → Minhas indicações | `paginate(15)` já implementado; não duplicar |
| Admin → Fila de selo | `paginate(20)` existente; preservado |
| Admin → Histórico de selo | **Novo `paginate(20, ['*'], 'history_page')`**, sem truncar a história |
| Admin → Central de denúncias | `paginate(20)` existente; preservado |
| Admin → Histórico de denúncia individual | **Novo `paginate(15)`** |
| Admin → Links de membro | `paginate(10)` existente; preservado |
| Studio → Meus Links | Substituído `paginate(99999)` por `paginate(20)`, atualização de `data-per-page` e renderização de `links()` |
| Dados agregados / seletores (temas, botões, relatórios de fontes, métricas públicas) | Não são listagens de resultados pagináveis de navegação; preservar |

## Gates e riscos

1. **Automáticos obrigatórios:** lint PHP/Node/Python; HTTP real com CSRF e SQLite sintético; Chromium admin mobile e desktop; config-browser da aplicação; regressões de rotas e layout. Reaproveitar jobs anteriores apenas quando não alteraram código relacionado.
2. **Dependências de interface:** garantir que a substituição visual de `UserTable` mantém wire:model, coluna, sort, status e as tabelas. `Livewire` deve funcionar em página com JS ativo.
3. **Backend sensível:** criação intencional só no salvar, sem submissão em links/GET, privilégios controlados pelo servidor e senha temporária visível apenas ao admin antes da submissão.
4. **Publicação controlada:** merge somente após validação; VPS segue intacta até deploy separado, com `--check`, backup SQLite e homologação pós-publicação.
5. **Manuais complementares:** Opera real no celular para Sidebar e navbar; acessibilidade WCAG completa continua separada (job `browser-a11y` automático historicamente skipped).

## Evidências

- Tests HTTP reutilizados/atualizados: `scripts/qa-isolated/http_acceptance.py`.
- Testes Chromium novos focados: `scripts/qa-isolated/admin_ux_smoke.cjs`, job `admin-ux-regression.yml`.
- Fixture/instalador browser reutilizado sem contas reais: `scripts/qa-isolated/run_config_browser_smoke.sh`.
- Teste de interações existente atualizado para fluxo seguro: `scripts/qa-isolated/browser_interactions.cjs`.

**Atualização obrigatória:** após obter o resultado real do GitHub Actions, registrar SHA, nome de job, total PASS/FAIL, falhas e escopos de validação incompletos antes de qualquer declaração de conclusão.
