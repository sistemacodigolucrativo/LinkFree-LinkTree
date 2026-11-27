/* Never navigate the panel's back control to a public or external page. */
(function () {
  'use strict';
  const button = document.querySelector('.lf-nav-back');
  if (!button) return;

  function panelUrl(candidate) {
    try {
      const url = new URL(candidate, location.href);
      if (url.origin !== location.origin) return null;
      return /^\/(?:dashboard(?:\/|$)|studio(?:\/|$)|admin(?:\/|$)|update(?:\/|$)|backup\/?$|theme-updater\/?$)/.test(url.pathname) ? url.href : null;
    } catch (_) { return null; }
  }

  function destination() {
    // The Navigation API exposes the actual same-origin history entry when available.
    if (window.navigation && typeof window.navigation.entries === 'function' && window.navigation.currentEntry) {
      const index = window.navigation.currentEntry.index;
      const previous = window.navigation.entries()[index - 1];
      const url = previous && panelUrl(previous.url);
      return url ? {url, history: true} : null;
    }
    // Older browsers cannot inspect history. Navigate only to a safe panel referrer.
    const url = document.referrer && panelUrl(document.referrer);
    return url ? {url, history: false} : null;
  }

  function refresh() { button.disabled = !destination(); }
  button.addEventListener('click', function () {
    const target = destination();
    if (!target) return;
    if (target.history) history.back();
    else location.assign(target.url);
  });
  window.addEventListener('pageshow', refresh);
  refresh();
})();
