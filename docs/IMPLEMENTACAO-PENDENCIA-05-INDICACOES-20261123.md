# Pendência 05 — Sistema de indicações (23/11/2026)

## Situação

Implementação proposta na branch `implementation/linkfree-indicacoes-05-20261123`, sem merge na main e sem deploy na VPS. Testes funcionais e homologação serão realizados com as pendências 01–10 após concluir todas as implementações, conforme orientação do responsável.

## Fluxos implementados

1. Membro autenticado acessa `/studio/referrals` e gera um código exclusivo por POST com proteção CSRF. O mesmo código é reutilizado em visitas posteriores.
2. O link `/invite/{code}` é público e grava um convite na sessão do navegador por até 7 dias. Código inválido ou conta indicadora bloqueada gera HTTP 404. Conta autenticada não pode se registrar como novo convidado.
3. Cadastro tradicional e cadastro social novo registram a atribuição **dentro da mesma transação de criação da conta**, após o comprovante de consentimento. Cadastros existentes, login normal e associação a contas já existentes não geram atribuição.
4. Membro vê total e histórico paginado, com @handle público e data (sem revelar e-mail).
5. Admin acessa `/admin/referrals`, protegido pelo middleware admin; mostra total, últimos 30 dias, indicadores ativos, histórico paginado e filtro pelo ID do indicador.
6. Um convidado só pode ser atribuído uma vez. Nenhum pagamento, bonificação ou concessão VIP está associado a indicação.

## Persistência

- Nova migration aditiva `linkstack/database/migrations/2026_11_23_000002_create_referral_tables.php`: `referral_codes` (um código por usuário), `referrals` (uma indicação por convidado).
- Índices e chaves únicas para evitar duplicidade. Chaves estrangeiras com remoção cascata do convidado, remoção do código do indicador e anonimização do indicador removido.
- Gancho de exclusão no modelo de usuário lida com a exclusão feita pelo painel que temporariamente desabilita restrições de chave estrangeira.
- Não armazenar IPs, localização, cabeçalhos HTTP ou identificadores de dispositivo para indicações.

## Operação / riscos

- **Não publicar código antes da migração.** O deploy da main não é automaticamente alterado nesta branch. Planejar backup íntegro do banco, migração aditiva com a mesma rotina de deploy homologada, e validação técnica no momento oportuno.
- Fluxo social depende da sessão atravessar o redirecionamento OAuth; testes reais ainda pendentes.
- URL base usa `route()` e precisa refletir HTTPS/domínio configurado no servidor.
- Para cadastro de contas antigas, o link não é retroativamente aplicado. Sem incentivos financeiros automáticos.

## Cenários reservados para a homologação final (01–10)

- 2 membros geram códigos exclusivos, geração repetida é idempotente.
- Acesso pelo convite, cadastro tradicional novo e registro único, inclusive recarga e atualização da página.
- Convite vencido, código inexistente, tentativa de autoindicação, conta bloqueada, login existente.
- Cadastro social novo com aceite explícito e caso social já vinculado.
- Concorrência de criação do mesmo código, unicidade de cadastro indicado, integridade das FKs em SQLite.
- Controle de acesso de member/admin e paginação, mobile, claro/escuro, contraste e cópia do link.
- Exclusão de indicador/convidado preserva integridade conforme o comportamento documentado.
- Backup, migração, deploy, smoke test e homologação final apenas quando todas as funcionalidades estiverem prontas.

## Próximos módulos

- Pendência 06: solicitações e gestão de selo verificado.
- Pendência 07: caixa de denúncias e moderação.
- Pendências 08–10: testes e homologação final.
