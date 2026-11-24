# STEP 06 — relatório operacional da VPS

Data: 2026-11-24, execução aproximadamente 19:07 UTC. **Resultado: publicação concluída e homologação técnica read-only aprovada; recuperação integral da aplicação e homologação funcional permanecem parciais.** Não equivale a aceite final da matriz histórica ou conformidade integral de acessibilidade.

## Ambiente e versão

- Origem: `sistemacodigolucrativo/LinkFree-LinkTree`, main. Checkout operacional limpo antes da operação, branch main, origin confirmado. VPS `/srv/linkfree` não é checkout Git.
- SHA anteriormente registrado: `3f20bf95e75fb93cb6abfef3aaa388c9798b664d`. Alvo da main imediatamente antes de publicar e SHA registrado depois: `6e55da55e57203da2606a4b1ca1c83865c0f760d`. Integração de aplicação: `1b71b747e2c2b803eaffc6c63b64b90c408e8f4e`.
- A diferença entre merge e alvo contém somente quatro documentos. Árvore do merge idêntica à árvore do head validado. Base é ancestral do alvo. Diff de publicação: 40 arquivos de aplicação, nenhuma exclusão, migration ou alteração de dependência.
- SQLite, PHP 8.3.6, SQLite 3.45.1; nginx e php8.3-fpm ativos. Disco `/` 39 GiB livres (18% usado na inspeção). Marcadores `INSTALLING` e `INSTALLERLOCK` ausentes.
- `users.auth_as`: zero valores legados ativos; zero arquivos de sessão contendo a chave legada `display_auth_nav` na inspeção agregada, sem divulgar conteúdo. Isso não testa todas as sessões futuras nem prova o fluxo de impersonação real.

## Preflight e backup

O dispatcher `/root/atualizar-linkstack.sh` foi comparado byte a byte com o gerado pelo instalador versionado: idêntico; não houve reinstalação. `bash /root/atualizar-linkstack.sh --check` retornou zero, apresentou 40 arquivos e `SIMULACAO CONCLUIDA`, sem conflitos. O check busca Git e cria lock temporário; rsync já estava instalado. O SQLite real foi aberto somente para leitura: `quick_check` e `integrity_check` retornaram `ok`, `foreign_key_check` zero linhas; migrations de consentimento e M13 000002–000004 constam registradas.

Antes do deploy foi criado backup privado `/var/backups/linkfree-deploy/step06-preflight-l8sk86u3`, root:root 0700: snapshot online SQLite, `.env`, configuração avançada, `storage/app`, imagens, favicon, temas e os 36 arquivos existentes a substituir. 130 arquivos foram verificados com manifesto SHA-256 armazenado no próprio backup; cópias persistentes conferidas por `rsync -rcn`, sem diferenças. Quatro arquivos novos não tinham versão anterior. Cópia do SQLite restaurada em diretório temporário privado passou `integrity_check` e FK; os 36 arquivos de código foram copiados em exercício isolado. **Não foi iniciada a aplicação sobre o banco restaurado**, logo a recuperação funcional integral continua pendente. O ensaio isolado de rollback de código documentado no STEP 05 foi reaproveitado; não equivale à execução integral do rollback na VPS.

O deploy gerou backup adicional `/var/backups/linkfree-deploy/20261124-190714-ViCtZ9`, root:root 0700; snapshot SQLite `quick_check` aprovado. Os backups contêm dados privados e devem permanecer fora do repositório e da web. O log operacional completo fica privado em `/root/step06-deploy-20261124.log`. A recuperação pós-sucesso deve ser conduzida como operação controlada, preservando escritas posteriores; restauração de banco sobre produção requer avaliação e autorização específica. O trap oficial de rollback durante falha restaura código e não restaura banco; conferir hashes após qualquer recuperação.

## Publicação e verificações

1. `git ls-remote ... refs/heads/main`: destino `6e55da55e57203da2606a4b1ca1c83865c0f760d` confirmado imediatamente antes.
2. `bash /root/atualizar-linkstack.sh --deploy`: retorno zero; backup criado, 40 arquivos publicados, caches de configuração, rotas e views limpos; sem migrations. Não se usou `--deploy-m13`.
3. O próprio bootstrap executou `homologar-linkfree.sh --read-only`: **0 falhas, 0 avisos**, SHA, banco, consentimento, permissões, backup, Home/login/registro, 404 e proteção anônima do Studio passaram. Não foi necessário repetir `--homologar`.
4. Comparação independente dos 40 arquivos implantados com blobs do alvo: **40/40 idênticos**. `integrity_check` novamente `ok`, zero violações FK, quatro migrations esperadas permanecem. `.env`, configuração avançada, storage/app, imagens, favicon e temas coincidiram com backup pré-deploy. Sem alterar permissões de dados.
5. HTTPS: `/`, `/login`, `/register`, `/report` responderam 200; `/dashboard`, `/studio/profile`, `/admin/config`, `/admin/reports` redirecionaram 302 anonimamente; `/.env` e banco responderam 403. HTTP Home redirecionou 308 para HTTPS. O smoke oficial valida TLS; não foram realizadas mutações ou criadas contas reais.
6. nginx e php8.3-fpm ativos. `journalctl` prioridade erro desde 19:07:14 UTC retornou zero linhas. Contagem limitada em logs Laravel para o minuto da publicação não apontou erros; isso não exclui erros não registrados ou posteriores.

## Evidências anteriores reaproveitadas

[CI pré-merge 45453865896](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45453865896): jobs HTTP, navegador e instaladores SQLite/MariaDB concluídos com sucesso; relatórios existentes registram HTTP 20/20, interações 6/6, axe 12 páginas sem violações detectadas, perfis 24/24 e formulários 6/6, instaladores 4/4 cada. [PHP pós-merge](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45456662353) e [layout pós-merge](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45456662150) confirmados success para `1b71b747`. A bateria anterior não foi repetida indiscriminadamente.

## Atividades 31–38

| Nº | Atividade | Estado e evidência |
|---|---|---|
| 31 | Autorização de integração | Concluída anteriormente |
| 32 | Integrar PR #16 | Concluída anteriormente, merge `1b71b747` |
| 33 | Main e CI | Concluída, árvore e checks conferidos |
| 34 | Preflight e backup | Concluída no escopo técnico; restauração com aplicação completa pendente |
| 35 | Autorização deploy | Concluída pela instrução expressa do operador nesta execução |
| 36 | Publicar na VPS | Concluída, `6e55da55` registrado e 40 arquivos conferidos |
| 37 | Homologação segura | Concluída no escopo read-only; funcional autenticada pendente |
| 38 | Registros finais | Relatório e continuidade preparados para PR documental; consultar PR para estado de integração |

## Pendências e parecer

Não foram feitas correções de código nesta operação nem testes destrutivos. Permanecem: recuperação completa em staging e observação operacional; Safari/iOS físico, leitor de tela, inspeção humana de acessibilidade e itens axe incompletos; OAuth e SMTP reais; sessões autenticadas; política LGPD e selo legado; homologação e aceite definitivo dos 43 casos. A PR #15 e sua matriz histórica permanecem intactas. O deploy e o smoke técnico estão comprovados; não declarar homologação funcional integral.
