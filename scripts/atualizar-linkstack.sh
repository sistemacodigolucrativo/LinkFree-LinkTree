#!/usr/bin/env bash
# LinkFree-LinkTree: atualizacao seletiva GitHub -> desenvolvimento -> producao.
# Uso: sudo bash atualizar-linkstack.sh --check | --deploy
# Primeiro acesso: prepara clone persistente e estágio de código (sem dados privados).
set -Eeuo pipefail
umask 077

REPO=/home/ubuntu/src-Projects/LinkFree-LinkTree
DEV=/home/ubuntu/linkstack-dev
PROD=/srv/linkfree
BRANCH=main
STATE_DIR=/var/lib/linkfree-deploy
LAST_FILE="$STATE_DIR/last-successful-sha"
BACKUP_ROOT=/var/backups/linkfree-deploy
SITE=https://linkfree.website

MODE="${1:-}"
if [[ "$MODE" != --check && "$MODE" != --deploy ]]; then
  echo 'Uso: bash atualizar-linkstack.sh --check | --deploy' >&2
  exit 2
fi
if (( EUID != 0 )); then echo 'Execute como root (sudo).' >&2; exit 1; fi
for cmd in git rsync php flock runuser cmp mktemp cp python3; do
  command -v "$cmd" >/dev/null || { echo "Dependencia ausente: $cmd" >&2; exit 1; }
done
[[ -d "$PROD" && -f "$PROD/artisan" && -f "$PROD/.env" ]] || {
  echo "Instalacao LinkFree nao encontrada em $PROD. Nenhum arquivo modificado." >&2
  exit 1
}
[[ ! -L "$PROD" && ! -L "$DEV" && ! -L "$REPO" ]] || { echo 'Diretorio simbólico recusado.' >&2; exit 1; }
id ubuntu >/dev/null 2>&1 || { echo 'Usuario ubuntu nao existe.' >&2; exit 1; }
exec 9>/tmp/linkfree-deploy.lock
flock -n 9 || { echo 'Ja existe uma atualizacao em andamento.' >&2; exit 1; }

# O instal.sh obtem um clone temporario. O deploy mantem sua propria copia persistente.
if [[ ! -d "$REPO/.git" ]]; then
  [[ ! -e "$REPO" ]] || { echo "Destino do clone ocupado: $REPO" >&2; exit 1; }
  install -d -o ubuntu -g ubuntu -m 755 /home/ubuntu/src-Projects
  echo "Criando clone persistente em $REPO..."
  runuser -u ubuntu -- git clone --branch "$BRANCH" \
    https://github.com/sistemacodigolucrativo/LinkFree-LinkTree.git "$REPO"
fi
[[ -d "$REPO/linkstack" ]] || { echo 'Aplicacao ausente no clone.' >&2; exit 1; }
# Evita o bloqueio de ownership do Git ao usar sudo/root em repositorio do ubuntu.
gitrepo() { runuser -u ubuntu -- git -C "$REPO" "$@"; }

current_branch="$(gitrepo symbolic-ref --short HEAD)"
[[ "$current_branch" == "$BRANCH" ]] || { echo "Branch inesperada: $current_branch" >&2; exit 1; }
[[ -z "$(gitrepo status --porcelain)" ]] || {
  echo 'Reposítorio local possui alteracoes pendentes. Pare para evitar perda de codigo.' >&2
  gitrepo status --short >&2
  exit 1
}
remote_url="$(gitrepo config --get remote.origin.url || :)"
case "$remote_url" in
  https://github.com/sistemacodigolucrativo/LinkFree-LinkTree.git|https://github.com/sistemacodigolucrativo/LinkFree-LinkTree|git@github.com:sistemacodigolucrativo/LinkFree-LinkTree.git) ;;
  *) echo 'O origin nao corresponde ao repositorio esperado; operacao bloqueada.' >&2; exit 1 ;;
esac

echo 'Buscando atualizacoes no GitHub...'
gitrepo fetch --quiet origin "$BRANCH"
TARGET="$(gitrepo rev-parse "origin/$BRANCH^{commit}")"
LOCAL_HEAD="$(gitrepo rev-parse 'HEAD^{commit}')"
BASE="$LOCAL_HEAD"
if [[ -s "$LAST_FILE" ]]; then
  BASE="$(head -n1 "$LAST_FILE")"
  gitrepo cat-file -e "$BASE^{commit}" || { echo 'Historico de deploy invalido.' >&2; exit 1; }
else
  # O instalador web copia os fontes sem gravar SHA da instalacao. Procura uma
  # revisao cujos arquivos de codigo coincidam EXATAMENTE com a producao.
  # Arquivos de usuario, .env, SQLite, uploads e dependencias ficam fora.
  # Se nao houver coincidencia, para sem presumir uma versao ou sobrescrever dados.
  echo 'Identificando a revisao instalada pela comparacao dos arquivos de codigo...'
  BASE=$(python3 - "$REPO" "$PROD" <<'PYBASE'
import hashlib
import os
import subprocess
import sys

repo, prod = sys.argv[1:3]
gitcmd = ['git', '-c', f'safe.directory={repo}', '-C', repo]
def git(*args):
    return subprocess.check_output(gitcmd + list(args))

def managed(rel):
    if rel == '.env' or rel.startswith(('.env.', 'storage/', 'database/', 'themes/',
                                        'vendor/', 'node_modules/', '.git/',
                                        'assets/favicon/icons/')):
        return False
    if rel in ('config/advanced-config.php', 'INSTALLERLOCK',
               'composer.json', 'composer.lock', 'package.json', 'package-lock.json',
               'yarn.lock', 'webpack.mix.js'):
        return False
    if rel.endswith(('.sqlite', '.db')) or rel.startswith(('vite.config.',)):
        return False
    return rel.startswith(('app/', 'config/', 'routes/', 'resources/', 'assets/',
                           'blocks/', 'bootstrap/', 'docs/')) or rel in (
                               'index.php', '.htaccess', 'server.php', 'web.config',
                               'mix-manifest.json', 'version.json')

cache = {}
def live_blob(rel):
    if rel in cache:
        return cache[rel]
    path = os.path.join(prod, rel)
    if not os.path.isfile(path) or os.path.islink(path):
        cache[rel] = None
        return None
    h = hashlib.sha1()
    h.update(b'blob ' + str(os.path.getsize(path)).encode() + b'\0')
    with open(path, 'rb') as f:
        for chunk in iter(lambda: f.read(1024 * 1024), b''):
            h.update(chunk)
    cache[rel] = h.hexdigest().encode()
    return cache[rel]

for candidate in git('rev-list', '-n', '500', 'origin/main').splitlines():
    raw = git('ls-tree', '-r', '-z', candidate.decode(), '--', 'linkstack/')
    compared = 0
    for record in raw.split(b'\0'):
        if not record:
            continue
        meta, path = record.split(b'\t', 1)
        if meta.split()[1] != b'blob':
            continue
        rel = os.fsdecode(path)[len('linkstack/'):]
        if not managed(rel):
            continue
        compared += 1
        if live_blob(rel) != meta.split()[2]:
            break
    else:
        if compared >= 5:
            print(candidate.decode())
            sys.exit(0)
print('Nao foi possivel identificar com seguranca o commit instalado. '
      'Nada foi atualizado; solicite revisao antes do primeiro deploy.', file=sys.stderr)
sys.exit(1)
PYBASE
  ) || exit 1
  printf 'Versao original identificada: %.12s\n' "$BASE"
fi

gitrepo merge-base --is-ancestor "$LOCAL_HEAD" "$TARGET" || {
  echo 'Main local divergiu do remoto. Resolva manualmente; nao sera usado reset --hard.' >&2
  exit 1
}
gitrepo merge-base --is-ancestor "$BASE" "$TARGET" || {
  echo 'O commit de referencia do ultimo deploy nao e ancestral de origin/main.' >&2
  exit 1
}

mapfile -t changed < <(gitrepo diff --name-only --diff-filter=ACMRT "$BASE" "$TARGET" -- linkstack/)
mapfile -t deleted < <(gitrepo diff --name-only --diff-filter=D "$BASE" "$TARGET" -- linkstack/)
if (( ${#deleted[@]} )); then
  echo 'AVISO: ha exclusoes no projeto. Este script nao remove arquivos da producao:' >&2
  printf '  %s\n' "${deleted[@]}" >&2
  exit 1
fi

files=()
protected=()
manual=()
for path in "${changed[@]}"; do
  rel="${path#linkstack/}"
  case "$rel" in
    database/migrations/*) manual+=("$rel"); continue ;; # Alteracao de esquema requer migracao e backup coordenados.
    .env|.env.*|config/advanced-config.php|storage/*|database/*|themes/*|vendor/*|node_modules/*|.git/*|assets/favicon/icons/*|INSTALLERLOCK|*.sqlite|*.sqlite-*|*.db|*.db-*) protected+=("$rel"); continue ;;
    composer.json|composer.lock|package.json|package-lock.json|yarn.lock|vite.config.*|webpack.mix.js) manual+=("$rel"); continue ;;
    app/*|config/*|routes/*|resources/*|assets/*|blocks/*|bootstrap/*|docs/*|index.php|.htaccess|server.php|web.config|mix-manifest.json|version.json)
      files+=("$rel") ;;
    *) manual+=("$rel") ;;
  esac
done

printf '\nVersao instalada (referencia): %.12s\n' "$BASE"
printf 'Versao disponivel:             %.12s\n' "$TARGET"
printf 'Arquivos de codigo para publicar: %s\n' "${#files[@]}"
((${#files[@]} == 0)) || printf '  %s\n' "${files[@]}"
((${#protected[@]} == 0)) || { echo 'Arquivos protegidos nao serao copiados:'; printf '  %s\n' "${protected[@]}"; }
if ((${#manual[@]})); then
  echo 'Mudancas que exigem revisao manual (dependencias/estrutura):' >&2
  printf '  %s\n' "${manual[@]}" >&2
  exit 1
fi

# Evita sobrescrever modificacoes feitas diretamente em dev/producao.
# Todo arquivo alterado deve corresponder ao commit anterior, ou estar ausente se for novo.
for rel in "${files[@]}"; do
  old_path="linkstack/$rel"
  old_exists=false
  if gitrepo cat-file -e "$BASE:$old_path" 2>/dev/null; then old_exists=true; fi
  for dest in "$DEV" "$PROD"; do
    # O diretorio DEV pode nao existir numa instalacao criada pelo instal.sh;
    # sera inicializado somente em --deploy com fontes de codigo, sem dados.
    [[ "$dest" != "$DEV" || -d "$DEV" ]] || continue
    if [[ -L "$dest/$rel" ]]; then
      echo "CONFLITO: destino e link simbolico: $dest/$rel" >&2
      exit 1
    fi
    if [[ "$old_exists" == true ]]; then
      if [[ ! -f "$dest/$rel" ]] || ! gitrepo show "$BASE:$old_path" | cmp -s - "$dest/$rel"; then
        echo "CONFLITO: $dest/$rel difere da versao base ($BASE). Nao sobrescrevi." >&2
        exit 1
      fi
    elif [[ -e "$dest/$rel" ]]; then
      echo "CONFLITO: arquivo novo ja existe no destino: $dest/$rel" >&2
      exit 1
    fi
  done
done

if [[ "$MODE" == --check ]]; then
  [[ -d "$DEV" ]] || echo "O estagio de codigo $DEV sera criado no primeiro --deploy (sem .env ou banco)."
  echo 'SIMULACAO CONCLUIDA: os destinos existentes passaram na verificacao de conflito.'
  echo 'Nenhum arquivo da aplicacao foi modificado.'
  echo 'Para publicar: bash atualizar-linkstack.sh --deploy'
  exit 0
fi

# Cria o estagio de desenvolvimento com APENAS os arquivos de codigo da
# revisao instalada. Nao copia .env, usuarios, SQLite, uploads ou storage.
if [[ ! -d "$DEV" ]]; then
  echo "Preparando estagio de codigo isolado em $DEV..."
  stage_list=$(mktemp)
  while IFS= read -r path; do
    rel="${path#linkstack/}"
    case "$rel" in
      .env|.env.*|config/advanced-config.php|storage/*|database/*|themes/*|vendor/*|node_modules/*|.git/*|assets/favicon/icons/*|INSTALLERLOCK|*.sqlite|*.sqlite-*|*.db|*.db-*|composer.json|composer.lock|package.json|package-lock.json|yarn.lock|vite.config.*|webpack.mix.js) ;;
      app/*|config/*|routes/*|resources/*|assets/*|blocks/*|bootstrap/*|docs/*|index.php|.htaccess|server.php|web.config|mix-manifest.json|version.json) printf '%s\n' "$rel" >> "$stage_list";;
    esac
  done < <(gitrepo ls-tree -r --name-only "$BASE" -- linkstack/)
  install -d -o ubuntu -g ubuntu -m 755 "$DEV"
  rsync -a --files-from="$stage_list" "$PROD/" "$DEV/"
  chown -R ubuntu:ubuntu "$DEV"
  rm -f "$stage_list"
fi

if [[ "$LOCAL_HEAD" == "$TARGET" && ${#files[@]} -eq 0 ]]; then
  install -d -m 700 "$STATE_DIR"
  if [[ ! -s "$LAST_FILE" ]]; then printf '%s\n' "$BASE" > "$LAST_FILE"; fi
  echo 'Repositorio ja atualizado, nenhum arquivo pendente de publicacao.'
  exit 0
fi

mkdir -p "$STATE_DIR" "$BACKUP_ROOT"
if [[ ! -s "$LAST_FILE" ]]; then printf '%s\n' "$BASE" > "$LAST_FILE"; fi
backup="$(mktemp -d "$BACKUP_ROOT/$(date +%Y%m%d-%H%M%S)-XXXXXX")"
mkdir -p "$backup/dev" "$backup/prod"
# O instalador deste servidor utiliza SQLite. Crie um snapshot consistente
# sem interromper o site: SQLite .backup utiliza a API de backup online.
if [[ -f "$PROD/database/database.sqlite" ]]; then
  command -v sqlite3 >/dev/null || { echo 'sqlite3 ausente: backup SQLite obrigatório não realizado.' >&2; exit 1; }
  sqlite3 "$PROD/database/database.sqlite" ".backup '$backup/sqlite-before-deploy.sqlite'" ||
    { echo 'Falha no backup SQLite; publicacao bloqueada.' >&2; exit 1; }
  check_sqlite=$(sqlite3 "$backup/sqlite-before-deploy.sqlite" 'PRAGMA quick_check;')
  [[ "$check_sqlite" == ok ]] || { echo "Integridade do backup SQLite falhou: $check_sqlite" >&2; exit 1; }
  echo "Backup SQLite gerado: $backup/sqlite-before-deploy.sqlite"
else
  echo 'Aviso: SQLite nao encontrado. Para MySQL/MariaDB, providencie backup consistente separado.'
fi
: > "$backup/new-dev.txt"
: > "$backup/new-prod.txt"
: > "$backup/files.txt"
((${#files[@]} == 0)) || printf '%s\n' "${files[@]}" > "$backup/files.txt"
printf '%s\n' "$BASE" > "$backup/base-sha.txt"
printf '%s\n' "$TARGET" > "$backup/target-sha.txt"
for rel in "${files[@]}"; do
  for label in dev prod; do
    if [[ "$label" == dev ]]; then source_dir="$DEV"; else source_dir="$PROD"; fi
    if [[ -f "$source_dir/$rel" ]]; then
      mkdir -p "$backup/$label/$(dirname "$rel")"
      cp -a -- "$source_dir/$rel" "$backup/$label/$rel"
    else
      printf '%s\n' "$rel" >> "$backup/new-$label.txt"
    fi
  done
done

deploy_started=false
completed=false
rollback() {
  status=$?
  trap - EXIT
  if [[ "$deploy_started" == true && "$completed" != true ]]; then
    echo 'ERRO: restaurando arquivos anteriores de desenvolvimento e producao...' >&2
    for label in dev prod; do
      if [[ "$label" == dev ]]; then destination="$DEV"; else destination="$PROD"; fi
      while IFS= read -r rel; do
        [[ -n "$rel" ]] || continue
        rm -f -- "$destination/$rel"
      done < "$backup/new-$label.txt"
      if [[ -s "$backup/files.txt" ]]; then
        rsync -a --no-owner --no-group "$backup/$label/" "$destination/" || true
      fi
    done
    (cd "$PROD" && runuser -u www-data -- php artisan config:clear && runuser -u www-data -- php artisan route:clear && runuser -u www-data -- php artisan view:clear) || true
  fi
  if [[ "$completed" != true ]]; then echo "Backup de recuperacao: $backup"; fi
  exit "$status"
}
trap rollback EXIT

# O repositorio local e atualizado somente por fast-forward.
gitrepo merge --ff-only --quiet "origin/$BRANCH"

# Analise sintatica antes de publicar na producao.
for rel in "${files[@]}"; do
  [[ -f "$REPO/linkstack/$rel" && ! -L "$REPO/linkstack/$rel" ]] || { echo "Arquivo de codigo ausente ou symlink: $rel" >&2; exit 1; }
  case "$rel" in
    *.blade.php) ;; # Blade e compilado pelo Laravel, nao por php -l puro.
    *.php) php -l "$REPO/linkstack/$rel" >/dev/null ;;
    *.js) if command -v node >/dev/null; then node --check "$REPO/linkstack/$rel" >/dev/null; fi ;;
  esac
done

if ((${#files[@]})); then
  deploy_started=true
  # Rsync apenas dos arquivos alterados: sem --delete, sem tocar em storage, .env ou DB.
  rsync -a --chmod=F644,D755 --chown=ubuntu:ubuntu \
    --files-from="$backup/files.txt" "$REPO/linkstack/" "$DEV/"
  rsync -a --chmod=F644,D755 --no-owner --no-group \
    --files-from="$backup/files.txt" "$REPO/linkstack/" "$PROD/"
fi

# Nunca executar config:cache, route:cache ou optimize neste projeto.
(cd "$PROD" && runuser -u www-data -- php artisan config:clear && runuser -u www-data -- php artisan route:clear && runuser -u www-data -- php artisan view:clear)
printf '%s\n' "$TARGET" > "$LAST_FILE"
completed=true
printf '\nPUBLICACAO CONCLUIDA: %.12s\n' "$TARGET"
echo "Backup: $backup"
echo "Site: $SITE"
echo 'Confira no navegador com sessao autenticada: /dashboard, /studio/profile e /admin/config.'
