#!/usr/bin/env bash
# Prepara uma VPS Ubuntu 24.04 para o assistente web do LinkFree.
set -Eeuo pipefail
umask 027
usage() {
  cat <<'HELP'
Uso interativo: sudo bash instal.sh
Uso avançado: sudo bash instal.sh --mode ip --public-ip IP --database sqlite [--dest /srv/linkfree]
              sudo bash instal.sh --mode domain --domain dominio --database mysql (--email email | --cert arquivo --key arquivo) [--dest /srv/linkfree]
Teste local:  bash instal.sh --prepare-only /caminho/novo --url http://localhost:8080

Prepara uma VPS Ubuntu 24.04, clona main e abre o instalador web original.
SQLite usa dependências locais; MariaDB é instalado apenas se escolhido.
No modo domínio, --email solicita Let's Encrypt. DNS e portas 80/443 precisam funcionar.
No modo IP a conexão usa HTTP sem criptografia. Use apenas rede confiável.
O instalador fica acessível pelo link; a rota /skip é bloqueada.
Para evitar a pergunta de diretório em automações, use LINKFREE_NONINTERACTIVE=1.
O modo --prepare-only requer uma cópia completa do repositório local.
O destino existente só pode ser retomado quando pertence à preparação identificada.

HELP
}
fail() { printf 'Erro: %s\n' "$*" >&2; exit 1; }
repo=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)
domain= cidr= cert= key= email= dest=/srv/linkfree prepare= url= mode= db= cloudflare=0 public_ip=
while (($#)); do
 case "$1" in
  --help|-h) usage; exit 0;;
  --domain|--cert|--key|--email|--dest|--prepare-only|--url|--mode|--database|--public-ip)
   (($# >= 2)) || fail "Falta valor para $1"
   case "$1" in --domain) domain=$2;; --cert) cert=$2;; --key) key=$2;; --email) email=$2;; --mode) mode=$2;; --database) db=$2;; --public-ip) public_ip=$2;; --dest) dest=$2;; --prepare-only) prepare=$2;; --url) url=$2;; esac; shift 2;;
  --cloudflare) cloudflare=1; shift;;
  *) fail "Opção desconhecida: $1";;
 esac
done
# An interactive terminal is required; shell pipelines must not consume answers.
ask() { local reply; read -r -p "$1" reply </dev/tty || fail 'Não foi possível ler o terminal.'; REPLY=$reply; }
detect_public_ip() {
 local token result
 token=$(curl --noproxy '*' -fsS --max-time 2 -X PUT -H 'X-aws-ec2-metadata-token-ttl-seconds: 60' http://169.254.169.254/latest/api/token 2>/dev/null || true)
 if [[ -n $token ]]; then
  result=$(curl --noproxy '*' -fsS --max-time 2 -H "X-aws-ec2-metadata-token: $token" http://169.254.169.254/latest/meta-data/public-ipv4 2>/dev/null || true)
 fi
 if [[ -z ${result:-} ]]; then result=$(curl -4fsS --max-time 5 https://api.ipify.org 2>/dev/null || true); fi
 printf '%s' "${result:-}"
}
valid_ip() { python3 - "$1" <<'PYIP'
import ipaddress,sys
try:
 ip=ipaddress.ip_address(sys.argv[1]); assert ip.version == 4 and ip.is_global
except (ValueError,AssertionError): sys.exit(1)
PYIP
}
if [[ -n $prepare ]]; then
 dest=$prepare
 [[ $url =~ ^https?://[a-zA-Z0-9.:-]+$ ]] || fail 'Informe --url, por exemplo http://localhost:8080.'
else
 [[ $EUID == 0 ]] || fail 'Execute como root ou com sudo.'
 [[ -r /etc/os-release ]] || fail 'Sistema não identificado.'
 . /etc/os-release
 [[ ${ID:-} == ubuntu && ${VERSION_ID:-} == 24.04 ]] || fail 'O preparador suporta Ubuntu Server 24.04.'
 command -v python3 >/dev/null && command -v curl >/dev/null || fail 'Python 3 e curl são necessários para iniciar.'
 if [[ -z $mode && -n $domain ]]; then mode=domain; fi
 if [[ -z $mode ]]; then
  printf '\nComo deseja acessar o LinkFree?\n1. Domínio (recomendado)\n2. IP público (HTTP)\n'
  while :; do ask 'Escolha 1 ou 2: '; case "$REPLY" in 1) mode=domain; break;; 2) mode=ip; break;; esac; done
 fi
 [[ $mode == domain || $mode == ip ]] || fail 'Modo inválido.'
 if [[ -z $public_ip ]]; then public_ip=$(detect_public_ip); fi
 while ! valid_ip "$public_ip"; do ask 'Não foi possível detectar o IP público IPv4 da VPS. Informe-o: '; public_ip=$REPLY; done
 if [[ $mode == ip ]]; then
  printf 'IP público detectado: %s\n' "$public_ip"
  if [[ -t 0 ]]; then ask 'Está correto? [S/n] '; if [[ $REPLY =~ ^[Nn] ]]; then ask 'Informe o IP público: '; public_ip=$REPLY; valid_ip "$public_ip" || fail 'IP público inválido.'; fi; fi
  domain=$public_ip; url="http://$domain"
  printf 'Atenção: HTTP não cifra senhas ou dados. Use uma rede confiável.\n'
 else
  if [[ -z $domain ]]; then ask 'Domínio ou subdomínio: '; domain=${REPLY,,}; fi
  [[ $domain =~ ^[a-zA-Z0-9]([a-zA-Z0-9.-]*[a-zA-Z0-9])?$ && $domain == *.* && $domain != *..* ]] || fail 'Domínio inválido.'
  url="https://$domain"
  if [[ -z $cert && -z $key && -z $email ]]; then
   printf 'Certificado: 1. Emitir automaticamente (recomendado)  2. Usar certificado existente\n'
   ask 'Escolha [1]: '
   if [[ $REPLY == 2 ]]; then ask 'Caminho absoluto do certificado: '; cert=$REPLY; ask 'Caminho absoluto da chave: '; key=$REPLY
   elif [[ -z $REPLY || $REPLY == 1 ]]; then ask 'E-mail para avisos do certificado: '; email=$REPLY
   else fail 'Opção de certificado inválida.'; fi
  fi
  if [[ -n $email ]]; then
   [[ -z $cert && -z $key && $email =~ ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+$ ]] || fail 'E-mail inválido ou opções de certificado incompatíveis.'
  else
   [[ -f $cert && -r $cert && -f $key && -r $key && $cert =~ ^/[a-zA-Z0-9_./-]+$ && $key =~ ^/[a-zA-Z0-9_./-]+$ ]] || fail 'Certificado ou chave inválidos.'
  fi
 fi
 if [[ -z $db ]]; then
  printf 'Banco de dados: 1. SQLite (recomendado)  2. MariaDB/MySQL\n'
  while :; do ask 'Escolha [1]: '; case "$REPLY" in ''|1) db=sqlite; break;; 2) db=mysql; break;; esac; done
 fi
 [[ $db == sqlite || $db == mysql ]] || fail 'Banco inválido.'
 if [[ $dest == /srv/linkfree && -z ${LINKFREE_NONINTERACTIVE:-} ]]; then
  ask 'Usar /srv/linkfree como diretório? [S/n] '
  if [[ $REPLY =~ ^[Nn] ]]; then ask 'Diretório absoluto novo: '; dest=$REPLY; fi
 fi

fi
[[ $dest == /* && $dest != / && ! -L $dest ]] || fail 'O destino deve ser absoluto e não pode ser um link simbólico.'
resuming=0
state_dir=/var/lib/linkfree-preparer
if [[ -e $dest ]]; then
 if [[ -n $prepare || ! -f $state_dir/$domain.state || ! -f $dest/INSTALLING ]]; then fail 'O destino já existe; não será sobrescrito.'; fi
 if ! python3 - "$state_dir/$domain.state" "$dest" "$mode" "$db" "$cidr" "$url" <<'PYSTATE'
import json,sys
try:
 d=json.load(open(sys.argv[1])); assert [d[k] for k in ('dest','mode','db','cidr','url')]==sys.argv[2:]
except (OSError,KeyError,ValueError,AssertionError): sys.exit(1)
PYSTATE
 then fail 'Estado de retomada não corresponde às opções informadas; instalação preservada.'; fi
 resuming=1; printf 'Retomando preparação anterior, preservando a aplicação e os dados.\n'
fi
site_existing=0
if [[ -z $prepare && ( -e /etc/nginx/sites-available/linkfree-$domain || -e /etc/nginx/sites-enabled/linkfree-$domain ) ]]; then
 [[ $resuming == 1 && -f $state_dir/$domain.site.sha256 ]] || fail 'Já existe configuração Nginx com esse nome.'
 (cd /etc/nginx/sites-available && sha256sum -c "$state_dir/$domain.site.sha256" >/dev/null) || fail 'Configuração Nginx foi modificada; revisão manual necessária.'
 [[ -L /etc/nginx/sites-enabled/linkfree-$domain && $(readlink /etc/nginx/sites-enabled/linkfree-$domain) == /etc/nginx/sites-available/linkfree-$domain ]] || fail 'Link Nginx existente não corresponde à instalação.'
 site_existing=1
fi
[[ $dest =~ ^/[a-zA-Z0-9_./-]+$ && $dest != *..* ]] || fail 'Use um caminho de destino simples, sem espaços ou ..'
if [[ -z $prepare ]]; then
 umask 077
 log_file=/var/log/linkfree-preparer.log
 touch "$log_file"
 chmod 600 "$log_file"
 exec > >(tee -a "$log_file") 2>&1
 printf 'Preparação LinkFree iniciada em %s. Log: %s\n' "$(date -u +%FT%TZ)" "$log_file"
 export DEBIAN_FRONTEND=noninteractive
 packages=(nginx php8.3-fpm php8.3-cli php8.3-bcmath php8.3-curl php8.3-mbstring php8.3-xml php8.3-sqlite3 php8.3-mysql php8.3-gd php8.3-zip sqlite3 git unzip curl ca-certificates openssl python3 composer dnsutils)
 [[ $db == mysql ]] && packages+=(mariadb-server)
 [[ $mode == domain && -n $email ]] && packages+=(certbot)
 missing=()
 for pkg in "${packages[@]}"; do dpkg-query -W -f='${Status}' "$pkg" 2>/dev/null | grep -qx 'install ok installed' || missing+=("$pkg"); done
 if ((${#missing[@]})); then apt-get update; apt-get install -y "${missing[@]}"; fi
 [[ $db != mysql ]] || systemctl enable --now mariadb
 if [[ $site_existing != 1 ]] && nginx -T 2>/dev/null | python3 -c 'import re,sys; target=sys.argv[1].lower(); text=sys.stdin.read(); sys.exit(0 if any(target in x.lower().split() for x in re.findall(r"(?m)^\s*server_name\s+([^;]+);",text)) else 1)' "$domain"; then
  fail "Já existe um site Nginx com server_name $domain; configuração preservada."
 fi
 cf_file=$(mktemp /tmp/linkfree-cf.XXXXXXXX)
 if [[ $mode == domain ]]; then
  if ! { curl -fsS --max-time 15 https://www.cloudflare.com/ips-v4; curl -fsS --max-time 15 https://www.cloudflare.com/ips-v6; } > "$cf_file"; then
   fail 'Não foi possível obter os IPs oficiais da Cloudflare; verifique a conexão antes de continuar.'
  fi
  while :; do
   dns_result=$(python3 - "$domain" "$public_ip" "$cf_file" <<'PYDNS'
import ipaddress
import subprocess
import sys

domain, public_ip, cf_file = sys.argv[1:4]
try:
    networks = [ipaddress.ip_network(line.strip()) for line in open(cf_file) if line.strip()]
    if not any(net.version == 4 for net in networks) or not any(net.version == 6 for net in networks):
        raise ValueError('faixas da Cloudflare incompletas')
    expected = ipaddress.IPv4Address(public_ip)
except (OSError, ValueError) as exc:
    print(f'Falha nos dados de validação: {exc}', file=sys.stderr)
    print('invalido')
    sys.exit(0)

def lookup(server, kind):
    result = subprocess.run(
        ['dig', '+time=2', '+tries=1', '+short', f'@{server}', domain, kind],
        capture_output=True, text=True, timeout=5, check=False,
    )
    if result.returncode:
        raise RuntimeError(result.stderr.strip() or f'consulta {kind} falhou')
    addresses = set()
    for line in result.stdout.splitlines():
        try:
            address = ipaddress.ip_address(line.strip())
        except ValueError:
            continue  # Um CNAME pode aparecer antes do endereço numérico.
        if address.version == (4 if kind == 'A' else 6):
            addresses.add(address)
    return addresses

validations = []
for resolver in ('1.1.1.1', '8.8.8.8'):
    try:
        a_records = lookup(resolver, 'A')
        aaaa_records = lookup(resolver, 'AAAA')
    except (OSError, RuntimeError, subprocess.TimeoutExpired) as exc:
        print(f'DNS {resolver}: consulta indisponível ({exc})', file=sys.stderr)
        continue
    addresses = a_records | aaaa_records
    print(
        f'DNS {resolver}: A={",".join(sorted(map(str, a_records))) or "nenhum"}; '
        f'AAAA={",".join(sorted(map(str, aaaa_records))) or "nenhum"}',
        file=sys.stderr,
    )
    if addresses and all(any(address in net for net in networks) for address in addresses):
        state = 'cloudflare'
    elif a_records == {expected} and not aaaa_records:
        state = 'direto'
    else:
        state = 'invalido'
        if aaaa_records:
            print('Existe registro AAAA: verifique/remova IPv6 se a VPS não dispõe dele. '
                  'O Let’s Encrypt pode priorizar IPv6.', file=sys.stderr)
        if a_records != {expected}:
            print(f'O registro A não corresponde exclusivamente ao IP esperado {expected}.', file=sys.stderr)
    validations.append(state)

if not validations:
    print('Nenhum DNS público respondeu; confira saída UDP/TCP 53 e a conexão.', file=sys.stderr)
    print('invalido')
elif len(set(validations)) > 1:
    print('Servidores DNS públicos divergem; aguarde propagação/cache e tente novamente.', file=sys.stderr)
    print('invalido')
else:
    print(validations[0])
PYDNS
)
   case "$dns_result" in
    cloudflare) cloudflare=1; printf 'DNS indica proxy da Cloudflare; verifique se a origem aponta para esta VPS e use SSL Full (strict).\n'; break;;
    direto) printf 'DNS aponta para o IP público detectado.\n'; break;;
    *) printf 'DNS não validado para %s. Confira os registros A/AAAA e os diagnósticos acima.\n' "$domain"; ask 'Pressione Enter para tentar novamente ou digite sair: '; [[ $REPLY != sair ]] || fail 'DNS ainda não validado.';;
   esac
  done
 fi
 clone_dir=$(mktemp -d /tmp/linkfree-repo.XXXXXXXX)
 trap 'rm -rf -- "$clone_dir"; rm -f -- "${cf_file:-}"' EXIT
 git clone --depth 1 --branch main https://github.com/sistemacodigolucrativo/LinkFree-LinkTree.git "$clone_dir/repo"
 repo="$clone_dir/repo"
fi
[[ -f "$repo/linkstack/artisan" && -f "$repo/linkstack/vendor/autoload.php" && -f "$repo/linkstack/database/database.sqlite" ]] || fail 'Repositório incompleto: faltam aplicação, vendor ou banco inicial.'
if [[ -z $prepare ]]; then
 COMPOSER_ALLOW_SUPERUSER=1 php8.3 "$(command -v composer)" --working-dir="$repo/linkstack" check-platform-reqs --no-dev --no-interaction
fi
for ext in bcmath ctype curl dom fileinfo json mbstring openssl pcre pdo pdo_sqlite pdo_mysql tokenizer xml iconv gd zip; do
 php8.3 -r 'exit(extension_loaded($argv[1]) ? 0 : 1);' "$ext" || fail "Extensão PHP ausente: $ext"
done
php8.3 -r 'exit(PHP_VERSION_ID >= 80300 && PHP_VERSION_ID < 80400 ? 0 : 1);' || fail 'PHP 8.3 é necessário para esta configuração.'
if [[ $resuming != 1 ]]; then
mkdir -p -- "$(dirname -- "$dest")"
mkdir -- "$dest"
cp -a -- "$repo/linkstack/." "$dest/"
# O banco de catálogo distribuído deve estar sem usuários para evitar reutilizar dados.
php8.3 -r '$d=new PDO("sqlite:".$argv[1]); if ((int)$d->query("select count(*) from users")->fetchColumn() !== 0) {fwrite(STDERR,"Banco de origem contém usuários; instalação interrompida.\n"); exit(1);}' "$dest/database/database.sqlite"
[[ -f $dest/.env ]] || fail 'Arquivo .env do projeto não encontrado.'
# Limpa segredos e opções externas do modelo versionado, preservando as opções LinkStack.
python3 - "$dest/.env" "$url" <<'PYENV'
import pathlib, re, secrets, base64, sys
p=pathlib.Path(sys.argv[1]); text=p.read_text()
values={"APP_ENV":"production", "APP_DEBUG":"false", "APP_URL":sys.argv[2],
 "APP_KEY":"base64:"+base64.b64encode(secrets.token_bytes(32)).decode(),
 "DB_CONNECTION":"sqlite", "DB_HOST":"", "DB_PORT":"", "DB_USERNAME":"", "DB_PASSWORD":"",
 "MAIL_MAILER":"log", "MAIL_HOST":"", "MAIL_PORT":"", "MAIL_USERNAME":"", "MAIL_PASSWORD":"", "MAIL_ENCRYPTION":"", "MAIL_FROM_ADDRESS":"", "MAIL_FROM_NAME":"", "MAIL_SENDMAIL":"",
 "ADMIN_EMAIL":"", "HOME_URL":"", "ENABLE_SOCIAL_LOGIN":"false", "REDIS_PASSWORD":"null", "CACHE_DRIVER":"file", "SESSION_DRIVER":"file", "QUEUE_CONNECTION":"sync"}
# Prevent copied API credentials from being retained if added to the template later.
for line in text.splitlines():
 m=re.match(r"^([A-Z][A-Z0-9_]*)=", line)
 if m and re.search(r"PASSWORD|SECRET|TOKEN|API_KEY|ACCESS_KEY|CLIENT_ID",m.group(1)):
  values.setdefault(m.group(1), "")
text=re.sub(r"^DB_DATABASE=.*(?:\n|$)", "", text, flags=re.M)
for k,v in values.items():
 pat=re.compile(r"^"+re.escape(k)+r"=.*$",re.M)
 if pat.search(text): text=pat.sub(lambda m:k+"="+v,text)
 else: text+="\n"+k+"="+v+"\n"
p.write_text(text)
PYENV
chmod 600 "$dest/.env"
cp -n "$dest/storage/templates/advanced-config.php" "$dest/config/advanced-config.php"
touch "$dest/INSTALLING"
rm -f -- "$dest/INSTALLERLOCK" "$dest/bootstrap/cache/config.php" "$dest/bootstrap/cache/routes-v7.php"
mkdir -p "$dest/storage/framework/cache/data" "$dest/storage/framework/sessions" "$dest/storage/framework/views" "$dest/storage/logs" "$dest/bootstrap/cache"
(cd "$dest" && php8.3 artisan config:clear && php8.3 artisan route:clear && php8.3 artisan view:clear)
if [[ -z $prepare ]]; then
 install -d -m 700 "$state_dir"
 python3 - "$state_dir/$domain.state" "$dest" "$mode" "$db" "$cidr" "$url" <<'PYSAVE'
import json,os,sys
p=sys.argv[1]; data=dict(zip(('dest','mode','db','cidr','url'),sys.argv[2:]));
with open(p,'x') as f: json.dump(data,f)
os.chmod(p,0o600)
PYSAVE
fi
fi
if [[ -n $prepare ]]; then
 printf 'Cópia de testes preparada em %s. Execute: cd %s && php -S 127.0.0.1:8080 server.php\n' "$dest" "$dest"
 exit 0
fi
chown -R www-data:www-data "$dest"
chmod 640 "$dest/.env"
site="/etc/nginx/sites-available/linkfree-$domain"
if [[ -n $email && $site_existing != 1 ]]; then
 challenge_dir="/var/lib/linkfree-acme/$domain"
 install -d -m 755 /var/lib/linkfree-acme "$challenge_dir"
 challenge_site="/etc/nginx/sites-available/linkfree-acme-$domain"
 [[ ! -e $challenge_site && ! -e /etc/nginx/sites-enabled/linkfree-acme-$domain ]] || fail 'Já existe configuração temporária ACME para este domínio.'
 cat > "$challenge_site" <<ACME
server {
 listen 80;
 server_name $domain;
 location ^~ /.well-known/acme-challenge/ { root $challenge_dir; }
 location / { return 404; }
}
ACME
 ln -s "$challenge_site" "/etc/nginx/sites-enabled/linkfree-acme-$domain"
 if ! nginx -t || ! systemctl enable --now nginx || ! systemctl reload nginx; then
  rm -f -- "/etc/nginx/sites-enabled/linkfree-acme-$domain" "$challenge_site"
  fail 'Não foi possível ativar configuração temporária para emitir certificado.'
 fi
 if ! (umask 022; certbot certonly --webroot --webroot-path "$challenge_dir" --non-interactive --agree-tos --email "$email" --domain "$domain" --deploy-hook "systemctl reload nginx"); then
  rm -f -- "/etc/nginx/sites-enabled/linkfree-acme-$domain" "$challenge_site"
  systemctl reload nginx || true
  fail 'Certificado não emitido; confira DNS, porta 80 e eventuais sites com o mesmo domínio.'
 fi
 rm -f -- "/etc/nginx/sites-enabled/linkfree-acme-$domain" "$challenge_site"
 cert="/etc/letsencrypt/live/$domain/fullchain.pem"
 key="/etc/letsencrypt/live/$domain/privkey.pem"
fi
if [[ $mode == domain && $site_existing != 1 ]]; then
 openssl x509 -in "$cert" -noout -checkend 0 >/dev/null || fail 'Certificado vencido.'
 openssl x509 -in "$cert" -noout -checkhost "$domain" | grep -Fq "does match certificate" || fail 'Certificado não corresponde ao domínio.'
 cert_pub=$(openssl x509 -in "$cert" -pubkey -noout | openssl pkey -pubin -outform DER | sha256sum | cut -d' ' -f1)
 key_pub=$(openssl pkey -in "$key" -pubout -outform DER | sha256sum | cut -d' ' -f1)
 [[ $cert_pub == "$key_pub" ]] || fail 'A chave privada não corresponde ao certificado.'
fi
# Cloudflare's visitor header is accepted only from its published proxy networks.
if [[ $site_existing != 1 ]]; then
real_ip_config=
if [[ $cloudflare == 1 ]]; then
 while IFS= read -r network; do
  [[ -z $network ]] && continue
  real_ip_config+="set_real_ip_from $network;"$'\n'
 done < "$cf_file"
 real_ip_config+="real_ip_header CF-Connecting-IP;"
fi
if [[ $mode == domain ]]; then
 cat > "$site" <<NGINX
server {
 listen 80;
 server_name $domain;
 location ^~ /.well-known/acme-challenge/ { root /var/lib/linkfree-acme/$domain; }
 location / { return 308 https://\$host\$request_uri; }
}
NGINX
 listen_directive='listen 443 ssl;'
 ssl_directives="ssl_certificate $cert; ssl_certificate_key $key;"
 https_param='fastcgi_param HTTPS on;'
else
 : > "$site"
 listen_directive='listen 80;'
 ssl_directives=
 https_param=
fi
cat >> "$site" <<NGINX
server {
 $listen_directive
 server_name $domain;
 $ssl_directives
 $real_ip_config
 root $dest;
 index index.php;
 client_max_body_size 20m;
 location = /skip { return 404; }
 location = /vendor/livewire/livewire/dist/livewire.js { try_files \$uri =404; }
 location ~ /\. { deny all; }
 location ~* \.(sqlite|sqlite3|db|log|zip|env|lock|json|md|yml|yaml|xml|conf|config|bak|sql)$ { deny all; }
 location ~* ^/(app|bootstrap|config|database|resources|routes|vendor|blocks|backups|storage/framework|storage/logs|storage/app)/ { deny all; }
 location ~* \.php$ { return 404; }
 location / { try_files \$uri \$uri/ /index.php?\$query_string; }
 location = /index.php {
  include /etc/nginx/fastcgi_params;
  fastcgi_param SCRIPT_FILENAME $dest/index.php;
  $https_param
  fastcgi_pass unix:/run/php/php8.3-fpm.sock;
 }
}
NGINX
ln -s "$site" "/etc/nginx/sites-enabled/linkfree-$domain"
if ! nginx -t; then
 rm -f -- "/etc/nginx/sites-enabled/linkfree-$domain" "$site"
 fail 'Configuração Nginx inválida; arquivos do site preparados, mas a configuração foi removida.'
fi
(cd /etc/nginx/sites-available && sha256sum "linkfree-$domain") > "$state_dir/$domain.site.sha256"
fi
systemctl enable --now php8.3-fpm nginx
systemctl reload nginx
if [[ $mode == domain && -n $email ]]; then
 systemctl enable --now certbot.timer || fail 'Falha ao ativar o agendamento de renovação.'
 if ! certbot renew --cert-name "$domain" --dry-run; then
  printf 'Aviso: a simulação de renovação falhou. Verifique o DNS, o proxy e a porta 80.\n' >&2
 fi
fi
db_file=
if [[ $db == mysql ]]; then
# Banco dedicado opcional para seleção de MySQL no assistente web.
# Identificadores e senha são gerados com caracteres hexadecimais seguros.
db_pointer="$state_dir/$domain.dbfile"
if [[ -f $db_pointer ]]; then
 db_file=$(cat "$db_pointer")
 [[ $db_file == /home/ubuntu/Secrets/linkfree-banco-*.txt || $db_file == /root/Secrets/linkfree-banco-*.txt ]] || fail 'Referência de credenciais inválida.'
 [[ -f $db_file && ! -L $db_file ]] || fail 'Arquivo de credenciais anterior não encontrado.'
 db_name=$(awk -F ': ' '/^Banco: / {print $2}' "$db_file")
 db_user=$(awk -F ': ' '/^Usuário: / {print $2}' "$db_file")
 db_password=$(awk -F ': ' '/^Senha: / {print $2}' "$db_file")
 [[ $db_name =~ ^linkfree_[0-9a-f]{10}$ && $db_user =~ ^lf_[0-9a-f]{10}$ && $db_password =~ ^[0-9a-f]{48}$ ]] || fail 'Credenciais anteriores inválidas.'
else
db_suffix=$(openssl rand -hex 5)
db_name="linkfree_$db_suffix"
db_user="lf_$db_suffix"
db_password=$(openssl rand -hex 24)
secret_dir=/root/Secrets
if id ubuntu >/dev/null 2>&1 && [[ -d /home/ubuntu && ! -L /home/ubuntu/Secrets ]]; then secret_dir=/home/ubuntu/Secrets; fi
if [[ -e $secret_dir && ! -d $secret_dir || -L $secret_dir ]]; then fail 'Diretório de segredos inválido.'; fi
if [[ ! -d $secret_dir ]]; then
 install -d -m 700 -- "$secret_dir"
 if [[ $secret_dir == /home/ubuntu/Secrets ]]; then chown ubuntu:ubuntu "$secret_dir"; fi
else
 python3 - "$secret_dir" <<'PYSECRET' || fail 'Diretório Secrets existente possui permissões ou proprietário inseguros.'
import os,stat,sys,pwd
s=os.stat(sys.argv[1]); allowed={0}
try: allowed.add(pwd.getpwnam('ubuntu').pw_uid)
except KeyError: pass
if s.st_uid not in allowed or stat.S_IMODE(s.st_mode)&0o077: sys.exit(1)
PYSECRET
fi
db_file="$secret_dir/linkfree-banco-$db_suffix.txt"
umask 077
( set -o noclobber; printf 'Banco: %s\nUsuário: %s\nSenha: %s\nHost: 127.0.0.1\nPorta: 3306\n' "$db_name" "$db_user" "$db_password" > "$db_file" ) || fail 'Não foi possível criar arquivo protegido de credenciais.'
printf '%s\n' "$db_file" > "$db_pointer"
fi
if ! mariadb --batch 2>/dev/null <<SQL
CREATE DATABASE IF NOT EXISTS \`$db_name\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$db_user'@'localhost' IDENTIFIED BY '$db_password';
GRANT ALL PRIVILEGES ON \`$db_name\`.* TO '$db_user'@'localhost';
SQL
then
 fail "Falha ao criar banco MariaDB; consulte $db_file e revise o estado parcial antes de continuar."
fi
if ! MYSQL_PWD="$db_password" mariadb --protocol=tcp --host=127.0.0.1 --user="$db_user" "$db_name" -N -e 'SELECT 1' >/dev/null; then
 fail 'Banco criado, mas a autenticação do usuário não passou na validação.'
fi
chmod 600 "$db_file"
if [[ $db_file == /home/ubuntu/Secrets/* ]]; then chown ubuntu:ubuntu "$db_file"; fi
fi
if [[ $mode == domain ]]; then
 http_code=$(curl --noproxy '*' --resolve "$domain:443:127.0.0.1" -sS --max-time 20 -o /dev/null -w '%{http_code}' "$url/" || true)
else
 http_code=$(curl --noproxy '*' -sS --max-time 20 -H "Host: $domain" -o /dev/null -w '%{http_code}' http://127.0.0.1/ || true)
fi
[[ $http_code == 200 ]] || fail "O instalador web não respondeu HTTP 200 na origem (resposta: $http_code)."
printf '\nLINKFREE — VPS PREPARADA\nDependências, servidor web, PHP, banco e código: OK\nInstalador web: DISPONÍVEL na origem\nAcesse %s e conclua o assistente.\n' "$url"
if [[ $db == mysql ]]; then printf 'No assistente escolha MySQL. Credenciais em %s.\n' "$db_file"; else printf 'No assistente escolha SQLite.\n'; fi
printf 'Crie o administrador, conclua todas as etapas e verifique login e gravação do perfil.\n'
