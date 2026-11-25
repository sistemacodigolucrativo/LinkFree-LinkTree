# Anotação 01 — Alternância contextual

Código implementado em branch isolada; homologação pendente. Contexto por rota e papel admin, incluindo rotas legadas update, backup e theme-updater. Destinos dashboard e admin/overview. Menus mutuamente exclusivos no Blade, rodapé fora da rolagem, controle com nome acessível. Middleware e rotas preservados. Submenus de Configurações fora do escopo. H3-C permanece pausada.

## Aceite pendente
- Admin: alternar nos dois sentidos, recarregar e acessar diretamente cada rota administrativa, inclusive legadas.
- Membro comum: ausência de controle e menus admin; URL administrativa protegida pelo middleware.
- Desktop, tablet, mobile 320/375/768 px, sidebar expandida/recolhida, rolagem e ausência de sobreposição.
- Teclado, leitor de tela, contraste claro/escuro.
- CI: compilação Blade e regressões existentes; teste estático atualizado para nova estrutura.

Não declarar homologado até evidências de execução. Sem merge ou deploy nesta etapa.
