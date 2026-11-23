# Hotfix — HTTP 500 na página inicial do LinkFree

Data: 23/11/2026

## Defeito reproduzido

Após o deploy do editor de páginas públicas, a Home `/` apresentava erro HTTP 500. O teste de compilação do Laravel Blade revelou erro de sintaxe PHP no template `resources/views/home.blade.php`: `unexpected token "endif"`. O problema estava em diretivas `@endif@if` e `@endauth@if` concatenadas. A primeira tentativa de separá-las revelou `@if@if` duplicados, corrigidos antes da aprovação.

## Correção

- Separar diretivas condicionais, mantendo a lógica e conteúdo configurável.
- Eliminar diretivas `@if` duplicadas.
- Acrescentar `scripts/audit-blade-compile.php` para compilar e executar `php -l` sobre os templates do projeto antes de publicar novas alterações.
- Executar o teste automaticamente pelo workflow `.github/workflows/studio-layout-regression.yml`.

## Validação confirmada

GitHub Actions: https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45252965153

- 105 templates Blade compilados e sem erro de sintaxe;
- 7 views de autenticação com componentes não compiladas no teste isolado (necessitam inicialização de Laravel/View Factory);
- 51 campos de configuração do editor público auditados;
- Demais verificações automatizadas da execução aprovadas.

**Escopo da confirmação:** fonte do GitHub e testes em runner isolado. Não houve acesso SSH à VPS, leitura de logs do Laravel em produção nem teste autenticado no servidor real.

## Homologação após deploy

1. Publicar o código com o script normal de deploy, após confirmar backups.
2. Executar `cd /srv/linkfree && sudo -u www-data php artisan view:clear && sudo -u www-data php artisan config:clear` (o script de deploy já costuma realizar estes passos).
3. Abrir `/`, `/home` se configurado, `/demo-page`, `/login`, páginas institucionais, painel do membro e editor `/admin/site`.
4. Testar salvamento de cada seção do editor e verificar persistência no recarregamento da Home.
5. Se qualquer rota ainda responder HTTP 500, obter a exceção real de `/srv/linkfree/storage/logs/laravel.log` (remover tokens, e-mails e dados pessoais antes de compartilhar).
6. Garantir que `storage/app/linkfree` permita escrita pelo usuário web; jamais remover o arquivo `storage/app/linkfree/public-site.json` sem backup.

## Reversão

Branch `backup/main-antes-fix-500-20261123` conserva o commit remoto anterior à correção. Não sobrescrever dados existentes nem executar reset destrutivo em produção.

## Pendências

- Homologação funcional e visual no servidor real.
- Compilação contextual das sete views Blade de autenticação (não cobertas pelo runner isolado).
- Revisão dos logs caso persista qualquer HTTP 500.
