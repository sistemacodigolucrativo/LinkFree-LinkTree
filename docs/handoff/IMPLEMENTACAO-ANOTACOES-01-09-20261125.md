# LinkFree — Progresso das Anotações Nº 01 a Nº 09

**Data:** 25/11/2026. **Origem:** main `844979bdbd4cc1bfeb676cedb32342eb1c5c2d3c`.  
**Branch de implementação:** `feat/admin-workflow-ux-paginations-20261125`.  
**Estado final de validação — 25/11/2026:** os nove requisitos foram implementados na branch, com os testes diretamente relacionados **aprovados no CI** (SHA de código testado `e6a7ea2c1c3b2a42253b72dd849f91c891b95717`). **PR #36 pronta para revisão; sem merge nem deploy.** Acessibilidade WCAG integral e dispositivos físicos continuam validações separadas.

## Metodologia e reaproveitamento

- A homologação em produção anterior confirmou o desaparecimento do flash branco após o deploy `844979b`. Não modificar novamente o bootstrap de tema sem evidência.
- [PR #35](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/pull/35) já tinha: **6/6** transições, **24/24** telas config Chromium, **23/23** HTTP; são evidências reutilizáveis **somente** para código/escopo que não mudou.
- A presente branch modifica `AdminController`, Sidebar, views, serviço de personalização, tabela Livewire, históricos e lista de links. Reexecutar **HTTP, Chromium administrativo focado, sintaxe e regressões diretamente envolvidas**. Não dar como validado com resultados antigos nos módulos modificados.
- Reutilizar `UserTable`/Rappasoft em vez de reinventar estado de busca, colunas e paginação. Reutilizar `Page`, `PublicSiteContent`, as rotas existentes e mecanismos de sessão flash/Bootstrap toast.
- Não alterar dados reais de produção: todos os testes com mutações utilizam o ambiente descartável SQLite/contas sintéticas no localhost `127.0.0.1:18765`.

## Estado por anotação (implementação proposta, CI pendente)

| Nº | Resultado da auditoria | Mudança localizada | Evidência / validação |
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


## Execução concluída: matriz de evidências do código testado

**Commit de implementação efetivamente testado:** `e6a7ea2c1c3b2a42253b72dd849f91c891b95717`  
**PR:** https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/pull/36  
**CI completo:** sucesso nos **10 jobs obrigatórios executados**; a suíte `browser-a11y` terminou `skipped` por estar reservada para execução manual.

| Job de CI | Resultado | Evidência |
|---|---|---|
| `admin-ux-smoke` — 375 px escuro e 1280 px claro, renderização, busca real, colunas, criação GET inerte, hub/editor, X, toast, rodapé, históricos e Studio | **11 PASS / 0 FAIL** | [Job 139509110089](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45700526404/job/139509110089) |
| `http-behavior` — segurança CRUD, CSRF, RBAC, GET sem gravação, formulário inválido sem gravação, role server-side, confirmação em Salvar, unicidade e persistência | **23 PASS / 0 FAIL** | [Job 139509110584](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45700526435/job/139509110584) |
| `config-browser-smoke` — hub + 10 seções do admin, desktop/mobile e persistência | **24 PASS / 0 FAIL** | [Job 139509111293](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45700526435/job/139509111293) |
| `dark-transition-filmstrip` — navegação e reload claro/escuro após desativar transição composta da Sidebar no admin | **6 PASS / 0 FAIL** | [Job 139509110088](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45700526319/job/139509110088) |
| `studio-layout` | **success** | [Job 139509111288](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45700526560/job/139509111288) |
| `regression` (guardas a11y e segurança existentes atualizadas ao fluxo intencional) | **success** | [Job 139509110408](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45700526428/job/139509110408) |
| `php-lint` | **success** | [Job 139509111070](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45700526626/job/139509111070) |
| `seo-http` | **success** | [Job 139509110619](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45700526343/job/139509110619) |
| `installer-isolated (sqlite)` | **success** | [Job 139509111110](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45700526435/job/139509111110) |
| `installer-isolated (mysql)` | **success** | [Job 139509111124](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45700526435/job/139509111124) |
| `browser-a11y` global | **skipped — NÃO aprovado** | Escopo manual histórico; nenhuma alegação de conformidade WCAG total |

### Linha do tempo e regressões corrigidas no processo

1. Implementação da branch a partir da `main` `844979b`; nenhum código foi aplicado à VPS.
2. Primeira rodada encontrou **guardas estáticas obsoletas**, pois exigiam a antiga tela intermediária e título interno. As verificações foram **atualizadas para o novo requisito**, mantendo asserts de segurança, sem desativar os guardas.
3. Um job de blocos teve travamento nativo do Chromium (`FATAL:content/browser/site_instance_impl.cc`) sem falha comprovada no código; foi **reexecutado sem mudança no módulo e terminou success**.
4. Primeira versão de teste do histórico falhou por examinar o DOM imediatamente após clicar na página 2. O teste foi corrigido para aguardar a navegação `domcontentloaded` e conferir status HTTP. **A segunda página passou com 20 linhas; a primeira também passou com 20.** Os dados sintéticos forçaram o cenário de múltiplas páginas.
5. A busca da `UserTable` foi verificada com filtro e retorno dos resultados; seleção de colunas alterou efetivamente o cabeçalho. Links do Studio foram testados com 27 registros sintéticos (20 na página 1 e 7 na página 2).
6. Execução final do commit de implementação `e6a7ea2`: **todos os jobs obrigatórios executados verdes**, sem recorrer a dados reais.

### Observações para homologação pós-merge, sem repetir o que já foi aprovado

- **Admin em Opera mobile físico:** confirmar visualmente que não há duplicação transitória da Sidebar e que o título fica entre seta e sino; filmstrip Chromium não é prova universal.
- **Novo Usuário:** inspecionar GET sem cadastrar dados reais; o teste completo de criação já foi realizado com contas sintéticas (não criar contas de teste reais sem necessidade).
- **Personalização do Site:** percorrer uma seção, usar X, salvar alteração autorizada e observar toast. Diferenciar `Páginas institucionais` da configuração de textos dos links.
- **Históricos:** verificar navegação das páginas quando houver registros reais suficientes, sem gerar dados artificiais na produção.
- **Publicação:** antes de qualquer deploy, rodar `/root/atualizar-linkstack.sh --check` e observar o diff de arquivos; usar backup privado e homologação read-only do procedimento já existente.

**Limitações declaradas:** testes automáticos reproduzem os comportamentos no Chromium sintético; testes de Opera, Safari, outros navegadores/GPU e WCAG completa permanecem fora do escopo de aprovação desta PR. Não inferir o estado de produção a partir do merge.

**Status de entrega:** implementação e validação de código prontas para revisão/merge; `main` e VPS inalteradas durante esta implementação.
