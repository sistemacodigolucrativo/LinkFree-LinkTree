#!/usr/bin/env python3
# Escopo sintético: atualiza preferências e reverte dados somente na cópia.
from pathlib import Path
source=Path('/tmp/linkfree-h3a-private/harness.py').read_text().split('admin=Client();')[0];exec(source)
OUT=pathlib.Path('/tmp/linkfree-h3a-private/resultados-extra.json')
admin=Client().login('auditadmin');vip=Client().login('auditvip');original_image=query('select image from users where id=4')[0][0];permpath=ROOT/'storage/app/linkfree/theme-options.json';original_permissions=permpath.read_text() if permpath.exists() else None

def preferences():
 t=admin.token('/admin/theme');s,h,b=admin.req('/admin/theme-options',{'_token':t,'font_family':'1'});ensure(s==302,f'permissão admin HTTP {s}');ensure(json.loads(permpath.read_text())['font_family'] is True,'permissão não persistida');body=page(vip,'/studio/theme');ensure('name="font_family"' in body,'controle de fonte ausente');t=vip.token('/studio/theme');s,h,b=vip.req('/studio/theme-options',{'_token':t,'font_family':'mono'});ensure(s==302,f'POST preferência {s}');image=json.loads(query('select image from users where id=4')[0][0]);ensure(image['theme_options']['font_family']=='mono','preferência não salva');vip.logout();vip.login('auditvip');ensure(json.loads(query('select image from users where id=4')[0][0])['theme_options']['font_family']=='mono','perdeu após nova sessão');body=page(Client(ip='127.0.0.7',ua='Python-urllib/3.12'),'/@auditvip');ensure('id="lf-user-theme-options"' in body and 'monospace' in body,'CSS público não reflete fonte');return {'permissao_admin':True,'preferencia':'mono','nova_sessao':'persistida','publico':'CSS monospace'}
run('TC-03-B','preferência de tema, nova sessão e CSS público',preferences)

def counter_render():
 s,h,b=vip.req('/studio/page');ensure(s==200,f'GET studio/page {s}');f=form_from(b,'/studio/page');ensure('littlelink_name' in f and 'name' in f and 'pageDescription' in f,'form de página incompleto');f.pop('image',None);f['public_visit_count']='1';s,h,b=vip.req('/studio/page',f);ensure(s==302,f'POST contador {s}');im=json.loads(query('select image from users where id=4')[0][0]);ensure(im.get('public_visit_count') is True,'flag contador não persistiu');before=score('auditvip');ensure(before==3,f'baseline esperado 3, obtido {before}');body=page(Client(ip='127.0.0.7',ua='Python-urllib/3.12'),'/@auditvip');ensure(re.search(r'\b3\s+(?:page visits|visitas à página)',html.unescape(body)) is not None,'contador 3 não renderizado');body=page(vip,'/studio/visitors');ensure(re.search(r'\b3\s+(?:page visits|visitas à página)',html.unescape(body)) is not None,'total 3 não está no relatório');s,h,b=vip.req('/studio/visitors?user=3');rows=Table();rows.feed(b);ensure(s==200 and rows.mapping().get('instagram')=='1' and rows.mapping().get('telegram')=='1','filtro externo mudou relatório VIP');return {'contador_público':3,'relatório_total':3,'query_outra_conta':'ignorada, dados próprios'}
run('TC-08-D','contador público, total do relatório e escopo proprietário',counter_render)
# Independent synthetic cleanup even if an assertion fails.
try:
 with sqlite3.connect(DB) as c:c.execute('update users set image=? where id=4',(original_image,));c.commit()
 if original_permissions is None:
  if permpath.exists():permpath.unlink()
 else:permpath.write_text(original_permissions)
 print('CLEANUP: imagem JSON sintética e permissões restauradas',flush=True)
except Exception as e:results.append({'id':'CLEANUP','pass':False,'error':str(e)})
OUT.write_text(json.dumps(results,indent=2,ensure_ascii=False));print('TOTAL',len(results),'FALHAS',sum(not x['pass'] for x in results),flush=True)
