#!/usr/bin/env python3
"""Exercise the virgin installation over HTTP, only on disposable runner services."""
import hashlib
import http.cookiejar
import json
import os
import re
import sqlite3
import subprocess
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

ROOT=Path('/tmp/linkfree-install/linkstack').resolve()
BASE='http://127.0.0.1:18766'
DB=ROOT/'database/audit.sqlite'
ENGINE=os.environ['QA_INSTALL_ENGINE']
assert ENGINE in ('sqlite','mysql') and os.environ['QA_INSTALL_ORIGIN']==BASE
assert ROOT == Path('/tmp/linkfree-install/linkstack') and DB.is_file()
assert 'APP_ENV=local' in (ROOT/'.env').read_text()
assert 'DB_DATABASE='+str(DB) in (ROOT/'.env').read_text()
assert 'MAIL_MAILER=log' in (ROOT/'.env').read_text()
assert (ROOT/'INSTALLING').is_file() and not (ROOT/'INSTALLERLOCK').exists()
results=[]

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*args,**kwargs): return None
class Client:
    def __init__(self):
        self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()),NoRedirect(),urllib.request.ProxyHandler({}))
    def req(self,method,path,data=None):
        assert path.startswith('/') and not path.startswith('//')
        request=urllib.request.Request(BASE+path, method=method, data=None if data is None else urllib.parse.urlencode(data).encode(), headers={'Accept':'application/json'})
        try: response=self.opener.open(request,timeout=90)
        except urllib.error.HTTPError as e: response=e
        return response.status,urllib.parse.urlparse(response.headers.get('Location','')).path,urllib.parse.urlparse(response.headers.get('Location','')).query,response.read().decode('utf-8','replace')
    def token(self,path):
        status,_,_,html=self.req('GET',path)
        assert status==200, f'CSRF page {path} returned {status}'
        match=re.search(r'name=["\']_token["\']\s+value=["\']([^"\']+)',html)
        if not match: match=re.search(r'name=["\']csrf-token["\']\s+content=["\']([^"\']+)',html)
        assert match,'No CSRF token'
        return match.group(1)
def expect(condition,message):
    if not condition: raise AssertionError(message)
def snapshot():
    return hashlib.sha256((ROOT/'.env').read_bytes()).hexdigest()
def mysql_sql(sql):
    # All parameters are fixed synthetic fixture values, not environment credentials.
    code=r'$p=new PDO("mysql:host=127.0.0.1;port=33306;dbname=lf_install","lf_fixture","qa-fixture-only");$s=$p->query($argv[1]);echo json_encode($s?$s->fetchAll(PDO::FETCH_NUM):[]);'
    return json.loads(subprocess.check_output(['php','-r',code,sql],text=True))
def query(sql):
    if ENGINE=='mysql': return mysql_sql(sql)
    with sqlite3.connect(DB) as con: return con.execute(sql).fetchall()
def checked(name,fn):
    try:
        fn()
    except Exception as exc:
        results.append({'name':name,'passed':False,'error':str(exc)})
        (ROOT.parent/'installer-results.json').write_text(json.dumps({'engine':ENGINE,'sourceSha':os.getenv('GITHUB_SHA'),'production':False,'results':results},indent=2))
        raise
    results.append({'name':name,'passed':True})
    print('PASS installer: '+name,flush=True)

owner=Client(); stranger=Client()
def preinstall():
    before=snapshot()
    token=owner.token('/?3')
    for url,code in [('/mysql-test',405),('/skip',404),('/anything',404),('/?5',403)]:
        expect(owner.req('GET',url)[0]==code,'Unexpected status: '+url)
    status=owner.req('POST','/db',{})[0]
    expect(status==419,f'Missing CSRF returned {status}, expected 419')
    for path in ('/login','/register','/admin/new-user','/social-auth/github/consent'):
        status=owner.req('POST',path,{'_token':token})[0]
        expect(status in (404,405),f'Normal application route exposed during installation: {path} {status}')
    expect(owner.req('POST','/editConfigInstaller',{'_token':token,'entry':'APP_KEY','value':'invalid'})[0]==422,'Arbitrary env key accepted')
    expect(owner.req('POST','/options',{'_token':token})[0]==403,'Options before root accepted')
    expect(snapshot()==before and (ROOT/'INSTALLING').exists() and not (ROOT/'INSTALLERLOCK').exists(),'Read or unauthorized action mutated fixture')
    with sqlite3.connect(DB) as con: expect(not con.execute("SELECT name FROM sqlite_master WHERE type='table'").fetchall(),'Virgin SQLite already changed')
checked('GET side effects denied, CSRF and arbitrary env denied',preinstall)

def database():
    t=owner.token('/?3')
    if ENGINE=='sqlite':
        status,_,q,_=owner.req('POST','/db',{'_token':t,'database':'SQLite'})
        expect(status==302 and q=='4',f'SQLite init did not redirect to step 4 ({status},{q})')
    else:
        status,_,q,_=owner.req('POST','/db',{'_token':t,'database':'MySQL'})
        expect(status==302 and q=='mysql','MySQL choice failed')
        t=owner.token('/?mysql')
        data={'_token':t,'host':'127.0.0.1','port':'33306','name':'lf_install','username':'lf_fixture','password':'qa-fixture-only'}
        before=snapshot()
        expect(mysql_sql('SHOW TABLES')==[],'MySQL service is not empty')
        mysql_sql('CREATE TABLE qa_guard (id INT PRIMARY KEY)')
        mysql_sql('INSERT INTO qa_guard VALUES (7)')
        expect(owner.req('POST','/mysql',data)[0]==409,'Nonempty schema accepted')
        expect(mysql_sql('SELECT id FROM qa_guard')==[[7]] and mysql_sql('SHOW TABLES')==[['qa_guard']] and snapshot()==before,'Existing fixture schema or env changed')
        mysql_sql('DROP TABLE qa_guard')
        status,_,q,_=owner.req('POST','/mysql',{**data,'password':'wrong-synthetic'})
        expect(status==302 and q=='error' and snapshot()==before and mysql_sql('SHOW TABLES')==[],'Wrong connection altered state')
        status,_,q,_=owner.req('POST','/mysql',data)
        expect(status==302 and q=='4',f'MySQL init did not redirect to step 4 ({status},{q})')
        expect('DB_CONNECTION="mysql"' in (ROOT/'.env').read_text(),'MySQL configuration was not persisted')
    expect(query('SELECT COUNT(*) FROM users')[0][0]==0,'Database contains users before account creation')
    expect(int(query('SELECT COUNT(*) FROM pages')[0][0])>=1 and int(query('SELECT COUNT(*) FROM buttons')[0][0])>=1,'Catalog not initialized')
checked('database initialization with empty schema only',database)

def create_root():
    t=owner.token('/?4')
    stranger_token=stranger.token('/?4')
    data={'_token':t,'email':'setup@example.test','name':'QA Setup','handle':'qasetup','password':'Synthetic-setup-456'}
    expect(owner.req('POST','/create-admin',{**data,'_token':'bad'})[0]==419,'Invalid CSRF accepted')
    status,_,q,_=owner.req('POST','/create-admin',data)
    expect(status==302 and q=='5',f'Admin create failed ({status},{q})')
    expect(query('SELECT id,role,email FROM users')==[(1,'admin','setup@example.test')] or query('SELECT id,role,email FROM users')==[[1,'admin','setup@example.test']],'Root account missing or duplicated')
    expect((ROOT/'INSTALLING').exists() and (ROOT/'INSTALLERLOCK').exists(),'Setup lock missing')
    before=snapshot()
    expect(stranger.req('POST','/create-admin',{**data,'_token':stranger_token})[0]==403,'Second admin creation accepted')
    expect(stranger.req('GET','/?5')[0]==403,'Other session can read protected finalization')
    expect(stranger.req('POST','/options',{'_token':stranger_token,'register':'No','verify':'No','page':'No','app':'Other'})[0]==403,'Other session finalized')
    expect(owner.req('GET','/unrelated')[0]==404 and (ROOT/'INSTALLING').exists() and snapshot()==before,'GET removed setup marker')
    expect(int(query('SELECT COUNT(*) FROM users')[0][0])==1,'Duplicate account created')
checked('first admin creation and session-bound setup lock',create_root)

def finalize():
    t=owner.token('/?5')
    fields={'_token':t,'register':'Yes','verify':'No','page':'No','app':'LinkFreeQA'}
    expect(owner.req('POST','/options',{**fields,'_token':'bad'})[0]==419,'Finalization without CSRF')
    expect((ROOT/'INSTALLING').exists() and (ROOT/'INSTALLERLOCK').exists(),'CSRF removed markers')
    status,path,_,_=owner.req('POST','/options',fields)
    expect(status==302 and path=='/dashboard',f'Finalization failed: {status}, {path}')
    expect(not (ROOT/'INSTALLING').exists() and not (ROOT/'INSTALLERLOCK').exists(),'Markers remain')
    t=owner.token('/login')
    status,path,_,_=owner.req('POST','/login',{'_token':t,'email':'setup@example.test','password':'Synthetic-setup-456'})
    expect(status==302 and path=='/dashboard','New root cannot sign in')
    expect(owner.req('GET','/admin/users')[0]==200,'New root cannot access Admin')
    before=snapshot()
    expect(stranger.req('POST','/options',{'_token':'bad'})[0] in (404,405,419,302),'Completed installer still exposed')
    expect(snapshot()==before and int(query('SELECT COUNT(*) FROM users')[0][0])==1,'Post-install request changed data')
checked('CSRF finalization, marker removal, login and installed lockout',finalize)

output=ROOT.parent/'installer-results.json'
output.write_text(json.dumps({'engine':ENGINE,'sourceSha':os.getenv('GITHUB_SHA'),'production':False,'results':results},indent=2))
print('INSTALLER RESULT: '+ENGINE+' '+str(len(results))+'/4 passed',flush=True)
