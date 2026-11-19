#!/usr/bin/env python3
"""Contracts for admin-only persistent VIP-on-signup feature (static source test).
Full integration requires Laravel, authenticated admin and writable production .env.
"""
from pathlib import Path

root = Path(__file__).resolve().parents[1] / 'linkstack'

def file(path):
    return (root / path).read_text(encoding='utf-8')

service = file('app/Services/RegistrationVip.php')
admin = file('app/Http/Controllers/AdminController.php')
manual = file('app/Http/Controllers/Auth/RegisteredUserController.php')
social = file('app/Http/Controllers/Auth/SocialLoginController.php')
users = file('resources/views/panel/users.blade.php')
routes = file('routes/web.php')
user_model = file('app/Models/User.php')

def check(ok, message):
    assert ok, message
    print('PASS:', message)

check("AUTO_VIP_NEW_USERS" in service and "EnvEditor::getKey" in service and
      "EnvEditor::editKey" in service and "EnvEditor::addKey" in service,
      'VIP global preference read/write from persistent server .env')
check("return false;" in service and "roleForNewUser" in service and
      "return self::enabled() ? 'vip' : 'user';" in service,
      'New VIP default off and role assignment central')
check("Route::post('/admin/users/registration-vip'" in routes and
      "->name('admin.registration-vip')" in routes and
      "'middleware' => 'admin'" in routes,
      'VIP toggle POST protected in admin route group')
check("updateRegistrationVip(Request $request)" in admin and
      "'enabled' => 'required|boolean'" in admin and
      "RegistrationVip::setEnabled($request->boolean('enabled'))" in admin,
      'Admin endpoint validates and persists boolean')
check('id="auto-vip-new-users"' in users and 'role="switch"' in users and
      "@csrf" in users and "route('admin.registration-vip')" in users and
      "this.form.requestSubmit()" in users,
      'Accessible CSRF-protected switch on admin users page')
check("RegistrationVip::roleForNewUser()" in manual and
      "$user->role = RegistrationVip::roleForNewUser();" in manual and
      "event(new Registered($user));" in manual and
      "$user = $request->name;" not in manual,
      'Public registration sets new role and fires Registered with a User object')
check("if (!$user) {" in social and
      "$user->role = RegistrationVip::roleForNewUser();" in social,
      'Social login changes role only on account creation')
check("$user->role = RegistrationVip::roleForNewUser();" in admin and
      "return redirect(\"admin/edit-user/\" . $user->id);" in admin,
      'Admin-created accounts respect preference')
check("'role'," not in user_model and "'role'\n" not in user_model,
      'Role remains unfillable from public mass assignment')
check("with('fail'" in admin and "with('success'" in admin,
      'Feedback for persistence success and failure')
print('VIP REGISTRATION AUDIT PASS')
