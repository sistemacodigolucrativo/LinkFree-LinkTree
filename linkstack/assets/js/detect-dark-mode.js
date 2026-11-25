// Pre-paint theme bootstrap, shared by guest and authenticated layouts.
// The settings plugin remains the sole owner of user theme controls;
// this script only restores saved mode before the first visible paint.
var colorMode = 'auto';
try {
  var savedColorMode = localStorage.getItem('color-mode');
  if (savedColorMode === 'light' || savedColorMode === 'dark') {
    colorMode = savedColorMode;
  } else if (savedColorMode !== 'auto') {
    localStorage.setItem('color-mode', 'auto');
  }
} catch (_) {
  // Storage may be unavailable (restricted browsing); defer to system preference.
}

(function () {
  'use strict';
  var prefersDark = window.matchMedia &&
    window.matchMedia('(prefers-color-scheme: dark)').matches;
  var shouldBeDark = colorMode === 'dark' || (colorMode === 'auto' && prefersDark);

  // HTML is already available in the head; preserve the dark canvas immediately.
  document.documentElement.classList.toggle('lf-prepaint-dark', !!shouldBeDark);

  function prepaintBody() {
    if (!document.body) return false;
    // A body.dark CSS palette is used by the dashboard and the shared components.
    // Applying it at DOM insertion prevents transient light components while the
    // footer's settings.js has not yet loaded.
    document.body.classList.remove('dark', 'light', 'auto');
    document.body.classList.add(colorMode);
    if (colorMode === 'auto' && shouldBeDark) document.body.classList.add('dark');
    return true;
  }

  if (prepaintBody()) return;
  // Body is inserted only after the head has been parsed. MutationObserver
  // callbacks run before a subsequent rendering opportunity.
  var observer = new MutationObserver(function () {
    if (prepaintBody()) observer.disconnect();
  });
  observer.observe(document.documentElement, {childList: true});
  document.addEventListener('DOMContentLoaded', function () {
    prepaintBody();
    observer.disconnect();
  }, {once: true});
})();
