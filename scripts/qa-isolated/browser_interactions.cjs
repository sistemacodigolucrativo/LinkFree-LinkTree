/* Browser regression tests: only the disposable Laravel fixture. */
'use strict';
const fs = require('node:fs');
const assert = require('node:assert/strict');
const {execFileSync} = require('node:child_process');
const {chromium} = require('/tmp/linkfree-qa-node/node_modules/playwright');
const base = 'http://127.0.0.1:18765';
const root = '/tmp/linkfree-audit/linkstack';
const db = root + '/database/audit.sqlite';

function scalar(sql, args = []) {
  return Number(execFileSync('python3', ['-c',
    'import sqlite3,sys,json; c=sqlite3.connect("file:"+sys.argv[1]+"?mode=ro",uri=True); print(c.execute(sys.argv[2],json.loads(sys.argv[3])).fetchone()[0])',
    db, sql, JSON.stringify(args)], {encoding:'utf8'}).trim());
}
const countLink = id => scalar('SELECT COUNT(*) FROM links WHERE id=?',[id]);

async function run() {
  assert.equal(process.env.QA_EXPECTED_SITE,base);
  const env = fs.readFileSync(root+'/.env','utf8').split(/\r?\n/);
  for (const line of ['APP_ENV=local','DB_CONNECTION=sqlite','DB_DATABASE='+db,'MAIL_MAILER=log','APP_URL='+base]) assert(env.includes(line), 'Isolated environment guard failed');
  assert.equal(fs.realpathSync(db),db);
  assert.equal(scalar("SELECT COUNT(*) FROM users WHERE email LIKE '%@example.test'"),4);
  assert.equal(scalar('SELECT COUNT(*) FROM users'),4);
  assert.equal(scalar('SELECT COUNT(*) FROM links'),5);
  const results=[];
  const browser=await chromium.launch({headless:true});
  async function check(id, name, fn) {
    try { const observed=await fn(); results.push({id,name,status:'PASS',observed}); }
    catch(e) { results.push({id,name,status:'FAIL',error:String(e.message).slice(0,2000)}); }
  }
  async function session(name) {
    const context=await browser.newContext({viewport:{width:1280,height:900},reducedMotion:'reduce',serviceWorkers:'block'});
    await context.route('**/*',route=>{
      try {if(new URL(route.request().url()).origin===base)return route.continue();}catch(_){}
      return route.abort('blockedbyclient');
    });
    const page=await context.newPage();
    try {
      await page.goto(base+'/login',{waitUntil:'domcontentloaded'});
      const form=page.locator('form[action$="/login"]').first();
      await form.locator('[name=email]').fill(name+'@example.test');
      await form.locator('[name=password]').fill('Synthetic-password-123');
      await Promise.all([page.waitForURL('**/dashboard',{timeout:20000}),form.locator('button[type=submit]').click()]);
      return {context,page};
    } catch(e) { await context.close(); throw e; }
  }
  async function dialogClick(page,button,accept) {
    await page.waitForLoadState('load',{timeout:30000});
    await button.scrollIntoViewIfNeeded({timeout:20000});
    try {
      await button.click({trial:true,timeout:20000});
    } catch (e) {
      const hit=await button.evaluate(el=>{
        const r=el.getBoundingClientRect(),target=document.elementFromPoint(r.x+r.width/2,r.y+r.height/2);
        return {target:target?.tagName,className:String(target?.className||'').slice(0,150),own:el.contains(target),rect:{x:r.x,y:r.y,w:r.width,h:r.height}};
      }).catch(()=>null);
      throw new Error('Real click actionability failed '+JSON.stringify(hit)+' '+String(e.message).slice(0,1600));
    }
    await Promise.all([
      page.waitForEvent('dialog',{timeout:20000}).then(d=>accept?d.accept():d.dismiss()),
      button.click({timeout:20000})
    ]);
  }
  try {
    await check('TC-04-D','Member UI cancel, confirm, reload and account isolation',async()=>{
      const {context,page}=await session('auditfree');
      try {
        await page.goto(base+'/studio/links',{waitUntil:'domcontentloaded'});
        const button=page.locator('form[action$="/deleteLink/101"] button[data-delete-link]');
        await button.waitFor({state:'visible'});
        let requests=0;
        page.on('request',req=>{if(new URL(req.url()).pathname==='/deleteLink/101' && req.method()!=='GET')requests++;});
        await dialogClick(page,button,false);
        assert.equal(countLink(101),1,'Cancel changed the database');
        await page.reload({waitUntil:'domcontentloaded'});
        await button.waitFor({state:'visible'});
        assert.equal(requests,0,'Cancel sent a mutation');
        const response=page.waitForResponse(res=>new URL(res.url()).pathname==='/deleteLink/101'&&res.request().method()==='DELETE',{timeout:20000});
        await dialogClick(page,button,true);
        assert.equal((await response).status(),200);
        await page.locator('#link-delete-feedback').filter({hasText:'Link excluído e alteração salva.'}).waitFor({state:'visible'});
        await button.waitFor({state:'detached'});
        await page.reload({waitUntil:'domcontentloaded'});
        assert.equal(await button.count(),0);
        assert.equal(countLink(101),0);
        assert.equal(countLink(104),1);
        assert.equal(countLink(102),1);
        return {cancelNoMutation:true,delete200:true,feedback:true,reloadPersisted:true,otherLinksIntact:true};
      } finally {await context.close();}
    });
    await check('TC-04-D','Admin UI cancel, confirm and reload',async()=>{
      const {context,page}=await session('auditadmin');
      try {
        await page.goto(base+'/admin/links/4',{waitUntil:'domcontentloaded'});
        const form=page.locator('form[action$="/admin/deleteLink/103"]');
        const button=form.locator('button[type=submit]');
        await button.waitFor({state:'visible'});
        let requests=0;
        page.on('request',req=>{if(new URL(req.url()).pathname==='/admin/deleteLink/103' && req.method()!=='GET')requests++;});
        await dialogClick(page,button,false);
        assert.equal(countLink(103),1);
        await page.reload({waitUntil:'domcontentloaded'});
        assert.equal(requests,0);
        const response=page.waitForResponse(res=>new URL(res.url()).pathname==='/admin/deleteLink/103'&&res.request().method()==='POST',{timeout:20000});
        await dialogClick(page,button,true);
        const res=await response;
        assert.equal(res.status(),302);
        assert(new URLSearchParams(res.request().postData()||'').get('_method')==='DELETE');
        await page.goto(base+'/admin/links/4',{waitUntil:'domcontentloaded'});
        assert.equal(await form.count(),0);
        assert.equal(countLink(103),0);
        assert.equal(countLink(102),1);
        return {cancelNoMutation:true,postDeleteOverride:true,redirect302:true,reloadPersisted:true,otherAccountIntact:true};
      } finally {await context.close();}
    });
    await check('TC-04-D','Every admin creation shortcut opens read-only form; only Salvar creates',async()=>{
      const {context,page}=await session('auditadmin');
      try {
        for (const path of ['/admin/overview','/admin/users']) {
          await page.goto(base+path,{waitUntil:'domcontentloaded'});
          assert((await page.locator('a[href$="/admin/new-user"]').count())>=1,'Missing GET shortcut: '+path);
          assert.equal(await page.locator('form[action$="/admin/new-user"]').count(),0,
            'Navigation must not submit a POST: '+path);
        }
        const before=scalar('SELECT COUNT(*) FROM users');
        await page.goto(base+'/admin/new-user',{waitUntil:'domcontentloaded'});
        assert.equal(scalar('SELECT COUNT(*) FROM users'),before);
        await page.reload({waitUntil:'domcontentloaded'});
        assert.equal(scalar('SELECT COUNT(*) FROM users'),before);
        const form=page.locator('#lf-admin-create-user');
        assert.equal(await form.getAttribute('method'),'POST');
        assert.equal(await form.locator('input[name="_token"]').count(),1);
        await form.locator('[name=name]').fill('Admin-Created-Browser');
        await form.locator('[name=email]').fill('admin-created-browser@example.test');
        await form.locator('[name=password]').fill('Synthetic-password-789');
        assert.equal(scalar('SELECT COUNT(*) FROM users'),before,
          'Typing into the form must not persist anything');
        const response=page.waitForResponse(res=>new URL(res.url()).pathname==='/admin/new-user'&&res.request().method()==='POST',{timeout:20000});
        await form.locator('button[type=submit]').click({timeout:10000});
        assert.equal((await response).status(),302);
        await page.waitForURL('**/admin/edit-user/*',{timeout:20000});
        assert.equal(scalar('SELECT COUNT(*) FROM users'),before+1);
        return {getReadOnly:true,fillReadOnly:true,post302:true,editorReached:true,createdExactlyOne:true};
      } finally {await context.close();}
    });
    await check('TC-04-D','Impersonation browser start, member access and return',async()=>{
      const {context,page}=await session('auditadmin');
      try {
        await page.goto(base+'/admin/users',{waitUntil:'load'});
        const button=page.locator('form[action$="/auth-as/2"] button[type=submit]');
        await button.waitFor({state:'visible'});
        await Promise.all([page.waitForURL('**/dashboard',{timeout:20000}),button.click({timeout:20000})]);
        await page.goto(base+'/studio/links',{waitUntil:'load'});
        const bar=page.locator('.lf-impersonation-bar');
        await bar.waitFor({state:'visible',timeout:15000});
        assert.equal(await bar.locator('a').innerText(),'auditfree');
        assert.equal(await page.locator('#links-table-body [data-id="104"]').count(),1);
        assert.equal(await page.locator('#links-table-body [data-id="105"]').count(),0);
        const returnButton=bar.locator('form[action$="/auth-as"] button[type=submit]');
        await Promise.all([page.waitForURL('**/admin/users',{timeout:20000}),returnButton.click({timeout:20000})]);
        assert.equal(await page.locator('.lf-impersonation-bar').count(),0);
        await page.locator('a[href$="/admin/new-user"]').first().waitFor({state:'attached'});
        return {memberOnly:true,returnButton:true,adminRestored:true};
      } finally {await context.close();}
    });
    await check('TC-04-D','Page icon browser cancel and confirm',async()=>{
      execFileSync('python3',['-c',"import sqlite3,sys; c=sqlite3.connect(sys.argv[1]); c.execute(\"INSERT INTO buttons(id,name) VALUES(94,'icon')\"); c.execute(\"INSERT INTO links(id,user_id,button_id,link,title) VALUES(106,2,94,'https://example.test/icon','facebook')\"); c.commit()",db]);
      const {context,page}=await session('auditfree');
      try {
        await page.goto(base+'/studio/page-icons',{waitUntil:'load'});
        const button=page.locator('button[formaction$="/deleteLink/106"]');
        await dialogClick(page,button,false);
        assert.equal(countLink(106),1);
        const response=page.waitForResponse(res=>new URL(res.url()).pathname==='/deleteLink/106'&&res.request().method()==='POST',{timeout:20000});
        await dialogClick(page,button,true);
        const res=await response;
        assert.equal(res.status(),302);
        await page.goto(base+'/studio/page-icons',{waitUntil:'load'});
        assert.equal(await button.count(),0);
        assert.equal(countLink(106),0);
        return {cancelPreserves:true,confirmed:true,reloadPersisted:true};
      } finally {await context.close();}
    });
    await check('TC-04-D','Admin account deletion browser cancel and confirm',async()=>{
      const {context,page}=await session('auditadmin');
      try {
        await page.goto(base+'/admin/users',{waitUntil:'load'});
        const button=page.locator('button.confirmation[data-id="4"]');
        await dialogClick(page,button,false);
        assert.equal(scalar('SELECT COUNT(*) FROM users WHERE id=4'),1);
        const response=page.waitForResponse(res=>new URL(res.url()).pathname==='/admin/delete-table-user/4'&&res.request().method()==='POST',{timeout:20000});
        await dialogClick(page,button,true);
        assert.equal((await response).status(),200);
        await page.reload({waitUntil:'load'});
        assert.equal(await button.count(),0);
        assert.equal(scalar('SELECT COUNT(*) FROM users WHERE id=4'),0);
        assert.equal(scalar('SELECT COUNT(*) FROM users WHERE id=1'),1);
        return {cancelPreserves:true,confirmed:true,reloadPersisted:true,rootPreserved:true};
      } finally {await context.close();}
    });
    await check('ADMIN-NAV','All 17 administrative destinations and responsive sidebar',async()=>{
      const {context,page}=await session('auditadmin');
      try {
        const paths=['/admin/overview','/admin/users','/admin/new-user','/admin/referrals','/admin/verification','/admin/reports','/admin/site','/admin/pages','/admin/theme','/admin/github-themes','/admin/linktype','/admin/config','/admin/backups','/admin/advanced-config','/admin/env','/admin/phpinfo','/update'];
        for(const width of [1280,768,375]){
          await page.setViewportSize({width,height:900});
          for(const path of paths){
            const res=await page.goto(base+path,{waitUntil:'domcontentloaded'});
            assert.equal(res.status(),200,path+' HTTP '+res.status());
            assert.equal(new URL(page.url()).pathname,path,'Unexpected destination '+path);
            assert.equal(await page.locator('[data-panel-context="admin"]').count(),1,path+' panel context');
            if(path==='/admin/phpinfo'){
              assert.equal(await page.locator('#php-diagnostics').count(),1,'Safe PHP diagnostic content');
              assert.equal(await page.locator('iframe[title="Informações técnicas do PHP"]').count(),0,'Raw phpinfo must not be rendered');
              assert.equal(await page.locator('#download-php-diagnostics').count(),1,'Diagnostic download preserved');
              const secret=fs.readFileSync(root+'/.env','utf8').split(/\r?\n/).find(v=>v.startsWith('APP_KEY='));
              if(secret) assert(!(await page.content()).includes(secret),'APP_KEY leaked into PHP diagnostic page');
            }
            if(path==='/admin/env') assert.equal(await page.locator('h2').filter({hasText:'Variáveis de ambiente'}).count(),1,'Environment editor heading');
            if(path==='/admin/advanced-config') assert(await page.locator('h2').count()>=1,'Advanced editor heading');
            if(path==='/admin/new-user') assert.equal(await page.locator('form[action$="/admin/new-user"][method="POST"]').count(),1,'Direct user-creation form');
            if(path==='/update') assert(await page.locator('h1').filter({hasText:'Atualização da instalação'}).count()===1,'Maintenance page content');
            const leaf=page.locator('#sidebar-menu a.nav-link.active:not(.nav-accordion-toggle)');
            assert.equal(await leaf.count(),1,path+' must have exactly one active destination');
            assert.equal(new URL(await leaf.getAttribute('href'),base).pathname,path,path+' active link points elsewhere');
            const submenu=leaf.locator('xpath=ancestor::ul[contains(concat(" ", normalize-space(@class), " "), " sub-nav ")]');
            if(await submenu.count()){
              assert((await submenu.getAttribute('class')).includes('show'),path+' parent submenu is collapsed');
              const menuId=await submenu.getAttribute('id');
              assert.equal(await page.locator('#sidebar-menu a[aria-controls="'+menuId+'"]').getAttribute('aria-expanded'),'true',path+' accordion indicator');
            }
            assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+2),path+' horizontal overflow at '+width);
          }
        }
        return {destinations:paths.length,viewports:3};
      } finally {await context.close();}
    });
    await check('ADMIN-ACCORDIONS','Administrative category accordions expand and collapse on desktop and mobile',async()=>{
      const {context,page}=await session('auditadmin');
      try {
        await page.goto(base+'/admin/overview',{waitUntil:'load'});
        for(const id of ['admin-users-menu','admin-site-menu','admin-themes-menu','admin-system-menu']){
          const toggle=page.locator('#sidebar-menu a[aria-controls="'+id+'"]');
          const submenu=page.locator('#'+id);
          assert.equal(await toggle.getAttribute('aria-expanded'),'false',id+' expected collapsed');
          await toggle.click();
          await page.waitForFunction(menuId=>{
            const el=document.getElementById(menuId);
            return el.classList.contains('show')&&!el.classList.contains('collapsing');
          },id,{timeout:10000});
          assert.equal(await toggle.getAttribute('aria-expanded'),'true',id+' did not expand');
          await toggle.click();
          await page.waitForFunction(menuId=>{
            const el=document.getElementById(menuId);
            return !el.classList.contains('show')&&!el.classList.contains('collapsing');
          },id,{timeout:10000});
          assert.equal(await toggle.getAttribute('aria-expanded'),'false',id+' did not collapse');
        }
        await page.setViewportSize({width:375,height:900});
        await page.goto(base+'/admin/overview',{waitUntil:'load'});
        const sidebar=page.locator('.sidebar-default');
        const toggle=page.locator('.main-content [data-toggle="sidebar"]').first();
        assert.equal(await toggle.count(),1,'Mobile sidebar toggle missing');
        if((await sidebar.getAttribute('class')).includes('sidebar-mini')){
          await toggle.click();
          await page.waitForFunction(()=>!document.querySelector('.sidebar-default').classList.contains('sidebar-mini'));
        }
        const category=page.locator('#sidebar-menu a[aria-controls="admin-users-menu"]');
        await category.click();
        assert.equal(await category.getAttribute('aria-expanded'),'true','Mobile submenu not expanded');
        await page.keyboard.press('Escape');
        await page.waitForFunction(()=>document.querySelector('.sidebar-default').classList.contains('sidebar-mini'));
        return {desktopAccordions:4,mobileExpanded:true,escapeCloses:true};
      } finally {await context.close();}
    });
    await check('ADMIN-RBAC','Admin-only destinations reject member and anonymous clients',async()=>{
      const routes=['/admin/overview','/admin/users','/admin/new-user','/admin/referrals','/admin/verification','/admin/reports','/admin/site','/admin/pages','/admin/theme','/admin/github-themes','/admin/linktype','/admin/config','/admin/backups','/admin/advanced-config','/admin/env','/admin/phpinfo','/update'];
      const {context:member}=await session('auditfree');
      const guest=await browser.newContext();
      try {
        for(const path of routes){
          const memberResponse=await member.request.get(base+path,{maxRedirects:0});
          assert([302,401,403].includes(memberResponse.status()),path+' unauthorized member HTTP '+memberResponse.status());
          const guestResponse=await guest.request.get(base+path,{maxRedirects:0});
          assert([302,401,403].includes(guestResponse.status()),path+' anonymous HTTP '+guestResponse.status());
        }
        return {destinations:routes.length,memberDenied:true,anonymousDenied:true};
      } finally {await member.close();await guest.close();}
    });
    await check('ADMIN-ENV','Allowed environment edit preserves all other entries',async()=>{
      const {context,page}=await session('auditadmin');
      const file=root+'/.env';
      const before=fs.readFileSync(file,'utf8');
      try {
        await page.goto(base+'/admin/env',{waitUntil:'domcontentloaded'});
        const form=page.locator('form[action$="/admin/env"]');
        assert.equal(await form.count(),1);
        const name=form.locator('[name="APP_NAME"]');
        await name.fill('Audit Safe Name');
        assert.equal(await form.locator('[name="APP_KEY"],[name="DB_PASSWORD"],[name="altConfig"]').count(),0);
        await Promise.all([page.waitForURL('**/admin/env'),form.locator('button[type="submit"]').click()]);
        const after=fs.readFileSync(file,'utf8');
        assert(after.includes('Audit Safe Name'),'Allowed value not saved');
        const protectedLines=v=>v.split(/\r?\n/).filter(l=>!/^\s*(APP_NAME|APP_LOCALE|LOG_LEVEL)\s*=/.test(l)).filter(l=>l.trim()).sort();
        assert.deepEqual(protectedLines(after),protectedLines(before),'Protected environment entries changed');
        return {allowedWrite:true,protectedEntriesPreserved:true};
      } finally {fs.writeFileSync(file,before);await context.close();}
    });
  } finally {await browser.close();}
  return {status:results.every(x=>x.status==='PASS')?'PASS':'FAIL',sourceSha:process.env.GITHUB_SHA||'',results};
}
module.exports={run};
