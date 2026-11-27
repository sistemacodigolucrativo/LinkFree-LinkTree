/* Only offer a return when this document has a same-origin referrer. */
(function () {
  'use strict';
  const button = document.querySelector('.lf-nav-back');
  if (!button) return;
  function refresh() {
    let valid = false;
    try { valid = !!document.referrer && new URL(document.referrer).origin === location.origin && history.length > 1; } catch (_) {}
    button.disabled = !valid;
  }
  button.addEventListener('click', () => { if (!button.disabled) history.back(); });
  window.addEventListener('pageshow', refresh);
  refresh();
})();
