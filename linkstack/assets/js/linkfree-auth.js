/* Alternancia entre formularios no mesmo card: mantem rotas e POSTs nativos. */
(function () {
  'use strict';
  function init() {
    const shell = document.querySelector('.lf-login-shell[data-lf-auth-initial]');
    if (!shell) return;
    const views = new Map(Array.from(shell.querySelectorAll('[data-lf-auth-view]'))
      .map(element => [element.getAttribute('data-lf-auth-view'), element]));
    const initial = shell.getAttribute('data-lf-auth-initial') || 'login';
    if (!views.has(initial)) return;

    function show(mode, focus) {
      if (!views.has(mode)) return;
      views.forEach((element, name) => {
        element.hidden = name !== mode;
        element.setAttribute('aria-hidden', name === mode ? 'false' : 'true');
      });
      if (focus) {
        const field = views.get(mode).querySelector('input:not([type="hidden"])');
        if (field) field.focus({preventScroll: true});
      }
    }

    show(initial, false);
    try { history.replaceState(Object.assign({}, history.state, {lfAuthMode: initial}), '', location.href); }
    catch (_) { /* Navegacao tradicional continua disponivel. */ }

    shell.addEventListener('click', event => {
      const link = event.target.closest('a[data-lf-auth-target]');
      if (!link || !shell.contains(link) || event.defaultPrevented ||
          event.button !== 0 || event.ctrlKey || event.metaKey || event.altKey || event.shiftKey) return;
      const mode = link.getAttribute('data-lf-auth-target');
      if (!views.has(mode)) return;
      const destination = new URL(link.href, location.href);
      if (destination.origin !== location.origin) return;
      event.preventDefault();
      show(mode, true);
      try { history.pushState(Object.assign({}, history.state, {lfAuthMode: mode}), '', destination.href); }
      catch (_) { /* A troca do card nao depende do History API. */ }
    });

    window.addEventListener('popstate', event => {
      const mode = event.state && event.state.lfAuthMode;
      if (views.has(mode)) show(mode, false);
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, {once: true});
  else init();
})();
