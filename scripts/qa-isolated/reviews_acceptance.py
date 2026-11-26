#!/usr/bin/env python3
"""HTTP review/consent regression; refuses anything other than the disposable fixture."""
import http.cookiejar
import os
import re
import sqlite3
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

BASE = 'http://127.0.0.1:18765'
ROOT = Path('/tmp/linkfree-audit/linkstack').resolve()
DB = ROOT / 'database/audit.sqlite'
assert os.getenv('QA_EXPECTED_SITE') == BASE
assert DB.is_file() and ROOT == Path('/tmp/linkfree-audit/linkstack')
assert 'DB_DATABASE=' + str(DB) in (ROOT / '.env').read_text()
assert 'APP_ENV=local' in (ROOT / '.env').read_text()

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None

class Client:
    def __init__(self):
        self.opener = urllib.request.build_opener(
            urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()), NoRedirect())
    def request(self, path, fields=None):
        assert path.startswith('/') and not path.startswith('//')
        data = urllib.parse.urlencode(fields).encode() if fields is not None else None
        req = urllib.request.Request(BASE + path, data=data)
        try:
            r = self.opener.open(req, timeout=18)
        except urllib.error.HTTPError as e:
            r = e
        return r.status, dict(r.headers), r.read().decode('utf-8', 'replace')
    def token(self, path):
        code, _, html = self.request(path)
        assert code == 200, (path, code)
        m = re.search(r'name=["\']_token["\']\s+value=["\']([^"\']+)', html)
        if not m:
            m = re.search(r'name=["\']csrf-token["\']\s+content=["\']([^"\']+)', html)
        assert m, path
        return m.group(1)
    def login(self, name):
        status, headers, _ = self.request('/login', {
            '_token': self.token('/login'),
            'email': name + '@example.test', 'password': 'Synthetic-password-123'
        })
        assert status == 302 and '/dashboard' in headers.get('Location', ''), (name, status)
        return self

def rows():
    with sqlite3.connect(DB) as d:
        return d.execute('SELECT id,user_id,rating,testimonial,publication_consent,'
                         'consented_at,status FROM platform_reviews').fetchall()

member = Client().login('auditfree')
other = Client().login('auditother')
admin = Client().login('auditadmin')
anon = Client()

status, _, public = anon.request('/plataforma/avaliacoes')
assert status == 200 and 'Nenhuma' in public or status == 200 and 'No approved' in public
assert anon.request('/studio/avaliar')[0] in (302, 401, 403)
assert member.request('/studio/avaliar')[0] == 200
assert other.request('/admin/avaliacoes')[0] in (302, 403)
assert rows() == [], 'New table should be empty in isolated fixture'
print('PASS public read, authorized access and empty fixture')

token = member.token('/studio/avaliar')
assert member.request('/studio/avaliar', {'rating':'5', 'testimonial':'No consent', '_token':token})[0] in (302, 422)
assert rows() == []
assert member.request('/studio/avaliar', {'rating':'6', 'testimonial':'bad', 'publication_consent':'1','_token':token})[0] in (302, 422)
assert rows() == []
assert member.request('/studio/avaliar', {'rating':'5', 'testimonial':'spam', 'publication_consent':'1', 'website':'bot', '_token':token})[0] == 302
assert rows() == []
print('PASS consent, rating validation and honeypot')

review_text = '<script>alert(1)</script> Plataforma de teste'
status, _, _ = member.request('/studio/avaliar', {
    'rating':'4', 'testimonial':review_text, 'publication_consent':'1', '_token':token
})
assert status == 302, status
record = rows()
assert len(record) == 1 and record[0][1] == 2 and record[0][2] == 4
assert record[0][4] == 1 and record[0][5] and record[0][6] == 'pending'
assert member.request('/studio/avaliar', {
    'rating':'1','publication_consent':'1','_token':token
})[0] in (302,422)
assert len(rows()) == 1
assert review_text not in anon.request('/plataforma/avaliacoes')[2]
print('PASS one review per member, pending and invisible before moderation')

admin_token = admin.token('/admin/avaliacoes')
assert member.request('/admin/avaliacoes/' + str(record[0][0]) + '/moderar', {
    '_token':token, 'decision':'approve'
})[0] in (302,403)
assert rows()[0][6] == 'pending'
status, _, _ = admin.request('/admin/avaliacoes/' + str(record[0][0]) + '/moderar', {
    '_token':admin_token, 'decision':'approve'
})
assert status == 302 and rows()[0][6] == 'approved'
_, _, public = anon.request('/plataforma/avaliacoes')
assert '&lt;script&gt;' in public and '<script>alert(1)</script>' not in public
assert 'auditfree@example.test' not in public and 'auditfree' not in public
assert member.request('/studio/avaliar', {'rating':'5','publication_consent':'1','_token':token})[0] in (302,422)
print('PASS admin moderation, escaped public testimonial and anonymity')

status, _, _ = member.request('/studio/avaliar/retirar', {'_token':token})
assert status == 302 and rows() == []
assert '&lt;script&gt;' not in anon.request('/plataforma/avaliacoes')[2]
print('PASS author withdrawal permanently removes publication and review')
print('PASS review consent, publication, role guards, XSS escaping and withdrawal: 5 groups')
