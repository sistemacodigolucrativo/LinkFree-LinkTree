# LinkFree — Roadmap de SEO orgânico e prévias sociais

Implementação: novembro de 2026. Referências de práticas consolidadas; a web ao vivo está indisponível nesta entrega. Não há garantia de ranking, de indexação nem do layout exato dos cards em terceiros.

## Objetivo

SEO agressivo = excelência técnica, páginas relevantes, rastreamento consistente e prévias confiáveis. Não usar keyword stuffing, backlinks pagos, cloaking, conteúdos artificiais nem avaliações falsas.

## Auditoria inicial

- A Home e os perfis possuíam Open Graph básico com fallback SVG (incompatível com diversos leitores de miniaturas).
- Faltavam dimensões e tipo MIME da imagem, canonicals consistentes, Schema.org e descoberta via sitemaps.
- Há perfis individuais e domínios mapeados para perfis, portanto não se deve divulgar todas as contas no sitemap de um domínio personalizado.
- Respeitar as opções do administrador advanced-config.robots, advanced-config.canonical_url e HOME_URL.

## Roadmap e status

| Etapa | Entrega | Situação |
| --- | --- | --- |
| P0 | JPEG público 1200 × 630 para Home e Twitter/X, e 1080 × 1350 para perfis no WhatsApp | Código implementado |
| P0 | Open Graph: título, descrição, imagem JPEG, HTTPS, dimensões, alt | Código implementado |
| P0 | Twitter Card com imagem grande e URL canônica | Código implementado |
| P1 | SEO Home, WebSite JSON-LD e título descritivo | Código implementado |
| P1 | ProfilePage JSON-LD com dados públicos e nome do titular | Código implementado |
| P1 | sitemap.xml paginado, robots.txt e segregação por domínio | Código implementado |
| P1 | Auditoria estática e lint PHP em GitHub Actions | Automatizado no repositório |
| P2 | Validar HTTP/HTTPS reais, HTML renderizado e imagem na VPS | Pendente |
| P2 | Search Console: validar propriedade e enviar sitemap | Pendente, ação do administrador |
| P2 | Conferir WhatsApp, Facebook, Telegram e X com perfis reais | Pendente |
| P3 | Monitorar erros de rastreamento, CTR, impressões e Core Web Vitals | Contínuo |

## Contratos

- /social-preview/site.jpg: card geral LinkFree, Content-Type image/jpeg, 1200 × 630.
- /social-preview/profile/{id}.jpg: card Open Graph vertical (1080 × 1350 px, proporção 4:5), com foto grande e tipografia de alto contraste para dar maior área visual no WhatsApp.
- /social-preview/profile/{id}-wide.jpg: variante horizontal (1200 × 630 px) usada pelo Twitter/X para evitar cortes indevidos.
- Ambas as imagens usam a foto pública JPEG/PNG/WebP/GIF quando GD suporta. Perfis bloqueados retornam 404.
- O padrão vertical aumenta a área útil na maioria das prévias móveis, mas a altura efetiva e o recorte dependem do WhatsApp. O endereço recebe novo parâmetro de versão para minimizar cache de imagens antigas.
- /robots.txt: declara sitemap e desencoraja crawl de admin, contas, autenticação e páginas internas.
- /sitemap.xml: índice de sitemaps no domínio principal; em domínio dedicado a um perfil, somente a URL raiz desse perfil. Respeita noindex.
- /sitemap-home.xml: contém Home somente quando existe uma página indexável.
- /sitemap-profiles/{page}.xml: até 4000 usuários por arquivo, com lastmod e sem contas bloqueadas.
- O robô externo deve conseguir acessar o HTTPS e a imagem sem login. A renderização pelo WhatsApp é decisão da plataforma, com cache e corte próprios.
- Nunca publicar dados privados no JSON-LD. Robots.txt não substitui autorização; bloqueio é responsabilidade do backend.
- PHP GD + mbstring são exigidos para gerar thumbnails; a instalação padrão da VPS prevê essas extensões.

## Verificação operacional após o deploy

1. Executar --check e --deploy com o script da VPS.
2. Abrir https://linkfree.website/robots.txt e https://linkfree.website/sitemap.xml no navegador.
3. Conferir Content-Type image/jpeg e código HTTP 200 em https://linkfree.website/social-preview/site.jpg.
4. Inspecionar a origem HTML da Home e de um perfil; confirmar og:image, image:width/height, canonical, robots, JSON-LD e URL HTTPS.
5. Testar um perfil com foto, outro sem foto, um bloqueado e um domínio personalizado. Confirmar que nenhuma conta bloqueada aparece no sitemap.
6. Inserir o domínio e sitemap na Google Search Console e solicitar validação. Compartilhar URLs novas em WhatsApp e comparar.
7. Ajustar APP_URL, SSL e cabeçalhos X-Forwarded-Proto no servidor caso o HTML gere URLs HTTP. Este patch não modifica .env.
8. Acompanhar erros de renderização e indexação em logs e ferramentas de webmaster.

## Referências oficiais (endereços não verificados em tempo real)

- https://ogp.me/
- https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap
- https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls
- https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag
- https://developers.google.com/search/docs/appearance/structured-data/profile-page
- https://developers.google.com/search/docs/appearance/title-link
- https://schema.org/ProfilePage
- https://developers.facebook.com/tools/debug/
