# LinkFree — Melhorias 01 a 09 — 23/11/2026

## Escopo e estado

Nove solicitações de melhoria de interface, navegação e edição administrativa, implementadas a partir do commit `5628c9ee1c986176b150df287c88aca0fcb91bc9`. Alterações de código e auditorias concluídas na branch `implementation/linkfree-melhorias-01-09-20261123`.

## Implementações

| Item | Local | Alteração |
|---|---|---|
| 01 | Painéis — rodapé | Flex responsivo, quebras adequadas, links acessíveis sem corte ou sobreposição |
| 02 | Gerenciamento de Links | Remoção exclusiva do item de menu `Link Pré -Config`; a rota `/studio/add-link` e o editor continuam existentes |
| 03 | Menu lateral | Substituição do acordeão Início por link direto `Painel de Controle` |
| 04 | Tema do painel | Rolagem apenas vertical no offcanvas, grids responsivos e proteção contra overflow horizontal |
| 05 | Admin → Usuários | Card do toggle de novos cadastros VIP menor e com descrição objetiva, sem alteração da regra existente |
| 06 | Admin → Personalização do Site | Editor organizado por abas/seções com validação, persistência de conteúdo público e controles de visibilidade |
| 07 | Admin → Gerenciar Temas | Remoção da margem negativa do contêiner que sobrepunha o cabeçalho azul no mobile |
| 08 | Admin → Temas | Padronização das prévias instaladas e GitHub em cards de proporção 16:9; fallback para demonstração em iframe quando não há preview.png |
| 09 | Admin → Tipos de Bloco | Novo catálogo em cards, formulários responsivos e ações de editar/criar/excluir preservadas |

## Editor da Home pública

- **Navegação e ações:** nomes dos links de seção e botões existentes, sem alterar URLs, permissão de acesso ou rotas.
- **Apresentação:** chamada, título, destaque, descrição, mensagem e rótulo acessível da prévia.
- **Recursos:** título, descrição, três cards e opção de visibilidade.
- **Como funciona:** título, descrição, três etapas e opção de visibilidade.
- **FAQ:** três pares de perguntas/respostas, textos de introdução e opção de visibilidade.
- **Chamada final:** textos e opção de visibilidade.
- **Rodapé:** rótulos dos links de Início, Termos, Privacidade e Contato.
- **Marca e identidade:** uploads e mensagem existentes preservados.
- **Páginas legais / exibição de links:** links diretos para os módulos administrativos já existentes.

Os dados de conteúdo público de abrangência global são armazenados em `storage/app/linkfree/public-site.json`, com edição via POST protegido por `auth` e `admin`, CSRF, whitelist de campos, validação de tipos/limites e escrita com lock e rename atômico. O conteúdo dos perfis individuais continua sendo gerenciado no painel de cada membro.

O deploy seletivo atual preserva `storage/`; não é necessária migração de banco de dados. Se o processo PHP não tiver permissão de escrita em `storage/app/linkfree`, o painel deverá sinalizar falha de gravação.

## Arquivos centrais

- `resources/views/layouts/sidebar.blade.php`
- `assets/css/linkfree-navigation-polish.css`
- `resources/views/panel/users.blade.php`
- `resources/views/panel/site.blade.php`
- `resources/views/home.blade.php`
- `app/Http/Controllers/AdminController.php`
- `app/Services/PublicSiteContent.php`
- `resources/views/panel/theme.blade.php`
- `resources/views/panel/github-themes.blade.php`
- `resources/views/admin/linktype/{index,create,edit}.blade.php`
- `assets/css/linkfree-admin-management.css`
- `scripts/audit-studio-layout.py`
- `scripts/audit-public-site-editor.py`

Os caminhos listados são relativos a `linkstack/`, exceto arquivos em `scripts/`.

## Validação e lacunas

- O GitHub Actions executa auditorias estáticas de layout, contraste, mobile, autenticação, temas e contratos do editor.
- Auditoria específica verifica cobertura de mais de 45 campos, sanitização por escape Blade, validação e persistência protegida.
- A sintaxe de `PublicSiteContent.php`, `AdminController.php` e `routes/web.php` é verificada com `php -l`.
- **Validação externa pendente:** navegação manual na VPS com administrador/membro autenticados, testes reais de gravação e leitura, uploads, temas claro/escuro, larguras 320/360/390/768/1024/1440 e verificação de permissões em `storage/app`.
- A experiência foi implementada no repositório remoto; **não foi executado deploy nem alterado o banco em produção**.

## Homologação sugerida

1. Fazer backup da aplicação, rodar `--check` no script de deploy, depois aplicar `--deploy` com autorização operacional.
2. Em desktop, tablet e mobile: verificar menu direto, ausência de Link Pré-Config e footer sem overflow.
3. Abrir Tema do painel, alternar light/dark e confirmar ausência de rolagem horizontal.
4. Verificar toggle VIP e sua persistência existente, sem criar usuários reais de teste em produção sem autorização.
5. Verificar ausência de sobreposição no Gerenciar Temas e consistência visual com Temas GitHub.
6. Criar/editar um tipo de bloco de teste em ambiente de homologação.
7. Editar texto na Home, salvar, abrir a página pública e recarregar; verificar persistência, escape de HTML e ausência de regressões.
8. Desativar uma seção (ex.: FAQ), verificar que seu link também desaparece da navegação e reativar.
9. Abrir páginas legais e configurações do rodapé para comprovar que permanecem acessíveis.

## Recuperação

- Branch de segurança: `backup/main-antes-melhorias-01-09-20261123` (`5628c9ee...`).
- Reversão de código deve preservar `storage/app/linkfree/public-site.json` e dados dos usuários.
- Nunca usar `force push` como procedimento padrão; preferir novo commit de restauração.
