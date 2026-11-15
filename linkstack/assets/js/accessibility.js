(() => {
 const updateStates=()=>{
 document.querySelectorAll('[data-setting]').forEach(el=>el.setAttribute('aria-pressed',String(el.classList.contains('active'))));
 document.querySelectorAll('[data-toggle="sidebar"]').forEach(el=>el.setAttribute('aria-expanded',String(!document.querySelector('.sidebar')?.classList.contains('sidebar-mini'))));
};
 document.addEventListener('click',()=>setTimeout(updateStates,0));
 const enhance=()=>{
  document.querySelectorAll('label').forEach((label,i)=>{
   if(label.control) return;
   let next=label.nextElementSibling;
   while(next && !next.matches('input,select,textarea') && !next.querySelector('input,select,textarea')) next=next.nextElementSibling;
   const field=next && (next.matches('input,select,textarea')?next:next.querySelector('input,select,textarea'));
   if(field && field.type!=='hidden'){field.id ||= 'field-accessible-'+i;label.htmlFor=field.id;}
  });
  document.querySelectorAll('[data-setting],[data-toggle="sidebar"],.share-button,[data-bs-target="#staticBackdrop"],[data-bs-target="#offcanvasExample"]').forEach(el=>{
   if(!el.matches('button,input,a[href]')) {el.tabIndex=0;el.setAttribute('role','button');}
   if(!el.getAttribute('aria-label') && !el.textContent.trim()) el.setAttribute('aria-label',el.dataset.value || el.getAttribute('alt') || 'Abrir opções');
  });
  updateStates();
 };
 document.addEventListener('keydown',e=>{const el=e.target;if(el.matches('[role="button"]')&&!el.matches('button,input,a[href]')&&(e.key==='Enter'||e.key===' ')){e.preventDefault();el.click();}});
 enhance();new MutationObserver(enhance).observe(document.body,{childList:true,subtree:true});
})();
