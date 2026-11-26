#!/usr/bin/env python3
"""Browser regression for read-only inline previews on Gerenciar blocos.

Runs against the shipped card CSS and JS; does not require authentication, database,
or a deployed Laravel session. The production public theme is not simulated.
"""
from pathlib import Path
from html import escape
from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[1] / "linkstack"
blade = (ROOT / "resources/views/studio/blocks.blade.php").read_text(encoding="utf-8")
script = (ROOT / "assets/js/linkfree-block-previews.js").read_text(encoding="utf-8")
css = blade.split("<style>", 1)[1].split("</style>", 1)[0]
kinds = ("predefined", "link", "vcard", "email", "telephone", "heading", "spacer", "text")

for kind in kinds:
    assert 'case "' + kind + '":' in script, f"Missing preview renderer: {kind}"
assert 'sandbox=""' in blade, "Preview iframes must be sandboxed"
assert "data-preview-brand-css" in blade and "data-preview-skeleton-css" in blade
assert "type=\"button\"" in blade and "aria-expanded=\"false\"" in blade
assert "route('showButtons')" in blade and "route('showLink', $editingLink->id)" in blade
assert "fetch(" not in script and ".submit(" not in script

cards = "".join(
    f'<article class="lf-block-card" data-block-kind="{escape(kind)}">'
    f'<a class="lf-block-choice" href="/original-editor?block={kind}">'
    f'<span class="lf-block-icon">◉</span><span class="lf-block-copy">'
    f'<span class="lf-block-title">{escape(kind)}</span></span></a>'
    f'<div class="lf-block-preview-controls">'
    f'<button class="lf-block-preview-toggle" type="button" aria-expanded="false" '
    f'aria-controls="preview-{kind}">Prévia</button></div>'
    f'<div class="lf-block-preview-panel" id="preview-{kind}" aria-hidden="true" inert>'
    f'<div class="lf-block-preview-content"><div class="lf-block-preview-stage">'
    f'<iframe class="lf-block-preview-frame" title="Prévia" sandbox="" tabindex="-1">'
    f'</iframe></div></div></div></article>' for kind in kinds
)
html = (
    '<!doctype html><html lang="pt-BR"><head>'
    '<meta name="viewport" content="width=device-width,initial-scale=1"></head><body>'
    '<main class="lf-blocks-page" data-preview-brand-css="" data-preview-skeleton-css="" '
    'data-preview-icon-base="">'
    '<div class="lf-block-grid">' + cards + '</div></main></body></html>'
)
examples = {
    "predefined": "Site predefinido",
    "link": "Meu site",
    "vcard": "Salvar meu contato",
    "email": "Enviar e-mail",
    "telephone": "Ligar agora",
    "heading": "<h2>Meu título</h2>",
    "spacer": 'class="button-spacer"',
    "text": "Este é um exemplo de texto",
}

with sync_playwright() as playwright:
    browser = playwright.chromium.launch(
        headless=True, args=["--no-sandbox", "--disable-dev-shm-usage"]
    )
    try:
        for width in (320, 390, 768, 1280):
            page = browser.new_page(viewport={"width": width, "height": 840})
            page.set_content(html)
            page.add_style_tag(content=css)
            page.add_script_tag(content=script)
            assert page.locator(".lf-block-card").count() == len(kinds)
            for kind in kinds:
                card = page.locator(f'.lf-block-card[data-block-kind="{kind}"]')
                button = card.locator(".lf-block-preview-toggle")
                panel = card.locator(".lf-block-preview-panel")
                frame = card.locator("iframe")
                original_href = card.locator("a.lf-block-choice").get_attribute("href")
                assert button.get_attribute("aria-expanded") == "false"
                assert frame.get_attribute("srcdoc") is None
                button.click()
                assert button.get_attribute("aria-expanded") == "true", (width, kind)
                assert panel.get_attribute("aria-hidden") == "false", (width, kind)
                assert panel.get_attribute("inert") is None, (width, kind)
                assert "is-preview-open" in (card.get_attribute("class") or "")
                assert examples[kind] in (frame.get_attribute("srcdoc") or ""), (width, kind)
                assert frame.get_attribute("sandbox") == ""
                assert card.locator("a.lf-block-choice").get_attribute("href") == original_href
                assert page.url == "about:blank", (width, kind, "preview caused navigation")
                button.click()
                assert button.get_attribute("aria-expanded") == "false", (width, kind)
                assert panel.get_attribute("aria-hidden") == "true", (width, kind)
                assert panel.get_attribute("inert") is not None, (width, kind)
            page.wait_for_timeout(350)  # Allow expand/collapse CSS transitions to settle.
            sizes = page.evaluate("""() => {
              const items = [...document.querySelectorAll('.lf-block-card')].map(
                el => el.getBoundingClientRect()
              );
              let intersections = 0;
              for (let i = 0; i < items.length; i++) {
                for (let j = i + 1; j < items.length; j++) {
                  const a=items[i], b=items[j];
                  if (a.left < b.right-1 && b.left < a.right-1 &&
                      a.top < b.bottom-1 && b.top < a.bottom-1) intersections++;
                }
              }
              return {
                overflow: document.documentElement.scrollWidth - innerWidth,
                intersections,
                cardWidths: items.map(r => r.width)
              };
            }""")
            assert sizes["overflow"] <= 2, (width, "horizontal overflow", sizes)
            assert sizes["intersections"] == 0, (width, "cards overlap", sizes)
            assert min(sizes["cardWidths"]) > 180, (width, "card too narrow", sizes)
            print(f"PASS block previews {width}px: all eight expand/collapse, isolated, no overlap")
            page.close()
    finally:
        browser.close()

print("BLOCK PREVIEWS REGRESSION PASSED — browser fixture, not live Laravel")
