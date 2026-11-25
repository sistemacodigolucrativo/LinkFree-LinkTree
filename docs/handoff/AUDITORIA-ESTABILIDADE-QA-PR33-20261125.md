# Auditoria técnica: estabilidade do servidor PHP em homologação isolada

**Data:** 25/11/2026  
**Projeto:** LinkFree-LinkTree  
**PR:** https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/pull/33  
**Branch:** `fix/admin-navigation-audit-20261125`  
**Ambiente:** GitHub Actions, aplicação sintética local. **Sem alterações na VPS ou deploy.**

## 1. Sintoma, investigação e causa provável

A homologação apresentou falhas intermitentes `ERR_EMPTY_RESPONSE` durante interações do navegador. Os testes de segurança também modificam e restauram o arquivo `.env` da instalação isolada `/tmp/linkfree-audit/linkstack`. O comando de inicialização empregado no CI era `php artisan serve --host=127.0.0.1 --port=18765`.

O Laravel, por meio de `Illuminate/Foundation/Console/ServeCommand.php`, monitora modificações no ambiente e pode reiniciar o servidor quando `.env` é alterado. A mensagem `Environment modified. Restarting server...` foi identificada anteriormente no código Laravel instalado. Isso explica um mecanismo **plausível e verificável** para a interrupção transitória de conexões. **Não foi demonstrada correlação temporal direta, em logs disponíveis nesta auditoria, entre cada erro `ERR_EMPTY_RESPONSE` e cada reinicialização.** Portanto, o diagnóstico é de mecanismo de falha identificado, mas não de causalidade exclusiva definitivamente provada.

A inspeção do workflow e do commit `c4b778d20c420e9005b113d311d58c2dcd28da43` confirmou que o job `browser-a11y` já havia sido alterado para executar `php -S 127.0.0.1:18765 server.php`, mas o job separado `http-behavior` ainda usava `artisan serve`. Confirmou-se que `linkstack/server.php` existe, serve arquivos estáticos e encaminha requisições dinâmicas ao `index.php`. A diferença entre jobs era uma inconsistência preventiva, **não evidência de que o job HTTP efetivamente falhou**.

## 2. Fontes técnicas consultadas

- **Código da aplicação, primário:** [server.php no commit auditado](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/blob/c4b778d20c420e9005b113d311d58c2dcd28da43/linkstack/server.php).
- **GitHub — histórico real:** [commit c4b778d](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/commit/c4b778d20c420e9005b113d311d58c2dcd28da43), [workflow de QA](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/blob/fix/admin-navigation-audit-20261125/.github/workflows/qa-functional-isolated.yml), [run 45650603065](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45650603065).
- **Laravel, fonte oficial:** [ServeCommand API](https://api.laravel.com/docs/12.x/Illuminate/Foundation/Console/ServeCommand.html); [issue de reinício por alteração no .env](https://github.com/laravel/framework/issues/34121).
- **PHP, fonte oficial:** [Manual do servidor PHP embutido](https://www.php.net/manual/en/features.commandline.webserver.php). Documenta o script roteador e a limitação padrão de processamento em thread única; uma requisição bloqueante pode atrasar outras.
- **GitHub, fonte oficial:** [Workflow syntax](https://docs.github.com/en/actions/reference/workflows-and-actions/workflow-syntax), referência de condições e etapas de CI.

## 3. Escolha da solução

**Alternativa A: repetir testes ou elevar timeout.** Rejeitada por não eliminar a origem técnica identificada e por potencialmente mascarar problemas.

**Alternativa B: `php artisan serve --no-reload`.** Possível para impedir o watcher, mas o processo Laravel pode carregar e herdar variáveis do ambiente, tornando a verificação da atualização do `.env` menos direta. Não selecionada.

**Alternativa C (selecionada): `php -S 127.0.0.1:18765 server.php`.** Utiliza o servidor PHP e o roteador real do projeto, sem passar pelo observador de `.env` do comando Artisan. Mantém execução no loopback e em dados temporários. O servidor embutido não é recomendado para uso em produção, e seu funcionamento serial deve ser considerado caso surjam atrasos por requisições concorrentes.

## 4. Mudanças efetivamente realizadas

**Commit inicial `c4b778d20c420e9005b113d311d58c2dcd28da43`:** o job `browser-a11y` passou a usar `php -S 127.0.0.1:18765 server.php`, com etapa final de verificação do PID e requisição HTTP a `/login`.

**Commit complementar `252d8c6c714870cad4d131052308891eb2ce2827`:** alterei o job **`http-behavior`** no mesmo arquivo `.github/workflows/qa-functional-isolated.yml`, substituindo `php artisan serve` por `php -S 127.0.0.1:18765 server.php`. Acrescentei a etapa `Verify isolated HTTP server remained alive`, executada com `if: always()`, que testa existência do arquivo de PID, atividade do processo (`kill -0`) e resposta HTTP (`curl --fail --max-time 10`) em `http://127.0.0.1:18765/login`.

Não alterei código de produção, rotas, controllers, banco da VPS ou a cobertura dos testes. Os jobs têm ambientes isolados; a mesma porta é usada apenas dentro de cada job.

## 5. Verificações e resultados

| Item | Resultado constatado |
|---|---|
| Mecanismo conhecido de recarga do Artisan | Fundamentado por fonte técnica |
| Arquivo roteador real do projeto | Inspecionado e compatível |
| Correção inicial do navegador | Confirmada no commit c4b778d |
| Correção complementar do HTTP e health check | Gravada no commit 252d8c6 |
| Job HTTP no run do commit anterior | Sucesso |
| Jobs de instalação SQLite e MySQL/MariaDB no mesmo run | Sucesso |
| Job navegador no momento da consulta | Em execução, sem conclusão definitiva |
| Todos os checks do novo head após o complemento | **Pendente de resultado final verificado** |
| Ausência definitiva de ERR_EMPTY_RESPONSE após o complemento | **Não comprovada ainda** |

O sucesso pontual de `kill -0` e `curl` verifica atividade naquele instante; não comprova estabilidade durante toda a suíte.

## 6. Homologação obrigatória antes de merge ou deploy

1. Conferir [os checks da PR #33](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/pull/33/checks), assegurando que correspondem ao SHA mais recente.
2. Exigir aprovação dos oito checks; examinar especialmente `browser-a11y`, `http-behavior`, `installer-isolated (sqlite)` e `installer-isolated (mysql)`.
3. Inspecionar logs, os dois health checks e artefatos de navegador/HTTP, preservando testes ADMIN-NAV, ADMIN-RBAC, ADMIN-ENV, CSRF, interações e acessibilidade.
4. Se persistirem falhas, correlacionar logs do PHP, timeouts e erros do navegador; distinguir reinício de servidor, processamento single-thread, erro de aplicação e condição de teste. Corrigir apenas defeitos reproduzíveis.
5. Somente então avaliar merge e publicação. **Nenhum deploy executado nesta atividade.**

## 7. Referências da auditoria

- [PR #33](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/pull/33)
- [Commit inicial](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/commit/c4b778d20c420e9005b113d311d58c2dcd28da43)
- [Commit complementar](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/commit/252d8c6c714870cad4d131052308891eb2ce2827)
- [Auditoria principal da navegação](AUDITORIA-NAVEGACAO-ADMIN-20261125.md)
