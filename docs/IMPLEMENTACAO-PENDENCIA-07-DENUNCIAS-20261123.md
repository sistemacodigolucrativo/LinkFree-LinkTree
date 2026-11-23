# Pendência 07 — Central administrativa de denúncias (23/11/2026)

## Estado

Implementada **no código** da branch implementation/linkfree-denuncias-07-20261123, sobre as branches das pendências 05 (indicações) e 06 (selo verificado). **Sem merge na main, sem deploy na VPS e sem homologação funcional.** O responsável decidiu testar as pendências 01–10 em conjunto após todas as implementações.

## Integração com o projeto existente

O LinkFree já tinha a página pública /report, um ícone de denúncia nos perfis e envio de denúncias por e-mail. O fluxo antigo não persistia casos em banco; o e-mail oferecia um link GET de exclusão de usuários, que foi removido desta notificação.

A implementação integra esses recursos existentes, sem criar um formulário público separado:

1. **Recepção:** GET /report continua disponível, inclusive no formato antigo /report?123456; o ícone público agora passa ?user=ID para identificar o perfil de forma explícita.
2. **Validação:** POST /report possui CSRF, throttle de 5 envios por 10 minutos, URL válida (máximo 2048 caracteres), categoria permitida e comentário de até 2000 caracteres. O formulário não aceita anexos. Valores traduzidos do campo report-type continuam sendo aceitos para compatibilidade.
3. **Identidade do alvo:** quando o perfil é fornecido, URL e handle oficiais são consultados no banco; valores ocultos de nome e URL não podem alterar a identificação do perfil. Denúncias de URLs sem perfil associado também são aceitas.
4. **Persistência:** abuso registrado em abuse_reports, com histórico em abuse_report_events dentro da mesma transação. O e-mail continua como notificação auxiliar, mas falha SMTP não descarta o relato.
5. **Administração:** /admin/reports exibe totalizadores, filtro de situação e lista paginada. /admin/reports/{id} exibe detalhes, histórico, membro vinculado e formulário de decisão.
6. **Fluxos de moderação:** nova → em análise → resolvida/arquivada, com possibilidade de reabertura. Encerramentos exigem justificativa. Toda alteração ou anotação tem data, autor e evento imutável pela interface.
7. **Controle de acesso:** leitura e alterações somente sob auth/blocked/impersonate/admin; mutações em POST com token CSRF. Usuários e visitantes não acessam os relatos internos.
8. **Segurança da operação:** receber ou tratar denúncias não bloqueia nem exclui usuários automaticamente; o administrador continua responsável por avaliar e executar eventuais providências em funcionalidade separada. A notificação por e-mail passou a oferecer somente link para a central autenticada, sem URL de exclusão.

## Dados e migração

- Migração aditiva: linkstack/database/migrations/2026_11_23_000004_create_abuse_reports_tables.php.
- abuse_reports contém alvo opcional, denunciante opcional (somente ID, se autenticado), snapshot de URL e handle, motivo, comentário, situação e última revisão.
- abuse_report_events contém protocolo, responsável opcional, alteração, status anterior/novo, nota e data.
- Chaves estrangeiras com SET NULL em relações com usuários e limpeza no evento de exclusão Eloquent dão suporte aos fluxos legados de remoção e preservam o registro de denúncias.
- Relatos recebidos anteriormente somente por e-mail **não serão importados automaticamente**.
- Não há novos campos de IP, user-agent, localização nem documento pessoal. Comentários livres podem conter dados voluntariamente fornecidos: definir retenção, acesso e direitos LGPD antes de encerramento do projeto.

## Critérios de homologação final (pendências 01–10)

- Cadastro de denúncia anônima e autenticada, CSRF, throttle, validação de URL/motivo/tamanho, XSS e textos traduzidos.
- Compatibilidade com /report?ID, exibição em perfis comuns/VIP e campos de URL livre.
- Banco SQLite e transação do registro; indisponibilidade do SMTP preservando denúncia; aviso de e-mail não contém exclusão GET.
- Painel admin com contadores, filtros, paginação, controle de acesso e histórico.
- Justificativa obrigatória para resolução/arquivamento; retorno para análise, reabertura, notas e atualização concorrente.
- Exclusão de denunciado, denunciante e administrador mantendo histórico.
- Compatibilidade mobile/tablet/desktop, temas claro/escuro, acessibilidade, navegadores e persistência após recarregar.
- Antes de publicar as pendências 05, 06 e 07, executar backup íntegro e migrações aditivas em ambiente apropriado com homologação técnica.

## Verificações técnicas automáticas (não substituem homologação)

- PHP syntax lint do GitHub Actions, ampliado para os controllers, service, model e migrations das pendências 05–07, **aprovado** no commit e1e68275 (run 45283888684).
- Auditoria automatizada de layout **aprovada** no commit f50278e7 (run 45283785970), depois da correção do balanceamento HTML de studio/page.blade.php herdada da Pendência 06. A correção também foi registrada na branch 06, onde a auditoria de layout passou (run 45283780390).
- A mudança posterior no workflow de lint não altera os templates. Os resultados de execução posterior devem ser consultados separadamente.
- Esses checks verificam principalmente sintaxe, contratos estáticos e layout; **cadastro real, moderação real, SQLite, OAuth, multi-browser, acessibilidade funcional e VPS** permanecem para o ciclo final 01–10.

## Estado das pendências

- 01–04: implementações anteriores; validações funcionais finais pendentes.
- 05: indicações implementadas em PR #11.
- 06: selo verificado implementado em PR #12 (empilhada sobre #11).
- 07: central de denúncias implementada nesta branch/PR, empilhada sobre #12.
- 08–10: testes e homologação final, reservados para depois do desenvolvimento integral.
