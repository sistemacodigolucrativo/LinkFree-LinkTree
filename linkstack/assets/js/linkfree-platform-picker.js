/* Plataforma pré-definida: expansão -> prévia com CSS público -> seleção explícita.
 * A opção nativa permanece no formulário e é a fonte de verdade do POST.
 */
(function () {
  'use strict';

  const htmlEscape = (value) => String(value == null ? '' : value).replace(/[&<>"']/g, (ch) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[ch]));
  const iconName = (value) => {
    const name = String(value || '').replace(/^default /, '');
    return /^[a-z0-9_-]+$/i.test(name) ? name : 'website';
  };

  function iconSources(wrapper, value) {
    const filename = iconName(value);
    const base = (wrapper.dataset.iconBase || '').replace(/\/+$/, '');
    const customBase = (wrapper.dataset.customIconBase || '').replace(/\/+$/, '');
    const ext = wrapper.dataset.customIconExtension || '.svg';
    const fallback = base + '/website.svg';
    return {
      preferred: customBase
        ? customBase + '/' + encodeURIComponent(filename) + ext
        : base + '/' + encodeURIComponent(filename) + '.svg',
      official: base + '/' + encodeURIComponent(filename) + '.svg',
      fallback: fallback
    };
  }

  function icon(wrapper, value) {
    const img = document.createElement('img');
    const sources = iconSources(wrapper, value);
    img.className = 'lf-platform-icon';
    img.alt = '';
    img.loading = 'lazy';
    img.src = sources.preferred;
    img.addEventListener('error', () => {
      if (img.src !== sources.official) { img.src = sources.official; return; }
      if (img.src !== sources.fallback) { img.src = sources.fallback; return; }
      img.hidden = true;
    });
    return img;
  }

  function previewDocument(wrapper, value, title) {
    const name = String(value || '');
    // Button names originate from the backend's predefined option values.
    const safeClass = /^[a-z0-9_ -]+$/i.test(name) ? name : 'default';
    const assets = iconSources(wrapper, name);
    const sheet = (url) => url ? '<link rel="stylesheet" href="' + htmlEscape(url) + '">' : '';
    const img = '<img class="icon hvr-icon" alt="" src="' + htmlEscape(assets.preferred) + '" ' +
      'onerror="this.onerror=null;this.src=\'' + htmlEscape(assets.official) + '\'">';
    // No links or scripts run in this sandboxed iframe.
    return '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">' +
      '<meta name="viewport" content="width=device-width, initial-scale=1">' +
      '<style>*,*:before,*:after{box-sizing:border-box}body{margin:0;background:#fff;color:#18243b}' +
      '.lf-platform-stage{min-height:100px;padding:14px 8px;display:grid;place-items:center;overflow:hidden}' +
      '.lf-platform-stage .button{max-width:100%;box-sizing:border-box;overflow-wrap:anywhere;white-space:normal}' +
      '.lf-platform-stage .icon{max-width:28px;max-height:28px}' +
      '@media(prefers-reduced-motion:reduce){*,*:before,*:after{animation:none!important;transition:none!important}}' +
      '</style>' + sheet(wrapper.dataset.skeletonCss) + sheet(wrapper.dataset.brandCss) +
      '</head><body><div class="lf-platform-stage"><span class="button button-' +
      htmlEscape(safeClass) + ' button-hover icon-hover">' + img + ' ' +
      htmlEscape(title) + '</span></div></body></html>';
  }

  function init(host) {
    (host || document).querySelectorAll('.lf-platform-picker:not(.is-enhanced)').forEach((wrapper) => {
      const select = wrapper.querySelector('select[name="button"]');
      if (!select) return;
      const options = [...select.options].filter((opt) => opt.value && !opt.disabled);
      const seen = new Set();
      const platforms = options.filter((opt) => {
        if (seen.has(opt.value)) return false;
        seen.add(opt.value);
        return true;
      });
      if (!platforms.length) return;

      const panel = document.createElement('div');
      panel.className = 'lf-platform-enhanced';
      const trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.id = 'lf-platform-trigger';
      trigger.className = 'lf-platform-trigger';
      trigger.setAttribute('aria-expanded', 'false');
      trigger.setAttribute('aria-controls', 'lf-platform-options');
      trigger.setAttribute('aria-haspopup', 'true');
      trigger.setAttribute('aria-required', 'true');

      const triggerText = document.createElement('span');
      triggerText.className = 'lf-platform-trigger-text';
      const triggerIcon = document.createElement('span');
      triggerIcon.className = 'lf-platform-trigger-icon';
      trigger.append(triggerIcon, triggerText);
      const chevron = document.createElement('span');
      chevron.className = 'lf-platform-chevron';
      chevron.setAttribute('aria-hidden', 'true');
      chevron.textContent = '⌄';
      trigger.append(chevron);
      panel.append(trigger);

      const list = document.createElement('div');
      list.className = 'lf-platform-options';
      list.id = 'lf-platform-options';
      list.setAttribute('role', 'group');
      list.setAttribute('aria-label', 'Plataformas disponíveis');
      list.hidden = true;
      panel.append(list);

      const notice = document.createElement('p');
      notice.className = 'lf-platform-feedback';
      notice.setAttribute('role', 'status');
      notice.setAttribute('aria-live', 'polite');
      panel.append(notice);

      const rows = [];
      const closeRows = (except) => rows.forEach(({ button, details }) => {
        if (button === except) return;
        button.setAttribute('aria-expanded', 'false');
        details.hidden = true;
      });
      const closeMenu = () => {
        list.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        closeRows();
      };
      const reflectSelected = () => {
        const option = [...select.options].find((o) => o.value === select.value);
        triggerText.textContent = option && option.value ? option.textContent.trim() : 'Selecione uma plataforma';
        triggerIcon.replaceChildren();
        if (option && option.value) triggerIcon.append(icon(wrapper, option.value));
      };

      platforms.forEach((opt, index) => {
        const row = document.createElement('div');
        row.className = 'lf-platform-option';
        const expand = document.createElement('button');
        expand.className = 'lf-platform-expand';
        expand.type = 'button';
        expand.setAttribute('aria-expanded', 'false');
        expand.setAttribute('aria-controls', 'lf-platform-details-' + index);
        expand.append(icon(wrapper, opt.value));
        const text = document.createElement('span');
        text.className = 'lf-platform-name';
        text.textContent = opt.textContent.trim();
        expand.append(text);
        const arrow = document.createElement('span');
        arrow.className = 'lf-platform-chevron';
        arrow.setAttribute('aria-hidden', 'true');
        arrow.textContent = '⌄';
        expand.append(arrow);
        row.append(expand);

        const details = document.createElement('div');
        details.className = 'lf-platform-details';
        details.id = 'lf-platform-details-' + index;
        details.hidden = true;
        const frame = document.createElement('iframe');
        frame.className = 'lf-platform-preview';
        frame.title = 'Pré-visualização de ' + opt.textContent.trim();
        frame.setAttribute('sandbox', '');
        frame.setAttribute('tabindex', '-1');
        frame.setAttribute('referrerpolicy', 'no-referrer');
        details.append(frame);
        const confirm = document.createElement('button');
        confirm.className = 'btn btn-primary lf-platform-confirm';
        confirm.type = 'button';
        confirm.textContent = 'Selecionar ' + opt.textContent.trim();
        confirm.addEventListener('click', () => {
          select.value = opt.value;
          select.dispatchEvent(new Event('change', { bubbles: true }));
          reflectSelected();
          notice.textContent = opt.textContent.trim() + ' selecionado.';
          closeMenu();
          trigger.focus();
        });
        details.append(confirm);
        row.append(details);
        list.append(row);
        rows.push({ button: expand, details });

        expand.addEventListener('click', () => {
          const opening = expand.getAttribute('aria-expanded') !== 'true';
          closeRows(expand);
          expand.setAttribute('aria-expanded', String(opening));
          details.hidden = !opening;
          if (opening) {
            if (!frame.hasAttribute('srcdoc')) {
              frame.srcdoc = previewDocument(wrapper, opt.value, opt.textContent.trim());
            }
            notice.textContent = 'Prévia de ' + opt.textContent.trim() + '. Confirme para selecionar.';
          } else {
            notice.textContent = '';
          }
        });
      });

      trigger.addEventListener('click', () => {
        const opening = list.hidden;
        list.hidden = !opening;
        trigger.setAttribute('aria-expanded', String(opening));
        if (!opening) closeRows();
      });
      panel.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          if (!list.hidden) { event.preventDefault(); closeMenu(); trigger.focus(); }
          return;
        }
        const buttons = rows.map((entry) => entry.button);
        const current = buttons.indexOf(document.activeElement);
        if (current < 0) return;
        let next = -1;
        if (event.key === 'ArrowDown') next = (current + 1) % buttons.length;
        else if (event.key === 'ArrowUp') next = (current - 1 + buttons.length) % buttons.length;
        else if (event.key === 'Home') next = 0;
        else if (event.key === 'End') next = buttons.length - 1;
        if (next >= 0) { event.preventDefault(); buttons[next].focus(); }
      });

      wrapper.append(panel);
      wrapper.classList.add('is-enhanced');
      select.tabIndex = -1;
      select.required = false; // The custom trigger receives validation and focus.
      const label = document.querySelector('label[for="predefined-button"]');
      if (label) label.htmlFor = trigger.id;
      select.addEventListener('change', reflectSelected);
      reflectSelected();

      const form = wrapper.closest('form');
      if (form) form.addEventListener('submit', (event) => {
        if (select.value) return;
        event.preventDefault();
        notice.textContent = 'Selecione uma plataforma antes de salvar.';
        closeMenu();
        trigger.focus();
      });
      document.addEventListener('pointerdown', function outside(event) {
        if (!wrapper.isConnected) {
          document.removeEventListener('pointerdown', outside);
        } else if (!wrapper.contains(event.target)) {
          closeMenu();
        }
      });
    });
  }

  window.LinkFreePlatformPicker = { init };
})();
