#!/usr/bin/env python3
"""Guards estáticos das configurações modulares; validação dinâmica continua obrigatória."""
from pathlib import Path
import re
root = Path(__file__).resolve().parents[1]
def read(path): return (root / path).read_text(encoding='utf-8')
routes = read('linkstack/routes/web.php')
ctrl = read('linkstack/app/Http/Controllers/AdminController.php')
hub = read('linkstack/resources/views/panel/config-editor.blade.php')
page = read('linkstack/resources/views/panel/config-section.blade.php')
component = read('linkstack/resources/views/components/config/config.blade.php')
sidebar = read('linkstack/resources/views/layouts/sidebar.blade.php')
expected_controls = ["toggle:ALLOW_REGISTRATION","toggle2:REGISTER_AUTH","toggle:MANUAL_USER_VERIFICATION","text:ADMIN_EMAIL","toggle:FORCE_HTTPS","text:APP_NAME","toggle:HIDE_VERIFICATION_CHECKMARK","toggle:ENABLE_REPORT_ICON","toggle:NOTIFY_EVENTS","toggle:NOTIFY_UPDATES","toggle:ENABLE_ADMIN_BAR_USERS","toggle:ENABLE_BUTTON_EDITOR","toggle:USE_THEME_PREVIEW_IFRAME","toggle:ALLOW_CUSTOM_BACKGROUNDS","toggle:ALLOW_USER_HTML","toggle:ALLOW_CUSTOM_CODE_IN_THEMES","toggle:ENABLE_THEME_UPDATER","toggle:ALLOW_USER_EXPORT","toggle:ALLOW_USER_IMPORT","toggle:JOIN_BETA","toggle:SKIP_UPDATE_BACKUP","toggle:CUSTOM_META_TAGS","toggle:ENABLE_SOCIAL_LOGIN","toggle:FORCE_ROUTE_HTTPS","toggle:DISPLAY_FOOTER","toggle:DISPLAY_CREDIT","toggle:DISPLAY_CREDIT_FOOTER","toggle:DISPLAY_FOOTER_HOME","text:TITLE_FOOTER_HOME","toggle:DISPLAY_FOOTER_TERMS","text:TITLE_FOOTER_TERMS","toggle:DISPLAY_FOOTER_PRIVACY","text:TITLE_FOOTER_PRIVACY","toggle:DISPLAY_FOOTER_CONTACT","text:TITLE_FOOTER_CONTACT"]
sections = ['application','panel','appearance','security','advanced','smtp','footer','debug','language']
actual_controls = [f"{a}:{b}" for a,b in re.findall(r"\{\{(toggle2?|text)\('([^']+)'\)\}\}", component)]
checks = {
  'all legacy controls preserved exactly once': sorted(actual_controls) == sorted(expected_controls),
  'all section branches separated': all(f"@if($section === '{name}')" in component for name in sections),
  'GET sections use admin controller': "Route::get('/admin/config/sections/{section}', [AdminController::class, 'showConfigSection'])" in routes,
  'admin authorization middleware remains': "'middleware' => 'admin'" in routes and "Route::middleware(['auth', 'blocked', 'impersonate'])" in routes,
  'section allowlist enforced before view': 'abort_unless(array_key_exists($section, self::CONFIG_SECTIONS), 404)' in ctrl,
  'config writes remain POST': "Route::post('/admin/config', [AdminController::class, 'editConfig'])" in routes,
  'forms keep original CSRF handler': "route('editConfig')" in component and "csrf_token()" in component,
  'SMTP test remains POST and CSRF': "route('SendTestMail')" in component and '@csrf' in component,
  'no previous navigation tabs on hub': 'id="myTab"' not in hub and "components.config.config" not in hub,
  'diagnostics reused in own view': "@include('components.config.diagnose')" in page,
  'legacy fragment destinations preserved': all("'#"+k+"':" in hub for k in ['1','2','3','4','5','Application','SMTP','Footer','Language']),
  'safe post redirect checks origin': 'parse_url($previous, PHP_URL_HOST) === $request->getHost()' in ctrl,
  'advanced editor path retained': "return redirect()->route('showFileEditor');" in ctrl,
  'SMTP feedback redirected to module': 'admin.config.section", [\'section\' => \'smtp\']' in ctrl,
  'sidebar continues to link to existing hub': "route('showConfig')" in sidebar,
}
for name, result in checks.items(): print(('PASS ' if result else 'FAIL ') + name)
if not all(checks.values()): raise SystemExit(1)
print('STATIC TESTS PASS: ainda faltam navegador, persistência e homologação manual em ambientes isolados.')
