#!/usr/bin/env bash
# Isolated synthetic Chromium QA; never uses production URLs or accounts.
set -euo pipefail
export QA_EXPECTED_SITE=http://127.0.0.1:18765
export QA_APP_ROOT=/tmp/linkfree-audit/linkstack
install -d /tmp/linkfree-audit "$QA_APP_ROOT"
if ! php -m | grep -qi '^pdo_sqlite$'; then
  sudo apt-get update -qq
  sudo apt-get install -y php-sqlite3
fi
rsync -a --exclude='.env' --exclude='*.sqlite' --exclude='*.sqlite3' --exclude='*.db' --exclude=INSTALLING --exclude=INSTALLERLOCK linkstack/ "$QA_APP_ROOT/"
install -d "$QA_APP_ROOT"/database "$QA_APP_ROOT"/storage/framework/{cache,sessions,views} "$QA_APP_ROOT"/storage/logs "$QA_APP_ROOT"/assets/favicon/icons
touch "$QA_APP_ROOT/database/audit.sqlite"
KEY=$(php -r 'echo "base64:".base64_encode(random_bytes(32));')
cat > "$QA_APP_ROOT/.env" <<EOF
APP_NAME=LinkFreeQA
APP_ENV=local
APP_DEBUG=false
APP_KEY=$KEY
APP_URL=http://127.0.0.1:18765
DB_CONNECTION=sqlite
DB_DATABASE=/tmp/linkfree-audit/linkstack/database/audit.sqlite
DB_FOREIGN_KEYS=true
SESSION_DRIVER=file
CACHE_DRIVER=array
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr
MAIL_MAILER=log
MAIL_FROM_ADDRESS=qa@example.test
ALLOW_REGISTRATION=true
REGISTER_AUTH=auth
FORCE_ROUTE_HTTPS=false
EOF
cd "$QA_APP_ROOT"
php artisan migrate --force --no-interaction > /tmp/linkfree-audit/config-migrations.log
QA_APP_ROOT="$QA_APP_ROOT" php "$GITHUB_WORKSPACE/scripts/qa-isolated/seed.php"
cd "$GITHUB_WORKSPACE"
install -d /tmp/linkfree-qa-node
cd /tmp/linkfree-qa-node
npm init -y > /dev/null
timeout 180s npm install --no-audit --no-fund playwright
timeout 180s ./node_modules/.bin/playwright install --with-deps chromium > /tmp/linkfree-audit/config-browser-install.log 2>&1
cd "$QA_APP_ROOT"
php -S 127.0.0.1:18765 server.php > /tmp/linkfree-audit/config-server.log 2>&1 &
SERVER_PID=$!
trap 'kill "$SERVER_PID" 2>/dev/null || true' EXIT
for i in $(seq 1 45); do
  if curl --fail --silent --output /dev/null --max-time 2 http://127.0.0.1:18765/login; then break; fi
  if [ "$i" -eq 45 ]; then echo 'Local server did not start'; tail -n 40 /tmp/linkfree-audit/config-server.log; exit 1; fi
  sleep 1
done
cd "$GITHUB_WORKSPACE"
timeout 360s node scripts/qa-isolated/config_browser_smoke.cjs
curl --fail --silent --show-error --max-time 10 http://127.0.0.1:18765/login > /dev/null
