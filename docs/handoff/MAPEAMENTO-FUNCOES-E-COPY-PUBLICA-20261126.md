# LinkFree — inventário de funções para membros e comunicação pública

**Data:** 26/11/2026. **Branch de desenvolvimento:** `feature/public-copy-and-roadmap-20261126`. **Base:** `main` @ `43e175081b55262dec70b9628593e03d505d93ff`.

## Escopo e regra de comunicação

1. Mapear funções observáveis nas rotas autenticadas, templates e serviços, **sem presumir disponibilidade irrestrita** em todas as contas.
2. Atualizar a copy de entrada em `PublicSiteContent::sections()` (mantendo o CMS administrativo) e tornar o conjunto real de recursos acessível por uma página pública específica.
3. Criar a página pública `/proximas-implementacoes`, **sem incluir qualquer ferramenta já existente**. Não inventar lançamentos, nomes de recursos, compromissos de data ou estados de desenvolvimento.
4. Manter páginas legais, perfis públicos dos membros, autenticação, rotas de administração, banco e dados persistentes intactos. Não ativar recursos condicionais.

## Inventário técnico das funcionalidades de membros

| Área | Recurso observado no código | Evidência | Restrições e comunicação |
| --- | --- | --- | --- |
| Acesso | Cadastro, login, verificação e recuperação de acesso | `routes/auth.php`, `routes/web.php` | Dependem das configurações da instância e provedor de e-mail |
| Identidade pública | Página pessoal em endereço `/@{littlelink}` | `routes/web.php`, `UserController::littlelink` | Também pode haver prefixos/domínios configurados |
| Perfil | Nome, biografia, foto, informações públicas, links de contato | `studio/page.blade.php`, `studio/profile.blade.php` | Dados de conta não são publicados como privados |
| Links | Adicionar, editar, remover e organizar links | `studio/links.blade.php`, `studio/edit-link.blade.php`; `saveLink`, `sortLinks`, `deleteLink` | Contas Free podem ter limite de links |
| Blocos | Catálogo de tipos (links, redes/plataformas, texto, cabeçalho, espaço, contato, outros registrados) | `studio/blocks.blade.php`, `LinkTypeViewController` | O catálogo disponível depende dos tipos instalados/habilitados |
| Plataformas | Escolha de modelos predefinidos e ícones | `predefined-form.blade.php`, `ButtonSeeder`, SVG/CSS públicos | Não anunciar 104 botões para todos sem confirmar publicação; tipo e personalização podem variar |
| Ícones | Organização dos ícones da página | `studio/icons.blade.php`, `editIcons`, `clearIcon` | Disponibilidade depende das configurações |
| Aparência | Temas, prévia de tema e opções visuais; fundos personalizados quando ativados | `studio/theme.blade.php`, `ThemePreviewController`, `ThemeOptions` | Editor avançado pode depender de flags da instância |
| Botões | Edição de estilo de botões próprios | `studio/button-editor.blade.php`, `showCSS` / `editCSS` | Funcionalidade sujeita a `ENABLE_BUTTON_EDITOR` |
| Compartilhamento | Endereço público e ações de compartilhamento da página | `layouts/sidebar.blade.php`, `linkstack/modules/share-button.blade.php` | Não prometer distribuição automática |
| Visualização | Prévia real de página ou de blocos conforme componentes existentes | `studio/links.blade.php`, `studio/blocks.blade.php` | Prévia de alguns modelos é ilustrativa |
| Estatísticas | Cliques e contagens no painel; relatório de origem dos visitantes | `panel/index.blade.php`, `studio/visitor-reports.blade.php` | Origens de visitantes são restritas a VIP e admin |
| Indicações | Link de convite, cadastros atribuídos e histórico | `studio/referrals.blade.php`, `ReferralController` | Não constitui remuneração nem bônus automático |
| Selo | Solicitação de verificação, acompanhamento e opção de exibição após aprovação | `studio/verification.blade.php`, `VerificationController` | Concessão depende de análise administrativa |
| Segurança | Configurações de e-mail/senha e controles de conta | `studio/profile.blade.php`, `UserController` | Não fazer promessa jurídica ou de segurança absoluta |
| Dados | Exportação e importação de conteúdo | `routes/web.php` (`exportLinks`, `exportAll`, `importData`) | Recursos condicionais `ALLOW_USER_EXPORT` e `ALLOW_USER_IMPORT` |
| Suporte público | Contato, termos, privacidade e formulário de denúncia | `routes/web.php`, `pages.blade.php`, `report.blade.php` | Não apresentar como suporte 24h |

## Verificação de antigas propostas versus funcionalidades em produção

| Ideia mencionada anteriormente | Estado encontrado | Incluir na página de próximas implementações? |
| --- | --- | --- |
| Programa de indicações | Rotas, controller e telas existentes | **Não** |
| Solicitação de selo verificado e gestão de aprovação | Rotas, controller e telas existentes | **Não** |
| Canal público de denúncias | Rotas públicas e gestão administrativa existentes | **Não** |
| Personalização e temas PolySleek/Galaxy | Diretórios `themes/PolySleek` e `themes/galaxy` já presentes; não são novas ferramentas confirmadas | **Não** |
| Prévia de blocos e seleção de plataformas | Funcionalidades de código na `main` e PR #38 em desenvolvimento; não anunciar como novidade futura | **Não** |
| Auditorias, LGPD, SMTP/OAuth, homologação e infraestrutura | Pendências técnicas/de operação, não novas ferramentas de membro aprovadas | **Não** |

**Conclusão rastreável:** não existe no material consultado uma proposta identificável de **nova ferramenta para membros** que seja simultaneamente aprovada, ainda não implementada e adequada para divulgação pública. A página `/proximas-implementacoes` exibirá um estado vazio transparente e estruturado, preparado para futuras inclusões **somente após uma ferramenta efetivamente ser aprovada e confirmada como ausente**. Isto respeita o pedido explícito de não listar ferramentas existentes e não cria promessas fictícias.

## Implementação e validação

**Pontos pretendidos:** `PublicSiteContent.php` (defaults editáveis via CMS), `home.blade.php` (navegação e cartões reais), `routes/home.php` (rotas públicas), novos templates de recursos e roadmap com layout compartilhado, `PublicFeatureCatalog.php` para catálogo verificável e teste de regressão direcionado.

**Atenção ao CMS:** `PublicSiteContent` lê `storage/app/linkfree/public-site.json` e respeita substituições editadas pelo administrador. Novos valores **default** não sobrepõem conteúdo personalizado persistido em produção. Não alterar ou apagar esse arquivo durante deploy.

**Padrão de validação:** reaproveitar os testes existentes de Studio, SEO, acessibilidade escopada, HTTP e QA sintético; acrescentar somente checks de páginas e copy pública onde a cobertura atual for insuficiente.

**Gates:** não fazer merge nem deploy automaticamente, não modificar produção, não alterar telas autenticadas nem perfis dos usuários.
