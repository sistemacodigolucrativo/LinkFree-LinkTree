# H2 — backup quiescente e recuperação da instalação

24/11/2026. **Parecer: backup da instalação e ensaio de recuperação concluídos no escopo descrito; H2 permanece condicional por P1 no rollback ainda instalado.** O operador autorizou expressamente janela de até 15 minutos. O acesso foi retomado em aproximadamente 9 segundos. A PR #19 não foi integrada, não houve deploy e a H3 não foi iniciada.

## Janela e segurança operacional

Antes da janela, nginx e php8.3-fpm estavam ativos e compartilhados; não foram parados. Não foram encontrados processos de worker/scheduler LinkFree nem tarefas LinkFree nos crontabs root, www-data e ubuntu; nenhum timer LinkFree identificado. O domínio foi colocado temporariamente em manutenção 503 por configuração específica de `linkfree.website` e reload gracioso do nginx. A resposta 503 foi comprovada por HTTPS, em seguida aguardou-se `lsof` mostrar zero descritores abertos na instalação. `/srv/linkfree` recebeu bind mount temporário **somente leitura**, garantindo que novas aberturas nesse caminho não escrevessem durante a cópia. Não havia symlinks ou arquivos com múltiplos hardlinks identificados na instalação. Nenhum serviço compartilhado foi interrompido; o comportamento de outros sites não foi alterado deliberadamente nem submetido a teste funcional completo.

| Marco UTC | Horário |
|---|---|
| Manutenção iniciada | 20:32:28 |
| Instalação somente leitura | 20:32:31 |
| Cópia completa | 20:32:37 |
| Acesso restaurado | 20:32:37 |

Limite solicitado: 900 s; duração registrada cerca de 9 s. `timeout` de 540 s, trap de restauração e timer independente de 11 minutos foram preparados antes da intervenção. Ao concluir a cópia, o bind mount foi removido e a configuração original do nginx restaurada e recarregada; timer cancelado. Nginx e PHP-FPM permaneceram ativos; após retorno Home, login, registro e denúncia responderam 200, Studio anônimo 302, configuração nginx coincidiu byte a byte com a anterior e não permaneceu montagem temporária nem marcador de manutenção. SHA de produção permaneceu `6e55da55e57203da2606a4b1ca1c83865c0f760d`.

## Backup integral do escopo da instalação

Diretório privado `/var/backups/linkfree-deploy/h2-integral-quiescente-3693662k`, root:root 0700; contém dados pessoais, configurações, chave de aplicação e material TLS. Não publicar ou expor esse diretório. Durante a janela, `rsync -aHAX --numeric-ids` copiou a instalação inteira `/srv/linkfree` incluindo código, `vendor`, assets/uploads, temas, storage e configurações; banco SQLite e sidecars foram excluídos dessa cópia para usar snapshot consistente da API SQLite, criado enquanto a instalação estava somente leitura e sem descritores abertos. `quick_check=ok` e `foreign_key_check` sem linhas antes de retornar acesso; `integrity_check=ok` posteriormente.

Antes da janela foram copiados `/etc/nginx`, `/etc/php`, `/etc/letsencrypt`, unidades/overrides pertinentes, crontabs disponíveis e diretórios cron, dispatcher de deploy e estado de SHA, inventário de pacotes, versão e extensões PHP, versão nginx, estágio de código e bundle Git do clone operacional. Bundle verificado pelo Git; comparações posteriores por conteúdo dos diretórios nginx/PHP/certificados não mostraram diferenças. O backup inclui dependências de aplicação; pacotes do sistema operacional são registrados por inventário, não armazenados como imagem executável de todo o sistema. A reconstrução em outro host exige SO compatível, instalação de pacotes e restauração controlada de configurações, permissões, chaves e unidades. Não foi testado provisionamento completo de outro host nem redundância externa.

Após o retorno, manifesto privado SHA-256 de conteúdo e metadados foi criado e verificado: **12.912 entradas** (11.075 arquivos, 1.759 diretórios, 78 links), zero divergências ou erros de atributos estendidos. O manifesto guarda UID/GID, modos, mtime, destinos de links e hashes de xattrs; não está publicado. [Evidência agregada](evidencias-h2-quiescente/resultados.json). O backup anterior e a ressalva de ausência de log bruto do TC-02-C não foram alterados.

## Recuperação posterior em ambiente isolado

Uma cópia completa do novo backup foi restaurada em diretório privado temporário com `rsync -aHAX --numeric-ids`. **12.912/12.912 entradas** foram conferidas contra o manifesto antes de qualquer adaptação, sem divergências em conteúdo, propriedade, permissões, datas, links ou xattrs. Para o boot isolado, a cópia recebeu `.env` restrito com a chave necessária do próprio backup e conexões de teste; sessões e caches antigos foram removidos somente da cópia. A configuração avançada restaurada foi mantida. SMTP era local sem serviço, sem workers/scheduler.

`unshare -mn` isolou rede e montagem antes do boot: somente loopback, nenhuma rota externa, conexão externa falhou, `/srv/linkfree`, backup, home operacional, `/run` e certificados reais ocultados. Processo PHP executou sob UID/GID sem privilégios 61050. Laravel confirmou banco e storage da cópia, sessões e cache locais. Smokes: Home/login/registro/denúncia 200, Studio e Admin anônimos 302, rota inexistente 404; SQLite `integrity_check=ok`, zero violações FK. O conteúdo das respostas e registros pessoais não foram divulgados. A cópia restaurada, chave e dados reais foram removidos ao final. Não foram exercitados autenticação de contas reais, provedores externos nem restauração de SO/servidor nginx completo.

## Parecer da H2

- Backup consistente no escopo da instalação, manifesto verificado e recuperação local da aplicação: **concluídos**. A janela ficou abaixo de 15 minutos.
- Script candidato integral da PR #19: ensaios aprovados conforme [relatório anterior](H2-ENSAIO-INTEGRAL-E-BACKUP-20261124.md); não repetidos nesta janela.
- **Encerramento global da H2: condicionado.** A correção candidata não está integrada nem publicada por instrução expressa, portanto o mecanismo de rollback antigo com P1 reproduzido permanece na versão operacional. Não declarar recuperação operacional de produção integralmente segura até tratar essa pendência pelo fluxo e autorizações aplicáveis. A recuperação de outro host, serviços externos e testes funcionais completos continuam fora da prova.
- PR #19 permanece aberta; não houve deploy, alterações de código ou início da H3. TC-02-C histórico segue bloqueado; este backup novo não cria evidência histórica inexistente.
