#!/usr/bin/env bash
# Homologação na VPS sem cadastro de usuários ou alteração do banco.
set -Eeuo pipefail
umask 077
[[ "$#" -eq 0 || "$1" == --read-only ]] || { echo 'Uso: homologar-linkfree.sh --read-only' >&2; exit 2; }
(( EUID == 0 )) || { echo 'Execute como root.' >&2; exit 1; }
cd /
APP=/srv/linkfree
DB="$APP/database/database.sqlite"
SITE=https://linkfree.website
STATE=/var/lib/linkfree-deploy/last-successful-sha
BACKUPS=/var/backups/linkfree-deploy
errors=0
warnings=0
pass() { printf 'OK: %s\n' "$1"; }
fail() { printf 'FALHOU: %s\n' "$1" >&2; errors=$((errors+1)); }
warn() { printf 'PENDENTE MANUAL: %s\n' "$1"; warnings=$((warnings+1)); }
for cmd in curl sqlite3 php runuser grep find; do
  command -v "$cmd" >/dev/null 2>&1 || { fail "Dependência ausente: $cmd"; exit 1; }
done
[[ -f "$APP/artisan" && -f "$APP/.env" ]] || { fail 'Instalação Laravel indisponível'; exit 1; }
deployed=''
if [[ -r "$STATE" && -s "$STATE" ]]; then
  deployed="$(head -n1 "$STATE")"
  if [[ "$deployed" =~ ^[0-9a-f]{40}$ ]]; then
    pass "SHA registrado pelo deploy: $(printf '%s' "$deployed" | cut -c1-12)"
  else
    fail 'SHA registrado inválido'
  fi
else
  fail 'Deploy não possui SHA de publicação registrado'
fi
if [[ -f "$DB" && ! -L "$DB" ]]; then
  if [[ "$(sqlite3 -readonly "$DB" 'PRAGMA quick_check;' 2>/dev/null)" == ok ]]; then
    pass 'SQLite de produção íntegro'
  else
    fail 'SQLite não passou no quick_check'
  fi
  if [[ "$(sqlite3 -readonly "$DB" "SELECT count(*) FROM sqlite_master WHERE type='table' AND name='registration_consents';" 2>/dev/null)" == 1 ]]; then
    pass 'Tabela registration_consents presente'
  else
    fail 'Tabela registration_consents ausente'
  fi
  for col in user_id method terms_version privacy_version accepted_at; do
    if [[ "$(sqlite3 -readonly "$DB" "SELECT count(*) FROM pragma_table_info('registration_consents') WHERE name='$col';" 2>/dev/null)" == 1 ]]; then
      pass "Coluna do comprovante: $col"
    else
      fail "Coluna do comprovante ausente: $col"
    fi
  done
  if [[ "$(sqlite3 -readonly "$DB" "SELECT count(*) FROM migrations WHERE migration='2026_11_23_000001_create_registration_consents_table';" 2>/dev/null)" == 1 ]]; then
    pass 'Migração de consentimento registrada como executada'
  else
    fail 'Migração não consta como executada'
  fi
  if [[ "$(sqlite3 -readonly "$DB" "SELECT CASE WHEN EXISTS (SELECT 1 FROM pages WHERE length(trim(coalesce(terms, ''))) > 0 AND length(trim(coalesce(privacy, ''))) > 0) THEN 1 ELSE 0 END;" 2>/dev/null)" == 1 ]]; then
    pass 'Termos e Política de Privacidade preenchidos'
  else
    fail 'Termos/Privacidade ausentes ou vazios'
  fi
  if runuser -u www-data -- test -r "$DB"; then
    pass 'PHP-FPM pode ler o SQLite'
  else
    fail 'PHP-FPM não pode ler o SQLite'
  fi
else
  fail 'Banco SQLite de produção não encontrado'
fi
if [[ -d "$APP/storage/app/linkfree" && ! -L "$APP/storage/app/linkfree" ]]; then
  if runuser -u www-data -- test -w "$APP/storage/app/linkfree"; then
    pass 'PHP-FPM pode acessar a área persistente de personalização'
  else
    fail 'PHP-FPM não pode gravar a área persistente'
  fi
  if [[ -d "$APP/storage/app/linkfree/visit-sources" ]] && runuser -u www-data -- test -w "$APP/storage/app/linkfree/visit-sources"; then
    pass 'PHP-FPM pode acessar a área de estatísticas'
  else
    fail 'Área persistente de estatísticas indisponível'
  fi
  if [[ -n "$(find "$APP/storage/app/linkfree" -type l -print -quit)" ]]; then
    fail 'Arquivos symlink encontrados no storage privado'
  else
    pass 'Storage privado sem symlinks'
  fi
  unreadable="$(runuser -u www-data -- find "$APP/storage/app/linkfree" -type f ! -readable -print -quit)"
  if [[ -n "$unreadable" ]]; then
    fail 'Arquivo persistente ilegível por www-data'
  else
    pass 'Arquivos persistentes legíveis por www-data'
  fi
else
  fail 'Storage persistente ausente'
fi
# Checagem não destrutiva de snapshot associado à versão publicada.
backups_ok=0
if [[ -d "$BACKUPS" && -n "$deployed" ]]; then
  while IFS= read -r marker; do
    dir="$(dirname "$marker")"
    [[ "$(head -n1 "$marker")" == "$deployed" ]] || continue
    [[ -f "$dir/sqlite-before-deploy.sqlite" ]] || continue
    if [[ "$(sqlite3 -readonly "$dir/sqlite-before-deploy.sqlite" 'PRAGMA quick_check;' 2>/dev/null)" == ok ]]; then
      backups_ok=1
      break
    fi
  done < <(find "$BACKUPS" -mindepth 2 -maxdepth 2 -name target-sha.txt -type f 2>/dev/null)
fi
if (( backups_ok )); then
  pass 'Backup SQLite do commit publicado encontrado e íntegro'
else
  warn 'Não foi possível verificar backup SQLite associado ao SHA da publicação'
fi

http_get() {
  local path="$1" status
  if status="$(curl --silent --show-error --max-time 25 --connect-timeout 8 \
        --proto '=https' -o /dev/null -w '%{http_code}' "$SITE$path")"; then
    printf '%s' "$status"
  else
    printf '000'
  fi
}
home="$(http_get '/')"
if [[ "$home" == 200 ]]; then pass 'Página inicial HTTPS respondeu 200'; else fail "Home respondeu HTTP $home"; fi
login="$(http_get '/login')"
if [[ "$login" == 200 ]]; then pass 'Login público HTTPS respondeu 200'; else fail "Login respondeu HTTP $login"; fi
register="$(http_get '/register')"
case "$register" in
  200)
    pass 'Página de registro acessível por HTTPS'
    form="$(mktemp /tmp/lf-register-smoke-XXXXXX)"
    if curl --silent --show-error --max-time 25 --connect-timeout 8 --proto '=https' \
        --output "$form" "$SITE/register"; then
      if grep -q 'name="accept_terms"' "$form" && grep -q 'name="_token"' "$form"; then
        pass 'Cadastro público exibe aceite explícito e proteção CSRF'
      else
        fail 'Cadastro público não exibiu checkbox de aceite e token CSRF'
      fi
    else
      fail 'Não foi possível inspecionar o formulário de cadastro'
    fi
    rm -f -- "$form"
    ;;
  301|302|303|307|308) warn "Registro redireciona (HTTP $register); checar rota customizada e formulário" ;;
  404) warn 'Registro público retorna 404; checar se cadastro está desativado' ;;
  *) fail "Registro respondeu HTTP $register" ;;
esac
not_found="$(http_get '/__lf_homologacao_404_probe_nao_existente')"
if [[ "$not_found" == 404 ]]; then
  pass 'Rota desconhecida produz HTTP 404'
else
  fail "Rota desconhecida deveria retornar 404, mas respondeu $not_found"
fi
protected="$(http_get '/studio/index')"
case "$protected" in
  301|302|303|307|308|401|403) pass 'Studio não expôs conteúdo autenticado ao visitante anônimo' ;;
  *) fail "Studio não protegido ou indisponível (HTTP $protected)" ;;
esac
printf '\nRESULTADO: %d falha(s), %d verificação(ões) pendentes de revisão manual.\n' "$errors" "$warnings"
echo 'ESCOPO: HTTP público, esquema SQLite, backup e permissões sem alterar dados.'
echo 'NÃO COBERTO: cadastro real, OAuth real e telas autenticadas da VPS.'
if (( errors )); then
  echo 'HOMOLOGAÇÃO TÉCNICA NÃO APROVADA.' >&2
  exit 1
fi
if (( warnings )); then
  echo 'HOMOLOGAÇÃO TÉCNICA PARCIAL: revisar avisos.'
else
  echo 'HOMOLOGAÇÃO TÉCNICA READ-ONLY APROVADA.'
fi
