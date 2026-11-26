#!/usr/bin/env python3
"""Contratos de copy pública e catálogo. Não acessa produção nem dados de membros."""
from pathlib import Path
import re

root = Path(__file__).resolve().parents[1] / "linkstack"

def read(name):
    return (root / name).read_text(encoding="utf-8")

def require(ok, message):
    assert ok, message
    print("OK:", message)

catalog = read("app/Services/MemberFeatureCatalog.php")
controller = read("app/Http/Controllers/PublicCatalogController.php")
routes = read("routes/web.php")
page = read("resources/views/public/catalog.blade.php")
home = read("resources/views/home.blade.php")
content = read("app/Services/PublicSiteContent.php")
seo = read("app/Http/Controllers/SeoController.php")
styles = read("assets/css/linkfree-public-catalog.css")
auth = read("resources/views/auth/login.blade.php")
register = read("resources/views/auth/lf-variants.blade.php")

require("Route::get('/plataforma/recursos'" in routes and
        "Route::get('/plataforma/proximas-implementacoes'" in routes and
        "->name('public.resources')" in routes and
        "->name('public.roadmap')" in routes and
        routes.index("Route::get('/plataforma/recursos'") < routes.index("if(config('advanced-config.custom_url_prefix')"),
        "Public routes are registered before profile catchalls")

require("->middleware('disableCookies')" in routes and
        "PublicSeo::customDomain($request)" in controller and
        "abort_if(" in controller,
        "No session cookie and custom profile domains isolated")

require("private const LEGACY_DEFAULTS" in content and
        "if (isset($stored[$key]) && $stored[$key] === $priorDefault)" in content and
        "unset($stored[$key]);" in content and
        "array_replace(self::defaults(), array_intersect_key($stored, self::defaults()))" in content,
        "Existing operator copy remains in persistent JSON; only old defaults refreshed in memory")

for key in ["nav_catalog", "nav_roadmap", "button_explore"]:
    require(f"'{key}' =>" in content and f"PublicSiteContent::text('{key}')" in home,
            f"New admin-editable link field {key} is used on homepage")

require("class MemberFeatureCatalog" in catalog and
        "public static function groups()" in catalog and
        "public static function upcoming()" in catalog,
        "Inventory comes from a dedicated server-side catalog")
require("filter_var(env('ALLOW_USER_EXPORT')" in catalog and
        "filter_var(env('ALLOW_USER_IMPORT')" in catalog and
        "'VIP e administrador.'" in catalog,
        "Restricted reports and optional transfers are not advertised without qualification")

# The only previously discussed new member tool still outside main.
section = catalog.split("public static function upcoming()", 1)[1]
require(section.count("$items[] =") == 1 and
        "Pré-visualização interativa de botões por plataforma" in section and
        "is_file(base_path('assets/js/linkfree-platform-picker.js'))" in section,
        "Roadmap lists only pending platform preview and hides it once shipped")
for already in ["Selo de verificação", "Indicações por convite", "Temas e pré-visualização",
                "Origem dos visitantes", "Links mais acessados"]:
    require(already not in section, "Existing feature excluded from roadmap: " + already)

require("{{ $item['title'] }}" in page and
        "{{ $feature['status'] }}" in page and
        "@forelse($upcoming as $feature)" in page and "@empty" in page,
        "Both public pages escape dynamic data and show honest empty roadmap")
require("PublicSeo::robots()" in page and
        'rel="canonical"' in page and
        'name="description"' in page and
        'name="viewport"' in page,
        "Public SEO metadata and viewport present")
require("route('public.resources')" in home and "route('public.roadmap')" in home and
        "MemberFeatureCatalog::upcoming()" in home,
        "Homepage links both resources and only displays future highlights when pending")

require("linkfree-public-catalog.css" in page and
        "@media(max-width:720px)" in styles and
        "@media(max-width:450px)" in styles and
        "prefers-reduced-motion" in styles and
        "focus-visible" in styles,
        "Premium mobile layout, keyboard focus and reduced motion present")
require("route('public.resources')" in seo and "route('public.roadmap')" in seo and
        "PublicSeo::globallyNoIndex()" in seo,
        "Institutional pages participate in home sitemap under SEO preferences")

require("str_starts_with(strtolower(app()->getLocale()), 'pt')" in auth and
        "str_starts_with(strtolower(app()->getLocale()), 'pt')" in register and
        "route('login')" in auth and "route('register')" in register,
        "Portuguese auth copy improved without changing routes and other locales")

print("PASS: public copy, existing member catalog and future-only roadmap contracts")
