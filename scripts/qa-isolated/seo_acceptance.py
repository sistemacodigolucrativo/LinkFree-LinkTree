#!/usr/bin/env python3
"""SEO HTTP regression. Only the disposable localhost CI application."""
import html.parser
import http.cookiejar
import re
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
    # Reuse the existing SEO contract on both new public marketing routes.
    home_sitemap_urls = locs(xml('/sitemap-home.xml'))
    for new_path in ('/recursos', '/proximas-implementacoes'):
        marketing,_=page(new_path)
        expected = BASE + new_path
        assert marketing.one('canonical') == expected, (new_path, marketing.one('canonical'))
        assert marketing.one('og:url') == expected
        assert expected in home_sitemap_urls, (new_path, home_sitemap_urls)
    print('PASS public marketing metadata, social cards and sitemap entries')
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
    # Exercise the actual existing member profile form and owner-only persistence.
    class Member:
        def __init__(self, username):
            self.opener = urllib.request.build_opener(urllib.request.ProxyHandler({}),
                urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()), NoRedirect())
            self.post('/login', {'_token':self.token('/login'), 'email':username+'@example.test',
                'password':'Synthetic-password-123'})
        def request(self, path, data=None):
            payload = urllib.parse.urlencode(data).encode() if data is not None else None
            try:
                res = self.opener.open(urllib.request.Request(BASE+path,data=payload),timeout=15)
            except urllib.error.HTTPError as error:
                res = error
            return res.status, res.headers, res.read().decode('utf-8')
        def token(self, path):
            status, _, html = self.request(path)
            assert status == 200, (path,status)
            return re.search(r'name=["\x27]_token["\x27]\s+value=["\x27]([^"\x27]+)',html).group(1)
        def post(self, path, data):
            return self.request(path,data)
    def settings(id):
        with sqlite3.connect(DB) as d:
            row = d.execute('select image from users where id=?',(id,)).fetchone()[0]
            return json.loads(row or '{}') or {}
    def save(member, handle, name, bio, **extra):
        payload = {'_token':member.token('/studio/page'),'littlelink_name':handle,
                   'name':name,'pageDescription':bio, 'sharebtn':'on','tablinks':'on',
                   'search_noindex':'1','public_visit_count':'1'}
        payload.update(extra)
        status,headers,_=member.post('/studio/page',payload)
        assert status == 302 and '/studio/page' in headers.get('Location',''), (status,headers.get('Location'))
    member=Member('auditfree')
    vip=Member('auditvip')
    emoji_bio='💻 Desenvolvedor Full Stack 🧠 Tecnologia • Inteligência • Estratégia.🚀'
    slogan='🚀 Onde outros enxergam problemas, eu desenvolvo soluções.'
    clean_slogan='Onde outros enxergam problemas, eu desenvolvo soluções.'
    _,_,form=member.request('/studio/page')
    assert form.index('name="social_slogan"') < form.index('name="pageDescription"')
    assert 'maxlength="100"' in form
    other_before=settings(3)
    save(member,'auditfree','Roberto R. Almeida',emoji_bio,social_slogan=slogan,user_id='3',verified_at='2026-01-01',verification_visible='1')
    assert settings(2)['social_slogan']==slogan and settings(3)==other_before
    assert settings(2)['search_noindex'] is True and settings(2)['public_visit_count'] is True
    with sqlite3.connect(DB) as d:
        assert d.execute('select verified_at from users where id=2').fetchone()[0] is None
    _,_,form=member.request('/studio/page')
    assert slogan in form
    # Persistence after a new session, invalid length/type and legacy omission.
    member=Member('auditfree')
    _,_,form=member.request('/studio/page')
    assert slogan in form
    save(member,'auditfree','Roberto R. Almeida',emoji_bio,social_slogan='X'*101)
    assert settings(2)['social_slogan']==slogan
    save(member,'auditfree','Roberto R. Almeida',emoji_bio)
    assert settings(2)['social_slogan']==slogan
    save(vip,'auditvip','auditvip','VIP bio',social_slogan='Slogan VIP')
    assert settings(4)['social_slogan']=='Slogan VIP'
    print('PASS Free/VIP form, persistence, owner isolation, validation, existing flags and legacy omission')
    unicode_page,_=page('/@auditfree')
    assert unicode_page.one('og:description')==emoji_bio
    assert unicode_page.one('og:title')=='Roberto R. Almeida'
    unicode_status, unicode_headers, unicode_image=image(unicode_page.one('og:image'))
    assert unicode_status==200
    jpeg(unicode_image)
    save(member,'auditfree','Roberto R. Almeida',emoji_bio,social_slogan=clean_slogan)
    clean_page,_=page('/@auditfree')
    assert clean_page.one('og:description')==emoji_bio
    assert image(clean_page.one('og:image'))[2]==unicode_image
    # Bio changes affect metadata, not pixels. Slogan changes affect both image URL and pixels.
    save(member,'auditfree','Roberto R. Almeida','Outra biografia',social_slogan=clean_slogan)
    bio_page,_=page('/@auditfree')
    assert bio_page.one('og:description')=='Outra biografia'
    assert image(bio_page.one('og:image'))[2]==unicode_image
    save(member,'auditfree','Roberto R. Almeida','Outra biografia',social_slogan='Outra frase')
    changed,_=page('/@auditfree')
    assert changed.one('og:image')!=bio_page.one('og:image')
    assert image(changed.one('og:image'))[2]!=unicode_image
    save(member,'auditfree','Roberto R. Almeida',emoji_bio,social_slogan='')
    assert settings(2)['social_slogan']==''
    empty,_=page('/@auditfree')
    empty_image=image(empty.one('og:image'))[2]
    save(member,'auditfree','Roberto R. Almeida','Outra biografia',social_slogan='')
    assert image(page('/@auditfree')[0].one('og:image'))[2]==empty_image
    save(member,'auditfree','Roberto R. Almeida',emoji_bio,social_slogan=slogan)
    print('PASS slogan changes image; bio stays external; emoji fallback and empty slogan')
    # Synthetic granted/hidden/revoked states; check JPEG bytes and cache validators.
    before,_=page('/@auditfree')
    status,old_headers,old_image=image(before.one('og:image'))
    with sqlite3.connect(DB) as d:
        d.execute("update users set verified_at='2026-01-01 00:00:00',verification_visible=1 where id=2")
    verified,_=page('/@auditfree')
    status,verified_headers,verified_image=image(verified.one('og:image'),extra={'If-None-Match':old_headers['ETag']})
    assert status==200 and verified_image!=old_image and verified.one('og:image')!=before.one('og:image')
    jpeg(verified_image)
    # Detect actual blue pixels in the title region, not just different bytes.
    with tempfile.NamedTemporaryFile(suffix='.jpg') as f:
        f.write(verified_image); f.flush()
        count=int(subprocess.check_output(['php','-r',
            '$im=imagecreatefromjpeg($argv[1]);$n=0;for($y=210;$y<255;$y++)for($x=558;$x<1110;$x++){$c=imagecolorat($im,$x,$y);$r=($c>>16)&255;$g=($c>>8)&255;$b=$c&255;if($r<75&&$g>95&&$b>170)$n++;}echo $n;',f.name],text=True))
        assert count>100,count
    save(member,'auditfree','Roberto R. Almeida',emoji_bio,social_slogan=slogan)
    hidden_badge,_=page('/@auditfree')
    assert image(hidden_badge.one('og:image'))[2]==old_image
    save(member,'auditfree','Roberto R. Almeida',emoji_bio,social_slogan=slogan,verification_visible='1')
    assert image(page('/@auditfree')[0].one('og:image'))[2]==verified_image
    with sqlite3.connect(DB) as d:
        d.execute("update users set verified_at=null where id=2")
    revoked,_=page('/@auditfree')
    assert image(revoked.one('og:image'),extra={'If-None-Match':verified_headers['ETag']})[0]==200
    assert image(revoked.one('og:image'))[2]==old_image
    # Remove the test privacy flags from auditfree for the existing sitemap assertions.
    with sqlite3.connect(DB) as d:
        data=settings(2)
        data['search_noindex']=False
        d.execute("update users set image=? where id=2",(json.dumps(data),))
    artifact=os.getenv('SEO_CARD_ARTIFACT')
    if artifact: Path(artifact).write_bytes(verified_image)
    print('PASS actual blue badge; visibility toggle, revoked, cache invalidation')
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
