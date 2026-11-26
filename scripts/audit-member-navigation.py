#!/usr/bin/env python3
"""Targeted, read-only member-topbar and link/button UX contract regression.

Static checks cover all 14 locales, existing route reuse and exact badge source.
The Chromium fixture exercises actual layout CSS, not an authenticated Laravel
session; live end-to-end behavior remains a separate manual acceptance gate.
"""
from pathlib import Path
import re
from playwright.sync_api import sync_playwright

root = Path(__file__).resolve().parents[1] / "linkstack"
views = root / "resources/views"
sidebar = (views / "layouts/sidebar.blade.php").read_text(encoding="utf-8")
links = (views / "studio/links.blade.php").read_text(encoding="utf-8")
form = (views / "components/pageitems/predefined-form.blade.php").read_text(encoding="utf-8")
blocks = (views / "studio/blocks.blade.php").read_text(encoding="utf-8")
verification = (views / "studio/verification.blade.php").read_text(encoding="utf-8")
routes = (root / "routes/web.php").read_text(encoding="utf-8")
badge = (views / "components/verify-svg.blade.php").read_text(encoding="utf-8")

def check(condition, message):
    assert condition, message
    print("OK:", message)

patterns = (
    "dashboard", "studio/visitors", "studio/links", "studio/add-link",
    "studio/edit-link/*", "studio/page-icons", "studio/blocks",
    "studio/theme", "studio/referrals", "studio/verification",
    "studio/page", "studio/no_page_name", "studio/profile",
    "studio/button-editor/*",
)
for pattern in patterns:
    check("'" + pattern + "' =>" in sidebar, "Topbar route: " + pattern)
check("if (!$isAdminPanel)" in sidebar and
      'lf-member-titled-nav' in sidebar and
      "memberNavbarKey === 'verified_badge'" in sidebar,
      "Member titles do not replace Admin-specific navbar")
check("@include('components.verify-svg')" in sidebar and
      'fill="#179cf0"' in badge and 'fill="#fcfcfc"' in badge and
      'svg.badge{' in badge and '      .badge{' not in badge,
      "Verified badge reuses original public-profile SVG")
check("route('showBlocks')" in links and
      "->name('showBlocks')" in routes and
      "Route::get('/studio/blocks'" in routes,
      "CTA reuses existing authenticated Gerenciar Blocos route")
check("->name('showButtons')" in routes and
      "Route::get('/studio/add-link'" in routes,
      "Original Adicionar Bloco route remains available")
check("{{ __('member_ui.add_link_block') }}" in links and
      "aria-label=\"{{ __('member_ui.add_link_block') }} — {{ __('member_ui.add_link_block_action') }}\"" in links,
      "CTA label and accessible action match")
check("{{ __('member_ui.links_blocks') }}" in links and
      "memberNavbarTitle.' — '" in sidebar,
      "Links e Blocos appears in card, topbar and HTML title")
check("<label for=\"predefined-button\" class='form-label'>{{ __('member_ui.button_icon') }}</label>" in form and
      "id='predefined-button'" in form and "name='button'" in form and
      "foreach ($buttons as $b)" in form,
      "Select label changed without altering its options or form name")
check("{{ __('member_ui.manage_blocks') }}" in blocks and
      "lf-block-grid" in blocks, "Blocks screen and selection remain present")
check("@include('components.verify-svg')" in verification and
      '<i class="bi bi-patch-check"' not in verification,
      "Verified title uses public profile exact badge, not alternate icon")
keys = (
    "dashboard", "visitor_sources", "links_blocks", "add_block",
    "edit_block", "page_icons", "manage_blocks", "custom_backgrounds",
    "referrals", "verified_badge", "my_profile", "account_info",
    "button_editor", "button_icon", "add_link_block", "add_link_block_action",
)
expected_locales = (
    "ar", "bg", "de", "el", "en", "es", "fr", "gl", "pt-BR",
    "ru", "sv", "vi", "zh", "zh-TW",
)
for locale in expected_locales:
    file = root / "resources/lang" / locale / "member_ui.php"
    check(file.is_file(), "Locale exists: " + locale)
    source = file.read_text(encoding="utf-8")
    found = re.findall(r"^\s*'([a-z_]+)'\s*=>", source, re.M)
    check(len(found) == len(keys) and set(found) == set(keys),
          "All interface strings present: " + locale)
ptbr = (root / "resources/lang/pt-BR/member_ui.php").read_text(encoding="utf-8")
for value in ("'links_blocks' => 'Links e Blocos'",
              "'button_icon' => 'Selecionar ícone do botão'",
              "'add_link_block' => '+ Bloco-Link'",
              "'custom_backgrounds' => 'Fundos Personalizados'"):
    check(value in ptbr, "Exact pt-BR text: " + value)

# Responsive smoke fixture using the exact CSS from the shared Blade layout.
css = "/* Cabeçalho slim:" + sidebar.split("/* Cabeçalho slim:", 1)[1].split("</style>", 1)[0]
base_css = """
*{box-sizing:border-box}
html,body{margin:0;padding:0;font:16px/1.4 Arial,sans-serif}
.main-content,.mobile-unified-nav{width:100%;min-width:0}
.navbar-inner{display:flex;align-items:center;justify-content:space-between;width:100%}
.navbar-brand{display:block;white-space:nowrap}
.navbar-collapse{display:flex;align-items:center}
.navbar-list{display:flex;list-style:none;padding:0;margin:0;align-items:center}
.navbar-list>li{display:block}
.nav-link{display:inline-flex;align-items:center;justify-content:center;min-width:40px;min-height:44px;padding:5px}
.sidebar-toggle{border:0;background:transparent}
"""
html_template = """<!doctype html><html lang="pt-BR"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<style>%%CSS%%</style></head><body><main class="main-content">
<nav class="nav navbar navbar-expand-lg navbar-light iq-navbar mobile-unified-nav">
<div class="container-fluid navbar-inner lf-mobile-titled-nav lf-member-titled-nav">
<a href="#" class="navbar-brand">LinkFree</a>
<button class="sidebar-toggle" aria-label="Abrir ou fechar menu">←</button>
<span class="lf-mobile-nav-title lf-member-nav-title"><span class="lf-member-nav-copy">%%TITLE%%</span>%%BADGE%%</span>
<div class="collapse navbar-collapse" id="navbarSupportedContent">
<ul class="mb-2 navbar-nav ms-auto align-items-center navbar-list mb-lg-0">
<li class="nav-item"><a class="nav-link" href="#" aria-label="Notificações">◉</a></li>
<li class="nav-item"><a class="nav-link" href="#" aria-label="Perfil">●</a></li>
</ul></div></div></nav></main></body></html>"""
with sync_playwright() as playwright:
    browser = playwright.chromium.launch(channel="chrome", headless=True, args=["--no-sandbox", "--disable-dev-shm-usage"])
    try:
        for title, use_badge in (("Fundos Personalizados", False),
                                 ("Selo Verificado", True)):
            for width in (320, 360, 375, 768, 991, 992, 1280):
                page = browser.new_page(viewport={"width": width, "height": 850})
                html = html_template.replace("%%CSS%%", base_css + css)
                html = html.replace("%%TITLE%%", title).replace(
                    "%%BADGE%%",
                    '<span class="lf-member-verified-icon" aria-hidden="true">' + badge + "</span>"
                    if use_badge else "")
                page.set_content(html)
                geometry = page.evaluate("""() => {
                  const box = x => {let r=document.querySelector(x).getBoundingClientRect();
                    return {left:r.left,right:r.right,width:r.width,top:r.top,bottom:r.bottom}};
                  return {overflow:document.documentElement.scrollWidth>innerWidth+1,
                    title:box('.lf-member-nav-title'),arrow:box('.sidebar-toggle'),
                    bell:box('[aria-label="Notificações"]'),
                    display:getComputedStyle(document.querySelector('.lf-member-nav-title')).display,
                    badge:document.querySelector('.lf-member-verified-icon')!==null};
                }""")
                check(not geometry["overflow"], f"No horizontal overflow {width}px: {title}")
                check(geometry["display"] != "none" and geometry["title"]["width"] > 0,
                      f"Title visible {width}px: {title}")
                check(geometry["title"]["left"] >= geometry["arrow"]["right"] - 3,
                      f"Title clears back arrow {width}px: {title}")
                check(geometry["title"]["right"] <= geometry["bell"]["left"] + 3,
                      f"Title clears navbar actions {width}px: {title}")
                check(geometry["badge"] == use_badge,
                      f"Badge matches route {width}px: {title}")
                page.close()
    finally:
        browser.close()
print("MEMBER TOPBAR STATIC + RESPONSIVE FIXTURE CHECKS PASSED")
