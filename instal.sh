#!/usr/bin/env bash
# Prepara uma VPS Ubuntu 24.04 para o assistente web do LinkFree.
set -Eeuo pipefail
umask 027
usage() {
  cat <<'HELP'
Uso: sudo bash instal.sh --domain dominio --admin-cidr IP/CIDR (--email email | --cert certificado.pem --key chave.pem) [--dest /srv/linkfree]
     bash instal.sh --prepare-only /caminho/novo --url http://localhost:8080

Instala Nginx, PHP 8.3 e extensões, MariaDB, SQLite, Composer, Git, Node.js e npm.
Prepara uma cópia nova e abre o assistente web com SQLite como opção inicial.
Um banco e usuário MariaDB dedicados são criados para a opção MySQL do assistente.
Com --email, solicita certificado Let’s Encrypt; o domínio deve apontar para esta VPS
e as portas 80 e 443 devem estar acessíveis. Também aceita certificado já existente.
O IP/CIDR autoriza o acesso ao assistente até que seja concluído. A rota /skip é bloqueada.
--prepare-only prepara uma cópia para testes sem alterar serviços ou instalar pacotes.
O repositório público é clonado automaticamente. Consulte dependências.txt.
No modo --prepare-only, execute a partir de uma cópia completa do repositório.
Não use em uma instalação existente; o destino precisa ser novo.
HELP
}
fail() { printf 'Erro: %s\n' "$*" >&2; exit 1; }
repo=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)
domain= cidr= cert= key= email= dest=/srv/linkfree prepare= url=
while (($#)); do
 case "$1" in
  --help|-h) usage; exit 0;;
  --domain|--admin-cidr|--cert|--key|--email|--dest|--prepare-only|--url)
   (($# >= 2)) || fail "Falta valor para $1"
   case "$1" in --domain) domain=$2;; --admin-cidr) cidr=$2;; --cert) cert=$2;; --key) key=$2;; --email) email=$2;; --dest) dest=$2;; --prepare-only) prepare=$2;; --url) url=$2;; esac; shift 2;;
  *) fail "Opção desconhecida: $1";;
 esac
done
if [[ -n $prepare ]]; then
 dest=$prepare
 [[ $url =~ ^https?://[a-zA-Z0-9.:-]+$ ]] || fail 'Informe --url, por exemplo http://localhost:8080.'
else
 [[ $EUID == 0 ]] || fail 'Execute a instalação completa como root.'
 [[ -r /etc/os-release ]] || fail 'Sistema não identificado.'
 . /etc/os-release
 [[ ${ID:-} == ubuntu && ${VERSION_ID:-} == 24.04 ]] || fail 'A instalação automática foi preparada para Ubuntu 24.04.'
 [[ $domain =~ ^[a-zA-Z0-9]([a-zA-Z0-9.-]*[a-zA-Z0-9])?$ && $domain == *.* && $domain != *..* ]] || fail 'Informe um domínio DNS válido em --domain.'
 if [[ -n $email ]]; then
  [[ -z $cert && -z $key && $email =~ ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+$ ]] || fail 'Informe um email válido ou um certificado e chave, não ambos.'
 else
  [[ -f $cert && -r $cert && -f $key && -r $key && $cert =~ ^/[a-zA-Z0-9_./-]+$ && $key =~ ^/[a-zA-Z0-9_./-]+$ ]] || fail 'Informe --email para emitir certificado ou caminhos absolutos para --cert e --key.'
 fi
 command -v python3 >/dev/null || fail 'python3 é necessário para validar o IP.'
 python3 - "$cidr" <<'PY' || fail 'Informe --admin-cidr com IP ou rede válida.'
import ipaddress,sys
network = ipaddress.ip_network(sys.argv[1], strict=False)
if network.prefixlen == 0: raise ValueError("A rede de instalação não pode permitir todos os endereços")
PY
 url="https://$domain"
 [[ ! -e /etc/nginx/sites-available/linkfree-$domain && ! -e /etc/nginx/sites-enabled/linkfree-$domain ]] || fail 'Já existe configuração Nginx com esse nome.'
fi
[[ $dest == /* && $dest != / && ! -e $dest && ! -L $dest ]] || fail 'O destino deve ser absoluto e ainda não existir.'
[[ $dest =~ ^/[a-zA-Z0-9_./-]+$ && $dest != *..* ]] || fail 'Use um caminho de destino simples, sem espaços ou ..'
if [[ -z $prepare ]]; then
 export DEBIAN_FRONTEND=noninteractive
 apt-get update
 apt-get install -y nginx php8.3-fpm php8.3-cli php8.3-bcmath php8.3-curl php8.3-mbstring php8.3-xml php8.3-sqlite3 php8.3-mysql php8.3-gd php8.3-zip mariadb-server sqlite3 git unzip curl ca-certificates openssl python3 composer nodejs npm certbot
 systemctl enable --now mariadb
 clone_dir=$(mktemp -d /tmp/linkfree-repo.XXXXXXXX)
 trap 'rm -rf -- "$clone_dir"' EXIT
 git clone --depth 1 --branch main https://github.com/sistemacodigolucrativo/LinkFree-LinkTree.git "$clone_dir/repo"
 repo="$clone_dir/repo"
fi
[[ -f "$repo/linkstack/artisan" && -f "$repo/linkstack/vendor/autoload.php" && -f "$repo/linkstack/database/database.sqlite" ]] || fail 'Repositório incompleto: faltam aplicação, vendor ou banco inicial.'
if [[ -z $prepare ]]; then
 COMPOSER_ALLOW_SUPERUSER=1 php8.3 /usr/bin/composer --working-dir="$repo/linkstack" check-platform-reqs --no-dev --no-interaction
fi
for ext in bcmath ctype curl dom fileinfo json mbstring openssl pcre pdo pdo_sqlite pdo_mysql tokenizer xml iconv gd zip; do
 php8.3 -r 'exit(extension_loaded($argv[1]) ? 0 : 1);' "$ext" || fail "Extensão PHP ausente: $ext"
done
php8.3 -r 'exit(PHP_VERSION_ID >= 80300 && PHP_VERSION_ID < 80400 ? 0 : 1);' || fail 'PHP 8.3 é necessário para esta configuração.'
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
if [[ -n $prepare ]]; then
 printf 'Cópia de testes preparada em %s. Execute: cd %s && php -S 127.0.0.1:8080 server.php\n' "$dest" "$dest"
 exit 0
fi
chown -R www-data:www-data "$dest"
chmod 640 "$dest/.env"
site="/etc/nginx/sites-available/linkfree-$domain"
if [[ -n $email ]]; then
 challenge_dir=$(mktemp -d /tmp/linkfree-acme.XXXXXXXX)
 chmod 755 "$challenge_dir"
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
 if ! certbot certonly --webroot --webroot-path "$challenge_dir" --non-interactive --agree-tos --email "$email" --domain "$domain"; then
  rm -f -- "/etc/nginx/sites-enabled/linkfree-acme-$domain" "$challenge_site"
  systemctl reload nginx || true
  fail 'Certificado não emitido; confira DNS, porta 80 e eventuais sites com o mesmo domínio.'
 fi
 rm -f -- "/etc/nginx/sites-enabled/linkfree-acme-$domain" "$challenge_site"
 cert="/etc/letsencrypt/live/$domain/fullchain.pem"
 key="/etc/letsencrypt/live/$domain/privkey.pem"
fi
cat > "$site" <<NGINX
geo \$linkfree_setup_allowed_$$ {
 default 0;
 $cidr 1;
}
server {
 listen 80;
 server_name $domain;
 return 308 https://\$host\$request_uri;
}
server {
 listen 443 ssl;
 server_name $domain;
 ssl_certificate $cert;
 ssl_certificate_key $key;
 root $dest;
 index index.php;
 client_max_body_size 20m;
 set \$setup_guard 0;
 if (-f $dest/INSTALLING) { set \$setup_guard 1; }
 if (-f $dest/INSTALLERLOCK) { set \$setup_guard 1; }
 if (\$linkfree_setup_allowed_$$ = 1) { set \$setup_guard 0; }
 if (\$setup_guard = 1) { return 403; }
 location = /skip { return 404; }
 location = /vendor/livewire/livewire/dist/livewire.js { try_files $uri =404; }
 location ~ /\. { deny all; }
 location ~* \.(sqlite|sqlite3|db|log|zip|env|lock|json|md|yml|yaml|xml|conf|config|bak|sql)$ { deny all; }
 location ~* ^/(app|bootstrap|config|database|resources|routes|vendor|blocks|backups|storage/framework|storage/logs|storage/app)/ { deny all; }
 location ~* \.php$ { return 404; }
 location / { try_files \$uri \$uri/ /index.php?\$query_string; }
 location = /index.php {
  include /etc/nginx/fastcgi_params;
  fastcgi_param SCRIPT_FILENAME $dest/index.php;
  fastcgi_param HTTPS on;
  fastcgi_pass unix:/run/php/php8.3-fpm.sock;
 }
}
NGINX
ln -s "$site" "/etc/nginx/sites-enabled/linkfree-$domain"
if ! nginx -t; then
 rm -f -- "/etc/nginx/sites-enabled/linkfree-$domain" "$site"
 fail 'Configuração Nginx inválida; arquivos do site preparados, mas a configuração foi removida.'
fi
systemctl enable --now php8.3-fpm nginx
systemctl reload nginx
# Banco dedicado opcional para seleção de MySQL no assistente web.
# Identificadores e senha são gerados com caracteres hexadecimais seguros.
db_suffix=$(openssl rand -hex 5)
db_name="linkfree_$db_suffix"
db_user="lf_$db_suffix"
db_password=$(openssl rand -hex 24)
db_file="/root/linkfree-banco-$db_suffix.txt"
umask 077
( set -o noclobber; printf 'Banco: %s\nUsuário: %s\nSenha: %s\nHost: 127.0.0.1\nPorta: 3306\n' "$db_name" "$db_user" "$db_password" > "$db_file" ) || fail 'Não foi possível criar arquivo protegido de credenciais.'
if ! mariadb --batch <<SQL
CREATE DATABASE \`$db_name\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER '$db_user'@'localhost' IDENTIFIED BY '$db_password';
GRANT ALL PRIVILEGES ON \`$db_name\`.* TO '$db_user'@'localhost';
SQL
then
 fail "Falha ao criar banco MariaDB; consulte $db_file e revise o estado parcial antes de continuar."
fi
printf '\nInstalação preparada em %s. Acesse %s a partir de %s e conclua o assistente.\n' "$dest" "$url" "$cidr"
printf 'Escolha SQLite ou use o banco MariaDB criado. Credenciais em %s (somente root).\n' "$db_file"
printf 'Crie o administrador, conclua todas as etapas e verifique login e gravação do perfil.\n'
