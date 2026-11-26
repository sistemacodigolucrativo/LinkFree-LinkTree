# LinkFree — Inventário funcional dos membros, copy pública e próximas implementações

**Registro:** 26/11/2026. **Base auditada:** `main` em `43e175081b55262dec70b9628593e03d505d93ff`. **Trabalho:** branch `feature/public-copy-member-catalog-roadmap-20261126`. **Referência histórica:** handoff `LinkFree_Knowledge_Handoff_2026-11-25.zip`, matriz REQ-001–051 e decisões do projeto, complementados pelas conversas sobre ferramentas futuras.

## 1. Funções oferecidas ao membro — mapeamento por evidência de código

A listagem detalha as capacidades existentes no **código da main**. A disponibilidade real pode depender de plano, configuração administrativa, estado da conta, dados ou tema. Não equivale a garantia de homologação em aparelhos reais.

| Grupo | Capacidades auditadas | Implementação/rota | Disponibilidade |
|---|---|---|---|
| Dashboard | total de links, cliques, ranking Top Links, atalhos de edição | `/dashboard`; `panel/index.blade.php` | sessão autenticada |
| Página pública | endereço `/@usuario`, nome, descrição, imagem/avatar, slogan para cartões sociais, links e blocos | `/studio/page`; `UserController::editPage` | sessão autenticada |
| Compartilhamento | página publicada e preview; ações de compartilhamento da área de boas-vindas | `/@{littlelink}`, `panel/index` e layout | sujeito à configuração |
| SEO/privacidade | ocultar o perfil de indexação, contador público opcional de visitas, slogan de prévia social | `studio/page`, `PublicSeo`, `UserData` | membro |
| Links | adicionar, editar, remover e ordenar links, com limites definidos pelo plano | `/studio/links`, `/studio/add-link`, `/studio/edit-link/{id}`, ordenação POST | conforme permissão e limite |
| Tipos de blocos | plataforma predefinida, link personalizado, vCard, e-mail, telefone, títulos, espaçadores e textos; mais tipos apenas se instalados | `/studio/blocks`; `LinkType::get()` em `blocks/*/config.yml` | membro; conjunto depende dos blocos instalados |
| Plataformas | botões com cores, marca/ícones predefinidos e título personalizável | `components/pageitems/predefined-form.blade.php`; `ButtonSeeder` | opções instaladas |
| Edição visual do botão | personalização de CSS/estilo por link selecionado | `/studio/button-editor/{id}` | conforme permissões do editor |
| Ícones da página | edição e remoção de ícones associados aos links | `/studio/page-icons`, `/edit-icons`, `/clearIcon/{id}` | membro |
| Temas | seleção e pré-visualização de temas, opções permitidas pelo administrador | `/studio/theme`, `/studio/theme-preview/{theme}`, `studio/theme-options` | temas instalados e opções liberadas |
| Fundo personalizado | enviar/remover imagem de fundo da página | `/studio/background`, `/studio/rem-background` | limites e configurações do sistema |
| Analytics | contagem de cliques e indicadores de links no dashboard | `panel/index.blade.php` | membro |
| Origens de visitantes | origens e contagem dos últimos 30 dias, agrupadas por fonte | `/studio/visitors`; `UserController::showVisitorReports` | **VIP ou administrador**; Free recebe 403 |
| Indicações | criar e copiar convite, atribuição de novos cadastros e histórico por identificador público | `/studio/referrals`, `/studio/referrals/code`, `/invite/{code}` | membro; sem recompensa financeira automática |
| Selo verificado | solicitar análise, acompanhar status e exibir/ocultar selo quando aprovado | `/studio/verification` e POSTs de solicitação/visibilidade | sujeito a aprovação do administrador |
| Conta | atualizar nome, e-mail e senha; exclusão da própria conta | `/studio/profile`, `/studio/delete-user/{id}` | autenticação e regras aplicáveis |
| Portabilidade | exportar links/dados, importar JSON | `/export-links`, `/export-all`, `/import-data` | **condicional** a `ALLOW_USER_EXPORT`/`ALLOW_USER_IMPORT` |
| Proteção e atendimento | denúncia pública de conteúdo | `/report` e `AbuseReportController` | visitante ou membro |
| Autenticação | cadastro, entrada, recuperação de senha e OAuth condicional | `/register`, `/login`, `/password/*`, `/social-auth/*` | conforme flags e provedores configurados |
| Acessibilidade e idioma | traduções e controles existentes de tema, teclado e layout | `resources/lang`, layouts e componentes | diferentes níveis de cobertura; não alegar conformidade WCAG global |

**Restrições de comunicação:** não anunciar relatórios avançados como gratuitos; não prometer domínio próprio por usuário, funil de conversão, pagamentos, análises em tempo real, geração de leads ou integrações ainda não codificadas. Não prometer gratuidade ilimitada. Não usar depoimentos, número de membros ou métricas inventadas.

## 2. Páginas públicas: estratégia de copy

- A homepage `home.blade.php` já renderiza textos por `App\Services\PublicSiteContent` e permite ao administrador personalizá-los em `/admin/site`.
- Melhorar os textos padrão de apresentação, benefícios, etapas, FAQ e CTA; **preservar** valores customizados em `storage/app/linkfree/public-site.json`. Onde um valor ainda corresponda exatamente ao padrão antigo, o sistema pode apresentar o novo padrão sem sobrescrever o arquivo. Documentar este comportamento.
- Acrescentar páginas explicativas `/plataforma/recursos` e `/plataforma/proximas-implementacoes` com navegação e rodapé da identidade pública, seguindo responsividade e SEO.
- Ajustar apenas copy (não lógica) na entrada/cadastro, mantendo campos, consentimento e recursos de recuperação intactos.
- **Não alterar** Termos, Política de Privacidade nem informações jurídicas; não substituir customizações feitas pelo administrador.
- Evitar links mortos quando cadastro estiver desligado; utilizar entrada existente como alternativa.

## 3. Inventário de ferramentas futuras — critério estrito

**Incluir somente novas ferramentas para membros que foram mencionadas no projeto e ainda não estão publicadas.** Não confundir com outros produtos ou com bugs, manutenção, auditoria, reformulação de UX e operações administrativas.

| Ferramenta mencionada | Evidência | Estado em 26/11 | Exposição pública |
|---|---|---|---|
| Pré-visualização interativa do botão por plataforma, com ícones e confirmação explícita | Anotação Nº 02 da sessão de 25/11; [PR #38](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/pull/38) | **Em validação no código**, PR em draft e **não integrada à main** na referência auditada | Apresentar como `Em validação`, sem prazo; remover automaticamente da página após sua publicação |

**Excluídos por já existirem**: catálogo de blocos e seus previews, indicações, selo, denúncia, temas, editor de botões, SEO, relatórios VIP, personalização e compartilhamento. **Excluídos por não constituírem ferramentas de membros já definidas**: melhorias de deploy, OAuth/SMTP, LGPD, revisão de interface, acessibilidade, importação de temas pelo administrador, sugestões vagas de módulos independentes ou ferramentas de outros projetos.

**Regra de atualização:** o item da PR #38 não deve continuar sendo anunciado depois que seu código estiver instalado; na ausência de novas ferramentas comprovadamente planejadas, mostrar estado vazio honesto e convite para acompanhar futuras novidades. Não inventar previsão nem backlog comercial.

## 4. Critérios de validação

1. Rotas públicas não podem interferir em login, autenticação, perfis `/@`, slugs ou domínios personalizados.
2. A homepage mantém todas as seções configuráveis e os textos previamente personalizados.
3. As páginas públicas usam títulos, canonical, `robots`, navegação por teclado e compatibilidade mobile/tablet/desktop, reduzindo movimento se solicitado.
4. A lista de recursos inclui restrições de VIP e flags; roadmap não contém funcionalidades atuais.
5. Testes estáticos e funcionais executados em isolamento; documentação atualizada com resultados e estado da PR.
6. Nenhum merge/deploy sem autorização do usuário.
