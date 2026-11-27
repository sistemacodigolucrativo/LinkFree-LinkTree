/* The browser controls the history destination; do not assume a referrer is available. */
(function () {
  'use strict';
  const button = document.querySelector('.lf-nav-back');
  if (!button) return;
  function refresh() {
    button.disabled = history.length <= 1;
  }
  button.addEventListener('click', () => { if (!button.disabled) history.back(); });
  window.addEventListener('pageshow', refresh);
  refresh();
})();
