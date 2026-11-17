/* LinkFree — fecha a navegação lateral quando a interação ocorre fora dela.
 * Respeita o mesmo estado "sidebar-mini" usado pelo Hope UI.
 * Não intercepta links, formulários nem a própria seta de abrir/fechar.
 */
(function () {
  'use strict';

  function initialize() {
    const sidebar = document.querySelector('.sidebar-default');
    if (!sidebar) return;

    const toggles = Array.from(document.querySelectorAll('[data-toggle="sidebar"]'));
    const syncAccessibility = () => {
      const expanded = !sidebar.classList.contains('sidebar-mini');
      toggles.forEach(button => {
        button.setAttribute('aria-expanded', String(expanded));
        button.setAttribute('aria-label', expanded ? 'Recolher menu lateral' : 'Expandir menu lateral');
      });
    };

    // A biblioteca Hope UI gerencia o toggle; observar o estado evita divergências.
    const observer = new MutationObserver(syncAccessibility);
    observer.observe(sidebar, {attributes: true, attributeFilter: ['class']});
    syncAccessibility();

    document.addEventListener('click', event => {
      if (sidebar.classList.contains('sidebar-mini')) return;
      if (!(event.target instanceof Element)) return;
      if (event.target.closest('.sidebar-default, [data-toggle="sidebar"]')) return;
      sidebar.classList.add('sidebar-mini');
      sidebar.classList.remove('on-resize');
      syncAccessibility();
    });

    // Fechamento por teclado mantém acessibilidade em desktop e mobile.
    document.addEventListener('keydown', event => {
      if (event.key !== 'Escape' || sidebar.classList.contains('sidebar-mini')) return;
      sidebar.classList.add('sidebar-mini');
      sidebar.classList.remove('on-resize');
      syncAccessibility();
      const control = toggles.find(button => button.getClientRects().length);
      if (control) control.focus();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize, {once: true});
  } else {
    initialize();
  }
})();
