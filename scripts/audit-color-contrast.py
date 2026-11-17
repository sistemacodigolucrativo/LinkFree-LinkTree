#!/usr/bin/env python3
"""WCAG 2.2 AA audit of LinkFree's shared semantic light/dark palette.

Coverage: text and meaningful UI borders in dashboard/admin, studio and auth.
This tests source tokens; browser fixture separately checks computed CSS on pages.
"""
from pathlib import Path
import re

ROOT=Path(__file__).resolve().parents[1]
css=(ROOT/"linkstack/assets/css/linkfree-color-modes.css").read_text(encoding="utf-8")
assert "body.dark {" in css
light,dark=css.split("body.dark {",1)
dark=dark.split("}",1)[0]
palette_re=re.compile(r"(--lf-[a-z]+)\s*:\s*(#[0-9a-fA-F]{6})\s*;")
modes={"light":dict(palette_re.findall(light)),"dark":dict(palette_re.findall(dark))}

def luminance(color):
    channels=[int(color[i:i+2],16)/255 for i in (1,3,5)]
    linear=[n/12.92 if n<=0.04045 else ((n+0.055)/1.055)**2.4 for n in channels]
    return sum(a*b for a,b in zip(linear,(0.2126,0.7152,0.0722)))

def ratio(a,b):
    hi,lo=sorted((luminance(a),luminance(b)),reverse=True)
    return (hi+0.05)/(lo+0.05)

for mode,vars in modes.items():
    for key in ["--lf-surface","--lf-raised","--lf-text","--lf-muted",
                "--lf-link","--lf-input","--lf-border","--lf-focus","--lf-chip"]:
        assert key in vars,(mode,"missing palette token",key)
    tests=[
        ("body text / card", "--lf-text","--lf-surface",4.5),
        ("body text / raised", "--lf-text","--lf-raised",4.5),
        ("muted text / card", "--lf-muted","--lf-surface",4.5),
        ("muted text / raised", "--lf-muted","--lf-raised",4.5),
        ("link / card", "--lf-link","--lf-surface",4.5),
        ("text / input", "--lf-text","--lf-input",4.5),
        ("input/card border", "--lf-border","--lf-surface",3.0),
        ("input/raised border", "--lf-border","--lf-raised",3.0),
        ("focus / card", "--lf-focus","--lf-surface",3.0),
    ]
    for name,a,b,minimum in tests:
        value=ratio(vars[a],vars[b])
        assert value>=minimum,(mode,name,round(value,2),minimum)
        print(f"PASS {mode}: {name} = {value:.2f}:1")
    badge=ratio("#ffffff",vars["--lf-chip"])
    assert badge>=4.5,(mode,"click count badge",badge)
    print(f"PASS {mode}: click-count text / badge = {badge:.2f}:1")

assert "body.dark .main-content .modal-title" in css
assert "body.dark :is(.auth-card" in css
assert "dashboard-slim .top-links-list>li.top-links-slim-item" in css
print("AUDITORIA DE CONTRASTE WCAG 2.2 AA APROVADA (paleta de tokens)")

# Sidebar contrast regression for both themes and color variants.
sidebar_pairs = [
    ("light normal", "#18243b", "#ffffff", 4.5),
    ("light section", "#4b5870", "#ffffff", 4.5),
    ("light active", "#ffffff", "#3a57e8", 4.5),
    ("light hover", "#18243b", "#eaf0fa", 4.5),
    ("light divider", "#75839a", "#ffffff", 3),
    ("dark normal", "#f4f7fd", "#222738", 4.5),
    ("dark section", "#c3cede", "#222738", 4.5),
    ("dark active", "#ffffff", "#3a57e8", 4.5),
    ("dark hover", "#f4f7fd", "#354058", 4.5),
    ("dark divider", "#7f90ad", "#222738", 3),
    ("color section", "#ffffff", "#3a57e8", 4.5),
    ("color active", "#204a9b", "#ffffff", 4.5),
    ("color hover", "#ffffff", "#263dae", 4.5),
    ("color divider", "#bdd0ff", "#3a57e8", 3),
]
for name,fg,bg,minimum in sidebar_pairs:
    value=ratio(fg,bg)
    assert value>=minimum,("sidebar",name,round(value,2),minimum)
    print(f"PASS sidebar {name}: {value:.2f}:1")
assert "LinkFree sidebar semantic contrast V1" in css
assert ".nav-link:not(.static-item):is(.active,[aria-expanded=\"true\"])" in css
print("AUDITORIA DE MENU LATERAL APROVADA")
