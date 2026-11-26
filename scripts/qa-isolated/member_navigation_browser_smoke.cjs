/* Authenticated member UX acceptance against a disposable Laravel + SQLite fixture.
 * The site and accounts are synthetic; external requests are blocked.
 * Broad site-wide axe analysis stays in the separately dispatched workflow.
 */
'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require('/tmp/linkfree-qa-node/node_modules/playwright');
const {AxeBuilder} = require('/tmp/linkfree-qa-node/node_modules/@axe-core/playwright');
const base = 'http://127.0.0.1:18765';
const dir = '/tmp/linkfree-audit';
const reportPath = dir + '/member-navigation-results.json';
const screenshots = dir + '/member-navigation-screenshots';
const result = {origin:base, fixture:'ephemeral SQLite / synthetic member only',
  sourceSha:process.env.GITHUB_SHA || '',checks:[]};
const pages = [
  ['/dashboard','Painel de Controle'],
  ['/studio/links','Links e Blocos'],
  ['/studio/add-link','Adicionar Bloco'],
  ['/studio/page-icons','Ícones da Página'],
  ['/studio/blocks','Gerenciar Blocos'],
  ['/studio/theme','Fundos Personalizados'],
  ['/studio/referrals','Minhas Indicações'],
  ['/studio/verification','Selo Verificado'],
  ['/studio/page','Meu Perfil'],
  ['/studio/profile','Informações da Conta'],
];
function save() {
  result.summary = {passed:result.checks.filter(x=>x.status==='PASS').length,
    failed:result.checks.filter(x=>x.status==='FAIL').length};
  fs.writeFileSync(reportPath,JSON.stringify(result,null,2));
}
async function check(label, fn) {
  const started = Date.now();
  try {
    const evidence = await fn();
    result.checks.push({label,status:'PASS',evidence,durationMs:Date.now()-started});
    console.log('PASS',label);
  } catch (error) {
    result.checks.push({label,status:'FAIL',
      error:String(error.stack||error).slice(0,2500),durationMs:Date.now()-started});
    console.error('FAIL',label,String(error.message||error));
  }
  save();
}
async function login(page, user='auditfree') {
  await page.goto(base+'/login',{waitUntil:'domcontentloaded',timeout:20000});
  const form=page.locator('form[action$="/login"]').first();
  await form.locator('[name=email]').fill(user+'@example.test');
  await form.locator('[name=password]').fill('Synthetic-password-123');
  await Promise.all([
    page.waitForURL('**/dashboard',{timeout:20000}),
    form.locator('button[type=submit]').click()
  ]);
}
async function verifyTopbar(page, route, label, width) {
  const response=await page.goto(base+route,{waitUntil:'domcontentloaded',timeout:25000});
  assert.equal(response?.status(),200,'HTTP '+route+' '+response?.status());
  assert.equal(new URL(page.url()).pathname,route,'Unexpected redirect for '+route);
  const title=page.locator('.lf-member-nav-copy');
  assert.equal(await title.count(),1,'Member Topbar title count for '+route);
  assert(await title.isVisible(),'Member title hidden on '+route);
  assert.equal((await title.innerText()).trim(),label);
  assert((await page.title()).startsWith(label+' — '),'HTML title missing '+label);
  const metrics=await page.evaluate(()=>{
    const el=document.querySelector('.lf-member-nav-copy');
    const outer=document.querySelector('.lf-member-nav-title');
    const bounds=el.getBoundingClientRect(),bar=outer.getBoundingClientRect();
    return {width:innerWidth,scrollWidth:document.documentElement.scrollWidth,
      labelWidth:bounds.width,labelLeft:bounds.left,labelRight:bounds.right,
      barLeft:bar.left,barRight:bar.right,
      display:getComputedStyle(outer).display};
  });
  assert(metrics.labelWidth>0 && metrics.display!=='none','Title has no visible space');
  assert(metrics.barLeft>=-3 && metrics.barRight<=width+3,
    'Topbar extends beyond viewport '+JSON.stringify(metrics));
  assert(metrics.scrollWidth<=width+3,'Horizontal document overflow '+JSON.stringify(metrics));
  return metrics;
}
async function assertAxe(page, selector) {
  const report=await new AxeBuilder({page}).include(selector)
    .withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa']).analyze();
  const violations=report.violations.map(v=>({id:v.id,impact:v.impact,
    targets:v.nodes.slice(0,3).map(n=>n.target)}));
  assert.equal(violations.length,0,'Scoped axe violations: '+JSON.stringify(violations));
  return {violations:0,incomplete:report.incomplete.map(v=>v.id)};
}
async function linksFlow(page,width,mode) {
  await verifyTopbar(page,'/studio/links','Links e Blocos',width);
  assert.equal((await page.locator('#studio-my-links-title').innerText()).includes('Links e Blocos'),true);
  const action=page.locator('#studio-my-links-title a.btn-primary');
  assert.equal(await action.count(),1,'Exactly one card CTA expected');
  assert.equal((await action.innerText()).trim(),'+ Bloco-Link');
  assert((await action.getAttribute('aria-label')||'').includes('+ Bloco-Link'));
  assert.equal(new URL(await action.getAttribute('href'),base).pathname,'/studio/blocks');
  if(width===375) {
    await check('Member Topbar WCAG scope',()=>assertAxe(page,'.lf-member-titled-nav'));
    await check('Links and Blocks CTA WCAG scope',()=>assertAxe(page,'#studio-my-links-title'));
  }
  await action.focus();
  assert(await action.evaluate(el=>document.activeElement===el),'CTA is not keyboard focusable');
  await page.keyboard.press('Enter');
  await page.waitForURL('**/studio/blocks',{timeout:15000});
  assert.equal((await page.locator('.lf-member-nav-copy').innerText()).trim(),'Gerenciar Blocos');
  await page.goBack({waitUntil:'domcontentloaded',timeout:15000});
  assert.equal(new URL(page.url()).pathname,'/studio/links','Browser back must restore links');
  await page.locator('#studio-my-links-title a.btn-primary').click();
  await page.waitForURL('**/studio/blocks',{timeout:15000});
  await page.goBack({waitUntil:'domcontentloaded',timeout:15000});
  assert.equal(new URL(page.url()).pathname,'/studio/links','Browser back after pointer click');
  return {viewport:width,mode,keyboardActivation:true,pointerActivation:true,history:true};
}
async function run() {
  assert.equal(process.env.QA_EXPECTED_SITE,base,'Only isolated localhost accepted');
  const root=fs.realpathSync(dir+'/linkstack');
  assert(root.startsWith(dir+'/'),'Not an ephemeral application');
  const env=fs.readFileSync(root+'/.env','utf8');
  for(const needle of ['APP_ENV=local','DB_CONNECTION=sqlite',
      'DB_DATABASE='+root+'/database/audit.sqlite','MAIL_MAILER=log','LOCALE=pt-BR'])
    assert(env.includes(needle),'Fixture configuration missing '+needle);
  fs.mkdirSync(screenshots,{recursive:true});
  save();
  const browser=await chromium.launch({headless:true});
  try {
    for(const [width,mode] of [[320,'dark'],[375,'light'],[768,'dark'],
                               [1024,'light'],[1280,'dark']]){
      const context=await browser.newContext({
        viewport:{width,height:900},reducedMotion:'reduce',serviceWorkers:'block'});
      await context.route('**/*',r=>{
        try {if(new URL(r.request().url()).origin===base)return r.continue();}
        catch (_) {}
        return r.abort('blockedbyclient');
      });
      await context.addInitScript(color=>{
        try {localStorage.setItem('color-mode',color);} catch (_) {}
      },mode);
      const page=await context.newPage();
      page.setDefaultTimeout(12000);
      try {
        await check('Login member '+width+' '+mode,async()=>{await login(page);return {width,mode};});
        if(width===375) {
          await check('Free member cannot access VIP visitor report',async()=>{
            const response=await page.goto(base+'/studio/visitors',{waitUntil:'domcontentloaded'});
            assert.equal(response?.status(),403,'Free visitor reports must be blocked');
            return {freeStatus:403};
          });
          await check('VIP member visitor report Topbar',async()=>{
            const vip=await browser.newContext({viewport:{width,height:900},serviceWorkers:'block'});
            try {
              await vip.route('**/*',r=>{
                try {if(new URL(r.request().url()).origin===base)return r.continue();}
                catch (_) {}
                return r.abort('blockedbyclient');
              });
              const vipPage=await vip.newPage();
              await login(vipPage,'auditvip');
              return await verifyTopbar(vipPage,'/studio/visitors','Origem dos Visitantes',width);
            } finally {await vip.close();}
          });
          for(const [route,label] of pages) {
            await check('Authenticated route '+route,()=>verifyTopbar(page,route,label,width));
          }
          await check('Canonical verified badge on member Topbar',async()=>{
            await verifyTopbar(page,'/studio/verification','Selo Verificado',width);
            const mark=page.locator('.lf-member-verified-icon svg.badge');
            assert.equal(await mark.count(),1);
            assert.equal(await mark.locator('path.badge-body').getAttribute('fill'),'#179cf0');
            assert.equal(await mark.locator('path.badge-tick').getAttribute('fill'),'#fcfcfc');
            return {sameCanonicalBadge:true};
          });
          await check('Edit link icon selector label and preserved options',async()=>{
            const response=await page.goto(base+'/studio/add-link?block=predefined',{waitUntil:'domcontentloaded'});
            assert.equal(response?.status(),200);
            const label=page.locator('label[for="predefined-button"]');
            await label.waitFor({state:'visible',timeout:12000});
            assert.equal((await label.innerText()).trim(),'Selecionar ícone do botão');
            const select=page.locator('#predefined-button');
            assert((await select.locator('option').count())>0,'Original choices disappeared');
            return {nativeSelect:true,options:await select.locator('option').count()};
          });
        } else {
          await check('Responsive Links Topbar '+width+' '+mode,
            ()=>verifyTopbar(page,'/studio/links','Links e Blocos',width));
          await check('Responsive Verified badge '+width+' '+mode,
            ()=>verifyTopbar(page,'/studio/verification','Selo Verificado',width));
        }
        await check('CTA keyboard click pointer click and browser back '+width,
          ()=>linksFlow(page,width,mode));
        if(width===375||width===1280) {
          await check('Screenshot '+width+' '+mode,async()=>{
            await verifyTopbar(page,'/studio/links','Links e Blocos',width);
            const output=path.join(screenshots,'member-links-'+width+'-'+mode+'.png');
            await page.screenshot({path:output,fullPage:true,timeout:15000});
            return {file:path.basename(output)};
          });
        }
      } finally {await context.close();}
    }
    await check('Unauthenticated member access is protected',async()=>{
      const ctx=await browser.newContext();
      try {
        const response=await ctx.request.get(base+'/studio/links',{maxRedirects:0});
        assert([302,401,403].includes(response.status()),'Guest allowed: '+response.status());
        return {status:response.status()};
      } finally {await ctx.close();}
    });
  } finally {await browser.close();save();}
  console.log('MEMBER NAVIGATION BROWSER SUMMARY',JSON.stringify(result.summary));
  if(result.summary.failed)process.exitCode=1;
}
run().catch(e=>{
  result.checks.push({label:'harness',status:'FAIL',error:String(e.stack||e).slice(0,2500)});
  save();console.error('MEMBER BROWSER FATAL',e);process.exitCode=1;
});
