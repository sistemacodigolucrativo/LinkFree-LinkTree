# Pendência 06 — Selo verificado (23/11/2026)

## Requisitos confirmados

- O membro solicita a verificação ao administrador; não pode aprovar a si próprio.
- Após aprovação, a opção **Exibir/Ocultar** fica disponível para o membro.
- O administrador pode conceder ou revogar diretamente pela **badge** de selo na lista de membros, no estilo do controle de status atual (badge verde "Aprovado" quando concedido).
- O selo visual existente (components.verify-svg) e as configurações de tema/visibilidade globais são preservados.
- Aprovação do selo **não** é sinônimo de plano VIP ou de verificação do e-mail.

## Fluxos

- /studio/verification: status do pedido, envio de solicitação e alternância de exibição se aprovado.
- /studio/page: mantém o controle de exibição de selo somente para quem tem aprovação.
- /admin/verification: fila de pedidos (aprovar/recusar com motivo), histórico das últimas ações e indicadores.
- /admin/users: coluna "Selo" com badge clicável para ativação ou revogação sem pedido prévio, via POST/CSRF.
- Perfil público: selo aparece apenas quando users.verified_at existe e users.verification_visible é verdadeiro, respeitando desativações globais e do tema.
- Reprovação requer resposta administrativa. Membro pode solicitar de novo após recusa ou revogação. Uma solicitação pendente por conta. Concessão direta resolve automaticamente eventual pedido pendente.

## Persistência e segurança

- Migração aditiva 2026_11_23_000003_create_profile_verification_workflow.php com campos users.verified_at, users.verification_visible, tabela verification_requests (um estado atual por usuário) e verification_audit_events (histórico de decisões).
- Middleware auth/blocked/impersonate para membro; middleware admin para concessão/revisão/revogação.
- Todos os comandos alteradores usam POST com CSRF; nunca disponibilizar concessão via GET.
- A aprovação/revogação desassocia o selo da função VIP, sem afetar o plano.
- O estado de exibição de selo antigo (UserData.checkmark) não dá permissão; a antiga elegibilidade automática de VIP foi removida. **Não há migração automática de VIP para selo aprovado**, porque não existe evidência de autorização explícita anterior. Aprovar manualmente antigos membros legítimos quando necessário.
- Nenhuma coleta de documento de identidade, IP, localização ou dado sensível; campo de mensagem é texto livre e recomenda não incluir dados sensíveis.
- Um registro de pedido por usuário é atualizado nas re-solicitações; cada decisão e nova solicitação gera evento de auditoria.

## Operação e pendências de teste

**Não executar merge/deploy antes de planejar e aplicar as migrações (05 e 06) com backup e validação do banco.** PR empilhada na branch da pendência 05. A main da produção permanece inalterada.

Testes funcionais 01–10 ficam reservados para depois da implementação de todos os módulos conforme decisão do responsável. Verificar especialmente: permissão de usuário comum/VIP/admin; exibição, ocultação, revogação; fila, reenvio, rejeição com motivo, concessão direta; preservação de temas e visual do selo; acesso externo, mobile, coexistência de solicitações e exclusão de membros; SQL SQLite e integridade.
