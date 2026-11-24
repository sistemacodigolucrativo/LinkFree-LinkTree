#!/usr/bin/env python3
"""Static, dependency-free guards; not a substitute for isolated HTTP/browser testing."""
from pathlib import Path
import re

root = Path(__file__).resolve().parents[1]
def read(path):
    return (root / path).read_text(encoding="utf-8")

routes = read("linkstack/routes/web.php")
checks = {
    "GET member link deletion forbidden": "Route::get('/deleteLink/{id}'" not in routes,
    "DELETE member link registered": "Route::delete('/deleteLink/{id}', [UserController::class, 'deleteLink'])->name('deleteLink')->middleware('link-id');" in routes,
    "GET admin link deletion forbidden": "Route::get('/admin/deleteLink/{id}'" not in routes,
    "DELETE admin link registered": "Route::delete('/admin/deleteLink/{id}', [AdminController::class, 'deleteLinkUser'])->name('deleteLinkUser');" in routes,
}
links = read("linkstack/resources/views/studio/links.blade.php")
js = read("linkstack/assets/js/main-dashboard.js")
icons = read("linkstack/resources/views/studio/icons.blade.php")
admin = read("linkstack/resources/views/panel/links.blade.php")
info = read("linkstack/resources/views/linkinfo.blade.php")
checks.update({
    "Member delete form has CSRF, method override": 'data-delete-link-form' in links and "@csrf" in links and "@method('DELETE')" in links and 'type="submit" data-delete-link' in links,
    "Member AJAX sends DELETE with CSRF": "fetch(form.action" in js and "method: 'DELETE'" in js and "'X-CSRF-TOKEN'" in js and "control.href" not in js,
    "Page icon deletion has native method override": 'formaction=' in icons and 'name="_method" value="DELETE"' in icons,
    "Admin delete form has CSRF/method": "route('deleteLinkUser'" in admin and "@method('DELETE')" in admin and "@csrf" in admin,
    "Admin link info uses CSRF-protected form": "route('deleteLinkUser'" in info and "@method('DELETE')" in info and "@csrf" in info,
})
sidebar = read("linkstack/resources/views/layouts/sidebar.blade.php")
logos = re.findall(r'<img\s+class="img logo"[^>]*>', sidebar)
checks["All sidebar logos have alternative text"] = len(logos) >= 8 and all(re.search(r'\balt=', item) for item in logos)
for path in ["linkstack/resources/views/studio/referrals.blade.php","linkstack/resources/views/panel/abuse-reports.blade.php"]:
    content = read(path)
    checks[path+" table is keyboard-focusable"] = bool(re.search(r'<div class="table-responsive"[^>]*tabindex="0"[^>]*role="region"', content))
for name, passed in checks.items():
    print(("PASS" if passed else "FAIL") + " " + name)
if not all(checks.values()):
    raise SystemExit(1)
print("STATIC GUARDS PASS: runtime HTTP, browsers and remaining unsafe GET routes require separate review.")
