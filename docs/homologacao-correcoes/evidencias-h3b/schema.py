#!/usr/bin/env python3
"""H3-B apenas no staging efêmero, com FK habilitadas por conexão."""
from pathlib import Path
import os,sqlite3,json
root=Path('/tmp/linkfree-h3b-private/app');db=root/'database/database.sqlite';assert os.getuid()==61050 and not Path('/srv/linkfree/artisan').exists();assert 'DB_DATABASE='+str(db) in (root/'.env').read_text()
c=sqlite3.connect(db);c.execute('pragma foreign_keys=on');assert c.execute('pragma foreign_keys').fetchone()[0]==1
r=[]
def check(name,fn):
 try: v=fn();r.append({'id':'TC-01-C/D','assertion':name,'pass':True,'observed':v})
 except Exception as e:r.append({'id':'TC-01-C/D','assertion':name,'pass':False,'error':str(e)})
 print(json.dumps(r[-1],ensure_ascii=False),flush=True)
def assert_(x,msg):
 if not x:raise AssertionError(msg)
def schema():
 expected={'referral_codes':[('user_id',),('code',)],'referrals':[('invited_user_id',)],'verification_requests':[('user_id',)]}
 allfk=0
 for table,required in expected.items():
  indexes=c.execute('pragma index_list('+table+')').fetchall();unique=[]
  for i in indexes:
   if i[2]:unique.append(tuple(x[2] for x in c.execute('pragma index_info("'+i[1]+'")')))
  for cols in required:assert_(cols in unique,table+' faltou único '+str(cols))
 for t in ['referral_codes','referrals','verification_requests','verification_audit_events','abuse_reports','abuse_report_events']:
  fk=c.execute('pragma foreign_key_list('+t+')').fetchall();assert_(len(fk)>0,t+' sem FK');allfk+=len(fk)
 assert_(not c.execute('pragma foreign_key_check').fetchall(),'violações prévias');return {'indices_unicos':4,'tabelas_com_fk':6,'foreign_keys':allfk,'violacoes':0}
check('índices únicos e FKs M13 reais',schema)
def rejects():
 cases=[('referral_codes',"insert into referral_codes(user_id,code) values(999991,'0123456789abcdef01234567')"),('referrals',"insert into referrals(referrer_user_id,invited_user_id,referral_code) values(999991,2,'0123456789abcdef01234567')"),('verification_requests',"insert into verification_requests(user_id,status,requested_at) values(999991,'pending',CURRENT_TIMESTAMP)"),('verification_audit_events',"insert into verification_audit_events(user_id,action,created_at) values(999991,'requested',CURRENT_TIMESTAMP)"),('abuse_reports',"insert into abuse_reports(reported_user_id,reported_url,category,status) values(999991,'https://example.test','other_specify','new')"),('abuse_report_events',"insert into abuse_report_events(abuse_report_id,action,to_status,created_at) values(999991,'created','new',CURRENT_TIMESTAMP)")]
 for label,sql in cases:
  c.execute('savepoint fkcase')
  try:c.execute(sql);raise AssertionError(label+' aceitou órfão')
  except sqlite3.IntegrityError as e:assert_('FOREIGN KEY' in str(e),label+' erro diferente')
  finally:c.execute('rollback to fkcase');c.execute('release fkcase')
 return {'insercoes_orfas_rejeitadas':len(cases)}
check('FK rejeitam órfãos em seis tabelas',rejects)
def uniques():
 c.execute('savepoint unique_case')
 try:
  c.execute("insert into referral_codes(user_id,code) values(2,'111111111111111111111111')")
  c.execute("insert into verification_requests(user_id,status,requested_at) values(3,'pending',CURRENT_TIMESTAMP)")
  cases=["insert into referral_codes(user_id,code) values(2,'222222222222222222222222')","insert into referral_codes(user_id,code) values(3,'111111111111111111111111')","insert into verification_requests(user_id,status,requested_at) values(3,'pending',CURRENT_TIMESTAMP)"]
  for sql in cases:
   try:c.execute(sql);raise AssertionError('duplicata aceita')
   except sqlite3.IntegrityError as e:assert_('UNIQUE' in str(e),'erro não único')
  return {'duplicatas_rejeitadas':len(cases)}
 finally:c.execute('rollback to unique_case');c.execute('release unique_case')
check('restrições únicas executadas em SQLite',uniques)
def rollback():
 before={t:c.execute('select count(*) from '+t).fetchone()[0] for t in ['users','registration_consents','referral_codes']};c.execute('begin');c.execute("insert into users(id,name,email,littlelink_name,role,block) values(90001,'Legacy QA','h3b-legacy@example.test','h3blegacy','user','no')");c.execute("insert into registration_consents(user_id,method,terms_version,privacy_version,accepted_at) values(90001,'email','x','y',CURRENT_TIMESTAMP)");c.execute("insert into referral_codes(user_id,code) values(90001,'333333333333333333333333')");c.rollback();after={t:c.execute('select count(*) from '+t).fetchone()[0] for t in before};assert_(before==after,'rollback perdeu ou manteve dados');assert_(not c.execute('pragma foreign_key_check').fetchall(),'FK após rollback');return {'tabelas_revertidas':3,'contagens_legadas_iguais':True}
check('transação com falha simulada preserva registros legados',rollback)
Path('/tmp/linkfree-h3b-private/schema-resultados.json').write_text(json.dumps(r,indent=2,ensure_ascii=False));c.close();raise SystemExit(1 if any(not x['pass'] for x in r) else 0)
