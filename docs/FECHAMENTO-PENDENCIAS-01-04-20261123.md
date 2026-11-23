# LinkFree — Fechamento técnico das pendências 01–04 (23/11/2026)

## Situação e limites de acesso

Repositório: `sistemacodigolucrativo/LinkFree-LinkTree`.
A branch de preparação é `fix/consent-migration-deploy-persistence-tests-20261123`, derivada da `main` (`5996df6c2f76673797e767a346bf2edc289d881c`).
Backup da referência anterior: `backup/main-antes-consent-hardening-20261123`.

**Nunca executar um comando de migração ou deploy no banco de produção sem conferir o `--check` e o backup.** O GitHub não oferece acesso SSH à VPS. O aceite da branch e os testes CI NÃO significam que a migração já foi aplicada no servidor real.

## Nº 01 — Migração de consentimento

A migração aditiva `database/migrations/2026_11_23_000001_create_registration_consents_table.php` cria a tabela `registration_consents`, com `user_id`, `method`, versões hash SHA-256 dos Termos e da Privacidade, `accepted_at` e timestamps.

Foi verificada a execução real da migração contra um SQLite **temporário e isolado**, incluindo tipos/colunas e gravação de comprovante. Ela é idempotente pelo controle de migrações Laravel; não altera tabelas antigas. Uma falha antes do deploy mantém o código antigo e o backup disponível. **Não executar rollback destrutivo dessa tabela:** informações legais devem ser preservadas.

### Limites do preparador

O script só permite a migração de consentimento por nome exato, e somente no modo explícito `--deploy-consent`. Outras migrações, banco não SQLite, caminho inesperado, arquivos divergentes, SQL corrompido ou textos legais vazios são bloqueados para revisão manual.

**Importante:** o modo `--deploy` normal CONTINUA recusando essa migração quando ainda pendente.

## Nº 02 — Deploy com backup e ordem coordenada

Arquivo: `scripts/atualizar-linkstack.sh` (versão atualizada).

1. `--check` compara a versão instalada, verifica a base Git e conflitos e confere dependências, acesso ao SQLite, integridade `PRAGMA quick_check`, Termos/Privacidade e diretórios/arquivos de configuração.
2. Com autorização operacional explícita, `--deploy-consent` cria backup online com `sqlite3 .backup`, verifica sua integridade e realiza cópia privada do `storage/app/linkfree`.
3. Copia **somente** a migração permitida, executa `php artisan migrate --path=database/migrations/2026_11_23_000001_create_registration_consents_table.php --force --no-interaction` com `www-data`, verifica existência da nova tabela e **só então** publica o restante do código selecionado.
4. Limpa caches de configuração, rotas e views, sem ativar `config:cache` ou `route:cache`. Registra SHA publicado e caminho do backup.
5. Os arquivos `.env`, banco de dados, temas personalizados, uploads e todo `storage` não são alterados pelo rsync de código.

O script existente usa `/home/ubuntu/src-Projects/LinkFree-LinkTree` como clone persistente, `/home/ubuntu/linkstack-dev` como estágio e `/srv/linkfree` como produção. Verificar esses caminhos no servidor; **não presumir** que outra cópia em `/home/ubuntu/LinkFree-LinkTree` esteja sincronizada.

### Execução no servidor (SOMENTE após conferência)

Após o merge da PR, atualizar **somente a referência remota**, sem modificar os arquivos rastreados no clone (o script exige `git status` limpo). Extrair a versão revisada do script para um local privado fora do repositório. Conferir seu conteúdo antes de executá-la.

```bash
sudo -u ubuntu git -C /home/ubuntu/src-Projects/LinkFree-LinkTree fetch origin main
sudo -u ubuntu git -C /home/ubuntu/src-Projects/LinkFree-LinkTree show origin/main:scripts/atualizar-linkstack.sh | sudo install -m 700 /dev/stdin /root/linkfree-consent-deploy.sh

# Primeira etapa: conferir o resultado, sem publicar o código.
sudo bash /root/linkfree-consent-deploy.sh --check

# Segunda etapa, após revisão do backup e da saída do check:
sudo bash /root/linkfree-consent-deploy.sh --deploy-consent
```

A última linha exige aprovação operacional baseada no resultado real do `--check`. Não executá-la se houver qualquer advertência, caminho divergente ou falha de pré-requisito.

**Essas linhas são procedimentos futuros, não execuções realizadas.** Para `--check`, o script já realiza `git fetch`, mas não modifica os arquivos da aplicação.

No caso de falha após a migração e antes da sincronização completa, manter a tabela nova (aditiva) e restaurar somente o código anterior. Conferir o backup sob `/var/backups/linkfree-deploy`. A recuperação do banco exigiria decisão separada porque não se deve descartar comprovantes de consentimento criados depois.

## Nº 03 — Permissões e persistência

Serviços de configuração reutilizados:
- `ThemeOptions` salva `storage/app/linkfree/theme-options.json`.
- `VisitAttribution` salva `storage/app/linkfree/visit-sources/{id}.json`.
- `PublicSiteContent` salva `storage/app/linkfree/public-site.json`.
- `UserData` utiliza a tabela `users` atual para preferências pessoais do membro.

A atualização inclui backup da pasta de dados no diretório privado, verificação de escrita para `www-data`, leitura dos arquivos JSON existentes, escrita em locks e bloqueio de symlinks. Somente pastas ainda ausentes são criadas como `www-data`, sem `chmod -R 777` ou `chown -R` em dados dos clientes.

Os testes isolados confirmam gravação, reabertura e alteração das configurações, agregação de visitas e ausência de identificadores pessoais adicionais no JSON.

## Nº 04 — Cadastro e consentimento

Testes contra SQLite temporário com Laravel inicializado:

- POST de cadastro sem `accept_terms` é rejeitado e não cria conta;
- POST com aceite cria usuário e **um** comprovante com data e hash da versão publicada;
- transação que falha não deixa conta órfã nem recibo;
- OAuth com identidade nova **sem** aceite é bloqueado;
- OAuth com aceite cria conta e comprovante `method=social`;
- login posterior pela conta social não duplica comprovante.

O provedor OAuth no CI é simulado: nenhum token real ou conta externa é usado. Autenticação e banco são executados pelo Laravel em memória/SQLite temporário.

## Evidências e status

- GitHub Actions: workflow `Consent migration, persistence and deploy safeguards`, com `bash -n`, auditoria de proteção de dados, PHP lint e integração real SQLite.
- Este procedimento **não substitui** um teste de homologação autenticado em uma VPS com configurações equivalentes à produção.
- Nenhuma migração foi executada contra o banco de produção e nenhum deploy foi feito nesta rodada.

## Checklist obrigatório de homologação real

- Verificar banco, `--check`, armazenamento e documentos legais; não expor conteúdo do `.env`.
- Garantir snapshot SQLite íntegro e cópia de `storage/app/linkfree`.
- Aplicar migração coordenada, conferir tabela e status da migração.
- Criar uma conta de teste sem aceite (deve falhar) e com aceite (deve salvar recibo).
- Verificar contas sociais novas e existentes em ambiente de teste com credenciais próprias.
- Verificar login, área do membro, Home pública, tema, estatísticas e editor administrativo.
- Se houver falha, parar. Nunca executar reset de banco, limpeza de tabela de consentimentos, exclusão de `storage` ou force push.

**Condição final:** preparação e integração isolada concluídas; validação e execução efetivas na VPS aguardam acesso ao ambiente.
