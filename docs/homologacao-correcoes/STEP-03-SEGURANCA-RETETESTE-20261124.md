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
