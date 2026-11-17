#!/usr/bin/env python3
"""Browser regression for LinkFree profile avatar, description editor and gradients.

CSS extracted from the production Blade template is tested against representative
markup. This does not replace authenticated testing against a deployed Laravel app.
"""
from pathlib import Path
from playwright.sync_api import sync_playwright

root=Path(__file__).resolve().parents[1]/"linkstack"
views=root/"resources/views"
pageblade=(views/"studio/page.blade.php").read_text(encoding="utf-8")
sidebar=(views/"layouts/sidebar.blade.php").read_text(encoding="utf-8")
page_css=pageblade.split("<style>",1)[1].split("</style>",1)[0]
sidebar_css=sidebar.split("/* Cabeçalho slim:",1)[1].split("</style>",1)[0]
palette=(root/"assets/css/linkfree-color-modes.css").read_text(encoding="utf-8")
gradient=(root/"assets/css/linkfree-light-gradient.css").read_text(encoding="utf-8")
nav=(root/"assets/css/linkfree-navigation-polish.css").read_text(encoding="utf-8")

assert "studio-profile-editor-page" in pageblade
assert "linkfree-light-gradient.css" in sidebar
assert "margin-top:0!important" in page_css
assert ".studio-profile-editor-page .ck.ck-editor__main > .ck-editor__editable" in page_css
assert "background:var(--lf-card-gradient)!important" in gradient
assert "--lf-card-gradient:linear-gradient(115deg,#ffffff 0%,#f7f9ff 58%,#ecf2ff 100%);" in palette
assert "body:not(.dark)" in gradient and "body.dark" not in gradient
assert "width:16px!important;height:16px!important" in sidebar_css

base_css="""
*{box-sizing:border-box}
html,body{margin:0;font:16px/1.4 Arial,sans-serif}
.nav,.navbar-inner{display:flex;align-items:center}
.navbar-inner{width:100%}
.navbar-collapse{display:flex}
.navbar-list{display:flex;align-items:center;list-style:none;margin:0;padding:0}
.navbar-brand{display:block}
.navbar-toggler{display:none}
.row{display:flex;flex-wrap:wrap;min-width:0;margin:0}
.col-lg-12,.col-sm-12,.col-md-12{width:100%;min-width:0}
.col-12{width:100%;min-width:0}
.card{background:#fff;color:#18243b;border:1px solid #ccc;border-radius:12px}
.card-header,.card-body{padding:12px}
.content-inner{padding:12px;margin-top:-3rem}
.profile-img{margin-top:-4.375rem!important}
.profile-img img{border:4px solid white}
.avatar-100{height:100px;width:100px}
.form-control{padding:8px;border:1px solid #ccc}
.sidebar-toggle{border:1px solid #777;background:#ccc;width:44px;height:44px}
.ck.ck-toolbar{background:white;color:#333;padding:8px}
.ck.ck-editor__main>.ck-editor__editable{background:white;color:white;border:1px solid #ccc}
.ck-content{min-height:200px;padding:12px}
@media(max-width:991.98px){.navbar-toggler{display:block}.navbar-collapse:not(.show){display:none}}
"""
markup="""<!doctype html><html lang="pt-BR"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body class="light"><div class="main-content">
<div class="iq-banner">
<nav class="nav navbar iq-navbar mobile-unified-nav"><div class="navbar-inner">
<a class="navbar-brand">LinkFree</a>
<button class="sidebar-toggle" id="arrow" data-toggle="sidebar"><i class="icon"><svg class="icon-20" viewBox="0 0 24 24" width="20" height="20"><path d="M0 12H24"/></svg></i></button>
<button class="navbar-toggler">☰</button>
<div class="collapse navbar-collapse" id="navbarSupportedContent">
 <ul class="navbar-list">
 <li><a class="view-page-slim btn btn-primary btn-sm">Exibir Página</a></li>
 <li><a id="notification-drop" class="nav-link">🔔</a></li>
 <li><a id="navbarDropdown" class="nav-link">Perfil</a></li>
 </ul>
</div></div></nav>
<div class="iq-navbar-header welcome-slim header-block">
<div class="iq-container"><div class="row"><div class="col-md-12"><h1>Boas-vindas</h1><h5>Bem-vindo ao LinkFree</h5></div></div></div>
</div>
</div>
<div class="content-inner studio-profile-editor-page">
 <div class="row"><div class="col-lg-12">
 <div id="profile-card" class="card rounded"><div class="card-body">
  <div class="row"><div class="col-sm-12">
   <div class="profile-img profile-logo position-relative"><img id="avatar" class="avatar-100" width="100" height="100" alt="Foto de perfil" src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs="></div>
   <h3 class="card-header" id="profile-head">Meu Perfil</h3>
   <div class="form-group"><label id="label">Descrição da página</label>
    <div class="ck ck-editor"><div class="ck ck-toolbar"><button class="ck ck-button">Negrito</button></div>
    <div class="ck ck-editor__main"><div class="ck ck-editor__editable ck-content" role="textbox" contenteditable="true" id="editor"><p>Texto legível</p><h3>Subtítulo</h3><p class="ck-placeholder">Descrição</p></div></div></div>
   </div>
   <div class="form-group"><input class="form-control" value="Texto editável"></div>
  </div></div>
 </div></div>
 </div></div>
</div>
<div id="modal" class="modal" style="position:relative"><div id="modal-card" class="card">Conteúdo de modal preservado</div></div>
</body></html>"""

def measure(page):
    return page.evaluate("""() => {
      const at=s=>document.querySelector(s);
      const box=s=>at(s).getBoundingClientRect();
      const style=s=>getComputedStyle(at(s));
      return {
        overflow:document.documentElement.scrollWidth-innerWidth,
        welcomeBottom:box('.welcome-slim').bottom,
        avatarTop:box('#avatar').top,
        avatarH:box('#avatar').height,
        profileTop:box('.studio-profile-editor-page').top,
        cardGradient:style('#profile-card').backgroundImage,
        heading:style('#profile-head').color,
        label:style('#label').color,
        fieldBg:style('.form-control').backgroundColor,
        fieldFg:style('.form-control').color,
        editorBg:style('#editor').backgroundColor,
        editorFg:style('#editor').color,
        childFg:style('#editor p').color,
        canvasBg:style('.main-content').backgroundColor,
        toolbarBg:style('.ck-toolbar').backgroundColor,
        toolbarFg:style('.ck-toolbar').color,
        arrowBg:style('#arrow').backgroundColor,
        arrowBorder:style('#arrow').borderTopWidth,
        arrowSvg:box('#arrow svg').width,
        arrowWidth:box('#arrow').width,
        modalBg:style('#modal-card').backgroundImage
      };
    }""")

with sync_playwright() as p:
    browser=p.chromium.launch(channel="chrome",headless=True,args=["--no-sandbox","--disable-dev-shm-usage"])
    try:
        for width in [320,360,390,430,768,1024,1440]:
            context=browser.new_context(viewport={"width":width,"height":820})
            page=context.new_page()
            page.set_content(markup)
            page.add_style_tag(content=base_css)
            page.add_style_tag(content=palette+"\n"+nav+"\n"+gradient+"\n"+"/* Cabeçalho slim:"+sidebar_css+"\n"+page_css)
            light=measure(page)
            assert light["overflow"]<=2,(width,"horizontal overflow light",light)
            assert light["avatarTop"]>=light["welcomeBottom"]-1,(width,"avatar overlaps welcome",light)
            assert light["avatarH"]<=101,(width,"avatar oversized",light)
            assert light["cardGradient"].startswith("linear-gradient("),(width,"light card gradient absent",light)
            assert "rgb(255, 255, 255)" in light["cardGradient"] and "rgb(236, 242, 255)" in light["cardGradient"],(width,"incorrect light gradient",light)
            assert light["heading"]=="rgb(24, 36, 59)",(width,"light card title contrast",light)
            assert light["canvasBg"]=="rgb(243, 246, 252)",(width,"Light V1 canvas token unused",light)
            assert light["toolbarBg"]=="rgb(245, 247, 252)",(width,"Light V1 editor toolbar remains lilac",light)
            assert light["editorBg"]=="rgb(255, 255, 255)",(width,"light editor background",light)
            assert light["editorFg"]=="rgb(24, 36, 59)",(width,"light editor text contrast",light)
            assert light["childFg"]=="rgb(24, 36, 59)",(width,"light editor paragraphs",light)
            assert light["fieldFg"]=="rgb(24, 36, 59)",(width,"light form text on gradient",light)
            assert light["modalBg"]=="none",(width,"modal incorrectly inherits gradient",light)
            if width<=991:
                assert light["arrowBorder"]=="0px" and light["arrowBg"]=="rgba(0, 0, 0, 0)",(width,"arrow still card",light)
                assert light["arrowSvg"]<=16.5,(width,"arrow too large",light)

            page.evaluate("""() => {document.body.classList.remove('light');document.body.classList.add('dark')}""")
            dark=measure(page)
            assert dark["overflow"]<=2,(width,"horizontal overflow dark",dark)
            assert dark["avatarTop"]>=dark["welcomeBottom"]-1,(width,"dark avatar overlaps welcome",dark)
            assert dark["cardGradient"]=="none",(width,"dark card gradient accidentally changed",dark)
            assert dark["editorBg"]=="rgb(36, 44, 64)",(width,"dark editor background",dark)
            assert dark["editorFg"]=="rgb(244, 247, 253)",(width,"dark editor text contrast",dark)
            assert dark["childFg"]=="rgb(244, 247, 253)",(width,"dark editor paragraphs",dark)
            print(f"PASS {width}px: avatar, editor contrast, light gradient, dark unchanged, bare arrow")
            context.close()
    finally:
        browser.close()
# Admin configuration tab: verify the actual vendor + scoped config + Light V1 CSS.
# Regression: card-wide a:not(.btn) used to override active tab foreground
# with #204a9b, producing 1.47:1 over the blue #3a57e8 background.
admin_config=(views/"components/config/config.blade.php").read_text(encoding="utf-8")
config_css=admin_config.split("<style>",1)[1].split("</style>",1)[0]
assert "#exTab2 #myTab.nav-tabs .nav-link.active" in config_css
assert "a:not(.btn):not(.dropdown-item):not(.nav-link)" in gradient
vendor=(root/"assets/css/hope-ui.min.css").read_text(encoding="utf-8")
vendor_custom=(root/"assets/css/customizer.min.css").read_text(encoding="utf-8")
vendor_dark=(root/"assets/css/dark.min.css").read_text(encoding="utf-8")

tab_markup="""<!doctype html><html lang="pt-BR">
<head><meta charset="utf-8"></head>
<body class="light"><main class="main-content">
<div class="card"><div class="card-body">
<div id="exTab2"><ul id="myTab" class="nav nav-tabs">
<li class="nav-item"><a href="#1" id="home-tab" class="nav-link active">Configuração</a></li>
<li class="nav-item"><a href="#2" id="advanced-tab" class="nav-link">Avançado</a></li>
</ul></div></div></div></main></body></html>"""

def tab_contrast(text_color,background_color):
    import re
    def luminosity(color):
        rgb=[int(value)/255 for value in re.findall(r"\d+",color)[:3]]
        linear=[x/12.92 if x<=0.04045 else ((x+0.055)/1.055)**2.4 for x in rgb]
        return sum(x*y for x,y in zip(linear,(0.2126,0.7152,0.0722)))
    top,bottom=sorted([luminosity(text_color),luminosity(background_color)],reverse=True)
    return (top+0.05)/(bottom+0.05)

with sync_playwright() as playwright:
    browser=playwright.chromium.launch(channel="chrome",headless=True,args=["--no-sandbox","--disable-dev-shm-usage"])
    try:
        page=browser.new_page(viewport={"width":390,"height":820})
        page.set_content(tab_markup)
        for css in (vendor,vendor_dark,vendor_custom,palette,gradient,config_css):
            page.add_style_tag(content=css)
        page.add_style_tag(content="*,*::before,*::after{animation:none!important;transition:none!important}")
        for mode in ("light","dark"):
            page.evaluate("(mode)=>{document.body.className=mode}",mode)
            colors=page.evaluate("""() => {
                const active=getComputedStyle(document.querySelector('#home-tab'));
                const inactive=getComputedStyle(document.querySelector('#advanced-tab'));
                return {
                    active:[active.color,active.backgroundColor],
                    inactive:[inactive.color,inactive.backgroundColor]
                }
            }""")
            ratio=tab_contrast(*colors["active"])
            assert ratio>=4.5,(mode,"Configuração active tab WCAG AA",round(ratio,2),colors)
            if mode=="light":
                assert colors["active"]==["rgb(255, 255, 255)","rgb(58, 87, 232)"],(mode,"active tab changed colors",colors)
            print(f"PASS {mode} admin Configuração tab text/background: {ratio:.2f}:1")
        page.close()
    finally:
        browser.close()
print("ADMIN CONFIG TAB CONTRAST REGRESSION PASS")

print("PROFILE + EDITOR + LIGHT GRADIENT AUDIT PASS (browser fixtures)")
