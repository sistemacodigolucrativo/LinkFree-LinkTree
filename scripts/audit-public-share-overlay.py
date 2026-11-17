#!/usr/bin/env python3
"""Check public LinkStack profile top-spacing and floating share control.

Tests CSS extracted from production Blade, with the original public theme
.sharediv behavior reproduced. No authenticated VPS browser session is used.
"""
from pathlib import Path

from playwright.sync_api import sync_playwright

root=Path(__file__).resolve().parents[1]/"linkstack"
share=(root/"resources/views/linkstack/modules/share-button.blade.php").read_text(encoding="utf-8")
layout=(root/"resources/views/linkstack/layout.blade.php").read_text(encoding="utf-8")
base_theme=(root/"assets/linkstack/css/share.button.css").read_text(encoding="utf-8")
assert 'class="sharediv"' in share, "Share wrapper must stay in Blade"
assert "navigator.share" in share and "navigator.clipboard.writeText" in share, "Share behavior lost"
assert '@stack(\'linkstack-body-start\')' in layout, "Public layout no longer mounts share widget"
assert 'body > .sharediv' in share, "Share wrapper not independently positioned"
assert "position: fixed !important" in share
assert "z-index: 100000 !important" in share
assert "pointer-events: auto" in share, "Share click target lost"
assert "padding-bottom: 40px" in base_theme and "position:relative" in base_theme, "Legacy layout regression changed"
overlay_css=share.split('<style id="linkfree-public-share-overlay">',1)[1].split('</style>',1)[0]

old_css="""
*,*::before,*::after{box-sizing:border-box}
html,body{margin:0;padding:0;max-width:100%;font:16px Arial,sans-serif}
body{background:#101014;color:white}
.container{position:relative;width:100%;max-width:600px;margin:0 auto;text-align:center;box-sizing:border-box}
.column{width:100%;box-sizing:border-box;margin-top:5%}
.sharediv{position:relative;top:30px;right:30px;padding-bottom:40px;text-align:right}
.sharebutton{display:inline-flex;align-items:center;justify-content:center;
width:84px;height:84px;border:0;border-radius:50%;background:#782e9d;color:white;cursor:pointer}
.profile{margin:0 auto;padding-top:0}
.profile img{display:block;width:128px;height:128px;border-radius:50%;margin:auto}
.profile h1{font-size:28px}
"""

html="""<!doctype html><html lang="pt-BR"><head><meta name="viewport" content="width=device-width,initial-scale=1"></head><body>
<div align="right" class="sharediv" id="share"><div><button class="sharebutton share-button" type="button" aria-label="Compartilhar">➦</button></div></div>
<span class="copy-icon" aria-hidden="true"></span>
<div class="container"><div class="row"><div class="column" style="margin-top:5%">
<div class="profile" id="profile"><img alt="Foto de perfil" src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs="/>
<h1>Marcelo</h1><a href="#link">Instagram</a></div>
</div></div></div>
</body></html>"""

def measure(page):
    return page.evaluate("""() => {
      const share=document.querySelector('.sharediv');
      const button=document.querySelector('.share-button');
      const content=document.querySelector('#profile');
      const sr=share.getBoundingClientRect();
      const br=button.getBoundingClientRect();
      const cr=content.getBoundingClientRect();
      const sh=getComputedStyle(share);
      return {
        sharePosition:sh.position, shareTop:sr.top, shareLeft:sr.left, shareRight:sr.right,
        shareZIndex:sh.zIndex, sharePadding:sh.paddingBottom,
        contentTop:cr.top, buttonTop:br.top, buttonRight:br.right,
        docWidth:document.documentElement.scrollWidth, viewport:innerWidth,
        clickStyle:getComputedStyle(button).pointerEvents
      };
    }""")

with sync_playwright() as p:
    browser=p.chromium.launch(channel="chrome",headless=True,args=["--no-sandbox","--disable-dev-shm-usage"])
    try:
        for width in (320,360,390,430,768,1024,1440):
            context=browser.new_context(viewport={"width":width,"height":820})
            page=context.new_page()
            page.set_content(html)
            page.add_style_tag(content=old_css)
            old=measure(page)
            assert old["contentTop"]>=120,(width,"test fixture did not reproduce old black bar",old)
            page.add_style_tag(content=overlay_css)
            new=measure(page)
            assert new["sharePosition"]=="fixed",(width,"share still in flow",new)
            assert new["sharePadding"]=="0px",(width,"share still reserves bottom gap",new)
            assert new["contentTop"]<=45,(width,"blank strip above profile remains",new)
            assert old["contentTop"]-new["contentTop"]>=120,(width,"did not remove legacy space",old,new)
            assert new["shareLeft"]>=0 and new["shareRight"]<=width+1,(width,"floating button outside viewport",new)
            assert new["buttonTop"]>=0 and new["buttonRight"]<=width+1,(width,"button clipped",new)
            assert int(new["shareZIndex"])>=100000,(width,"share behind theme layers",new)
            assert new["docWidth"]<=width+1,(width,"horizontal scroll",new)
            assert new["clickStyle"]=="auto",(width,"share button unclickable",new)
            page.evaluate("""() => {
              window.shareCount=0;
              document.querySelector('.share-button').addEventListener('click',()=>window.shareCount++);
            }""")
            page.locator(".share-button").click()
            assert page.evaluate("window.shareCount")==1,(width,"share click blocked")
            page.evaluate("document.querySelector('.sharediv').style.visibility='hidden'")
            hidden=measure(page)
            assert hidden["contentTop"]==new["contentTop"],(width,"disabled share still reserves a top bar",hidden)
            print(f"PASS {width}px: removed {round(old['contentTop']-new['contentTop'])}px of blank flow; share visible on top and interactive")
            context.close()
    finally:
        browser.close()
print("PUBLIC PROFILE TOP GAP + SHARE OVERLAY: PASS (browser fixture)")
