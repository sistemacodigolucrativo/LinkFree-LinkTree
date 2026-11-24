# H3-A — persistência e estatísticas

24/11/2026 UTC. **TC-03-B e TC-08-D: aprovados nos cenários automatizados de staging descritos abaixo.** As falhas históricas foram reproduzidas como problemas das fixtures, sem demonstrar defeito da aplicação. Este resultado não altera a matriz histórica da PR #15, não representa homologação da produção e aguarda revisão do ChatGPT antes de qualquer H3-B.

## Ambiente e pré-condições

Código da aplicação `6e55da55e57203da2606a4b1ca1c83865c0f760d`, igual ao SHA registrado em produção por leitura; `git diff` entre esse SHA e a main documental `919eb326c4821a5ea96d01dd5f921182311e530a` em `linkstack/` vazio. O staging H2 estava em `/tmp/linkfree-h2-private/synthetic`: SQLite local íntegro, zero violações FK, quatro contas sintéticas `@example.test`, uma Admin, uma VIP e duas Free desbloqueadas; chave distinta da produção, SMTP apontado para loopback sem serviço, sessão/cache locais. Na comparação de 526 arquivos app/rotas/views, faltava apenas `resources/ckeditor/plugins/scayt/CHANGELOG.md`; o arquivo de documentação do CKEditor foi restaurado no staging sintético a partir do SHA de código, sem alterar código da aplicação.

Foi criada cópia descartável `/tmp/linkfree-h3a-private/app`, banco e storage sintéticos próprios e nova chave de teste. PHP 8.3.6/Laravel 9, SQLite, `php -S 127.0.0.1:18775` em namespace `unshare -mn`: montagem privada com aplicação real, backup, home operacional e `/run` ocultados, nenhuma rota IPv4 externa; tentativa de saída bloqueada, loopback inacessível pelo host, servidor UID 61050. Somente o cliente sintético entrou no namespace. O servidor foi encerrado ao terminar. Quatro logins foram confirmados por sessão e redirect específico, antes das assertivas; a página pública alvo retornou 200. Não foram usados dados de clientes, credenciais reais ou acesso de teste à produção.

## Diagnóstico das fixtures históricas

| Caso | Reprodução e causa | Conclusão |
|---|---|---|
| TC-03-B | O teste histórico salvava `nav_features` e esperava encontrar o valor em `/admin/site`, que abre a seção `identity`. No staging, `nav_features` está na seção `navigation`; após salvar, foi visível em `/admin/site?section=navigation` e na Home, enquanto a seção padrão não o continha. A assertiva histórica sem mensagem não identificou qual condição falhou; este cenário reproduz uma causa concreta compatível, sem provar que era a única. | Falha de preparação/seleção da seção; não foi comprovado defeito de persistência. |
| TC-08-D | A fixture histórica usava `urllib` com User-Agent padrão `Python-urllib/3.12`. Perfil sintético respondeu 200, mas a linha total não foi criada; `fetchone()` retornou `None`. O `CrawlerDetect` instalado classificou esse User-Agent como crawler, ignorado pela configuração. O novo harness exige perfil acessível e distingue linha ausente de zero. | Causa reproduzida da pré-condição ausente/NoneType; não é prova de falha do contador para visita legítima. |

Fontes históricas: [script](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/blob/361c4cb4d4da9db98039d58e10834ad8541f40a7/docs/homologacao-automatizada/scripts/persistence_checks.py) e [resultado](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/blob/361c4cb4d4da9db98039d58e10834ad8541f40a7/docs/homologacao-automatizada/evidencias/persistence.json). A prova anterior de serviço/armazenamento é complementar; os resultados abaixo percorrem HTTP e persistência.

## TC-03-B — persistência: APROVADO EM STAGING

| Assertiva | Esperado e observado |
|---|---|
| Texto Admin | POST da seção `navigation` retornou 302 para a seção correta; `storage/app/linkfree/public-site.json` registrou `nav_features`. |
| Reload, nova sessão e Home | Valor sintético apareceu na seção correta e na Home, persistindo após logout e login independente. A seção padrão `identity` não foi confundida com a seção editada. |
| Tema | VIP selecionou `PolySleek`; DB manteve a seleção após reload e novo login e o perfil público referenciou os assets do tema. |
| Preferência de fonte | Admin autorizou `font_family`; VIP salvou `mono`; DB manteve após novo login e CSS público incluiu `monospace`. |
| Relatório | Após geração de visitas, os totais e origens continuaram visíveis ao VIP após logout e novo login. |
| Restauração | Texto e tema originais restaurados por rotas; imagem JSON e permissões sintéticas foram restauradas na cópia ao fim do ensaio adicional. Nenhum dado real foi alterado. |

Evidências: [execução principal](evidencias-h3a/resultados.json), [preferências e renderização](evidencias-h3a/resultados-extra.json). Os 302 foram confrontados com persistência e renderização, não usados isoladamente para aprovar.

## TC-08-D — contador, origens e permissões: APROVADO EM STAGING

`config/visits.php` define deduplicação por IP por 900 segundos e exclusão de crawlers. `UserController::renderPublicProfile` incrementa o contador e só registra origem se a biblioteca aceita a visita. A chave efetiva do total para a fixture VIP foi `visits:users_auditvip_total`. Nenhum cabeçalho de proxy foi usado para forjar IP; identidades independentes vieram de endereços loopback distintos no próprio namespace.

| Assertiva | Esperado e observado |
|---|---|
| Pré-condições | Antes das visitas, a linha do total e arquivo de origens estavam ausentes; ausência registrada explicitamente. Uma visita de tema gerou baseline 1. |
| UTM e repetição | Visita legítima com UTM Instagram de IP sintético passou de 1 para 2; repetição imediata do mesmo IP manteve 2 e não duplicou origem. |
| Nova identidade e referrer | Outro IP sintético com referrer Telegram passou de 2 para 3; agregados Instagram=1 e Telegram=1. |
| Expiração | Ao marcar **somente na fixture** o registro do primeiro IP como expirado, próxima visita passou de 3 para 4 e repetição imediata permaneceu 4. Foi uma simulação de relógio; não se esperaram 900 s reais. |
| Persistência e renderização | VIP viu total 3 e as duas origens após nova sessão; com flag sintética ativada via `/studio/page`, perfil público também exibiu 3. |
| Permissão e isolamento | Free recebeu 403 em requisições HTML e JSON; VIP e Admin obtiveram 200 nos próprios relatórios. Query com ID de outra conta foi ignorada e cada relatório manteve dados do próprio usuário. |

Evidências: [execução principal](evidencias-h3a/resultados.json), [contador e escopo](evidencias-h3a/resultados-extra.json), [expiração simulada](evidencias-h3a/resultados-expiracao.json), [reprodução histórica](evidencias-h3a/diagnostico-fixture.json). A validação usou IPs loopback distintos em ambiente fechado; comportamento de proxy/rede real e intervalo de 900 s decorrido em tempo real não foram testados.

## Comandos e limites

Harnesses separados [principal](evidencias-h3a/harness.py), [adicional](evidencias-h3a/extra.py), [expiração](evidencias-h3a/expiracao.py) e [diagnóstico](evidencias-h3a/diagnostico_fixture.py) só executam sob UID 61050 e caminhos sintéticos verificados. Foram usados `unshare -mn`, mounts privados para ocultar produção, `setpriv`, servidor PHP local e clientes HTTP com cookies próprios; SQLite foi consultado na cópia e alterado somente para preparar/restaurar fixture e simular expiração. Execuções: 7/7 assertivas principais, 2/2 adicionais, 1/1 de expiração; diagnóstico histórico também passou. Não foi detectado defeito da aplicação nos cenários executados.

Produção não recebeu alterações, deploy ou testes mutáveis. H3-B não foi iniciada e depende da revisão pelo ChatGPT. Itens externos e aceite final permanecem no plano oficial; estas aprovações de staging não reclassificam automaticamente a matriz histórica nem demonstram a configuração específica da VPS em todos os fluxos.
