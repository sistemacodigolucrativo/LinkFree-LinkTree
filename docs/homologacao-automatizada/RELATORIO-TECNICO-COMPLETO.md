# Homologação automatizada — relatório técnico

43 casos: 4 aprovados, 2 reprovados, 34 parciais, 2 bloqueados e 1 não automatizável.

**Revisão independente (24/11):** [Matriz de rastreabilidade dos 43 testes](MATRIZ-RASTREABILIDADE-43-TESTES.md) documenta arquivos de evidência, verificações vinculadas por ID e lacunas concretas, sem reclassificar resultados. [PR #16](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/pull/16) contém correções propostas em branch separada, sem deploy. Os checks de consistência documental verificam a integridade do relatório, não a homologação funcional.


Estado: parcial, sem aceite final. Checkout VPS limpo em 3f20bf95e75fb93cb6abfef3aaa388c9798b664d; documentação remota em 342fce9b15cdc7a55e9bcc6f8a00278dc712d73d. /srv/linkfree não é Git; registro de deploy aponta o primeiro SHA e 540 arquivos comparados de aplicação coincidem. Produção: SQLite somente leitura íntegro, zero violações FK, backup restaurado em diretório privado temporário e conferido, storage copiado com hashes coincidentes. Nenhuma escrita na aplicação ou banco de produção. HEAD em arquivos privados foi precedido de inspeção da regra nginx e retornou 403.

Ambiente isolado em /tmp com schema sem dados reais, PHP 8.3, Laravel 9 e rede externa bloqueada. Testes de consentimento, rollback, cadastro social simulado, indicações, selo, denúncias, importação, acesso, navegador e acessibilidade: ver [evidências](evidencias/) e [matriz](MATRIZ-RESULTADOS-43-TESTES.md). Chromium e Firefox abriram telas e testaram fluxos; WebKit não iniciou por bibliotecas ausentes. OAuth simulado não comprova provedor real. Duas verificações adicionais de persistência falharam por preparação do ambiente e não foram atribuídas ao produto.

Falhas e limitações: [relatório de riscos](FALHAS-E-RISCOS.md); [pendências humanas](PENDENCIAS-HOMOLOGACAO-MANUAL.md). Preflight e ordem histórica dependem de log original não encontrado; restauração não iniciou aplicação com backup real. Próximo passo: corrigir falhas, repetir testes em staging e completar OAuth real, recuperação e revisão humana.

## Casos oficiais

### TC-01-A — APROVADO

Ambiente/pré-condições: Técnico / VPS, somente leitura.. Procedimento previsto: Conferir a saída de bash /root/atualizar-linkstack.sh --homologar ou log M13; não executar SQL de escrita.. Critério: SQLite íntegro, quick_check sem erro; relatório de integridade aprovado.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-01-B — APROVADO

Ambiente/pré-condições: Técnico / VPS.. Procedimento previsto: Na saída do relatório, localizar registration_consents, colunas e migration 000001.. Critério: Tabela, user_id, method, terms_version, privacy_version, accepted_at e migration registrados.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-01-C — PARCIAL

Ambiente/pré-condições: Técnico / logs M13 e staging isolado.. Procedimento previsto: Conferir migrations 000002, 000003, 000004; esquema, índices e foreign keys.. Critério: referral_codes/referrals; verification_requests/verification_audit_events e users.verified_at/verification_visible; abuse_reports/abuse_report_events existem; sem violações de FK.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-01-D — PARCIAL

Ambiente/pré-condições: Somente staging descartável.. Procedimento previsto: Simular repetição das migrations e falha transacional com dados fictícios.. Critério: Sem tabelas/registros duplicados, estado parcial ou perda de dados.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-02-A — BLOQUEADO

Ambiente/pré-condições: Técnico / logs preflight 23/11.. Procedimento previsto: Ler saída de --check enviada pelo operador.. Critério: SHA antigo 54add49d30a8, alvo 3f20bf95e75f, 23 arquivos de código, nenhum conflito, SIMULACAO CONCLUIDA.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-02-B — PARCIAL

Ambiente/pré-condições: Técnico / backup privado e staging.. Procedimento previsto: Verificar arquivo /var/backups/linkfree-deploy/20261123-231449-jORt0u/sqlite-before-deploy.sqlite; restaurar somente cópia isolada.. Critério: Backup íntegro, recuperável e acompanhado dos dados persistentes relevantes.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-02-C — BLOQUEADO

Ambiente/pré-condições: Técnico / saída do deploy.. Procedimento previsto: Comparar ordem do log: backup, 3 migrations, verificação schema, publicação, limpeza de cache.. Critério: Migrations 000002–000004 concluídas ANTES da cópia de código; publicação 3f20bf95e75f.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-02-D — PARCIAL

Ambiente/pré-condições: Técnico / staging.. Procedimento previsto: Comparar SHA publicado e testar plano de recuperação de código sem reset de banco.. Critério: Aplicação VPS usa 3f20bf95e75f; main conserva esse commit no histórico, apesar de commits posteriores só de documentação; restauração não apaga dados novos.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-03-A — APROVADO

Ambiente/pré-condições: Técnico / relatório --homologar.. Procedimento previsto: Verificar permissões www-data de SQLite e storage e ausência de links simbólicos.. Critério: Relatório sem erros de leitura/escrita/arquivos privados.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-03-B — PARCIAL

Ambiente/pré-condições: Membro/Admin / /studio/theme, /admin/site, /studio/visitors.. Procedimento previsto: Em conta autorizada, alterar dado NÃO crítico, salvar, atualizar, sair/entrar e conferir.. Critério: Preferências e textos continuam salvos; informação pública reflete alteração quando aplicável; relatório de visitas persiste.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-03-C — PARCIAL

Ambiente/pré-condições: Visitante anônimo / site e relatório técnico.. Procedimento previsto: Inspecionar menus públicos e relatórios de acesso sem explorar conteúdos privados.. Critério: Sem exposição de .env, backups, storage privado ou dados de outros membros.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-04-A — PARCIAL

Ambiente/pré-condições: Visitante / /register.. Procedimento previsto: Com conta de teste inédita, preencher cadastro e enviar SEM aceitar termos.. Critério: Mensagem de aceite obrigatório; cadastro bloqueado; nenhuma conta/recibo deve ser criado.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-04-B — PARCIAL

Ambiente/pré-condições: Visitante / /register; Admin quando necessário.. Procedimento previsto: Criar conta nova com aceite e conferir Dashboard e registro.. Critério: Exatamente um usuário, um comprovante com método email/data/hash; login coerente com configuração de aprovação manual e VIP inicial.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-04-C — PARCIAL

Ambiente/pré-condições: Visitante / provedor social habilitado.. Procedimento previsto: Com conta social de teste, provar bloqueio sem aceite e cadastro/login com aceite.. Critério: Sem aceite novo usuário não é criado; com aceite conta e recibo social únicos; login posterior não duplica.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-04-D — REPROVADO

Ambiente/pré-condições: Membro/visitante / /login, /dashboard, /studio/profile.. Procedimento previsto: Login, navegação, refresh, logout e tentativa anônima de abrir Studio/Admin.. Critério: Membro acessa apenas seus dados, sessão funciona e logout protege área interna; dados salvos permanecem.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-05-A — PARCIAL

Ambiente/pré-condições: Membro / /studio/referrals.. Procedimento previsto: Clicar Criar meu link de convite se não existir, copiar URL, recarregar e comparar em outra conta.. Critério: URL HTTPS /invite/CODIGO com 24 caracteres hexadecimais; código estável e diferente entre membros.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-05-B — PARCIAL

Ambiente/pré-condições: Indicador/Membro novo/Admin / link convite, /register, /studio/referrals, /admin/referrals.. Procedimento previsto: Abrir convite numa janela anônima, cadastrar conta nova com aceite, consultar histórico.. Critério: Indicador recebe exatamente +1 no total e um @handle com data; Admin vê mesma indicação; membro não vê e-mail do convidado.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-05-C — PARCIAL

Ambiente/pré-condições: Visitante/membro / link convite.. Procedimento previsto: Abrir convite já logado, testar código inválido e login de conta existente; outros negativos em staging.. Critério: Logado não atribui; código inválido dá 404; conta existente não vira indicada; autoindicação, bloqueio do indicador e convite após 7 dias não atribuem.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-05-D — PARCIAL

Ambiente/pré-condições: Membro/Admin / /studio/referrals e /admin/referrals.. Procedimento previsto: Ver histórico, números e datas; Admin filtra por ID do indicador; membro tenta abrir /admin/referrals.. Critério: Membro vê somente próprios convites, sem e-mails; Admin tem totais/detalhes corretos; acesso Admin negado ao membro.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-06-A — PARCIAL

Ambiente/pré-condições: Membro SEM selo / /studio/verification.. Procedimento previsto: Solicitar selo com mensagem não sensível, recarregar e tentar segunda solicitação.. Critério: Mostra Aguardando aprovação; apenas um pedido pendente; Exibir/Ocultar indisponível antes de concessão.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-06-B — PARCIAL

Ambiente/pré-condições: Admin / /admin/verification e Membro / /studio/verification.. Procedimento previsto: Analisar duas solicitações de teste: aprovar uma; recusar outra com justificativa.. Critério: Aprovado libera opção de exibir/ocultar; recusado não ganha selo e vê motivo; histórico mostra data/responsável.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-06-C — PARCIAL

Ambiente/pré-condições: Admin / /admin/users, coluna Selo.. Procedimento previsto: Escolher membro sem solicitação; clicar badge Não aprovado e depois revogar com Aprovado.. Critério: Badge muda para verde Aprovado e volta a Não aprovado; concessão direta funciona e persiste.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-06-D — PARCIAL

Ambiente/pré-condições: Membro aprovado / /studio/verification e /@IDENTIFICADOR.. Procedimento previsto: Salvar Exibir, atualizar perfil público; salvar Ocultar; Admin revoga e perfil atualiza.. Critério: Aprovado+Exibir mostra selo junto ao nome quando tema/flag permite; Ocultar ou revogado não mostram selo; revogado perde controle.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-06-E — PARCIAL

Ambiente/pré-condições: Free/VIP/Admin / /admin/users e perfil público.. Procedimento previsto: Comparar papéis, e-mail verificado e status de selo antes/depois da concessão.. Critério: Conceder selo não altera VIP nem status do e-mail; VIP não ganha selo automaticamente.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-07-A — PARCIAL

Ambiente/pré-condições: Visitante / /report ou perfil público autorizado.. Procedimento previsto: Abrir /report?user=ID_DE_TESTE, escolher motivo e enviar relato inofensivo; testar também /report com URL.. Critério: Formulário público funciona sem login, confirma envio; Admin encontra novo protocolo, perfil denunciado não é bloqueado.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-07-B — PARCIAL

Ambiente/pré-condições: Visitante / /report.. Procedimento previsto: Testar motivo ausente, URL inválida e comentário além de 2000 caracteres; XSS/CSRF/throttle somente em staging.. Critério: Dados inválidos são recusados; URL até 2048, comentário até 2000; HTML fornecido não executa no Admin; proteção CSRF/rate limit.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-07-C — PARCIAL

Ambiente/pré-condições: Visitante/Admin / /report e /admin/reports.. Procedimento previsto: Enviar uma denúncia de teste e localizar no painel; testar falha SMTP apenas em staging.. Critério: Denúncia e evento persistem mesmo sem SMTP; e-mail opcional direciona para central e não contém link GET de exclusão.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-07-D — PARCIAL

Ambiente/pré-condições: Admin e membro / /admin/reports, /admin/reports/ID.. Procedimento previsto: Conferir total/filtro Nova, botão Analisar, detalhes e acesso como membro.. Critério: Registro correto na fila, detalhe e histórico; membro comum não consegue abrir telas Admin.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-07-E — PARCIAL

Ambiente/pré-condições: Admin / /admin/reports/ID de TESTE.. Procedimento previsto: Mudar Nova para Em análise; tentar resolver SEM motivo; informar motivo e salvar; arquivar/reabrir.. Critério: Sem justificativa impede encerramento; com nota salva estado, data, ator e histórico sem apagar eventos anteriores.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-07-F — PARCIAL

Ambiente/pré-condições: Admin e staging / perfil denunciado e casos encerrados.. Procedimento previsto: Verificar que denúncias não alteram status do usuário; testar exclusões apenas em staging.. Critério: Receber, resolver ou arquivar não bloqueia nem exclui automaticamente; histórico de caso permanece após exclusão controlada.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-08-A — PARCIAL

Ambiente/pré-condições: Free / /studio/links; VIP/admin conforme perfil.. Procedimento previsto: Em conta Free de teste criar cinco links navegáveis, tentar sexto; testar importação em staging.. Critério: Limite Free de 5 links navegáveis; sexto recusado sem perda de outros; heading/spacer/text não contam; VIP/admin seguem permissões.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-08-B — PARCIAL

Ambiente/pré-condições: Membro / /studio/add-link e /studio/links.. Procedimento previsto: Criar bloco WhatsApp predefinido com DDI/número/mensagem de teste e abrir link público.. Critério: Gera URL wa.me com número normalizado e mensagem com codificação correta, sem alterar texto.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-08-C — APROVADO

Ambiente/pré-condições: Membro / /studio/page e /@IDENTIFICADOR.. Procedimento previsto: Ativar search_noindex, salvar e recarregar; conferir meta robots e header X-Robots-Tag tecnicamente.. Critério: Página pública continua acessível diretamente, mas orienta noindex por meta/header quando habilitado.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-08-D — PARCIAL

Ambiente/pré-condições: VIP/Admin / /studio/visitors e página pública.. Procedimento previsto: Gerar visita legítima com UTM/referrer de teste, revisar contador e relatório.. Critério: Contador segue política anti-duplicação (não aumenta necessariamente a cada refresh); origem classificada corretamente; Free sem permissão não vê relatório restrito.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-08-E — PARCIAL

Ambiente/pré-condições: Membro / /studio/links, /studio/blocks, /studio/page, /studio/theme.. Procedimento previsto: Criar/editar link/bloco, ordenar, prévia, escolher tema permitido; salvar, recarregar e abrir página pública.. Critério: Elementos persistem na ordem correta e aparecem fielmente no público; prévias e tema não quebram layout.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-09-A — PARCIAL

Ambiente/pré-condições: Android/tablet/desktop / áreas pública, membro e Admin.. Procedimento previsto: Percorrer telas em larguras 320/360/390, 768/1024 e 1440 quando disponíveis.. Critério: Menus, formulários, badges, tabelas e botões visíveis/acessíveis, sem sobreposição, corte ou overflow geral.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-09-B — REPROVADO

Ambiente/pré-condições: Claro/escuro / telas principais.. Procedimento previsto: Alternar tema, verificar legibilidade, campos, alertas e navegação por teclado.. Critério: Contraste legível, foco visível, rótulos claros, estado não comunicado apenas pela cor.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-09-C — PARCIAL

Ambiente/pré-condições: Navegadores disponíveis / Chrome Android, Firefox, Safari iOS, desktop.. Procedimento previsto: Repetir fluxos-chave de cadastro, formulários, botões Copiar, modal e menus.. Critério: Comportamento equivalente nos navegadores efetivamente testados.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-09-D — PARCIAL

Ambiente/pré-condições: Visitante, membro, Admin / menus e URLs.. Procedimento previsto: Usar menus, voltar/avançar, refresh, modal e múltiplas abas em páginas permitidas.. Critério: Sem rotas quebradas, sobreposição nem acesso Admin indevido; sessão protegida.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-10-A — PARCIAL

Ambiente/pré-condições: Técnico / GitHub e log VPS.. Procedimento previsto: Conferir SHA, três migrações concluídas, backup e integridade SQLite.. Critério: SHA de aplicativo VPS 3f20bf95e75f; main contém commit mesmo com docs posteriores; schema e backup confirmados.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-10-B — PARCIAL

Ambiente/pré-condições: Visitante / /, /login, /register e URL inexistente.. Procedimento previsto: Verificar HTTPS, HTTP 200 nas telas públicas e 404 em rota inválida; 500/503 apenas em staging controlado.. Critério: 200 nas páginas esperadas; 404 verdadeiro para inexistente; sem stack trace; manutenção 503 controlada.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-10-C — PARCIAL

Ambiente/pré-condições: Membro/Admin / /dashboard, /studio/profile, /admin/config.. Procedimento previsto: Testar sessão, visibilidade de menus, acesso permitido, isolamento e persistência.. Critério: Membro não vê dados/admin de terceiros; Admin acessa funções autorizadas; logout protege acessos.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

### TC-10-D — NÃO AUTOMATIZÁVEL

Ambiente/pré-condições: Operador + IA / registro integral dos 43 casos.. Procedimento previsto: Revisar resultados, evidências, defeitos, plano de recuperação, selo legado e LGPD; solicitar aceite explícito.. Critério: Somente encerrar quando obrigatórios aprovados, nenhum P0/P1 aberto, bloqueios tratados, evidências suficientes e aceite do operador.. Evidências detalhadas nos arquivos de execução; PARCIAL indica cobertura incompleta.

