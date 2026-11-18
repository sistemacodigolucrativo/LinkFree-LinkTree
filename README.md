# LinkFree / LinkStack

## Instalação em uma VPS

**Um comando para iniciar.** O preparador faz perguntas, clona a branch `main`, prepara a VPS e entrega o endereço do instalador web original. Em uma VPS Ubuntu Server 24.04, conecte-se por SSH e execute:

```bash
sudo bash -c 'command -v curl >/dev/null || { apt-get update && apt-get install -y curl ca-certificates; }; f=$(mktemp); trap '\''rm -f "$f"'\'' EXIT; curl -fsSL https://raw.githubusercontent.com/sistemacodigolucrativo/LinkFree-LinkTree/main/instal.sh -o "$f" && bash "$f" </dev/tty'
```

O comando exige acesso `sudo`, conexão com a internet e um terminal SSH interativo. Revise o [script](instal.sh) antes de executá-lo, se desejar.

### O que o assistente pergunta

- **Acesso por domínio ou IP público:** o IP da VPS é detectado, com consulta ao IMDSv2 em EC2 quando disponível e alternativa de detecção externa. No modo IP, confirme o endereço detectado ou corrija-o.
- **Domínio e certificado:** para domínio, informe o nome e escolha emitir certificado Let's Encrypt ou fornecer os caminhos de certificado e chave existentes. Para Let's Encrypt, informe um e-mail. O domínio precisa apontar para a VPS e as portas 80/443 precisam estar acessíveis. Se o DNS não estiver pronto, corrija-o e tente novamente no próprio assistente.
- **Banco:** SQLite é recomendado para começar; MariaDB pode ser preparado automaticamente. A escolha definitiva continua no instalador web.
- **Diretório:** o padrão é `/srv/linkfree`; é possível escolher outro diretório novo.

No modo IP, a URL usa **HTTP**: senhas e dados não são criptografados. Use uma rede confiável e configure HTTPS com um domínio antes de usar o sistema em produção. Não é solicitado e-mail nem emitido certificado para o IP.

### O que o preparador faz

Ele verifica e instala os pacotes necessários para Nginx, PHP 8.3/FPM e extensões, Git, Composer e SQLite; MariaDB e Certbot são instalados quando escolhidos. Node.js e npm não são necessários para executar os arquivos visuais já compilados; consulte [dependências.txt](dependências.txt) para desenvolvimento e integrações opcionais. O script verifica os requisitos de plataforma do Composer, prepara uma cópia nova da aplicação, valida o Nginx e inicia os serviços necessários.

Se escolher MariaDB, o preparador cria um banco e um usuário dedicados. As credenciais são geradas automaticamente e salvas em arquivo protegido em `/home/ubuntu/Secrets` quando esse usuário existe; caso contrário, em `/root/Secrets`. O caminho é mostrado ao final. Não compartilhe o arquivo.

Para Let's Encrypt, o desafio HTTP permanece configurado para renovação, o temporizador do Certbot é ativado e uma simulação de renovação é solicitada. Confira os avisos: uma falha no teste de renovação exige correção antes do vencimento. Um certificado fornecido pelo usuário deve ser renovado pelo mecanismo usado para obtê-lo.

Ao final, abra a URL HTTPS ou HTTP apresentada, e siga o instalador web para selecionar o banco, criar o administrador e concluir a configuração. Se escolher MySQL no assistente, use os dados do arquivo de credenciais indicado. A rota `/skip` é bloqueada. O assistente fica acessível publicamente; conclua a instalação e crie o administrador assim que possível.

### Verificação de DNS

Antes da emissão do certificado, o instalador compara os registros A e AAAA obtidos de resolvedores públicos com o IPv4 público e os IPv6 globais ativos da VPS. Os resultados também são comparados com o resolvedor local para diagnosticar cache ou `/etc/hosts`. Respostas divergentes são apresentadas individualmente; um endereço extra que não esteja nesta VPS pode receber tráfego ou o desafio ACME e deve ser corrigido. Se houver um AAAA válido, o Nginx também escuta em IPv6. DNS não comprova que portas, firewall e roteamento estejam acessíveis externamente; o Certbot ainda precisa validar o desafio.

Para repetir apenas o diagnóstico, sem instalar pacotes ou alterar a instalação, baixe o script e execute (requer Python 3, curl, `ip` e acesso HTTPS):

```bash
curl -fsSL https://raw.githubusercontent.com/sistemacodigolucrativo/LinkFree-LinkTree/main/instal.sh -o instal.sh
bash instal.sh --dns-check --domain linkfree.website --public-ip 13.185.135.57
```

Troque domínio e IP pelos seus; `--public-ip` é opcional e o instalador tenta IMDSv2 na EC2 e depois um serviço externo. O serviço externo pode mostrar um IP de saída NAT que não seja o IP da VPS. Durante a instalação, Enter após uma falha repete somente a consulta de DNS, sem reinstalar dependências. Para executar os testes: `python3 -m unittest discover -s tests -p 'test_dns_installer.py' -v`.

### Cloudflare

Quando o DNS retornar endereços da Cloudflare, o script reconhece o proxy e configura o Nginx para aceitar `CF-Connecting-IP` apenas das redes oficiais obtidas durante a instalação. Use SSL/TLS **Full (strict)** e confirme no painel da Cloudflare que o endereço de origem aponta para a VPS. O DNS público de um domínio com proxy mostra endereços da Cloudflare, portanto o preparador não consegue comprovar automaticamente o IP de origem configurado no painel. Mantenha a lista de redes da Cloudflare atualizada.

### Recuperação e diagnóstico

O log técnico fica em `/var/log/linkfree-preparer.log`. Dados do banco ficam no arquivo protegido indicado ao final, não no log. A instalação não substitui um destino existente. Quando há um estado de preparação reconhecido e o instalador web ainda não foi concluído, execute novamente o comando e informe as mesmas opções para retomar sem substituir o banco ou a configuração. Se a interrupção ocorrer antes de o estado ser registrado, ou se arquivos gerenciados tiverem sido alterados, revise o estado manualmente; o script interrompe para preservar os dados.

O preparador valida o acesso à origem; a disponibilidade pela internet ainda depende de DNS, firewall, regras da nuvem, proxy e certificado. Não abre portas no firewall nem altera DNS ou configurações da Cloudflare. Se uma emissão ou renovação falhar, verifique DNS, porta 80 e proxy. Em instalações com outros serviços, revise conflitos de portas e domínios antes de executar.

### Opções avançadas

`sudo bash instal.sh --help` mostra as opções por linha de comando. Para uma execução automatizada, forneça modo, endereço, banco e, no modo domínio, certificado ou e-mail; `LINKFREE_NONINTERACTIVE=1` desativa a pergunta sobre o diretório padrão. O modo `--prepare-only` serve para preparar uma cópia local de testes e não configura serviços.

Não execute `config:cache`, `route:cache` ou `optimize`: o projeto utiliza `env()` diretamente. Consulte [orientações de publicação](linkstack/docs/DEPLOY.md).

### Limites de validação

O preparador foi testado de forma isolada nos modos IP e domínio, com certificado de teste antes da remoção da restrição por IP, e em uma simulação de proxy Cloudflare e renovação ACME. O assistente web foi testado anteriormente com SQLite e com migrações e dados iniciais MySQL. **Não houve instalação integral em VPS Ubuntu recém-criada nem emissão ou renovação real de certificado pela autoridade certificadora**; os testes isolados simularam instalação de pacotes, clonagem e, no cenário de ACME, o Certbot. Verifique esses pontos no ambiente final.

Projeto baseado no LinkStack, licenciado sob [GPL-3.0 ou posterior](linkstack/LICENSE).
