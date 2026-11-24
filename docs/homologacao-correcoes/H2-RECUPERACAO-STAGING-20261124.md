# H2 — recuperação e preparação de staging

24/11/2026 UTC. **Resultado H2: PARCIAL; staging sintético preparado, recuperação integral ainda não aprovada.** O boot de uma cópia privada restaurada foi comprovado. Um ensaio do rollback oficial identificou que arquivos de código podem não ser restaurados; a H3 não foi iniciada.

## Identidade e escopo

Plano [H2](PLANO-HOMOLOGACAO-DEFINITIVA-20261124.md), [matriz de continuidade](MATRIZ-CONTINUIDADE-43-CASOS-20261124.md), manual oficial, STEP 05/06 e scripts oficiais lidos. Repositório remoto `main` em `8a654a40b44bac138d660c9d420bf024a93103d3`; SHA registrado na produção, confirmado por leitura: `6e55da55e57203da2606a4b1ca1c83865c0f760d`. Nenhum deploy, migração ou escrita na produção. Documento criado em checkout separado.

## TC-02-B — recuperação do backup privado: PARCIAL

- **Fonte:** `/var/backups/linkfree-deploy/step06-preflight-l8sk86u3`, backup anterior ao deploy; `base-sha.txt` `3f20bf95e75fb93cb6abfef3aaa388c9798b664d`.
- **Integridade:** 130/130 entradas do manifesto SHA-256 conferidas, sem divergências; ao final o backup original permaneceu íntegro. Os 36 arquivos de código nele contidos coincidem com os blobs do SHA anterior.
- **Reconstrução:** `git archive` do SHA anterior forneceu o restante do código, dependências e arquivos versionados; os 36 arquivos do backup foram sobrepostos. SQLite, `storage/app`, imagens, favicon e temas vieram do backup. O backup não é imagem integral da instalação: código não substituído, `vendor`, demais arquivos fora dos caminhos preservados e cache/configuração operacional não foram comprovados por aquele manifesto. Como verificação complementar de leitura, 7.465 arquivos de `vendor` do Git anterior coincidiram com a instalação atual; isso não prova, por si só, todo o estado histórico. `composer.lock` do Git anterior também coincide com o atual.
- **Configuração:** a chave original foi lida apenas da cópia privada para permitir boot, sem impressão ou publicação; a configuração efetiva foi substituída por `.env` e configuração avançada isolados. Não se testou a aplicação usando integralmente as configurações e integrações originais. A cópia restaurada, inclusive chave e dados reais, foi removida após o smoke; o backup original foi preservado.
- **Isolamento anterior ao boot:** `unshare -mn`, montagens privadas, diretórios `/srv/linkfree`, `/var/backups/linkfree-deploy`, `/home/ubuntu` e `/run` ocultados, rede com somente loopback e nenhuma rota IPv4 externa; tentativa de conexão externa falhou. PHP executou como UID/GID 61050 sem grupos suplementares, em raiz privada 0700. Sem worker, scheduler ou serviço externo; SMTP apontado a porta local sem servidor, fila e sessões locais, bancos e storage de cópia. O servidor escutou somente `127.0.0.1:18771` dentro do namespace e foi encerrado.
- **Esperado/observado:** Laravel iniciou apontando para SQLite e storage da cópia; HTTP Home, login e registro 200, rota inexistente 404. Nenhum conteúdo de resposta ou registro pessoal foi publicado. Boot e leitura local foram demonstrados; recuperação integral continua parcial até comprovar dependências/configurações ausentes, integridade funcional da restauração e procedimento de rollback confiável.

## TC-02-D — rollback e proteção de escritas: REPROVADO no fixture

Foi extraída a função `rollback` do `scripts/atualizar-linkstack.sh` no código oficial e executada em namespace isolado sobre pastas e SQLite exclusivamente sintéticos. Um shim substituiu `runuser` (não executou limpeza de cache real). A publicação simulada alterou arquivos de dev/prod, acrescentou arquivos novos e gravou uma linha sintética no banco; foi injetado `exit 42`.

- **Esperado:** status 42 preservado, arquivos antigos restaurados, arquivos novos removidos e nova escrita no SQLite preservada.
- **Observado:** status 42 preservado, arquivos novos removidos e escrita sintética preservada, SQLite íntegro; os arquivos antigos **não** foram restaurados. Em ambos os destinos, arquivo novo e backup tinham o mesmo tamanho e o mesmo `mtime` em segundos, mas conteúdo diferente. O `rsync -a` do trecho oficial fez comparação rápida e ignorou a diferença. O `|| true` no código também impede tratar falhas do rsync como erro. Evidência e contagens: [resultados.json](evidencias-h2/resultados.json).
- **Severidade:** P1, alta confiança no cenário reproduzido, pois um rollback pode deixar código novo ativo apesar da mensagem de restauração. A execução do script completo e o comportamento de caches reais não foram testados; não se afirma que o cenário específico aconteceu na VPS. Não executar rollback real por esta prova. Recomenda-se corrigir o mecanismo em trabalho autorizado posterior, usar comparação por conteúdo, verificar hashes após restauração e propagar erros; retestar em staging. Nenhum código de aplicação foi alterado em H2.
- Em exercício separado no mesmo fixture, `rsync -ac` restaurou por conteúdo os arquivos antigos e preservou a escrita sintética. Isso demonstra uma possibilidade técnica, não valida uma correção do deploy oficial nem autoriza alterá-lo nesta fase.

## TC-02-C — histórico: BLOQUEADO

O log privado da execução STEP 06 é do deploy sem migrations, não do deploy M13. Na inspeção dos locais de logs e diretório de backup histórico consultados, o log bruto M13 não foi encontrado. Documentação histórica é evidência secundária; não se afirma ordem histórica observada nem se repetiu migration em produção. Falha injetada de transação/deploy integral continua pendente e não se confunde com o teste do fragmento de rollback acima.

## Staging sintético e prontidão H3

Código do SHA publicado `6e55da55e57203da2606a4b1ca1c83865c0f760d`, extraído do Git em `/tmp/linkfree-h2-private/synthetic`, com banco SQLite inicialmente vazio, chave distinta de teste, sessões/cache/storage próprios e migrações executadas somente nessa cópia. Seed adaptado do script oficial gerou quatro contas `@example.test` e dados sintéticos. `integrity_check` = `ok`, zero violações FK; `/login` HTTP 200 dentro de namespace isolado. Nenhuma conta real foi copiada. Rede externa bloqueada e montagens/sockets de produção ocultados, UID 61050; servidor interrompido ao final.

Launcher local root-only `/tmp/lf-h2-start-staging.sh` inicia a instância sintética em namespace novo e sem acesso externo; o endereço de loopback fica dentro desse namespace e não fica disponível no host. O diretório `/tmp` é efêmero e o launcher não é um serviço persistente: para a próxima fase confirmar a existência, permissões, SHA, configuração, isolamento de rede e identidade do banco antes de usar. O launcher e o staging não fornecem OAuth/SMTP reais. Nenhum teste residual da H3 foi iniciado. **Prontidão H3: condicionada à revisão e tratamento do defeito de rollback e à revalidação do isolamento do ambiente efêmero.**

## Comandos e limites

Verificações principais: `git archive`, SHA-256 do manifesto, `unshare -mn`, `mount --bind` em namespace privado, `setpriv`, `php -S 127.0.0.1:18771`, consultas SQLite de integridade, `php artisan migrate` e seed **apenas no banco sintético**, função de rollback em fixture, `rsync -ac` apenas no fixture. `vendor` foi comparado com a produção atual sem copiar seus dados. Resultados agregados sanitizados em [JSON](evidencias-h2/resultados.json). Sem dados de clientes, tokens, `.env`, logs brutos ou cópias de banco no GitHub. O manifesto do backup permanece privado.
