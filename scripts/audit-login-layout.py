#!/usr/bin/env python3
"""Login redesign regression: browser CSS fixture + source contracts.
No authenticated Laravel or production integration is claimed.
"""
from pathlib import Path
from playwright.sync_api import sync_playwright
import re

ROOT = Path(__file__).resolve().parents[1] / "linkstack"
login = (ROOT / "resources/views/auth/login.blade.php").read_text(encoding="utf-8")
css = (ROOT / "assets/css/linkfree-login.css").read_text(encoding="utf-8")
for contract in [
    "route('login')", "@csrf", 'name="email"', 'name="password"',
    'name="remember"', "route('password.request')", "ENABLE_SOCIAL_LOGIN",
    "ALLOW_REGISTRATION", "route('register')", "x-auth-session-status",
    "x-auth-validation-errors", "lf-login-shell", "lf-login-submit",
    "linkfree-login.css",
]:
    assert contract in login, ("Missing original authentication contract",contract)
for provider in ("facebook","twitter","google","github"):
    assert "route('social.redirect','"+provider+"')" in login, ("Missing social provider",provider)
assert "<x-guest-layout>" in login and "<x-auth-card>" in login
assert "100dvh" in css and "@media(max-width:420px)" in css

html = """<!doctype html><html lang="pt-BR"><head>
<meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body class="light"><div class="container d-flex align-items-center justify-content-center" style="height:100vh">
<div class="col-sm-12 col-lg-6">
<section class="lf-login-shell"><aside class="lf-login-visual"><div class="lf-login-visual-center">
<h2>LinkFree</h2></div></aside><div class="lf-login-panel">
<a class="lf-login-brand">LinkFree</a>
<div class="lf-login-heading"><h1>Acessar</h1><p>Continue sua sessão</p></div>
<form class="lf-login-form">
<div class="row"><div class="col-lg-12"><div class="form-group"><label class="form-label" for="email">Email</label>
<input type="email" class="form-control" name="email" id="email" required></div></div>
<div class="col-lg-12"><div class="form-group"><label class="form-label" for="password">Senha</label>
<input type="password" class="form-control" name="password" id="password" required></div></div>
<div class="col-lg-12 lf-login-remember-row d-flex justify-content-between">
<div class="form-check"><input type="checkbox" class="form-check-input" id="remember"><label class="form-check-label" for="remember">Lembrar</label></div>
<a href="#recover">Esqueci senha</a></div></div>
<div class="d-flex justify-content-center"><button class="btn btn-primary lf-login-submit">Entrar</button></div>
</form></div></section></div></div></body></html>"""

def contrast(a,b):
    def lum(color):
        parts=[int(v)/255 for v in re.findall(r"\d+",color)[:3]]
        linear=[v/12.92 if v<=.04045 else ((v+.055)/1.055)**2.4 for v in parts]
        return sum(a*b for a,b in zip(linear,(.2126,.7152,.0722)))
    hi,lo=sorted((lum(a),lum(b)),reverse=True)
    return (hi+.05)/(lo+.05)

with sync_playwright() as playwright:
    browser=playwright.chromium.launch(channel="chrome",headless=True,args=["--no-sandbox","--disable-dev-shm-usage"])
    try:
        for width in (320,360,390,430,768,1024,1440):
            page=browser.new_page(viewport={"width":width,"height":820})
            page.set_content(html)
            page.add_style_tag(content=css)
            for mode in ("light","dark"):
                page.evaluate("(mode)=>document.body.className=mode",mode)
                data=page.evaluate("""() => {
                    const select=s=>document.querySelector(s);
                    const bounds=s=>select(s).getBoundingClientRect();
                    const styles=s=>getComputedStyle(select(s));
                    return {
                       overflow:document.documentElement.scrollWidth-innerWidth,
                       shell:bounds('.lf-login-shell'),
                       form:bounds('.lf-login-form'),
                       submit:bounds('.lf-login-submit'),
                       fg:styles('.lf-login-panel').color,
                       bg:styles('.lf-login-panel').backgroundColor,
                       btnFg:styles('.lf-login-submit').color,
                       btnBg:styles('.lf-login-submit').backgroundColor,
                       fieldFg:styles('#email').color,
                       fieldBg:styles('#email').backgroundColor,
                       fieldBorder:styles('#email').borderTopColor,
                       visual:styles('.lf-login-visual').display
                    };
                }""")
                assert data["overflow"]<=2,(width,mode,"horizontal scroll",data)
                assert data["shell"]["width"]<=width+1,(width,mode,"shell cut",data)
                assert data["form"]["width"]>=200,(width,mode,"form too narrow",data)
                assert data["submit"]["width"]>=200,(width,mode,"submit too narrow",data)
                assert contrast(data["fg"],data["bg"])>=4.5,(width,mode,"panel contrast",data)
                assert contrast(data["btnFg"],data["btnBg"])>=4.5,(width,mode,"button contrast",data)
                assert contrast(data["fieldFg"],data["fieldBg"])>=4.5,(width,mode,"field contrast",data)
                assert contrast(data["fieldBorder"],data["fieldBg"])>=3,(width,mode,"field border",data)
                if width<=900:
                    assert data["visual"]=="none",(width,mode,"mobile visual should be hidden")
                else:
                    assert data["visual"]!="none",(width,mode,"desktop visual missing")
                print(f"PASS login {width}px {mode}: no overflow, controls and WCAG contrast")
            page.close()
    finally:
        browser.close()
print("LOGIN PREMIUM REDESIGN AUDIT PASS")
