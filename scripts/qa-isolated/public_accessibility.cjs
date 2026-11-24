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
  const image=root+'/assets/img/background-img/2_step04qa.png';
  assert(!fs.existsSync(image),'Synthetic image path already exists');
  fs.mkdirSync(root+'/assets/img/background-img',{recursive:true});
  // Build a real black/white PNG with the standard Python library; no external assets.
  execFileSync('python3',['-c',"import sys,struct,zlib; w=h=16; chunk=lambda k,d: struct.pack('>I',len(d))+k+d+struct.pack('>I',zlib.crc32(k+d)&0xffffffff); rows=b''.join(b'\\0'+bytes([0]*24+[255]*24) for y in range(h)); open(sys.argv[1],'xb').write(b'\\x89PNG\\r\\n\\x1a\\n'+chunk(b'IHDR',struct.pack('>IIBBBBB',w,h,8,2,0,0,0))+chunk(b'IDAT',zlib.compress(rows))+chunk(b'IEND',b''))",image]);
  const imageData=fs.readFileSync(image);fs.unlinkSync(image);
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
    ['custom-outline','default','dark',390,{background_color:'#111111',text_color:'#ffffff',button_color:'#ffffff',button_text:'#111111',button_style:'outline'}],
    ['custom-image','default','light',320,{text_color:'#ffffff',button_text:'#f1f5f9'}]
  ];
  try {
    for(const [engine,type] of Object.entries({chromium,firefox,webkit})){
      let browser;
      try {
        browser=await type.launch({headless:true});
        for(const [name,theme,scheme,width,opts] of cases){
          fixture(theme,opts);
          if(name==='custom-image')fs.writeFileSync(image,imageData,{flag:'wx'});
          else if(fs.existsSync(image))fs.unlinkSync(image);
          const ctx=await browser.newContext({viewport:{width,height:844},colorScheme:scheme,reducedMotion:name==='default-light'?'no-preference':'reduce',serviceWorkers:'block'});
          try{
            await ctx.route('**/*',route=>{try{if(new URL(route.request().url()).origin===base)return route.continue();}catch(_){}return route.abort('blockedbyclient');});
            const page=await ctx.newPage();
            const res=await page.goto(base+'/@auditfree',{waitUntil:'load',timeout:30000});
            assert.equal(res.status(),200);
            await page.locator('body[data-lf-contrast-ready=true]').waitFor({timeout:10000});
            const title=page.locator('h1').first(),button=page.locator('.button-entrance .button').first();
            assert.equal((await title.textContent()).trim(),'auditfree'); // theme may render uppercase
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
            // Independent contrast audit of the original failing text and button, including gradient stops.
            async function sample(locator){
              return locator.evaluate(el=>{
                function rgb(v){const x=v.match(/[\d.]+/g)?.map(Number);return x&&x.length>=3?[x[0],x[1],x[2],x[3]??1]:null}
                function luminance(v){return v.slice(0,3).map(x=>{x/=255;return x<=.04045?x/12.92:((x+.055)/1.055)**2.4}).reduce((a,x,i)=>a+x*[.2126,.7152,.0722][i],0)}
                const fg=rgb(getComputedStyle(el).color);let samples=[];
                for(let n=el;n;n=n.parentElement){
                  const st=getComputedStyle(n);
                  if(Number(st.opacity)<.999)throw Error('Text or ancestor has reduced opacity');
                  if(samples.length)continue;
                  if(st.backgroundImage!=='none'){
                    if(st.backgroundImage.includes('url('))throw Error('Image behind unprotected text');
                    const stops=(st.backgroundImage.match(/rgba?\([\d.,\s]+\)/g)||[]).map(rgb);
                    if(stops.length<2||stops.some(x=>!x||x[3]!==1))throw Error('Unresolved background gradient');
                    for(let i=1;i<stops.length;i++)for(let j=0;j<=20;j++)samples.push(stops[i-1].map((v,k)=>v+(stops[i][k]-v)*j/20));
                  }else{
                    const bg=rgb(st.backgroundColor);
                    if(bg&&bg[3]===1)samples=[bg];
                    else if(bg&&bg[3]!==0)throw Error('Unresolved translucent surface');
                  }
                }
                if(!samples.length)samples=[[255,255,255,1]];
                if(!fg||fg[3]!==1)throw Error('Unresolved foreground');
                const a=luminance(fg);
                return {min:Math.min(...samples.map(b=>{const z=luminance(b);return (Math.max(a,z)+.05)/(Math.min(a,z)+.05)})),foreground:getComputedStyle(el).color,adjusted:el.dataset.lfContrastAdjusted==='true'};
              });
            }
            const titleContrast=await sample(title),buttonContrast=await sample(button);
            assert(titleContrast.min>=4.5&&buttonContrast.min>=4.5,JSON.stringify({titleContrast,buttonContrast}));
            if(name==='custom-image'){
              assert((await page.locator('body').evaluate(e=>getComputedStyle(e).backgroundImage)).includes('2_step04qa.png'),'Image fixture not rendered');
              assert(titleContrast.adjusted,'Unknown background image did not receive local protection');
            }
            await button.hover();
            await page.waitForTimeout(350);
            const hoverContrast=await sample(button);
            assert(hoverContrast.min>=4.5,'Hovered button contrast '+hoverContrast.min);
            const axe=await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa']).analyze();
            const violations=axe.violations.map(v=>({id:v.id,nodes:v.nodes.map(n=>({target:n.target,reason:n.failureSummary})).slice(0,5)}));
            results.push({engine,name,width,scheme,status:violations.length?'FAIL':'PASS',violations,contrast:{title:titleContrast,button:buttonContrast,hover:hoverContrast},incomplete:axe.incomplete.map(v=>v.id)});
          }catch(e){results.push({engine,name,status:'FAIL',error:String(e.message).slice(0,500)});}
          finally{await ctx.close();}
        }
      }catch(e){results.push({engine,status:'FAIL',error:String(e.message).slice(0,1000)});}
      finally{if(browser)await browser.close();}
    }
  }finally{
    sql("import sqlite3,sys,json;c=sqlite3.connect(sys.argv[1]);c.execute('update users set theme=?,image=? where id=2 and littlelink_name=?',(*json.loads(sys.argv[2]),'auditfree'));c.commit()",[before]);
    if(fs.existsSync(image))fs.unlinkSync(image);
    if(permissions===null)fs.unlinkSync(file);else fs.writeFileSync(file,permissions);
  }
  return {status:results.length===24&&results.every(r=>r.status==='PASS')?'PASS':'FAIL',results};
}
module.exports={run};
