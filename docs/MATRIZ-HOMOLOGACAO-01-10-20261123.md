# LinkFree — Matriz de homologação conjunta 01–10

Preparação em 23/11/2026. **Nenhum teste abaixo é declarado executado nesta fase**.

## Protocolo

Executar após concluir implementações e aprovar rollout seguro, primeiro em staging SQLite isolado, depois na VPS com autorização. Perfis: visitante, Free, VIP, admin, bloqueado, cadastro normal e social. Para cada TC registrar ambiente, SHA, data, esperado, observado, status, prova sanitizada e issue. Nunca expor senha, cookie, OAuth, .env ou dados reais. Status possíveis: APROVADO, REPROVADO, NÃO EXECUTADO, NÃO APLICÁVEL (justificado).

## Pendências e cenários

### 01 — Migração do banco
- TC-01-A: integrity quick_check antes/depois; dados antigos intactos.
- TC-01-B: registration_consents com colunas e migração Ran.
- TC-01-C: migrations 000002–000004 com tabelas, colunas, índices e foreign keys.
- TC-01-D: execução repetida não duplica dados; rollback de transação seguro em staging.

### 02 — Backup, ordem do deploy
- TC-02-A: preflight, SHA e diff, nenhum arquivo protegido alterado.
- TC-02-B: snapshot SQLite consistente e backup de storage verificável e restaurável.
- TC-02-C: migrações antes de controllers, falhas sem estado parcialmente publicado.
- TC-02-D: restauração de código sem apagar consentimentos nem novas escritas; SHA publicado correto.

### 03 — Persistência e permissões
- TC-03-A: PHP-FPM/www-data lê/grava onde autorizado; sem symlinks no storage.
- TC-03-B: temas, textos e origem de visitas persistem após recarga/sessão.
- TC-03-C: arquivos privados e backups inacessíveis pela web.

### 04 — Cadastro, consentimento, OAuth
- TC-04-A: cadastro sem aceite não cria usuário nem recibo.
- TC-04-B: com aceite cria um usuário e um recibo correto; rollback sem órfãos.
- TC-04-C: OAuth real novo exige aceite, cadastra uma vez; conta existente não duplica.
- TC-04-D: login, logout, recuperação, sessão, cookies, CSRF e navegação HTTPS.

### 05 — Indicações
- TC-05-A: código exclusivo e estável por membro, link HTTPS.
- TC-05-B: cadastro novo normal/social atribuído uma vez; histórico e admin.
- TC-05-C: autoindicação, conta antiga, convite inválido/expirado, membro bloqueado.
- TC-05-D: concorrência/duplicações, paginação, FKs, privacidade dos convidados.

### 06 — Selo
- TC-06-A: solicitação, fila e não duplicação pendente.
- TC-06-B: admin aprova/recusa com histórico e motivo.
- TC-06-C: concessão/revogação pela badge Admin sem solicitação prévia.
- TC-06-D: Exibir/Ocultar só depois da aprovação; revogação remove selo público.
- TC-06-E: estado do selo separado de VIP e email_verified_at; política de legados e temas.

### 07 — Denúncias
- TC-07-A: visitantes/membros, rotas /report?ID e /report?user=ID.
- TC-07-B: CSRF, throttle, validação URL/categoria/comentários, anti-XSS.
- TC-07-C: registro persistente mesmo com SMTP indisponível; e-mail sem exclusão GET.
- TC-07-D: admin: fila, filtros, contadores, paginação, visualização, histórico.
- TC-07-E: resolução/arquivamento com justificativa, reabertura e auditoria.
- TC-07-F: nenhuma suspensão/exclusão automática; exclusão de contas mantém histórico.

### 08 — Funcionalidades gerais
- TC-08-A: máximo de 5 links Free, importação, direitos VIP/admin.
- TC-08-B: WhatsApp com DDI/telefone/mensagem.
- TC-08-C: noindex em HTML/HTTP e página pública acessível.
- TC-08-D: contador, relatórios de origem e permissões.
- TC-08-E: editores, prévias, links, ordenação e temas.

### 09 — UX/UI e compatibilidade
- TC-09-A: Android/iOS, tablet, desktop, 320–1440 px; sem overflow/ocultações.
- TC-09-B: claro/escuro, contraste, foco/teclado, labels, navegação acessível.
- TC-09-C: Chrome/Firefox/Safari conforme disponibilidade, sessões Membro/Admin.
- TC-09-D: dados, menus, modal, badge e botões sem regressões.

### 10 — Homologação de produção
- TC-10-A: SHA VPS=GitHub, migrado corretamente, integridade e backup.
- TC-10-B: HTTPS Home/login/cadastro, 404 e respostas 500/503 em ambiente controlado, manutenção.
- TC-10-C: sessões, permissões Admin/Membro, isolamento, persistência após recarregar.
- TC-10-D: evidências sanitizadas, riscos LGPD encerrados, plano de recuperação e aceite do operador.

## Critério de aprovação

Todos os testes obrigatórios aprovados, nenhuma falha P0/P1 em aberto, migrações e armazenamento confirmados, selo antigo tratado, retenção de denúncias decidida e evidências associadas. CI estático não substitui testes reais.

**Registro:** TC-01-A até TC-10-D = NÃO EXECUTADO (a preencher no ciclo final).
