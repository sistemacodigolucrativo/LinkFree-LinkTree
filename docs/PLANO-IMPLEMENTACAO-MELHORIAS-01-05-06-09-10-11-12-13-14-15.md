# LinkFree — Plano de implementação das dez melhorias (23/11/2026)

Origem auditada: repositório `sistemacodigolucrativo/LinkFree-LinkTree`, referência inicial `8d782bcd8450335b59bfa4aced6766b86ed69df1`. Branch de trabalho `implementation/linkfree-10-melhorias-20261123`. Escopo estrito às dez melhorias do pedido; não executar deploy, exclusões de dados ou alterações em `main` sem verificação adicional.

## Baseline e plano individual

| ID | Existente confirmado | Lacuna a implementar | Critérios de aceite / testes | Riscos e dependências |
|---|---|---|---|---|
| M01 — Temas personalizáveis | `studio/theme`, `themeBackground`, escolha de tema, CSS dos temas e `UserData` | Controles liberados pelo admin; editor de tipografia/cores/botões/raio/espaçamento; CSS isolado e persistente; manter upload atual | Permissões no backend; valores validados; opções desabilitadas ocultas; não quebrar temas existentes, mobile, foco e idiomas | CSS conflitante dos temas; limitar valores e não aceitar CSS arbitrário |
| M05 — Limites de links | `users.role` = user/vip/admin; `saveLink`, `importData`, `editIcons` | Contar apenas links de navegação; máximo cinco para user; validado no servidor em todos os caminhos de criação; não limitar edição | sexto link rejeitado; VIP/admin ilimitados; importação acima do limite abortada sem apagar links; atualização preservada | importação atual deleta previamente os dados; transações e race conditions |
| M06 — WhatsApp | Plataforma `whatsapp` entre botões predefinidos e formulário de URL | Campos DDI, número, mensagem; prévia; link wa.me normalizado/escapado no servidor | validar dígitos 8–15; texto URL-encoded; edição preservada; links existentes mantidos | não criar protocolo inseguro nem URL malformada |
| M09 — Indexação | Metatags em `linkstack/modules/meta`, preferências em `UserData` | Toggle por usuário; meta robots e cabeçalho X-Robots-Tag no perfil | opção salva; cabeçalho/metatag aparecem quando ativa; URL continua abrindo | noindex não garante remoção imediata de buscadores |
| M10 — Contador público | `visits()` e relatórios de `pageStats` | Toggle de exibição; contador usa `visits(...)->count()` atual | esconde/exibe sem mudar a contagem; mesmo valor do dashboard | dependência de armazenamento de visitas; evitar consultas duplicadas |
| M11 — Origem de visitas | `visits(...)->refs()`, registros por IP e períodos | Categorias por referrer/UTM; Premium no servidor; exibição agrupada acessível; não registrar IP adicional | usuário básico negado; VIP/admin recebem painel; origem ausente como direto; dados agregados | UTM exata não está no banco original; precisa armazenamento próprio de agregados, observando privacidade |
| M12 — Termos e privacidade | Páginas em `pages`; cadastro `RegisteredUserController`; social login | Checkbox não premarcado, validação backend e registro por data/versão; social novos cadastros protegidos | sem aceite retorna erro; cadastro normal salva prova; URLs de documentos válidas; conta existente não forçada | integridade da prova; versão do termo e armazenamento em tabela; migração exige operação explícita |
| M13 — Administração | área `/admin`, gerenciamento usuários, bloqueio, VIP, estatísticas, temas e configurações | Agrupar acessos existentes e acrescentar somente navegação a pendências reais | middleware admin; sem rotas duplicadas e layout mobile | solicitações de selo/indicações dependem de módulos ainda não existentes |
| M14 — Institucional | `home.blade.php` e `PublicSiteContent` com 51 campos | Ajustes de CTA/semântica/performance de exibição e conteúdos dos módulos existentes | CTA de cadastro visível se registro permitido; login visível; edições persistentes | prevenir regressão Blade HTTP 500; validar compilação |
| M15 — Erros | `maintenance.blade.php` e exceções padrão Laravel | Views 404/500/503 reutilizáveis; manutenção com HTTP 503; opção de retorno | códigos corretos; sem vazamento de exceção; mobile, teclado, traduções | manutenção hoje possui desativação por GET — segurança exige revisão |

## Sequência e garantia de qualidade

1. Implementar em commits separados, preservar `main` e documentação de cada item.
2. Primeira camada: serviços/validação, depois controles de UI, depois templates públicos.
3. Cada alteração deve passar por `php -l`, auditorias existentes e compilação Blade (incluindo os templates modificados).
4. Testes de integração com SQLite temporário e Laravel em ambiente de homologação antes do deploy.
5. Não modificar migrações antigas; criar nova migração quando realmente necessária.
6. Caso módulo tenha dependência de produto indisponível (ex.: indicações ou verificação), documentar sem fingir que foi implementado.
7. Fazer deploy apenas após aprovação explícita e checklist de regressão da Home, /login, /studio, /admin e páginas de perfil.

**Estados:** em análise e implementação na branch indicada. Testes e homologação são declarados individualmente, nunca presumidos.
