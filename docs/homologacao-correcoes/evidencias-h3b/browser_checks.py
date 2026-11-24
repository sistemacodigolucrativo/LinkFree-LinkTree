#!/usr/bin/env python3
"""HTTP H3-B só com dados fictícios em namespace isolado; sem transporte externo."""
import os,pathlib,sqlite3,http.client,http.cookies,urllib.parse,json,re,hashlib,email,html,time
ROOT=pathlib.Path('/tmp/linkfree-h3b-private/app');DB=ROOT/'database/database.sqlite';OUT=pathlib.Path('/tmp/linkfree-h3b-private/http-resultados.json');assert os.getuid()==61050 and not pathlib.Path('/srv/linkfree/artisan').exists();assert 'DB_DATABASE='+str(DB) in (ROOT/'.env').read_text()
UA='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36';results=[]
def ensure(x,msg):
 if not x:raise AssertionError(msg)
def record(id,name,fn,critical=False):
 try:r={'id':id,'assertion':name,'pass':True,'observed':fn()}
 except Exception as e:
  r={'id':id,'assertion':name,'pass':False,'error':str(e)};results.append(r);OUT.write_text(json.dumps(results,indent=2,ensure_ascii=False));print(json.dumps(r,ensure_ascii=False),flush=True)
  if critical:raise SystemExit('Interrompido por possível exposição P0/P1; revisão necessária')
  return
 results.append(r);OUT.write_text(json.dumps(results,indent=2,ensure_ascii=False));print(json.dumps(r,ensure_ascii=False),flush=True)
class Client:
 def __init__(self):self.cookies={};self.last_cookies=[]
 def req(self,path,data=None,accept='text/html'):
  c=http.client.HTTPConnection('127.0.0.1',18776,timeout=20);head={'User-Agent':UA,'Accept':accept,'Accept-Language':'pt-BR,pt;q=0.9','Connection':'close'}
  if self.cookies:head['Cookie']='; '.join(k+'='+v for k,v in self.cookies.items())
  body=None
  if data is not None:body=urllib.parse.urlencode(data);head['Content-Type']='application/x-www-form-urlencoded'
  c.request('POST' if data is not None else 'GET',path,body,head);r=c.getresponse();raw=r.read().decode('utf8','replace');hs=r.getheaders();self.last_cookies=[]
  for k,v in hs:
   if k.lower()=='set-cookie':
    self.last_cookies.append(v);o=http.cookies.SimpleCookie();o.load(v)
    for key,m in o.items():self.cookies[key]=m.value
  return r.status,dict(hs),raw
 def token(self,path):
  s,h,b=self.req(path);ensure(s==200,f'GET {path} esperado 200 observado {s}; Location={h.get("Location")}');m=re.search(r'name="_token"\s+value="([^"]+)"',b);ensure(bool(m),'CSRF ausente');return m.group(1)
 def login(self,who,password='Synthetic-password-123'):
  t=self.token('/login');s,h,b=self.req('/login',{'_token':t,'email':who+'@example.test','password':password});ensure(s==302 and h.get('Location','').endswith('/dashboard'),f'login {who}: HTTP {s}');return self
 def logout(self):
  t=self.token('/dashboard');s,h,b=self.req('/logout',{'_token':t});ensure(s==302,'logout falhou');self.cookies={}
def query(sql,args=()):
 with sqlite3.connect('file:'+str(DB)+'?mode=ro',uri=True) as c:return c.execute(sql,args).fetchall()
def counts():return [query('select count(*) from users')[0][0],query('select count(*) from registration_consents')[0][0]]
def private():
 c=Client();codes={}
 for path in ['/.env','/database/database.sqlite','/backups/h3b-stub.zip','/storage/app/linkfree/h3b-private.txt','/config/advanced-config.php']:
  s,h,b=c.req(path);codes[path]=s;ensure(s==403,f'{path} HTTP {s} (esperado 403)');ensure('H3B_PRIVATE_STUB' not in b and 'H3B_BACKUP_STUB' not in b,'stub privado exposto')
 for path in ['/export-links','/export-all','/admin/backups']:
  s,h,b=c.req(path);ensure(s in (301,302,303,401,403,404),f'anônimo {path}={s}')
 return {'arquivos_e_backups':codes,'download_anonimo':'negado','config':'nginx equivalente com backend PHP local'}
record('TC-03-C','stubs privados e downloads anônimos negados',private,True)
def accounts():
 free=Client().login('auditfree');other=Client().login('auditother');a=Client().login('auditadmin');s,h,b=free.req('/export-all?user=3');ensure(s==200,f'export Free {s}');data=json.loads(b);ensure(data.get('littlelink_name')=='auditfree','export retornou conta alheia');ensure(all(x.get('user_id')==2 for x in data.get('links',[])),'export inclui link de terceiro');ensure('password' not in data and 'remember_token' not in data,'export expõe dados de autenticação');s,h,b=free.req('/studio/edit-link/102');ensure(s==403,f'edição de link alheio {s}');s,h,b=free.req('/admin/config');ensure(s in (302,403),f'Free admin/config {s}');s,h,b=a.req('/admin/config');ensure(s==200,f'Admin config {s}');return {'export':'somente própria conta e links','link_outra_conta':403,'admin_membro':'negado','admin_autorizado':200}
record('TC-03-C/TC-10-C','exports e acesso entre contas',accounts,True)
def without_consent():
 before=counts();c=Client();t=c.token('/register');data={'_token':t,'name':'QA No Consent','email':'h3b-no-consent@example.test','littlelink_name':'h3bnoconsent','password':'Synthetic-password-123'};s,h,b=c.req('/register',data,'application/json');ensure(s==422,f'sem aceite esperado 422 recebido {s}');e=json.loads(b).get('errors',{});ensure('accept_terms' in e,'mensagem do aceite ausente');ensure(counts()==before,'usuário ou recibo criado sem aceite');return {'http':s,'mensagem_aceite':True,'delta_usuarios':0,'delta_recibos':0}
record('TC-04-A','cadastro HTTP sem aceite não cria registros',without_consent)
def registration():
 before=counts();c=Client();t=c.token('/register');d={'_token':t,'name':'QA With Consent','email':'h3b-consent@example.test','littlelink_name':'h3bconsent','password':'Synthetic-password-123','accept_terms':'1'};s,h,b=c.req('/register',d);ensure(s==302 and h.get('Location','').endswith('/dashboard'),f'cadastro aceito {s}');after=counts();ensure(after==[before[0]+1,before[1]+1],f'delta incorreto {before} -> {after}');u=query("select id,role,block from users where email=?",(d['email'],));ensure(len(u)==1 and u[0][1:] == ('user','no'),f'papel/estado inesperado {u}');receipt=query('select method,terms_version,privacy_version,accepted_at from registration_consents where user_id=?',(u[0][0],));legal=query('select terms,privacy from pages limit 1')[0];ensure(len(receipt)==1 and receipt[0][0]=='email' and receipt[0][1]==hashlib.sha256((legal[0] or '').encode()).hexdigest() and receipt[0][2]==hashlib.sha256((legal[1] or '').encode()).hexdigest() and receipt[0][3], 'comprovante divergente');s2,h,b=c.req('/dashboard');ensure(s2==200,f'dashboard novo {s2}');dclient=Client();t=dclient.token('/register');d['_token']=t;d['littlelink_name']='h3bduplicate';s3,h,b=dclient.req('/register',d,'application/json');ensure(s3==422 and 'email' in json.loads(b).get('errors',{}),'email duplicado aceito');ensure(counts()==after,'duplicação de conta ou recibo');return {'post':s,'usuario_delta':1,'recibo_delta':1,'metodo':'email','data_e_hashes':'conferidos','role':'user','block':'no','dashboard':s2,'duplicado':s3}
record('TC-04-B','cadastro HTTP com recibo, hashes e duplicação',registration)
def flags():
 p=ROOT/'.env';original=p.read_text()
 try:
  p.write_text(original+'\nAUTO_VIP_NEW_USERS=true\nMANUAL_USER_VERIFICATION=true\n');c=Client();t=c.token('/register');d={'_token':t,'name':'QA Flags','email':'h3b-flags@example.test','littlelink_name':'h3bflags','password':'Synthetic-password-123','accept_terms':'1'};s,h,b=c.req('/register',d);ensure(s==302,f'cadastro flags HTTP {s}');r=query('select id,role,block from users where email=?',(d['email'],));ensure(len(r)==1 and r[0][1:]==('vip','yes'),f'flags não aplicadas: {r}');ensure(len(query('select id from registration_consents where user_id=?',(r[0][0],)))==1,'recibo ausente');s2,h2,b2=c.req('/dashboard');ensure(s2==302 and h2.get('Location','').endswith('/blocked'),f'conta bloqueada dashboard {s2} {h2.get("Location")}');return {'role':'vip','block':'yes','recibos':1,'dashboard':s2,'destino':'/blocked'}
 finally:p.write_text(original)
record('TC-04-B','flags VIP inicial e aprovação manual em ambiente sintético',flags)
def sessions():
 f=Client().login('auditfree');o=Client().login('auditother');a=Client().login('auditadmin');
 for c in [f,o,a]:
  for _ in range(2):s,h,b=c.req('/studio/profile');ensure(s==200,f'perfil reload HTTP {s}')
 before=query('select name from users where id=3')[0][0];f.logout();s,h,b=f.req('/studio/profile');ensure(s==302 and '/login' in h.get('Location',''),'logout não protege');s,h,b=o.req('/studio/profile');ensure(s==200,'logout de uma conta afetou outra');ensure(query('select name from users where id=3')[0][0]==before,'dados de outra conta alterados');s,h,b=a.req('/admin/config');ensure(s==200,'sessão Admin não preservada');return {'tres_contas':'login e reload ok','logout':'área protegida','outra_sessao':'ativa','admin':200}
record('TC-04-D/TC-10-C','sessão, reload, logout e múltiplas contas',sessions)
def reset():
 msg=pathlib.Path('/tmp/linkfree-h3b-private/smtp-message.txt')
 if msg.exists():msg.unlink()
 c=Client();t=c.token('/forgot-password');s,h,b=c.req('/forgot-password',{'_token':t,'email':'auditother@example.test'});ensure(s==302,f'pedido reset {s}');ensure(msg.exists(),'email sintético não capturado; conferir SMTP local');m=email.message_from_bytes(msg.read_bytes());payload=[]
 for part in m.walk():
  if part.get_content_type() in ('text/html','text/plain'):
   raw=part.get_payload(decode=True)
   if raw:payload.append(raw.decode('utf8','replace'))
 text=html.unescape('\n'.join(payload));matches=re.findall(r'http://127\.0\.0\.1:18776/reset-password/([a-zA-Z0-9]+)\?email=([^\s"<>]+)',text);found=next(((t,urllib.parse.unquote(e)) for t,e in matches if urllib.parse.unquote(e)=='auditother@example.test'),None);ensure(bool(found),'link de reset sintético não localizado');token,email_value=found;url='/reset-password/'+token+'?email='+urllib.parse.quote(email_value);csrf=c.token(url);data={'_token':csrf,'token':token,'email':email_value,'password':'New-synthetic-password-456','password_confirmation':'New-synthetic-password-456'};s2,h2,b2=c.req('/reset-password',data);ensure(s2==302 and h2.get('Location','').endswith('/login'),f'redefinição HTTP {s2}');fresh=Client().login('auditother','New-synthetic-password-456');ensure(fresh.req('/studio/profile')[0]==200,'nova senha não autentica');old=Client();t=old.token('/login');s3,h3,b3=old.req('/login',{'_token':t,'email':email_value,'password':'Synthetic-password-123'},'application/json');ensure(s3==422,'senha anterior ainda autentica');return {'smtp':'servidor sintético local','pedido':s,'reset':s2,'nova_senha':'autentica','senha_antiga':s3}
record('TC-04-D','recuperação de senha com SMTP local de captura',reset)
print('TOTAL',len(results),'FALHAS',sum(not x['pass'] for x in results),flush=True)
