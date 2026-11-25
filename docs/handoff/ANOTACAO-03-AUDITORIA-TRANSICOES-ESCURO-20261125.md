# Anotação Nº 03 — Auditoria cirúrgica das transições no tema escuro

**Data:** 25/11/2026  
**Repositório:** sistemacodigolucrativo/LinkFree-LinkTree  
**Branch:** `test/dark-transition-filmstrip-20261125`  
**Estado:** primeira execução encontrou flash claro no reload; correção cirúrgica implementada apenas no bootstrap de tema, **aguardando reexecução**. Sidebar não modificada; nenhuma alteração em produção.

## 1. Verificação prévia: não repetir verificações existentes

O head da PR #34, `beb7e5cfbe5981b2f74e421c7865de47ba68e533`, foi integrado à `main` em `8fad67d5036807488525ce0a313d047f062ac90e`. O último commit documental da main anterior à auditoria é `ee05750f571c66b352ea584b47d1029be6cdc566`.

| Teste/escopo já realizado | Evidência | Decisão |
|---|---|---|
| 23 testes de HTTP: autenticação, CSRF, rotas, permissões e salvamento | [HTTP 23 PASS / 0 FAIL](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45684365852/job/139452917215) | Reutilizar |
| 24 testes Chromium: hub + 10 seções em desktop claro e celular escuro, persistência de alteração sintética | [Chromium 24 PASS / 0 FAIL](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45684365852/job/139452916796) | Reutilizar |
| Regressão, lint, SEO, layout e instaladores SQLite/MySQL | Checks `success` da PR #34 | Reutilizar enquanto os arquivos testados não forem alterados |
| Exame `browser-a11y` completo | `skipped`, execução manual | Não interpretar como aprovação |
| Captura de quadros **durante** navegação/post/reload para detecção de flash | Busca por `Page.startScreencast` e `screencastFrame` não identificou harness anterior | **Lacuna comprovada** |

**Critério de reutilização:** se uma alteração posterior mudar um arquivo ou comportamento diretamente abrangido por um desses testes, a evidência anterior deixa de ser suficiente para aquele escopo. A primeira revisão adicionou apenas harness e documentação. **Após a falha observada no reload, esta PR passou a modificar exclusivamente `assets/js/detect-dark-mode.js`**, que é parte direta do mecanismo de tema. Por isso os testes diretamente afetados, incluindo o Chromium de configurações e as verificações de modo claro/escuro/automático, devem ser executados novamente. As evidências HTTP de rotas e banco permanecem reaproveitáveis quanto ao seu escopo, embora o CI também possa dispará-las automaticamente. O runner original mantém a execução anterior quando `QA_DARK_TRANSITION_ONLY` não está configurado.

## 2. Mecanismos existentes — não corrigir por presunção

- `resources/views/layouts/sidebar.blade.php`: estilo inline anterior ao CSS com `html.lf-prepaint-dark{background:#222738}`, `body{background-color:inherit}`, ocultação de `#loading`, `@view-transition{navigation:auto}` e script precoce de preferência `color-mode`.
- `assets/js/plugins/setting.js`: restaura `color-mode`, sincroniza `body.dark` com `html.lf-prepaint-dark`, acompanha preferências de sistema e permite alternância via controles.
- `assets/css/linkfree-color-modes.css`: tokens do modo escuro para superfícies, formulários, cartões, menus e overlays, com escopo autenticado.
- `resources/views/layouts/guest.blade.php` não contém a mesma regra inline antecipada; isso não prova um flash e não autoriza alterar o layout de convidados sem reprodução do problema naquele escopo.
- A tela de configurações modular foi validada **após o carregamento**, mas essa evidência não amostra os quadros de transição.

## 3. Técnica acrescentada (base oficial atual)

- **Playwright CDPSession** interage com o Chrome DevTools Protocol apenas no Chromium: https://playwright.dev/docs/api/class-cdpsession
- **CDP Page.startScreencast / Page.screencastFrameAck** captura e confirma quadros reais de composição: https://chromedevtools.github.io/devtools-protocol/tot/Page/
- O Chrome documenta transições entre documentos da mesma origem ativadas com `@view-transition{navigation:auto}`, mas **recarregamentos explícitos não entram no mesmo mecanismo**; por isso são observados separadamente: https://developer.chrome.com/docs/web-platform/view-transitions/cross-document
- A inspeção de quadros e paint flashing é recomendada no Chrome DevTools para diagnosticar renderizações inesperadas: https://developer.chrome.com/docs/devtools/rendering/performance

A suíte `scripts/qa-isolated/dark_transition_filmstrip.cjs` trabalha exclusivamente em `http://127.0.0.1:18765`, com SQLite, contas sintéticas, e-mail em log e `APP_ENV=local`. Ela reutiliza `run_config_browser_smoke.sh` e seu provisionamento. Um job dedicado `dark-transition-filmstrip` evita reexecutar manualmente o conjunto de 23+24 testes já homologado.

**Casos novos:**

1. Navegação com clique real no hub → aplicação, tema escuro, viewport 375 px.
2. Salvamento real de toggle sintético, redirecionamento HTTP 302 e retorno à aplicação, viewport 375 px.
3. Recarregamento `reload` da aplicação após o salvamento, viewport 375 px.
4. Navegação com clique real no hub → aplicação, tema escuro, viewport 1280 px.

**Oráculo e evidências:** em cada captura por CDP, amostras distribuídas no viewport detectam se um quadro tem 70% ou mais de pixels quase brancos (RGB ≥ 235); também se verifica a classe de pré-pintura, a classe escura, fundo computado do `html` e overflow. O relatório registra quadros coletados, pior proporção branca e PNGs do primeiro, do pior e do último quadro. Capturas insuficientes são falha/inconclusão, nunca aprovação silenciosa.

**Limitação metodológica:** a captura por CDP pode omitir frames entre amostras e o limiar detecta principalmente piscadas de tela predominantemente branca, não todo artefato sutil. Portanto, nenhum resultado desta suíte isoladamente demonstra ausência absoluta de flash em todos os navegadores, GPUs, aparelhos, rotas e circunstâncias. Não usar esse teste como substituto de WCAG nem do procedimento manual com dispositivo físico.

## 4. Regras de encerramento

Somente após logs e PNGs objetivos é permitido classificar os casos executados como **PASS** ou **FAIL**. Se houver frames brancos, investigar a causa antes de modificar código; se forem todos verdes, registrar que o fluxo representativo foi aprovado, mantendo pendências restritas aos ambientes não cobertos.

## 5. Primeira execução — diagnóstico real e correção localizada

**PR:** [#35](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/pull/35)  
**Execução inicial:** [Run 45687935291](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45687935291)  
**Artefato:** `linkfree-dark-transition-filmstrip-synthetic`, id `15057536973`.

| Caso | Quadros válidos | Máxima porcentagem quase branca | Resultado |
|---|---:|---:|---|
| Navegação móvel 375px tema escuro | 43 | 17,65% | PASS |
| POST + redirecionamento móvel | 52 | 0% | PASS |
| Reload móvel 375px | 38 | **98,93%** | **FAIL** |
| Navegação desktop 1280px escuro | 40 | 15,51% | PASS |

O PNG `reload-375-worst.png` foi inspecionado: mostra a barra lateral e a área central temporariamente no **visual do tema claro** durante o reload, e não apenas uma página branca vazia. O primeiro e o último frame do mesmo caso mostram fundo `#222738`, comprovando um estado intermediário claro. Assim, a hipótese inicial de simples erro do gravador de frames ficou enfraquecida.

**Causa técnica correlacionada com o código:** `layouts/sidebar.blade.php` já aplicava a classe `lf-prepaint-dark` no elemento `html` cedo, mas a paleta de componentes em `linkfree-color-modes.css` depende de `body.dark`, que era aplicado pelo `setting.js` carregado no rodapé. Isso permitia pintar os componentes com o tema claro durante a reconstrução do DOM.

**Correção cirúrgica:** no arquivo `linkstack/assets/js/detect-dark-mode.js`, carregado no `head`, pré-aplicar `html.lf-prepaint-dark` e registrar `MutationObserver` que adiciona `body.dark` assim que o elemento `body` nasce, antes da próxima oportunidade de renderização. Preservam-se os demais atributos e classes; `setting.js` continua responsável por controles e mudanças de tema. A regra do usuário de **não modificar a Sidebar** permanece integralmente atendida.

**Regressões exigidas após a alteração direta:** reexecutar as quatro capturas, verificar preferência automática `auto` com sistema em claro e escuro, e a suíte Chromium de configurações (o script de bootstrap é compartilhado). Não declarar sucesso até examinar os logs da nova revisão; se houver flash residual, revisar os quadros antes de novo ajuste.

**Sem merge, sem deploy nesta etapa de validação.** A implementação e os resultados devem permanecer rastreáveis e não se deve afirmar que a anomalia acabou em todos os navegadores e dispositivos com base apenas em Chromium isolado.
