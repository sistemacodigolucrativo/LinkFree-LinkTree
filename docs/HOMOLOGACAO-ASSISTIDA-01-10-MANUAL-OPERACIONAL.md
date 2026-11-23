# LinkFree — Manual operacional de homologação guiada 01–10

**Objetivo:** outra IA conduzir o operador em linguagem simples, um teste por vez, e decidir se as palavras do operador demonstram o estado final exigido.

**Base publicada:** commit de código VPS 3f20bf95e75fb93cb6abfef3aaa388c9798b664d (23/11/2026). A main teve commits posteriores exclusivamente documentais. A publicação foi aprovada tecnicamente (0 falhas no preflight), mas a validação funcional 01–10 continua pendente.

**Referências:** [Matriz 01–10](MATRIZ-HOMOLOGACAO-01-10-20261123.md), [status da produção](STATUS-PRODUCAO-M13-20261123.md), [indicações](IMPLEMENTACAO-PENDENCIA-05-INDICACOES-20261123.md), [selo](IMPLEMENTACAO-PENDENCIA-06-SELO-VERIFICADO-20261123.md) e [denúncias](IMPLEMENTACAO-PENDENCIA-07-DENUNCIAS-20261123.md). As URLs e expectativas foram comparadas com as rotas, controllers e templates da main; representam comportamento esperado, **não fatos já homologados**.

## Protocolo obrigatório da IA

1. Apresentar SOMENTE um ID de teste por mensagem: **onde ir → qual perfil usar → o que fazer → qual resultado deve aparecer**; depois aguardar relato livre do usuário.
2. Comparar a fala real ao resultado final exigido; registrar literalmente as palavras do usuário antes de interpretar. 'A página abriu' não aprova teste que exige gravação, permissão ou persistência.
3. Classificações: **APROVADO conforme relato** (todas as condições provadas), **REPROVADO** (contradição), **INCONCLUSIVO** (faltam detalhes; fazer UMA pergunta), **BLOQUEADO** (dependência/risco), **NÃO EXECUTADO** e **NÃO APLICÁVEL justificado**.
4. Uma falha P0 (vazamento, acesso Admin indevido, perda de dados, exploração, erro sistêmico) interrompe testes dependentes e exige registro. Não corrigir expectativas para forçar aprovação.
5. Registro de cada caso: ID, data, papel, dispositivo, URL, ação, resposta literal, esperado, provas, status, problema e próximo passo. Ver [modelo de registro](HOMOLOGACAO-ASSISTIDA-01-10-REGISTRO.md).
6. Não solicitar senhas, cookies, tokens OAuth, conteúdo do .env, nomes/dados privados de terceiros, dump SQL ou prints sem mascaramento. Não publicar observações privadas do operador no repositório público.
7. Testes de exclusão, falha, concorrência excessiva, restauração, 500/503, bloqueios e mutações perigosas: **somente staging isolado**, ou BLOQUEADO até ambiente e autorização. Nunca executar de novo --deploy-m13.
8. Use contas e dados de teste autorizados; configuração de VIP inicial, provedores sociais, temas, estatísticas e ícones públicos pode variar. Se a pré-condição não estiver comprovada, registrar INCONCLUSIVO/BLOQUEADO, não pressupor defeito ou aprovação.
9. Depois de cada conclusão, apresentar 'ID — STATUS — motivo — o que ficou pendente' e avançar somente ao próximo caso independente.

## Preparação

Site: https://linkfree.website. Perfis: visitante; membro Free e VIP de teste; Admin autorizado; membro sem selo e com selo; nova conta de cadastro/e-mail social; conta bloqueada só em staging. URLs usuais: /login, /register, /dashboard, /studio/profile, /studio/links, /studio/page, /studio/theme, /studio/referrals, /studio/verification, /studio/visitors, /admin/users, /admin/referrals, /admin/verification, /admin/reports, /admin/config, /report e /@IDENTIFICADOR. Não inventar usuário, dados ou conteúdo.

**Exemplos de interpretação:** 'Salvei e apareceu sucesso' = INCONCLUSIVO se for necessária persistência após recarregar; 'depois do refresh voltou ao antigo' = REPROVADO; 'aprovado na badge, mas ocultado no perfil quando o membro escolheu ocultar' = potencial APROVADO em cenário correto; 'não tenho conta de teste' = BLOQUEADO.

## Catálogo completo de cenários oficiais
## Pendência 01 — Banco e migrações

### TC-01-A

**Onde / quem:** Técnico / VPS, somente leitura.

**Ação orientada:** Conferir a saída de bash /root/atualizar-linkstack.sh --homologar ou log M13; não executar SQL de escrita.

**Resultado final exigido para APROVAR:** SQLite íntegro, quick_check sem erro; relatório de integridade aprovado.

**Reprovar / bloquear / observar:** Falha ou falta de evidência: REPROVADO ou INCONCLUSIVO; log de 23/11 comprova apenas aquele momento.

### TC-01-B

**Onde / quem:** Técnico / VPS.

**Ação orientada:** Na saída do relatório, localizar registration_consents, colunas e migration 000001.

**Resultado final exigido para APROVAR:** Tabela, user_id, method, terms_version, privacy_version, accepted_at e migration registrados.

**Reprovar / bloquear / observar:** Não expor recibos individuais; ausência de tabela/coluna reprova.

### TC-01-C

**Onde / quem:** Técnico / logs M13 e staging isolado.

**Ação orientada:** Conferir migrations 000002, 000003, 000004; esquema, índices e foreign keys.

**Resultado final exigido para APROVAR:** referral_codes/referrals; verification_requests/verification_audit_events e users.verified_at/verification_visible; abuse_reports/abuse_report_events existem; sem violações de FK.

**Reprovar / bloquear / observar:** DONE em log é evidência de aplicação, não substitui validação dos índices em staging.

### TC-01-D

**Onde / quem:** Somente staging descartável.

**Ação orientada:** Simular repetição das migrations e falha transacional com dados fictícios.

**Resultado final exigido para APROVAR:** Sem tabelas/registros duplicados, estado parcial ou perda de dados.

**Reprovar / bloquear / observar:** BLOQUEADO sem staging; nunca migrar/resetar banco de produção por teste.

## Pendência 02 — Backup e deploy

### TC-02-A

**Onde / quem:** Técnico / logs preflight 23/11.

**Ação orientada:** Ler saída de --check enviada pelo operador.

**Resultado final exigido para APROVAR:** SHA antigo 54add49d30a8, alvo 3f20bf95e75f, 23 arquivos de código, nenhum conflito, SIMULACAO CONCLUIDA.

**Reprovar / bloquear / observar:** SHA errado, symlink, arquivos protegidos ou conflito: falha; não executar novo deploy.

### TC-02-B

**Onde / quem:** Técnico / backup privado e staging.

**Ação orientada:** Verificar arquivo /var/backups/linkfree-deploy/20261123-231449-jORt0u/sqlite-before-deploy.sqlite; restaurar somente cópia isolada.

**Resultado final exigido para APROVAR:** Backup íntegro, recuperável e acompanhado dos dados persistentes relevantes.

**Reprovar / bloquear / observar:** Log mostrou integridade do snapshot, mas restauração prática continua pendente.

### TC-02-C

**Onde / quem:** Técnico / saída do deploy.

**Ação orientada:** Comparar ordem do log: backup, 3 migrations, verificação schema, publicação, limpeza de cache.

**Resultado final exigido para APROVAR:** Migrations 000002–000004 concluídas ANTES da cópia de código; publicação 3f20bf95e75f.

**Reprovar / bloquear / observar:** Código antes do banco ou falha ignorada reprova; não repetir --deploy-m13.

### TC-02-D

**Onde / quem:** Técnico / staging.

**Ação orientada:** Comparar SHA publicado e testar plano de recuperação de código sem reset de banco.

**Resultado final exigido para APROVAR:** Aplicação VPS usa 3f20bf95e75f; main conserva esse commit no histórico, apesar de commits posteriores só de documentação; restauração não apaga dados novos.

**Reprovar / bloquear / observar:** Teste de restauração prática BLOQUEADO até staging; não realizar rollback destrutivo na VPS.

## Pendência 03 — Persistência e permissões

### TC-03-A

**Onde / quem:** Técnico / relatório --homologar.

**Ação orientada:** Verificar permissões www-data de SQLite e storage e ausência de links simbólicos.

**Resultado final exigido para APROVAR:** Relatório sem erros de leitura/escrita/arquivos privados.

**Reprovar / bloquear / observar:** Se erro de permissão, reprovar; não pedir conteúdo de .env nem listar dados privados.

### TC-03-B

**Onde / quem:** Membro/Admin / /studio/theme, /admin/site, /studio/visitors.

**Ação orientada:** Em conta autorizada, alterar dado NÃO crítico, salvar, atualizar, sair/entrar e conferir.

**Resultado final exigido para APROVAR:** Preferências e textos continuam salvos; informação pública reflete alteração quando aplicável; relatório de visitas persiste.

**Reprovar / bloquear / observar:** Toast sozinho não aprova; perda após refresh reprova. Restaurar valor original.

### TC-03-C

**Onde / quem:** Visitante anônimo / site e relatório técnico.

**Ação orientada:** Inspecionar menus públicos e relatórios de acesso sem explorar conteúdos privados.

**Resultado final exigido para APROVAR:** Sem exposição de .env, backups, storage privado ou dados de outros membros.

**Reprovar / bloquear / observar:** Exposição de arquivo privado é falha P0; parar imediatamente.

## Pendência 04 — Cadastro, consentimento, OAuth e sessão

### TC-04-A

**Onde / quem:** Visitante / /register.

**Ação orientada:** Com conta de teste inédita, preencher cadastro e enviar SEM aceitar termos.

**Resultado final exigido para APROVAR:** Mensagem de aceite obrigatório; cadastro bloqueado; nenhuma conta/recibo deve ser criado.

**Reprovar / bloquear / observar:** Tela pode só indicar bloqueio; verificar inexistência real por evidência administrativa segura.

### TC-04-B

**Onde / quem:** Visitante / /register; Admin quando necessário.

**Ação orientada:** Criar conta nova com aceite e conferir Dashboard e registro.

**Resultado final exigido para APROVAR:** Exatamente um usuário, um comprovante com método email/data/hash; login coerente com configuração de aprovação manual e VIP inicial.

**Reprovar / bloquear / observar:** Conta sem recibo, duplicada ou erro 500 = REPROVADO; não pedir senha ao operador.

### TC-04-C

**Onde / quem:** Visitante / provedor social habilitado.

**Ação orientada:** Com conta social de teste, provar bloqueio sem aceite e cadastro/login com aceite.

**Resultado final exigido para APROVAR:** Sem aceite novo usuário não é criado; com aceite conta e recibo social únicos; login posterior não duplica.

**Reprovar / bloquear / observar:** BLOQUEADO se provedor não estiver configurado; não compartilhar tokens.

### TC-04-D

**Onde / quem:** Membro/visitante / /login, /dashboard, /studio/profile.

**Ação orientada:** Login, navegação, refresh, logout e tentativa anônima de abrir Studio/Admin.

**Resultado final exigido para APROVAR:** Membro acessa apenas seus dados, sessão funciona e logout protege área interna; dados salvos permanecem.

**Reprovar / bloquear / observar:** Qualquer visitante autenticado indevidamente ou dado de outra conta acessível é falha crítica.

## Pendência 05 — Indicações

### TC-05-A

**Onde / quem:** Membro / /studio/referrals.

**Ação orientada:** Clicar Criar meu link de convite se não existir, copiar URL, recarregar e comparar em outra conta.

**Resultado final exigido para APROVAR:** URL HTTPS /invite/CODIGO com 24 caracteres hexadecimais; código estável e diferente entre membros.

**Reprovar / bloquear / observar:** Copiar falha, código troca ao atualizar ou é idêntico em duas contas.

### TC-05-B

**Onde / quem:** Indicador/Membro novo/Admin / link convite, /register, /studio/referrals, /admin/referrals.

**Ação orientada:** Abrir convite numa janela anônima, cadastrar conta nova com aceite, consultar histórico.

**Resultado final exigido para APROVAR:** Indicador recebe exatamente +1 no total e um @handle com data; Admin vê mesma indicação; membro não vê e-mail do convidado.

**Reprovar / bloquear / observar:** Cadastro bem-sucedido sem atribuição, atribuição duplicada ou vazamento de dados.

### TC-05-C

**Onde / quem:** Visitante/membro / link convite.

**Ação orientada:** Abrir convite já logado, testar código inválido e login de conta existente; outros negativos em staging.

**Resultado final exigido para APROVAR:** Logado não atribui; código inválido dá 404; conta existente não vira indicada; autoindicação, bloqueio do indicador e convite após 7 dias não atribuem.

**Reprovar / bloquear / observar:** Caso de expiração, conta bloqueada ou concorrência não deve ser induzido na produção.

### TC-05-D

**Onde / quem:** Membro/Admin / /studio/referrals e /admin/referrals.

**Ação orientada:** Ver histórico, números e datas; Admin filtra por ID do indicador; membro tenta abrir /admin/referrals.

**Resultado final exigido para APROVAR:** Membro vê somente próprios convites, sem e-mails; Admin tem totais/detalhes corretos; acesso Admin negado ao membro.

**Reprovar / bloquear / observar:** Dados de outro membro expostos ou filtros/histórico divergentes reprovam.

## Pendência 06 — Selo verificado

### TC-06-A

**Onde / quem:** Membro SEM selo / /studio/verification.

**Ação orientada:** Solicitar selo com mensagem não sensível, recarregar e tentar segunda solicitação.

**Resultado final exigido para APROVAR:** Mostra Aguardando aprovação; apenas um pedido pendente; Exibir/Ocultar indisponível antes de concessão.

**Reprovar / bloquear / observar:** Se o membro consegue se aprovar ou vê controle prematuro, reprovar.

### TC-06-B

**Onde / quem:** Admin / /admin/verification e Membro / /studio/verification.

**Ação orientada:** Analisar duas solicitações de teste: aprovar uma; recusar outra com justificativa.

**Resultado final exigido para APROVAR:** Aprovado libera opção de exibir/ocultar; recusado não ganha selo e vê motivo; histórico mostra data/responsável.

**Reprovar / bloquear / observar:** Sem histórico, justificativa perdida ou selo concedido ao recusado reprova.

### TC-06-C

**Onde / quem:** Admin / /admin/users, coluna Selo.

**Ação orientada:** Escolher membro sem solicitação; clicar badge Não aprovado e depois revogar com Aprovado.

**Resultado final exigido para APROVAR:** Badge muda para verde Aprovado e volta a Não aprovado; concessão direta funciona e persiste.

**Reprovar / bloquear / observar:** Coluna Selo NÃO é a badge de status da conta nem verificação de e-mail; não alterar outros controles.

### TC-06-D

**Onde / quem:** Membro aprovado / /studio/verification e /@IDENTIFICADOR.

**Ação orientada:** Salvar Exibir, atualizar perfil público; salvar Ocultar; Admin revoga e perfil atualiza.

**Resultado final exigido para APROVAR:** Aprovado+Exibir mostra selo junto ao nome quando tema/flag permite; Ocultar ou revogado não mostram selo; revogado perde controle.

**Reprovar / bloquear / observar:** Se tema global oculta selo, resultado visual é BLOQUEADO até verificar configuração; selo visível após revogação reprova.

### TC-06-E

**Onde / quem:** Free/VIP/Admin / /admin/users e perfil público.

**Ação orientada:** Comparar papéis, e-mail verificado e status de selo antes/depois da concessão.

**Resultado final exigido para APROVAR:** Conceder selo não altera VIP nem status do e-mail; VIP não ganha selo automaticamente.

**Reprovar / bloquear / observar:** Casos antigos de selo devem ser revisados separadamente; sem aprovação automática retroativa.

## Pendência 07 — Denúncias

### TC-07-A

**Onde / quem:** Visitante / /report ou perfil público autorizado.

**Ação orientada:** Abrir /report?user=ID_DE_TESTE, escolher motivo e enviar relato inofensivo; testar também /report com URL.

**Resultado final exigido para APROVAR:** Formulário público funciona sem login, confirma envio; Admin encontra novo protocolo, perfil denunciado não é bloqueado.

**Reprovar / bloquear / observar:** Ícone no perfil depende de ENABLE_REPORT_ICON; ausente com flag desligada não implica rota quebrada.

### TC-07-B

**Onde / quem:** Visitante / /report.

**Ação orientada:** Testar motivo ausente, URL inválida e comentário além de 2000 caracteres; XSS/CSRF/throttle somente em staging.

**Resultado final exigido para APROVAR:** Dados inválidos são recusados; URL até 2048, comentário até 2000; HTML fornecido não executa no Admin; proteção CSRF/rate limit.

**Reprovar / bloquear / observar:** Não executar ataque ou rajada em produção; parte invasiva fica BLOQUEADA se sem staging.

### TC-07-C

**Onde / quem:** Visitante/Admin / /report e /admin/reports.

**Ação orientada:** Enviar uma denúncia de teste e localizar no painel; testar falha SMTP apenas em staging.

**Resultado final exigido para APROVAR:** Denúncia e evento persistem mesmo sem SMTP; e-mail opcional direciona para central e não contém link GET de exclusão.

**Reprovar / bloquear / observar:** Confirmação sem registro ou exclusão automática reprova.

### TC-07-D

**Onde / quem:** Admin e membro / /admin/reports, /admin/reports/ID.

**Ação orientada:** Conferir total/filtro Nova, botão Analisar, detalhes e acesso como membro.

**Resultado final exigido para APROVAR:** Registro correto na fila, detalhe e histórico; membro comum não consegue abrir telas Admin.

**Reprovar / bloquear / observar:** Exposição de dados internos a membro/visitante = falha crítica.

### TC-07-E

**Onde / quem:** Admin / /admin/reports/ID de TESTE.

**Ação orientada:** Mudar Nova para Em análise; tentar resolver SEM motivo; informar motivo e salvar; arquivar/reabrir.

**Resultado final exigido para APROVAR:** Sem justificativa impede encerramento; com nota salva estado, data, ator e histórico sem apagar eventos anteriores.

**Reprovar / bloquear / observar:** Encerramento sem motivo ou histórico sumido reprova.

### TC-07-F

**Onde / quem:** Admin e staging / perfil denunciado e casos encerrados.

**Ação orientada:** Verificar que denúncias não alteram status do usuário; testar exclusões apenas em staging.

**Resultado final exigido para APROVAR:** Receber, resolver ou arquivar não bloqueia nem exclui automaticamente; histórico de caso permanece após exclusão controlada.

**Reprovar / bloquear / observar:** Política LGPD de retenção ainda exige decisão; não excluir membros reais.

## Pendência 08 — Funcionalidades gerais

### TC-08-A

**Onde / quem:** Free / /studio/links; VIP/admin conforme perfil.

**Ação orientada:** Em conta Free de teste criar cinco links navegáveis, tentar sexto; testar importação em staging.

**Resultado final exigido para APROVAR:** Limite Free de 5 links navegáveis; sexto recusado sem perda de outros; heading/spacer/text não contam; VIP/admin seguem permissões.

**Reprovar / bloquear / observar:** Sexto link comum salvo, importação destrói existentes ou bloco decorativo consome vaga.

### TC-08-B

**Onde / quem:** Membro / /studio/add-link e /studio/links.

**Ação orientada:** Criar bloco WhatsApp predefinido com DDI/número/mensagem de teste e abrir link público.

**Resultado final exigido para APROVAR:** Gera URL wa.me com número normalizado e mensagem com codificação correta, sem alterar texto.

**Reprovar / bloquear / observar:** Não enviar mensagens reais a terceiros.

### TC-08-C

**Onde / quem:** Membro / /studio/page e /@IDENTIFICADOR.

**Ação orientada:** Ativar search_noindex, salvar e recarregar; conferir meta robots e header X-Robots-Tag tecnicamente.

**Resultado final exigido para APROVAR:** Página pública continua acessível diretamente, mas orienta noindex por meta/header quando habilitado.

**Reprovar / bloquear / observar:** Não usar indexação real do Google como evidência imediata.

### TC-08-D

**Onde / quem:** VIP/Admin / /studio/visitors e página pública.

**Ação orientada:** Gerar visita legítima com UTM/referrer de teste, revisar contador e relatório.

**Resultado final exigido para APROVAR:** Contador segue política anti-duplicação (não aumenta necessariamente a cada refresh); origem classificada corretamente; Free sem permissão não vê relatório restrito.

**Reprovar / bloquear / observar:** Sem permissões ou visitas suficientes classificar INCONCLUSIVO, não aprovado.

### TC-08-E

**Onde / quem:** Membro / /studio/links, /studio/blocks, /studio/page, /studio/theme.

**Ação orientada:** Criar/editar link/bloco, ordenar, prévia, escolher tema permitido; salvar, recarregar e abrir página pública.

**Resultado final exigido para APROVAR:** Elementos persistem na ordem correta e aparecem fielmente no público; prévias e tema não quebram layout.

**Reprovar / bloquear / observar:** Se prévia específica não existir, registrar divergência de escopo, sem presumir que está implementada.

## Pendência 09 — UI, dispositivos e compatibilidade

### TC-09-A

**Onde / quem:** Android/tablet/desktop / áreas pública, membro e Admin.

**Ação orientada:** Percorrer telas em larguras 320/360/390, 768/1024 e 1440 quando disponíveis.

**Resultado final exigido para APROVAR:** Menus, formulários, badges, tabelas e botões visíveis/acessíveis, sem sobreposição, corte ou overflow geral.

**Reprovar / bloquear / observar:** Tabela pode ter scroll interno deliberado; não declarar dispositivo não testado.

### TC-09-B

**Onde / quem:** Claro/escuro / telas principais.

**Ação orientada:** Alternar tema, verificar legibilidade, campos, alertas e navegação por teclado.

**Resultado final exigido para APROVAR:** Contraste legível, foco visível, rótulos claros, estado não comunicado apenas pela cor.

**Reprovar / bloquear / observar:** Auditoria WCAG formal pode exigir ferramentas, não confundir triagem visual com laudo.

### TC-09-C

**Onde / quem:** Navegadores disponíveis / Chrome Android, Firefox, Safari iOS, desktop.

**Ação orientada:** Repetir fluxos-chave de cadastro, formulários, botões Copiar, modal e menus.

**Resultado final exigido para APROVAR:** Comportamento equivalente nos navegadores efetivamente testados.

**Reprovar / bloquear / observar:** Navegador indisponível fica NÃO EXECUTADO, não aprovação presumida.

### TC-09-D

**Onde / quem:** Visitante, membro, Admin / menus e URLs.

**Ação orientada:** Usar menus, voltar/avançar, refresh, modal e múltiplas abas em páginas permitidas.

**Resultado final exigido para APROVAR:** Sem rotas quebradas, sobreposição nem acesso Admin indevido; sessão protegida.

**Reprovar / bloquear / observar:** Falha de privilégio deve suspender bateria.

## Pendência 10 — Homologação final

### TC-10-A

**Onde / quem:** Técnico / GitHub e log VPS.

**Ação orientada:** Conferir SHA, três migrações concluídas, backup e integridade SQLite.

**Resultado final exigido para APROVAR:** SHA de aplicativo VPS 3f20bf95e75f; main contém commit mesmo com docs posteriores; schema e backup confirmados.

**Reprovar / bloquear / observar:** Nenhum SQL de escrita nem novo --deploy-m13.

### TC-10-B

**Onde / quem:** Visitante / /, /login, /register e URL inexistente.

**Ação orientada:** Verificar HTTPS, HTTP 200 nas telas públicas e 404 em rota inválida; 500/503 apenas em staging controlado.

**Resultado final exigido para APROVAR:** 200 nas páginas esperadas; 404 verdadeiro para inexistente; sem stack trace; manutenção 503 controlada.

**Reprovar / bloquear / observar:** Não provocar erro 500/503 na VPS real por teste.

### TC-10-C

**Onde / quem:** Membro/Admin / /dashboard, /studio/profile, /admin/config.

**Ação orientada:** Testar sessão, visibilidade de menus, acesso permitido, isolamento e persistência.

**Resultado final exigido para APROVAR:** Membro não vê dados/admin de terceiros; Admin acessa funções autorizadas; logout protege acessos.

**Reprovar / bloquear / observar:** Vazamento de informação ou privilégio é falha crítica.

### TC-10-D

**Onde / quem:** Operador + IA / registro integral dos 43 casos.

**Ação orientada:** Revisar resultados, evidências, defeitos, plano de recuperação, selo legado e LGPD; solicitar aceite explícito.

**Resultado final exigido para APROVAR:** Somente encerrar quando obrigatórios aprovados, nenhum P0/P1 aberto, bloqueios tratados, evidências suficientes e aceite do operador.

**Reprovar / bloquear / observar:** Não confundir CI/preflight com aprovação funcional; não anunciar homologação sem esses gates.
## Critério de encerramento do programa

Cada caso precisa de observação suficiente e classificação rastreável. Só encerrar a pendência quando os casos obrigatórios dela estiverem aprovados; apenas encerrar todo o sistema após TC-10-D, aceite expresso do responsável, nenhum P0/P1 aberto, decisões sobre selos legados e LGPD tomadas, e recuperação testada em staging.

**Regra de continuidade:** ao abrir o manual, a IA inicia TC-01-A ou o primeiro caso pendente escolhido, sem exibir todo o checklist de uma só vez. Consulte [prompt para a próxima IA](HOMOLOGACAO-ASSISTIDA-01-10-PROMPT-IA.md).
