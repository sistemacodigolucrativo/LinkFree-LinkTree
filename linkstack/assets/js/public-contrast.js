/* WCAG AA guard for public profile text. Never writes user preferences.
 * Retain readable foregrounds and backgrounds. Unknown imagery gets a local
 * opaque surface, not a global theme replacement. No third-party requests. */
(function () {
  'use strict';
  const root = document.querySelector('body.lf-public-profile');
  if (!root) return;
  const selector = 'h1,.description-parent p,.description-parent a,.button-entrance .button,.dynamic-contrast,.footer a,.social-icon,.sharebutton';
  const tracked = new WeakMap();
  const forced = matchMedia('(forced-colors: active)');
  const black = [0,0,0,1], white = [255,255,255,1];
  function rgba(s) {
    const m = /^rgba?\(([\d.,\s]+)\)$/.exec(s);
    if (!m) return null;
    const a = m[1].split(',').map(x => Number(x.trim()));
    if (a.length !== 3 && a.length !== 4 || a.some(x=>!Number.isFinite(x))) return null;
    return [a[0],a[1],a[2],a[3] ?? 1];
  }
  function lum(c) {
    const v=c.slice(0,3).map(n=>{n/=255;return n<=.04045?n/12.92:((n+.055)/1.055)**2.4;});
    return .2126*v[0]+.7152*v[1]+.0722*v[2];
  }
  function composite(f,b) { return [0,1,2].map(i=>f[i]*f[3]+b[i]*(1-f[3])).concat(1); }
  function ratio(a,b) { const x=lum(a),y=lum(b);return (Math.max(x,y)+.05)/(Math.min(x,y)+.05); }
  // Conservative RGB channel envelope for ordinary opaque sRGB gradients.
  // Other images, alpha stops and unsupported color spaces are not assumed safe.
  function backgrounds(el) {
    const layers=[];
    for(let n=el;n;n=n.parentElement) {
      const style=getComputedStyle(n);
      const bg=rgba(style.backgroundColor);
      if(style.backgroundImage!=='none') {
        const image=style.backgroundImage;
        if(!/^(?:repeating-)?(?:linear|radial|conic)-gradient\(/.test(image) ||
           /url\(|image-set\(|\bin\s+(?:ok|lab|lch|hsl|hwb|display-p3)/i.test(image)) return null;
        const matches=image.match(/rgba?\([\d.,\s]+\)/g)||[];
        const stops=matches.map(rgba);
        // Reject unparsed color functions, multiple images, and translucent stops.
        const rest=image.replace(/rgba?\([\d.,\s]+\)/g,'');
        if(stops.length<2||stops.some(c=>!c||c[3]!==1)|| /(?:gradient|url|color|lab|lch|hsl|hwb)\(/i.test(rest.replace(/^(?:repeating-)?(?:linear|radial|conic)-gradient\(/,''))) return null;
        const lo=[0,1,2].map(i=>Math.min(...stops.map(c=>c[i]))).concat(1);
        const hi=[0,1,2].map(i=>Math.max(...stops.map(c=>c[i]))).concat(1);
        return [lo,hi].map(c=>layers.reduceRight((b,f)=>composite(f,b),c));
      }
      if(bg && bg[3]>0) {
        if(bg[3]===1) return [layers.reduceRight((b,f)=>composite(f,b),bg)];
        layers.push(bg);
      }
    }
    // Default browser canvas (light unless an explicit background is set).
    return [layers.reduceRight((b,f)=>composite(f,b),white)];
  }
  function safe(f,bgs) {
    if(!f||!bgs||!bgs.length) return false;
    const l=lum(f), vals=bgs.map(lum);
    if(bgs.length>1 && l>=Math.min(...vals) && l<=Math.max(...vals)) return false;
    return bgs.every(b=>ratio(composite(f,b),b)>=4.5);
  }
  function saveSet(el,property,value,entry) {
    const old=[el.style.getPropertyValue(property),el.style.getPropertyPriority(property)];
    el.style.setProperty(property,value,'important');
    entry[property]=[...old,el.style.getPropertyValue(property)];
  }
  function reset(el) {
    const old=tracked.get(el);
    if(old) for(const [key,[value,priority,ours]] of Object.entries(old)) {
      if(el.style.getPropertyValue(key)===ours && el.style.getPropertyPriority(key)==='important') {
        if(value) el.style.setProperty(key,value,priority);else el.style.removeProperty(key);
      }
    }
    tracked.delete(el);
    el.removeAttribute('data-lf-contrast-adjusted');
  }
  function protect() {
    const elements=[...root.querySelectorAll(selector)];
    elements.forEach(reset);
    if(forced.matches) return;
    for(const el of elements) {
      if(!el.getClientRects().length||getComputedStyle(el).visibility!=='visible') continue;
      const style=getComputedStyle(el), fg=rgba(style.color), bgs=backgrounds(el);
      if(safe(fg,bgs)) continue;
      const entry={};
      if(safe(black,bgs)||safe(white,bgs)) {
        const chosen=safe(black,bgs)?black:white;
        saveSet(el,'color',chosen===black?'#000000':'#ffffff',entry);
      } else {
        // Preserve the original text if opaque and readable on either surface.
        const preserve=fg&&fg[3]===1&&(safe(fg,[black])||safe(fg,[white]));
        const dark=preserve?safe(fg,[black]):true;
        saveSet(el,'background-image','none',entry);
        saveSet(el,'background-color',dark?'#000000':'#ffffff',entry);
        if(!preserve) saveSet(el,'color',dark?'#ffffff':'#000000',entry);
      }
      tracked.set(el,entry);
      el.setAttribute('data-lf-contrast-adjusted','true');
    }
    root.setAttribute('data-lf-contrast-ready','true');
  }
  let queued=false;
  function schedule() {
    if(queued)return;
    queued=true;
    requestAnimationFrame(()=>{queued=false;protect();});
  }
  // Re-evaluate only on visual state changes, never poll or mutate stored data.
  for(const name of ['focusin','focusout','pointerover','pointerout']) root.addEventListener(name,schedule,{passive:true});
  addEventListener('resize',schedule,{passive:true});
  addEventListener('load',schedule,{once:true});
  for(const q of [matchMedia('(prefers-color-scheme: dark)'),forced]) q.addEventListener('change',schedule);
  protect();
})();
