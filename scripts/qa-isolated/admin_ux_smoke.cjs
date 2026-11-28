/* Targeted UX acceptance: reuses the synthetic Laravel/Playwright fixture.
 * Never visits production, never uses production accounts, no duplicate full WCAG scan.
 */
'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const {execFileSync}=require('node:child_process');
const {chromium}=require('/tmp/linkfree-qa-node/node_modules/playwright');
const base='http://127.0.0.1:18765';
const fixture='/tmp/linkfree-audit/linkstack';
const db=fixture+'/database/audit.sqlite';
const results=[];
const report='/tmp/linkfree-audit/admin-ux-smoke-results.json';
const shots='/tmp/linkfree-audit/admin-ux-screenshots';
fs.mkdirSync(shots,{recursive:true});
function snapshot(){fs.writeFileSync(report,JSON.stringify({
  sourceSha:process.env.GITHUB_SHA||'',scope:'admin UX / synthetic localhost only',
  results,pass:results.filter(x=>x.status==='PASS').length,
  fail:results.filter(x=>x.status==='FAIL').length
},null,2));}
async function check(name,fn){
  try{const observed=await fn();results.push({name,status:'PASS',observed});console.log('PASS '+name);}
  catch(e){results.push({name,status:'FAIL',error:String(e.stack||e).slice(0,1800)});console.log('FAIL '+name+' '+String(e.message).slice(0,260));}
  snapshot();
}
function sql(query,params=[]){
  const code='import sqlite3,json,sys\nc=sqlite3.connect(sys.argv[1]);print(json.dumps(c.execute(sys.argv[2],json.loads(sys.argv[3])).fetchone()[0]))';
  return JSON.parse(execFileSync('python3',['-c',code,db,query,JSON.stringify(params)],{encoding:'utf8'}));
}
function seedHistories(){
  const py=`import sqlite3,sys
c=sqlite3.connect(sys.argv[1])
for i in range(48):
    c.execute("INSERT INTO verification_audit_events(user_id,actor_id,action,created_at) VALUES(2,1,'requested',datetime('now'))")
c.execute("INSERT INTO abuse_reports(id,reported_user_id,reported_url,category,status,created_at,updated_at) VALUES(800,2,'https://example.test','spam','new',datetime('now'),datetime('now'))")
for i in range(26):
    c.execute("INSERT INTO abuse_report_events(abuse_report_id,actor_user_id,action,to_status,created_at) VALUES(800,1,'created','new',datetime('now'))")
for i in range(25):
    c.execute("INSERT INTO links(id,user_id,button_id,link,title,type,created_at,updated_at) VALUES(?,?,?,?,?,'link',datetime('now'),datetime('now'))",
              (600+i,2,1,'https://example.test/studio-'+str(i),'Studio QA link '+str(i)))
c.commit()`;
  // Seeded audit data is disposable and only used to force multiple pages.
  execFileSync('python3',['-c',py,db]);
}
async function login(page,name='auditadmin'){
  await page.goto(base+'/login',{waitUntil:'domcontentloaded',timeout:25000});
  const form=page.locator('form[action$="/login"]').first();
  await form.locator('[name=email]').fill(name+'@example.test');
  await form.locator('[name=password]').fill('Synthetic-password-123');
  await Promise.all([page.waitForURL('**/dashboard',{timeout:20000}),form.locator('button[type=submit]').click()]);
}
async function run(){
  assert.equal(process.env.QA_EXPECTED_SITE,base);
  assert(fs.realpathSync(fixture).startsWith('/tmp/linkfree-audit/'));
  const env=fs.readFileSync(fixture+'/.env','utf8');
  for(const token of ['APP_ENV=local','DB_CONNECTION=sqlite','MAIL_MAILER=log',
    'DB_DATABASE=/tmp/linkfree-audit/linkstack/database/audit.sqlite'])assert(env.includes(token));
  seedHistories();
  const browser=await chromium.launch({headless:true});
  try{
    for(const [width,mode] of [[375,'dark'],[1280,'light']]){
      const context=await browser.newContext({
        viewport:{width,height:width===375?844:900},
        colorScheme:mode,serviceWorkers:'block',reducedMotion:'reduce'
      });
      await context.route('**/*',route=>{
        try{if(new URL(route.request().url()).origin===base)return route.continue();}catch(_){}
        return route.abort('blockedbyclient');
      });
      await context.addInitScript(mode=>{try{localStorage.setItem('color-mode',mode);}catch(_){}},mode);
      try{
        const page=await context.newPage();
        page.setDefaultTimeout(15000);
        await login(page);
        await check('Public report control and keyboard '+width,async()=>{
          const response=await page.goto(base+'/@auditfree',{waitUntil:'domcontentloaded'});
          assert.equal(response.status(),200);
          const button=page.locator('.report-icon-container'), panel=page.locator('#lf-report-panel');
          assert(await button.isVisible());
          assert.equal(await button.getAttribute('aria-expanded'),'false');
          await button.click();
          assert.equal(await button.getAttribute('aria-expanded'),'true');
          const link=panel.locator('a.report-text');
          assert(await link.isVisible());
          assert((await link.getAttribute('href')).includes('/report'));
          await page.keyboard.press('Escape');
          assert.equal(await button.getAttribute('aria-expanded'),'false');
          assert(await panel.isHidden());
        });
        await check('Sidebar initial state before deferred scripts '+width,async()=>{
          await page.route('**/assets/js/plugins/setting.js*', route=>route.abort());
          await page.route('**/assets/js/hope-ui*.js*', route=>route.abort());
          try {
            await page.goto(base+'/admin/overview',{waitUntil:'domcontentloaded'});
            const sidebar=page.locator('.sidebar-default');
            const mini=await sidebar.evaluate(el=>el.classList.contains('sidebar-mini'));
            assert.equal(mini,width<1025,'Sidebar did not start in its responsive state');
          } finally {
            await page.unroute('**/assets/js/plugins/setting.js*');
            await page.unroute('**/assets/js/hope-ui*.js*');
          }
        });
        await check('Admin contextual topbar '+width,async()=>{
          for (const [url,label] of [['/admin/overview','Administration overview'],['/admin/users','Manage Users'],['/admin/site','Site Customization'],['/admin/avaliacoes','Review moderation']]) {
            const response=await page.goto(base+url,{waitUntil:'domcontentloaded'});
            assert.equal(response.status(),200,'HTTP '+url);
            assert.equal((await page.locator('.lf-nav-menu').innerText()).trim(),label,'Topbar title for '+url);
            if(url==='/admin/overview') assert.equal(await page.locator('.lf-nav-back').count(),0,'Admin dashboard must hide back');
          }
        });
        await check('Blocked account card alignment '+width,async()=>{
          const setBlocked=value=>execFileSync('python3',['-c',"import sqlite3,sys; c=sqlite3.connect(sys.argv[1]); c.execute('update users set block=? where id=3',(sys.argv[2],)); c.commit()",db,value]);
          setBlocked('no');
          const ctx=await browser.newContext({viewport:{width,height:900},serviceWorkers:'block'});
          try {
            await ctx.route('**/*',route=>{try{if(new URL(route.request().url()).origin===base)return route.continue()}catch(_){}return route.abort('blockedbyclient')});
            const blocked=await ctx.newPage();
            await login(blocked,'auditother');
            setBlocked('yes');
            await blocked.goto(base+'/blocked',{waitUntil:'domcontentloaded'});
            assert.equal(new URL(blocked.url()).pathname,'/blocked');
            const card=blocked.locator('.lf-blocked-card');
            assert(await card.isVisible());
            const metrics=await card.evaluate(el=>{
              const b=el.getBoundingClientRect(), mid=b.left+b.width/2;
              const center=node=>{const x=node.getBoundingClientRect();return x.left+x.width/2};
              const mark=el.querySelector('.logo-main'), title=el.querySelector('.logo-title');
              const a=mark.getBoundingClientRect(), t=title.getBoundingClientRect();
              return {mid,brand:(a.left+t.right)/2,heading:center(el.querySelector('h2')),button:center(el.querySelector('button')),overflow:document.documentElement.scrollWidth-innerWidth};
            });
            for(const key of ['brand','heading','button'])assert(Math.abs(metrics[key]-metrics.mid)<3,key+' not centered '+JSON.stringify(metrics));
            assert(metrics.overflow<=2,'Blocked page overflows');
            await Promise.all([blocked.waitForURL(base+'/',{timeout:15000}),card.locator('button[type=submit]').click()]);
            return {width,centered:true,logout:true};
          } finally {await ctx.close();setBlocked('no');}
        });
        await check('Manage Users page '+width+' '+mode,async()=>{
          const response=await page.goto(base+'/admin/users',{waitUntil:'load'});
          assert.equal(response.status(),200);
          const search=page.locator('.lf-users-search #lf-users-search-input');
          const card=page.locator('.lf-users-results-card');
          await search.waitFor({state:'visible'});
          assert.equal(await card.count(),1,'Missing unique users card');
          assert.equal(await card.locator('a[href$="/admin/new-user"]').count(),1,'Missing one create shortcut in card');
          assert.equal(await card.locator('#lf-user-columns-trigger').count(),1,'Column selector not in footer');
          assert.equal(await card.locator('#perPage').count(),1,'Pagination size selection missing');
          assert.equal(await page.locator('form[action$="/admin/new-user"]').count(),0,'Create shortcut submitted instead of navigating');
          const a=await search.boundingBox(), c=await card.boundingBox();
          assert(a.y+a.height<=c.y+2,'Search does not appear ABOVE the card');
          assert.equal(await page.locator('aside[data-panel-context=admin]').count(),1);
          if(width===375){
            const title=page.locator('.lf-mobile-nav-title');
            assert(await title.isVisible(),'Mobile navbar title missing');
            const back=await page.locator('.mobile-unified-nav .lf-nav-back').boundingBox();
            const bell=await page.locator('.mobile-unified-nav #notification-drop').boundingBox();
            const t=await title.boundingBox();
            assert(back.x+back.width<=t.x+5 && t.x+t.width<=bell.x+5,'Title not positioned between back and bell');
            assert.equal(await page.locator('h1').filter({hasText:'Gerenciar Usuários'}).isVisible(),false,'Old mobile title still occupies content');
          }
          const menu=page.locator('.lf-nav-menu'), side=page.locator('.sidebar-default');
          const initiallyMini=await side.evaluate(el=>el.classList.contains('sidebar-mini'));
          await menu.focus();
          await page.keyboard.press('Enter');
          await page.waitForFunction(state=>document.querySelector('.sidebar-default').classList.contains('sidebar-mini')!==state,initiallyMini);
          await menu.press('Enter');
          await page.waitForFunction(state=>document.querySelector('.sidebar-default').classList.contains('sidebar-mini')===state,initiallyMini);
          await page.evaluate(()=>{const el=document.createElement('div');el.id='lf-test-spacer';el.style.height='2000px';document.querySelector('main').append(el);window.scrollTo(0,800)});
          await page.waitForTimeout(100);
          const topbar=await page.locator('.mobile-unified-nav').boundingBox();
          assert(Math.abs(topbar.y)<2,'Admin topbar moved while scrolling: '+JSON.stringify(topbar));
          await page.evaluate(()=>{document.querySelector('#lf-test-spacer').remove();window.scrollTo(0,0)});
          await page.screenshot({path:shots+'/users-'+width+'-'+mode+'.png',fullPage:true});
          if(width===375){
            await search.fill('auditfree');
            await page.waitForFunction(()=>{
              const text=document.querySelector('.lf-users-results-card tbody')?.textContent||'';
              return text.includes('auditfree') && !text.includes('auditadmin');
            },null,{timeout:20000});
            await search.fill('');
            await page.waitForFunction(()=>{
              const text=document.querySelector('.lf-users-results-card tbody')?.textContent||'';
              return text.includes('auditadmin') && text.includes('auditfree');
            },null,{timeout:20000});
          }
          return {status:200,searchAbove:true,columnFooter:true,searchFunctional:width===375,createLink:true,mobileTitle:width===375};
        });
        await check('Column selector slim and GET creation '+width,async()=>{
          await page.locator('#lf-user-columns-trigger').click();
          assert(await page.locator('#lf-user-columns-menu').isVisible(),'Slim dropdown did not open');
          if(width===375){
            const options=page.locator('#lf-user-columns-menu input[value]');
            assert(await options.count()>0,'No selectable columns');
            const first=options.first();
            const initial=await page.locator('.lf-users-results-card thead th').count();
            await first.setChecked(!(await first.isChecked()));
            await page.waitForFunction(count=>
              document.querySelectorAll('.lf-users-results-card thead th').length!==count,
              initial,{timeout:20000});
          }
          await page.locator('.lf-users-add-icon').click();
          await page.waitForURL('**/admin/new-user',{timeout:18000});
          assert.equal(await page.locator('#lf-admin-create-user').count(),1);
          const before=sql('SELECT COUNT(*) FROM users');
          await page.reload({waitUntil:'domcontentloaded'});
          assert.equal(sql('SELECT COUNT(*) FROM users'),before,'Form reload created user');
          return {dropdownOpens:true,GETSafe:true};
        });
        await check('Site hub, exclusive section, sidebar continuity '+width,async()=>{
          await page.goto(base+'/admin/site',{waitUntil:'domcontentloaded'});
          assert.equal(await page.locator('.lf-site-settings-nav a').count()>=5,true);
          assert.equal(await page.locator('.lf-site-form-card').count(),0,'Section rendered below navigation links');
          assert.equal(await page.locator('aside[data-panel-context=admin]').count(),1);
          const link=page.locator('a[href*="section=final"]').first();
          await link.click();
          await page.waitForURL('**/admin/site?section=final',{timeout:20000});
          assert.equal(await page.locator('.lf-site-settings-nav').count(),0,'Section still shows button list');
          assert.equal(await page.locator('aside[data-panel-context=admin]').count(),1,'Duplicate Sidebar');
          assert.equal(await page.locator('.lf-site-section-toolbar .lf-site-close').count(),1);
          assert.equal(await page.locator('#lf-site-final_title').count(),1);
          await page.locator('.lf-site-close').click();
          await page.waitForURL('**/admin/site',{timeout:18000});
          assert.equal(await page.locator('.lf-site-form-card').count(),0);
          return {hubOnly:true,exclusiveSection:true,sidebarOnce:true,closeReturns:true};
        });
        await check('Footer functions are distinct '+width,async()=>{
          await page.goto(base+'/admin/site?section=footer',{waitUntil:'domcontentloaded'});
          const heading=await page.locator('.lf-site-section-toolbar h1').innerText();
          assert(heading.includes('Textos dos links do rodapé'),heading);
          assert.equal(await page.locator('a[href$="/admin/pages"]').count()>=1,true);
          assert.equal(await page.locator('a[href$="/admin/config"]').count()>=1,true);
          const sidebar=page.locator('#sidebar-menu');
          assert.equal(await sidebar.getByText('Páginas institucionais',{exact:true}).count(),1);
          return {textLabels:true,legalPagesLink:true,configLink:true,sidebarRenamed:true};
        });
        if(width===375){
          await check('Member Studio links pagination reuses Sortable page offset',async()=>{
            const memberContext=await browser.newContext({viewport:{width:375,height:844},serviceWorkers:'block'});
            try {
              const member=await memberContext.newPage();
              await login(member,'auditfree');
              let response=await member.goto(base+'/studio/links',{waitUntil:'domcontentloaded'});
              assert.equal(response.status(),200);
              const body=member.locator('#links-table-body');
              const rows=body.locator(':scope > .row[data-id]');
              assert.equal(await rows.count(),20,'First page must render 20 links');
              assert.equal(await body.getAttribute('data-per-page'),'20');
              assert(await member.locator('nav[aria-label="Paginação dos meus links"] a[href*="page=2"]').count()>=1);
              response=await member.goto(base+'/studio/links?page=2',{waitUntil:'domcontentloaded'});
              assert.equal(response.status(),200);
              assert.equal(await rows.count(),7,'Second page must contain remaining 7 links');
              assert.equal(await body.getAttribute('data-page'),'2');
              return {firstPage:20,secondPage:7,sortableOffset:'20'};
            } finally {await memberContext.close();}
          });
          await check('Saving site section returns to hub with toast',async()=>{
            await page.goto(base+'/admin/site?section=final',{waitUntil:'domcontentloaded'});
            const title=page.locator('#lf-site-final_title');
            await title.fill('Título de QA sintética 2026');
            const response=page.waitForResponse(x=>new URL(x.url()).pathname==='/admin/site/content'&&x.request().method()==='POST',{timeout:25000});
            await page.locator('form[action$="/admin/site/content"] button[type=submit]').click();
            assert.equal((await response).status(),302);
            await page.waitForURL('**/admin/site',{timeout:25000});
            assert.equal(await page.locator('.lf-site-form-card').count(),0);
            assert.equal(await page.locator('.lf-site-message-success').count(),1,'Toast success missing');
            await page.goto(base+'/admin/site?section=final',{waitUntil:'domcontentloaded'});
            assert.equal(await page.locator('#lf-site-final_title').inputValue(),'Título de QA sintética 2026');
            return {saved:true,returnedToHub:true,toast:true,persisted:true};
          });
          await check('Independent pagination on badges and abuse histories',async()=>{
            const badgeResponse=await page.goto(base+'/admin/verification',{waitUntil:'domcontentloaded'});
            assert.equal(badgeResponse.status(),200,'Badge history HTTP '+badgeResponse.status()+' '+(await page.locator('body').innerText()).slice(0,210));
            const history=page.locator('[aria-label="Histórico administrativo de verificação"] tbody tr');
            assert.equal(await history.count(),20,'Expected 20 history rows per page; body: '+(await page.locator('body').innerText()).slice(0,250));
            const link=page.locator('a[href*="history_page=2"]').first();
            assert((await link.count())>=1,'History second-page link missing');
            const nextPage=page.waitForNavigation({waitUntil:'domcontentloaded',timeout:25000});
            await link.click();
            const historyResponse=await nextPage;
            assert.equal(historyResponse?.status(),200,'History page 2 HTTP '+historyResponse?.status()+' body: '+(await page.locator('body').innerText()).slice(0,270));
            assert(new URL(page.url()).searchParams.get('history_page')==='2');
            assert.equal(await history.count(),20,'History page2 should have 20 rows, body: '+(await page.locator('body').innerText()).slice(0,270));
            await page.goto(base+'/admin/reports/800',{waitUntil:'domcontentloaded'});
            assert.equal(await page.locator('h2').filter({hasText:'Histórico da denúncia'}).count(),1);
            assert.equal(await page.locator('ol.list-group > li').count(),15);
            assert(await page.locator('a[href*="page=2"]').count()>=1,'Abuse-event history page link missing');
            return {verificationHistory:true,abuseHistory:true,existingQueuePaginationRetained:true};
          });
        }
      }finally{await context.close();}
    }
  }finally{await browser.close();snapshot();}
  const fail=results.filter(x=>x.status==='FAIL');
  console.log('ADMIN UX RESULT: '+(results.length-fail.length)+' passed, '+fail.length+' failed; '+report);
  if(fail.length)process.exitCode=1;
}
run().catch(e=>{results.push({name:'harness',status:'FAIL',error:String(e.stack||e).slice(0,2000)});snapshot();console.error('ADMIN UX FATAL',e);process.exitCode=1;});
