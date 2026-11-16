<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Pré-visualização — {{ $theme }}</title>
  @php
    $cssRoot = $theme === 'default' ? 'assets/linkstack/css' : 'themes/'.$theme;
    $themeConfig = $theme === 'default' ? [] : (include base_path('themes/'.$theme.'/config.php'));
    $buttonCss = ($themeConfig['use_default_buttons'] ?? 'false') === 'true' ? 'assets/linkstack/css/brands.css' : $cssRoot.'/brands.css';
    $animationCss = is_file(base_path($cssRoot.'/animations.css')) ? $cssRoot.'/animations.css' : 'assets/linkstack/css/animations.css';
  @endphp
  <link rel="stylesheet" href="{{ asset($cssRoot.'/skeleton-auto.css') }}">
  <link rel="stylesheet" href="{{ asset($buttonCss) }}">
  <link rel="stylesheet" href="{{ asset($animationCss) }}">
  <style>
    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body { min-height: 100vh; margin: 0; }
    .demo-page { padding: 48px 16px 72px; }
    .demo-profile { display: flex; justify-content: center; align-items: center; margin: 0 auto 16px; border-radius: 50%; width: 100px; height: 100px; background: linear-gradient(135deg,#6558ed,#f4ae7b); color: white; font: 700 36px system-ui; }
    .demo-title { margin-bottom: 6px; overflow-wrap: anywhere; }
    .demo-description { margin: 0 auto 20px; max-width: 480px; }
    .demo-social { display: flex; justify-content: center; flex-wrap: wrap; gap: 12px; margin: 16px auto 26px; }
    .demo-social span { padding: 10px 12px; border: 1px solid currentColor; border-radius: 50%; opacity: .9; }
    .demo-section { margin: 28px 0 12px; font-size: 1.05rem; text-transform: uppercase; letter-spacing: .09em; }
    .demo-block { margin: 15px 10px; }
    .demo-media { border: 1px solid currentColor; border-radius: 15px; overflow: hidden; background: rgba(130,130,130,.13); text-align: left; }
    .demo-video { aspect-ratio: 16/9; display: grid; place-items: center; background: linear-gradient(135deg,#1a2747,#543c78); color: #fff; }
    .demo-play { display: grid; place-items: center; width: 64px; height: 64px; border: 2px solid currentColor; border-radius: 50%; font-size: 26px; }
    .demo-media-body { padding: 15px; }
    .demo-media-body p { margin: 8px 0 0; }
    .demo-picture { min-height: 170px; display: grid; place-items: center; color: white; font-size: 1.2rem; background: linear-gradient(135deg,#1c9bb8,#7971df,#dd95a9); }
    .demo-spacer { height: 25px; }
    .demo-caption { display: block; margin-top: 22px; font: 12px system-ui; opacity: .7; }
    @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation: none!important; transition: none!important; } }
  </style>
</head>
<body>
  <main class="container demo-page">
    <div class="row"><div class="column">
      <div class="demo-profile" aria-hidden="true">LF</div>
      <h1 class="demo-title">Minha página de demonstração</h1>
      <p class="demo-description">Uma prévia ilustrativa do tema <strong>{{ $theme }}</strong>, com conteúdos fictícios e sem alterar sua página.</p>
      <div class="demo-social" aria-label="Ícones de redes sociais">
        <span title="Instagram">◎</span><span title="YouTube">▶</span><span title="Contato">✉</span><span title="Compartilhar">↗</span>
      </div>

      @if($variant === 'media')
        <h2 class="demo-section">Vídeos e mídias</h2>
        <article class="demo-block demo-media">
          <div class="demo-video"><span class="demo-play" aria-hidden="true">▶</span></div>
          <div class="demo-media-body"><strong>Card de vídeo demonstrativo</strong><p>Exemplo de conteúdo multimídia incorporado.</p></div>
        </article>
        <article class="demo-block demo-media">
          <div class="demo-picture" role="img" aria-label="Arte de demonstração em degradê">Imagem de demonstração</div>
          <div class="demo-media-body"><strong>Card de imagem</strong><p>Apresentação de um destaque visual e descrição.</p></div>
        </article>
        <a class="button button-default" href="#demo">Abrir conteúdo de demonstração</a>
        <h2 class="demo-section">Outros componentes</h2>
        <p class="demo-description">Texto, títulos, divisores e blocos de conteúdo.</p>
      @else
        <h2 class="demo-section">Links e botões</h2>
        <a class="button button-default button-hover" href="#demo">🌐 Visitar meu site</a>
        <a class="button button-instagram button-hover" href="#demo">◎ Instagram</a>
        <a class="button button-youtube button-hover" href="#demo">▶ Canal de vídeos</a>
        <a class="button button-default button-hover" href="#demo">✉ Enviar e-mail</a>
        <a class="button button-default button-hover" href="#demo">☎ Contato por telefone</a>
        <h2 class="demo-section">Título e texto</h2>
        <p class="demo-description">Este é um bloco de texto demonstrativo para visualizar a tipografia, as cores e os espaçamentos deste tema.</p>
        <div class="demo-spacer" aria-hidden="true"></div>
        <a class="button button-default" href="#demo">Salvar contato (vCard)</a>
        <a class="button button-default" href="#demo">Compartilhar página</a>
      @endif
      <small class="demo-caption">Demonstração {{ $variant === 'media' ? '2/2 — mídias' : '1/2 — links' }}. Os elementos não executam ações reais.</small>
    </div></div>
  </main>
</body>
</html>
