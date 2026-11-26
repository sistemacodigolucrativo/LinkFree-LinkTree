#!/usr/bin/env python3
"""Public marketing HTTP smoke in the existing isolated Laravel/SQLite fixture.

Reuses the localhost-only QA environment from qa-functional-isolated.yml.
No authentication, production access or data writes.
"""
import json
import os
import re
import urllib.error
import urllib.parse
import urllib.request

ROOT = "http://127.0.0.1:18765"
assert os.environ.get("QA_EXPECTED_SITE") == ROOT, "Refusing to test a non-isolated host"
results = []

def get(path):
    url = urllib.parse.urljoin(ROOT, path)
    assert urllib.parse.urlparse(url).netloc == "127.0.0.1:18765"
    request = urllib.request.Request(url, headers={"User-Agent": "LinkFree-public-synthetic-QA"})
    with urllib.request.urlopen(request, timeout=18) as response:
        return response.status, response.headers, response.read().decode("utf-8", "replace")

def verify(label, fn):
    try:
        detail = fn()
        results.append({"name": label, "status": "PASS", "detail": detail})
        print("PASS", label, flush=True)
    except Exception as error:
        results.append({"name": label, "status": "FAIL", "detail": str(error)})
        print("FAIL", label, error, flush=True)

def test_home():
    code, headers, page = get("/")
    assert code == 200
    assert "/plataforma/recursos" in page and "/plataforma/proximas-implementacoes" in page
    assert "Recursos reais. Evolução transparente." in page
    assert "Conhecer todos os recursos" in page
    assert "Criar minha página" in page
    assert headers.get("Set-Cookie") is None, "Public homepage should not set session cookie"
    return "Home links both public pages; no auth required"

def test_resources():
    code, headers, page = get("/plataforma/recursos")
    assert code == 200
    for fragment in (
        "Mais do que um link.", "Seu espaço na internet.",
        "Página pública personalizada", "Links e blocos", "Temas e aparência",
        "Cliques no painel", "Recurso VIP", "Convites e indicações",
        "Selo verificado", "Portabilidade condicionada",
    ):
        assert fragment in page, f"Missing verified feature: {fragment}"
    assert 'href="http://127.0.0.1:18765/plataforma/recursos"' in page, "Canonical missing"
    assert "<meta name=\"description\" content=" in page
    assert "/assets/css/linkfree-public-pages.css" in page
    assert headers.get("Set-Cookie") is None, "Feature page should not set cookie"
    return "Public catalog renders verified member features and tier disclaimers"

def test_roadmap():
    code, headers, page = get("/plataforma/proximas-implementacoes")
    assert code == 200
    picker_installed = os.path.isfile(
        "/tmp/linkfree-audit/linkstack/assets/js/linkfree-platform-picker.js"
    )
    if picker_installed:
        assert "Nenhuma nova ferramenta anunciada no momento." in page
        assert "Prévia interativa dos botões por plataforma" not in page
    else:
        assert "Prévia interativa dos botões por plataforma" in page
        assert "Em validação" in page
        assert "Nenhuma nova ferramenta anunciada no momento." not in page
    assert "Sem datas inventadas" in page
    assert 'href="http://127.0.0.1:18765/plataforma/proximas-implementacoes"' in page
    # Previously requested tools have existing routes and must not be re-announced.
    for delivered in ("Programa de indicações", "Central de denúncias", "Solicitação de selo"):
        assert delivered not in page
    assert headers.get("Set-Cookie") is None
    return "Roadmap public, no delivered or fabricated tools advertised"

def test_styles():
    code, headers, css = get("/assets/css/linkfree-public-pages.css")
    assert code == 200 and ".lfp-grid" in css and "max-width:600px" in css
    assert "prefers-reduced-motion" in css and ":focus-visible" in css
    return "Responsive and keyboard-focus CSS asset served"

for label, fn in (
    ("Public home navigation and copy", test_home),
    ("Public capabilities with accurate tier notes", test_resources),
    ("Roadmap excludes available and unapproved tools", test_roadmap),
    ("Public premium responsive CSS", test_styles),
):
    verify(label, fn)

output = os.environ.get("QA_PUBLIC_REPORT", "/tmp/linkfree-audit/public-marketing-http.json")
with open(output, "w", encoding="utf-8") as file:
    json.dump({"origin": ROOT, "checks": results,
               "passed": sum(x["status"] == "PASS" for x in results),
               "failed": sum(x["status"] == "FAIL" for x in results)}, file,
              indent=2, ensure_ascii=False)
print("PUBLIC MARKETING HTTP SUMMARY", json.dumps({
    "passed": sum(x["status"] == "PASS" for x in results),
    "failed": sum(x["status"] == "FAIL" for x in results),
}), flush=True)
if any(x["status"] != "PASS" for x in results):
    raise SystemExit(1)
