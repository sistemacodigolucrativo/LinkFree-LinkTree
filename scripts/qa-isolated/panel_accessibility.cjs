'use strict';
// Only existing isolated synthetic users. This suite never submits profile/admin data.
const fs=require('fs');
const assert=require('node:assert/strict');
const {chromium}=require('/tmp/linkfree-qa-node/node_modules/playwright');
const {AxeBuilder}=require('/tmp/linkfree-qa-node/node_modules/@axe-core/playwright');
const base='http://127.0.0.1:18765',root='/tmp/linkfree-audit/linkstack';
const file=root+'/storage/app/linkfree/theme-options.json';
async function run(){
  assert.equal(process.env.QA_EXPECTED_SITE,base);
  assert(fs.readFileSync(root+'/.env','utf8').includes('DB_DATABASE='+root+'/database/audit.sqlite'));
  const saved=fs.existsSync(file)?fs.readFileSync(file):null;
  fs.mkdirSync(root+'/storage/app/linkfree',{recursive:true});
  fs.writeFileSync(file,JSON.stringify({background_color:true,text_color:true,button_color:true,button_text:true,button_style:true}));
  const results=[];
  const browser=await chromium.launch({headless:true});
  async function nextTab(page,first,next){
    assert(await first.evaluate(el=>el.labels?.length>0||!!el.getAttribute('aria-label')),'First control has no label');
    assert(await next.evaluate(el=>el.labels?.length>0||!!el.getAttribute('aria-label')),'Next control has no label');
    await first.focus();
    await page.keyboard.press('Tab');
    assert(await next.evaluate(el=>document.activeElement===el),'Tab did not move to expected control');
    assert(await next.evaluate(el=>{
      const s=getComputedStyle(el);
      return s.outlineStyle!=='none'&&parseFloat(s.outlineWidth)>=2||s.boxShadow!=='none';
    }),'No visible focus indicator');
  }
  async function runCase(role,route,scheme,width){
    const context=await browser.newContext({viewport:{width,height:844},colorScheme:scheme,reducedMotion:'reduce',serviceWorkers:'block'});
    try{
      await context.route('**/*',r=>{try{if(new URL(r.request().url()).origin===base)return r.continue()}catch(_){}return r.abort('blockedbyclient')});
      await context.addInitScript(mode=>localStorage.setItem('color-mode',mode),scheme);
      const page=await context.newPage();
      await page.goto(base+'/login',{waitUntil:'domcontentloaded'});
      const form=page.locator('form[action$="/login"]').first();
      await nextTab(page,form.locator('[name=email]'),form.locator('[name=password]'));
      await form.locator('[name=email]').fill(role+'@example.test');
      await form.locator('[name=password]').fill('Synthetic-password-123');
      await Promise.all([page.waitForURL('**/dashboard',{timeout:25000}),form.locator('button[type=submit]').click()]);
      const res=await page.goto(base+route,{waitUntil:'load',timeout:30000});
      assert.equal(res.status(),200);
      await page.waitForFunction(mode=>document.body.classList.contains(mode),scheme,{timeout:12000});
      if(route==='/studio/page'){
        await nextTab(page,page.locator('#littlelink_name'),page.locator('#lf-display-name'));
      }else if(route==='/studio/theme'){
        await nextTab(page,page.locator('#lf-custom-background_color'),page.locator('#lf-custom-text_color'));
      }else{
        await nextTab(page,page.locator('#field-name'),page.locator('#field-email'));
      }
      const axe=await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa']).analyze();
      const violations=axe.violations.map(v=>({id:v.id,nodes:v.nodes.slice(0,4).map(n=>({target:n.target,reason:n.failureSummary}))}));
      const overflow=await page.evaluate(()=>({viewport:innerWidth,document:document.documentElement.scrollWidth}));
      assert(overflow.document<=overflow.viewport+2,'Horizontal overflow '+JSON.stringify(overflow));
      let toggle=null;
      if(route==='/studio/theme'&&width===1280){
        const trigger=page.locator('.lf-panel-theme-link').first();
        await trigger.focus();
        await page.keyboard.press('Enter');
        const panel=page.locator('#offcanvasExample');
        await panel.waitFor({state:'visible'});
        const light=panel.locator('button[data-setting="color-mode"][data-value="light"]');
        const dark=panel.locator('button[data-setting="color-mode"][data-value="dark"]');
        assert.equal(await light.getAttribute('aria-pressed'),String(scheme==='light'));
        await dark.focus();
        await page.keyboard.press('Space');
        await page.waitForFunction(()=>document.body.classList.contains('dark'));
        assert.equal(await dark.getAttribute('aria-pressed'),'true');
        assert.equal(await light.getAttribute('aria-pressed'),'false');
        await page.keyboard.press('Tab');
        assert(await light.evaluate(e=>document.activeElement===e),'Color mode keyboard order is broken');
        await page.keyboard.press('Enter');
        await page.waitForFunction(()=>document.body.classList.contains('light'));
        assert.equal(await light.getAttribute('aria-pressed'),'true');
        toggle={keyboard:true,pressedState:true};
        await panel.locator('button[data-bs-dismiss=offcanvas]').click();
      }
      results.push({role,route,scheme,width,status:violations.length?'FAIL':'PASS',violations,incomplete:axe.incomplete.map(x=>x.id),keyboard:true,overflow,toggle});
    }catch(e){results.push({role,route,scheme,width,status:'FAIL',error:String(e.message).slice(0,1000)});}
    finally{await context.close();}
  }
  try{
    for(const [role,route] of [['auditfree','/studio/page'],['auditfree','/studio/theme'],['auditadmin','/admin/edit-user/2']]){
      await runCase(role,route,'light',1280);
      await runCase(role,route,'dark',390);
    }
  }finally{
    await browser.close();
    if(saved===null)fs.unlinkSync(file);else fs.writeFileSync(file,saved);
  }
  return {status:results.length===6&&results.every(r=>r.status==='PASS')?'PASS':'FAIL',results};
}
module.exports={run};
