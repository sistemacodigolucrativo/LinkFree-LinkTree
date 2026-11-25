/* Scoped Chromium smoke for admin configuration on isolated synthetic Laravel only. */
'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const {chromium} = require('/tmp/linkfree-qa-node/node_modules/playwright');
const base = 'http://127.0.0.1:18765';
const output = '/tmp/linkfree-audit/config-browser-results.json';
const root = '/tmp/linkfree-audit/linkstack';
const sections = [
  'application', 'panel', 'appearance', 'security', 'advanced',
  'smtp', 'footer', 'debug', 'language', 'diagnostics'
];
const results = [];
const screenshots = '/tmp/linkfree-audit/config-screenshots';
fs.mkdirSync(screenshots, {recursive:true});
function log(text) { console.log(new Date().toISOString(), text); }
function snapshot() {
  fs.writeFileSync(output, JSON.stringify({
    scope: 'synthetic localhost config browser only; no production URLs',
    sourceSha: process.env.GITHUB_SHA || '',
    results, totals: {pass:results.filter(v=>v.status==='PASS').length, fail:results.filter(v=>v.status==='FAIL').length}
  },null,2));
}
async function check(name, task) {
  const start=Date.now(); log('START '+name);
  try { const observed=await task(); results.push({name,status:'PASS',observed,durationMs:Date.now()-start}); log('PASS '+name); }
  catch(e) { results.push({name,status:'FAIL',error:String(e.stack||e.message).slice(0,1600),durationMs:Date.now()-start}); log('FAIL '+name+' '+String(e.message).slice(0,250)); }
  snapshot();
}
async function login(page) {
  await page.goto(base+'/login',{waitUntil:'domcontentloaded',timeout:25000});
  const form=page.locator('form[action$="/login"]').first();
  await form.locator('[name=email]').fill('auditadmin@example.test');
  await form.locator('[name=password]').fill('Synthetic-password-123');
  await Promise.all([
    page.waitForURL('**/dashboard',{timeout:20000}),
    form.locator('button[type=submit]').click({timeout:15000})
  ]);
}
async function makeContext(browser, width, mode) {
  const context=await browser.newContext({
    viewport:{width,height:width<500?844:900},
    reducedMotion:'reduce', serviceWorkers:'block'
  });
  await context.route('**/*',route=>{
    try {if(new URL(route.request().url()).origin===base)return route.continue();}
    catch(_){}
    return route.abort('blockedbyclient');
  });
  await context.addInitScript(mode=>{
    try {localStorage.setItem('color-mode',mode);} catch(_){}
  },mode);
  return context;
}
async function show(page, url) {
  const response=await page.goto(base+url,{waitUntil:'domcontentloaded',timeout:25000});
  assert.equal(response?.status(),200,'GET '+url+' HTTP '+response?.status());
  assert.equal(new URL(page.url()).pathname,url,'Incorrect route after navigation '+url);
  await page.locator('h1').first().waitFor({state:'visible',timeout:10000});
}
async function layout(page, mode) {
  const state=await page.evaluate(()=>{
    const doc=document.documentElement, body=document.body;
    return {
      viewport:innerWidth,
      documentWidth:doc.scrollWidth,
      bodyWidth:body.scrollWidth,
      htmlBg:getComputedStyle(doc).backgroundColor,
      bodyBg:getComputedStyle(body).backgroundColor,
      prepaintDark:doc.classList.contains('lf-prepaint-dark'),
      bodyDark:body.classList.contains('dark')
    };
  });
  assert(state.documentWidth<=state.viewport+3,
         'Unexpected horizontal overflow '+JSON.stringify(state));
  if(mode==='dark'){
    assert(state.prepaintDark,'Dark theme was not applied before paint');
    assert(state.bodyDark,'Dark class missing on body');
    assert(!['rgb(255, 255, 255)','rgba(0, 0, 0, 0)'].includes(state.htmlBg),
           'Dark page has white/transparent root background '+JSON.stringify(state));
  }
  return state;
}
async function run() {
  assert.equal(process.env.QA_EXPECTED_SITE,base,'Explicit localhost origin required');
  assert(fs.realpathSync(root).startsWith('/tmp/linkfree-audit/'),'Fixture outside disposable root');
  const env=fs.readFileSync(root+'/.env','utf8');
  for(const needle of ['APP_ENV=local','DB_CONNECTION=sqlite','MAIL_MAILER=log','DB_DATABASE=/tmp/linkfree-audit/linkstack/database/audit.sqlite'])
    assert(env.includes(needle),'Synthetic fixture guard missing: '+needle);
  const browser=await chromium.launch({headless:true});
  try {
    for(const [viewport,mode] of [[1280,'light'],[375,'dark']]){
        const context=await makeContext(browser,viewport,mode);
        const page=await context.newPage();
        page.setDefaultTimeout(12000);
        try {
          await check('Login and index '+viewport+' '+mode, async()=>{
            await login(page);
            await show(page,'/admin/config');
            const cards=page.locator('a[href*="/admin/config/sections/"]');
            assert.equal(await cards.count(),10,'Not all ten module links visible');
            assert.equal(await page.locator('h1').filter({hasText:'Configurações administrativas'}).count(),1);
            const observed=await layout(page,mode);
            await page.screenshot({path:screenshots+'/index-'+viewport+'-'+mode+'.png',fullPage:true,timeout:12000});
            const link=page.locator('a[href$="/admin/config/sections/application"]').first();
            await link.click({timeout:12000});
            assert.equal(new URL(page.url()).pathname,'/admin/config/sections/application');
            await page.locator('a[href$="/admin/config"]').filter({hasText:'Voltar às configurações'}).first().click({timeout:12000});
            assert.equal(new URL(page.url()).pathname,'/admin/config');
            return observed;
          });
          for(const section of sections){
            await check('Render '+section+' '+viewport+' '+mode,async()=>{
              await show(page,'/admin/config/sections/'+section);
              const title=page.locator('h1').first();
              assert((await title.innerText()).trim().length>1,'Missing page title');
              assert.equal(await page.locator('a[href$="/admin/config"]').filter({hasText:'Voltar às configurações'}).count(),1);
              if(section!=='diagnostics'){
                assert((await page.locator('form[action$="/admin/config"]').count())>0,
                        'No existing configuration forms in '+section);
              }
              return await layout(page,mode);
            });
          }
        } finally { await context.close(); }
    }
    await check('Administrator form saves and persists via real Chromium click',async()=>{
      const context=await makeContext(browser,1280,'light');
      try {
        const page=await context.newPage();
        await login(page);
        await show(page,'/admin/config/sections/application');
        const toggle=page.locator('#ALLOW_REGISTRATION');
        await toggle.waitFor({state:'visible'});
        assert(await toggle.isChecked(),'Synthetic registration flag was not initially enabled');
        // URL is already the target before submit; waitForURL can resolve immediately.
        // Instead require the actual POST response and verify persistence from a NEW page.
        const postResponse=page.waitForResponse(
          res=>new URL(res.url()).pathname==='/admin/config' && res.request().method()==='POST',
          {timeout:20000});
        await toggle.uncheck({timeout:15000});
        const saved=await postResponse;
        assert.equal(saved.status(),302,'Config POST did not return a redirect');
        const verification=await context.newPage();
        try {
          await show(verification,'/admin/config/sections/application');
          const persisted=verification.locator('#ALLOW_REGISTRATION');
          await persisted.waitFor({state:'visible'});
          assert.equal(await persisted.isChecked(),false,'Saved toggle did not survive a fresh GET');
        } finally {await verification.close();}
        return {POST:true,status:302,persistedAfterNewGET:true,disposableFixture:true};
      } finally { await context.close(); }
    });
    await check('Guest cannot view config section',async()=>{
      const guest=await browser.newContext();
      try {
        const response=await guest.request.get(base+'/admin/config/sections/security',{maxRedirects:0});
        assert([302,401,403].includes(response.status()),'Guest allowed: '+response.status());
        return {status:response.status()};
      } finally {await guest.close();}
    });
  } finally {await browser.close(); snapshot();}
  const failed=results.filter(x=>x.status==='FAIL');
  console.log('CONFIG BROWSER RESULT: '+(results.length-failed.length)+' passed, '+failed.length+' failed; '+output);
  if(failed.length)process.exitCode=1;
}
run().catch(e=>{ console.error('CONFIG BROWSER FATAL',e);results.push({name:'harness',status:'FAIL',error:String(e.stack||e)});snapshot();process.exitCode=1;});
