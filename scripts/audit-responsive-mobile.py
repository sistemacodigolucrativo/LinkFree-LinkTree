#!/usr/bin/env python3
"""Browser smoke test: fixture of LinkStack Studio and navbar at target widths.

This is NOT an authenticated Laravel integration test. It tests CSS/layout
contracts matching the edited Blade markup. Chrome is preinstalled on GitHub.
"""
import re
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT=Path(__file__).resolve().parents[1]/"linkstack/resources/views"
sidebar=(ROOT/"layouts/sidebar.blade.php").read_text()
links=(ROOT/"studio/links.blade.php").read_text()
dashboard=(ROOT/"panel/index.blade.php").read_text()
theme=(ROOT/"studio/theme.blade.php").read_text()
# CSS has no dynamic Blade directives in the sections being exercised.
css_sidebar=sidebar.split("/* Cabeçalho slim:",1)[1].split("</style>",1)[0]
css_links=links.split("/* Mesmo alinhamento e mesma largura",1)[1].split("</style>",1)[0]
css_dashboard=dashboard.split("<style>",1)[1].split("</style>",1)[0]
css_theme=theme.split("<style>",1)[1].split("</style>",1)[0]
css = ("/* Cabeçalho slim:"+css_sidebar+
       "/* Mesmo alinhamento e mesma largura"+css_links+
       css_dashboard+css_theme)
base_css = """
*{box-sizing:border-box}html,body{margin:0;font:16px/1.4 Arial,sans-serif}
.row{display:flex;flex-wrap:wrap;width:100%;margin:0}
.col-lg-12,.col-12{width:100%;min-width:0}
.navbar-inner{display:flex;align-items:center;justify-content:space-between}
.navbar-brand{display:block}
.navbar-collapse{display:flex;align-items:center}
.navbar-nav{display:flex;list-style:none;padding:0;margin:0;align-items:center}
.navbar-toggler{display:none}
.btn{display:inline-flex;padding:7px 10px;text-decoration:none}
.card{padding:0;border:1px solid #ccc;border-radius:12px}
.card-header{padding:12px}.card-body{padding:12px}
.sidebar-toggle{border:0;background:transparent}
@media(max-width:991.98px){.navbar-collapse:not(.show){display:none}.navbar-toggler{display:block}}
"""
html = """<!doctype html><html lang="pt-BR"><head>
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta charset="utf-8"></head><body>
<nav class="nav navbar navbar-expand-lg navbar-light iq-navbar mobile-unified-nav">
<div class="container-fluid navbar-inner" id="navbar-inner">
  <a href="#home" class="navbar-brand">Link Free</a>
  <button id="side-arrow" class="sidebar-toggle" data-toggle="sidebar" aria-label="Menu lateral">←</button>
  <button id="hamburger" class="navbar-toggler" type="button">☰</button>
  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <ul class="mb-2 navbar-nav ms-auto align-items-center navbar-list mb-lg-0">
      <li><div class="dropdown d-flex flex-row align-items-center">
          <a class="btn btn-primary btn-sm" href="#profile">Exibir Página</a>
          <button class="btn btn-primary btn-sm dropdown-toggle" aria-label="Compartilhar">↗</button>
      </div></li>
      <li class="nav-item dropdown"><a href="#" class="nav-link" aria-label="Notificações">◉</a></li>
      <li class="nav-item dropdown"><a href="#" class="nav-link" id="mail-drop" aria-label="Atualizações">↻</a></li>
      <li class="nav-item dropdown"><a href="#" class="nav-link" id="navbarDropdown" aria-label="Perfil">
       <img class="avatar avatar-40" alt="Perfil" width="40" height="40" src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs="></a></li>
    </ul>
  </div>
</div></nav>
<div class="iq-navbar-header header-block mb-2 welcome-slim" id="welcome" style="height:auto">
 <div class="container-fluid iq-container"><div class="row"><div class="col-md-12">
 <div><h1>👋 Olá, @usuario</h1><h5>Bem-vindo ao LinkFree</h5></div>
 </div></div></div>
 <div class="iq-header-img"></div>
</div>
<main class="studio-links-page" style="max-width:100%;padding:10px">
 <div class="studio-links-sections">
   <section class="pre-left links-section-card card rounded" id="card-links">
    <h3 class="card-header">Meus Links</h3><div class="card-body">
     <div id="links-table-body"><div class="row studio-link-slim pb-0 mb-2 border rounded w-100">
      <div class="d-flex"><div class="col-auto"><svg class="sortable-handle" width="25" height="22"></svg></div>
      <div class="col"><div class="row">
       <div class="col-12 p-2"><span class="h6">Link de exemplo</span><a>https://example.com/usuario</a></div>
       <div class="col text-end"></div>
       <div class="col-12 py-1"><span>123 cliques</span>
       <a href="#" class="btn">Excluir</a><a href="#" class="btn">Editar</a>
       <a href="#" class="btn">CSS</a></div>
      </div></div></div>
     </div></div></div>
   </section>
   <section class="links-section-card page-icons-card card rounded" id="card-icons">
     <h3 class="card-header">Ícones de Página</h3>
     <div class="card-body"><div class="form-group col-lg-8">
       <div class="input-group"><span class="input-group-text">@</span>
       <input class="form-control" style="width:100%" value="https://instagram.com/example"></div>
     </div></div>
   </section>
 </div>
</main>
<section class="dashboard-slim" style="max-width:100%;padding:10px">
<div class="dashboard-main-section">
 <h6>Principais Links</h6>
 <ol class="top-links-list list-group" style="padding:0">
 <li class="top-links-slim-item list-group-item" tabindex="0">
  <div class="top-link-info"><span class="top-link-title">Meu Portfólio</span>
  <span class="top-link-url">https://example.com/endereco-bastante-grande/teste</span></div>
  <span class="top-link-clicks badge">123 cliques</span>
 </li></ol></div>
</section>
<div class="theme-preview-strip">
 <h3 class="theme-preview-title">🎨 Seleção de tema</h3>
 <button class="btn btn-primary theme-select-action">Selecionar tema</button>
 <button class="btn theme-preview-current">Pré-visualizar tema atual</button>
</div>
<div class="modal-content studio-links-preview-modal" style="position:relative;display:none"></div>
</body></html>"""

with sync_playwright() as p:
    browser=p.chromium.launch(channel="chrome",headless=True,args=["--no-sandbox","--disable-dev-shm-usage"])
    try:
        for width in [320,360,390,430,768,1024,1440]:
            context=browser.new_context(viewport={"width":width,"height":820})
            page=context.new_page()
            page.set_content(html)
            page.add_style_tag(content=base_css)
            page.add_style_tag(content=css)
            values=page.evaluate("""() => {
              const rect=s=>document.querySelector(s).getBoundingClientRect();
              const visible=s=>getComputedStyle(document.querySelector(s)).display!=='none';
              return {
                overflow:document.documentElement.scrollWidth-innerWidth,
                bodyOverflow:document.body.scrollWidth-innerWidth,
                cards:[rect('#card-links').width,rect('#card-icons').width],
                cardsLeft:[rect('#card-links').left,rect('#card-icons').left],
                navBrandVisible:visible('.mobile-unified-nav .navbar-brand'),
                burgerVisible:visible('#hamburger'),
                sidebarVisible:visible('#side-arrow'),
                navControlVisible:visible('#navbarDropdown'),
                nav:rect('#navbar-inner').width,
                welcome:rect('#welcome').height,
                toolbar:rect('#navbarSupportedContent').right,
                topClickWidth:rect('.top-links-slim-item').width,
                offenders:[...document.querySelectorAll('body *')].filter(el=>{
                  const r=el.getBoundingClientRect();
                  return r.right>innerWidth+1||r.left< -1;
                }).slice(0,12).map(el=>({element:el.tagName,
                  className:typeof el.className==='string'?el.className:'',
                  left:Math.round(el.getBoundingClientRect().left),
                  right:Math.round(el.getBoundingClientRect().right),
                  scroll:el.scrollWidth,client:el.clientWidth}))
              };
            }""")
            assert values["overflow"] <= 2, (width,"page horizontal overflow",values)
            assert values["bodyOverflow"] <= 2, (width,"body horizontal overflow",values)
            assert abs(values["cards"][0]-values["cards"][1])<2, (width,"card widths differ",values)
            assert abs(values["cardsLeft"][0]-values["cardsLeft"][1])<2, (width,"card misaligned",values)
            if width<=991:
                assert not values["navBrandVisible"] and not values["burgerVisible"],(width,"duplicate mobile navbar",values)
                assert values["sidebarVisible"] and values["navControlVisible"],(width,"lost toolbar/side-arrow",values)
                assert values["toolbar"]<=width+2,(width,"toolbar exceeds viewport",values)
            assert values["welcome"]<180,(width,"welcome too tall",values)
            print(f"OK viewport {width}px: same-width cards, slim header, mobile navbar, no page overflow")
            context.close()
    finally:
        browser.close()
print("AUDITORIA DE LAYOUT RESPONSIVO APROVADA (fixture HTML)")
