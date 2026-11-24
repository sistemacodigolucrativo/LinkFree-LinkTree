#!/usr/bin/env python3
"""SEO HTTP regression. Only the disposable localhost CI application."""
import html.parser
import json
import os
import sqlite3
import subprocess
import tempfile
import urllib.request
import urllib.error
import urllib.parse
import xml.etree.ElementTree as ET
from pathlib import Path

ROOT = Path('/tmp/linkfree-audit/linkstack').resolve()
DB = ROOT / 'database/audit.sqlite'
BASE = 'http://127.0.0.1:18765'
assert os.getenv('QA_EXPECTED_SITE') == BASE
assert ROOT == Path('/tmp/linkfree-audit/linkstack') and DB.is_file()
assert 'DB_DATABASE=' + str(DB) in (ROOT / '.env').read_text()

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args):
        return None

opener = urllib.request.build_opener(urllib.request.ProxyHandler({}), NoRedirect())

def get(path, headers=None):
    assert path.startswith('/') and not path.startswith('//')
    hdr = {'User-Agent': 'facebookexternalhit/1.1'}
    hdr.update(headers or {})
    try:
        response = opener.open(urllib.request.Request(BASE+path, headers=hdr), timeout=15)
    except urllib.error.HTTPError as error:
        response = error
    return response.status, response.headers, response.read()

class Metadata(html.parser.HTMLParser):
    def __init__(self, body):
        super().__init__()
        self.tags = {}
        self.schemas = []
        self.collect = False
        self.feed(body.decode('utf-8'))
    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'meta':
            key = attrs.get('property', attrs.get('name'))
            if key:
                self.tags.setdefault(key, []).append(attrs.get('content', ''))
        if tag == 'link' and attrs.get('rel') == 'canonical':
            self.tags.setdefault('canonical', []).append(attrs.get('href', ''))
        if tag == 'script' and attrs.get('type') == 'application/ld+json':
            self.collect = True
    def handle_endtag(self, tag):
        if tag == 'script':
            self.collect = False
    def handle_data(self, data):
        if self.collect:
            self.schemas.append(json.loads(data))
    def one(self, key):
        values = self.tags.get(key, [])
        assert len(values) == 1, (key, values)
        return values[0]

def page(path, host=None):
    code, headers, body = get(path, {'Host':host} if host else None)
    assert code == 200, (path, code)
    m = Metadata(body)
    for key in ('description','robots','canonical','og:title','og:description','og:url','og:image',
                'og:image:type','og:image:width','og:image:height','twitter:card','twitter:image'):
        assert m.one(key), (path,key)
    assert m.one('og:image:type') == 'image/jpeg'
    assert (m.one('og:image:width'),m.one('og:image:height')) == ('1200','630')
    assert m.one('og:image') == m.one('twitter:image')
    assert m.one('og:url') == m.one('canonical')
    return m, headers

def image(url, host=None, extra=None):
    p = urllib.parse.urlsplit(url)
    assert p.hostname in ('127.0.0.1','seo-profile.test')
    headers = {'Host':host} if host else {}
    headers.update(extra or {})
    return get(p.path+('?' + p.query if p.query else ''), headers)

def jpeg(data):
    # Actually decode the JPEG with GD and check dimensions, not merely extension.
    with tempfile.NamedTemporaryFile(suffix='.jpg') as f:
        f.write(data); f.flush()
        result = subprocess.check_output(['php','-r',
            '$im=imagecreatefromjpeg($argv[1]); if(!$im) exit(1); echo imagesx($im)."x".imagesy($im);',
            f.name], text=True)
        assert result == '1200x630', result

def xml(path, host=None):
    status, headers, data = get(path, {'Host':host} if host else None)
    assert status == 200 and 'application/xml' in headers['Content-Type'], (path,status)
    return ET.fromstring(data)

def locs(root):
    return [x.text for x in root.findall('.//{http://www.sitemaps.org/schemas/sitemap/0.9}loc')]

mode=os.getenv('SEO_TEST_MODE','default')
if mode == 'global-noindex':
    m,_=page('/@auditfree')
    assert 'noindex' in m.one('robots') or 'none' in m.one('robots'), m.one('robots')
    assert not locs(xml('/sitemap.xml')) and not locs(xml('/sitemap-profiles/1.xml'))
    print('PASS global robots noindex/none excludes sitemap')
elif mode == 'home-profile':
    m,_=page('/')
    assert m.one('canonical').rstrip('/') == BASE
    assert m.one('og:title') == 'auditfree'
    assert any(x.rstrip('/')==BASE for x in locs(xml('/sitemap-home.xml')))
    assert all('@auditfree' not in x for x in locs(xml('/sitemap-profiles/1.xml')))
    with sqlite3.connect(DB) as d:
        d.execute("update users set block='yes' where id=2")
    assert get('/')[0] == 404
    assert not any(x.rstrip('/') == BASE for x in locs(xml('/sitemap-home.xml')))
    print('PASS HOME_URL canonical root, no duplicate and blocked home rejected')
else:
    home,_=page('/')
    assert any(x.get('@type')=='WebSite' for x in home.schemas)
    profile,_=page('/@auditfree')
    assert profile.one('og:title') == 'auditfree'
    assert any(x.get('@type')=='ProfilePage' and x['mainEntity']['name']=='auditfree' for x in profile.schemas)
    assert all('@example.test' not in json.dumps(x) for x in profile.schemas)
    for label, m in [('home',home),('profile',profile),('fallback',page('/@auditvip')[0])]:
        status, head, data = image(m.one('og:image'))
        assert status == 200 and head['Content-Type'].startswith('image/jpeg')
        jpeg(data)
        assert len(data)>1000 and head.get('ETag')
        assert image(m.one('og:image'),extra={'If-None-Match':head['ETag']})[0]==304
        print('PASS JPEG 1200x630 + ETag',label)
    assert image(profile.one('og:image'))[2] != image(page('/@auditvip')[0].one('og:image'))[2]
    assert get('/social-preview/profile/2-wide.jpg')[0]==200
    assert get('/social-preview/profile/999999999.jpg')[0]==404
    print('PASS profile-specific images, legacy wide URL and nonexistent profile')
    # Real raster and metadata comparison with synthetic Unicode input.
    emoji_bio = '💻 Desenvolvedor Full Stack 🧠 Tecnologia • Inteligência • Estratégia.🚀'
    clean_bio = 'Desenvolvedor Full Stack Tecnologia • Inteligência • Estratégia.'
    with sqlite3.connect(DB) as d:
        d.execute("update users set name=?,littlelink_description=? where id=2", ('Roberto R. Almeida',emoji_bio))
    unicode_page,_ = page('/@auditfree')
    assert unicode_page.one('og:description') == emoji_bio
    assert unicode_page.one('og:title') == 'Roberto R. Almeida'
    unicode_status, unicode_headers, unicode_image = image(unicode_page.one('og:image'))
    assert unicode_status == 200
    jpeg(unicode_image)
    with sqlite3.connect(DB) as d:
        d.execute("update users set littlelink_description=? where id=2",(clean_bio,))
    clean_page,_ = page('/@auditfree')
    assert clean_page.one('og:description') == clean_bio
    assert image(clean_page.one('og:image'))[2] == unicode_image, 'Emoji fallback changes pixels compared with clean text'
    artifact = os.getenv('SEO_CARD_ARTIFACT')
    if artifact:
        Path(artifact).write_bytes(unicode_image)
    print('PASS Unicode metadata preserved; raster equals clean text; adaptive title rendered')
    original_image = image(profile.one('og:image'))[2]
    # Real update of synthetic public data, including potentially unsafe description.
    with sqlite3.connect(DB) as d:
        d.execute("update users set name=?,littlelink_description=? where id=2",('Nome & QA','Descrição <b>pública</b> &amp; teste </script>'))
        d.execute("update users set image=? where id=3",(json.dumps({'search_noindex':True}),))
        d.execute("update users set block='yes' where id=4")
    updated,_=page('/@auditfree')
    assert updated.one('og:title')=='Nome & QA'
    assert 'Descrição pública & teste' in updated.one('og:description')
    assert updated.one('og:image') != profile.one('og:image')
    assert image(updated.one('og:image'))[2] != original_image
    hidden,headers=page('/@auditother')
    assert hidden.one('robots') == 'noindex, follow' and 'noindex' in headers['X-Robots-Tag']
    assert image(hidden.one('og:image'))[0]==200
    assert get('/@auditvip')[0]==404 and get('/social-preview/profile/4.jpg')[0]==404
    assert get('/social-preview/profile/4-wide.jpg')[0]==404
    print('PASS metadata update, escaping, noindex preserved, blocked images denied')
    status,head,body=get('/robots.txt')
    assert status==200 and 'text/plain' in head['Content-Type'] and b'Sitemap:' in body
    assert '/sitemap-profiles/1.xml' in ' '.join(locs(xml('/sitemap.xml')))
    urls=locs(xml('/sitemap-profiles/1.xml'))
    assert any('@auditfree' in x for x in urls) and not any('@auditother' in x or '@auditvip' in x for x in urls)
    assert get('/sitemap-profiles/999.xml')[0]==404
    print('PASS XML sitemap excludes noindex and blocked profiles')
    m,_=page('/', 'seo-profile.test')
    assert m.one('canonical').rstrip('/')=='http://seo-profile.test'
    assert [x.rstrip('/') for x in locs(xml('/sitemap.xml','seo-profile.test'))]==['http://seo-profile.test']
    assert not locs(xml('/sitemap-profiles/1.xml','seo-profile.test'))
    print('PASS custom domain canonical and isolated sitemap')
    assert get('/login')[0]==200 and get('/register')[0]==200
    assert get('/admin/users')[0] in (302,403)
    print('PASS authentication routes still protected')
