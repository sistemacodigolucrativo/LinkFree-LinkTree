# Deploy controlado SEO — 24/11/2026

**Resultado:** deploy normal do SEO concluído e homologação técnica pós-deploy aprovada no escopo abaixo. SHA anterior `6e55da55e57203da2606a4b1ca1c83865c0f760d`; SHA publicado `a90dbb80efccacd629b1687e2a41c43c0515c219`. Sem migrations, reinício de serviço, alteração do nginx ou restauração do banco. H3-C permanece pausada. Este registro não é aceite funcional geral nem prova de atualização de cache de terceiros.

## Autorização e preflight

O operador autorizou expressamente o deploy controlado após a [integração da PR #27](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/pull/27). Documento-base: [SEO-RECUPERACAO](SEO-RECUPERACAO-20261124.md). O remote `origin`, working tree e ancestralidade foram conferidos. A main imediatamente antes da execução era o SHA autorizado; o dispatcher confirmou o mesmo destino. PR #27 integrada; os workflows do candidato e os três workflows pós-merge consultados estavam concluídos com sucesso.

`bash /root/atualizar-linkstack.sh --check` passou: oito arquivos de aplicação, sem migrations ou mudanças de dependência. As alterações são dois controllers SEO novos, UserController, PublicSeo novo, imagem de marca nova, home, meta e rotas. O nginx efetivo já continha os três caminhos específicos de sitemap anteriores ao bloqueio geral de XML; `nginx -t` passou. Nenhuma exceção adicional foi aplicada. PHP 8.3 dispunha de GD, FreeType e mbstring; fontes DejaVu regular/negrito eram legíveis por `www-data`, assim como a marca base; o diretório de views era gravável.

## Staging isolado

Uma cópia sintética do staging H2 recebeu somente os oito arquivos do SHA alvo. SQLite, chave, contas e storage eram fictícios. Namespace próprio de montagem e rede, sem rota externa, mascarou produção, backups, `/home/ubuntu` e `/run`; a porta local não era acessível do host. Nginx com as regras de proteção equivalentes encaminhou para um PHP-FPM próprio por socket Unix, mais listener TLS com certificado sintético. Não foram usados dados reais. A suite `scripts/qa-isolated/seo_acceptance.py`, adaptada apenas para o caminho privado da fixture, passou em FastCGI:

- JPEG de Home, perfil com avatar e fallback decodificados por GD em 1200 × 630, ETag e resposta 304; imagem legacy e perfil inexistente.
- Mudança sintética de nome/bio e de foto alterou a imagem; escape, canonical, Open Graph e X, JSON-LD, domínio personalizado e HOME_URL verificados.
- Noindex individual e global, exclusão de bloqueados/noindex de sitemaps, 404 de perfis bloqueados e imagens, robots e sitemaps XML válidos.
- Login/registro e proteção anônima. Com agente de navegador sintético, visita ao perfil incrementou a tabela de visitas; com agentes crawlers os testes SEO não criaram visitas na fixture.
- HTTPS sintético: canonical e URLs sociais em HTTPS; login HTTP 200. Os caminhos sintéticos `.env`, SQLite, configuração, storage/XML privado e XML arbitrário receberam 403. O certificado local não comprova confiança pública de certificados.

## Backup e recuperação

Antes do deploy foi criado backup local restrito a root em `/var/backups/linkfree-deploy/seo-20261124-UzV3MP`: snapshot SQLite online via API de backup, `quick_check=ok`, zero violações em `foreign_key_check`, 16 migrations, manifesto de SHA-256 e metadados dos quatro arquivos existentes, registro dos quatro novos, além de configuração e nginx protegidos. As cópias do manifesto foram comparadas por hash. Como não houve parada de escritores, o snapshot é consistente para SQLite, mas não constitui captura atômica simultânea de todos os recursos da instalação.

Ensaio de rollback em diretório descartável aplicou o conjunto candidato e depois restaurou conteúdo e modo dos quatro arquivos antigos, removeu os quatro novos e preservou escrita realizada em SQLite **sintético**. A prova anterior H2 cobre também metadados, falhas e limpeza de caches do rollback oficial. O dispatcher criou outro snapshot SQLite e backup dos arquivos em `/var/backups/linkfree-deploy/20261124-221327-5o7DSZ`, validando integridade. Não restaurar SQLite sobre produção: o rollback de código/configuração deve manter novas escritas; restauração de dados requer decisão específica. O plano usa o manifesto e backup correspondente, valida conteúdo/proprietário/modo, remove apenas os arquivos marcados como novos, limpa caches de configuração/rota/views e confirma SHA e serviços. Para nginx, restaurar o backup de configuração somente se houver alteração autorizada, testar `nginx -t` e fazer reload gracioso. Não foi necessário executar rollback.

## Deploy e homologação

`bash /root/atualizar-linkstack.sh --deploy` executou o mecanismo oficial; os oito arquivos foram publicados e os caches de configuração, rota e views limpos. O dispatcher confirmou SHA e homologação read-only sem falhas. Conferência independente byte a byte confirmou os oito arquivos iguais ao commit; `PRAGMA quick_check=ok`, `foreign_key_check` sem violações e 16 migrations antes e depois. nginx e PHP-FPM ativos; nenhuma entrada de erro identificada nos journals de ambos nem erro Laravel novo entre o início do deploy e a verificação.

Na origem HTTPS pública, com agente crawler:

- `/` e `/@Dev`: HTTP 200, canonical HTTPS, robots `index, follow`, Open Graph e X com mesma URL JPEG e dimensões declaradas 1200 × 630.
- As duas imagens retornaram HTTP 200 `image/jpeg`, ETag e foram decodificadas por GD como 1200 × 630.
- `/robots.txt` 200 com referência a sitemap; `/sitemap.xml`, `/sitemap-home.xml` e `/sitemap-profiles/1.xml` 200, XML válido e MIME esperado.
- `/login` e `/register` 200; `/admin/users` redirecionou visitante ao login. `.env`, SQLite, configuração avançada e XML arbitrário receberam 403, sem publicação de conteúdo.

[Resultados sanitizados](evidencias-seo-deploy/producao-sanitizada.json). GETs públicos foram executados conforme autorização; o agente crawler reduz efeitos em contadores, mas não se afirma que todos os acessos sejam isentos de efeitos de sessão ou logs. Não foi realizada homologação autenticada, OAuth/SMTP real, compartilhamento físico no WhatsApp nem avaliação dos caches externos. Noindex real de conta específica não foi alterado para teste; seu comportamento foi comprovado apenas na fixture sintética. Não houve janela de manutenção.

## Próximos passos

Revisar este relatório; avaliar visualização e atualização dos cards em dispositivos e serviços externos quando autorizado. A H3-C permanece pausada por instrução atual; o defeito de tradução TC-07-B permanece documentado e não foi corrigido por este deploy. Nenhuma H3-D foi iniciada.
