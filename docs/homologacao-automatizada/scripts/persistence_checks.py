from pathlib import Path
p=Path(__file__).with_name('http_checks.py');exec(p.read_text().split('results=[]')[0])
results=[]
def record(id,name,fn):
 try:obs=fn();r={'id':id,'assertion':name,'pass':True,'observed':obs}
 except Exception as e:r={'id':id,'assertion':name,'pass':False,'error':str(e)}
 results.append(r);print(r,flush=True)
def persistence():
 c=Client().login('auditadmin');t=c.token('/admin/site');s,h,b=c.request('/admin/site/content',{'_token':t,'section':'navigation','nav_features':'Synthetic persistent menu'});assert s==302
 c=Client().login('auditadmin');s,h,b=c.request('/admin/site');assert 'Synthetic persistent menu' in b
 s,h,b=Client().request('/');assert s==200 and 'Synthetic persistent menu' in b
 return 'admin save, new login, reload and public content match'
record('TC-03-B','site text persists across independent login and public render',persistence)
def counter():
 with sqlite3.connect(DB) as d:
  d.execute("insert into users(name,email,littlelink_name) values('Counter','auditcounter@example.test','auditcounter')");id=d.execute("select id from users where littlelink_name='auditcounter'").fetchone()[0]
 c=Client()
 scores=[]
 for i in range(2):
  s,h,b=c.request('/@auditcounter?utm_source=instagram');assert s==200
  with sqlite3.connect(DB) as d:scores.append(d.execute("select score from visits where primary_key='visits:users_auditcounter_total'").fetchone()[0])
 assert scores==[1,1],scores
 stored=json.loads((ROOT/f'linkstack/storage/app/linkfree/visit-sources/{id}.json').read_text());assert sum(x.get('instagram',0) for x in stored.values())==1
 assert Client().login('auditfree').request('/studio/visitors')[0]==403
 assert Client().login('auditvip').request('/studio/visitors')[0]==200
 return {'two_page_requests_counter':scores,'instagram_attributions':1,'free_status':403,'vip_status':200}
record('TC-08-D','HTTP dedup counter origin attribution and permissions',counter)
(ROOT/'docs/homologacao-automatizada/evidencias/persistence.json').write_text(json.dumps(results,indent=2))
