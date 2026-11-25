# Homologação pós-deploy — PR #33 / LinkFree

**Data:** 25/11/2026  
**Ambiente:** produção (`https://linkfree.website`)  
**Origem das evidências:** saída do atualizador/homologador da VPS fornecida pelo operador nesta conversa e roteiro de navegação manual realizado com o operador. Não houve acesso administrativo remoto pela IA.  
**PR:** [#33](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/pull/33) — integrada à `main`  
**SHA publicado, conforme saída da VPS:** `88e508554f99faa43fb16142c864897a0da65bf1`  
**SHA anterior, conforme saída da VPS:** `b8babc38b1fb`

## 1. Publicação e verificações técnicas informadas pela VPS

O operador executou:

```bash
bash /root/atualizar-linkstack.sh --check && bash /root/atualizar-linkstack.sh --deploy
```

O nome `atualizar-linkstack.sh` sozinho não foi encontrado no `PATH` do `sudo`; a execução com caminho absoluto `/root/atualizar-linkstack.sh` funcionou.

**Preflight:** simulação aprovada, sem modificação da aplicação, com oito arquivos de código previstos para publicação:

1. `app/Http/Controllers/AdminController.php`
2. `resources/views/components/config/alternative-config.blade.php`
3. `resources/views/layouts/sidebar.blade.php`
4. `resources/views/panel/config-editor.blade.php`
5. `resources/views/panel/maintenance-operations.blade.php`
6. `resources/views/panel/new-user.blade.php`
7. `resources/views/panel/phpinfo.blade.php`
8. `routes/web.php`

**Deploy:** `PUBLICACAO CONCLUIDA: 88e508554f99`. O script declarou limpeza dos caches de configuração, rotas e views. Backup informado em `/var/backups/linkfree-deploy/20261125-165120-vPSNO7`, com SQLite `sqlite-before-deploy.sqlite`.

**Pós-deploy read-only:** o homologador informou **0 falhas, 0 verificações pendentes de revisão manual dentro do escopo técnico executado** e `HOMOLOGAÇÃO TÉCNICA READ-ONLY APROVADA`. Itens informados como OK: SHA registrado, integridade SQLite, tabela `registration_consents` e suas colunas, migração de consentimento, textos de Termos/Privacidade, leitura/permissões PHP-FPM, diretórios persistentes, ausência de symlinks em storage privado, backup SQLite íntegro, respostas HTTPS 200 para home e login, registro público acessível, aceite e CSRF no cadastro, 404 em rota desconhecida e conteúdo privado não exposto ao visitante anônimo.

**Limite:** essas verificações não executaram cadastro público real, OAuth real ou navegação autenticada na VPS; os resultados autenticados abaixo foram observados manualmente pelo operador.

## 2. Navegação administrativa — teste manual guiado

O operador percorreu os destinos da barra lateral e confirmou que abriram sem erro aparente. O registro abaixo descreve **carregamento visual reportado**, não prova funcionalidade integral de cada módulo.

| Nº | Destino da sidebar (código da PR #33) | Observação do operador |
|---|---|---|
| 01 | Visão geral administrativa | Abriu normalmente |
| 02 | Gerenciar usuários | Abriu normalmente |
| 03 | Novo usuário | Abriu normalmente |
| 04 | Indicações | Abriu normalmente |
| 05 | Solicitações de selo | **Correspondência do rótulo não confirmada**: o operador mencionou “solicitação de suporte” e informou abertura normal; o código da sidebar denomina este destino “Solicitações de selo” |
| 06 | Central de denúncias | Abriu normalmente |
| 07 | Personalização do site | Abriu normalmente |
| 08 | Páginas do rodapé | Abriu normalmente |
| 09 | Gerenciar temas | Abriu normalmente |
| 10 | Temas GitHub | Abriu normalmente |
| 11 | Tipos de bloco | Abriu normalmente |
| 12 | Configurações do sistema | Abriu normalmente |
| 13 | Backups | Abriu normalmente |
| 14 | Configurações avançadas | Abriu normalmente; operador não observou alertas inesperados |
| 15 | Ambiente ENV | Abriu normalmente |
| 16 | Informações PHP | Abriu normalmente |
| 17 | Atualizações | Abriu normalmente |

**Esclarecimento de nomenclatura:** “Operações de manutenção” não é um 18º item de menu: corresponde às páginas de consulta/aviso de **Backups** e **Atualizações**. Não foi executada operação de manutenção a partir do navegador.

**Resultado:** navegação percorrida com sucesso reportado nos 17 passos; a identificação precisa do item 05 permanece não confirmada. O teste não verificou, por si só, todas as permissões, persistências, alterações ou resoluções.

## 3. Criação de usuário — teste manual funcional

O operador declarou ter preenchido e enviado intencionalmente o formulário de **Novo usuário** e recebido sucesso na criação. Após instrução para abrir **Gerenciar usuários**, confirmar presença e ausência de duplicação, respondeu “Tá tudo certo”.

**Resultado reportado:** criação intencional e listagem de uma conta sem duplicação aparente — **aprovado no roteiro manual realizado**. Não foram capturadas evidências independentes de banco, logs, ID ou conta; nenhuma credencial foi coletada. A mera abertura da página não foi tratada como criação.

## 4. Configurações avançadas e manutenção

- **Configurações avançadas:** página abriu, sem alerta inesperado relatado. **Não** foi testada gravação ou restauração de parâmetros.
- **Backups / Atualizações:** páginas abriram durante o roteiro de navegação; operações destrutivas, restauração e atualização pelo navegador **não** foram executadas.
- **Ambiente ENV / PHP:** acesso visual reportado; nenhum segredo ou conteúdo de configuração foi compartilhado nesta homologação.

## 5. Dashboard, perfil e responsividade do membro

- **Dashboard de membro:** operador entrou com conta de membro e informou carregamento sem erro.
- **Meu perfil:** operador confirmou abertura sem erro; não foi testada edição de dados.
- **Responsividade básica:** após orientação para testar layout estreito/celular, operador declarou não observar botões ou conteúdos cortados. **Aprovação apenas da inspeção visual informal reportada**, sem matriz de dimensões, dispositivos, navegadores ou capturas de tela.

## 6. Testes automatizados da PR #33 e exceção registrada

No head `bca238873fd9e3a53e8598f82b49d208bf7b53d4`, anterior ao merge, os checks concluíram assim:

| Check | Conclusão |
|---|---|
| `php-lint` | success |
| `http-behavior` | success |
| `installer-isolated (sqlite)` | success |
| `installer-isolated (mysql)` | success |
| `regression` | success |
| `studio-layout` | success |
| `seo-http` | success |
| `browser-a11y` | **skipped** — execução automática desativada após bloqueios recorrentes; preservado para `workflow_dispatch` manual |

**Sete checks executados e aprovados; um check adiado, não aprovado.** Não converter `skipped` em `success`. A suíte extensiva de navegador/axe, acessibilidade WCAG completa, RBAC e todas as combinações de resolução não foi revalidada no head final por esse job.

## 7. Conclusão e pendências objetivas

**Resultado:** publicação da PR #33 informada como concluída e homologação técnica **read-only** aprovada na VPS; os testes visuais e a criação de usuário foram declarados funcionais pelo operador na produção, dentro do escopo descrito.

**Não declarar homologação funcional universal.** Permanecem sem evidência de execução final: `browser-a11y` manual, bateria de acessibilidade/dispositivos, cadastro público real, OAuth, edição e reversão de configurações, restauração de backup, ações destrutivas e fluxos autenticados não percorridos. A inconsistência de nomenclatura do destino 05 pode ser esclarecida em revisão futura, sem impedir o registro dos demais resultados.

**Referências:** [auditoria de navegação](AUDITORIA-NAVEGACAO-ADMIN-20261125.md) · [auditoria de estabilidade do CI](AUDITORIA-ESTABILIDADE-QA-PR33-20261125.md) · [merge da PR #33](https://github.com/sistemacodigolucrativo/LinkFree-LinkTree/commit/88e508554f99faa43fb16142c864897a0da65bf1).
