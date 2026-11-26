/* Public LinkFree acceptance using an isolated Laravel SQLite fixture only. */
'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const {chromium}=require('/tmp/linkfree-qa-node/node_modules/playwright');
const {AxeBuilder}=require('/tmp/linkfree-qa-node/node_modules/@axe-core/playwright');
const base='http://127.0.0.1:18765';
const root='/tmp/linkfree-audit';
const captures=path.join(root,'public-catalog-screenshots');
const results=path.join(root,'public-catalog-results.json');
const summary={origin:base,fixture:'synthetic sqlite',checks:[]};

function save(){summary.totals={pass:summary.checks.filter(x=>x.status==='PASS').length,
  fail:summary.checks.filter(x=>x.status==='FAIL').length};fs.writeFileSync(results,JSON.stringify(summary,null,2))}
async function check(label,fn){
  try{const proof=await fn();summary.checks.push({label,status:'PASS',proof});console.log('PASS',label)}
  catch(error){summary.checks.push({label,status:'FAIL',error:String(error.stack||error).slice(0,2800)});
    console.error('FAIL',label,String(error.message||error));}
  save();
}
async function open(page,url){
  const response=await page.goto(base+url,{waitUntil:'domcontentloaded',timeout:25000});
  assert.equal(response?.status(),200,'Expected 200 on '+url+' got '+response?.status());
  assert.equal(new URL(page.url()).pathname,url,'Unexpected redirect');
}
async function horizontal(page,width){
  const measured=await page.evaluate(()=>({client:document.documentElement.clientWidth,
    scroll:document.documentElement.scrollWidth,body:document.body.scrollWidth}));
  assert(measured.scroll<=width+3,'Horizontal overflow '+JSON.stringify(measured));
  return measured;
}
async function axe(page,selector){
  const report=await new AxeBuilder({page}).include(selector)
    .withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa']).analyze();
  const issues=report.violations.map(v=>({id:v.id,impact:v.impact,
    target:v.nodes.slice(0,3).map(n=>n.target)}));
  assert.deepEqual(issues,[],'WCAG violations '+JSON.stringify(issues));
  return {violations:0,incomplete:report.incomplete.map(v=>v.id)};
}
async function run(){
  assert.equal(process.env.QA_EXPECTED_SITE,base);
  const appRoot=fs.realpathSync(root+'/linkstack');
  assert(appRoot.startsWith(root+'/'),'Fixture must live in /tmp');
  const env=fs.readFileSync(appRoot+'/.env','utf8');
  for(const fragment of ['APP_ENV=local','DB_CONNECTION=sqlite','MAIL_MAILER=log'])
    assert(env.includes(fragment),'Fixture missing '+fragment);
  fs.mkdirSync(captures,{recursive:true});save();
  const browser=await chromium.launch({headless:true});
  try{
    for(const width of [320,375,768,1024,1280]){
      const context=await browser.newContext({viewport:{width,height:820},
        reducedMotion:'reduce',serviceWorkers:'block'});
      await context.route('**/*',request=>{
        const url=new URL(request.request().url());
        if(url.origin===base)return request.continue();
        return request.abort('blockedbyclient');
      });
      const page=await context.newPage();
      page.setDefaultTimeout(14000);
      try{
        await check('Homepage public copy '+width,async()=>{
          await open(page,'/');
          assert((await page.locator('h1').first().innerText()).includes('Seu conteúdo'),
            'New value proposition not displayed');
          assert.equal(await page.locator('a[href$="/plataforma/recursos"]').count()>=1,true);
          assert.equal(await page.locator('a[href$="/plataforma/proximas-implementacoes"]').count()>=1,true);
          return horizontal(page,width);
        });
        await check('Public catalog resources '+width,async()=>{
          await open(page,'/plataforma/recursos');
          const tiles=page.locator('.lf-feature-tile');
          assert((await tiles.count())>=19,'Too few evidenced features');
          assert.equal(await page.locator('h1').count(),1);
          assert((await page.locator('body').innerText()).includes('VIP e administrador.'));
          assert((await page.title()).startsWith('Recursos para membros'));
          assert((await page.locator('link[rel="canonical"]').getAttribute('href')).endsWith('/plataforma/recursos'));
          const geometry=await horizontal(page,width);
          if(width===375||width===1280){
            await axe(page,'main');
            await page.screenshot({path:path.join(captures,'features-'+width+'.png'),fullPage:true,timeout:20000});
          }
          return {tiles:await tiles.count(),geometry};
        });
        await check('Public roadmap excludes existing tools '+width,async()=>{
          await open(page,'/plataforma/proximas-implementacoes');
          assert.equal(await page.locator('h1').count(),1);
          const items=page.locator('.lf-upcoming-card');
          assert.equal(await items.count(),1,'Only one strictly documented future tool');
          assert((await items.first().innerText()).includes('Pré-visualização interativa'));
          assert((await items.first().innerText()).includes('Em validação'));
          assert(!(await items.first().innerText()).includes('Selo verificado'));
          assert((await page.locator('link[rel="canonical"]').getAttribute('href')).endsWith('/plataforma/proximas-implementacoes'));
          const geometry=await horizontal(page,width);
          if(width===375||width===1280){
            await axe(page,'main');
            await page.screenshot({path:path.join(captures,'roadmap-'+width+'.png'),fullPage:true,timeout:20000});
          }
          return geometry;
        });
      }finally{await context.close()}
    }

    await check('Shipped feature is removed from future roadmap',async()=>{
      const fixtureFile=path.join(appRoot,'assets/js/linkfree-platform-picker.js');
      assert(!fs.existsSync(fixtureFile),'Base main fixture should not contain PR38 asset yet');
      fs.mkdirSync(path.dirname(fixtureFile),{recursive:true});
      fs.writeFileSync(fixtureFile,'/* synthetic feature marker; not product code */');
      const page=await browser.newPage();
      try{
        await open(page,'/plataforma/proximas-implementacoes');
        assert.equal(await page.locator('.lf-upcoming-card').count(),0);
        assert(await page.locator('.lf-empty-roadmap').isVisible());
        return {emptyState:true};
      }finally{await page.close();fs.unlinkSync(fixtureFile)}
    });
    await check('Public pages do not disclose restricted member data',async()=>{
      const ctx=await browser.newContext();
      try{
        const response=await ctx.request.get(base+'/studio/visitors',{maxRedirects:0});
        assert([302,401,403].includes(response.status()),'Guest gained member access');
        return {guestStatus:response.status()};
      }finally{await ctx.close()}
    });
  }finally{await browser.close();save()}
  console.log('PUBLIC CATALOG SUMMARY',JSON.stringify(summary.totals));
  if(summary.totals.fail)process.exitCode=1;
}
run().catch(e=>{summary.checks.push({label:'harness',status:'FAIL',error:String(e.stack||e)});save();process.exitCode=1;console.error(e)});
