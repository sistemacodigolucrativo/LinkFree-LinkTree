# LinkFree / LinkStack

Plataforma de páginas de links baseada no LinkStack. Este guia explica como preparar uma VPS e iniciar o instalador web que acompanha o projeto.

## Antes de começar

- Uma VPS com **Ubuntu 24.04**, acesso SSH e um usuário com `sudo`.
- Um domínio ou subdomínio apontado para o IP público da VPS. Se for emitir certificado com Let's Encrypt, o DNS precisa estar propagado e as portas **80 e 443** devem estar acessíveis pela internet, inclusive no firewall da hospedagem.
- O IP público do computador ou da rede que você usará para abrir o instalador. O acesso ao assistente fica limitado a esse IP ou rede até a conclusão.
- Um e-mail para receber avisos do Let's Encrypt, ou um certificado e uma chave já emitidos.
- Um destino novo: por padrão, `/srv/linkfree`. O preparador não atualiza nem substitui uma instalação existente.

O preparador instala pacotes do sistema e habilita serviços. Leia [instal.sh](instal.sh) e [dependências.txt](dependências.txt) antes de executá-lo. O fluxo completo de instalação de pacotes e emissão de certificado ainda não foi validado em uma VPS limpa.

## 1. Acesse a VPS e baixe o preparador

Conecte-se por SSH. Se `curl` ainda não estiver instalado, instale-o primeiro:

```bash
sudo apt-get update
sudo apt-get install -y curl ca-certificates
```

Baixe o script e consulte as opções:

```bash
curl -fsSLo instal.sh https://raw.githubusercontent.com/sistemacodigolucrativo/LinkFree-LinkTree/main/instal.sh
bash instal.sh --help
```

## 2. Prepare a VPS

Substitua os exemplos pelo seu domínio, e-mail e IP público. O sufixo `/32` representa um único endereço IPv4; para IPv6, use um CIDR apropriado. O IP deve ser o da rede de onde você vai acessar o assistente, não necessariamente o IP da VPS.

```bash
sudo bash instal.sh \
  --domain links.seudominio.com \
  --admin-cidr 203.0.113.25/32 \
  --email voce@seudominio.com
```

O script clona a branch `main`, instala Nginx, PHP 8.3 e extensões, MariaDB, SQLite, Git, Composer, Node.js, npm, Certbot e ferramentas auxiliares. Ele verifica os requisitos de plataforma do Composer, copia a aplicação para `/srv/linkfree`, gera uma chave própria e prepara o Nginx para o instalador web. Também cria um banco e usuário MariaDB dedicados, disponíveis caso você escolha MySQL no assistente.

Para usar outro diretório novo, acrescente `--dest /caminho/absoluto`. O domínio informado não deve estar em uso por outro site na mesma configuração Nginx. O script não altera as regras do firewall nem configura o DNS.

Se você já possui certificado e chave, substitua `--email` por:

```bash
--cert /caminho/para/fullchain.pem --key /caminho/para/privkey.pem
```

Essas opções devem acompanhar `--domain` e `--admin-cidr` no comando principal. Para emissão automática, o Certbot usa uma configuração temporária de validação HTTP. **A renovação automática do certificado não foi validada por este script**; configure e teste a renovação antes do vencimento.

## 3. Abra o instalador web

Ao final, o preparador mostra o endereço HTTPS e o caminho do arquivo de credenciais do banco. Abra `https://links.seudominio.com` no navegador **a partir do IP ou rede informado em `--admin-cidr`** e siga as etapas do assistente:

1. Verifique as dependências exibidas na tela.
2. Escolha **SQLite** ou **MySQL**. SQLite já está preparado para uma instalação nova.
3. Se escolher MySQL, consulte o arquivo de credenciais cujo caminho o script informou ao final. Ele fica protegido em `/root`; leia-o na VPS com `sudo cat /root/linkfree-banco-<identificador>.txt`, substituindo pelo caminho exibido. Use os campos de host, porta, banco, usuário e senha no assistente. Não compartilhe esse arquivo.
4. Crie a conta administrativa e conclua todas as etapas de configuração.
5. Faça login, abra o painel e teste o salvamento de um perfil.

A rota `/skip` fica bloqueada pelo Nginx. O acesso restrito por IP ao assistente deixa de valer quando a instalação é concluída e os marcadores de instalação são removidos pela aplicação.

## Se algo falhar

- **O certificado não foi emitido:** confira domínio, DNS, portas 80/443 e conflito de configuração para o mesmo domínio. O destino pode já ter sido criado durante uma tentativa parcial; revise o estado antes de repetir o processo.
- **O navegador recebe 403 durante a instalação:** confirme que o IP público da sua conexão está incluído em `--admin-cidr`, incluindo eventual VPN ou proxy.
- **O destino já existe:** o preparador interrompe a execução para evitar sobrescrever arquivos. Escolha um novo diretório ou investigue a tentativa anterior.
- **MySQL não conecta:** confirme os valores no arquivo de credenciais informado e o estado do MariaDB. SQLite é uma alternativa disponível no assistente.

Não execute `config:cache`, `route:cache` ou `optimize` neste projeto: há consultas diretas a `env()` que podem falhar com esses caches. Consulte [orientações de publicação](linkstack/docs/DEPLOY.md) e [dependências.txt](dependências.txt) para mais detalhes.

O código original do LinkStack está licenciado sob [GPL-3.0 ou posterior](linkstack/LICENSE).
