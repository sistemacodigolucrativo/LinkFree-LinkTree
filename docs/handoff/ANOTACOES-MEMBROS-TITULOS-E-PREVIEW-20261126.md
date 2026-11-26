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

**Status inicial:** PENDENTE — diagnóstico, implementação e testes.

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

**Status inicial:** PENDENTE — inventário das fontes de ícones/modelos, implementação e validação.

## Procedimento e evidências

- Criar branch isolada, documentar requisitos **antes** das mudanças de implementação.
- Identificar e registrar todas as views afetadas, rotas e dependências; usar mudanças mínimas comprovadas.
- Testes direcionados HTML/HTTP/Playwright/axe (se disponíveis), cenários teclado/touch, 320–1280px, claro/escuro.
- Registrar resultados verificáveis e pendências, sem equiparar CI sintético a aceite em produção.
- Não realizar merge nem deploy nesta etapa sem autorização posterior.
