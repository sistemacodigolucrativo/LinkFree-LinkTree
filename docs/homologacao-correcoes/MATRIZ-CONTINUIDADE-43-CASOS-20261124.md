# Matriz de continuidade — homologação definitiva (43 casos)
Data: 24/11/2026. Primeira auditoria documental do novo plano, sem novos testes de runtime. Baseline de produção relatado pelo Codex: 6e55da55e57203da2606a4b1ca1c83865c0f760d; código integrado 1b71b747e2c2b803eaffc6c63b64b90c408e8f4e; main documental inicial 3a609409a56f319e7a303a0d9de893bdf64fbb18.

## Fontes e regras
- [Matriz histórica da PR #15](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/blob/361c4cb4d4da9db98039d58e10834ad8541f40a7/docs/homologacao-automatizada/MATRIZ-RASTREABILIDADE-43-TESTES.md): preservada, não reclassificada. Distribuição original 4 aprovados, 2 reprovados, 34 parciais, 2 bloqueados, 1 não automatizável.
- [Manual operacional dos 43 casos](../HOMOLOGACAO-ASSISTIDA-01-10-MANUAL-OPERACIONAL.md) e [índice oficial](../MATRIZ-HOMOLOGACAO-01-10-20261123.md): critérios de aceite, não resultados.
- **VPS** = [relatório PR #17](STEP-06-RELATORIO-OPERACIONAL-VPS-20261124.md), fonte operacional de preflight, backup, deploy, SHA e smoke. **CI** = [STEP 03](STEP-03-SEGURANCA-RETETESTE-20261124.md), [STEP 04/05](STEP-04-05-ACESSIBILIDADE-REVISAO-20261124.md), [CI aprovado](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45453865896). **hist** = evidências originais da PR #15. Esta associação indica apoio à triagem, não aprovação automática de todos os critérios.
- R: revisão/reaproveitamento pelo ChatGPT, confirmar cobertura e complementar se faltar; C: Codex em staging isolado; E: integração externa de teste com Codex + operador quando necessário; H: validação ou decisão humana assistida pelo ChatGPT. Combinações não duplicam tarefas. ChatGPT revisa todas as provas e trata defeitos confirmados.
- Status histórico é imutável. A nova coluna é fila de trabalho, não nova classificação final. Registrar evidência por ID, SHA, ambiente, esperado/observado e conclusão. Não repetir prova válida salvo impacto de código/ambiente. Nunca publicar dados de produção ou executar mutações de teste na VPS real.

| ID | Estado histórico | Fila | Evidência de referência | Ação residual / critério |
|---|---|---|---|---|
| TC-01-A | APROVADO | R | VPS | integridade e FK pós-deploy; preservar prova; conferir invariantes legados no exercício de recuperação |
| TC-01-B | APROVADO | R | VPS | migração de consentimento registrada e smoke; conservar prova, não repetir migração |
| TC-01-C | PARCIAL | C | VPS | schema e FK observados; completar índices, constraints e rejeição FK no staging sintético |
| TC-01-D | PARCIAL | C | hist | reusar runner no-op/rollback; complementar falhas nas etapas faltantes e invariantes legados |
| TC-02-A | BLOQUEADO | R | VPS | preflight atual aprovado, alvo e diff identificados; vincular log sanitizado atual; não inventar log histórico |
| TC-02-B | PARCIAL | C | VPS | snapshot, manifesto e cópia SQLite íntegros; inicializar aplicação restaurada em ambiente fechado |
| TC-02-C | BLOQUEADO | C | VPS/hist | deploy atual sem migrations; recuperar log M13 se existir; senão comprovar ordem e falha injetada em fixture, mantendo lacuna histórica |
| TC-02-D | PARCIAL | C | VPS/CI | boot de recuperação e rollback controlado em cópia; preservar novas escritas sintéticas, confirmar SHA e hashes |
| TC-03-A | APROVADO | R | VPS | permissões e storage no smoke; complementar somente se invariante necessário faltar |
| TC-03-B | PARCIAL | C | hist | corrigir fixture NoneType; texto público, tema e origem após reload, nova sessão |
| TC-03-C | PARCIAL | C | VPS | 403 .env/banco; inventário de superfícies privadas e negação anônima/cross-account no staging |
| TC-04-A | PARCIAL | C | hist | HTTP cadastro sem aceite; contagens antes/depois de usuário/recibo |
| TC-04-B | PARCIAL | C | hist | cadastro com aceite, unicidade, recibo, rollback e flags; reusar provas existentes |
| TC-04-C | PARCIAL | E | hist | OAuth real em ambiente e conta autorizados; aceite, callback, unicidade; sem credenciais de produção no staging |
| TC-04-D | REPROVADO | C | CI/VPS | GET/CSRF/isolamento corrigidos, 20/20 HTTP e 6/6 browser; completar recuperação de senha, cookies HTTPS e fluxos reais não cobertos |
| TC-05-A | PARCIAL | C+H | hist | estabilidade entre sessões; copiar link em browser/dispositivo, HTTPS |
| TC-05-B | PARCIAL | C+E | hist | atribuição normal já parcial; UI e OAuth novo/conta existente, unicidade |
| TC-05-C | PARCIAL | C | hist | reusar casos inválidos/expirados/bloqueados; navegação e sessões faltantes |
| TC-05-D | PARCIAL | C | hist | reusar paginação/FK; conferir concorrência faltante e ausência de dados privados na UI |
| TC-06-A | PARCIAL | C | hist | pedido único e mensagens/estado após novo login |
| TC-06-B | PARCIAL | C | hist | aprovação/recusa e histórico em browser, motivo, foco e feedback |
| TC-06-C | PARCIAL | C | hist | badge grant/revoke no Admin e membro após refresh |
| TC-06-D | PARCIAL | C | hist | render público com temas, preferência persistente, revogação e flags |
| TC-06-E | PARCIAL | C+H | hist | independência VIP/email e temas; decisão de política de selos legados pelo operador |
| TC-07-A | PARCIAL | C | hist | denúncia por visitante e membro, rotas compatíveis, perfil e flag do ícone |
| TC-07-B | PARCIAL | C | hist | reusar CSRF/throttle/XSS; interface, traduções e limites faltantes |
| TC-07-C | PARCIAL | E | hist/CI | SMTP falho simulado preserva registro; e-mail real para destinatário de teste e ausência de exclusão GET |
| TC-07-D | PARCIAL | C | hist | UI Admin, filtros, contadores, histórico, paginação e permissões faltantes |
| TC-07-E | PARCIAL | C | hist | resolver/arquivar/reabrir via browser, justificativa e concorrência |
| TC-07-F | PARCIAL | C+H | hist | preservar histórico e ausência de suspensão automática; política de retenção e direitos do titular requer decisão |
| TC-08-A | PARCIAL | C | hist | limites e importação via browser, flags Free/VIP/Admin; reusar casos controller |
| TC-08-B | PARCIAL | C+H | hist | URL normalizada já parcial; destino WhatsApp em dispositivo de teste sem enviar mensagem |
| TC-08-C | APROVADO | R | hist | noindex HTML/HTTP e persistência aprovados anteriormente; confirmar impacto de mudanças no render público e retestar alvo se necessário |
| TC-08-D | PARCIAL | C | hist | corrigir fixture NoneType; HTTP dedup, contador, atribuição e permissões |
| TC-08-E | PARCIAL | C+H | hist/CI | prévia fiel, ordenação, persistência e temas; comparar com perfil público; manual só residual |
| TC-09-A | PARCIAL | C+H | CI | matriz 320/390/1280 e layout; completar 1440/tablet se faltar, Android/iOS físico |
| TC-09-B | REPROVADO | C+H | CI | 12 scans sem violações e matrizes 24/24+6/6; revisar incompletos axe, teclado e leitor de tela humano |
| TC-09-C | PARCIAL | C+H | CI | Chromium/Firefox/WebKit aprovados nos cenários cobertos; completar sessões Admin/Membro e Safari real |
| TC-09-D | PARCIAL | C+H | CI/hist | reusar interações já aprovadas; navegação reversa, multia­ba, modais e fluxos residuais |
| TC-10-A | PARCIAL | R | VPS | 40/40 arquivos, SHA, banco e backup conferidos; main posterior documental não requer deploy |
| TC-10-B | PARCIAL | C | VPS/hist | HTTPS/200/404 e bloqueios já comprovados; 500/503 e recuperação/manutenção somente staging |
| TC-10-C | PARCIAL | C | CI/VPS | reusar autenticação e negações; completar sessões e persistência multiusuário no staging |
| TC-10-D | NÃO AUTOMATIZÁVEL | H | todos | aceite humano após auditoria; retenção LGPD, selos legados e limites externos explícitos |

## Estado da triagem
43/43 IDs mapeados documentalmente. 6 casos na fila de reaproveitamento/revisão, 37 com ação residual ou dependência. Isso não representa total de testes aprovados no ciclo final. Prioridade imediata: recuperação integral e staging (TC-02-B/D), depois lacunas de fixture TC-03-B/08-D e demais casos automatizáveis. TC-02-C não requer repetir migrações reais; recuperar registro histórico apenas se disponível. O aceite TC-10-D depende de evidência e decisões humanas. Nenhum novo deploy nesta fase.
