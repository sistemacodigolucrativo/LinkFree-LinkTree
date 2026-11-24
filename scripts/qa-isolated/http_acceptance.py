#!/usr/bin/env python3
"""Behavioral Laravel regression over local HTTP. Never connects to a production URL."""
import os
import json
import re
import sqlite3
import urllib.error
import urllib.parse
import urllib.request
import http.cookiejar
from pathlib import Path

ROOT = Path('/tmp/linkfree-audit/linkstack').resolve()
DB = ROOT / 'database/audit.sqlite'
BASE = 'http://127.0.0.1:18765'
assert ROOT.is_relative_to(Path('/tmp/linkfree-audit'))
assert DB.is_file()
assert 'DB_DATABASE=' + str(DB) in (ROOT / '.env').read_text()
assert 'MAIL_MAILER=log' in (ROOT / '.env').read_text()
assert os.getenv('QA_EXPECTED_SITE') == BASE, 'Require exact isolated localhost origin'

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None

class Client:
    def __init__(self):
        self.cookies = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(
            urllib.request.HTTPCookieProcessor(self.cookies), NoRedirect())
    def request(self, method, path, fields=None, json_response=False):
        assert path.startswith('/') and not path.startswith('//')
        data = None if fields is None else urllib.parse.urlencode(fields).encode('utf-8')
        headers = {'Accept':'application/json' if json_response else 'text/html'}
        if method == 'DELETE' and data is None:
            data = b''
        rq = urllib.request.Request(BASE + path, data=data, method=method, headers=headers)
        try:
            response = self.opener.open(rq, timeout=15)
        except urllib.error.HTTPError as exc:
            response = exc
        return response.status, dict(response.headers), response.read().decode('utf-8','replace')
    def token(self, path):
        status, _, html = self.request('GET', path)
        assert status == 200, f'Cannot load CSRF page {path}: {status}'
        mt = re.search(r'name=["\']_token["\']\s+value=["\']([^"\']+)', html)
        if not mt:
            mt = re.search(r'name=["\']csrf-token["\']\s+content=["\']([^"\']+)', html)
        assert mt, f'Missing CSRF token on {path}'
        return mt.group(1)
    def login(self, name):
        csrf = self.token('/login')
        status, head, _ = self.request('POST','/login',{'_token':csrf,'email':name+'@example.test','password':'Synthetic-password-123'})
        assert status == 302 and '/dashboard' in head.get('Location',''), f'Login {name}: {status}, {head.get("Location")}'
        return self

def value(sql, params=()):
    with sqlite3.connect(DB) as con:
        return con.execute(sql, params).fetchone()[0]

results = []
def check(case, label, func):
    try:
        detail = func()
        results.append({'id':case,'assertion':label,'passed':True,'observed':detail})
        print(f'PASS [{case}] {label}: {detail}', flush=True)
    except Exception as exc:
        results.append({'id':case,'assertion':label,'passed':False,'error':f'{type(exc).__name__}: {exc}'})
        print(f'FAIL [{case}] {label}: {type(exc).__name__}: {exc}', flush=True)

def expect(condition, explanation):
    if not condition:
        raise AssertionError(explanation)

member = Client().login('auditfree')
other = Client().login('auditother')
admin = Client().login('auditadmin')
anonymous = Client()

def get_mutations():
    member_urls = [
        '/deleteLink/101','/upLink/yes/104','/clearIcon/104',
        '/studio/rem-background','/studio/page/delprofilepicture',
        '/studio/delete-user/2'
    ]
    admin_urls = [
        '/admin/deleteLink/102','/admin/users/block/no/2',
        '/admin/users/verify/vip/2','/admin/users/verify-mail/true/2',
        '/admin/delete-user/3','/admin/site/delavatar',
        '/admin/site/delfavicon','/send-test-email','/auth-as/2','/admin/new-user','/admin/delete-table-user/4'
    ]
    snapshot = (value('SELECT COUNT(*) FROM links'), value('SELECT COUNT(*) FROM users'),
                value('SELECT role FROM users WHERE id=2'),
                value('SELECT block FROM users WHERE id=2'),
                value('SELECT up_link FROM links WHERE id=104'))
    for cli, urls in [(member,member_urls),(admin,admin_urls)]:
        for url in urls:
            status,_,_ = cli.request('GET',url)
            expect(status == 405, f'GET {url} not rejected with 405 (got {status})')
    after = (value('SELECT COUNT(*) FROM links'), value('SELECT COUNT(*) FROM users'),
             value('SELECT role FROM users WHERE id=2'),
             value('SELECT block FROM users WHERE id=2'),
             value('SELECT up_link FROM links WHERE id=104'))
    expect(after == snapshot, f'GET altered synthetic database: {snapshot} -> {after}')
    return {'memberGETDenied':len(member_urls),'adminGETDenied':len(admin_urls)}

check('TC-04-D','GET cannot mutate links, accounts, roles, or personal media',get_mutations)

def no_csrf():
    payloads = [
        (member,'DELETE','/deleteLink/101',101),
        (admin,'DELETE','/admin/deleteLink/102',102)
    ]
    for cli,method,url,link_id in payloads:
        status,headers,_ = cli.request(method,url,{})
        expect(status in (302,419), f'{method} {url} without CSRF returned {status}')
        expect(value('SELECT COUNT(*) FROM links WHERE id=?',(link_id,))==1,f'Link {link_id} deleted without CSRF')
    status,_,_ = admin.request('POST','/admin/users/block/no/2',{})
    expect(status in (302,419), f'Block without CSRF returned {status}')
    expect(value('SELECT block FROM users WHERE id=2')=='no','Block changed without CSRF')
    return 'invalid/missing CSRF prevented DELETE and POST persistence'

check('TC-04-D','Mutation without CSRF does not persist',no_csrf)

def owner_isolation():
    tok = member.token('/studio/links')
    status,_,_ = member.request('DELETE','/deleteLink/102',{'_token':tok})
    expect(status == 403, f'cross-account deletion should return 403, got {status}')
    expect(value('SELECT COUNT(*) FROM links WHERE id=102')==1,'Cross-account deletion happened')
    return 'member cannot delete another member link'

check('TC-04-D','Owner middleware blocks cross-account link deletion',owner_isolation)

def valid_delete_member():
    tok = member.token('/studio/links')
    status,_,body = member.request('DELETE','/deleteLink/101',{'_token':tok,'id':105},json_response=True)
    expect(status == 200, f'expected JSON 200, got {status} {body[:90]}')
    expect(value('SELECT COUNT(*) FROM links WHERE id=101')==0,'Own link remained after DELETE')
    expect(value('SELECT COUNT(*) FROM links WHERE id=105')==1,'Body override deleted foreign link')
    obj=json.loads(body)
    expect(obj.get('deleted') is True and str(obj.get('id'))=='101','Unexpected delete response')
    return 'DELETE + CSRF returns JSON success and removes only requested row'

check('TC-04-D','Legitimate member DELETE with CSRF remains functional',valid_delete_member)

def valid_delete_admin():
    tok = admin.token('/admin/users')
    status,headers,_ = admin.request('DELETE','/admin/deleteLink/102',{'_token':tok})
    expect(status in (200,302),f'admin delete status {status}')
    expect(value('SELECT COUNT(*) FROM links WHERE id=102')==0,'Admin link not deleted')
    return f'admin DELETE + CSRF accepted: HTTP {status}'

check('TC-04-D','Legitimate admin DELETE with CSRF remains functional',valid_delete_admin)

def valid_member_post():
    tok = member.token('/studio/links')
    status,_,_ = member.request('POST','/upLink/yes/104',{'_token':tok})
    expect(status in (200,302),f'upLink status {status}')
    expect(value('SELECT up_link FROM links WHERE id=104')=='no','upLink did not persist toggle')
    return 'POST + CSRF toggles expected link state'

check('TC-04-D','Legitimate POST for link state remains functional',valid_member_post)

def valid_admin_post():
    tok = admin.token('/admin/users')
    status,_,_ = admin.request('POST','/admin/users/block/no/2',{'_token':tok})
    expect(status in (200,302),'valid block refused')
    expect(value('SELECT block FROM users WHERE id=2')=='yes','Admin block did not persist')
    status,_,_ = admin.request('POST','/admin/users/block/yes/2',{'_token':tok})
    expect(status in (200,302),'valid unblock refused')
    expect(value('SELECT block FROM users WHERE id=2')=='no','Admin unblock did not persist')
    status,_,_ = admin.request('POST','/admin/users/verify/vip/2',{'_token':tok})
    expect(status in (200,302),'role change refused')
    expect(value('SELECT role FROM users WHERE id=2')=='vip','Role change did not persist')
    status,_,_ = admin.request('POST','/admin/users/verify/user/2',{'_token':tok})
    expect(status in (200,302),'role revert refused')
    expect(value('SELECT role FROM users WHERE id=2')=='user','Role revert failed')
    return 'Admin block/unblock and role changes persist with POST + CSRF'

check('TC-04-D','Legitimate admin POST actions remain functional',valid_admin_post)

def forms():
    _,_,member_html=member.request('GET','/studio/links')
    _,_,admin_html=admin.request('GET','/admin/links/4')
    expect('data-delete-link-form' in member_html and 'name="_method" value="DELETE"' in member_html,'Member form absent')
    expect('name="_token"' in member_html,'Member CSRF input absent')
    expect('name="_method" value="DELETE"' in admin_html and '/admin/deleteLink/' in admin_html,'Admin delete form absent')
    return 'Rendered member and admin HTML include DELETE method override and CSRF'

check('TC-04-D','Rendered delete controls preserve CSRF and DELETE semantics',forms)

def admin_create_user():
    before = value('SELECT COUNT(*) FROM users')
    for payload in ({}, {'_token':'invalid-synthetic-token'}):
        status, headers, _ = admin.request('POST', '/admin/new-user', payload, json_response=True)
        expect(value('SELECT COUNT(*) FROM users') == before, 'Account created without valid CSRF')
        expect(status == 419 or (status == 302 and urllib.parse.urlparse(headers.get('Location','')).path == '/login'), f'Unexpected CSRF response {status}')
    token = member.token('/studio/links')
    status, _, _ = member.request('POST', '/admin/new-user', {'_token':token}, json_response=True)
    expect(status in (302, 403), f'Non-admin creation returned {status}')
    expect(value('SELECT COUNT(*) FROM users') == before, 'Non-admin created account')
    token = admin.token('/admin/users')
    status, headers, _ = admin.request('POST', '/admin/new-user', {'_token':token})
    expect(status == 302 and '/admin/edit-user/' in headers.get('Location',''), f'Admin creation did not redirect: {status}')
    expect(value('SELECT COUNT(*) FROM users') == before + 1, 'Expected exactly one new account')
    new_id = int(headers['Location'].rstrip('/').split('/')[-1])
    expect(value("SELECT COUNT(*) FROM users WHERE id=? AND name LIKE 'Admin-Created-%'", (new_id,)) == 1, 'Created account missing')
    return {'csrfDenied':True,'nonAdminDenied':True,'createdExactlyOne':True,'redirectedToEditor':True}

check('TC-04-D','Admin account creation requires POST, CSRF and administrator privileges',admin_create_user)

def reject_csrf(status, headers):
    return status == 419 or (status == 302 and urllib.parse.urlparse(headers.get('Location','')).path == '/login')

def assert_denied(status, headers):
    expect(status in (401,403) or (status == 302 and urllib.parse.urlparse(headers.get('Location','')).path in ('/login','/dashboard','/blocked')), f'Unexpected denial {status}')

def route_target_isolation():
    tok = member.token('/studio/links')
    status, _, _ = member.request('POST','/upLink/no/104',{'_token':tok,'id':105,'up':'yes'})
    expect(status in (200,302),'legitimate link toggle rejected')
    expect(value('SELECT up_link FROM links WHERE id=104')=='yes','Route target not used')
    expect(value('SELECT up_link FROM links WHERE id=105')=='no','Body ID modified another account')
    status, _, _ = member.request('POST','/upLink/bad/104',{'_token':tok})
    expect(status == 422,'Invalid operation accepted')
    return 'body id and operation cannot override authorized route'

check('TC-04-D','Route parameter isolation against body overrides',route_target_isolation)

def session_isolation():
    for path in ('/studio/links','/admin/users'):
        status, headers, _ = anonymous.request('GET',path)
        assert_denied(status,headers)
    status,headers,_ = member.request('GET','/admin/users')
    assert_denied(status,headers)
    token=member.token('/studio/links')
    status,_,_=member.request('POST','/logout',{'_token':token})
    expect(status==302,'Logout did not redirect')
    for path in ('/studio/links','/admin/users'):
        status,headers,_=member.request('GET',path)
        assert_denied(status,headers)
    member.login('auditfree')
    status,_,html=member.request('GET','/studio/links')
    expect(status==200 and 'QA synthetic 104' in html and 'QA synthetic 105' not in html,'Session or owner data mismatch')
    return 'guest denied, member admin denied, logout invalidates session, relogin retains own data'

check('TC-04-D','Login/logout and session/owner isolation',session_isolation)

def admin_permissions():
    tok=member.token('/studio/links')
    for path in ('/admin/users/block/no/3','/admin/users/verify/vip/3','/admin/users/verify-mail/false/3','/auth-as/3'):
        status,headers,_=member.request('POST',path,{'_token':tok})
        assert_denied(status,headers)
    expect(value('SELECT block FROM users WHERE id=3')=='no','Non-admin blocked user')
    expect(value('SELECT role FROM users WHERE id=3')=='user','Non-admin changed role')
    expect(value('SELECT email_verified_at IS NOT NULL FROM users WHERE id=3')==1,'Non-admin changed email verification')
    tok=admin.token('/admin/users')
    status,_,_=admin.request('POST','/admin/users/block/no/3',{'_token':tok})
    expect(status==302 and value('SELECT block FROM users WHERE id=3')=='yes','Block did not persist')
    status,headers,_=other.request('GET','/studio/links')
    assert_denied(status,headers)
    status,_,_=admin.request('POST','/admin/users/block/yes/3',{'_token':tok})
    expect(status==302 and value('SELECT block FROM users WHERE id=3')=='no','Unblock failed')
    expect(other.request('GET','/studio/links')[0]==200,'Unblock did not restore access')
    status,_,_=admin.request('POST','/admin/users/verify-mail/false/3',{'_token':tok})
    expect(status in (200,302) and value('SELECT email_verified_at IS NULL FROM users WHERE id=3')==1,'Unverify failed')
    status,_,_=admin.request('POST','/admin/users/verify-mail/true/3',{'_token':tok})
    expect(status in (200,302) and value('SELECT email_verified_at IS NOT NULL FROM users WHERE id=3')==1,'Verify failed')
    return 'member blocked from admin operations, access blocked/unblocked, email state persists'

check('TC-04-D','Administrative permission and block enforcement',admin_permissions)

def impersonation():
    # Separate admin session must never inherit another session's impersonation.
    independent=Client().login('auditadmin')
    tok=admin.token('/admin/users')
    status,_,_=admin.request('POST','/auth-as/1',{'_token':tok})
    expect(status==403,'Admin self impersonation accepted')
    status,headers,_=admin.request('POST','/auth-as/2',{})
    expect(reject_csrf(status,headers),'Missing CSRF not rejected')
    expect(admin.request('GET','/admin/users')[0]==200,'CSRF attempt changed identity')
    status,_,_=admin.request('POST','/auth-as/2',{'_token':tok})
    expect(status==302,'Valid impersonation refused')
    status,_,html=admin.request('GET','/studio/links')
    expect(status==200 and 'lf-impersonation-bar' in html and 'QA synthetic 104' in html and 'QA synthetic 105' not in html,'Impersonated member context missing')
    expect(independent.request('GET','/admin/users')[0]==200,'Independent admin session was switched')
    status,headers,_=admin.request('GET','/admin/users')
    assert_denied(status,headers)
    tok2=admin.token('/studio/links')
    status,_,_=admin.request('DELETE','/studio/delete-user/2',{'_token':tok2})
    expect(status==403 and value('SELECT COUNT(*) FROM users WHERE id=2')==1,'Impersonation deleted member account')
    mt=member.token('/studio/links')
    status,_,_=member.request('POST','/auth-as',{'_token':mt,'id':'1','token':'synthetic'})
    expect(status==403,'Ordinary member restored administrator')
    status,headers,_=admin.request('POST','/auth-as',{})
    expect(reject_csrf(status,headers),'Return without CSRF allowed')
    expect(admin.request('GET','/studio/links')[0]==200,'CSRF return changed identity')
    status,headers,_=admin.request('POST','/auth-as',{'_token':tok2,'id':'3'})
    expect(status==302 and '/admin/users' in headers.get('Location',''),'Return not successful')
    expect(admin.request('GET','/admin/users')[0]==200,'Admin not restored')
    expect(value('SELECT COUNT(*) FROM users WHERE auth_as IS NOT NULL')==0,'Legacy global impersonation state changed')
    return 'session-bound start and return, no cross-session switch, no privilege escalation, no account deletion'

check('TC-04-D','Impersonation start/return and independent sessions',impersonation)

def media_isolation():
    import base64
    png=base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jT1sAAAAASUVORK5CYII=')
    for relative in ('assets/img','assets/img/background-img','assets/favicon/icons'):
        folder=ROOT/relative
        folder.mkdir(parents=True,exist_ok=True)
        expect(folder.resolve().is_relative_to(ROOT),'Non-isolated media directory')
    tok=member.token('/studio/links')
    for rel,own,foreign,url in (
      ('assets/img','2_qa.png','3_qa.png','/studio/page/delprofilepicture'),
      ('assets/img/background-img','2_qa.png','3_qa.png','/studio/rem-background'),
      ('assets/favicon/icons','104.png','105.png','/clearIcon/104')):
        a,b=(ROOT/rel/own),(ROOT/rel/foreign)
        expect(not a.exists() and not b.exists(),'Fixture file already exists')
        a.write_bytes(png);b.write_bytes(png)
        status,_,_=member.request('GET',url)
        expect(status==405 and a.is_file() and b.is_file(),'GET changed media')
        status,headers,_=member.request('POST',url,{})
        expect(reject_csrf(status,headers) and a.is_file() and b.is_file(),'CSRF removed media')
        if 'clearIcon' in url:
            status,_,_=member.request('POST','/clearIcon/105',{'_token':tok})
            expect(status==403 and b.is_file(),'Foreign icon cleared')
        status,_,_=member.request('POST',url,{'_token':tok,'id':'105'})
        expect(status in (200,302) and not a.exists() and b.read_bytes()==png,'Own media removal or isolation failed: '+url)
    return 'avatar/background/icon deleted only with valid CSRF; foreign fixtures unchanged'

check('TC-04-D','Member media and icon deletion isolation',media_isolation)

def page_icon_and_brand():
    import base64
    png=base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jT1sAAAAASUVORK5CYII=')
    with sqlite3.connect(DB) as con:
        con.execute("INSERT INTO buttons(id,name) VALUES(94,'icon')")
        con.executemany("INSERT INTO links(id,user_id,button_id,link,title) VALUES(?,?,94,'https://example.test/icon','facebook')", [(106,2),(107,3)])
    tok=member.token('/studio/page-icons')
    status,_,html=member.request('GET','/studio/page-icons')
    expect(status==200 and '/deleteLink/106' in html and '/deleteLink/107' not in html,'Icon UI/ownership invalid')
    status,_,_=member.request('POST','/deleteLink/107',{'_method':'DELETE','_token':tok})
    expect(status==403 and value('SELECT COUNT(*) FROM links WHERE id=107')==1,'Foreign page icon deleted')
    status,headers,_=member.request('POST','/deleteLink/106',{'_method':'DELETE'})
    expect(reject_csrf(status,headers) and value('SELECT COUNT(*) FROM links WHERE id=106')==1,'CSRF page icon removal')
    status,headers,_=member.request('POST','/deleteLink/106',{'_method':'DELETE','_token':tok})
    expect(status==302 and '/studio/page-icons' in headers.get('Location','') and value('SELECT COUNT(*) FROM links WHERE id=106')==0,'Page icon deletion/redirect failed')
    expect(value('SELECT COUNT(*) FROM links WHERE id=107')==1,'Foreign page icon changed')
    directory=ROOT/'assets/linkstack/images'
    expect(directory.resolve().is_relative_to(ROOT),'Non-isolated brand directory')
    fallback=directory/'favicon.svg'
    baseline=fallback.read_bytes()
    mt=member.token('/studio/links')
    at=admin.token('/admin/users')
    for name,url in [('avatar_qa.png','/admin/site/delavatar'),('favicon_qa.png','/admin/site/delfavicon')]:
        f=directory/name
        expect(not f.exists(),'Brand fixture already exists')
        f.write_bytes(png)
        status,_,_=admin.request('GET',url)
        expect(status==405 and f.is_file(),'GET removed brand')
        status,headers,_=admin.request('POST',url,{})
        expect(reject_csrf(status,headers) and f.is_file(),'Missing CSRF removed brand')
        status,headers,_=member.request('POST',url,{'_token':mt})
        assert_denied(status,headers)
        expect(f.is_file(),'Member removed brand')
        status,_,_=admin.request('POST',url,{'_token':at})
        expect(status in (200,302) and not f.exists(),'Admin brand deletion failed')
        expect(fallback.read_bytes()==baseline,'Bundled favicon removed')
    return 'page icon native DELETE override, ownership/CSRF; brand admin only and fallback preserved'

check('TC-04-D','Page icon and site branding removal',page_icon_and_brand)

def block_editor_isolation():
    status,_,html=member.request('GET','/studio/linkparamform_part/predefined/104')
    expect(status==200 and 'QA synthetic 104' in html,'Owner editor denied')
    status,_,html=member.request('GET','/studio/linkparamform_part/predefined/105')
    expect(status==403 and 'QA synthetic 105' not in html,'Foreign block editor disclosed data')
    expect(member.request('GET','/studio/linkparamform_part/predefined/0')[0]==200,'New block form denied')
    expect(member.request('GET','/studio/linkparamform_part/predefined/999999999')[0]==404,'Missing block not rejected')
    expect(member.request('GET','/studio/linkparamform_part/unknown-type/0')[0]==404,'Unknown block type not rejected')
    return 'own/new forms work; foreign, missing and unknown blocks denied'

check('TC-04-D','Block editor owner authorization and valid type',block_editor_isolation)

def maintenance_get_guards():
    # No old updater or backup view may be entered, even in the disposable runner.
    import hashlib
    def snapshot():
        paths=[ROOT/'.env',ROOT/'storage/update.zip',ROOT/'backups/CANUPDATE',ROOT/'resources/views/components/pre-update.blade.php']
        return [(p.is_file(),hashlib.sha256(p.read_bytes()).hexdigest() if p.is_file() else None) for p in paths]
    before=snapshot()
    for url in ('/update','/backup'):
        status,_,html=admin.request('GET',url)
        expect(status==200 and 'procedimento controlado' in html,'Read-only maintenance navigation failed: '+url)
    for url in ('/update?updating','/update?preparing','/update?backup','/update?backups','/update?finishing','/update?error','/backup?backup','/backup?backups'):
        status,_,_=admin.request('GET',url)
        expect(status==403,'Legacy query stage not denied: '+url)
    expect(snapshot()==before,'Maintenance request changed protected fixture files')
    return 'read-only landing works; legacy update/backup stages denied without writes'

check('TC-04-D','Legacy updater and backup GET execution blocked',maintenance_get_guards)

def account_deletion():
    # Synthetic social account proves FK cascades are not bypassed.
    with sqlite3.connect(DB) as con:
        con.execute("INSERT INTO social_accounts(user_id,provider_name,provider_id) VALUES(3,'synthetic','qa-provider-3')")
    tok=other.token('/studio/links')
    status,_,_=other.request('DELETE','/studio/delete-user/2',{'_token':tok})
    expect(status==403 and value('SELECT COUNT(*) FROM users WHERE id=2')==1,'Cross-account deletion')
    status,headers,_=other.request('DELETE','/studio/delete-user/3',{})
    expect(reject_csrf(status,headers) and value('SELECT COUNT(*) FROM users WHERE id=3')==1,'CSRF deleted member')
    status,_,_=other.request('DELETE','/studio/delete-user/3',{'_token':tok,'id':'2'})
    expect(status==302 and value('SELECT COUNT(*) FROM users WHERE id=3')==0,'Member self deletion failed')
    expect(value('SELECT COUNT(*) FROM users WHERE id=2')==1,'Foreign member deleted')
    expect(value('SELECT COUNT(*) FROM links WHERE user_id=3')==0,'Deleted member links remain')
    expect(value('SELECT COUNT(*) FROM social_accounts WHERE user_id=3')==0,'Social FK cascade failed')
    status,headers,_=other.request('GET','/studio/links')
    assert_denied(status,headers)
    tok=admin.token('/admin/users')
    for method,path in (('DELETE','/admin/delete-user/1'),('POST','/admin/delete-table-user/1')):
        status,_,_=admin.request(method,path,{'_token':tok})
        expect(status==403 and value('SELECT COUNT(*) FROM users WHERE id=1')==1,'Protected administrator deleted')
    status,_,_=admin.request('DELETE','/admin/delete-user/4',{'_token':tok})
    expect(status==302 and value('SELECT COUNT(*) FROM users WHERE id=4')==0,'Admin deletion failed')
    expect(value('SELECT COUNT(*) FROM links WHERE user_id=4')==0,'Admin deleted account links remain')
    new_id=value("SELECT id FROM users WHERE name LIKE 'Admin-Created-%' ORDER BY id LIMIT 1")
    status,_,_=admin.request('POST',f'/admin/delete-table-user/{new_id}',{'_token':tok})
    expect(status==200 and value('SELECT COUNT(*) FROM users WHERE id=?',(new_id,))==0,'Admin AJAX deletion failed')
    with sqlite3.connect(DB) as con:
        expect(con.execute('PRAGMA foreign_key_check').fetchall()==[],'Database contains FK violations')
    return 'self/admin/AJAX delete, unauthorized denial, root protected, cascade and FK integrity'

check('TC-04-D','Permanent synthetic account deletion and foreign keys',account_deletion)

output=Path(os.environ.get('QA_OUTPUT','/tmp/linkfree-audit/http-results.json')).resolve()
assert output.is_relative_to(Path('/tmp/linkfree-audit'))
output.write_text(json.dumps({'source':'ephemeral checkout in GitHub Actions','production':False,'sourceSha':os.getenv('GITHUB_SHA',''),'results':results},ensure_ascii=False,indent=2))
failed=[x for x in results if not x['passed']]
print(f'HTTP RESULT: {len(results)-len(failed)} passed, {len(failed)} failed; {output}',flush=True)
if failed:
    raise SystemExit(1)
