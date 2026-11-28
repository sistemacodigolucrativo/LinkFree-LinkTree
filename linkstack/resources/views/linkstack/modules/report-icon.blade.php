@if(env('ENABLE_REPORT_ICON') == true)
<style>
  .report-pill-container { position:fixed; left:max(1rem, env(safe-area-inset-left)); bottom:max(1rem, env(safe-area-inset-bottom)); z-index:50; display:flex; align-items:center; gap:.5rem; font-family:system-ui,sans-serif; }
  .report-icon-container { width:44px; height:44px; display:grid; place-items:center; border:1px solid rgba(255,255,255,.5); border-radius:50%; color:#fff; background:#8e263c; box-shadow:0 4px 14px rgba(0,0,0,.25); cursor:pointer; flex-shrink:0; }
  .report-icon-container:hover { background:#711a2d; }
  .report-icon-container:focus-visible,.report-text:focus-visible { outline:3px solid #f5b949; outline-offset:3px; }
  .report-expanding-pill { max-width:min(260px,calc(100vw - 5rem)); padding:.7rem 1rem; border:1px solid #e6d5d9; border-radius:1rem; background:#fff; color:#29232a; box-shadow:0 6px 22px rgba(0,0,0,.18); }
  .report-expanding-pill[hidden] { display:none; }
  .report-text { color:#8e263c!important; font-size:.9rem!important; font-weight:700; line-height:1.35; text-decoration:underline; text-underline-offset:.15em; }
  @media (prefers-reduced-motion:no-preference) { .report-expanding-pill:not([hidden]) { animation:lf-report-enter .16s ease-out; } @keyframes lf-report-enter { from { opacity:0; transform:translateX(-6px); } to { opacity:1; transform:translateX(0); } } }
</style>
<div class="report-pill-container">
  <button type="button" aria-label="Denunciar abuso" aria-expanded="false" aria-controls="lf-report-panel" class="report-icon-container">
    <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 21V4m0 1c5-3 8 3 14 0v10c-6 3-9-3-14 0"/></svg>
  </button>
  <div id="lf-report-panel" class="report-expanding-pill" hidden>
    <a href="{{ route('report.form', ['user' => $userinfo->id]) }}" target="_blank" rel="noopener noreferrer" class="report-text">Denunciar abuso</a>
  </div>
</div>
<script>
(function () {
  var container = document.querySelector('.report-pill-container');
  if (!container) return;
  var button = container.querySelector('.report-icon-container'), panel = container.querySelector('.report-expanding-pill');
  function setOpen(open) { panel.hidden = !open; button.setAttribute('aria-expanded', String(open)); }
  button.addEventListener('click', function () { setOpen(panel.hidden); });
  document.addEventListener('click', function (event) { if (!container.contains(event.target)) setOpen(false); });
  document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && !panel.hidden) { setOpen(false); button.focus(); } });
})();
</script>
@endif
