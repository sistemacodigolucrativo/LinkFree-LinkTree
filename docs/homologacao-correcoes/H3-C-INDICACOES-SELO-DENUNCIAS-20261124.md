# H3-C — indicações, selo e denúncias

Execução em 24/11/2026. A H3-B foi revisada e aprovada no escopo sintético. Este relatório é um checkpoint acumulado; não altera a matriz histórica nem representa aceite integral dos 43 testes.

## Ambiente

Código de aplicação `6e55da55e57203da2606a4b1ca1c83865c0f760d`, 602 arquivos de código não persistente conferidos sem divergência na fixture e sem diferença em `linkstack/` em relação à base documental `9edfe536e584da15c051cee03414168677cff542`. Staging derivado da fixture H2 com quatro contas fictícias, SQLite `quick_check=ok`, sem violações de chaves estrangeiras, storage e chave sintéticos. Namespace de rede sem rota externa e montagem ocultando `/srv/linkfree`, backups, `/home/ubuntu` e `/run`; servidor local não exposto. Chromium headless usou rede isolada. Nenhum deploy, acesso mutável a banco de produção ou dado real.

## C1 — indicações: checkpoint concluído

| Caso | Evidência nova e resultado | Limite |
|---|---|---|
| TC-05-A | Chromium: botão de criação, código de 24 hex, cópia para clipboard de navegador, reload e nova sessão preservaram URL; outro membro obteve código distinto. | URL em HTTP isolado; HTTPS e código já provados na PR #15. Clipboard em dispositivo físico não comprovado. |
| TC-05-B | Convite em sessão anônima seguido de cadastro com aceite criou exatamente uma atribuição (+1); histórico do proprietário mostrou handle e data sem email; tela Admin filtrada mostrou a mesma pessoa. | OAuth real permanece para H5. |
| TC-05-C | Convite inválido em nova sessão anônima 404; membro autenticado redirecionado e login de conta existente, com reload, não criaram atribuição. | Reaproveitados testes históricos de expiração simulada, bloqueio e autoindicação; não foram repetidos. |
| TC-05-D | Proprietário viu seu histórico, outro membro não viu o convidado e membro não acessou painel Admin; filtro Admin identificou a indicação. | Paginação, unicidade e FK reaproveitadas das evidências históricas. |

[Resultados sanitizados](evidencias-h3c/c1-resultados.json); [harness Chromium](evidencias-h3c/c1.cjs). Provas anteriores por caso: [matriz histórica da PR #15](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/blob/361c4cb4d4da9db98039d58e10834ad8541f40a7/docs/homologacao-automatizada/MATRIZ-RASTREABILIDADE-43-TESTES.md), e [schema H3-B](evidencias-h3b/schema-resultados.json). A primeira tentativa do cenário inválido usou por engano uma sessão já autenticada; nessa situação o contrato redireciona. Em sessão realmente anônima retornou 404, sem defeito demonstrado. A primeira tentativa de automação do login também foi ajustada por seletores ambíguos, sem resultado de produto inferido.

## C2 — selo: checkpoint concluído no escopo exercitado

| Caso | Evidência nova e resultado | Limite |
|---|---|---|
| TC-06-A | Pedidos Free e VIP ficaram pendentes após reload, sem controle de visibilidade antes da aprovação; dois pedidos distintos persistiram. | Duplicação pendente e negativa anterior à aprovação reaproveitadas da PR #15. |
| TC-06-B | Admin aprovou pedido Free e recusou VIP com justificativa; membro aprovado recebeu controle, recusado viu motivo; dois eventos com ator Admin persistiram. | Dados e decisões fictícios; revisão humana de legados fica em H5. |
| TC-06-C | Na coluna Selo da lista Admin, concessão direta e revogação de outro membro refletiram as badges correspondentes. | Sem alteração de conta real. |
| TC-06-D | Com tema sintético compatível, Exibir gerou SVG junto ao nome; Ocultar e revogação removeram o SVG; controle desapareceu após revogação. | DOM de Chromium headless; não é avaliação visual humana. |
| TC-06-E | Decisões preservaram papel VIP e verificação de email do membro VIP; concessão/revogação direta preservou papel Free. | Nenhuma decisão retroativa sobre legados. |

[Evidência JSON](evidencias-h3c/c2-resultados.json), [harness](evidencias-h3c/c2.cjs) e [matriz histórica da PR #15](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/blob/361c4cb4d4da9db98039d58e10834ad8541f40a7/docs/homologacao-automatizada/MATRIZ-RASTREABILIDADE-43-TESTES.md). Não se repetiram testes equivalentes de regras e duplicação.

## C3 — denúncias: checkpoint concluído, com limites de localização

| Caso | Evidência nova e resultado | Limite |
|---|---|---|
| TC-07-A | Ícone ausente/desligado e presente/ligado; formulário público para perfil e URL gerou confirmação, registro e evento, sem bloquear alvo. | Somente ambiente sintético. |
| TC-07-B | Campos URL/motivo obrigatórios, limites HTML 2048/2000 e 14 opções observados no Chromium. Com `LOCALE=pt-BR` e HTML `lang="pt-BR"`, rótulos apareceram em inglês: defeito de localização confirmado. | CSRF, throttle, limites no servidor e XSS já comprovados na PR #15; não houve inspeção visual humana. |
| TC-07-C | SMTP de captura local recebeu mensagem contendo caminho da central e sem referência a exclusão; registro e evento persistiram. | Falha SMTP já demonstrada na PR #15; entrega real externa não comprovada. |
| TC-07-D | Admin visualizou fila filtrada e detalhe; membro foi impedido de abrir detalhe. | Paginação, totais e histórico reaproveitados da PR #15. |
| TC-07-E | Em análise persistiu; resolver sem nota foi negado; resolver, arquivar e reabrir persistiram, com quatro novos eventos com ator/data. | Somente cenário sintético; não foi repetida concorrência já coberta historicamente. |
| TC-07-F | Alvo permaneceu desbloqueado após recebimento e transições. | Exclusão controlada e preservação histórica reaproveitadas da PR #15; retenção LGPD e direitos do titular dependem de decisão humana. |

[Evidências JSON](evidencias-h3c/c3-resultados.json), [harness de fluxo](evidencias-h3c/c3.cjs), [harness do formulário](evidencias-h3c/c3-form.cjs) e [matriz histórica PR #15](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/blob/361c4cb4d4da9db98039d58e10834ad8541f40a7/docs/homologacao-automatizada/MATRIZ-RASTREABILIDADE-43-TESTES.md). A captura local não equivale a SMTP real. Não há falha P0/P1 demonstrada nesta execução.

## Próximo passo

C1 e C2 concluídos no escopo exercitado; C3 concluído com defeito de localização em TC-07-B. Após revisão pelo ChatGPT, avaliar correção do defeito de localização de TC-07-B e programar H3-D conforme roteiro futuro. OAuth e SMTP reais, HTTPS, dispositivos físicos, acessibilidade humana, legados e retenção permanecem em fases apropriadas; H3-D não foi iniciada. TC-02-C e matriz histórica permanecem inalterados.

## Achado C3-L1 — tradução ausente no formulário de denúncias

**Confirmado; gravidade baixa (P3), confiança alta.** Com `LOCALE=pt-BR` na cópia isolada, GET `/report` renderizou HTML com idioma `pt-BR`, mas rótulos como “Type of report” e “Additional comments” em inglês. O template usa chaves `messages.report_type_label` e `messages.additional_comments_label`; essas chaves e `report_violation`, `url_label`, `submit_button`, `report_success` estão ausentes de `linkstack/resources/lang/pt-BR/messages.php` e existem em `en/messages.php`. A configuração `fallback_locale=en` explica a observação. Impacto: formulário parcialmente em inglês para usuários da configuração portuguesa. Recomendação: acrescentar traduções pertinentes e retestar em ambiente isolado; nenhuma correção foi feita nesta auditoria.
