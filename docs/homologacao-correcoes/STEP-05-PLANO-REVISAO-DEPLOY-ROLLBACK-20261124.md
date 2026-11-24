# PR #16 — STEP 05: revisão e plano de liberação controlada
Data 24/11/2026. Documento de trabalho; nenhuma ordem de deploy. Main de referência 342fce9b15cdc7a55e9bcc6f8a00278dc712d73d; código anterior ao fechamento STEP 04: 388b80f8b83cde0622ff8ec057e2e6cec86ecd60.
## 1. Inventário e compatibilidade
Comparação API GitHub: 26 commits à frente, 0 atrás, 51 arquivos (38 aplicação), nenhum removido, nenhuma migration, dependência ou tema instalado alterado. PR #15 permanece draft 361c4cb4. PR #16 draft, mergeável na consulta, não integrada. O candidato posterior adiciona apenas correções de acessibilidade/testes/documentação; recontar no head final.
Revisados os patches de routes/web.php, controllers de segurança, middleware de impersonação/admin, Handler, views de ação e fluxo de edição de blocos. Verificado no Laravel versionado que Model::forceDelete() chama delete(), mantendo eventos do modelo (diferentemente de Builder::forceDelete()); FKs e histórico não são alterados por esta revisão. Revisar escopo completo novamente no candidato final; não confundir revisão pontual com auditoria externa.
## 2. Reuso de evidências e gates
TC-04-D: run 45448305974, HTTP 20/20 e 6/6 interações; instaladores aprovados. TC-09-B: 12/12 scans sem violações; matriz de temas inicial 18/21 com erro de comparação de capitalização, corrigido no candidato seguinte. Nova matriz inclui imagem e contraste numérico, formulário e foco. A matriz histórica PR #15 fica intacta. Regressões atuais necessárias apenas após mudança de código relevante; execução docs-only pode referenciar SHA de código e diff sem reexecutar por conveniência.
## 3. Preparação para merge (não executada)
- Confirmar head, base, diff completo, ausência de conflito e resultados dos workflows no SHA candidato; separar manual/humano e riscos externos. Não declarar WCAG integral nem homologação geral da matriz 01–10.
- Verificar produção por procedimento autorizado: versão registrada, base ancestral, marcadores de instalação ausentes, nenhuma sessão legada ativa de impersonação; reautenticação controlada se necessária.
- Solicitar autorização de merge separadamente. Após integrar, confirmar SHA da main e workflows de integração antes de preparar deploy.
## 4. Preflight e publicação (somente STEP 06 e autorização expressa)
Referências: scripts/instalar-deploy-unificado.sh, scripts/atualizar-linkstack.sh, scripts/homologar-linkfree.sh; procedimento M13 histórico. Não repetir --deploy-m13 nem migrations 000002–000004. Confirmar diff contra SHA instalado, sem migrar banco por suposição. O modo --check é de leitura quanto aos dados existentes, mas pode buscar Git e instalar dependência rsync; não executar nesta etapa.
- Bootstrap aprovado da main; preflight --check; revisar arquivo/sha/permissões/banco; bloquear em divergência ou arquivo protegido.
- Backup consistente do SQLite (quick_check) e armazenamento privado, código, manifestos de SHA. MariaDB exige backup consistente separado; o script legado apenas avisa quando SQLite não existe, logo backup externo deve ser gate explícito. Não apagar ou sobrescrever dados.
- Só executar --deploy após segunda autorização. O código da PR não inclui migrations novas; modo específico de migration só com revisão e autorização explícitas se diferença real exigir.
- Pós-publicação --homologar em modo read-only, SHA, integridade, HTTP público, não fazer exclusões ou alterações reais. Demais testes funcionais apenas no ambiente adequado.
## 5. Rollback
O script possui trap de rollback de código para falha durante a cópia: backup de arquivos existentes, lista de novos arquivos, restauração do código e limpeza de caches. Não restaura automaticamente o banco; recuperação de dados requer snapshot validado e autorização. Se falha detectada após retorno de sucesso, preparar recuperação controlada a partir do backup e registrar versão (não resetar Git de produção; não reverter DB automaticamente). Necessário exercício de recuperação isolado, não simular execução na VPS.
## 6. Limites e decisão
Dispositivo Safari/iOS físico, leitor de tela, temas instalados externamente, avaliação visual humana, OAuth/SMTP real e matriz histórica PR #15 são gates separados de homologação externa. STEP 04/05 só podem ser declarados completos no escopo automatizado comprovado, com limites explicitamente abertos. Merge/deploy bloqueados até autorização.

## Revisão complementar e ensaio de recuperação
Head de revisão anterior 6dcfa6e6: 28 commits, 54 arquivos, 39 na aplicação; diff completo disponível para os 54; nenhuma exclusão, migration, dependência, marcador ou arquivo de tema instalado. Comparação por caminho com a PR #15: 43 arquivos nela, interseção vazia. Main 342fce9b intacta e PR #16 draft/mergeável.
- Ensaio local no container (diretório temporário, rsync real, fragmento rollback do blob 0cc6588f6e459d54755624be160abbac1dcdef82, falha injetada exit 42): **PASS** restauração de arquivos distintos em dev/prod, remoção de arquivos novos, hash de SQLite/.env/storage sintéticos idêntico, PRAGMA quick_check ok, linha sintética preservada, status 42 preservado, chamadas de limpeza de cache registradas por shim. Não executamos o script integral nem o Artisan real no rollback; não equivale a recuperação de produção. O trap legado suprime falhas de rsync/cache; a verificação posterior de integridade e de hashes permanece gate obrigatório.
- Revisão do controller de assets de blocos detectou import ausente de Response e ausência de guarda para query não escalar. Corrigido no próximo candidato; ampliada assertiva HTTP existente (asset CSS válido, extensão proibida, ausente, fora da pasta e query malformada). Requer reteste.

## PARECER FINAL — STEP 05
SHA de código 1d5a4bc3688f7bfb6d7906b55088da4a20b36ca8; 31 commits à frente, 0 atrás de main 342fce9b15cdc7a55e9bcc6f8a00278dc712d73d; 55 arquivos (40 aplicação), zero removidos, zero migrations novas, zero dependências/temas instalados/marcadores modificados. PR #15 361c4cb4, interseção de arquivos vazia. PR #16 draft, mergeable=true na última consulta. [CI final](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45452596718) e [push](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45452573437) aprovados nos sete checks aplicáveis. Workflows de deploy não foram repetidos sem alteração no script; revisão e ensaio do fragmento rollback foram registrados acima. O script de rollback deve ser verificado por hash após recuperação pois suprime certos erros de rsync/cache.

| Nº original | Atividade | Estado |
|---|---|---|
| 22 | Revisar diff | Concluída; correções de tratamento de assets e acessibilidade incorporadas |
| 23 | Regressões/conflitos | Concluída no escopo, main preservada, nenhuma sobreposição PR15 |
| 24 | Workflows | Concluída; todos os checks aplicáveis verdes no SHA de código |
| 25 | Consolidar TC-04-D/TC-09-B | Concluída com separação de evidência automatizada/manual |
| 26 | Atualizar relatório de reteste | Concluída |
| 27 | Atualizar documento mestre | Concluída |
| 28 | Atualizar descrição PR | Atualizar para o parecer e links desta revisão |
| 29 | Plano de implantação/rollback | Concluída; ensaio local de restauração de código aprovado |
| 30 | Parecer técnico | Concluída: parecer favorável ao escopo automatizado, condicionado aos gates externos e autorização |

**Parecer:** revisão técnica interna concluída, candidato apto para avaliação de liberação controlada, não para publicação automática. O gate Safari/iOS físico/inspeção humana (incluindo achados axe incompletos) segue aberto; não alegar conformidade total. Não iniciar merge/deploy até autorização expressa e tratamento/aceite documentado dos riscos externos. Preflight da VPS somente no STEP 06; não presumir versão instalada. Sem alterar PR #15, main ou produção.
