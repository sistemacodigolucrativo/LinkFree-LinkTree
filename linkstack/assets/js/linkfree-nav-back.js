/* Offer a return only when an HTTP(S) referrer and a previous history entry exist. */
(function () {
  'use strict';
  const button = document.querySelector('.lf-nav-back');
  if (!button) return;
  function refresh() {
    let valid = false;
    try { valid = !!document.referrer && ['http:', 'https:'].includes(new URL(document.referrer).protocol) && history.length > 1; } catch (_) {}
    button.disabled = !valid;
  }
  button.addEventListener('click', () => { if (!button.disabled) history.back(); });
  window.addEventListener('pageshow', refresh);
  refresh();
})();
