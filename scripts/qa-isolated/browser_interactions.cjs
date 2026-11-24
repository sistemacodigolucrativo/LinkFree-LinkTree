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
    catch(e) { results.push({id,name,status:'FAIL',error:String(e.message).slice(0,300)}); }
  }
  async function session(name) {
    const context=await browser.newContext({viewport:{width:1280,height:900},serviceWorkers:'block'});
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
    await Promise.all([
      page.waitForEvent('dialog',{timeout:10000}).then(d=>accept?d.accept():d.dismiss()),
      button.click({timeout:10000})
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
    await check('TC-04-D','Admin creation controls render POST forms and create exactly one user from the users page',async()=>{
      const {context,page}=await session('auditadmin');
      try {
        for (const path of ['/admin/overview','/admin/users']) {
          await page.goto(base+path,{waitUntil:'domcontentloaded'});
          const forms=page.locator('form[action$="/admin/new-user"]');
          assert((await forms.count())>=2,'Missing creation controls: '+path);
          for (const form of await forms.all()) {
            assert.equal(await form.getAttribute('method'),'POST');
            assert.equal(await form.locator('input[name="_token"]').count(),1);
            assert.equal(await form.locator('button[type="submit"]').count(),1);
          }
        }
        const before=scalar('SELECT COUNT(*) FROM users');
        const button=page.locator('form[action$="/admin/new-user"] button[type="submit"]').filter({hasText:'+'}).first();
        const response=page.waitForResponse(res=>new URL(res.url()).pathname==='/admin/new-user'&&res.request().method()==='POST',{timeout:20000});
        await button.click({timeout:10000});
        assert.equal((await response).status(),302);
        await page.waitForURL('**/admin/edit-user/*',{timeout:20000});
        assert.equal(scalar('SELECT COUNT(*) FROM users'),before+1);
        return {formsWithCsrf:true,post302:true,editorReached:true,createdExactlyOne:true};
      } finally {await context.close();}
    });
  } finally {await browser.close();}
  return {status:results.every(x=>x.status==='PASS')?'PASS':'FAIL',sourceSha:process.env.GITHUB_SHA||'',results};
}
module.exports={run};
