const {chromium,firefox,webkit}=require('/tmp/lf-browser/node_modules/playwright');
const {AxeBuilder}=require('/tmp/lf-browser/node_modules/@axe-core/playwright');
const fs=require('fs');const out='/tmp/linkfree-audit/docs/homologacao-automatizada/evidencias';
(async()=>{const results={browsers:[],pages:[],layout:[],axe:[],interactions:[]};
for(const [name,type] of Object.entries({chromium,firefox,webkit})){
 let b;try{b=await type.launch({headless:true,timeout:15000});results.browsers.push({name,version:b.version(),launched:true})}catch(e){results.browsers.push({name,launched:false,reason:e.message.slice(0,500)});continue}
 try{
 const ctx=await b.newContext({viewport:{width:390,height:844}});await ctx.route('**/*',r=>new URL(r.request().url()).hostname==='127.0.0.1'?r.continue():r.abort());
 const p=await ctx.newPage();p.setDefaultTimeout(12000);
 async function login(who){await p.goto('http://127.0.0.1:18765/login');await p.locator('form[action$="/login"] input[name=email]').fill(who+'@example.test');await p.locator('form[action$="/login"] input[name=password]').fill('Synthetic-password-123');await Promise.all([p.waitForURL('**/dashboard'),p.locator('form[action$="/login"] button[type=submit]').click()]);results.interactions.push({browser:name,action:'login '+who,pass:true});}
 for(const [role,paths] of [['anon',['/','/login','/register','/report','/@auditfree']],['auditfree',['/studio/referrals','/studio/verification','/studio/links','/studio/page','/studio/theme']],['auditadmin',['/admin/referrals','/admin/verification','/admin/reports','/admin/users']]]){
  if(role!=='anon'){await ctx.clearCookies();await login(role)}
  for(const path of paths){let res=await p.goto('http://127.0.0.1:18765'+path,{waitUntil:'domcontentloaded'});await p.waitForTimeout(250);results.pages.push({browser:name,role,path,status:res.status()});
   if(name!=='chromium')continue;
   for(const mode of ['light','dark']){await p.evaluate(mode=>{document.documentElement.setAttribute('data-bs-theme',mode);document.body.classList.toggle('dark',mode==='dark');document.body.classList.toggle('light',mode==='light');document.documentElement.setAttribute('data-lf-color-mode',mode)},mode);
    for(const width of [320,360,390,768,1024,1440]){await p.setViewportSize({width,height:900});const dims=await p.evaluate(()=>({viewport:innerWidth,scroll:document.documentElement.scrollWidth,body:document.body.scrollWidth}));results.layout.push({path,role,mode,width,...dims,pass:dims.scroll<=width+2});}
   }
   await p.setViewportSize({width:390,height:844});const a=await new AxeBuilder({page:p}).withTags(['wcag2a','wcag2aa']).analyze();results.axe.push({path,role,violations:a.violations.map(v=>({id:v.id,impact:v.impact,description:v.description,nodes:v.nodes.slice(0,5).map(n=>({target:n.target,summary:n.failureSummary}))})),incomplete:a.incomplete.map(v=>v.id)});
   if(['/login','/studio/referrals','/admin/reports'].includes(path))await p.screenshot({path:out+'/chromium-'+path.replaceAll('/','_')+'-390.png',fullPage:true});
  }
 }
 await ctx.close();
 }catch(e){results.interactions.push({browser:name,pass:false,error:e.message})}finally{await b.close()}
 fs.writeFileSync(out+'/browser.json',JSON.stringify(results,null,2));
}fs.writeFileSync(out+'/browser.json',JSON.stringify(results,null,2));console.log(JSON.stringify({browsers:results.browsers,pages:results.pages.length,layout:results.layout.length,overflow:results.layout.filter(x=>!x.pass).length,axeViolations:results.axe.reduce((n,x)=>n+x.violations.length,0),interactions:results.interactions}));})();
