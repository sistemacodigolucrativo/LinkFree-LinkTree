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

## Acessibilidade — reteste real com navegador e axe-core

**Ambiente:** Chromium headless dentro do GitHub Actions, aplicação Laravel local/SQLite sintético, **12 telas públicas, de membro e Admin**, sem conexões externas. Auditoria limitada a WCAG A/AA por axe-core nos cenários verificados; não equivale a teste manual de leitores de tela, Safari ou todos os temas.

| Execução | Páginas | Erros de execução | Grupos de violações axe | Violações-alvo (alt, nome, foco, ARIA) |
|---|---:|---:|---:|---:|
| [Primeira bateria após correções iniciais](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45371320410) | 12 | 0 | 10 | **0** |
| [Depois de corrigir rodapé, formulário e prévia](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45371995654) | 12 | 0 | 2 | **0** |
| [Após correção complementar do formulário](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45372421569) | 12 | 0 | **1** | **0** |

Na última execução, **11/12 páginas ficaram sem regras de violação axe detectadas**; a única exceção foi \`/@auditfree\`, com três nós de contraste insuficiente no tema público sintético (nome de perfil e dois botões). Como as cores das páginas públicas podem depender de temas e preferências dos membros, uma correção global não deve sobrescrevê-las sem uma regra de contraste validada. Avaliar guarda de contraste nos editores/temas e teste manual de temas reais antes do aceite.

O job \`browser-a11y\` foi **aprovado para o escopo de regressões direcionadas** (faltas de alt, nome acessível, foco de tabelas e ARIA), e os 12 scans terminaram. Isso **não significa aprovação integral de TC-09-B**: ainda resta o contraste do tema público e a validação humana/cross-browser. Os JSONs completos de cada execução foram anexados como artefatos \`linkfree-qa-browser-a11y-synthetic\` e os logs de ações estão linkados acima.

**Controle de segurança repetido:** [run #45372421569](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45372421569) mantém \`http-behavior\` em sucesso, com 8/8 verificações sobre GET, autorização e CSRF no SQLite de teste.


## Gates que permanecem antes de merge e deploy

1. **Axe-core Chromium foi executado e registrado:** revisar e corrigir o contraste restante no tema público (ou adotar política explícita de contraste para temas configuráveis). Preservar comparativo 10 → 2 → 1 grupo de violações.
2. Validar interações de navegador dos botões convertidos (exclusão em Meus Links, ícones, Admin, confirmação e navegação), não só requisições HTTP diretas.
3. Verificar demais rotas mutáveis e interações não abrangidas; tomar cuidado especial com exclusão permanente, impersonação e procedimentos administrativos.
4. Concluir a revisão de compatibilidade WebKit/Safari e da acessibilidade visual real; temas públicos customizáveis têm contraste configurável.
5. Manter as duas PRs em rascunho até evidências suficientes e revisão de código. **Nenhum deploy automático está autorizado.**
6. Após futuro deploy aprovado e acompanhado de backup/homologação read-only, repetir checks seguros no ambiente publicado antes de alterar o status do caso oficial.

## Resultado atual

**Correção de comportamento GET/CSRF: tecnicamente comprovada no sandbox.**
**Acessibilidade: 11/12 páginas avaliadas sem violações axe detectadas; 1 grupo de contraste remanescente no perfil público sintético. TC-09-B segue sem aceite completo (temas, outros navegadores e avaliação humana).**
**Publicação na VPS: NÃO realizada. Homologação final: NÃO concluída.**
