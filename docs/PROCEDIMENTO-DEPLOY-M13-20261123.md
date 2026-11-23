# LinkFree — liberação controlada M13 (indicações, selos, denúncias)

Data: 23/11/2026. Documento operacional. **Não executar como um único comando de deploy sem revisão da saída do preflight.**

## O que muda

O script de deploy recebeu o modo **explícito** `--deploy-m13`. Diferente de `--deploy`, ele reconhece somente as migrations aditivas:
- 2026_11_23_000002_create_referral_tables.php
- 2026_11_23_000003_create_profile_verification_workflow.php
- 2026_11_23_000004_create_abuse_reports_tables.php

Migrations desconhecidas, conjunto parcial, divergência de arquivo de banco/migrations, caminho inesperado, symlink, integridade SQLite ou backup falho **bloqueiam** a publicação. As novas tabelas são migradas **antes** de copiar o código dos controllers. Todas as etapas são registradas pelo script existente.

`--deploy` permanece bloqueado se detectar mudanças M13, para que o alias histórico `deploy` não migre o banco inadvertidamente.

## Gate 1 — CI e revisão no GitHub

- Confirmar branch `main` com a PR #14 integrada e o hash esperado.
- Exigir aprovação dos workflows: **M13 isolated migration and guarded deploy checks**, **Consent migration, persistence and deploy safeguards**, **Deploy unificado e homologacao**, **LinkFree PHP syntax lint**, e regressão de layout.
- Teste da migration M13 usa SQLite temporário no runner GitHub Actions. **Não executa migração no banco real**, não substitui teste end-to-end nem procedimento de restauração.
- Confirmar tratamento do selo legado e risco de alteração do VIP antes da divulgação das novas regras.

## Gate 2 — Instalação segura do bootstrap atualizado na VPS

No terminal da VPS como root, após verificar que a main contém o código esperado:

```bash
cd / && sudo -u ubuntu git -C /home/ubuntu/src-Projects/LinkFree-LinkTree fetch origin main && sudo -u ubuntu git -C /home/ubuntu/src-Projects/LinkFree-LinkTree show origin/main:scripts/instalar-deploy-unificado.sh > /root/linkfree-instalar-m13.sh && test -s /root/linkfree-instalar-m13.sh && bash -n /root/linkfree-instalar-m13.sh && bash /root/linkfree-instalar-m13.sh
```

O instalador faz backup do bootstrap existente, sem alterar a aplicação ou o banco.

## Gate 3 — Preflight somente de leitura, obrigatório

```bash
bash /root/atualizar-linkstack.sh --check
```

Revisar toda a saída: versão anterior, versão alvo, 3 migrations reconhecidas, código a copiar, conflito ausente, banco SQLite íntegro e permissões corretas. **Não executar o próximo comando** se houver alertas, destino diferente, migration ausente, divergência, erro de armazenamento ou versão inesperada.

Este preflight é de leitura para dados existentes; dependendo da instalação poderá criar instalações auxiliares fora da aplicação (ex.: dependência rsync) conforme lógica preexistente.

## Gate 4 — Deploy coordenado com backup e migração

Somente após aprovação explícita do preflight e das condições de segurança, na janela de implantação:

```bash
bash /root/atualizar-linkstack.sh --deploy-m13
```

A execução deve:
1. Fazer backup online de SQLite (`sqlite-before-deploy.sqlite`) e cópia do storage privado;
2. Confirmar integridade do snapshot;
3. Instalar apenas migrations 000002, 000003, 000004 e executá-las via Laravel sob www-data;
4. Confirmar todas as seis tabelas, duas novas colunas de users, registros de migração e PRAGMA quick_check/foreign_key_check;
5. Publicar somente arquivos de código autorizados no stage e na VPS;
6. Limpar caches Laravel sem usar cache de config/rotas;
7. Registrar SHA e executar homologação técnica read-only automaticamente via bootstrap.

**Importante:** se houver falha depois de alguma migração e antes de terminar o deploy, o script pode restaurar o código, mas **não deve reverter o SQLite automaticamente**; preservar o backup e investigar. As migrations são aditivas. Não executar reset de DB nem rollback destrutivo de consentimentos.

## Gate 5 — Conferência após publicação

```bash
bash /root/atualizar-linkstack.sh --homologar
```

Conferir SHA, integridade, backup e HTTP público. Comparar com a main verificada. As rotas e schemas M13 requerem validação adicional; a matriz `docs/MATRIZ-HOMOLOGACAO-01-10-20261123.md` documenta o ciclo final. Não declarar homologação completa antes dos testes autenticados, OAuth real, selos, indicações, denúncias e compatibilidade.

## Observações

- Não copiar SQLite de produção para pastas públicas nem imprimir segredos.
- Não executar `php artisan migrate` manualmente fora do procedimento autorizado.
- A PR #14 consolida as PRs #11–#13; não mesclar as três em duplicidade depois de integrar a consolidação.
- É recomendável interromper temporariamente novos cadastros durante a transição, se a janela de implantação exigir, com estratégia de retorno segura; o procedimento não pausa serviços automaticamente.
- O operador é responsável por inspecionar a saída da VPS; nenhum ambiente remoto foi alterado pela criação desta documentação.
