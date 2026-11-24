# PR #16 — STEP 03: segurança residual e reteste funcional
Data: 2026-11-24. Base anterior: 0bfb563ac91f524dddb17dd37306870f662d21e4. Escopo: TC-04-D, ambiente isolado, sem deploy.
## Diagnóstico comprovado do CI anterior
[Run 45433349200](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45433349200): HTTP 8/9; criação sem CSRF devolveu 302, pois Handler.php redireciona TokenMismatchException ao login. A assertiva exigia 419 antes de conferir persistência, logo o run não comprova bypass. Novo teste exige ausência de mutação e 419 ou 302 especificamente ao login, também token inválido. Browser 2/3: criação Admin e exclusão Admin passaram; clique Membro timeout com log truncado. Não atribuir causa sem hit-test e nova execução.
## Mudanças propostas
- Alvo e operação vindos da rota, evitando sobreposição por dados de corpo/consulta nas mutações cobertas pelo middleware link-id.
- Exclusão de conta com transação, sem desativar FK; protege ID 1/administrador corrente, exige dono no Membro e nega exclusão durante impersonação. Preserva hooks de histórico existentes; não redefine política LGPD.
- Impersonação antes baseada em auth_as/remember_token global; agora restrita à sessão, login/retorno autorizados, sem token de autenticação exposto no HTML. Estado legado ignorado (sem migração nem limpeza de produção); validar compatibilidade e revogação.
- Remoção de mídia com listagem fresca de arquivos e prefixo exato, evita loops com cache local; não remove symlinks/arquivos de outras contas.
- Exclusão de links Membro trata submit do formulário, mantém DELETE+CSRF e confirmação, ajuste local do flex. Browser espera actionability real e registra hit-test, não força clique.
- Proteção do atalho /skip (já bloqueado no Nginx documentado) no aplicativo. Rotas GET públicas de cliques/visitas, convites e callbacks OAuth/verificação assinada têm efeitos intencionais, não reclassificar como mutações administrativas. Instalador /mysql-test e catch-all que remove marcador INSTALLING são mutáveis e condicionados ao modo de instalação; revisar em ambiente instalador separado, não executar migrações reais nem alterar esse fluxo sem reteste apropriado.
## Mapeamento STEP 03 — esperado e prova
| Nº | Teste/ação | Estado |
|---|---|---|
| 01–02 | CSRF ausente/inválido, GET 405, member negado, admin cria exatamente um | preparado, CI pendente |
| 03–04 | clique real, cancelamento, confirmação, feedback, reload, isolamento | preparado, CI pendente |
| 05 | exclusão própria/Admin/AJAX, root protegido, FK, isolamento | preparado, CI pendente |
| 06 | impersonação start/return, sessão independente, CSRF, bloqueio de escalada | preparado, CI pendente |
| 07 | bloqueio/desbloqueio, plano, e-mail, acesso negado | preparado, CI pendente |
| 08 | avatar, fundo, cache ícone, ícone de página, avatar/favicon admin | preparado, CI pendente; browser ícone e HTTP marca, preservando favicon.svg |
| 09 | GETs mutáveis e exceções intencionais | revisão estática parcial; instalador e outros controllers ainda a revisar |
| 10 | login/logout, acesso visitante/admin, persistência entre sessões | preparado, CI pendente |
| 11 | regressão e reteste no SHA novo | CI pendente |
## Reprodução e segurança
Workflow qa-functional-isolated.yml, runner GitHub, SQLite descartável e localhost; scripts/qa-isolated/http_acceptance.py e browser_interactions.cjs. Logs/JSON a verificar no novo SHA. Os casos originais da PR #15 não mudam de classificação. Acessibilidade residual TC-09-B permanece no STEP 04. Não declarar STEP 03 concluído enquanto testes ou lacunas restantes não forem comprovados.

## Segunda revisão
O primeiro candidato 61b10090d4016a5c2911b86305d62950c15c28c0 apresentou erro sintático no UserController (bloco residual duplicado); corrigido antes de novo candidato. Adicionados retestes de marca/ícone de página, impersonação e exclusão administrativa no navegador. Verificação de e-mail agora grava timestamp válido. Negação de admin com Accept JSON usa 403; redirects HTML continuam. Nenhum resultado de runtime novo aprovado até logs do candidato final.

## Terceira revisão — resultados confirmados e proteção residual
No SHA e51c66a7dc01db21ae3efc88421d85f59e7a1c7b, [HTTP 16/16, zero falhas](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45436916182/job/138597109172); PHP lint, layout e regressão estática também passaram. Browser ainda em execução quando consultado. A revisão de rotas revelou acesso ao formulário de bloco por ID sem autorização de proprietário e execução de manutenção legada por query GET (atualização de arquivos/código, migração e limpeza/geração de backups). Candidato subsequente: validação do tipo e dono do bloco, contenção de caminho de assets, remoção da execução por GET dos dois views legados, preservação da navegação e consulta de backups existentes. A desativação da manutenção automática pela interface é proposta de segurança a confirmar antes do merge; não executamos backup, deploy ou atualização. Instalador MySQL e marcadores de instalação permanecem lacuna separada: demandam fixture de instalação virgem e teste próprio, nunca ativar em produção. Dois novos testes HTTP e ampliação das assertivas de alvo e GET; resultado do novo SHA pendente.

## Quarta revisão — causa do timeout de interface
[Browser no SHA e51c66a7](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45436916182/job/138597109766): 5/6 cenários de interação passaram (exclusão Admin, criação Admin, impersonação, ícone e exclusão conta Admin). Membro falhou em hit-test: o elemento .tooltip-inner de tooltip Bootstrap interceptou eventos de ponteiro sobre botão 34x34. Correção pontual na página de links: tooltip descritivo deixa passar eventos de ponteiro; não há clique forçado e a assertiva original permanece. Reteste do candidato subsequente obrigatório. Axe nas mesmas 12 páginas: 11 sem violações, 3 nós de contraste no perfil público (STEP 04).

## Fechamento — revisão adicional
No commit 13c89b11: [HTTP 18/18](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45437811146/job/138600316454) e [browser 6/6](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45437811146/job/138600316051), cinco workflows aprovados. Tooltip confirmado corrigido. Editor Admin: papel validado, administrador principal único autorizado a promover outro admin, root e papel próprio protegidos, remoção de avatar/fundo por POST com CSRF; política preserva administração de membros. Instalador: GET sem efeitos, POST protegido, bloqueio de esquema MySQL não vazio, configuração limitada, exclusão de marcadores apenas por sessão que criou root, mutex de operação. Manutenção web legada permanece desativada, consulta de backups preservada; procedimento VPS controlado é a política adotada nesta retomada. Instalação nova deve permanecer com ingresso restrito ao operador até concluir setup; não reativar marcadores em instância instalada. Candidato novo requer CI HTTP, browser, instalador SQLite e MariaDB antes de fechar STEP 03.

### Ajuste da fixture instalada
O primeiro reteste do candidato c5c80696 encontrou GET /login 404 porque os runners anteriores copiavam o marcador INSTALLING versionado e dependiam da remoção automática por GET. Agora os dois jobs que representam uma aplicação já instalada excluem INSTALLING e INSTALLERLOCK somente na cópia efêmera; o job de instalação virgem cria o marcador explicitamente. A guarda do instalador permanece ativa.

### Isolamento das rotas do instalador
No candidato 14ededab, teste MySQL identificou CSRF de instalação respondendo com status diferente de 419. A leitura completa de web.php mostrou rotas de autenticação e Admin registradas após o bloco condicional do instalador. Retorno antecipado no bloco de instalação impede seu registro e Handler devolve 419 para token inválido durante setup. Testes agora exigem também indisponibilidade de login/registro/social/admin POST enquanto marcadores estão ativos. Não alterar comportamento CSRF 302 da aplicação normal.

### Reteste do ciclo de instalação
O candidato c82c5f56 comprovou os controles de GET/CSRF e inicialização de banco nos dois motores; SQLite também comprovou criação e isolamento do administrador. A ferramenta `artisan serve` reinicia o servidor quando `.env` muda (ServeCommand verifica mtime), provocando desconexão no teste durante configuração/finalização. A fixture virgem passa a invocar o roteador PHP do repositório diretamente, sem watcher, para ler a configuração atual a cada requisição, sem alterar a proteção nem repetir POSTs.

## Evidências da rodada final (SHA de código 6d865f4d309c232d28d5bab84f031b024b54dbaa)
- [SQLite](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45443009426/job/138619036468): 4/4, incluindo ciclo completo e login após conclusão.
- [MariaDB](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45443009426/job/138619035775): 4/4, incluindo rejeição de schema ocupado e credenciais inválidas sem alteração de estado.
- [HTTP 20/20](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45442661964/job/138617767751) no candidato 9d6b1155; inclui hierarquia Admin e mídias.
- [Execução final](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45443009426): browser-a11y=in_progress; installer-isolated (sqlite)=success; regression=success; http-behavior=in_progress; php-lint=success; studio-layout=success; installer-isolated (mysql)=success.

### Decisões operacionais e limites
- Manutenção web legada desativada por decisão de segurança; consulta e download administrativo dos backups existentes preservados. Atualização e backup efetivos seguem procedimento VPS controlado, com autorização, sem execução nesta etapa.
- Antes de uma instalação nova, restringir ingresso ao operador via firewall/rede; o primeiro cadastro do instalador não autentica um operador preexistente. Não reativar INSTALLING/INSTALLERLOCK em instalação existente. Se a sessão de setup for perdida ou houver falha parcial, suspender acesso ao instalador e revisar o estado com backup em ambiente controlado; não apagar banco ou marcadores às cegas.
- MariaDB testado como serviço efêmero de CI; nenhuma conexão de produção. Contraste residual do perfil público pertence ao STEP 04. A matriz histórica PR #15 não foi reclassificada.

**Estado do STEP 03:** Implementações e testes específicos concluídos; aguardar evidência dos retestes finais HTTP/browser do SHA acima para declarar encerramento integral.

## Encerramento formal — STEP 03
Candidato de código `6d865f4d309c232d28d5bab84f031b024b54dbaa`, merge sintético de CI `1ebaeebca62bca6665532aab62e62913bd3b388a`: [HTTP 20/20](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45443009426/job/138619036042), [interações 6/6](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45443009426/job/138619036510), SQLite/MariaDB 4/4 cada; lint, layout e regressão aprovados. Diff até e09e3033 somente dois documentos. **STEP 03 concluído no escopo técnico isolado**. Riscos operacionais anteriores mantidos; sem autorização de merge/deploy. TC-09-B segue aberto no STEP 04.
