#!/usr/bin/env python3
"""Auditoria dos contratos do editor global da Home, sem acesso a dados reais."""
from pathlib import Path
import re

root = Path(__file__).resolve().parents[1] / "linkstack"
def read(path):
    return (root / path).read_text(encoding="utf-8")

service = read("app/Services/PublicSiteContent.php")
home = read("resources/views/home.blade.php")
view = read("resources/views/panel/site.blade.php")
controller = read("app/Http/Controllers/AdminController.php")
routes = read("routes/web.php")
styles = read("assets/css/linkfree-admin-management.css")

def require(expr, msg):
    assert expr, msg
    print("OK:", msg)

fields = set(re.findall(r"'([a-z][a-z0-9_]*)'\s*=>\s*\['label'", service))
flags = {"show_features", "show_steps", "show_faq", "show_final"}
assert len(fields) >= 45, ("Missing editor fields", len(fields))
assert flags.issubset(fields)
require(all("PublicSiteContent::text('"+k+"')" in home for k in fields - flags),
        "Every text field of the editor is rendered in the public home")
require(all("PublicSiteContent::enabled('"+k[5:]+"')" in home for k in flags),
        "Every visibility switch controls its section and nav links")
require("function saveSection" in service and "flock($lock, LOCK_EX)" in service and
        "rename($temp, $path)" in service and "array_diff(array_keys($values), $allowed)" in service,
        "Settings persist atomically using lock and field whitelist")
require("storage_path('app/'" in service and "linkfree/public-site.json" in service,
        "Site config stored outside deploy-replaced source paths")
require("public function editPublicSiteContent" in controller and
        "$request->validate($rules)" in controller and
        "PublicSiteContent::saveSection($section, $values)" in controller,
        "Admin endpoint validates and saves only selected section")
require("Route::post('/admin/site/content'" in routes and
        "'middleware' => 'admin'" in routes and "->name('admin.site.content')" in routes,
        "Site editor route exists under authenticated admin middleware")
require("siteSections as $sectionKey" in view and "route('admin.site.content')" in view and
        "@csrf" in view and "siteSection === 'identity'" in view,
        "Responsive sectional editor with CSRF and legacy branding form")
require("route('showSitePage')" in view and "route('showConfig')" in view,
        "Existing legal pages and footer controls stay available")
require(".lf-site-settings-nav" in styles and "@media(max-width:767.98px)" in styles,
        "Editor navigation and forms adapt to mobile")
print(f"PASS public site editor: {len(fields)} configurable fields")
