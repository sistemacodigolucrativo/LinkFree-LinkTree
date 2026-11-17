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
require("data-bs-dismiss=\"modal\"" in links and "btn-close btn-close-white" in links,
        "Botão X de fechar modal presente e acessível")
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
require("theme-preview-current" in theme and "#ffcc36" in theme,
        "Botão pré-visualizar tema atual amarelo")
require("theme-preview-caption" in theme and "color:#fff!important" in theme,
        "Texto de demonstração em branco")
require("data-theme-preview-url" in theme and 'id="themePreviewModal"' in theme,
        "Pré-visualização de temas e seletor existentes preservados")
require("Duas demonstrações com links, botões, vídeos" in theme,
        "Descrição das duas demonstrações preservada")

print("AUDITORIA ESTÁTICA DO ESTÚDIO APROVADA")
