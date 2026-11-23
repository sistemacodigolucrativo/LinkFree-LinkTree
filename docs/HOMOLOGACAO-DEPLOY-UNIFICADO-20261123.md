# LinkFree — homologação técnica e deploy unificado (23/11/2026)

> **Situação operacional posterior (23/11/2026):** PR #14 integrada à `main` e M13 publicada na VPS no commit `3f20bf95e75f`. Migrações 000002–000004 concluídas; homologação técnica automática read-only com 0 falhas e 0 revisões manuais. A validação funcional 01–10 continua pendente. Leia [Estado operacional M13 e evidências de produção](STATUS-PRODUCAO-M13-20261123.md). O restante deste arquivo documenta etapas históricas de preparação, não o estado atual.

## Evidências confirmadas

### Produção — banco e versão publicados
Após o deploy de consentimento, o administrador do servidor confirmou:

- `/var/lib/linkfree-deploy/last-successful-sha`: `f8f3ddc82437502d829dddeae4d5ab375cb9a54d`.
- `php artisan migrate:status`: `2026_11_23_000001_create_registration_consents_table .... [2] Ran`.

Esses registros confirmam versão e migração. Não comprovam isoladamente a aplicação autenticada.

### Produção — testes públicos externos
O GitHub Actions executou HTTP GET sobre o site real, sem contas e sem alteração deliberada de dados:
- `https://linkfree.website/`: HTTP **200**.
- `https://linkfree.website/login`: HTTP **200**.
- `https://linkfree.website/register`: HTTP **200**, formulário contém `accept_terms` e token CSRF `_token`.
- Rota intencionalmente inexistente: HTTP **404**.

Run de referência: https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45274549304

### Testes isolados Laravel/SQLite
O workflow anterior de consentimento verificou a migração, gravação e hash de aceite, transações com rollback, cadastro normal com/sem aceite, OAuth simulado com/sem aceite e login social sem duplicação, além de leitura/escrita do storage.

CI de referência: https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/actions/runs/45268692650

**Limitação honesta:** sem acesso SSH, credenciais de homologação e fluxo de OAuth real, não se pode declarar aprovados os formulários reais autenticados da VPS. O novo script operacional cobre o máximo possível de modo seguro e read-only.

## O que foi corrigido no sistema de deploy

O alias atual do root foi identificado:
```bash
alias deploy='bash /root/atualizar-linkstack.sh --check && bash /root/atualizar-linkstack.sh --deploy'
```

O alias estava executando uma **versão desatualizada** em `/root/atualizar-linkstack.sh`. Para preservar a experiência, não precisamos reescrever aliases nem editar `.bashrc`.

O novo instalador `scripts/instalar-deploy-unificado.sh`:

1. Verifica a instalação e cria um backup datado do script root original.
2. Substitui o arquivo por um bootstrap pequeno e validado sintaticamente.
3. Em cada `deploy`, esse bootstrap busca a `main` da origem GitHub autorizada e executa o atualizador da versão publicada no repositório.
4. `--check` e `--deploy` continuam funcionando com o alias existente.
5. O `--deploy` executa a migração **aditiva conhecida** de consentimento quando pendente, após preflight e backup. Não autoriza migrações arbitrárias, destrutivas ou desconhecidas.
6. Após a publicação, executa automaticamente a homologação técnica read-only. Em caso de falha no smoke, reporta o problema e **não reverte automaticamente o banco**.

O script `scripts/atualizar-linkstack.sh` também passa a fazer `cd /` antes das chamadas `runuser`, eliminando o erro de diretório `/root: Permission denied`.

## Instalação na VPS — única vez

**Pré-requisito:** a PR de deploy unificado deve estar integrada à `main` e o CI deve estar aprovado.

Este comando só instala o bootstrap e executa uma homologação read-only. Não publica arquivos da aplicação nem aplica migrações:

```bash
cd / && sudo -u ubuntu git -C /home/ubuntu/src-Projects/LinkFree-LinkTree fetch origin main && sudo -u ubuntu git -C /home/ubuntu/src-Projects/LinkFree-LinkTree show origin/main:scripts/instalar-deploy-unificado.sh > /root/linkfree-instalar-deploy.sh && test -s /root/linkfree-instalar-deploy.sh && bash -n /root/linkfree-instalar-deploy.sh && bash /root/linkfree-instalar-deploy.sh && bash /root/atualizar-linkstack.sh --homologar
```

Após revisar a saída da homologação e resolver quaisquer falhas, o comando para atualizar versões futuras continua sendo simplesmente:

```bash
deploy
```

Não utilizar `--deploy-consent` repetidamente. O parâmetro foi mantido apenas para compatibilidade de instalações antigas.

## Como interpretar a homologação da VPS

Arquivo: `scripts/homologar-linkfree.sh`. Modo único: `--read-only`.

Checa:
- SHA do último deploy registrado;
- integridade do SQLite, tabela/colunas e registro de migração;
- presença dos Termos e Política de Privacidade;
- permissões `www-data` do banco e dos diretórios persistentes;
- inexistência de symlinks no storage;
- backup consistente associado ao SHA;
- HTTPS da Home, login, registro, 404 e proteção do Studio.

Não cadastra usuários, não escreve no banco, não recebe senhas e não imprime conteúdo privado. Avisos de registro desativado ou ausência de backup localizado são apresentados como **revisão manual**, não como aprovação irrestrita.

### Critério de encerramento

- CI do código: aprovado.
- Endpoints públicos: aprovados.
- Migração e SHA de produção: confirmados pelo terminal da VPS.
- Bootstrap do alias `deploy`: implementado e aprovado no CI; **instalação na VPS ainda precisa ser executada pelo operador**.
- Homologação read-only da VPS: **aguarda execução do comando acima**.
- Testes end-to-end autenticados e OAuth externo: pendentes, pois dependem de ambiente/contas de homologação.

Não confundir **atualização entregue no GitHub** com **instalação efetivamente aplicada na VPS**.
