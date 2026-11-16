# Publicação e cache

Este projeto consulta env() diretamente em rotas, controllers e templates.
Não executar config:cache, route:cache nem optimize nesta arquitetura.
O cache de configuração impede a leitura dessas opções e pode desabilitar
recursos ou produzir middleware vazio nas rotas autenticadas.

Após publicar os fontes, executar como o usuário do PHP-FPM:

```sh
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

Validar com sessão autenticada o Dashboard e um salvamento de perfil em banco
isolado. HTTP 200 na home e 302 de telas protegidas não validam o painel.
