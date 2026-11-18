/* Prévia local dos tipos de bloco. Somente DOM/CSS: sem requisições de gravação. */
(function () {
  "use strict";

  const escapeAttribute = (value) => String(value || "").replace(/[&<>"']/g, (character) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
  }[character]));

  function button(text, kind, iconName, base) {
    const icon = iconName
      ? '<img class="icon hvr-icon" alt="" src="' +
        escapeAttribute(base + "/" + iconName + ".svg") + '">'
      : "";
    return '<div class="button-entrance"><span class="button button-' + kind +
      ' button-hover icon-hover">' + icon + text + '</span></div>';
  }

  function sampleFor(kind, iconBase) {
    switch (kind) {
      case "predefined":
        return button("Site predefinido", "default", "website", iconBase);
      case "link":
        return button("Meu site", "custom", "custom", iconBase);
      case "vcard":
        return button("Salvar meu contato", "default", "vcard", iconBase);
      case "email":
        return button("Enviar e-mail", "default", "email", iconBase);
      case "telephone":
        return button("Ligar agora", "default", "phone", iconBase);
      case "heading":
        return '<div class="fadein"><h2>Meu título</h2></div>';
      case "spacer":
        return '<div class="lf-preview-guide" aria-hidden="true"></div>' +
          '<div class="button-spacer" style="height:40px"></div>' +
          '<div class="lf-preview-guide" aria-hidden="true"></div>';
      case "text":
        return '<div class="fadein"><span>Este é um exemplo de texto apresentado na página pública.</span></div>';
      default:
        return button("Bloco personalizado", "default", "website", iconBase);
    }
  }

  function documentFor(kind, config) {
    const brands = config.previewBrandCss
      ? '<link rel="stylesheet" href="' + escapeAttribute(config.previewBrandCss) + '">'
      : '';
    const skeleton = config.previewSkeletonCss
      ? '<link rel="stylesheet" href="' + escapeAttribute(config.previewSkeletonCss) + '">'
      : '';
    const html = sampleFor(kind, (config.previewIconBase || "").replace(/\/+$/, ""));
    return '<!doctype html><html lang="pt-BR"><head>' +
      '<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' +
      '<style>*,*::before,*::after{box-sizing:border-box}html{font-size:16px}body{margin:0;background:#fff;color:#18243b;font:16px/1.45 sans-serif}' +
      '.lf-preview-page{width:100%;min-height:100px;padding:15px 9px;text-align:center}' +
      '.button{display:inline-flex;align-items:center;justify-content:center;gap:5px;max-width:100%;min-height:48px;white-space:normal;' +
      'padding:11px 12px;border-radius:8px;background:#0085ff;color:#fff;font-size:16px;font-weight:700}' +
      '.button-custom{background:linear-gradient(135deg,#24243e,#302b63);color:#fff}' +
      '.button img.icon{width:20px;height:20px;object-fit:contain}h2{font-size:22px;line-height:1.3}span{overflow-wrap:anywhere}' +
      '</style>' + brands + skeleton +
      '<style>html,body{width:100%!important;min-width:0!important;max-width:100%!important;overflow-x:hidden!important}' +
      'body{margin:0!important;min-height:0!important} .lf-preview-page{width:100%;max-width:100%;min-height:100px;' +
      'padding:15px 9px!important;text-align:center;overflow-wrap:anywhere}' +
      '.lf-preview-page .button{display:inline-flex!important;align-items:center;justify-content:center;' +
      'max-width:100%!important;width:min(300px,100%)!important;height:auto!important;min-height:48px;' +
      'box-sizing:border-box;white-space:normal!important;margin:0 auto!important;pointer-events:none!important}' +
      '.lf-preview-page .button img.icon{flex:0 0 20px;width:20px;height:20px;object-fit:contain}' +
      '.lf-preview-page h2{margin:8px 0 12px;overflow-wrap:anywhere}' +
      '.lf-preview-page .fadein{animation:none!important;opacity:1!important;visibility:visible!important}' +
      '.lf-preview-page .button-entrance{animation:none!important;opacity:1!important;visibility:visible!important}' +
      '.lf-preview-guide{height:1px;margin:0 auto;width:74%;border-top:1px dashed rgba(119,133,154,.65)}' +
      '.lf-preview-page .button-spacer{height:40px!important}' +
      '@media(prefers-reduced-motion:reduce){*,*::before,*::after{animation:none!important;transition:none!important}}' +
      '</style></head><body><main class="lf-preview-page">' + html +
      '</main></body></html>';
  }

  function init() {
    const root = document.querySelector(".lf-blocks-page");
    if (!root || root.dataset.previewInitialized === "true") return;
    root.dataset.previewInitialized = "true";
    const config = root.dataset;

    root.querySelectorAll(".lf-block-preview-toggle").forEach((toggle) => {
      toggle.addEventListener("click", () => {
        const card = toggle.closest(".lf-block-card");
        if (!card) return;
        const panel = card.querySelector(".lf-block-preview-panel");
        const frame = panel && panel.querySelector(".lf-block-preview-frame");
        if (!panel || !frame) return;

        const opening = toggle.getAttribute("aria-expanded") !== "true";
        if (opening && !frame.hasAttribute("srcdoc")) {
          frame.srcdoc = documentFor(card.dataset.blockKind, config);
        }

        toggle.setAttribute("aria-expanded", String(opening));
        panel.setAttribute("aria-hidden", String(!opening));
        if (opening) panel.removeAttribute("inert");
        else panel.setAttribute("inert", "");
        card.classList.toggle("is-preview-open", opening);
      });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init, { once: true });
  } else {
    init();
  }
})();
