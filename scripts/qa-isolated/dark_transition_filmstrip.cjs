/* Focused frame audit, not a re-run of existing HTTP or layout tests.
 * Chromium/CDP captures compositor frames across same-origin navigation and POST.
 * Measures near-white viewport coverage while the dark theme is selected.
 * Synthetic fixture ONLY. Does not modify application files or production.
 */
'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const {chromium} = require('/tmp/linkfree-qa-node/node_modules/playwright');
const {PNG} = require('/tmp/linkfree-qa-node/node_modules/pngjs');
const base = 'http://127.0.0.1:18765';
const dir = '/tmp/linkfree-audit/dark-transition-filmstrip';
const fixture = '/tmp/linkfree-audit/linkstack/.env';
fs.mkdirSync(dir,{recursive:true});
const cases = [];
const resultsFile = dir+'/results.json';
function store() {
  fs.writeFileSync(resultsFile, JSON.stringify({
    sourceSha:process.env.GITHUB_SHA||null,
    scope:'Chromium compositor filmstrip, synthetic localhost only; not WCAG nor all platforms',
    threshold:'near-white pixel: R,G,B >= 235; alert when >70% of sampled viewport pixels',
    cases
  },null,2));
}
async function login(page) {
  await page.goto(base+'/login',{waitUntil:'domcontentloaded',timeout:25000});
  const form=page.locator('form[action$="/login"]').first();
  await form.locator('[name=email]').fill('auditadmin@example.test');
  await form.locator('[name=password]').fill('Synthetic-password-123');
  await Promise.all([
    page.waitForURL('**/dashboard',{timeout:25000}),
    form.locator('button[type=submit]').click({timeout:15000})
  ]);
}
async function darkState(page) {
  const state=await page.evaluate(()=>{
    const root=document.documentElement, body=document.body;
    return {
      dark:body.classList.contains('dark'),
      prepaint:root.classList.contains('lf-prepaint-dark'),
      rootBackground:getComputedStyle(root).backgroundColor,
      bodyBackground:getComputedStyle(body).backgroundColor,
      preference:localStorage.getItem('color-mode'),
      horizontalOverflow:root.scrollWidth-innerWidth
    };
  });
  assert(state.dark && state.prepaint, 'Dark/prepaint class missing: '+JSON.stringify(state));
  assert.equal(state.rootBackground,'rgb(34, 39, 56)','Root background differs from audited dark canvas');
  assert(state.horizontalOverflow<=3,'Horizontal overflow: '+JSON.stringify(state));
  return state;
}
function samplePNG(buffer) {
  const image=PNG.sync.read(buffer,{checkCRC:false});
  let bright=0, count=0, brightness=[];
  // Equally spaced viewport samples ignore 1px scrollbar/border artifacts.
  for(let y=1;y<=11;y++)for(let x=1;x<=17;x++){
    const px=Math.min(image.width-1,Math.floor(image.width*x/18));
    const py=Math.min(image.height-1,Math.floor(image.height*y/12));
    const at=(py*image.width+px)*4;
    const R=image.data[at],G=image.data[at+1],B=image.data[at+2],A=image.data[at+3];
    if(A<200) {bright++; brightness.push(255); count++;continue;}
    if(R>=235&&G>=235&&B>=235)bright++;
    brightness.push(Math.round(.2126*R+.7152*G+.0722*B));
    count++;
  }
  brightness.sort((a,b)=>a-b);
  return {whiteFraction:bright/count,medianBrightness:brightness[Math.floor(count/2)],width:image.width,height:image.height};
}
async function capture(page,label,action) {
  const cdp=await page.context().newCDPSession(page);
  const frames=[];
  let first=null,last=null,worst=null,maxFraction=-1;
  let closed=false;
  cdp.on('Page.screencastFrame',event=>{
    if(!closed) {
      try {
        const bytes=Buffer.from(event.data,'base64');
        const stats=samplePNG(bytes);
        frames.push(stats);
        if(!first) first=bytes;
        last=bytes;
        if(stats.whiteFraction>maxFraction){maxFraction=stats.whiteFraction;worst=bytes;}
      } catch(e) {frames.push({decodeError:String(e)});}
    }
    // CDP explicitly requires acknowledgement before sending additional frames.
    void cdp.send('Page.screencastFrameAck',{sessionId:event.sessionId}).catch(()=>{});
  });
  let actionError=null;
  try {
    await cdp.send('Page.enable');
    await cdp.send('Page.startScreencast',{format:'png',everyNthFrame:1,maxWidth:1280,maxHeight:900});
    await page.waitForTimeout(110);
    await action();
    await page.waitForTimeout(230);
  } catch(e){actionError=e;}
  finally {
    closed=true;
    await cdp.send('Page.stopScreencast').catch(()=>{});
    await cdp.detach().catch(()=>{});
  }
  const current=actionError?null:await darkState(page);
  const valid=frames.filter(f=>!f.decodeError);
  const bad=frames.filter(f=>f.decodeError);
  const report={name:label,recordedFrames:frames.length,validFrames:valid.length,
    failedDecodes:bad.length,maxWhiteFraction:valid.reduce((m,x)=>Math.max(m,x.whiteFraction),0),
    maxMedianBrightness:valid.reduce((m,x)=>Math.max(m,x.medianBrightness),0),
    firstFrame:valid[0]||null,lastFrame:valid[valid.length-1]||null,
    finalState:current,status:'PENDING'};
  if(first)fs.writeFileSync(dir+'/'+label+'-first.png',first);
  if(worst)fs.writeFileSync(dir+'/'+label+'-worst.png',worst);
  if(last)fs.writeFileSync(dir+'/'+label+'-last.png',last);
  try {
    if(actionError)throw actionError;
    assert.equal(bad.length,0,'Screencast frame decoding errors: '+bad.length);
    assert(valid.length>=3,'Insufficient recorded frames to inspect transition: '+valid.length);
    assert(report.maxWhiteFraction<0.70,
      'Near-white full-viewport frame found: '+JSON.stringify(report));
    report.status='PASS';
    console.log('PASS '+label+' frames='+valid.length+' worstWhite='+report.maxWhiteFraction.toFixed(3));
  }catch(e){report.status='FAIL';report.error=String(e.stack||e).slice(0,1500);console.log('FAIL '+label+' '+String(e.message).slice(0,250));}
  cases.push(report);store();
}
async function run(){
  assert.equal(process.env.QA_EXPECTED_SITE,base,'Local-only harness guard');
  assert(fs.realpathSync(fixture).startsWith('/tmp/linkfree-audit/'),'Unsafe fixture path');
  const env=fs.readFileSync(fixture,'utf8');
  for(const token of ['APP_ENV=local','DB_CONNECTION=sqlite','MAIL_MAILER=log',
    'DB_DATABASE=/tmp/linkfree-audit/linkstack/database/audit.sqlite'])assert(env.includes(token));
  const browser=await chromium.launch({headless:true});
  try{
    for(const width of [375,1280]){
      const context=await browser.newContext({viewport:{width,height:width<500?844:900},
        colorScheme:'dark',reducedMotion:'no-preference',serviceWorkers:'block'});
      await context.route('**/*',route=>{
        try {if(new URL(route.request().url()).origin===base)return route.continue();}catch(_){}
        return route.abort('blockedbyclient');
      });
      await context.addInitScript(()=>{try{localStorage.setItem('color-mode','dark');}catch(_){}});
      try{
        const page=await context.newPage();
        page.setDefaultTimeout(16000);
        await login(page);
        await page.goto(base+'/admin/config',{waitUntil:'domcontentloaded'});
        await darkState(page);
        await capture(page,'navigation-'+width,async()=>{
          await Promise.all([
            page.waitForURL('**/admin/config/sections/application',{waitUntil:'domcontentloaded',timeout:25000}),
            page.locator('a[href$="/admin/config/sections/application"]').first().click()
          ]);
        });
        if(width===375){
          await capture(page,'save-redirect-'+width,async()=>{
            const toggle=page.locator('#ALLOW_REGISTRATION');
            assert(await toggle.isChecked(),'Synthetic flag must begin enabled');
            const responsePromise=page.waitForResponse(res=>
              new URL(res.url()).pathname==='/admin/config'&&res.request().method()==='POST',
              {timeout:25000});
            await toggle.uncheck({timeout:20000});
            const response=await responsePromise;
            assert.equal(response.status(),302);
            await page.waitForLoadState('domcontentloaded');
            await page.locator('#ALLOW_REGISTRATION').waitFor({state:'visible'});
            assert.equal(await page.locator('#ALLOW_REGISTRATION').isChecked(),false);
          });
          await capture(page,'reload-'+width,async()=>{
            await page.reload({waitUntil:'domcontentloaded',timeout:25000});
            assert.equal(await page.locator('#ALLOW_REGISTRATION').isChecked(),false);
          });
        }
      }finally{await context.close();}
    }
  }finally{await browser.close();store();}
  const failures=cases.filter(x=>x.status!=='PASS');
  console.log('DARK TRANSITION FILMSTRIP: '+(cases.length-failures.length)+' passed, '+failures.length+
    ' failed; evidence at '+resultsFile);
  if(failures.length)process.exitCode=1;
}
run().catch(e=>{cases.push({name:'harness',status:'FAIL',error:String(e.stack||e).slice(0,1900)});store();console.error('DARK TRANSITION FATAL',e);process.exitCode=1;});
