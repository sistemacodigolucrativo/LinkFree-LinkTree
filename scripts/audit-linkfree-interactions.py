#!/usr/bin/env python3
"""Playwright UI regression: four review notes.
Fixture exercises the exact CSS and sidebar click handler, but not a live
authenticated Laravel session or production database.
"""
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[1] / "linkstack"
views = ROOT / "resources/views"
links = (views / "studio/links.blade.php").read_text(encoding="utf-8")
sidebar = (views / "layouts/sidebar.blade.php").read_text(encoding="utf-8")
nav_css = (ROOT / "assets/css/linkfree-navigation-polish.css").read_text(encoding="utf-8")
palette = (ROOT / "assets/css/linkfree-color-modes.css").read_text(encoding="utf-8")
close_js = (ROOT / "assets/js/sidebar-auto-close.js").read_text(encoding="utf-8")
link_css = links.split("/* Mesmo alinhamento e mesma largura", 1)[1].split("</style>",1)[0]
sidebar_css = sidebar.split("/* Cabeçalho slim:",1)[1].split("</style>",1)[0]
styles = palette + "\n" + "/* Cabeçalho slim:" + sidebar_css + "\n" + "/* Mesmo alinhamento e mesma largura" + link_css + "\n" + nav_css
base_css = """
*{box-sizing:border-box}html,body{margin:0;font:16px/1.4 Arial,sans-serif}
.main-content{width:100%}.iq-banner{width:100%}
.container-fluid{width:100%}.nav{display:flex}.navbar-inner{display:flex;width:100%}
.navbar-collapse{display:flex}.navbar-nav{display:flex;padding:0;list-style:none;margin:0}
.navbar-list{gap:3px}.navbar-list>li{position:relative}
.navbar-brand{display:block}
.navbar-toggler{display:none}
.btn{padding:6px 12px;display:inline-flex;text-decoration:none}
.card{border:1px solid #bbb;border-radius:10px}
.row{display:flex;flex-wrap:wrap;width:100%;margin:0}
.d-flex{display:flex}.col-auto{flex:0 0 auto}.col{flex:1;min-width:0}
.col-12{width:100%}
.dropdown-menu{display:none}
.dropdown-menu.show{display:block}
.sidebar-default{position:fixed;top:150px;left:0;width:150px;padding:10px;z-index:200}
@media(max-width:991.98px){.navbar-collapse:not(.show){display:none}.navbar-toggler{display:block}}
"""
html = """<!doctype html><html lang="pt-BR"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body><aside class="sidebar-default sidebar" id="sidebar"><a id="inside-menu" href="#inside">Menu interno</a></aside>
<div class="main-content">
<div class="iq-banner">
<nav class="nav navbar navbar-expand-lg navbar-light iq-navbar mobile-unified-nav">
  <div class="container-fluid navbar-inner">
    <a href="#" class="navbar-brand">LinkFree</a>
    <button id="side-arrow" type="button" data-toggle="sidebar" class="sidebar-toggle"
            aria-label="Recolher menu lateral">←</button>
    <button id="hamburger" class="navbar-toggler">☰</button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav navbar-list">
        <li><div class="dropdown d-flex view-page-buttons">
         <a href="#public" class="btn btn-primary btn-sm view-page-slim">Exibir Página</a>
         <button class="btn btn-sm share-page-slim">↗</button>
        </div></li>
        <li class="nav-item dropdown" id="notification-root">
          <a id="notification-drop" class="nav-link" href="#" data-bs-toggle="dropdown">🔔</a>
          <div class="sub-drop dropdown-menu dropdown-menu-end notification-panel show">
            <div class="card"><div class="card-header"><h5>Todas as notificações</h5></div>
            <div class="card-body"><p>Seu aviso mais recente</p><p>Mais uma notificação</p></div></div>
          </div>
        </li>
        <li><a id="mail-drop" class="nav-link" href="#">↻</a></li>
        <li><a id="navbarDropdown" class="nav-link" href="#"><span>Perfil</span></a></li>
      </ul>
    </div>
  </div>
</nav>
<div class="iq-navbar-header welcome-slim"><div class="iq-container"><h1>Olá!</h1></div></div>
</div>
<div class="content-inner studio-links-page" style="padding:10px;max-width:100%">
  <section id="links-section">
    <div id="links-table-body">
      <div id="row-1" class="row studio-link-slim has-clicks border rounded" data-id="1">
        <span class="studio-link-clicks"><i>●</i> 75 cliques</span>
        <div class="d-flex">
          <div class="col-auto"><svg id="drag" class="sortable-handle" viewBox="0 0 16 16"><circle cx="4" cy="4" r="3"/></svg></div>
          <div class="col"><div class="row">
            <div class="col-12 p-2"><span class="h6">Um link</span></div>
            <div class="col-12 py-1 studio-link-actions" role="group">
              <a href="#" id="remove" class="btn btn-icon btn-danger"><span class="btn-inner"><svg class="icon-20" viewBox="0 0 24 24"><path d="M4 4L20 20"/></svg></span></a>
              <a href="#" id="edit" class="btn btn-icon btn-warning"><span class="btn-inner"><svg class="icon-20" viewBox="0 0 24 24"><path d="M4 4L20 20"/></svg></span></a>
              <button id="up" class="btn studio-link-move" data-direction="-1">↑</button>
              <button id="down" class="btn studio-link-move" data-direction="1">↓</button>
            </div>
          </div></div>
        </div>
      </div>
      <div id="row-2" class="row studio-link-slim border rounded" data-id="2">
        <div class="d-flex"><div class="col-auto">⠿</div>
        <div class="col"><div class="studio-link-actions"><button class="studio-link-move" data-direction="-1">↑</button></div></div></div>
      </div>
    </div>
  </section>
</div>
<button id="outside" style="position:relative;margin:15px">Área externa ao menu</button>
</div></body></html>"""

def metrics(page):
    return page.evaluate("""() => {
      const rect = id => document.querySelector(id).getBoundingClientRect();
      const buttons = ['#remove','#edit','#up','#down'].map(id => rect(id));
      const badge=rect('.studio-link-clicks'),row=rect('#row-1');
      const panel=rect('.notification-panel'),view=rect('.view-page-slim');
      return {
        viewport:innerWidth,
        overflow:document.documentElement.scrollWidth-innerWidth,
        buttonWidths:buttons.map(r=>r.width),
        buttonHeights:buttons.map(r=>r.height),
        rowCenters:buttons.map(r=>(r.top+r.bottom)/2),
        badgeTop:badge.top-row.top, badgeRight:row.right-badge.right,
        handleWidth:rect('#drag').width,
        notificationLeft:panel.left, notificationRight:panel.right,
        notificationMiddle:(panel.left+panel.right)/2,
        viewHeight:view.height,
        viewAlign:getComputedStyle(document.querySelector('.view-page-slim')).alignItems,
        panelOverflow:getComputedStyle(document.querySelector('.notification-panel')).overflowX
      };
    }""")

with sync_playwright() as playwright:
    browser=playwright.chromium.launch(channel="chrome",headless=True,args=["--no-sandbox","--disable-dev-shm-usage"])
    try:
        for width in (320,360,390,430,768,1024,1440):
            context=browser.new_context(viewport={"width":width,"height":820})
            page=context.new_page()
            page.set_content(html)
            page.add_style_tag(content=base_css)
            page.add_style_tag(content=styles)
            data=metrics(page)
            assert data["overflow"] <= 2,(width,"page horizontal overflow",data)
            assert max(data["rowCenters"])-min(data["rowCenters"]) <= 2,(width,"actions not inline",data)
            assert max(data["buttonWidths"]) <= 37,(width,"button still oversized",data)
            assert max(data["buttonHeights"]) <= 37,(width,"button too tall",data)
            assert data["badgeRight"] >= 0 and data["badgeRight"] <= 12,(width,"click badge not at top-right",data)
            assert data["badgeTop"] <= 13,(width,"click badge not at top",data)
            assert data["handleWidth"] <= 15,(width,"drag handle too wide",data)
            assert data["notificationLeft"] >= -1 and data["notificationRight"] <= width+1,(width,"notifications off screen",data)
            assert abs(data["notificationMiddle"]-width/2) <= 2,(width,"notifications not centered",data)
            assert data["viewHeight"] <= 35,(width,"View Page button too tall",data)
            assert data["viewAlign"] == "center",(width,"View Page text not centered",data)
            # Theme switching must not move the notification outside viewport.
            page.evaluate("document.body.classList.add('dark')")
            night=metrics(page)
            assert night["overflow"]<=2 and abs(night["notificationMiddle"]-width/2)<=2,(width,"dark notification layout",night)
            print(f"OK {width}px: small inline actions, clicks upper-right, centered notification and compact View Page")
            context.close()

        context=browser.new_context(viewport={"width":390,"height":820})
        page=context.new_page()
        page.set_content(html)
        page.add_style_tag(content=base_css)
        page.add_style_tag(content=styles)
        # Test sidebar navigation without an unrelated notification popup on top.
        page.evaluate("document.querySelector('.notification-panel').classList.remove('show')")
        page.evaluate("""() => {
          document.querySelectorAll('[data-toggle="sidebar"]').forEach(b=>
            b.addEventListener('click',()=>document.querySelector('#sidebar').classList.toggle('sidebar-mini'))
          );
        }""")
        page.add_script_tag(content=close_js)
        page.click('#inside-menu')
        assert not page.locator('#sidebar').evaluate("(el)=>el.classList.contains('sidebar-mini')"),"inside menu closed sidebar"
        page.click('#outside')
        assert page.locator('#sidebar').evaluate("(el)=>el.classList.contains('sidebar-mini')"),"outside click did not close sidebar"
        page.click('#side-arrow')
        assert not page.locator('#sidebar').evaluate("(el)=>el.classList.contains('sidebar-mini')"),"toggle did not reopen sidebar"
        page.keyboard.press('Escape')
        assert page.locator('#sidebar').evaluate("(el)=>el.classList.contains('sidebar-mini')"),"Escape did not close sidebar"
        print("OK sidebar: inside untouched, outside closes, arrow reopens, Escape closes")
        context.close()
    finally:
        browser.close()

print("INTERACTION REGRESSION PASSED — fixtures, not live Laravel pages")
