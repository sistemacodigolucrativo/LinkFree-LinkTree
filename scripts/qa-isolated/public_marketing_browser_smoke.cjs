/* Public pages in the same disposable localhost fixture as member browser QA.
 * No production requests or browser access to non-local destinations.
 */
'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('/tmp/linkfree-qa-node/node_modules/playwright');
const { AxeBuilder } = require('/tmp/linkfree-qa-node/node_modules/@axe-core/playwright');

const origin = 'http://127.0.0.1:18765';
const outputRoot = '/tmp/linkfree-audit';
const screenshots = path.join(outputRoot, 'public-marketing-screenshots');
const results = { origin, fixture: 'ephemeral SQLite; no user or production data', checks: [] };
if (process.env.QA_EXPECTED_SITE !== origin ||
    !fs.realpathSync(outputRoot + '/linkstack').startsWith(outputRoot + '/')) {
  throw Error('Non-isolated environment rejected');
}
fs.mkdirSync(screenshots, {recursive: true});

async function check(name, func) {
  try {
    const evidence = await func();
    results.checks.push({name, status: 'PASS', evidence});
    console.log('PASS', name);
  } catch (error) {
    results.checks.push({name, status: 'FAIL', error: String(error.stack || error).slice(0, 1600)});
    console.error('FAIL', name, String(error.message || error));
  }
  fs.writeFileSync(outputRoot + '/public-marketing-browser.json', JSON.stringify(results, null, 2));
}
async function safeVisit(page, route) {
  const response = await page.goto(origin + route, {waitUntil:'domcontentloaded', timeout:20000});
  assert.equal(response.status(), 200, 'Unexpected HTTP for '+route);
  assert.equal(new URL(page.url()).pathname,route);
  assert.equal(await page.locator('main').count(),1);
  const geometry = await page.evaluate(() => ({
    viewport: innerWidth, documentWidth: document.documentElement.scrollWidth,
    bodyWidth: document.body.scrollWidth
  }));
  assert(geometry.documentWidth <= geometry.viewport + 3,
    'Horizontal overflow '+route+' '+JSON.stringify(geometry));
  return geometry;
}
async function axe(page, selector) {
  const result = await new AxeBuilder({page}).include(selector)
    .withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa']).analyze();
  const violations = result.violations.map(v=>({id:v.id,impact:v.impact,
    nodes:v.nodes.slice(0,3).map(n=>n.target)}));
  assert.equal(violations.length,0,'Scoped axe '+JSON.stringify(violations));
  return {violations:0, incomplete:result.incomplete.map(v=>v.id)};
}
(async () => {
  const browser = await chromium.launch({headless:true});
  try {
    for(const width of [320,375,768,1024,1280]){
      const context = await browser.newContext({viewport:{width,height:900},
        reducedMotion:'reduce',serviceWorkers:'block'});
      await context.route('**/*', route=>{
        try { if(new URL(route.request().url()).origin === origin) return route.continue(); }
        catch(_){}
        return route.abort('blockedbyclient');
      });
      try {
        const page = await context.newPage();
        page.setDefaultTimeout(10000);
        await check('Resources responsive '+width, async()=>{
          const metrics=await safeVisit(page,'/plataforma/recursos');
          assert.equal(await page.locator('main h1').count(),1);
          assert((await page.locator('main h1').innerText()).includes('Seu espaço na internet'));
          assert((await page.locator('.lfp-card').count()) >= 15);
          const cards=await page.locator('.lfp-card').all();
          for(const card of cards){
            const box=await card.boundingBox();
            if(box)assert(box.x>=-3 && box.x+box.width<=width+3,'Card clipped at '+width);
          }
          return metrics;
        });
        await check('Roadmap responsive and no false announcements '+width, async()=>{
          const metrics=await safeVisit(page,'/plataforma/proximas-implementacoes');
          assert.equal(await page.locator('main h1').count(),1);
          assert(await page.locator('#roadmap-empty-title').isVisible());
          const titleRects=await page.locator('.lfp-roadmap-heading em').evaluate(el=>el.getClientRects().length);
          assert.equal(titleRects,1,'Roadmap headline keyword must not split mid-word at '+width);
          assert.equal(await page.locator('.lfp-feature-tag').count(),0,
            'Roadmap must not expose existing or unapproved tool cards');
          return metrics;
        });
        if(width === 375 || width === 1280){
          await check('Public nav and keyboard '+width, async()=>{
            await safeVisit(page,'/plataforma/recursos');
            const link=page.locator('.lfp-links a[href$="/plataforma/proximas-implementacoes"]');
            assert(await link.isVisible());
            await link.focus();
            assert(await link.evaluate(el=>document.activeElement===el));
            await page.keyboard.press('Enter');
            await page.waitForURL('**/plataforma/proximas-implementacoes');
            assert(await page.locator('#roadmap-empty-title').isVisible());
            return {keyboardNavigation:true};
          });
          await check('Scoped public WCAG '+width, async()=>axe(page,'main'));
          for(const route of ['recursos','proximas-implementacoes']){
            await safeVisit(page,'/plataforma/'+route);
            const dest=path.join(screenshots,route+'-'+width+'.png');
            await page.screenshot({path:dest,fullPage:true,timeout:12000});
          }
        }
      } finally {await context.close();}
    }
  } finally {
    await browser.close();
    results.summary={passed:results.checks.filter(x=>x.status==='PASS').length,
      failed:results.checks.filter(x=>x.status==='FAIL').length};
    fs.writeFileSync(outputRoot + '/public-marketing-browser.json',JSON.stringify(results,null,2));
  }
  console.log('PUBLIC MARKETING BROWSER SUMMARY',JSON.stringify(results.summary));
  if(results.summary.failed) process.exitCode=1;
})().catch(error=>{
  console.error('PUBLIC MARKETING FATAL',error);
  results.checks.push({name:'harness',status:'FAIL',error:String(error.stack||error)});
  fs.writeFileSync(outputRoot + '/public-marketing-browser.json', JSON.stringify(results,null,2));
  process.exitCode=1;
});
