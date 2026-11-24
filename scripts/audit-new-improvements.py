#!/usr/bin/env python3
"""Static boundaries for 10 LinkFree scoped enhancements. Runtime workflows also use PHP tests."""
from pathlib import Path

root=Path(__file__).resolve().parents[1]/'linkstack'
def source(p): return (root/p).read_text(encoding='utf-8')
def check(v,msg):
    assert v,msg
    print('PASS:',msg)

theme=source('app/Services/ThemeOptions.php')
admin=source('app/Http/Controllers/AdminController.php')
member=source('app/Http/Controllers/UserController.php')
link=source('app/Models/Link.php')
route=source('routes/web.php')
meta=source('resources/views/linkstack/modules/meta.blade.php')
home=source('resources/views/home.blade.php')
register=source('app/Http/Controllers/Auth/RegisteredUserController.php')
social=source('app/Http/Controllers/Auth/SocialLoginController.php')
consent=source('app/Services/ConsentReceipt.php')
migration=source('database/migrations/2026_11_23_000001_create_registration_consents_table.php')
error=source('resources/views/errors/linkfree.blade.php')
form=source('resources/views/components/pageitems/predefined-form.blade.php')
report=source('resources/views/studio/visitor-reports.blade.php')
admin_view=source('resources/views/panel/admin-overview.blade.php')
check("savePermissions" in theme and "array_intersect_key($stored, $allowed)" in theme and "validated(" in theme and "contrast(" in theme, "M01 admin-gated theme settings and validated values")
check("route('admin.theme-options')" in source('resources/views/panel/theme.blade.php') and "route('studio.theme-options')" in source('resources/views/studio/theme.blade.php'), "M01 admin and member controls exist")
check("UserLinkLimit::ensureAllowed" in link and "User::whereKey($userId)->lockForUpdate()" in member and "countImportable" in member, "M05 server-side quotas, serialization and import preflight")
check("lf-whatsapp-fields" in form and "WhatsAppLink::build" in member, "M06 guided WhatsApp fields and server URL")
seo=source('app/Services/PublicSeo.php')
check("X-Robots-Tag" in member and "UserData::getData($userinfo->id, 'search_noindex') === true" in member and "PublicSeo::robots(!empty($noIndex))" in meta and 'name="robots" content="{{ $seoRobots }}"' in meta and "'noindex'" in seo and "$noIndex ||" in seo, "M09 both robots header and dynamic privacy-aware meta")
check("showVisitCount" in member and "lf-visit-counter" in source('resources/views/studio/page.blade.php'), "M10 toggle and public counter")
check("VisitAttribution::record" in member and "abort_unless(in_array(Auth::user()->role, ['vip', 'admin'], true), 403)" in member and "lf_visitor_source" in report, "M11 aggregation and server-side VIP authorization")
check("accept_terms' => 'accepted'" in register and "DB::transaction" in register and "ConsentReceipt::record" in register and "ConsentReceipt::record" in social and "registration_consents" in migration, "M12 mandatory receipt with document hash and migration")
check("lf-admin-shortcuts-title" in admin_view and "'middleware' => 'admin'" in route, "M13 grouped admin navigation under middleware")
check("PublicSiteContent::text('hero_title')" in home and "route('login')" in home and "route('register')" in home, "M14 existing institutional CTA and editable sections reused")
check("response()->view('maintenance', [], 503)" in route and all((root/'resources/views/errors'/f'{x}.blade.php').exists() for x in [404,500,503]) and 'lf_error_back' in error, "M15 error pages and HTTP 503")
print('ALL ENHANCEMENT STATIC CONTRACTS PASS')
