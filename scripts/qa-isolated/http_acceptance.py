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
        '/admin/site/delfavicon','/send-test-email','/auth-as/2','/admin/new-user'
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
    status,_,body = member.request('DELETE','/deleteLink/101',{'_token':tok},json_response=True)
    expect(status == 200, f'expected JSON 200, got {status} {body[:90]}')
    expect(value('SELECT COUNT(*) FROM links WHERE id=101')==0,'Own link remained after DELETE')
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
    status, _, _ = admin.request('POST', '/admin/new-user', {}, json_response=True)
    expect(status == 419, f'Admin creation without CSRF returned {status}')
    expect(value('SELECT COUNT(*) FROM users') == before, 'Account created without CSRF')
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
    return {'csrfDenied':419,'nonAdminDenied':True,'createdExactlyOne':True,'redirectedToEditor':True}

check('TC-04-D','Admin account creation requires POST, CSRF and administrator privileges',admin_create_user)

output=Path(os.environ.get('QA_OUTPUT','/tmp/linkfree-audit/http-results.json')).resolve()
assert output.is_relative_to(Path('/tmp/linkfree-audit'))
output.write_text(json.dumps({'source':'ephemeral checkout in GitHub Actions','production':False,'results':results},ensure_ascii=False,indent=2))
failed=[x for x in results if not x['passed']]
print(f'HTTP RESULT: {len(results)-len(failed)} passed, {len(failed)} failed; {output}',flush=True)
if failed:
    raise SystemExit(1)
