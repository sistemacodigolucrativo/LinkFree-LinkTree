# Temas do GitHub

Entrada administrativa: `/admin/github-themes`, também acessível em Gerenciar temas.
Repositório inicial: `sistemacodigolucrativo/LinkFree---Themes`.
Premium e VIP são equivalentes; categorias e aliases ficam em `config/theme-repository.php`.

O catálogo aceita `Free/Nome/*.zip` e `Premium/Nome/*.zip`, com exatamente um ZIP
e thumbnail.jpg, thumbnail.jpeg ou thumbnail.png por tema. Uma categoria vazia é normal.
O ZIP contém uma pasta de tema com readme.md. Mantém-se o formato de temas LinkStack,
inclusive config.php. Instale apenas pacotes de autores confiáveis.

Configuração e revisões ficam em `storage/app/github-themes.json` (não versionado).
Tokens ficam criptografados com APP_KEY e não retornam às views. Guarde APP_KEY junto
ao backup privado. Para token fine-grained: acesso ao repositório selecionado,
Contents: read-only e Metadata de leitura. Repositórios públicos dispensam token.
O servidor deve bloquear acesso HTTP a storage/app, como na configuração de produção.

Downloads são feitos pelo backend, fixados no commit do catálogo, e verificados pelo
SHA do blob Git. Atualizações comparam o SHA do ZIP, não seu nome. A interface consulta
novamente ao abrir ou clicar em Verificar atualizações. O atualizador legado encaminha
temas gerenciados ao catálogo e mantém a atualização dos demais temas.

ThemeInstaller é compartilhado por upload manual, GitHub e atualizador legado.
Valida caminhos, links simbólicos, pasta raiz, readme, quantidade e tamanho expandido.
A instalação prepara o conteúdo em staging e substitui somente a pasta de destino,
restaurando a anterior se a troca falhar. Temas já existentes sem vínculo com o catálogo
não são sobrescritos pela primeira importação GitHub.

Testes desta implementação: catálogo real (5 Free, 0 Premium), download com integridade,
instalação em ambiente isolado, segunda instalação sem alterações, rejeição de ../ no ZIP,
interface Chromium em 320/390/1280px, compilação Blade e sintaxe PHP.
Não foi realizado teste com credencial de repositório privado real.
