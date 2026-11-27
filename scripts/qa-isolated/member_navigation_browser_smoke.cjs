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
  const duplicates=await page.locator('main.main-content h1,main.main-content h2,main.main-content h3,main.main-content h4')
    .allInnerTexts();
  assert.equal(duplicates.filter(s=>s.trim()===label).length,0,
    'Duplicate heading outside Topbar: '+route+' '+label);
  return metrics;
}
async function assertAxe(page, selector) {
  const report=await new AxeBuilder({page}).include(selector)
    .withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa']).analyze();
  const violations=report.violations.map(v=>({id:v.id,impact:v.impact,
    targets:v.nodes.slice(0,3).map(n=>n.target),
    details:v.nodes.slice(0,3).map(n=>String(n.failureSummary||'').slice(0,600))}));
  assert.equal(violations.length,0,'Scoped axe violations: '+JSON.stringify(violations));
  return {violations:0,incomplete:report.incomplete.map(v=>v.id)};
}
async function linksFlow(page,width,mode) {
  await verifyTopbar(page,'/studio/links','Links e Blocos',width);
  const usage=page.locator('.alert.alert-info[role="status"]').first();
  assert.equal(await usage.count(),1,'Free usage notice should be visible');
  const notice=await usage.boundingBox();
  const linkCard=await page.locator('.studio-links-page .links-section-card').first().boundingBox();
  assert(notice && linkCard,'Missing usage notice or links card geometry');
  assert(linkCard.y >= notice.y+notice.height+7,
    'Free notice overlaps the card at '+width+'px: '+JSON.stringify({notice,linkCard}));
  assert.equal(await page.locator('#studio-my-links-title').count(),0,
    'Legacy duplicated title must be absent');
  const action=page.locator('.studio-links-page .lf-links-actions a.btn-primary');
  assert.equal(await action.count(),1,'Exactly one card CTA expected');
  assert.equal((await action.innerText()).trim(),'+ Bloco-Link');
  assert((await action.getAttribute('aria-label')||'').includes('+ Bloco-Link'));
  assert.equal(new URL(await action.getAttribute('href'),base).pathname,'/studio/blocks');
  if(width===375) {
    await check('Member Topbar WCAG scope',()=>assertAxe(page,'.lf-member-titled-nav'));
    await check('Links and Blocks CTA WCAG scope',()=>assertAxe(page,'.lf-links-actions'));
  }
  await action.focus();
  assert(await action.evaluate(el=>document.activeElement===el),'CTA is not keyboard focusable');
  await page.keyboard.press('Enter');
  await page.waitForURL('**/studio/blocks',{timeout:15000});
  assert.equal((await page.locator('.lf-member-nav-copy').innerText()).trim(),'Gerenciar Blocos');
  await page.goBack({waitUntil:'domcontentloaded',timeout:15000});
  assert.equal(new URL(page.url()).pathname,'/studio/links','Browser back must restore links');
  await page.locator('.studio-links-page .lf-links-actions a.btn-primary').click();
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
    for(const [width,mode] of [[320,'dark'],[375,'light'],[575,'dark'],[576,'light'],[768,'dark'],
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
        if(width===375 || width===1280) {
          await check('Sticky topbar, menu keyboard and back '+width,async()=>{
            await page.goto(base+'/studio/links',{waitUntil:'load'});
            const bar=page.locator('.mobile-unified-nav');
            const title=page.locator('.lf-nav-menu');
            const sidebar=page.locator('.sidebar-default');
            const back=page.locator('.lf-nav-back');
            assert.equal(await title.getAttribute('data-toggle'),'sidebar');
            assert.equal(await back.getAttribute('data-toggle'),null);
            const before=await sidebar.evaluate(el=>el.classList.contains('sidebar-mini'));
            await title.focus();
            await page.keyboard.press('Enter');
            await page.waitForFunction(state=>document.querySelector('.sidebar-default').classList.contains('sidebar-mini')!==state,before);
            await title.press('Enter');
            await page.waitForFunction(state=>document.querySelector('.sidebar-default').classList.contains('sidebar-mini')===state,before);
            await page.evaluate(()=>{const el=document.createElement('div');el.id='lf-test-spacer';el.style.height='2000px';document.querySelector('main').append(el);window.scrollTo(0,800)});
            await page.waitForTimeout(100);
            const box=await bar.boundingBox();
            assert(Math.abs(box.y)<2,'Topbar scrolled away: '+JSON.stringify(box));
            await page.evaluate(()=>{document.querySelector('#lf-test-spacer').remove();window.scrollTo(0,0)});
            await page.goto(base+'/studio/blocks',{waitUntil:'load'});
            assert(await back.isEnabled(),'Back control should be enabled for same-origin history');
            assert.equal(await back.locator('svg').evaluate(el=>getComputedStyle(el).animationName),'none','Reduced motion must stop animation');
            await back.focus();
            await page.keyboard.press('Enter');
            await page.waitForURL('**/studio/links');
            return {width,sticky:true,keyboard:true,history:true,reducedMotion:true};
          });
        }
        if(width===375 || width===1280) {
          await check('Premium review form, keyboard and WCAG '+width+' '+mode,async()=>{
            const response=await page.goto(base+'/studio/avaliar',
              {waitUntil:'domcontentloaded',timeout:25000});
            assert.equal(response?.status(),200);
            assert(await page.locator('.lf-review-shell').isVisible());
            assert.equal(await page.locator('.lf-rating-input').count(),5);
            assert.equal(await page.locator('input[name="publication_consent"]').count(),1);
            assert.equal(await page.locator('form[action$="/studio/avaliar"] input[name="_token"]').count(),1);
            assert((await page.locator('.lf-review-lead').innerText()).includes('LinkFreeQA'),
              'Review form must use the APP_NAME configured in fixture');
            await page.locator('#lf-rating-4').focus();
            await page.keyboard.press('Space');
            assert.equal(await page.locator('#lf-rating-4').isChecked(),true);
            assert.equal(await page.locator('label.lf-selected').count(),4,
              'Selected rating must be visibly reflected in the stars');
            await page.locator('input[name="publication_consent"]').check();
            assert.equal(await page.locator('input[name="publication_consent"]').isChecked(),true);
            const dimensions=await page.evaluate(()=>{
              const css=el=>getComputedStyle(document.querySelector(el));
              const toRgb=value=>value.slice(value.indexOf('(')+1,value.indexOf(')')).split(',').slice(0,3).map(Number);
              const luminosity=rgb=>rgb.map(n=>{
                const channel=n/255;
                return channel<=0.04045?channel/12.92:((channel+0.055)/1.055)**2.4;
              }).reduce((sum,n,index)=>sum+n*[.2126,.7152,.0722][index],0);
              const ratio=(first,second)=>{
                const a=luminosity(toRgb(first)),b=luminosity(toRgb(second));
                return (Math.max(a,b)+0.05)/(Math.min(a,b)+0.05);
              };
              const pageStyle=css('.lf-review-page');
              const card=css('.lf-review-shell');
              const heading=css('.lf-review-shell h1');
              const intro=css('.lf-review-lead');
              const consent=css('.lf-review-consent');
              const star=css('#lf-rating-4 + label');
              return {
                width:innerWidth,scroll:document.documentElement.scrollWidth,
                dark:document.body.classList.contains('dark'),
                background:pageStyle.backgroundColor,
                cardBackground:card.backgroundColor,
                textContrast:ratio(heading.color,card.backgroundColor),
                introContrast:ratio(intro.color,card.backgroundColor),
                consentContrast:ratio(consent.color,consent.backgroundColor),
                starFill:star.color,
                starBackground:star.backgroundColor,
                starContrast:ratio(star.color,star.backgroundColor)
              };
            });
            assert(dimensions.scroll<=width+3,'Review page overflows viewport '+JSON.stringify(dimensions));
            assert.equal(dimensions.dark,mode==='dark','Wrong panel color mode '+JSON.stringify(dimensions));
            assert.equal(dimensions.background,mode==='dark'?'rgb(21, 24, 36)':'rgb(245, 247, 252)',
              'Review page must follow native panel canvas in '+mode+' '+JSON.stringify(dimensions));
            assert.equal(dimensions.cardBackground,mode==='dark'?'rgb(34, 39, 56)':'rgb(255, 255, 255)',
              'Review card must follow native panel surface in '+mode+' '+JSON.stringify(dimensions));
            for(const key of ['textContrast','introContrast','consentContrast','starContrast'])
              assert(dimensions[key]>=4.5,'Insufficient '+key+' '+JSON.stringify(dimensions));
            assert.equal(dimensions.starFill,'rgb(255, 204, 54)',
              'Selected stars must be yellow in '+mode+' '+JSON.stringify(dimensions));
            const axe=await assertAxe(page,'.lf-review-shell');
            await page.screenshot({path:path.join(screenshots,'review-member-'+width+'-'+mode+'.png'),
              fullPage:true,timeout:15000});
            return {width,mode,keyboard:true,consentOptIn:true,appName:true,axe,...dimensions};
          });
        }
        if(width===375) {
          await check('Admin review moderation access and WCAG',async()=>{
            const ctx=await browser.newContext({viewport:{width,height:900},serviceWorkers:'block'});
            try {
              await ctx.route('**/*',route=>{
                try {if(new URL(route.request().url()).origin===base)return route.continue();}
                catch (_) {}
                return route.abort('blockedbyclient');
              });
              const reviewer=await ctx.newPage();
              await login(reviewer,'auditadmin');
              const response=await reviewer.goto(base+'/admin/avaliacoes',
                {waitUntil:'domcontentloaded',timeout:25000});
              assert.equal(response?.status(),200);
              assert.equal(await reviewer.locator('h1').filter({hasText:'Moderação de avaliações'}).count(),1);
              const bell=reviewer.locator('#notification-drop');
              await bell.click();
              const panel=reviewer.locator('.notification-panel');
              await panel.waitFor({state:'visible',timeout:10000});
              const bellText=await panel.innerText();
              assert(!bellText.includes('Está gostando de LinkFreeQA'),
                'Admin must never receive a member review invitation');
              if(await panel.locator('.iq-sub-card').count()===0)
                assert(bellText.includes('Parabéns! Nenhuma notificação pendente.'),
                  'Admin empty bell must use its own message');
              const empty=panel.locator('.lf-notifications-empty');
              if(await empty.count()){
                await reviewer.waitForTimeout(350);
                const colors=await empty.evaluate(el=>{
                  const cs=node=>{const c=getComputedStyle(node);return {
                    color:c.color,background:c.backgroundColor,opacity:c.opacity,
                    visibility:c.visibility,filter:c.filter,display:c.display
                  }};
                  return {self:cs(el),parent:cs(el.parentElement),
                    panel:cs(el.closest('.notification-panel')),bodyClass:document.body.className};
                });
                console.log('ADMIN EMPTY PALETTE',JSON.stringify(colors));
              }
              const axe=await assertAxe(reviewer,'.card-body');
              await reviewer.screenshot({path:path.join(screenshots,'review-admin-375.png'),
                fullPage:true,timeout:15000});
              return {moderatorOnly:true,axe};
            } finally {await ctx.close();}
          });
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
          await check('Platform icons, inline preview and explicit keyboard confirmation',async()=>{
            const response=await page.goto(base+'/studio/add-link?block=predefined',{waitUntil:'domcontentloaded'});
            assert.equal(response?.status(),200);
            await page.locator('.lf-platform-picker.is-enhanced').waitFor({state:'visible',timeout:15000});
            const label=page.locator('label[for="lf-platform-trigger"]');
            assert.equal((await label.innerText()).trim(),'Selecionar ícone do botão');
            const select=page.locator('#predefined-button');
            const options=await select.locator('option').allTextContents();
            for(const name of ['Amazon','Apple Books','Deezer','Discord'])
              assert(options.includes(name),'Missing original platform '+name);
            assert.equal(await select.inputValue(),'','New platform must be unconfirmed');
            const trigger=page.locator('#lf-platform-trigger');
            await trigger.focus();
            await page.keyboard.press('Enter');
            assert.equal(await trigger.getAttribute('aria-expanded'),'true');
            const names=await page.locator('.lf-platform-name').allTextContents();
            assert(names.length>5 && names.every(name=>name.trim()),'Every platform must display a name');
            const store=page.locator('.lf-platform-expand').filter({hasText:'Apple Store'}).first();
            assert.equal(await store.locator('img.lf-platform-icon-light').count(),1,
              'Light Apple Store icon requires a contrasting backdrop');
            const discordIcon=page.locator('.lf-platform-expand').filter({hasText:'Discord'}).first();
            assert.equal(await discordIcon.locator('img.lf-platform-icon-light').count(),1,
              'Light Discord icon requires a contrasting backdrop');
            for(const [name,expected] of [['WhatsApp','rgb(69, 90, 100)'],['YouTube Music','rgb(255, 0, 0)']]) {
              const item=page.locator('.lf-platform-expand').filter({hasText:name}).first();
              await item.click();
              const frame=item.locator('xpath=following-sibling::div//iframe');
              await frame.waitFor({state:'visible'});
              const preview=frame.contentFrame().locator('.button');
              await preview.waitFor({state:'visible'});
              await preview.evaluate(async el=>{await Promise.all([...el.ownerDocument.querySelectorAll('link[rel=stylesheet]')].map(link=>link.sheet?Promise.resolve():new Promise(resolve=>{link.addEventListener('load',resolve,{once:true});link.addEventListener('error',resolve,{once:true});setTimeout(resolve,4000)})))});
              const color=await preview.evaluate(el=>getComputedStyle(el).backgroundColor);
              assert.equal(color,expected,'Wrong default theme preview color for '+name);
            }
            const amazon=page.locator('.lf-platform-expand').filter({hasText:'Amazon'}).first();
            assert((await amazon.locator('img').getAttribute('src')).endsWith('/amazon.svg'));
            await amazon.focus();
            await page.keyboard.press('Enter');
            assert.equal(await amazon.getAttribute('aria-expanded'),'true');
            assert.equal(await select.inputValue(),'','First click cannot select Amazon');
            const preview=amazon.locator('xpath=following-sibling::div//iframe');
            assert(await preview.isVisible(),'Expanded preview not visible');
            const srcdoc=await preview.getAttribute('srcdoc');
            assert(srcdoc.includes('button-amazon') && srcdoc.includes('amazon.svg') &&
              srcdoc.includes('brands.css') && srcdoc.includes('skeleton-'),
              'Preview must reuse public button assets');
            await page.screenshot({path:path.join(screenshots,'member-platform-375-light.png'),
              fullPage:true,timeout:15000});
            const confirm=page.locator('.lf-platform-confirm').filter({hasText:'Amazon'}).first();
            await confirm.focus();
            await page.keyboard.press('Enter');
            assert.equal(await select.inputValue(),'amazon');
            assert.equal(await trigger.getAttribute('aria-expanded'),'false');
            assert((await trigger.innerText()).includes('Amazon'));
            await trigger.click();
            const discord=page.locator('.lf-platform-expand').filter({hasText:'Discord'}).first();
            assert((await discord.locator('img').getAttribute('src')).endsWith('/discord.svg'));
            await discord.click();
            assert.equal(await select.inputValue(),'amazon','Preview cannot change current platform');
            await discord.locator('xpath=following-sibling::div//button').click();
            assert.equal(await select.inputValue(),'discord');
            await trigger.click();
            await page.keyboard.press('Escape');
            assert.equal(await trigger.getAttribute('aria-expanded'),'false');
            return {nativeOptions:options.length,firstClickSelected:false,
              previewUsesOriginalCss:true,keyboardConfirmed:true};
          });
        } else {
          await check('Responsive Links Topbar '+width+' '+mode,
            ()=>verifyTopbar(page,'/studio/links','Links e Blocos',width));
          await check('Responsive Verified badge '+width+' '+mode,
            ()=>verifyTopbar(page,'/studio/verification','Selo Verificado',width));
        }
        await check('CTA keyboard click pointer click and browser back '+width,
          ()=>linksFlow(page,width,mode));
        if(width===1280) {
          await check('Desktop platform icon and theme-faithful preview',async()=>{
            const response=await page.goto(base+'/studio/add-link?block=predefined',{waitUntil:'domcontentloaded'});
            assert.equal(response?.status(),200);
            await page.locator('.lf-platform-picker.is-enhanced').waitFor({state:'visible',timeout:15000});
            const trigger=page.locator('#lf-platform-trigger');
            await trigger.click();
            const deezer=page.locator('.lf-platform-expand').filter({hasText:'Deezer'}).first();
            assert((await deezer.locator('img').getAttribute('src')).endsWith('/deezer.svg'));
            await deezer.click();
            assert.equal(await page.locator('#predefined-button').inputValue(),'');
            const frame=deezer.locator('xpath=following-sibling::div//iframe');
            const markup=await frame.getAttribute('srcdoc');
            assert(markup.includes('button-deezer') && markup.includes('deezer.svg'));
            const bounds=await frame.boundingBox();
            assert(bounds && bounds.x>=-3 && bounds.x+bounds.width<=width+3,
              'Platform preview must fit desktop viewport');
            await page.screenshot({path:path.join(screenshots,'member-platform-1280-dark.png'),
              fullPage:true,timeout:15000});
            return {viewport:width,expanded:true,selectedOnFirstClick:false};
          });
        }
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
