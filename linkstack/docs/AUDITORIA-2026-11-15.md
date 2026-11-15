# Confronto com AUDITORIA_FINAL_LINKFREE_LINKTREE.zip

Base da auditoria: c673fe2 (ZIP original). Desenvolvimento antes desta intervenção: d063126. Produção foi comparada aos arquivos-base antes da aplicação; não houve divergência nos arquivos modificados.

## Correções implementadas

- Viewport das páginas independentes; geometria de PHP Info e instalador; alinhamento do identificador.
- Rotas/views de tipos de link, listagem/download de backups e proteção administrativa do diagnóstico legado.
- Cancelamento da restauração não deixa ResetAdvancedConfig no formulário. Testado sem restaurar configurações reais.
- Salvamento do CSS separado da geração; geração explícita preserva propriedades anteriores; formulário reparado; tradução JavaScript serializada; controle de cor nativo disponível em telas estreitas.
- Backups com CSS limitado ao componente e links fluidos; diagnóstico com quebra de conteúdo nas tabelas.
- Identificador duplicado das abas e destino do atalho de idioma corrigidos.
- Retorno de campos não sensíveis de autenticação via old().
- Marcação de paginação, títulos, ações, prévias e navegação corrigida; títulos nos iframes.
- Ações administrativas com controles nativos, delegação de eventos, timeout e tratamento de falha, sem repetição automática de exclusões.
- Alternativa de reordenação sem arrastar, usando a mesma persistência Sortable; seleção de tema por botão; compartilhamento e denúncia operáveis por teclado; proteção do download QR quando não há imagem/botão.
- Associação de rótulos estáticos e suporte aos formulários dinâmicos; nomes de controles e foco visível. Controles legados de aparência mantêm seus handlers e recebem semântica/ativação por teclado.

## Evidências executadas

Ambiente isolado `/root/linkstack-audit-stage`, banco separado `linkstack_audit`. Não foram usados os dados de produção para testes de gravação.

- Templates Blade compilados; PHP lint dos templates compilados, controllers, Livewire e rotas: sem erro.
- Chromium, largura 390: config, backups, listagem/criação/edição de tipos, diagnóstico, links, temas, usuários e perfil público retornaram 200. Sem pageerror nos caminhos registrados.
- CSS com color, border-radius e padding conservado ao acionar Salvar (envio interceptado). Textarea e botão pertencem ao mesmo formulário.
- Cancelar restauração: zero campos ResetAdvancedConfig após o cancelamento.
- Reordenar e recarregar: ordem persistida no banco isolado.
- Resposta 500 simulada na ação administrativa: controle recuperado e mensagem apresentada.
- Denúncia: Enter abre/fecha, link fechado não permanece acessível.
- Lista de tipos: scrollWidth igual ao viewport em 320/360/390/430/768/1280.
- Configuração, edição de usuário, editor CSS, tema e edição de tipo inspecionados a 320px para controles sem nome e ações aninhadas; falhas encontradas corrigidas.

## Limites registrados na primeira etapa (atualizados abaixo)

A auditoria contém 25 hipóteses para validação, não 25 defeitos comprovados. Não se afirma homologação integral desses cenários. Permanecem sem cobertura completa: dispositivos Android/iOS reais e teclado virtual; TalkBack/VoiceOver; contraste de todos os temas/estados; texto 200% em todas as páginas; fixtures extremas de todos os temas e editores; todas as combinações SMTP/QR PNG e SVG; upload/importação/exportação reais; instalação nova ponta a ponta e todos os fluxos de exclusão/restauração. Não houve alteração especulativa desses fluxos.

A acessibilidade global foi melhorada, mas a revisão exaustiva de todos os controles dinâmicos e dos 43 componentes descritos não está homologada. Não classificar a auditoria inteira como encerrada apenas pelos testes acima.

Backups e saídas detalhadas dos testes ficam em `/root/linkstack-audit-backup`, fora do diretório público e do Git. A publicação copia apenas os arquivos de código alterados, preservando `.env`, bancos, uploads e contas.


## Continuação: tela de adicionar blocos e verificações ampliadas

### Defeitos reproduzidos e corrigidos

- `/studio/add-link`: viewport 320px e documento 423px; botão Salvar e adicionar mais ultrapassava o card. A barra agora quebra em linhas e usa botões de largura disponível em telas pequenas. Não foi aplicado overflow hidden para mascarar a falha.
- Trocar email → telephone → link alterava `https://example.com/` para `tel:mailto:https://example.com/`. Os listeners globais acumulados foram removidos; cada handler PHP adiciona apenas o protocolo de seu tipo. Teste repetido: URL preservada, e-mail e telefone gravados com seus protocolos corretos.
- Salvar e adicionar mais passa pela validação nativa e não acumula campos param. O salvamento comum remove essa intenção anterior.
- Atributos de campos dinâmicos malformados foram corrigidos, inclusive campos do cartão de contato.
- PHP Info: documento de 934px em viewport 320px. A saída técnica completa foi isolada em iframe rotulado e rolável, sem recortar os dados e sem emitir dois documentos PHP Info no documento principal.
- Página de informações de Galaxy: container com 834px e origem horizontal negativa em celular. Conteúdo agora fica em fluxo normal, com largura limitada e quebra das linhas do README. Mesmo ajuste validado com PolySleek.
- Tema padrão sem nome produzia TypeError por trans(null); o título agora utiliza default quando não há tema selecionado.
- Leitura direta de QUERY_STRING gerava exceção em servidores que não definem a chave para uma URL sem parâmetros. As leituras passaram a usar valor vazio explícito; comportamento das URLs com parâmetros preservado.

### Testes concluídos nesta etapa

- 48 combinações: oito tipos de bloco × 320/360/390/430/768/1280px; documento sem transbordamento e sem pageerror registrado.
- Gravação efetiva dos oito tipos no banco isolado; confirmação dos valores mailto e tel no banco.
- 120 medições de largura em 20 URLs/telas do painel e páginas secundárias; todas as respostas testadas com sucesso e largura do documento igual à largura disponível após as correções.
- 36 combinações de tema/URL/largura: default, PolySleek e galaxy, perfil público e informações de tema, nas seis larguras. Sem transbordamento após correção.
- Cinco abas de configuração acionadas individualmente com clique, conteúdo visível e documento com 320px.
- Instalação nova SQLite: criação de administrador, conclusão das opções e login administrativo bem-sucedidos.
- Instalação nova MySQL: banco vazio, migrações e dados iniciais via assistente, criação de administrador, conclusão e login administrativo bem-sucedidos.
- Nas duas instalações: login inválido mantém e-mail, esvazia senha e permite autenticação posterior.
- Etapas inicial, dependências, seleção de banco, conexão MySQL e cadastro administrativo abertas a 320px sem transbordamento.
- Compilação Blade e verificação sintática PHP executadas novamente.

As verificações acima foram executadas em Chromium automatizado, com bancos descartáveis. Isso não equivale a teste físico em Android/iPhone, leitor de tela ou todos os conteúdos que um usuário possa criar. Esses limites de cobertura não são declarados como resultados aprovados. Os registros ficam fora do diretório público em `/root/linkstack-audit-backup`.

### Verificação após publicação

A tela /studio/add-link foi aberta no hostname HTTPS de produção com uma sessão administrativa temporária criada para a verificação. HTTP 200, documento 320px em viewport 320px e três ações visíveis. Captura de tela inspecionada. Sessão temporária removida ao concluir; nenhuma conta ou link de produção foi criado pelo teste. Arquivos publicados conferidos contra o desenvolvimento.
