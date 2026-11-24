'use strict';
// Additional coverage only; retain the existing 12-page Chromium audit and 6 interaction cases.
const fs=require('fs');
const {execFileSync}=require('child_process');
const {chromium,firefox,webkit}=require('/tmp/linkfree-qa-node/node_modules/playwright');
const {AxeBuilder}=require('/tmp/linkfree-qa-node/node_modules/@axe-core/playwright');
const assert=require('node:assert/strict');
const root='/tmp/linkfree-audit/linkstack';
const base='http://127.0.0.1:18765';
const db=root+'/database/audit.sqlite';
function sql(code,args=[]) {return execFileSync('python3',['-c',code,db,...args],{encoding:'utf8'}).trim();}
async function run(){
  assert.equal(process.env.QA_EXPECTED_SITE,base);
  assert(fs.readFileSync(root+'/.env','utf8').includes('DB_DATABASE='+db));
  const before=sql("import sqlite3,sys,json;c=sqlite3.connect(sys.argv[1]);print(json.dumps(c.execute('select theme,image from users where id=2 and littlelink_name=?',('auditfree',)).fetchone()))");
  assert(Array.isArray(JSON.parse(before)));
  const file=root+'/storage/app/linkfree/theme-options.json';
  const permissions=fs.existsSync(file)?fs.readFileSync(file):null;
  fs.mkdirSync(root+'/storage/app/linkfree',{recursive:true});
  fs.writeFileSync(file,JSON.stringify({background_color:true,text_color:true,button_color:true,button_text:true,button_style:true}));
  const results=[];
  function fixture(theme,opts){
    sql("import sqlite3,sys,json;c=sqlite3.connect(sys.argv[1]);c.execute('update users set theme=?, image=? where id=2 and littlelink_name=?',(sys.argv[2],json.dumps({'theme_options':json.loads(sys.argv[3])}),'auditfree'));c.commit()",[theme,JSON.stringify(opts)]);
  }
  const cases=[
    ['default-light','default','light',390,{}],
    ['default-dark','default','dark',1280,{}],
    ['PolySleek','PolySleek','light',320,{}],
    ['galaxy','galaxy','dark',390,{}],
    ['custom-partial','default','light',390,{text_color:'#ffffff',button_text:'#f1f5f9'}],
    ['custom-readable','default','light',1280,{background_color:'#ffffff',text_color:'#123456',button_color:'#203040',button_text:'#ffffff'}],
    ['custom-outline','default','dark',390,{background_color:'#111111',text_color:'#ffffff',button_color:'#ffffff',button_text:'#111111',button_style:'outline'}]
  ];
  try {
    for(const [engine,type] of Object.entries({chromium,firefox,webkit})){
      let browser;
      try {
        browser=await type.launch({headless:true});
        for(const [name,theme,scheme,width,opts] of cases){
          fixture(theme,opts);
          const ctx=await browser.newContext({viewport:{width,height:844},colorScheme:scheme,reducedMotion:'reduce',serviceWorkers:'block'});
          try{
            await ctx.route('**/*',route=>{try{if(new URL(route.request().url()).origin===base)return route.continue();}catch(_){}return route.abort('blockedbyclient');});
            const page=await ctx.newPage();
            const res=await page.goto(base+'/@auditfree',{waitUntil:'load',timeout:30000});
            assert.equal(res.status(),200);
            await page.locator('body[data-lf-contrast-ready=true]').waitFor({timeout:10000});
            const title=page.locator('h1').first(),button=page.locator('.button-entrance .button').first();
            assert.equal(await title.innerText(),'auditfree');
            assert.equal(await page.locator('.button-entrance .button').count(),1); // HTTP interaction suite deleted link 101 in this disposable browser fixture
            if(name==='custom-readable') {
              assert.equal(await title.evaluate(el=>getComputedStyle(el).color),'rgb(18, 52, 86)');
              assert.equal(await button.evaluate(el=>getComputedStyle(el).color),'rgb(255, 255, 255)');
            }
            await page.keyboard.press('Tab');
            // Reach the first profile link via actual keyboard traversal, not forced focus.
            let focused=false;
            for(let i=0;i<20;i++){
              if(await button.evaluate(el=>document.activeElement===el)){focused=true;break;}
              await page.keyboard.press('Tab');
            }
            assert(focused,'Profile link not reachable using Tab');
            assert(await button.evaluate(el=>getComputedStyle(el).outlineStyle!=='none'&&parseFloat(getComputedStyle(el).outlineWidth)>=2),'No visible keyboard outline');
            const geometry=await page.evaluate(()=>({width:innerWidth,scroll:document.documentElement.scrollWidth}));
            assert(geometry.scroll<=geometry.width+1,'Horizontal overflow '+JSON.stringify(geometry));
            const axe=await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa']).analyze();
            const violations=axe.violations.map(v=>({id:v.id,nodes:v.nodes.map(n=>({target:n.target,reason:n.failureSummary})).slice(0,5)}));
            results.push({engine,name,width,scheme,status:violations.length?'FAIL':'PASS',violations,incomplete:axe.incomplete.map(v=>v.id)});
          }catch(e){results.push({engine,name,status:'FAIL',error:String(e.message).slice(0,500)});}
          finally{await ctx.close();}
        }
      }catch(e){results.push({engine,status:'FAIL',error:String(e.message).slice(0,1000)});}
      finally{if(browser)await browser.close();}
    }
  }finally{
    sql("import sqlite3,sys,json;c=sqlite3.connect(sys.argv[1]);c.execute('update users set theme=?,image=? where id=2 and littlelink_name=?',(*json.loads(sys.argv[2]),'auditfree'));c.commit()",[before]);
    if(permissions===null)fs.unlinkSync(file);else fs.writeFileSync(file,permissions);
  }
  return {status:results.length===21&&results.every(r=>r.status==='PASS')?'PASS':'FAIL',results};
}
module.exports={run};
