#!/usr/bin/env python3
"""Real Laravel HTTP registration using disposable SQLite and a refused local SMTP port."""
import http.cookiejar, os, re, sqlite3, urllib.error, urllib.parse, urllib.request
from pathlib import Path
BASE='http://127.0.0.1:18766'
ROOT=Path('/tmp/linkfree-audit/linkstack').resolve()
DB=ROOT/'database/audit.sqlite'
assert os.environ.get('QA_EXPECTED_REGISTRATION_SITE') == BASE
assert ROOT == Path('/tmp/linkfree-audit/linkstack') and DB.is_file()
assert 'DB_DATABASE='+str(DB) in (ROOT/'.env').read_text()
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl): return None
class Client:
    def __init__(self): self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()),NoRedirect())
    def req(self,path,fields=None):
        assert path.startswith('/') and not path.startswith('//')
        data=urllib.parse.urlencode(fields).encode() if fields is not None else None
        request=urllib.request.Request(BASE+path,data=data,headers={'Referer':BASE+'/register'})
        try: response=self.opener.open(request,timeout=20)
        except urllib.error.HTTPError as e: response=e
        return response.status,dict(response.headers),response.read().decode('utf8','replace')
    def token(self,path='/register'):
        status,_,body=self.req(path); assert status==200,(path,status)
        match=re.search(r'name=["\']_token["\']\s+value=["\']([^"\']+)',body)
        assert match; return match.group(1)
def count(email):
    with sqlite3.connect(DB) as db: return db.execute('select count(*) from users where email=?',(email,)).fetchone()[0]
def form(token,handle,email):
    return {'_token':token,'name':'Synthetic QA','littlelink_name':handle,'email':email,
            'password':'Synthetic-NoSend-123','accept_terms':'1'}
for handle,email,error_id in [('auditfree','unique-qa@example.test','username-error'),('uniqueqa','auditfree@example.test','lf-register-email-error')]:
    c=Client(); token=c.token(); status,_,_=c.req('/register',form(token,handle,email)); assert status==302,status
    status,_,body=c.req('/register'); assert status==200 and 'validation.unique' not in body
    assert error_id in body and 'já está em uso' in body and 'Synthetic QA' in body
print('PASS: duplicate handle and email show localized field errors and preserve safe input')
c=Client(); token=c.token(); email='registration-smtp-failure@example.test'
assert count(email)==0
status,headers,_=c.req('/register',form(token,'registrationfailureqa',email))
assert status==302 and '/verify-email' in headers.get('Location',''),(status,headers.get('Location'))
assert count(email)==1
status,_,body=c.req('/verify-email'); assert status==200 and 'não foi possível enviar o e-mail' in body
print('PASS: refused SMTP does not cause HTTP 500 or duplicate account; verification remains required')

# An actual local SMTP connection failure must stay generic to the administrator
# while recording only a sanitized exception class, never supplied credentials.
admin=Client(); token=admin.token('/login')
status,_,_=admin.req('/login',{'_token':token,'email':'auditadmin@example.test','password':'Synthetic-password-123'})
assert status==302,status
token=admin.token('/admin/config/sections/smtp')
status,headers,_=admin.req('/send-test-email',{'_token':token})
assert status==302 and '/admin/config/sections/smtp' in headers.get('Location','')
status,_,body=admin.req('/admin/config/sections/smtp')
assert status==200 and 'Não foi possível enviar o e-mail de teste' in body
log=Path('/tmp/linkfree-audit/registration-server.log').read_text()
assert len([line for line in log.splitlines() if 'registration_verification' in line]) == 1, 'Expected exactly one verification attempt'
smtp_lines=[line for line in log.splitlines() if 'smtp_test' in line]
assert smtp_lines and 'Email delivery failed' in smtp_lines[-1] and 'exception_type' in smtp_lines[-1] and 'connection' in smtp_lines[-1]
assert 'Synthetic-password-123' not in log and 'Synthetic-NoSend-123' not in log
print('PASS: SMTP test failure shows a localized message and logs only safe metadata')
