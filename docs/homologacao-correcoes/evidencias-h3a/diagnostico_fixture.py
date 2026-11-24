#!/usr/bin/env python3
# Reprodução da fixture histórica exclusivamente no namespace sintético.
from pathlib import Path
import os,sqlite3,urllib.request,urllib.error,http.cookiejar,json,subprocess
ROOT=Path('/tmp/linkfree-h3a-private/app');DB=ROOT/'database/database.sqlite';assert os.getuid()==61050 and not Path('/srv/linkfree/artisan').exists();assert 'DB_DATABASE='+str(DB) in (ROOT/'.env').read_text()
with sqlite3.connect(DB) as c:
 c.execute("insert into users(id,name,email,littlelink_name,role,block) values(90,'Historical Fixture','historical90@example.test','auditcounter90','user','no')");c.commit()
url='http://127.0.0.1:18775/@auditcounter90?utm_source=instagram';
with urllib.request.urlopen(url,timeout=10) as r:status=r.status
with sqlite3.connect('file:'+str(DB)+'?mode=ro',uri=True) as c:row=c.execute("select score from visits where primary_key='visits:users_auditcounter90_total' and secondary_key is null").fetchone()
assert status==200 and row is None
result={'id':'TC-08-D','http_historico':status,'user_agent':'Python-urllib/3.12 (padrão urllib)','linha_total_presente':False,'resultado_historico':'fetchone() retorna None; índice [0] gera TypeError','conclusao':'fixture usou agente ignorado como crawler; linha ausente é pré-condição não satisfeita'}
Path('/tmp/linkfree-h3a-private/diagnostico-fixture.json').write_text(json.dumps(result,indent=2,ensure_ascii=False));print(json.dumps(result,ensure_ascii=False))
