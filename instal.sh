#!/usr/bin/env bash
# Instalação nova do LinkFree em Ubuntu 24.04. Não atualiza instalações existentes.
set -Eeuo pipefail
umask 027
usage() {
  cat <<'HELP'
Uso: sudo bash instal.sh --domain dominio --admin-cidr IP/CIDR --cert certificado.pem --key chave.pem [--dest /srv/linkfree]
     bash instal.sh --prepare-only /caminho/novo --url http://localhost:8080

A instalação completa usa SQLite, Nginx, PHP 8.3 e certificado TLS já emitido.
O IP/CIDR autoriza o acesso ao assistente até que seja concluído. A rota /skip é bloqueada.
--prepare-only prepara uma cópia para testes sem alterar serviços ou instalar pacotes.
Execute a partir de uma cópia completa deste repositório. Consulte dependências.txt.
HELP
}
fail() { printf 'Erro: %s\n' "$*" >&2; exit 1; }
repo=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)
domain= cidr= cert= key= dest=/srv/linkfree prepare= url=
while (($#)); do
 case "$1" in
  --help|-h) usage; exit 0;;
  --domain|--admin-cidr|--cert|--key|--dest|--prepare-only|--url)
   (($# >= 2)) || fail "Falta valor para $1"
   case "$1" in --domain) domain=$2;; --admin-cidr) cidr=$2;; --cert) cert=$2;; --key) key=$2;; --dest) dest=$2;; --prepare-only) prepare=$2;; --url) url=$2;; esac; shift 2;;
  *) fail "Opção desconhecida: $1";;
 esac
done
[[ -f "$repo/linkstack/artisan" && -f "$repo/linkstack/vendor/autoload.php" && -f "$repo/linkstack/database/database.sqlite" ]] || fail 'Repositório incompleto: obtenha o projeto completo, incluindo vendor e database.'
if [[ -n $prepare ]]; then
 dest=$prepare
 [[ $url =~ ^https?://[a-zA-Z0-9.:-]+$ ]] || fail 'Informe --url, por exemplo http://localhost:8080.'
else
 [[ $EUID == 0 ]] || fail 'Execute a instalação completa como root.'
 [[ -r /etc/os-release ]] || fail 'Sistema não identificado.'
 . /etc/os-release
 [[ ${ID:-} == ubuntu && ${VERSION_ID:-} == 24.04 ]] || fail 'A instalação automática foi preparada para Ubuntu 24.04.'
 [[ $domain =~ ^[a-zA-Z0-9]([a-zA-Z0-9.-]*[a-zA-Z0-9])?$ && $domain == *.* && $domain != *..* ]] || fail 'Informe um domínio DNS válido em --domain.'
 [[ -f $cert && -r $cert && -f $key && -r $key && $cert =~ ^/[a-zA-Z0-9_./-]+$ && $key =~ ^/[a-zA-Z0-9_./-]+$ ]] || fail 'Informe caminhos absolutos simples para certificado e chave existentes.'
 command -v python3 >/dev/null || fail 'python3 é necessário para validar o IP.'
 python3 - "$cidr" <<'PY' || fail 'Informe --admin-cidr com IP ou rede válida.'
import ipaddress,sys
ipaddress.ip_network(sys.argv[1], strict=False)
PY
 url="https://$domain"
 [[ ! -e /etc/nginx/sites-available/linkfree-$domain && ! -e /etc/nginx/sites-enabled/linkfree-$domain ]] || fail 'Já existe configuração Nginx com esse nome.'
fi
[[ $dest == /* && $dest != / && ! -e $dest && ! -L $dest ]] || fail 'O destino deve ser absoluto e ainda não existir.'
[[ $dest =~ ^/[a-zA-Z0-9_./-]+$ && $dest != *..* ]] || fail 'Use um caminho de destino simples, sem espaços ou ..'
if [[ -z $prepare ]]; then
 export DEBIAN_FRONTEND=noninteractive
 apt-get update
 apt-get install -y nginx php8.3-fpm php8.3-cli php8.3-bcmath php8.3-curl php8.3-mbstring php8.3-xml php8.3-sqlite3 php8.3-mysql php8.3-gd php8.3-zip openssl
fi
for ext in bcmath ctype curl dom fileinfo json mbstring openssl pcre pdo pdo_sqlite tokenizer xml iconv gd zip; do
 php -r 'exit(extension_loaded($argv[1]) ? 0 : 1);' "$ext" || fail "Extensão PHP ausente: $ext"
done
php -r 'exit(PHP_VERSION_ID >= 80000 ? 0 : 1);' || fail 'PHP 8.0 ou superior é necessário.'
mkdir -p -- "$(dirname -- "$dest")"
mkdir -- "$dest"
cp -a -- "$repo/linkstack/." "$dest/"
# O banco de catálogo distribuído deve estar sem usuários para evitar reutilizar dados.
php -r '$d=new PDO("sqlite:".$argv[1]); if ((int)$d->query("select count(*) from users")->fetchColumn() !== 0) {fwrite(STDERR,"Banco de origem contém usuários; instalação interrompida.\n"); exit(1);}' "$dest/database/database.sqlite"
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
(cd "$dest" && php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan migrate --force)
if [[ -n $prepare ]]; then
 printf 'Cópia de testes preparada em %s. Execute: cd %s && php -S 127.0.0.1:8080 server.php\n' "$dest" "$dest"
 exit 0
fi
chown -R www-data:www-data "$dest"
chmod 640 "$dest/.env"
site="/etc/nginx/sites-available/linkfree-$domain"
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
printf '\nInstalação preparada em %s. Acesse %s a partir de %s e conclua o assistente.\n' "$dest" "$url" "$cidr"
printf 'Escolha SQLite, crie o administrador e conclua todas as etapas; verifique login e gravação do perfil.\n'
