#!/usr/bin/env python3
# Simula o relógio apenas pela expiração de uma linha de fixture sintética.
from pathlib import Path
source=Path('/tmp/linkfree-h3a-private/harness.py').read_text().split('admin=Client();')[0];exec(source)
OUT=pathlib.Path('/tmp/linkfree-h3a-private/resultados-expiracao.json')
def expiry():
 baseline=score('auditvip');ensure(baseline==3,f'baseline inesperado {baseline}');before=jsonsource(4);key='visits:users_auditvip_recorded_ips:127.0.0.2'
 with sqlite3.connect(DB) as c:
  rows=c.execute('select expired_at from visits where primary_key=?',(key,)).fetchall();ensure(len(rows)==1 and rows[0][0] is not None,'registro IP ou prazo ausente');c.execute("update visits set expired_at='2000-01-01 00:00:00' where primary_key=?",(key,));c.commit()
 v=Client(ip='127.0.0.2');s,h,b=v.req('/@auditvip?utm_source=instagram');ensure(s==200,f'perfil HTTP {s}');count=score('auditvip');after=jsonsource(4);ensure(count==4,f'contador após expiração simulada: {count}');ensure(after.get('instagram',0)==before.get('instagram',0)+1,'agregado não incrementou após expiração');s,h,b=v.req('/@auditvip?utm_source=instagram');ensure(s==200 and score('auditvip')==4,'novo intervalo não deduplicou');return {'tempo':'expiração da linha simulada, sem aguardar 900 s reais','baseline':baseline,'visita_após_expiração':count,'nova_repetição':score('auditvip'),'instagram_delta':1}
run('TC-08-D','nova visita após expiração simulada da janela',expiry)
