# LinkFree — Consolidação da PR #39 na implementação da PR #40

**Data da revisão:** 26/11/2026.  
**Referência:** `main` `86fd6686a8ec3a45a074e135415d3fa35f6054ad` após merges das PRs #35, #36, #37, #38 e #40.  
**Origem complementar:** PR #39, branch `feature/public-copy-and-roadmap-20261126`, commit `93950f1d5b0ee814f1f3ecc6c8ab7b6f72de97ad`.  
**Branch de conciliação:** `feature/pr39-unique-public-polish-20261126`.  

## Decisão técnica

**Não fazer merge integral da PR #39.** Ela implementa em paralelo o mesmo objetivo da PR #40, já integrada: copy da homepage, páginas `/plataforma/recursos` e `/plataforma/proximas-implementacoes`, catálogo de recursos, sitemap e testes. Os modelos e os componentes das duas PRs têm nomes diferentes; realizar ambos os merges criaria implementações duplicadas sem acrescentar outra funcionalidade de membro.

### Itens reaproveitados da PR #39

1. **Catálogo público: mencionar o QR Code já existente.** O componente em `resources/views/layouts/sidebar.blade.php` oferece o botão de QR Code no painel e o modal com geração SVG, ou PNG quando Imagick está disponível. `MemberFeatureCatalog` passa a descrever essa função na seção de acompanhamento, sem prometer download PNG em todos os servidores. **Não altera** os botões, modal, imagem ou configuração do QR Code.
2. **Título SEO da homepage:** o padrão de `PublicSeo::homeTitle()` passa a usar `Sua página de links, com identidade própria`, em linha com a nova proposta de valor. A prioridade de `CUSTOM_META_TAGS` e o título personalizado do administrador permanecem intactos. Nenhuma meta tag de perfil foi alterada.
3. **Testes direcionados:** adicionar guard estático ao inventário e asserts HTTP da homepage e do catálogo à suíte SEO sintética já existente. Não copiar scripts de outro layout que competiriam com a suíte atual.

### Itens intencionalmente não transportados

- `PublicFeatureCatalog`, `layouts/public-marketing.blade.php`, `public/features.blade.php`, `public/roadmap.blade.php`, `assets/css/linkfree-public-pages.css` e rotas paralelas: **já substituídos** pelo `MemberFeatureCatalog`, `public/catalog.blade.php` e rotas de `PublicCatalogController` da PR #40.
- Textos alternativos de hero, cards, rodapé e os mesmos URLs públicos: a homepage e o CMS de defaults novos da PR #40 prevalecem.
- Mudanças na suíte `public_marketing_browser_smoke.cjs`, `public_marketing_acceptance.py` e `seo_acceptance.py` antigas: a PR #40 já introduziu `public_catalog_browser_smoke.cjs` e `audit-public-member-catalog.py`; apenas checks mínimos adequados à implementação atual foram agregados.
- Qualquer alteração no menu lateral, nos perfis, em rotas autenticadas, em banco de dados, em pastas persistentes ou em permissões.

## Critérios de conclusão

- [x] Confirmar que PRs #35, #36, #37, #38 e #40 constam no histórico da `main`.
- [x] Comparar os 16 arquivos da PR #39 com a PR #40, separando o que é concorrente e o que é incremental.
- [x] Incorporar apenas catálogo do QR Code já existente e título SEO padrão, preservando configurações personalizadas.
- [ ] Validar `audit-public-member-catalog.py`, `seo_acceptance.py`, lint PHP e demais regressões relevantes no CI no commit final.
- [ ] Integrar a PR complementar à `main`.
- [ ] Encerrar formalmente a PR #39 com justificativa, sem merge.
- [ ] Registrar o estado final com commits e evidências.

**Importante:** CI em ambiente isolado não substitui homologação autenticada de produção; nenhum deploy é executado nesta conciliação.
