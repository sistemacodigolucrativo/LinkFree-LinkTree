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
for endpoint in ("/upLink/{up}/{id}", "/studio/rem-background", "/clearIcon/{id}", "/studio/page/delprofilepicture", "/studio/delete-user/{id}", "/admin/site/delavatar", "/admin/site/delfavicon"):
    checks["No mutating GET at " + endpoint] = "Route::get('" + endpoint + "'" not in routes
checks["Background removal is POST with CSRF"] = 'formaction="{{ route(\'removeBackground\') }}"' in read("linkstack/resources/views/studio/theme.blade.php") and "@csrf" in read("linkstack/resources/views/studio/theme.blade.php")
checks["Photo removal is POST with CSRF"] = 'action="{{ route(\'delProfilePicture\') }}"' in read("linkstack/resources/views/studio/page.blade.php") and "@csrf" in read("linkstack/resources/views/studio/page.blade.php")
checks["Icon-cache removal is POST with CSRF"] = 'action="{{ route(\'clearIcon\',' in links and "@csrf" in links
checks["Brand removal uses POST override"] = 'formaction="{{ route(\'delAvatar\') }}"' in read("linkstack/resources/views/panel/site.blade.php") and 'formaction="{{ route(\'delFavicon\') }}"' in read("linkstack/resources/views/panel/site.blade.php")

for endpoint in ("/admin/users/block/{block}/{id}", "/admin/users/verify/{verify}/{id}", "/admin/users/verify-mail/{verify}/{id}", "/admin/delete-user/{id}", "/auth-as/{id}"):
    checks["Admin GET mutation blocked: "+endpoint] = "Route::get('" + endpoint + "'" not in routes
checks["Admin AJAX role mutations use POST"] = "xhr.open('POST'" in read("linkstack/resources/views/panel/users.blade.php")
checks["Admin delete user form is CSRF protected"] = "@method('DELETE')" in read("linkstack/resources/views/linkstack/modules/admin-bar.blade.php") and "@method('DELETE')" in read("linkstack/resources/views/linkinfo.blade.php")
checks["Admin impersonation UI uses POST"] = 'method="POST" action="{{ route(\'authAsID\'' in read("linkstack/resources/views/components/table-components/action.blade.php")

checks["Test email is POST with CSRF"] = "Route::get('/send-test-email'" not in routes and "Route::post('/send-test-email'" in routes and "@csrf" in read("linkstack/resources/views/components/config/config.blade.php")
checks["Public report reminder uses accessible text color"] = '<p class="small" style="color:#334155!important">' in read("linkstack/resources/views/report.blade.php")
checks["Profile avatars have alt"] = read("linkstack/resources/views/studio/page.blade.php").count('alt="Foto do perfil"') == 3
checks["Livewire pagination select has label hook"] = "labelLivewirePerPage" in read("linkstack/resources/views/panel/users.blade.php")
checks["Verification tables are keyboard focusable"] = read("linkstack/resources/views/panel/verification.blade.php").count('tabindex="0" role="region"') == 2

checks["Admin user creation is POST only"] = "Route::get('/admin/new-user', [AdminController::class, 'showNewUser'])" in routes and "Route::post('/admin/new-user', [AdminController::class, 'createNewUser'])->name('createNewUser')->middleware('max.users');" in routes
checks["Sidebar opens non-mutating confirmation"] = "route('showNewUser')" in read("linkstack/resources/views/layouts/sidebar.blade.php") and "route('createNewUser')" not in read("linkstack/resources/views/layouts/sidebar.blade.php")
controller = read("linkstack/app/Http/Controllers/AdminController.php")
new_get = controller.split("function showNewUser()")[1].split("public function createNewUser(")[0]
new_post = controller.split("function createNewUser(")[1].split("public function deleteUser")[0]
checks["New-user GET returns only draft form"] = "return view('panel.new-user'," in new_get and "User::create" not in new_get
checks["Creation persists only in POST after validation"] = "$request->validate(" in new_post and "User::create(" in new_post
create_view = read("linkstack/resources/views/panel/new-user.blade.php")
checks["Single explicit new-user POST form"] = '<form method="POST" action="{{ route(\'createNewUser\') }}"' in create_view and "@csrf" in create_view and 'id="lf-save-new-user"' in create_view
overview = read("linkstack/resources/views/panel/admin-overview.blade.php")
users_view = read("linkstack/resources/views/panel/users.blade.php")
table_view = read("linkstack/resources/views/livewire/admin-user-table.blade.php")
checks["Overview and users open non-mutating form"] = (
    "['route' => 'showNewUser'" in overview and "route('createNewUser')" not in overview
    and "route('createNewUser')" not in users_view and "route('showNewUser')" in table_view
)
checks["Creating form cannot be reached by unsafe GET mutation"] = (
    "Route::get('/admin/new-user', [AdminController::class, 'showNewUser'])" in routes
    and "User::create" not in new_get
    and "method=\"POST\"" in create_view
)

checks["Member mutation target taken from route"] = all("request->route('id')" in read("linkstack/app/Http/Controllers/UserController.php").split("function "+name+"(")[1].split("public function")[0] for name in ("deleteLink","clearIcon","upLink","editCSS","editLink"))
checks["Impersonation state is session-bound, no remember token in middleware"] = "linkfree_impersonation" in read("linkstack/app/Http/Middleware/Impersonate.php") and "remember_token" not in read("linkstack/app/Http/Middleware/Impersonate.php")
checks["Impersonation return uses a POST CSRF form"] = 'method="POST" action="{{ route(\'authAs\') }}"' in read("linkstack/resources/views/components/impersonation-bar.blade.php") and "@csrf" in read("linkstack/resources/views/components/impersonation-bar.blade.php")
checks["Installer default account shortcut denied"] = "Route::get('/skip', fn () => abort(404));" in routes
checks["Member delete is handled as a form submit"] = "addEventListener('submit'" in js and "form[data-delete-link-form]" in js

for name, passed in checks.items():
    print(("PASS" if passed else "FAIL") + " " + name)
if not all(checks.values()):
    raise SystemExit(1)
print("STATIC GUARDS PASS: runtime HTTP, browsers and remaining unsafe GET routes require separate review.")
