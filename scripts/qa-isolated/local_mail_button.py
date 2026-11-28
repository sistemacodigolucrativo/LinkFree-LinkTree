#!/usr/bin/env python3
"""Synthetic admin HTTP test; never connects to a real mail service."""
import http.cookiejar
import os
import re
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

BASE = 'http://127.0.0.1:18767'
assert os.environ.get('QA_EXPECTED_LOCAL_MAIL_SITE') == BASE
capture = Path('/tmp/linkfree-audit/local-mail-button.eml')
capture.unlink(missing_ok=True)

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None

opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()), NoRedirect())
def request(path, data=None):
    assert path.startswith('/') and not path.startswith('//')
    try:
        result = opener.open(BASE + path, data=None if data is None else urllib.parse.urlencode(data).encode(), timeout=15)
    except urllib.error.HTTPError as exc:
        result = exc
    return result.status, dict(result.headers), result.read().decode('utf-8', 'replace')

def token(html):
    found = re.search(r'name=["\']_token["\']\s+value=["\']([^"\']+)', html)
    assert found, 'CSRF token missing'
    return found.group(1)

status, _, login = request('/login')
assert status == 200
status, headers, _ = request('/login', {'_token': token(login), 'email': 'auditadmin@example.test', 'password': 'Synthetic-password-123'})
assert status == 302 and '/dashboard' in headers.get('Location', '')
status, _, page = request('/admin/config/sections/smtp')
assert status == 200 and 'local_php' in page, 'Local option missing from SMTP settings'
status, headers, _ = request('/send-test-email', {'_token': token(page)})
assert status == 302 and '/admin/config/sections/smtp' in headers.get('Location', '')
status, _, page = request('/admin/config/sections/smtp')
assert status == 200 and ('accepted locally' in page.lower() or 'aceita localmente' in page.lower()), 'Local acceptance message missing'
assert capture.is_file(), 'Fake local MTA did not receive the test message'
message = capture.read_text()
assert 'auditadmin@example.test' in message and 'multipart/alternative' in message
print('PASS: authenticated admin test button submitted multipart mail to a fake local MTA')
