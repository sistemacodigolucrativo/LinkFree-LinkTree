# LinkFree — nova sessão: Painel de Membros (anotações 01–02)

**Registro:** 26/11/2026 (horário UTC); solicitação apresentada em 25/11 no horário de Brasília.  
**Base inicial:** `main` no commit `43e175081b55262dec70b9628593e03d505d93ff` (PR #37 já integrada).  
**Escopo autorizado:** somente os títulos visuais duplicados do Painel de Membros e o seletor de plataforma no fluxo Adicionar Bloco. Não alterar menu lateral, permissões, dados, schemas, rotas ou outros elementos sem necessidade comprovada.

## ANOTAÇÃO Nº 01 — Remoção de títulos duplicados e reorganização do conteúdo

**Local:** Painel de Membros → todas as páginas afetadas → Topbar e conteúdo principal.

**Problema:** títulos de páginas agora aparecem corretamente na Topbar, mas algumas views continuam exibindo seus cabeçalhos antigos, duplicando o texto e reservando espaço indevido, sobretudo em telas mobile.

**Implementação pedida:**
1. Mapear para cada rota o título já renderizado pela Topbar e conferir os cabeçalhos de conteúdo legados.
2. Remover **somente** o cabeçalho visual que duplica o título da Topbar; manter headings de seções, rótulos, estrutura semântica útil, ajuda e controles de formulário.
3. Retirar margens/paddings/espaço artificial relacionados ao heading removido e deixar o card/conteúdo subir naturalmente, especialmente no mobile.
4. Preservar Topbar, identidade visual, menus, interações, tabelas, CTA, navegação e permissões.
5. Validar em larguras mobile/desktop, temas claro/escuro, inclusive que nenhum conteúdo fique sobreposto ou oculto.

**Critério de aceite:** para cada rota afetada, somente a Topbar traz o título de página visível; o conteúdo mantém seus títulos internos quando exercem função distinta e fica alinhado sem lacuna decorrente do heading removido.

**Status:** IMPLEMENTADO na branch — validação automatizada dirigida aprovada, homologação real pendente.

## ANOTAÇÃO Nº 02 — Ícones oficiais e preview interativo no seletor de plataformas

**Local:** Painel de Membros → Adicionar Bloco → «Selecionar ícone do botão» → lista de plataformas.

**Problema:** plataformas como Apple Store, Amazon, Apple Books, Deezer e Discord estão identificadas textualmente, mas a lista não oferece os respectivos ícones nem uma prévia fiel do botão antes da seleção.

**Implementação pedida:**
1. Preservar todas as opções e nomes existentes, acrescentando à frente de cada item o ícone disponível que corresponda à plataforma.
2. Primeira ação de clique/toque/teclado abre a área expandida **sem selecionar** o item nem disparar o formulário.
3. A expansão mostra a prévia do botão da plataforma com ícone, cor, tipografia, borda, dimensões e estilo personalizados, preferencialmente aproveitando as mesmas fontes de dados e CSS usados na página pública.
4. Exigir segunda ação explícita de confirmação para gravar a seleção; fornecer recolhimento/troca de item e estados `aria-expanded`, navegação por teclado e foco visível.
5. Preservar lista completa, nomes, interação de mobile, lógica de submissão e comportamento dos demais blocos; evitar overflow e visual genérico quando existir estilo específico.

**Critério de aceite:** abertura da plataforma nunca altera o valor selecionado; confirmação o altera uma vez; o preview corresponde à configuração real cadastrada no projeto, com fallback consistente onde não exista estilização específica.

**Status:** IMPLEMENTADO na branch — validação automatizada dirigida aprovada, homologação real pendente.

## Procedimento e evidências

- Criar branch isolada, documentar requisitos **antes** das mudanças de implementação.
- Identificar e registrar todas as views afetadas, rotas e dependências; usar mudanças mínimas comprovadas.
- Testes direcionados HTML/HTTP/Playwright/axe (se disponíveis), cenários teclado/touch, 320–1280px, claro/escuro.
- Registrar resultados verificáveis e pendências, sem equiparar CI sintético a aceite em produção.
- Não realizar merge nem deploy nesta etapa sem autorização posterior.

## Implementação realizada na branch (26/11/2026)

**Situação:** código implementado; aguardando conclusão da bateria de CI no head final, revisão visual das capturas e aceite autenticado em produção. A `main` não foi alterada.

### Nº 01 — mapeamento dos títulos removidos

- `panel/index.blade.php` — Painel de Controle.
- `studio/links.blade.php` — Links e Blocos; CTA `+ Bloco-Link` mantido numa área de ações independente e card identificado via `aria-label`.
- `studio/blocks.blade.php` — Gerenciar Blocos; mantidos cards de seleção, introdução e prévias.
- `studio/icons.blade.php` — Ícones da Página; formulário mantido.
- `studio/edit-link.blade.php` — Adicionar/Editar Bloco; formulário e botões preservados.
- `studio/page.blade.php` — Meu Perfil; editor de foto e controles preservados.
- `studio/profile.blade.php` — Informações da Conta; títulos internos Email/Senha/etc. preservados.
- `studio/theme.blade.php` — Fundos Personalizados; cabeçalho «Selecionar um tema» preservado por ser uma seção distinta.
- `studio/referrals.blade.php` — Minhas Indicações; histórico preservado.
- `studio/verification.blade.php` — Selo Verificado; SVG do selo mantido exclusivamente na Topbar.
- `studio/visitor-reports.blade.php` — Origem dos Visitantes; tabela preservada.
- `studio/button-editor.blade.php` — Editor de Botões; subcabeçalhos de configuração preservados.

Os títulos da Topbar são gerados por `layouts/sidebar.blade.php`, que não foi modificado. Espaços dos headings removidos desapareceram com os elementos; o card de links passou a ter toolbar somente com a ação. Não foram alterados a sidebar, routes, controllers, banco ou dados privados.

### Nº 02 — arquitetura implementada

- `components/pageitems/predefined-form.blade.php`: `select[name="button"]` original com a mesma origem `$buttons` e os nomes existentes, placeholder obrigatório para novas escolhas e metadados dos CSS ativos.
- `assets/js/linkfree-platform-picker.js`: enhancement progressivo; lista com SVGs, expansão/colapso com `aria-expanded`, prévia em iframe `sandbox=""` sem scripts e seleção por botão explícito. A primeira ação não altera o valor do `select`; a segunda dispara evento `change` para preservar a lógica do formulário (incluindo WhatsApp).
- `assets/css/linkfree-platform-picker.css`: estilos locais do seletor, scroll interno, foco visível, áreas de toque, adaptação mobile e movimento reduzido.
- `studio/edit-link.blade.php`: carregamento dos assets somente no editor e inicialização após o carregamento AJAX do formulário.
- Prévia reutiliza as mesmas classes públicas `button button-<plataforma> button-hover icon-hover` com `brands.css`, `skeleton-auto.css` e o diretório de ícones do tema ou os assets padrão.
- Inventário do `ButtonSeeder`: **104 plataformas ativas**; havia 103 SVGs equivalentes. O único ausente, Scribd, foi incluído em `assets/linkstack/icons/scribd.svg` a partir de [Simple Icons v15.0.0](https://github.com/simple-icons/simple-icons/blob/15.0.0/icons/scribd.svg), com licença CC0 (direitos de marca continuam pertencendo aos titulares). Nenhum CDN ou serviço externo foi adicionado.
- Sem JavaScript, o `select` nativo permanece utilizável e exige seleção explícita.

### Validações e limites

- A suíte `member-navigation-browser` em fixture descartável já registrou **45/45 PASS**, incluindo ausência de duplicação de heading nas rotas exercitadas, seleção Amazon/Discord por teclado, primeiro clique sem seleção, uso de `brands.css` e responsividade a 320/375/575/576/768/1024/1280 px. Essa evidência é do commit de desenvolvimento `4b5a76c`, anterior ao acréscimo final do ícone Scribd, da captura desktop expandida e à documentação.
- `studio-layout` aprovou a auditoria estática adaptada ao novo requisito de título exclusivo na Topbar.
- Os checks do head final devem ser revalidados antes da PR sair de draft. O `browser-a11y` global, configurado para execução manual, não é considerado aprovado por estar `skipped`.
- Validação autenticada na VPS/staging e em aparelhos físicos permanece uma etapa posterior, separada de CI sintético.
- **Não executar merge nem deploy nesta etapa sem autorização expressa.**

### Evidência após revisão visual final da interface

- A primeira captura do teste sintético revelou duas falhas específicas da apresentação: uma opção vazia inserida pela fixture de QA e baixo contraste dos SVGs brancos de Apple Store, Deezer e Discord na lista clara.
- O `seed.php` descartável passou a marcar o botão sintético como excluído, sem alterar os 104 botões de produção. O CSS do seletor acrescentou apenas um fundo escuro de contraste a esses três SVGs, preservando os próprios arquivos e a prévia do botão público.
- A execução de navegador do código revisado `3f6778c5d050a3dd1eeae7baef1761faa2c7019a`, no [job 139626027910](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45735009506/job/139626027910), confirmou **46 testes PASS / 0 FAIL** e capturas em `375px/light` e `1280px/dark`, incluindo a área expandida da plataforma e sua confirmação por teclado.
- As capturas revisadas mostram o item Apple Store, Deezer e Discord legíveis no tema claro e ausência de linha de plataforma vazia. A documentação e as capturas refletem o build sintético, não dados reais da VPS.
- Os demais workflows no mesmo head devem ser verificados antes de promover a PR. **Não houve merge nem deploy.**
