# LinkFree — Implementação e auditoria das anotações Nº 06 e Nº 07

**Data:** 26/11/2026 · **Origem:** `main` `235e61d117e2756b11a69755387c4be3455fe484` · **Branch:** `feature/reviews-and-dynamic-platform-name-20261126` · **PR:** #42 (draft enquanto os testes e a revisão do deploy não forem aprovados).

## 1. Arquitetura auditada

- Nome da plataforma: **`APP_NAME` da instalação e do editor administrativo**; em Laravel, `config('app.name')`, sem nova configuração de nome. Mudança administrativa pela rota existente `/admin/env` e rotina `AdminController::editENV()`. Não introduzir configuração concorrente.
- Notificação "Está gostando?": `resources/views/layouts/notifications.blade.php`, exibida dentro de `layouts/sidebar.blade.php`. Antes remetia ao GitHub e às doações do software originário, e estava visível apenas ao administrador.
- Internacionalização: 14 catálogos `resources/lang/{ar,bg,de,el,en,es,fr,gl,pt-BR,ru,sv,vi,zh-TW,zh}/messages.php`. Campos `reviews.php` traduzidos para pt-BR e inglês como fallback no restante.
- Conteúdo institucional: `PublicSiteContent` administra defaults e conteúdo persistido em `storage/app/linkfree/public-site.json`. Os valores editados pelo administrador **não são sobrescritos**; só defaults não personalizados substituem marca antiga em memória.
- Banco: SQLite com migrations do Laravel. Registro de avaliações em tabela própria, com referências a usuários, consentimento, aprovação e possibilidade de retirada.

## 2. Anotação 07 — Entrega funcional

| Área | Contrato |
|---|---|
| Notificação | Texto "Deixe uma avaliação", rota local `/studio/avaliar`, visível para membros autenticados; substitui redirecionamentos da notificação ao GitHub e a site de doações alheio. |
| Formulário | Card premium branco/dourado/rubi, radio-buttons 1–5 estrelas com teclado, campo opcional de 1200 caracteres, checkbox obrigatório e independente para publicação, feedback de erro/sucesso, antispam honeypot. |
| Controle de acesso | `auth`, `blocked`, `impersonate`; CSRF Laravel em POST; throttle de 3 envios por hora. Uma avaliação por membro, com reenvio restrito; nunca usa valores de identidade enviados pelo navegador. |
| Persistência | `platform_reviews`: `user_id`, `rating`, `testimonial`, `publication_consent`, `consented_at`, `consent_version`, `status`, `reviewed_by`, `reviewed_at` e timestamps. |
| Moderação | `/admin/avaliacoes`, acesso administrativo, aprovação, recusa ou ocultação. Estado inicial `pending`; nenhuma publicação automática. |
| Publicação | `/plataforma/avaliacoes` e destaque de até três registros na Home, exclusivamente status `approved` com consentimento; exibe **"Membro da comunidade"**, sem nome, e-mail, ID ou URL pessoal. Escape padrão Blade em conteúdo fornecido por usuários. |
| LGPD | Informação de publicação e anonimato antes do envio; retirada por POST próprio, exclusão do registro e remoção da exibição pública. A moderação deve rejeitar informações pessoais sensíveis incluídas voluntariamente no texto. |
| Contribuições | **Nenhuma URL atribuída nesta entrega.** Futuramente a chave existente de configurações avançadas `voluntary_contribution_url` pode receber uma URL HTTPS previamente aprovada pelo operador; até lá somente a mensagem explicativa aparece. Nenhum serviço de pagamento nem dados financeiros são armazenados. |

## 3. Anotação 06 — Registros da auditoria de identificação

- `config/app.php`: fallback de marca para `LinkFree`, sem substituir `APP_NAME` personalizado.
- Páginas públicas (Home, catálogo, seus cards, SEO visual/alt), login, painel administrativo e de membros: referências identificadas como marca da plataforma passam a usar `config('app.name')`.
- 14 traduções: *somente* chaves de interface que representavam o nome desta instalação foram adaptadas em runtime; nomes de chaves, funções, classes, caminhos `linkstack/*`, serviços de terceiros e sua atribuição foram preservados.
- Setup: rótulos de instalação usam a tradução com nome configurado; remover alteração silenciosa de `APP_NAME` para `LinkStack` no componente de finalização. Campos administrativos de nome permanecem existentes.
- Conteúdo CMS: valores padrão "LinkFree" são interpolados dinamicamente em memória, **sem modificar os valores que o administrador salvou** e sem escrever no JSON.
- **Exceções intencionais:** a inscrição "by LinkStack" do rodapé de crédito upstream, endereços do autor `linkstack.org`, tema "LinkStack", classes, CSS, ícones, asset names, namespaces, nomes das bibliotecas, comentários e configuração técnica de atualização não foram alterados: não representam a marca configurada da instalação. As instruções e marcas de terceiros não podem ser rebatizadas.

## 4. Testes e critérios de aceite

O CI deverá verificar sobre aplicativo isolado, localhost e SQLite temporário:

1. GET público sem exposição de sessão privada; GET membro autenticado; GET admin reservado.
2. Ausência de criação com consentimento ausente, nota inválida ou honeypot preenchido.
3. Persistência somente com CSRF, nota válida e consentimento; status inicial `pending`.
4. Rejeição de uma segunda avaliação; autor não modera o próprio envio.
5. Aprovação somente pelo admin, HTML escapado, nenhuma exposição de nome/e-mail no site público.
6. Retirada de consentimento exclui o registro e retira imediatamente o depoimento da exibição.
7. Injeção temporária do nome "Minha Plataforma" em `config('app.name')` reflete-se em 14 catálogos, copy e interfaces.
8. Regressões preexistentes (HTTP/CSRF, sessões, login, Studio, SEO, navegadores isolados, PHP lint, Blade compilation, MySQL/SQLite setup).
9. Homologação manual futura em Android/iOS/desktop e auditoria global de acessibilidade: **não substituídas** pelos smokes sintéticos.

Resultados objetivos, referências de execução e lacunas devem constar na descrição da PR antes da integração.

## 5. **BLOQUEIO DE DEPLOY — MIGRAÇÃO NOVA**

O atualizador de produção **no momento do pedido** proíbe migrations desconhecidas; por isso `bash /root/atualizar-linkstack.sh --check && ... --deploy` **não serve para esta revisão** e não deverá ser sugerido sem preparação.

Esta branch propõe, **para revisão independente**, um novo modo de script `--deploy-reviews` que:

1. Detecta especificamente `2026_11_26_000001_create_platform_reviews_table.php`.
2. Bloqueia `--deploy` genérico e combinação desta migration com consentimento/M13.
3. Inspeciona permissões, integridade SQLite, ausência de symlinks, conflitos com código existente e gera backup online íntegro.
4. Executa **somente a migration autorizada** antes do novo código, verificando todas as colunas, registro no Laravel, `quick_check` e `foreign_key_check`.
5. Publica arquivos selecionados de código, preservando `.env`, SQLite, mídia, `storage`, temas e dependências. Nenhum deploy foi executado nesta PR.

O operador deve **revisar e instalar a versão nova do script** em `/root/atualizar-linkstack.sh` de forma controlada antes de qualquer preflight ou migração real. O script no GitHub não atualiza automaticamente a cópia privada da VPS.

Após revisão/autorização do merge, em janela supervisionada, o procedimento será:

- Fast-forward limpo do clone persistente da VPS à `main`; conferir SHA de origem e destino.
- Backup separado da versão antiga de `/root/atualizar-linkstack.sh`; `bash -n` na nova versão do repositório e instalação explícita pelo administrador.
- Rodar **somente** `--check`, conferir relação exata de arquivos e migration e obter aprovação antes de `--deploy-reviews`.
- Testar esquema/banco (backup) e smoke HTTP; testar com conta real membro/admin **sem avaliações de produção geradas automaticamente**.
- Registrar o SHA instalado e resultados no handoff.

**Rollback:** backup de SQLite é criado antes da migration. Como rollback SQL após escrita real pode destruir dados novos, não restaurar automaticamente o banco em caso de falha; revisar estado e restaurar somente por autorização específica e manutenção controlada.

## 6. Fora de escopo

As outras cinco anotações ainda não foram solicitadas nesta execução e permanecem sem alterações deliberadas. Não modificar o menu lateral fora do item necessário à moderação, nem tela de pagamentos, sistema de doações ou endereços de terceiros. **Não integrar PR nem fazer deploy nesta fase sem aprovação.**


## 7. Revisão do dispatcher oficial (correção necessária para o deploy)

O executável `/root/atualizar-linkstack.sh` pode ser o **dispatcher estável** instalado pelo repositório via `scripts/instalar-deploy-unificado.sh`. Nesse cenário, o dispatcher legado permitia `--check`, `--deploy`, `--deploy-m13` e `--homologar`, mas **não reconhecia `--deploy-reviews`**, embora a versão nova de `scripts/atualizar-linkstack.sh` já implementasse o modo.

**Correções realizadas na PR #42:** o instalador do dispatcher inclui `--deploy-reviews` na lista fechada de modos e na homologação read-only após publicação, preservando os modos anteriores; testes de regressão M13 foram adaptados. `scripts/audit-review-deploy.py` verifica o encaminhamento, rejeição de migração com modo genérico, existência de backup, execução do fluxo de migração com banco SQLite sintético, verificação da tabela e integridade. O job `script-quality` de `deploy-unificado.yml` executa essa auditoria.

**Ordem operacional obrigatória — após merge, não executada nesta sessão:**

1. Verificar a revisão de produção via acesso autorizado à VPS e preservar backup do arquivo do dispatcher em `/root`. Confirmar que o checkout persistente é o repositório correto e que a branch é `main` sem alterações locais.
2. Usar o GitHub **na SHA aprovada** para recuperar `scripts/instalar-deploy-unificado.sh`, conferir assinatura/commit e validar `bash -n`. Instalar a nova versão do dispatcher de forma controlada por root **sem executar deploy**; ele preserva cópia de segurança da versão anterior.
3. Fazer `sudo bash /root/atualizar-linkstack.sh --check`, inspecionar lista e conflitos. Se houver outras migrações simultâneas ou `--check` falhar, parar e investigar: **não contornar o bloqueio**.
4. Em janela aprovada e com pré-condições atendidas, fazer `sudo bash /root/atualizar-linkstack.sh --deploy-reviews`. O dispatcher executa homologação read-only após a publicação.
5. Confirmar com SSH, `sqlite3 -readonly` e smoke autenticado; registrar SHA de produção no handoff. Não confundir CI GitHub com validação direta da VPS.

**Atenção:** o GitHub Actions não possui neste repositório uma etapa autenticada de deploy SSH da VPS. Sua verificação pública de `https://linkfree.website` não instala código. A ausência de acesso à VPS impede afirmar que a publicação ou a homologação física aconteceram.
