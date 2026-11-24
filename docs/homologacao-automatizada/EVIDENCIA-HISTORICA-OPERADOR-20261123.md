# Registro histórico de operação fornecido pelo responsável — 23/11/2026

> **Proveniência:** síntese de saída de terminal da VPS apresentada pelo operador em conversa anterior. Não é extração de log bruto do servidor nem nova execução feita pela auditoria da PR #15. **Não reclassificar TC-02-A ou TC-02-C apenas com este documento**.

## Preflight informado pelo operador
- Versão instalada antes da publicação: `54add49d30a8`.
- Versão disponível na branch principal: `3f20bf95e75f`.
- Quantidade de arquivos de código a publicar: **23**.
- As migrações M13 000002–000004 foram reconhecidas no preflight; nenhum conflito reportado.
- Saída registrada: `SIMULACAO CONCLUIDA`.

## Publicação informada pelo operador
Comando executado: `bash /root/atualizar-linkstack.sh --deploy-m13`.

- Backup SQLite antes de migrar: `/var/backups/linkfree-deploy/20261123-231449-jORt0u/sqlite-before-deploy.sqlite`.
- `2026_11_23_000002_create_referral_tables`: **DONE**, 56 ms.
- `2026_11_23_000003_create_profile_verification_workflow`: **DONE**, 52 ms.
- `2026_11_23_000004_create_abuse_reports_tables`: **DONE**, 40 ms.
- Log apresentado: `Migrations 000002–000004 aplicadas e esquema verificado, antes de publicar código.`
- O código foi publicado, caches de configuração/rota/views foram limpos.
- Saída registrada: `PUBLICACAO CONCLUIDA: 3f20bf95e75f`.

## Smoke técnico pós-deploy informado pelo operador
- SHA da versão publicada identificado: `3f20bf95e75f`.
- SQLite de produção íntegro, consentimento e colunas presentes.
- PHP-FPM com permissões de leitura e escrita nas áreas esperadas.
- Storage privado sem symlinks, backup encontrado e íntegro.
- Homepage, login e registro em HTTPS responderam 200.
- URL inexistente respondeu 404; Studio não expôs conteúdo autenticado anonimamente.
- Resultado informado: `0 falha(s), 0 verificação(ões) pendentes de revisão manual.`
- `HOMOLOGAÇÃO TÉCNICA READ-ONLY APROVADA.`

## Limites da evidência
- Não contém o stdout/stderr integral com timestamp/autenticidade verificável. Os TCs 02-A e 02-C da PR #15 permanecem **BLOQUEADOS por falta de artefato original**.
- O relatório posterior do Codex executou novas verificações read-only e compara 540 arquivos do código implantado com o checkout; são evidências **independentes e posteriores**, não substituem o histórico do preflight.
- A operação de 23/11 não abrange cadastro real, OAuth real nem telas autenticadas da produção.
- Nenhum segredo, token, cookie, backup ou conteúdo do banco foi incluído aqui.

Veja [relatório operacional anterior](../STATUS-PRODUCAO-M13-20261123.md), [matriz de rastreabilidade](MATRIZ-RASTREABILIDADE-43-TESTES.md) e [matriz oficial](MATRIZ-RESULTADOS-43-TESTES.md).
