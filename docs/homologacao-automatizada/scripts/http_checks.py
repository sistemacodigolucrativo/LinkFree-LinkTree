import urllib.request,urllib.error,urllib.parse,http.cookiejar,re,json,sqlite3,pathlib
BASE='http://127.0.0.1:18765';ROOT=pathlib.Path('/tmp/linkfree-audit');DB=ROOT/'linkstack/database/audit.sqlite'
assert DB.resolve().is_relative_to(ROOT)
class NoRedirect(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,*a): return None
class Client:
 def __init__(self):self.jar=http.cookiejar.CookieJar();self.op=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar),NoRedirect())
 def request(self,path,data=None):
  rq=urllib.request.Request(BASE+path,data=urllib.parse.urlencode(data).encode() if data is not None else None)
  try:r=self.op.open(rq)
  except urllib.error.HTTPError as e:r=e
  return r.code,dict(r.headers),r.read().decode(errors='replace')
 def token(self,path='/login'):
  s,h,b=self.request(path);assert s==200,(path,s)
  return re.search(r'name="_token" value="([^"]+)"',b)[1]
 def login(self,user):
  token=self.token();s,h,b=self.request('/login',{'_token':token,'email':user+'@example.test','password':'Synthetic-password-123'});assert s==302,(s,b[:80]);assert h.get('Location','').endswith('/dashboard');return self
results=[]
def check(id,name,f):
 try:r=f();results.append(dict(id=id,assertion=name,pass_=True,observed=r));print('PASS',id,name,r)
 except Exception as e:results.append(dict(id=id,assertion=name,pass_=False,error=str(e)));print('FAIL',id,name,str(e))
def expect(v,msg):
 if not v:raise AssertionError(msg)
def scalar(sql,params=()):
 with sqlite3.connect(DB) as d:return d.execute(sql,params).fetchone()[0]
anon=Client();member=Client().login('auditfree');admin=Client().login('auditadmin');vip=Client().login('auditvip')
def pages():
 out={}
 for p in ['/','/login','/register','/report','/@auditfree','/does-not-exist']:
  s,h,b=anon.request(p);out[p]=s;expect(s==(404 if p=='/does-not-exist' else 200),str(out));expect('Stack trace' not in b,'stack trace')
 return out
check('TC-10-B','public pages true 404 no trace',pages)
def permissions():
 out={}
 for path in ['/admin/referrals','/admin/verification','/admin/reports','/admin/reports/1','/admin/users']:
  for role,c in [('anon',anon),('member',member),('admin',admin)]:
   s,h,b=c.request(path);out[role+path]=s;expect(s==(200 if role=='admin' else 302),str(out))
 return out
check('TC-10-C','HTTP middleware gates admin routes',permissions)
def csrf():
 before=scalar('select count(*) from abuse_reports');s,h,b=anon.request('/report',{'url':'https://example.test','report-type':'fraud_scams'});expect(s==302 and h.get('Location','').endswith('/login'),'CSRF must redirect to login');expect(scalar('select count(*) from abuse_reports')==before,'persisted');return s
check('TC-07-B','CSRF rejects missing token without persistence',csrf)
def throttle():
 t=anon.token('/report');out=[]
 for i in range(6):out.append(anon.request('/report',{'_token':t,'url':'https://example.test','report-type':'fraud_scams','message':'<script>window.__auditXss=1</script>'})[0])
 expect(out==[302]*5+[429],str(out));return out
check('TC-07-B','five requests accepted sixth throttled in isolated app',throttle)
def xss():
 id=scalar('select max(id) from abuse_reports');s,h,b=admin.request('/admin/reports/'+str(id));expect(s==200,str(s));expect('<script>window.__auditXss=1</script>' not in b,'raw script');expect('&lt;script&gt;window.__auditXss=1&lt;/script&gt;' in b,'escaped payload absent');return 'escaped in admin HTML'
check('TC-07-B','XSS text escaped in rendered admin detail',xss)
def noindex():
 s,h,b=anon.request('/@auditfree');expect(s==200,str(s));expect('noindex' in h.get('X-Robots-Tag',''),'header absent');expect(re.search(r'<meta[^>]*name="robots"[^>]*noindex',b) is not None,'meta absent');return h.get('X-Robots-Tag')
check('TC-08-C','public profile renders persisted noindex meta and header',noindex)
def access():
 id=scalar("select links.id from links join users on users.id=links.user_id where users.email='auditvip@example.test' limit 1")
 s,h,b=member.request('/studio/edit-link/'+str(id));expect(s==403,str(s));return s
check('TC-04-D','cross-account link edit denied',access)
def reload_logout():
 for path in ['/dashboard','/studio/profile','/studio/referrals','/studio/verification','/studio/page','/studio/links']:
  for i in range(2):expect(member.request(path)[0]==200,path)
 t=member.token('/studio/profile');expect(member.request('/logout',{'_token':t})[0]==302,'logout');expect(member.request('/studio/profile')[0]==302,'after logout');return 'refresh and logout protected'
check('TC-04-D','login session refresh logout and anonymous denial',reload_logout)
def get_mutation():
 c=Client().login('auditvip');id=scalar("select links.id from links join users on users.id=links.user_id where users.email='auditvip@example.test' and links.type='link' limit 1")
 s,h,b=c.request('/deleteLink/'+str(id));remaining=scalar('select count(*) from links where id=?',(id,));expect(remaining==1,'GET deleted synthetic link without CSRF token; HTTP '+str(s));return s
check('TC-04-D','GET must not delete link without CSRF',get_mutation)
(ROOT/'docs/homologacao-automatizada/evidencias/http.json').write_text(json.dumps(results,indent=2,ensure_ascii=False))
