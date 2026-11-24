/* eslint-disable no-console */
'use strict';
// Axe browser report against an ephemeral localhost Laravel app with synthetic users.
// No production accounts, secrets, or remote URLs are accessed.
const fs = require('fs');
const path = require('path');
const base = 'http://127.0.0.1:18765';
if (process.env.QA_EXPECTED_SITE !== base) throw new Error('Isolated localhost origin required');
const {chromium} = require('/tmp/linkfree-qa-node/node_modules/playwright');
const {AxeBuilder} = require('/tmp/linkfree-qa-node/node_modules/@axe-core/playwright');
const output = '/tmp/linkfree-audit/browser-a11y-results.json';

(async () => {
  const issues = [], pages = [], errors = [];
  const browser = await chromium.launch({headless:true});
  async function makeContext() {
    const ctx = await browser.newContext({viewport:{width:390,height:844},reducedMotion:'reduce',serviceWorkers:'block'});
    await ctx.route('**/*', route => {
      try {
        const url = new URL(route.request().url());
        if (url.hostname === '127.0.0.1' && url.port === '18765') return route.continue();
      } catch (_) {}
      return route.abort('blockedbyclient');
    });
    return ctx;
  }
  async function login(page, name) {
    await page.goto(base + '/login',{waitUntil:'domcontentloaded'});
    const form=page.locator('form[action$="/login"]').first();
    await form.locator('[name=email]').fill(name+'@example.test');
    await form.locator('[name=password]').fill('Synthetic-password-123');
    await Promise.all([
      page.waitForURL('**/dashboard',{timeout:20000}),
      form.locator('button[type=submit]').click()
    ]);
  }
  async function scan(page, location, role) {
    const res = await page.goto(base+location,{waitUntil:'domcontentloaded',timeout:30000});
    if (!res || res.status()!==200) throw new Error('Unexpected status '+location+' '+res?.status());
    if(location.startsWith('/@')) await page.locator('body[data-lf-contrast-ready="true"]').waitFor({timeout:10000});
    await page.waitForTimeout(500);
    const axe = await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa']).analyze();
    const found=axe.violations.map(v=>({
      rule:v.id,impact:v.impact,
      count:v.nodes.length,
      examples:v.nodes.slice(0,3).map(n=>({target:n.target,reason:String(n.failureSummary||'').slice(0,400)}))
    }));
    pages.push({route:location,role,axeRules:found.length,axeNodes:found.reduce((n,x)=>n+x.count,0),incomplete:axe.incomplete.map(x=>x.id)});
    for(const v of found)issues.push({route:location,role,...v});
    console.log(location,role,'violations='+found.map(v=>v.rule+'('+v.count+')').join(','));
  }
  try {
    for (const [role, paths] of [
      ['anonymous',['/','/login','/register','/report','/@auditfree']],
      ['member',['/studio/referrals','/studio/verification','/studio/links','/studio/page']],
      ['admin',['/admin/referrals','/admin/verification','/admin/users']]
    ]) {
      const context=await makeContext(),page=await context.newPage();
      try {
        if (role!=='anonymous') await login(page,role==='admin'?'auditadmin':'auditfree');
        for(const location of paths) {
          try {await scan(page,location,role);}
          catch(e) {
            errors.push({route:location,role,error:String(e.message).slice(0,300)});
            console.error('SCAN ERROR',role,location,String(e.message).slice(0,300));
          }
        }
      } finally { await context.close(); }
    }
  } finally {await browser.close();}
  const targeted=new Set(['image-alt','select-name','aria-prohibited-attr','scrollable-region-focusable']);
  const priority=issues.filter(x=>targeted.has(x.rule));
  const report={
    origin:'127.0.0.1 local isolated app, synthetic data only',
    sourceSha:process.env.GITHUB_SHA||'',
    status:'Automated scoped WCAG checks only; not complete WCAG or human/device acceptance',
    pages,issues,errors,
    summary:{pages:pages.length,errors:errors.length,axeRules:issues.length,
      criticalOrSerious:issues.filter(v=>['critical','serious'].includes(v.impact)).length,
      targetedRegressions:priority.length,remainingContrast:issues.filter(v=>v.rule==='color-contrast').length}
  };
  try {
    report.interactions = await require('./browser_interactions.cjs').run();
  } catch (e) {
    report.interactions = {status:'ERROR', results:[], error:String(e.message).slice(0,300)};
  }
  fs.writeFileSync(output,JSON.stringify(report,null,2));
  console.log('BROWSER INTERACTION SUMMARY',JSON.stringify(report.interactions));
  console.log('A11Y SUMMARY',JSON.stringify(report.summary));
  try { report.profileMatrix = await require('./public_accessibility.cjs').run(); }
  catch(e) { report.profileMatrix = {status:'ERROR',error:String(e.stack||e).slice(0,2500)}; }
  fs.writeFileSync(output,JSON.stringify(report,null,2));
  console.log('PUBLIC A11Y MATRIX',JSON.stringify(report.profileMatrix));
  try { report.formMatrix = await require('./panel_accessibility.cjs').run(); }
  catch(e) { report.formMatrix = {status:'ERROR',error:String(e.stack||e).slice(0,1800)}; }
  fs.writeFileSync(output,JSON.stringify(report,null,2));
  console.log('PANEL A11Y MATRIX',JSON.stringify(report.formMatrix));
  if(errors.length || issues.length || report.interactions.status !== 'PASS' || report.profileMatrix.status !== 'PASS' || report.formMatrix.status !== 'PASS') {
    process.exitCode=1;
  } else {
    console.log('PASS scoped automated accessibility matrix and interaction regression. Human/device acceptance is separate.');
  }
})().catch(e=>{console.error(String(e.stack||e));process.exitCode=1;});
