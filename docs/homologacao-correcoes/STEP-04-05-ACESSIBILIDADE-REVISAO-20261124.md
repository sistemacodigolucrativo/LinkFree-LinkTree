# PR #16 — STEP 04 / STEP 05
Data: 2026-11-24. Base de trabalho: e09e3033e34d7c3cf11a874508f230fd21ea54a2; base de produção não verificada por SSH. STEP 03 concluído, CI 45443009426 (20/20 HTTP, 6/6 browser, 4/4 em cada instalador).
## Reutilização de testes
Manter os 12 scans Chromium, 6 interações e 20 casos HTTP como baseline; a execução anterior comprovou 11/12 sem violações, perfil /@auditfree com 3 nós. Não reexecutar testes históricos PR #15 indiscriminadamente. O CI existente roda regressão ao alterar a view pública; o novo teste amplia lacunas, não substitui evidências. Instaladores não tiveram alteração nesta etapa, mas workflow atual os repete como gate de integração.
## STEP 04 — candidato inicial
- Hipóteses de causa: a animação fadein altera opacidade por 3s, e os botões sintéticos não possuem classe de marca conhecida; auditoria anterior fez scan após 500ms. Não considerar causa única confirmada sem reteste. Fallback neutro nos botões sem regra, opacidade de entrada legível e foco visível com dupla borda; responsividade localizada.
- Reaproveitar a validação de pares em ThemeOptions (4.5:1) e complementar na renderização com guard de cores computadas, incluindo preferências parciais/outline, gradiente conhecido e superfície local opaca quando fundo não pode ser avaliado. Não gravar ou substituir preferências do membro. Não prometer contraste de todo tema de terceiros/HTML arbitrário, imagens ou leitor de tela sem validação específica.
- Matriz nova: 3 motores (Chromium/Firefox/WebKit), 7 cenários por motor (default claro/escuro, PolySleek, galaxy, parcial, legível, outline), 320/390/1280px, foco por Tab, overflow e axe. WebKit Linux não equivale a Safari/iOS real. Scanner passa a reprovar qualquer violação encontrada nas 12 páginas, não apenas lista direcionada.
- Pendente: logs, diagnóstico e ajuste de falhas, navegação por teclado nos formulários/painéis, comparação de temas reais e validação humana/dispositivos físicos. Verificar contraste e foco também em estados interativos e com imagem de fundo. Não declarar WCAG integral.
## STEP 05 — inventário preliminar
Comparação main 342fce9b → e09e3033: 25 commits, 43 arquivos. Grupos: workflows e testes, segurança dos controllers/rotas/middleware, UI admin/membro/publica, três relatórios. Revisar diff e compatibilidade de deploy e recuperação sem executar. Gates: resultados do SHA candidato, nenhuma falha crítica no escopo, riscos manuais documentados, plano de implantação/rollback, autorização expressa separada para merge e deploy. PR #15 e main preservadas.
## Estado
STEP 04 iniciado, candidato em validação; STEP 05 iniciado (inventário e reuso de evidências). Sem merge/deploy/VPS.

## Primeira execução ampliada — 388b80f8
[CI 45448305974](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45448305974): 12/12 páginas sem violação axe, 0 dos 3 nós de contraste anteriores, HTTP 20/20, interações 6/6, instaladores 4/4 cada, lint/layout/regressão aprovados. Matriz 18/21: os três erros PolySleek eram comparação de innerText (transformação visual uppercase) com texto minúsculo; comparar textContent, sem mudar tema. Acrescentados cenário com PNG preto/branco real, medição independente de luminância WCAG do título/botão/hover e sem motion reduzido no primeiro cenário. Incompletos axe de contraste em gradientes não equivalem a falhas nem a aprovação completa: medir alvos e registrar revisão humana. Correções pontuais de labels/alt nos formulários de perfil, tema e editor Admin. Reteste obrigatório. 

## Complemento de formulários e STEP 05
Adicionada matriz de seis cenários Chromium em perfil/tema Membro e editor Admin, claro 1280px/escuro 390px, axe, largura e Tab; teste do seletor de esquema com teclado e aria-pressed sincronizado pelo setting.js existente. Sem alterar valores de negócio dos formulários. [Plano de revisão e liberação](STEP-05-PLANO-REVISAO-DEPLOY-ROLLBACK-20261124.md). Candidato ainda requer CI completo; não repetir testes históricos indiscriminadamente.

## Segunda execução — 72fcc555
[Browser 45450019655](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45450019655/job/138644199922) e push correspondente: 12/12 scans sem violações, seis interações aprovadas, matriz 22/24. Os três cenários PolySleek passaram. Firefox/WebKit com imagem falharam somente por ausência do marcador interno após o contraste numérico ter passado; não considerar aprovação final até reteste. Novo candidato restaura explicitamente os longhands que o guard modificou, evita transição de cores durante recálculo, testa a superfície opaca real e decodificação do PNG, e troca claro/escuro no mesmo DOM para detectar ajustes obsoletos. Não remove assertiva de contraste 4.5:1.

## Complemento — resultados dos formulários
[CI 45450239880](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45450239880), candidato 6dcfa6e6: HTTP 20/20, 12 scans sem violações, matriz pública 22/24 no PR (21/24 no push; Firefox parcial teve uma ocorrência de branco sobre branco, justificando correção de restauração/transição). Formulários 4/6 no PR, 5/6 no push: modo escuro mobile /studio/page apresentou contraste 2.27 no logo lateral em uma execução; seletor de esquema teclado expirou em ambas. Corrigidos no candidato seguinte: transições de cor/superfície no sidebar deixam de interpolar paletas; reduced motion respeitado; teste aguarda offcanvas totalmente aberto, confirma foco real, Space/Enter e estado persistido/aria-pressed, com diagnóstico específico. Não descartar a falha intermitente; retestar ambos.

## Resultados da matriz pública e último caso de teclado
[CI 45451178610](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45451178610/job/138648366383) e [push](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45451153871/job/138648279896): matriz pública **24/24** em ambos, 12 scans sem violação, mínimo medido 8.196:1 nos alvos título/botão/hover; imagem decodificada, superfície opaca real e reversão de esquema aprovadas. [HTTP 20/20, incluindo asset](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45451178610/job/138648366901). No push seguinte 03622649, [24/24 mantidos, formulários 5/6](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45451383729/job/138649113474), logo lateral sem nova reprovação; teclado falhou com foco correto, Space não alterou body/localStorage/aria-pressed. Correção: handler explícito Space/Enter usando a mesma função do clique e prevenção de acionamento duplicado, listeners instalados antes das preferências opcionais. Teste mantém teclado real, foco, persistência e aria-pressed; eventos diagnósticos se falhar. Não concluir até prova.

## ENCERRAMENTO TÉCNICO — STEP 04 e STEP 05 — 24/11/2026
**Código validado:** 1d5a4bc3688f7bfb6d7906b55088da4a20b36ca8. Merge sintético de CI da PR: ee24b896631b2ca2e961e9bc0a55c3d5936417df. [Execução PR](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45452596718) e [execução push](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45452573437), ambas com todos os jobs do escopo aprovados. Logs efetivamente consultados; não confundir com produção.

| Evidência | Resultado |
|---|---|
| [HTTP](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45452596718/job/138653421019) | 20/20, zero falhas, incluindo propriedade e assets de blocos |
| [Browser](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45452596718/job/138653421900) | 6/6 interações funcionais, 12/12 páginas sem violações axe detectadas, 0 erros, 0 contraste residual original |
| Matriz perfil | 24/24 (8 por motor Chromium, Firefox, WebKit), 320/390/1280 px, claro/escuro/temas e preferências parciais/outline/imagem |
| Matriz formulários | 6/6, claro/escuro, mobile/desktop, Tab/foco, Space/Enter no seletor, aria-pressed e persistência |
| Contraste numérico independente | mínimo observado **8.196:1** nos alvos título, botão e hover das fixtures; limite exigido 4.5:1, não é valor mínimo de toda a aplicação |
| Instalação SQLite | 4/4 |
| Instalação MariaDB | 4/4 |
| PHP, regressão estática, layout | aprovados |
| Recuperação isolada de arquivos | aprovada, com os limites descritos no plano STEP 05 |

Artefatos CI (retenção 30 dias): [browser 14946814836](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45452596718/artifacts/14946814836), [HTTP 14948653755](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45452596718/artifacts/14948653755), SQLite 14949325988, MariaDB 14947008932. Evidência permanente: links de execuções e resumo versionado. A correção final de Space/Enter foi aprovada tanto no push quanto no PR. Ensaio local DOM complementar com armazenamento simulado confirmou um único ChangeMode por acionamento; não substitui integração.

### Mapeamento das 10 atividades do STEP 04
| Nº original | Atividade | Estado |
|---|---|---|
| 12 | Diagnosticar contraste | Concluída: opacidade, fallback de botão, ajustes de tema e estados |
| 13 | Corrigir os três nós originais | Concluída: 0 violações em 12 scans |
| 14 | Guarda compatível com cores personalizadas | Concluída no escopo dos alvos testados, inclusive imagem e alternância de esquema |
| 15 | Claro/escuro | Concluída na matriz sintética |
| 16 | Teclado e foco | Concluída nos fluxos testados, Space/Enter/Tab |
| 17 | Formulários e controles | Concluída nos seis cenários autenticados |
| 18 | Responsividade | Concluída nos tamanhos testados e regressão de layout |
| 19 | Chromium/Firefox/WebKit | Concluída 24/24 |
| 20 | Safari/iOS e dispositivo real | **Não executada: ambiente físico não disponível**; gate externo |
| 21 | Reteste axe e documentação | Concluída, evidências acima |

**STEP 04: 9/10 atividades comprovadas; escopo automatizado encerrado.** Limites: axe marcou `color-contrast` como incompleto em nove cenários públicos com gradiente/outline (os alvos originais têm medição independente), e `aria-prohibited-attr`/contraste como incompletos nos seis cenários de painel; esses pontos exigem inspeção humana e não significam conformidade WCAG integral. WebKit Linux não é Safari/iOS físico. Não houve auditoria de todos os temas externos, leitor de tela ou HTML personalizado arbitrário. TC-09-B: correção e regressão automatizadas aprovadas, aceite externo continua aberto. Não reclassificar matriz histórica da PR #15.

### Encerramento da revisão STEP 05
[Plano e parecer](STEP-05-PLANO-REVISAO-DEPLOY-ROLLBACK-20261124.md). Revisão interna completa com parecer condicionado aos gates externos e autorização separada. **Não houve merge, deploy, SSH, alteração de dados reais ou migração de produção.** O commit documental seguinte não altera código, portanto preserva a evidência do SHA acima sem exigir nova repetição por conveniência; checks eventualmente disparados automaticamente pelo GitHub são independentes.
