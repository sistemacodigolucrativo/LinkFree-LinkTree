#!/usr/bin/env python3
"""QA de regressão das alterações do Estúdio (UI/UX), sem alterar o backend.

Testes estáticos dos contratos Blade, componentes de preview e CSS das telas.
Para aprovar funcionamento end-to-end, testar na VPS com sessão autenticada.
"""
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1] / "linkstack" / "resources" / "views" / "studio"
links = (ROOT / "links.blade.php").read_text(encoding="utf-8")
theme = (ROOT / "theme.blade.php").read_text(encoding="utf-8")
views = ROOT.parent
dashboard = (views / "panel" / "index.blade.php").read_text(encoding="utf-8")
sidebar = (views / "layouts" / "sidebar.blade.php").read_text(encoding="utf-8")

def require(condition, description):
    if not condition:
        raise AssertionError(description)
    print("OK:", description)

# Links: dois cards independentes; o card principal de fundo foi removido.
require('class="links-workspace"' in links, "Removido o grande cartão externo")
require(re.search(r"<section class='[^']*pre-left[^']*card rounded[^']*'", links),
        "Meus Links possui card separado")
require(re.search(r"<section class='[^']*page-icons-card card rounded[^']*'", links),
        "Ícones de Página possuem card separado")
require(links.index('id="studio-my-links-title"') < links.index('id="studio-page-icons-title"'),
        "Ordem correta de Meus Links e Ícones de Página")
require('id="links-table-body"' in links, "Lista original de links preservada")
require("route('editIcons')" in links, "Formulário de ícones preservado")
require("route('editLink'" in links and "route('deleteLink'" in links,
        "Ações de editar/excluir links preservadas")
require("linksTableOrders" in links, "Ordenação de links preservada")

# FAB: prévia de página real, tela inteira, fecha sem recarregar editor.
require("id=\"studio-links-preview-trigger\"" in links, "Botão flutuante de prévia presente")
require(".studio-links-preview-fab {" in links, "Estilos do botão flutuante presentes")
require(links.count("id=\"studioLinksPreviewModal\"") == 1, "Um único modal fullscreen")
require("modal-dialog modal-fullscreen" in links, "Modal ocupa a tela inteira")
require('id="studio-links-preview-frame"' in links, "Iframe da página pública presente")
require("data-preview-url=\"{{ url('/@'.Auth::user()->littlelink_name) }}\"" in links,
        "URL da página real do próprio usuário")
require('class="studio-links-preview-close"' in links and "data-bs-dismiss=\"modal\"" in links,
        "Botão X exclusivo de fechar modal presente e acessível")
require("Minha página — visualização" not in links and "studioLinksPreviewTitle" not in links,
        "Removida barra superior da prévia; somente X à esquerda")
require("show.bs.modal" in links and "hidden.bs.modal" in links,
        "Carregamento sob demanda e limpeza ao fechar")
require("window.location" not in links and "location.reload()" not in links,
        "Fechamento não recarrega o editor")
require("frPreview1" not in links and "frPreview2" not in links,
        "Prévia embutida antiga totalmente removida")
require('class="pre-right' not in links and 'class="pre-bottom' not in links,
        "Sem colunas de prévia antigas")

# Theme: remoção do card cinza sem quebrar modais/preview funcionais.
require("theme-preview-strip" in theme, "Antigo card de prévia substituído")
require('card-title">{{__(\'messages.Preview\')}}' not in theme,
        "Cabeçalho cinza 'Prévia' removido")
require("theme-preview-current" in theme and "var(--lf-preview-action)" in theme and
        "--lf-preview-action:#ffcc36;" in (views.parents[1] / "assets/css/linkfree-color-modes.css").read_text(encoding="utf-8"),
        "Botão pré-visualizar tema atual amarelo")
require("theme-preview-title" in theme and "theme-select-action" in theme,
        "Título com pincel e seletor azul estão dentro do card de prévia")
require(theme.index("theme-preview-title") < theme.index("theme-select-action") < theme.index("data-theme-preview-url"),
        "Ordem dos controles: título, botão azul, botão amarelo")
require("theme-selection-layout" in theme and 'class="theme-preview-strip"' in theme,
        "Card independente de Seleção de Tema removido")
require("data-theme-preview-url" in theme and 'id="themePreviewModal"' in theme,
        "Pré-visualização de temas e seletor existentes preservados")
require("Duas demonstrações com links, botões, vídeos" not in theme,
        "Descrição das demonstrações removida")
admin_themes = (views / "panel" / "theme.blade.php").read_text(encoding="utf-8")
require('id="theme-upload-form"' not in theme and 'aria-label="Temas GitHub"' not in theme,
        "Ações administrativas removidas da página do membro")
require('id="admin-theme-management"' in admin_themes and 'id="theme-upload-form"' in admin_themes,
        "Gestão e upload de temas disponíveis na área administrativa")
require(admin_themes.count('id="github-theme-updates"') == 1 and admin_themes.count('id="github-theme-status"') == 1,
        "Monitoramento dos temas GitHub transferido sem duplicação")
require("studio-links-sections" in links and "studio-links-sections>.links-section-card" in links,
        "Meus Links e Ícones de Página compartilham contêiner e largura")
require("studio-link-slim" in links and "sortable-handle" in links and "linksTableOrders" in links,
        "Cards slim mantêm controles e drag-and-drop")
require("top-links-slim-item" in dashboard and "overflow-x:auto" in dashboard,
        "Top Clicks em barras compactas com deslocamento horizontal")
require("top-link-title" in dashboard and "top-link-url" in dashboard and "top-link-clicks" in dashboard,
        "Título, link e cliques em linha única nos Top Clicks")
require("welcome-slim" in sidebar and "height:auto;" in sidebar,
        "Card de boas-vindas compactado")
require("mobile-unified-nav" in sidebar and ".mobile-unified-nav .navbar-toggler{display:none!important}" in sidebar,
        "Barra mobile sem menu hambúrguer duplicado")
require("data-toggle=\"sidebar\"" in sidebar and ".mobile-unified-nav .sidebar-toggle" in sidebar,
        "Seta de abertura/fechamento do menu lateral mantida")
require('id="navbarSupportedContent"' in sidebar and 'id="navbarDropdown"' in sidebar,
        "Exibir página e perfil preservados no navbar")
require('id="sidebar-scroll-region"' in sidebar and
        'class="sidebar-body pt-0 data-scrollbar"' not in sidebar,
        "Sidebar usa rolagem vertical independente sem conflito com o plugin")
for group in ("admin-navigation", "nav-overview", "nav-links", "nav-my-page", "nav-appearance", "nav-account"):
    require('id="' + group + '" data-bs-parent="#sidebar-menu"' in sidebar,
            "Acordeão principal com fechamento exclusivo: " + group)
for group in ("admin-users-menu", "admin-site-menu", "admin-themes-menu", "admin-system-menu"):
    require('id="' + group + '" data-bs-parent="#admin-navigation"' in sidebar,
            "Subcategoria administrativa com fechamento exclusivo: " + group)
require(sidebar.count('class="nav-link nav-accordion-toggle') == 10,
        "Todas as dez categorias usam gatilhos de acordeão")
require('id="nav-admin-panel"' in sidebar and "auth()->user()->role === 'admin'" in sidebar,
        "Menu administrativo agrupado e restrito a administradores")
require(sidebar.index('id="nav-admin-panel"') < sidebar.index('messages.Dashboard'),
        "Administração está no topo do menu lateral")
require('id="mail-drop"' not in sidebar and "url('/update')" in sidebar,
        "Atualizações administrativas movidas ao menu Admin")


# Revisão 2026-11: quatro pontos de contraste e compacidade.
palette = (views.parents[1] / "assets" / "css" / "linkfree-color-modes.css").read_text(encoding="utf-8")
guest = (views / "layouts" / "guest.blade.php").read_text(encoding="utf-8")
js = (views.parents[1] / "assets" / "js" / "main-dashboard.js").read_text(encoding="utf-8")
require(".main-content > .iq-banner:not(.hide) + .content-inner" in sidebar,
        "Margem antiga do banner removida: sem sobreposição no mobile")
require("grid-template-columns:44px minmax(0,1fr)" in sidebar,
        "Seta e botões do mobile na mesma linha de uma única barra")
require("position:static!important" in sidebar and "data-toggle=\"sidebar\"" in sidebar,
        "Seta sem posição absoluta e handler de sidebar preservado")
require("width:16px!important;height:16px!important" in links,
        "Ícone visual de seis pontos reduzido")
require("width:15px!important;height:15px!important" in links,
        "Ícones editar/excluir reduzidos e ações mantidas")
require("min-width:40px!important" in links and "min-height:40px!important" in links,
        "Áreas tocáveis compactas e preservadas")
require("frPreview1').src" not in js and "frPreview2').src" not in js,
        "Ordenação não acessa mais iframes de prévia removidos")
require("refreshStudioPreview" in js and "Sortable.create" in js,
        "Prévia e reordenação continuam integradas")
require("background:var(--lf-raised)!important" in palette and
        ".dashboard-slim .top-links-list>li.top-links-slim-item" in palette,
        "Top Clicks usam cor semântica nos dois modos")
require("body.dark" in palette and "--lf-text:#f4f7fd" in palette and
        "--lf-muted:#c3cede" in palette, "Paleta Dark utiliza contraste legível")
require("--lf-surface:#ffffff" in palette and "--lf-text:#18243b" in palette,
        "Paleta Light utiliza contraste legível")
require("linkfree-color-modes.css" in sidebar and "linkfree-color-modes.css" in guest,
        "Paleta central ativada nos layouts do painel e autenticação")
require("body.dark .main-content .modal-title" in palette,
        "Cor de título modal não fica preta no modo escuro")


# Última rodada de revisão: a auditoria verifica também o código de produção,
# não somente os elementos simplificados do teste visual.
nav_css = (views.parents[1] / "assets/css/linkfree-navigation-polish.css").read_text(encoding="utf-8")
require("overflow-y:auto!important" in nav_css and "#sidebar-scroll-region" in nav_css,
        "Rolagem vertical nativa e acessível no menu lateral")
close_js = (views.parents[1] / "assets/js/sidebar-auto-close.js").read_text(encoding="utf-8")
require('class=\'col-12 py-1 px-3 m-0 mt-2 studio-link-actions\'' in links,
        "Barra de ações existente integra os botões do link")
require(links.count('class="btn btn-sm btn-outline-secondary studio-link-move"') == 2,
        "Duas setas de movimentação renderizadas ao lado de editar/excluir")
require('class="studio-link-clicks"' in links and
        links.index('class="studio-link-clicks"') < links.index('studio-link-actions\' role="group"'),
        "Indicador de cliques fora da barra de botões e no topo")
require("button.studio-link-move" in js and "row.append(controls)" not in js,
        "Ordenação usa botões inline, sem criar segunda linha")
require('view-page-slim' in sidebar and 'share-page-slim' in sidebar,
        "Altura e alinhamento dos botões de Exibir Página e Compartilhar controlados")
require('notification-panel' in sidebar and 'notification-panel.show' in nav_css,
        "Notificações usam posicionamento fixo centralizado na viewport")
require('sidebar-auto-close.js' in sidebar and 'sidebar-mini' in close_js and
        '[data-toggle="sidebar"]' in close_js,
        "Menu lateral fecha fora dele preservando a seta original")
require('max-width:calc(100vw - 24px)!important' in nav_css and
        'overflow-x:hidden!important' in nav_css,
        "Card de notificações não causa transbordamento horizontal")

# Consistency checks: navigation, admin cards, account deletion and link list.
admin_overview = (views / "panel" / "admin-overview.blade.php").read_text(encoding="utf-8")
users_view = (views / "panel" / "users.blade.php").read_text(encoding="utf-8")
profile_view = (views / "studio" / "profile.blade.php").read_text(encoding="utf-8")
github_view = (views / "panel" / "github-themes.blade.php").read_text(encoding="utf-8")
web_routes = (views.parents[1] / "routes" / "web.php").read_text(encoding="utf-8")
require("Site statistics:" not in dashboard and "Site statistics:" in admin_overview,
        "Global statistics moved out of shared dashboard")
require("auth()->user()->role === 'admin'" in admin_overview and
        "Route::get('/admin/overview'" in web_routes and
        "route('adminOverview')" in sidebar,
        "Admin statistics guarded and reachable in administrative menu")
require('<h5 class="mb-2">{{__(\'messages.Select Action\')}}:</h5>' in users_view,
        "Admin users heading closes correctly")
require('style="height:100vh;"' not in profile_view,
        "Account deletion confirmation cannot force a whole extra viewport")
require('min-height:420px' not in theme and 'height:min(60dvh,640px)' in theme,
        "Theme preview adjusts to compact mobile screens")
require('id="studio-my-links-title" class="card-header mb-3 d-flex flex-wrap' in links,
        "My Links heading flex-wraps with its action")
require('href="{{ route(\'showThemes\') }}">Voltar ao gerenciamento' in github_view,
        "Admin themes return to admin management")


# Validate DIV tag counts in shared templates; missed closing tags can change
# element positioning and cause inaccessible controls in browsers.
for template_path in [
    views / "panel" / "index.blade.php",
    views / "studio" / "links.blade.php",
    views / "studio" / "page.blade.php",
    views / "studio" / "theme.blade.php",
]:
    markup = template_path.read_text(encoding="utf-8")
    require(len(re.findall(r"<div\b", markup)) == len(re.findall(r"</div>", markup)),
            "Balanced DIV containers: " + template_path.name)
require('@endif"">Default Theme' not in theme,
        "Theme card has well-formed class attribute")


# Sidebar mobile: regressões de posicionamento, sobreposição e rótulos cortados.
require('> .sidebar-header > .sidebar-toggle {' in nav_css and
        'position:static!important' in nav_css,
        "Botão de recolhimento do Hope UI reposicionado no cabeçalho")
require('#sidebar-menu .nav-link .item-name {' in nav_css and
        'white-space:normal!important' in nav_css and 'min-width:0' in nav_css,
        "Títulos da navegação podem quebrar em telas estreitas")
require('#sidebar-menu .nav-accordion-toggle .right-icon {' in nav_css and
        'flex:0 0 16px' in nav_css,
        "Setas do acordeão reservam espaço e não se sobrepõem ao título")
require('nav-accordion-toggle:is(.active,[aria-expanded="true"])' in nav_css,
        "Categoria aberta usa superfície distinta do link ativo")
require('id="sidebar-scroll-region"' in sidebar and
        'data-bs-parent="#sidebar-menu"' in sidebar and
        'data-bs-parent="#admin-navigation"' in sidebar,
        "Rolagem e acordeão exclusivo preservados")

print("AUDITORIA ESTÁTICA DO ESTÚDIO APROVADA")
