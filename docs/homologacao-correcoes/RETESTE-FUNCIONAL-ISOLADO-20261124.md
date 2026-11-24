# LinkFree — Reteste funcional da correção de segurança e acessibilidade (24/11/2026)

## Escopo da revisão

- **Origem:** [auditoria PR #15](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/pull/15), achados TC-04-D (exclusão por GET sem CSRF) e TC-09-B (axe WCAG).
- **Correções propostas:** [PR #16](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/pull/16), branch \`fix/homologacao-get-delete-a11y-20261124\`.
- **Ambiente:** runner efêmero GitHub Actions, Laravel servido exclusivamente em \`127.0.0.1:18765\`, banco SQLite criado do zero e quatro contas fictícias \`@example.test\`. Sem credenciais da VPS ou dados de clientes.
- **Código publicado em produção:** ainda \`3f20bf95e75fb93cb6abfef3aaa388c9798b664d\` no último registro conhecido. A PR #16 **não** foi integrada ou implantada.
- **Workflow:** [Isolated HTTP and browser a11y acceptance](../../.github/workflows/qa-functional-isolated.yml).
- **Código de execução:** [seed](../../scripts/qa-isolated/seed.php), [testes HTTP](../../scripts/qa-isolated/http_acceptance.py) e [browser/axe](../../scripts/qa-isolated/browser_a11y.cjs).

## Evidência comportamental já confirmada — segurança

**Execução GitHub Actions:** [run #45371033768](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45371033768), com job \`http-behavior\` concluído com sucesso (**8 aprovações, 0 falhas**). Verificações repetidas com sucesso em [run #45371320410](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45371320410).

| Assertiva | Resultado no sandbox |
|---|---|
| GET nas 6 rotas pessoais testadas, incluindo exclusão e mídia, não altera estado | PASSOU — HTTP 405 |
| GET nas 9 rotas administrativas testadas não altera estado | PASSOU — HTTP 405 |
| DELETE e POST sem token CSRF não conseguem alterar banco | PASSOU |
| Membro não pode excluir link de outro usuário | PASSOU — HTTP 403 |
| DELETE legítimo do membro com CSRF remove apenas o próprio link e retorna JSON | PASSOU |
| DELETE legítimo Admin com CSRF remove link autorizado | PASSOU |
| POST legítimo de ativação/desativação de link persiste alteração | PASSOU |
| Admin bloqueia/desbloqueia e altera plano com POST+CSRF e persistência | PASSOU |
| Formulários HTML de exclusão de Membro/Admin incluem token e método DELETE | PASSOU |

**Nota:** as duas primeiras verificações são contabilizadas como assertivas agregadas dentro da bateria de 8 testes, não como 15 casos oficiais novos. O teste não demonstra segurança de todos os endpoints nem eficácia contra todas as técnicas CSRF, e não equivale a pentest profissional. O detalhe completo e os resultados estruturados estão no artefato GitHub Actions \`linkfree-qa-http-synthetic\`.

## Classificação correta do TC-04-D

A falha original **existe na versão de produção reportada** e permanece \`REPROVADO\` na matriz histórica da PR #15. **Na branch corretiva**, o subconjunto de exclusão GET/CSRF e autorização foi reexecutado e passou contra dados sintéticos. A aprovação integral do TC-04-D exige confirmar todos os estados do caso (inclusive sessão, logout e isolamento) na versão que for efetivamente publicada. Não usar estes testes para alterar os números históricos 4/2/34/2/1.

## Acessibilidade — prova separada

O teste \`browser-a11y\` verifica Chromium com axe-core em páginas anônimas, do membro e do administrador; os achados originais TC-09-B incluem contraste, imagens sem alt, componentes sem nome e regiões roláveis sem acesso por teclado. A política do job é **falhar se regressões de acessibilidade direcionadas forem encontradas** e registrar todos os contrastes e problemas remanescentes, independentemente do estado do job.

**A aprovação de \`php-lint\`/\`studio-layout\`/\`regression\` não substitui axe-core real nem inspeção manual de dispositivos/teclado.** Anexar o JSON \`linkfree-qa-browser-a11y-synthetic\` e atualizar esta seção após evidência de execução; não declarar \`TC-09-B APROVADO\` sem zero falhas pertinentes, revisão do contraste e aceitação de UX.

## Gates que permanecem antes de merge e deploy

1. Executar e avaliar por completo o job axe-core em Chromium. Resolver violações prioritárias, reexecutar CI e preservar comparativo antes/depois.
2. Validar interações de navegador dos botões convertidos (exclusão em Meus Links, ícones, Admin, confirmação e navegação), não só requisições HTTP diretas.
3. Verificar demais rotas mutáveis e interações não abrangidas; tomar cuidado especial com exclusão permanente, impersonação e procedimentos administrativos.
4. Concluir a revisão de compatibilidade WebKit/Safari e da acessibilidade visual real; temas públicos customizáveis têm contraste configurável.
5. Manter as duas PRs em rascunho até evidências suficientes e revisão de código. **Nenhum deploy automático está autorizado.**
6. Após futuro deploy aprovado e acompanhado de backup/homologação read-only, repetir checks seguros no ambiente publicado antes de alterar o status do caso oficial.

## Resultado atual

**Correção de comportamento GET/CSRF: tecnicamente comprovada no sandbox.**
**Correção visual/acessibilidade: ainda depende da auditoria axe e de testes de UX.**
**Publicação na VPS: NÃO realizada. Homologação final: NÃO concluída.**
