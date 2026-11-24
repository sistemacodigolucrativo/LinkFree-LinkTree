#!/usr/bin/env python3
"""H3-A: somente cópia sintética e servidor isolado; não executar em produção."""
import os,sqlite3,http.client,http.cookies,urllib.parse,re,json,html,pathlib,socket
from html.parser import HTMLParser
ROOT=pathlib.Path('/tmp/linkfree-h3a-private/app'); DB=ROOT/'database/database.sqlite';OUT=pathlib.Path('/tmp/linkfree-h3a-private/resultados.json')
assert os.getuid()==61050 and ROOT.resolve()==ROOT and not pathlib.Path('/srv/linkfree/artisan').exists()
assert 'DB_DATABASE='+str(DB) in (ROOT/'.env').read_text() and not os.system('ip -4 route show | grep -q .')==0
BASE='127.0.0.1';PORT=18775;UA='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36'
class Client:
 def __init__(self,ip='127.0.0.1',ua=UA):self.ip=ip;self.ua=ua;self.cookies={}
 def req(self,path,data=None,headers=None):
  c=http.client.HTTPConnection(BASE,PORT,timeout=20,source_address=(self.ip,0));h={'User-Agent':self.ua,'Accept':'text/html','Accept-Language':'pt-BR,pt;q=0.9','Connection':'close'};h.update(headers or {})
  if self.cookies:h['Cookie']='; '.join(k+'='+v for k,v in self.cookies.items())
  payload=None
  if data is not None:payload=urllib.parse.urlencode(data);h['Content-Type']='application/x-www-form-urlencoded'
  c.request('POST' if data is not None else 'GET',path,payload,h);r=c.getresponse();body=r.read().decode('utf8','replace');hs=r.getheaders()
  for name,value in hs:
   if name.lower()=='set-cookie':
    ck=http.cookies.SimpleCookie();ck.load(value)
    for k,v in ck.items():self.cookies[k]=v.value
  return r.status,dict(hs),body
 def token(self,path):
  s,h,b=self.req(path);ensure(s==200,f'GET {path}: esperado 200 observado {s}, Location={h.get("Location")}');m=re.search(r'name="_token"\s+value="([^"]+)"',b);ensure(m is not None,f'CSRF ausente {path}');return m.group(1)
 def login(self,name):
  t=self.token('/login');s,h,b=self.req('/login',{'_token':t,'email':name+'@example.test','password':'Synthetic-password-123'});ensure(s==302 and h.get('Location','').endswith('/dashboard'),f'login {name}: {s} {h.get("Location")}');return self
 def logout(self):
  t=self.token('/dashboard');s,h,b=self.req('/logout',{'_token':t});ensure(s==302,'logout não redirecionou');self.cookies={}
class Forms(HTMLParser):
 def __init__(self):super().__init__();self.forms=[];self.current=None;self.textarea=None
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='form':self.current={'action':a.get('action',''),'fields':{}}
  elif self.current is not None and tag=='input' and 'name' in a and (a.get('type') not in ('checkbox','radio') or 'checked' in a):self.current['fields'][a['name']]=a.get('value','')
  elif self.current is not None and tag=='textarea' and 'name' in a:self.textarea=a['name'];self.current['fields'][self.textarea]=''
 def handle_data(self,data):
  if self.current is not None and self.textarea:self.current['fields'][self.textarea]+=data
 def handle_endtag(self,tag):
  if tag=='textarea':self.textarea=None
  if tag=='form' and self.current is not None:self.forms.append(self.current);self.current=None
class Table(HTMLParser):
 def __init__(self):super().__init__();self.row=None;self.cell=None;self.rows=[]
 def handle_starttag(self,tag,attrs):
  if tag=='tr':self.row=[]
  if tag in ('th','td') and self.row is not None:self.cell=''
 def handle_data(self,data):
  if self.cell is not None:self.cell+=data
 def handle_endtag(self,tag):
  if tag in ('th','td') and self.cell is not None:self.row.append(self.cell.strip());self.cell=None
  if tag=='tr' and self.row is not None:self.rows.append(self.row);self.row=None
 def mapping(self):return {r[0].lower():r[1] for r in self.rows if len(r)==2}
results=[]
def ensure(x,msg):
 if not x:raise AssertionError(msg)
def run(id,name,fn):
 try:o=fn();r={'id':id,'assertion':name,'pass':True,'observed':o}
 except Exception as e:r={'id':id,'assertion':name,'pass':False,'error':str(e)}
 results.append(r);print(json.dumps(r,ensure_ascii=False),flush=True);OUT.write_text(json.dumps(results,indent=2,ensure_ascii=False))
def query(sql,args=()):
 with sqlite3.connect('file:'+str(DB)+'?mode=ro',uri=True) as c:return c.execute(sql,args).fetchall()
def score(handle):
 rows=query('select score from visits where primary_key=? and secondary_key is null',('visits:users_'+handle+'_total',));ensure(len(rows)<=1,'contadores duplicados');return None if not rows else rows[0][0]
def jsonsource(uid):
 p=ROOT/'storage/app/linkfree/visit-sources'/f'{uid}.json'
 if not p.exists():return None
 v=json.loads(p.read_text());s={}
 for day in v.values():
  for k,n in day.items():s[k]=s.get(k,0)+n
 return s
def form_from(body,suffix):
 p=Forms();p.feed(body);forms=[f for f in p.forms if f['action'].endswith(suffix)];ensure(len(forms)==1,f'form {suffix}: encontrados {len(forms)}');return forms[0]['fields']
def page(c,path):
 s,h,b=c.req(path);ensure(s==200,f'GET {path}: HTTP {s}, Location={h.get("Location")}');return b
admin=Client();vip=Client();free=Client();other=Client()
def prerequisites():
 a=query('select littlelink_name,role,block from users order by id');ensure(a==[('auditadmin','admin','no'),('auditfree','user','no'),('auditother','user','no'),('auditvip','vip','no')],f'usuarios incorretos {a}');ensure(score('auditvip') is None,'fixture já tem visitas VIP');ensure(jsonsource(4) is None,'fixture já tem atribuições VIP');admin.login('auditadmin');vip.login('auditvip');free.login('auditfree');other.login('auditother');return {'usuarios':len(a),'perfis':'Admin, Free, outro Free e VIP', 'contador_inicial':'sem linha','origens_inicial':'sem arquivo','sessoes':'4 logins 302 dashboard'}
run('PRE','perfis, autenticação e baseline sem atribuições',prerequisites)
original={}
def navigation():
 body=page(admin,'/admin/site?section=navigation');fields=form_from(body,'/admin/site/content');ensure('nav_features' in fields,'campo nav_features ausente da seção navigation');original.update(fields);old=fields['nav_features'];new='Recursos H3A Sinteticos';ensure(old!=new,'fixture com mesmo valor');fields['nav_features']=new;s,h,b=admin.req('/admin/site/content',fields);ensure(s==302 and 'section=navigation' in h.get('Location',''),f'POST navegação: {s} {h.get("Location")}');p=ROOT/'storage/app/linkfree/public-site.json';ensure(p.exists(),'arquivo site não criado');ensure(json.loads(p.read_text()).get('nav_features')==new,'JSON nav_features não persistiu');default=page(admin,'/admin/site');section=page(admin,'/admin/site?section=navigation');ensure(new not in default,'fixture histórica: campo inesperado na seção padrão');ensure(new in section,'campo ausente na seção navigation');home=page(Client(),'/');ensure(new in html.unescape(home),'texto não exibido na home');admin.logout();admin.login('auditadmin');section=page(admin,'/admin/site?section=navigation');ensure(form_from(section,'/admin/site/content')['nav_features']==new,'não persistiu após novo login');ensure(new in html.unescape(page(Client(),'/')),'texto não persistiu na home');return {'post':s,'arquivo':'nav_features salvo','admin_default':'identity: campo não presente','admin_navigation':'campo salvo visível após novo login','home':'texto público presente'}
run('TC-03-B','nav_features persistência, seção correta, logout/login e home',navigation)
def theme():
 old=query('select theme from users where id=4')[0][0];original['old_theme']=old;t=vip.token('/studio/theme');s,h,b=vip.req('/studio/theme',{'_token':t,'theme':'PolySleek'});ensure(s==302 and h.get('Location','').endswith('/studio/theme'),f'POST tema {s}');ensure(query('select theme from users where id=4')[0][0]=='PolySleek','tema não persistiu em DB');page(vip,'/studio/theme');vip.logout();vip.login('auditvip');page(vip,'/studio/theme');ensure(query('select theme from users where id=4')[0][0]=='PolySleek','tema não persistiu após novo login');public=page(Client(ip='127.0.0.9'),'/@auditvip');ensure('themes/PolySleek/' in public,'tema escolhido ausente do perfil público');return {'post':s,'database':'PolySleek após novo login','publico':'assets PolySleek presentes'}
run('TC-03-B','tema persiste no banco, nova sessão e perfil público',theme)
def old_fixture():
 before=score('auditother');ensure(before is None,'baseline other não vazio');s,h,b=Client(ip='127.0.0.8',ua='Python-urllib/3.12').req('/@auditother?utm_source=instagram');ensure(s==200,f'perfil crawler {s}');after=score('auditother');ensure(after is None,'crawler inesperadamente contou');return {'HTTP':s,'linha_antes':'ausente','linha_depois':'ausente','interpretação':'crawler ignorado; NoneType não prova erro de contador'}
run('TC-08-D','reprodução controlada de User-Agent histórico ignorado como crawler',old_fixture)
def visits():
 start=score('auditvip');ensure(start is not None,'contagem baseline ausente após perfil de tema');src0=jsonsource(4);ensure(src0 is not None,'agregados ausentes após perfil de tema');v=Client(ip='127.0.0.2');s,h,b=v.req('/@auditvip?utm_source=instagram');ensure(s==200,f'perfil primeira visita {s}');first=score('auditvip');ensure(first is not None,f'linha visits:users_auditvip_total ausente após HTTP {s}');src1=jsonsource(4);ensure(src1 is not None,'arquivo agregado ausente após primeira visita');ensure(first==start+1,f'contador {start}->{first}');ensure(src1.get('instagram',0)==src0.get('instagram',0)+1,f'instagram {src0}->{src1}');s2,h,b=v.req('/@auditvip?utm_source=instagram');ensure(s2==200,'repetição não 200');same=score('auditvip');src2=jsonsource(4);ensure(same==first and src2==src1,'duplicação na janela de 900s');v2=Client(ip='127.0.0.3');s3,h,b=v2.req('/@auditvip',headers={'Referer':'https://t.me/example'});ensure(s3==200,'visita independente não 200');second=score('auditvip');src3=jsonsource(4);ensure(second==first+1,f'contador independente {first}->{second}');ensure(src3.get('telegram',0)==src2.get('telegram',0)+1,'referer telegram não classificado');return {'baseline':start,'após_instagram':first,'repetição':same,'outro_ip':second,'instagram':src3.get('instagram',0),'telegram':src3.get('telegram',0),'janela_segundos':900,'ips':'loopback sintéticos independentes'}
run('TC-08-D','contador, UTM, deduplicação e referrer com identidades distintas',visits)
def reports():
 s,h,b=free.req('/studio/visitors',headers={'Accept':'application/json'});ensure(s==403,f'Free JSON esperado 403, observado {s}, location {h.get("Location")}');s2,h2,b2=free.req('/studio/visitors');ensure(s2 in (302,403),f'Free HTML inesperado {s2}');s3,h3,b3=vip.req('/studio/visitors');ensure(s3==200,f'VIP {s3} {h3.get("Location")}');rows=Table();rows.feed(b3);v=rows.mapping();ensure(v.get('instagram')=='1' and v.get('telegram')=='1',f'fontes VIP divergentes: {v}');vip.logout();vip.login('auditvip');s4,h4,b4=vip.req('/studio/visitors');rows=Table();rows.feed(b4);ensure(s4==200 and rows.mapping().get('instagram')=='1' and rows.mapping().get('telegram')=='1','relatório VIP não persistiu após novo login');s5,h5,b5=admin.req('/studio/visitors?user=4');rows=Table();rows.feed(b5);a=rows.mapping();ensure(s5==200,f'Admin HTTP {s5}');ensure(a.get('instagram')=='0' and a.get('telegram')=='0',f'Admin recebeu origens do VIP: {a}');return {'free_json':s,'free_html':s2,'vip':s4,'vip_instagram':1,'vip_telegram':1,'admin':s5,'admin_origens_proprias':a.get('instagram')=='0' and a.get('telegram')=='0'}
run('TC-03-B/TC-08-D','relatório persiste, Free negado e VIP/Admin isolados',reports)
def restore():
 ensure('nav_features' in original and 'old_theme' in original,'faltam valores iniciais');f=original.copy();f.pop('old_theme');f['_token']=admin.token('/admin/site?section=navigation');s,h,b=admin.req('/admin/site/content',f);ensure(s==302,'falha restaurar nav');stored=json.loads((ROOT/'storage/app/linkfree/public-site.json').read_text());ensure(stored.get('nav_features')==original['nav_features'],'nav não restaurado');t=vip.token('/studio/theme');old=original['old_theme'];s,h,b=vip.req('/studio/theme',{'_token':t,'theme':'' if old is None else old});ensure(s==302,'falha restaurar tema');now=query('select theme from users where id=4')[0][0];ensure(now==old or (old is None and now is None),'tema não restaurado');return {'nav_features':'restaurado','tema':'restaurado'}
run('TC-03-B','restauração dos valores sintéticos originais',restore)
print('TOTAL',len(results),'FALHAS',sum(not x['pass'] for x in results),flush=True)
