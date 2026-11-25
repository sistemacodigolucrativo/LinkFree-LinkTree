# LinkFree — Homologação manual da PR #36 e nova lista de correções

**Data do registro:** 25/11/2026  
**Repositório:** `sistemacodigolucrativo/LinkFree-LinkTree`  
**Base efetivamente confrontada:** `main` em `d8dc7043ab41d4749c2592c1994c5ea6f3268607`, conferida diretamente no GitHub em 25/11/2026.  
**PR original:** [#36](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/pull/36), integrada em `c1bb27d94c5ff7f553ec16aac6c9743c0a6901ce`.  
**Deploy comprovado pelo operador:** `3d2391563b05b18067229eae5424a630a45047c5`; backup privado `/var/backups/linkfree-deploy/20261125-211519-s2XlSu`; homologação HTTP/SQLite **read-only**, 0 falhas no escopo automatizado.  
**Natureza desta etapa:** conferência do código **atual da `main`** com os relatos de navegação em produção. **Nenhum código, rota, banco ou VPS foi modificado nesta etapa; atualização exclusivamente documental.**

## Como interpretar as evidências

1. **Código na main** confirma implementação/rotas/elementos presentes, mas não equivale à aprovação em dispositivo físico.
2. **Usuário durante a homologação** confirma apenas as interações que relatou explicitamente. "A tela exibe" **não** significa "edição e persistência testadas".
3. A suíte sintética da PR #36 aprovou **11/11 Chromium administrativo, 23/23 HTTP, 24/24 configuração e 6/6 transições**, mais lint, segurança estática, layout, SEO e instaladores. O `browser-a11y` global ficou `skipped` (não aprovado).
4. A nova lista abaixo **não foi implementada** e **não faz parte da homologação técnica aprovada da PR #36**. Não confundir pedidos novos com funcionalidades já entregues.

## Homologação das 9 anotações originais — estado reconciliado

| Nº | Requisito original | Evidência da main | Relato da navegação na VPS | Estado manual |
|---|---|---|---|---|
| **01** | Eliminar sobreposição/duplicação de Sidebar | `resources/views/layouts/sidebar.blade.php` desativa `view-transition` no Admin | Usuário informou que parou de acontecer | **Aprovado no dispositivo testado** |
| **02** | Título mobile de Gerenciar Usuários na barra entre seta e sino | `panel/users.blade.php` define `mobile-navbar-title`; Sidebar renderiza o slot | Usuário confirmou título na barra superior | **Aprovado quanto ao título** |
| **03** | Pesquisa acima do card; colunas slim junto dos resultados e paginação | `livewire/admin-user-table.blade.php` coloca busca antes de `.lf-users-results-card`, dropdown `lf-user-columns-trigger` e seletor `perPage` no rodapé | Dropdown confirmado correto; pesquisa ainda parece estar em um card visual | **Parcial: ver nova Nº 01** |
| **04** | Ícone verde de adicionar usuário no topo do card | `livewire/admin-user-table.blade.php`, link `showNewUser` com `bi-person-plus-fill` e classe verde `lf-users-add-icon` | Fluxo de Novo Usuário utilizado, mas posicionamento do ícone não foi aprovado separadamente por relato explícito | **Implementado; confirmação visual específica pendente** |
| **05** | Abrir formulário de novo usuário direto, sem tela intermediária | `GET /admin/new-user` → `AdminController::showNewUser` → `panel/new-user.blade.php` | Usuário chegou ao formulário e usou `Cancelar sem criar` | **Aprovado no fluxo observado** |
| **06** | Criar usuário apenas ao clicar Salvar; pré-preenchimento permitido | `showNewUser` apenas renderiza; `createNewUser` valida POST/CSRF e persiste, depois retorna `showUser` | Usuário confirmou que salvou, cadastro apareceu na lista; cancelamento sem criar funcionou; redirecionamento pós-salvar é indesejado | **Criação persistida; ajuste de destino na nova Nº 02**. Imutabilidade pré-salvar coberta em CI, não auditada diretamente no banco real |
| **07** | Paginação padronizada nas listas de admin/membros | `VerificationController` tem `history_page` e `paginate(20)`; `verification.blade.php` usa apenas `$history->links()`; histórico denúncias `paginate(15)`; Studio links `paginate(20)` | No histórico de selo com 3 itens não há controle visível de paginação/exibição por página como Gerenciar Usuários | **Não atende ao novo padrão visual solicitado; ver nova Nº 04** |
| **08** | Personalização por páginas exclusivas, fechar e toast/retorno | `panel/site.blade.php` tem hub, `?section=`, barra sticky com título e X, toast Bootstrap na própria view e retorno ao hub no salvamento com sucesso | Páginas exclusivas/navegação aprovadas; botão X deve virar FAB, sem título; padronização de toasts precisa abranger ambos painéis | **Parcial: ver novas Nº 03 e Nº 05** |
| **09** | Diferenciar Páginas Institucionais, textos e configurações dos links de rodapé | Sidebar → `showSitePage`; `PublicSiteContent` chama seção de textos; `config` traz opções de exibição/destino | Usuário conseguiu abrir Páginas Institucionais e disse que estava exibindo normalmente; navegação/nomenclatura causaram dúvida | **Exibição confirmada; salvamento dos textos e clareza da navegação não homologados** |

### Precauções

- O aceite de uma interação **não** implica aprovação de todos os subrequisitos da mesma anotação.
- Não houve teste manual confirmado de salvamento dos três textos de Páginas Institucionais em produção; **não** declarar a Anotação 09 completamente homologada.
- Não repetir cadastro real apenas para comprovar ausência de persistência em GET: a PR #36 já cobriu isso em ambiente descartável. Testes manuais com dados reais devem ser estritamente necessários e autorizados.
- A constatação de falta de controle de páginas com só 3 itens não prova ausência de `paginate()` no backend: o Laravel tende a ocultar links quando só há uma página. O pedido atual é **sempre oferecer o mesmo padrão de controles de listagem**, com tamanho de página e resumo dos resultados.

## Nova lista de correções — cinco anotações pendentes

### NOVA ANOTAÇÃO Nº 01 — Pesquisa completamente fora de qualquer card

**Local:** Painel Administrativo → Gerenciar Usuários (principalmente mobile).  
**Relato:** a pesquisa subiu, porém parece dentro de um card.  
**Confronto na main:** o campo está fora de `.lf-users-results-card` no DOM, mas o próprio contêiner `.lf-users-search` recebe `background`, `border`, `border-radius` e `padding` em `resources/views/panel/users.blade.php`, produzindo a aparência de card.  
**Resultado exigido:** campo/label acima da tabela sem moldura/card individual, preservando Livewire, responsividade, foco, busca e limpeza; não mover a busca para dentro do card de resultados.  
**Situação:** **não implementada**; mudança visual pequena, testar 375px/1280px e tema escuro.

### NOVA ANOTAÇÃO Nº 02 — Salvar novo usuário retorna a Gerenciar Usuários

**Local:** Painel Administrativo → Novo Usuário → Salvar Usuário.  
**Relato:** criação funciona e aparece na listagem, mas salvar abre tela de edição (senha vazia etc.).  
**Confronto na main:** `AdminController::createNewUser` devolve `redirect()->route('showUser', ['id' => $user->id])` após `DB::transaction` (linhas ~263–286).  
**Resultado exigido:** após **POST válido e gravação bem-sucedida**, `redirect()->route('showUsers')` com feedback claro; falha de validação permanece no formulário com campos preservados. `Cancelar sem criar` permanece sem escrita no banco.  
**Situação:** **não implementada**; ajustar sem alterar regras de criação, VIP ou segurança. Atualizar testes HTTP/Chromium que hoje esperam redirecionamento para o editor.

### NOVA ANOTAÇÃO Nº 03 — X de Personalização do Site em padrão FAB

**Local:** Painel Administrativo → Personalização do Site → seção individual.  
**Relato:** X aparece dentro de barra com título, em posição inadequada.  
**Confronto na main:** `panel/site.blade.php` renderiza `.lf-site-section-toolbar` com `<h1>` e link `.lf-site-close`; `linkfree-admin-management.css` define a barra com `position:sticky`.  
**Resultado exigido:** **apenas o X visível**, em botão de ação flutuante (FAB), independente da barra de título e acessível durante a navegação, sem ocupar o fluxo normal dos cards. Responsivo, com área de toque adequada; **preservar `aria-label` para leitores de tela**. O clique fecha a seção e volta ao hub, sem perder dados salvos.  
**Situação:** **não implementada**; validar sobreposição, z-index, scroll e tema escuro.

### NOVA ANOTAÇÃO Nº 04 — Paginação e exibição por página em todas as listas tabulares

**Local:** Painel Administrativo e Painel de Membros — tabelas/listagens em grade de usuários, ações, históricos, indicações, solicitações e atividades.  
**Relato:** Histórico recente de selos tinha somente 3 itens e não exibia os mesmos controles de Gerenciar Usuários.  
**Confronto na main:** `VerificationController::admin` usa `paginate(20, ['*'], 'history_page')`, enquanto `verification.blade.php` exibe somente `$history->links()`; não existe ali um seletor explícito de tamanho da página. O `UserTable` usa Livewire e oferece `perPage`. Outros módulos usam paginações Laravel próprias, sem UI uniforme.  
**Resultado exigido:** auditar **cada listagem tabular real**, preservar paginação existente quando houver, mas padronizar resumo `Showing Results` (ou tradução pt-BR), seletor de quantidade por página e navegação; apresentar controles coerentes mesmo com apenas uma página/3 registros (p. ex., navegação desativada) quando houver listagem. Preservar filtros, ordenação, escopos de permissões e nomes independentes de query string para múltiplas listas na mesma página. Evitar paginações duplicadas, consultas `get()` sem limite indevido e opções `all` com volume perigoso. Não forçar paginação em componentes que não sejam listas de resultados.  
**Situação:** **não implementada em escala global**; não inferir que `paginate()` isolado satisfaça o requisito de UX.

### NOVA ANOTAÇÃO Nº 05 — Sistema global consistente de toasts

**Local:** todas as operações relevantes dos painéis Administrativo e de Membros.  
**Relato:** salvar, erro, advertência e necessidade de atenção precisam aparecer como toast flutuante, com boas práticas.  
**Confronto na main:** `panel/site.blade.php` contém `.lf-site-toast-stack` e usa Bootstrap Toast, mas `panel/users.blade.php` e `components/config/config.blade.php` ainda exibem mensagens por alertas inline; `panel/verification.blade.php` idem. Não há evidência de padronização global completa entre as telas examinadas.  
**Resultado exigido:** auditar feedback de operações de gravação, falha e advertência; preferir **componente/serviço reutilizável** em layout compartilhado, reaproveitando Bootstrap e flashes existentes. Severidades `success`, `error`, `warning` e `info`; mensagens objetivas, duração adequada, possibilidade de dispensar, `role=status/alert`, `aria-live`, navegação por teclado, responsividade, contraste e respeito a movimento reduzido. Não duplicar toast com alerta desnecessário, não sinalizar sucesso antes da resposta e não esconder erros de validação críticos do formulário. Navegação passiva não precisa gerar notificações.  
**Situação:** **não implementada globalmente**; requer inventário técnico antes da migração das views.

## Verificação adiada: acesso e nomenclatura das Páginas Institucionais

O usuário pediu para investigar depois por que os nomes **Páginas Institucionais** e **Links do Rodapé** parecem confusos. Isto é **uma revisão adicional pendente**, mas **não uma sexta anotação numerada**, para preservar a numeração solicitada.

Confronto estático na `main`:
- **`/admin/pages` → `AdminController::showSitePage` → `resources/views/panel/pages.blade.php`:** conteúdos de **Termos de Uso, Política de Privacidade e Contato**. O menu lateral fica em **Site → Páginas institucionais**.
- **`/admin/site?section=footer`:** textos/títulos dos links do rodapé via `PublicSiteContent::sections()`.
- **`/admin/config/sections/footer`:** exibição e destinos de links de rodapé via configuração administrativa.

São **funções diferentes no código**; não renomear/excluir/consolidar indiscriminadamente. Validar com usuário a nomenclatura e o caminho de navegação, em especial no mobile.

**Risco técnico encontrado durante a revisão (não homologado manualmente):** `AdminController::editSitePage` atualiza `terms`, `privacy`, `contact` e `register` num mesmo POST, mas a view `panel/pages.blade.php` examinada contém campos visíveis somente para os três primeiros. Auditar se o campo `register` ausente no POST pode sobrescrever indevidamente conteúdo existente por `null`. A página tem três botões Salvar dentro de um mesmo formulário, portanto confirmar o escopo do salvamento antes de modificar o fluxo. **Não alterar dados reais durante auditoria**.

## Próximo ciclo de implementação, sem mexer na produção agora

1. Priorizar correções pequenas e rastreáveis: busca sem card (Nº 01) e retorno após salvar usuário (Nº 02), com atualização dos testes pertinentes.
2. Adaptar o X para FAB (Nº 03), mantendo acessibilidade e boa usabilidade mobile.
3. Inventariar as listas relevantes; desenhar padrão compartilhado de paginação + tamanho de página (Nº 04) sem duplicar o Livewire existente.
4. Inventariar mensagens e centralizar toasts via layout/componentes compartilhados (Nº 05); preservar validação inline onde necessária.
5. Analisar navegação institucional e o possível `register` implícito; propor correções somente após prova de impacto.
6. Rodar suites **direcionadas** em fixture isolada, registrar resultados e somente depois considerar PR, merge, `--check` e deploy separados.

**Situação final:** homologação manual **parcial**; cinco novas correções numeradas pendentes + revisão institucional adiada. Os testes CI anteriores permanecem válidos para a revisão original, não aprovam as correções novas.
