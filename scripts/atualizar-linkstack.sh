#!/usr/bin/env bash
# LinkFree-LinkTree: atualizacao seletiva GitHub -> desenvolvimento -> producao.
# Uso: sudo bash atualizar-linkstack.sh --check | --deploy | --deploy-consent | --deploy-m13 | --deploy-reviews
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
if [[ "$MODE" != --check && "$MODE" != --deploy && "$MODE" != --deploy-consent && "$MODE" != --deploy-m13 && "$MODE" != --deploy-reviews ]]; then
  echo 'Uso: bash atualizar-linkstack.sh --check | --deploy | --deploy-consent | --deploy-m13 | --deploy-reviews' >&2
  exit 2
fi
if (( EUID != 0 )); then echo 'Execute como root (sudo).' >&2; exit 1; fi
# runuser -u www-data não consegue voltar a /root: use um cwd público sem alterar arquivos.
cd /
# O instal.sh nao instala rsync: obtenha-o no primeiro deploy, sem prompts Y/N.
if ! command -v rsync >/dev/null 2>&1; then
  export DEBIAN_FRONTEND=noninteractive
  apt-get update && apt-get install -y rsync
fi
for cmd in git rsync php flock runuser cmp mktemp cp python3 find realpath stat grep; do
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
CONSENT_MIGRATION='database/migrations/2026_11_23_000001_create_registration_consents_table.php'
consent_migration=false
# Allowlist fechada: somente três migrations M13 conhecidas.
M13_MIGRATION_05='database/migrations/2026_11_23_000002_create_referral_tables.php'
M13_MIGRATION_06='database/migrations/2026_11_23_000003_create_profile_verification_workflow.php'
M13_MIGRATION_07='database/migrations/2026_11_23_000004_create_abuse_reports_tables.php'
m13_pending=()
# Migração isolada; requer autorização explícita do operador.
REVIEW_MIGRATION='database/migrations/2026_11_26_000001_create_platform_reviews_table.php'
review_migration=false
for path in "${changed[@]}"; do
  rel="${path#linkstack/}"
  case "$rel" in
    "$CONSENT_MIGRATION") consent_migration=true; continue ;; # Migração aditiva conhecida, admitida no fluxo normal.
    "$M13_MIGRATION_05"|"$M13_MIGRATION_06"|"$M13_MIGRATION_07") m13_pending+=("$rel"); continue ;;
    "$REVIEW_MIGRATION") review_migration=true; continue ;;
    database/migrations/*) manual+=("$rel"); continue ;; # As demais permanecem bloqueadas.
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

# Avaliações exigem modo explícito, não modificam SQLite no --deploy regular.
if [[ "$review_migration" == true ]]; then
  [[ "$consent_migration" == false && ${#m13_pending[@]} -eq 0 ]] || {
    echo 'Migração de avaliações e outra migração detectadas no mesmo salto.' >&2; exit 1;
  }
  if [[ "$MODE" != --check && "$MODE" != --deploy-reviews ]]; then
    echo 'Migração de avaliações detectada. Use --check; após aprovação use --deploy-reviews.' >&2; exit 1
  fi
elif [[ "$MODE" == --deploy-reviews ]]; then
  echo 'Sem migração de avaliações pendente; --deploy-reviews não se aplica.' >&2; exit 1
fi

# A M13 exige modo explícito: deploy normal não pode alterar seu esquema.
if (( ${#m13_pending[@]} > 0 )); then
  if [[ "$MODE" != --check && "$MODE" != --deploy-m13 ]]; then
    echo 'M13 detectada. Use --check primeiro; em janela autorizada use --deploy-m13.' >&2
    exit 1
  fi
  [[ "$consent_migration" == false ]] || {
    echo 'Consentimento e M13 no mesmo salto: revisar separadamente.' >&2; exit 1;
  }
  (( ${#m13_pending[@]} == 3 )) || {
    echo 'Conjunto incompleto de migrations M13; esperadas 000002, 000003, 000004.' >&2; exit 1;
  }
elif [[ "$MODE" == --deploy-m13 ]]; then
  echo 'Sem migrations M13 pendentes nesta revisão; --deploy-m13 não se aplica.' >&2
  exit 1
fi

# As migrations conhecidas exigem preflight de banco, inclusive no modo --check.
if [[ "$consent_migration" == true || ${#m13_pending[@]} -gt 0 || "$review_migration" == true ]]; then
  # --deploy e --deploy-consent utilizam o MESMO preflight e backup.
  # Migrações desconhecidas continuam bloqueadas.
  [[ -f "$PROD/database/database.sqlite" && ! -L "$PROD/database/database.sqlite" ]] || {
    echo 'SQLite esperado não encontrado em database/database.sqlite.' >&2; exit 1;
  }
  db_driver="$(sed -n 's/^[[:space:]]*DB_CONNECTION=//p' "$PROD/.env" | tail -n1 | tr -d '" \r')"
  [[ "$db_driver" == sqlite ]] || { echo 'DB_CONNECTION não é sqlite. Revisão manual.' >&2; exit 1; }
  db_path="$(sed -n 's/^[[:space:]]*DB_DATABASE=//p' "$PROD/.env" | tail -n1 | tr -d '"\r')"
  case "$db_path" in
    ''|database/database.sqlite|"$PROD/database/database.sqlite") ;;
    *) echo 'DB_DATABASE aponta para outro banco. Operação bloqueada.' >&2; exit 1 ;;
  esac
  command -v sqlite3 >/dev/null || { echo 'sqlite3 obrigatório para backup online.' >&2; exit 1; }
  php -m | grep -qi '^pdo_sqlite$' || { echo 'Extensão pdo_sqlite ausente.' >&2; exit 1; }
  runuser -u www-data -- test -r "$PROD/database/database.sqlite" || { echo 'www-data não lê SQLite.' >&2; exit 1; }
  [[ "$(sqlite3 -readonly "$PROD/database/database.sqlite" 'PRAGMA quick_check;')" == ok ]] || {
    echo 'Integridade do SQLite não confirmada.' >&2; exit 1;
  }
  legal_ok="$(sqlite3 -readonly "$PROD/database/database.sqlite" "SELECT CASE WHEN EXISTS (SELECT 1 FROM pages WHERE length(trim(coalesce(terms, ''))) > 0 AND length(trim(coalesce(privacy, ''))) > 0) THEN 'ok' ELSE 'missing' END;" 2>/dev/null || :)"
  [[ "$legal_ok" == ok ]] || { echo 'Termos/Privacidade não estão preenchidos na tabela pages.' >&2; exit 1; }
  [[ ! -L "$PROD/$CONSENT_MIGRATION" ]] || { echo 'Migração via symlink recusada.' >&2; exit 1; }
  gitrepo cat-file -e "$TARGET:linkstack/$CONSENT_MIGRATION" || { echo 'Migração ausente do commit remoto.' >&2; exit 1; }
  if [[ -e "$PROD/$CONSENT_MIGRATION" ]] && ! gitrepo show "$TARGET:linkstack/$CONSENT_MIGRATION" | cmp -s - "$PROD/$CONSENT_MIGRATION"; then
    echo 'Migração instalada diverge do commit remoto.' >&2; exit 1
  fi
  if [[ "$review_migration" == true ]]; then
    [[ ! -L "$PROD/$REVIEW_MIGRATION" ]] || { echo 'Migration de avaliações via symlink recusada.' >&2; exit 1; }
    gitrepo cat-file -e "$TARGET:linkstack/$REVIEW_MIGRATION" || {
      echo 'Migration de avaliações não existe no commit remoto.' >&2; exit 1
    }
    if [[ -e "$PROD/$REVIEW_MIGRATION" ]] && ! gitrepo show "$TARGET:linkstack/$REVIEW_MIGRATION" | cmp -s - "$PROD/$REVIEW_MIGRATION"; then
      echo 'Migration de avaliações instalada divergente.' >&2; exit 1
    fi
  fi
  for migration in "${m13_pending[@]}"; do
    [[ ! -L "$PROD/$migration" ]] || { echo "Symlink recusado: $migration" >&2; exit 1; }
    gitrepo cat-file -e "$TARGET:linkstack/$migration" || { echo "Migration ausente do remoto: $migration" >&2; exit 1; }
    if [[ -e "$PROD/$migration" ]] && ! gitrepo show "$TARGET:linkstack/$migration" | cmp -s - "$PROD/$migration"; then
      echo "Migration divergente instalada: $migration" >&2; exit 1
    fi
  done
  [[ -z "$(sqlite3 -readonly "$PROD/database/database.sqlite" 'PRAGMA foreign_key_check;' 2>/dev/null)" ]] || {
    echo 'SQLite contém foreign keys inválidas.' >&2; exit 1;
  }
  if (( ${#m13_pending[@]} > 0 )); then
    [[ "$(sqlite3 -readonly "$PROD/database/database.sqlite" "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='registration_consents';")" == 1 ]] || {
      echo 'Tabela de consentimento necessária antes da M13.' >&2; exit 1;
    }
  fi
fi
if [[ -d "$PROD/storage/app/linkfree" ]]; then
  [[ ! -L "$PROD/storage/app/linkfree" ]] || { echo 'Storage symlink recusado.' >&2; exit 1; }
  runuser -u www-data -- test -w "$PROD/storage/app/linkfree" || { echo 'Configurações sem escrita por www-data.' >&2; exit 1; }
  if [[ -d "$PROD/storage/app/linkfree/visit-sources" ]]; then
    [[ ! -L "$PROD/storage/app/linkfree/visit-sources" ]] || { echo 'Visit-sources symlink recusado.' >&2; exit 1; }
    runuser -u www-data -- test -w "$PROD/storage/app/linkfree/visit-sources" || { echo 'Relatórios sem escrita por www-data.' >&2; exit 1; }
  fi
  # Arquivos antigos podem estar em propriedade de root: recusar antes de sobrescrever.
  if [[ -n "$(find "$PROD/storage/app/linkfree" -type l -print -quit)" ]]; then
    echo 'Symlink dentro de storage/app/linkfree: revisão manual obrigatória.' >&2; exit 1
  fi
  unreadable="$(runuser -u www-data -- find "$PROD/storage/app/linkfree" -type f ! -readable -print -quit)"
  [[ -z "$unreadable" ]] || { echo "Arquivo de configuração sem leitura por www-data: $unreadable" >&2; exit 1; }
  unwritable_locks="$(runuser -u www-data -- find "$PROD/storage/app/linkfree" -name '*.lock' -type f ! -writable -print -quit)"
  [[ -z "$unwritable_locks" ]] || { echo "Arquivo de lock sem escrita por www-data: $unwritable_locks" >&2; exit 1; }
else
  runuser -u www-data -- test -w "$PROD/storage/app" || { echo 'www-data não pode criar storage/app/linkfree.' >&2; exit 1; }
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
  if [[ "$review_migration" == true ]]; then
    echo 'Migração de avaliações reconhecida. Em janela aprovada: bash /root/atualizar-linkstack.sh --deploy-reviews'
  elif (( ${#m13_pending[@]} > 0 )); then
    echo 'M13 reconhecida. Após aprovação e backup, use: bash /root/atualizar-linkstack.sh --deploy-m13'
  elif [[ "$consent_migration" == true ]]; then
    echo 'Migração reconhecida: o comando deploy aplicará backup e migração automaticamente.'
  else
    echo 'Para publicar: bash atualizar-linkstack.sh --deploy'
  fi
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

if [[ "$LOCAL_HEAD" == "$TARGET" && ${#files[@]} -eq 0 && "$consent_migration" != true && ${#m13_pending[@]} -eq 0 ]]; then
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
# Backup também das configurações privadas, sem modificar nem apagar dados.
if [[ -d "$PROD/storage/app/linkfree" ]]; then
  mkdir -p "$backup/storage-app"
  cp -a -- "$PROD/storage/app/linkfree" "$backup/storage-app/linkfree" || {
    echo 'Falha no backup de storage/app/linkfree.' >&2; exit 1;
  }
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
      cmp -s -- "$source_dir/$rel" "$backup/$label/$rel" || { echo "Backup de codigo divergente: $label/$rel" >&2; exit 1; }
    else
      printf '%s\n' "$rel" >> "$backup/new-$label.txt"
    fi
  done
done

deploy_started=false
completed=false
# Compare contents independently of rsync quick-check (size/mtime).
verify_published_files() {
  local destination="$1" rel
  for rel in "${files[@]}"; do
    if [[ ! -f "$destination/$rel" || -L "$destination/$rel" ]] ||
       ! cmp -s -- "$REPO/linkstack/$rel" "$destination/$rel"; then
      echo "ERRO: publicacao divergente: $destination/$rel" >&2
      return 1
    fi
  done
}

rollback() {
  local status=$? failed=0 label destination rel source target old_meta restored_meta task
  trap - EXIT
  if [[ "$deploy_started" == true && "$completed" != true ]]; then
    echo 'ERRO: restaurando arquivos anteriores de desenvolvimento e producao...' >&2
    for label in dev prod; do
      if [[ "$label" == dev ]]; then destination="$DEV"; else destination="$PROD"; fi
      # Restaurar somente os arquivos do manifesto, nunca atributos dos diretorios
      # privados do backup sobre os diretorios da instalacao.
      if [[ ! -f "$backup/files.txt" || ! -f "$backup/new-$label.txt" ]]; then
        echo "ERRO rollback: manifesto ausente ($label)" >&2
        failed=1
        continue
      fi
      while IFS= read -r rel; do
        [[ -n "$rel" ]] || continue
        source="$backup/$label/$rel"
        target="$destination/$rel"
        # Nao seguir caminhos simbolicos em diretorios nem aceitar caminhos fora da raiz.
        if [[ "$rel" == /* || "/$rel/" == */../* || "/$rel/" == */./* ]] ||
           [[ "$(realpath -m -- "$(dirname -- "$target")")" != "$(realpath -ms -- "$(dirname -- "$target")")" ]]; then
          echo "ERRO rollback: caminho inseguro ($label/$rel)" >&2
          failed=1
          continue
        fi
        if grep -Fxq -- "$rel" "$backup/new-$label.txt"; then
          if [[ -e "$source" || -L "$source" ]] ||
             ! rm -f -- "$target" || [[ -e "$target" || -L "$target" ]]; then
            echo "ERRO rollback: nao removido ou manifesto conflitante ($label/$rel)" >&2
            failed=1
          fi
        elif [[ ! -f "$source" || -L "$source" || -L "$target" ]] ||
             ! rsync -ac --numeric-ids -- "$source" "$target" ||
             [[ ! -f "$target" || -L "$target" ]] ||
             ! cmp -s -- "$source" "$target"; then
          echo "ERRO rollback: restauracao de conteudo falhou ($label/$rel)" >&2
          failed=1
        elif ! old_meta="$(stat -c '%a:%u:%g:%Y' -- "$source")" ||
             ! restored_meta="$(stat -c '%a:%u:%g:%Y' -- "$target")" ||
             [[ "$old_meta" != "$restored_meta" ]]; then
          echo "ERRO rollback: metadados divergentes ($label/$rel)" >&2
          failed=1
        fi
      done < "$backup/files.txt"
    done
    for task in config:clear route:clear view:clear; do
      if ! (cd "$PROD" && runuser -u www-data -- php artisan "$task"); then
        echo "ERRO rollback: cache nao limpo ($task)" >&2
        failed=1
      fi
    done
    if (( failed )); then
      echo "ROLLBACK INCOMPLETO: falha original=$status; recuperacao manual obrigatoria. Banco nao revertido." >&2
      echo "Backup de recuperacao: $backup" >&2
      exit 74
    fi
    echo 'ROLLBACK DE CODIGO VERIFICADO: conteudo, metadados e arquivos novos; banco nao revertido.' >&2
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

# Executa uma única migração aditiva antes de publicar o código de cadastro.
if [[ "$MODE" == --deploy-consent && "$consent_migration" != true ]]; then
  echo '--deploy-consent só é válido com a migração de consentimento pendente.' >&2; exit 1
fi
if [[ "$consent_migration" == true ]]; then
  [[ "$MODE" == --deploy || "$MODE" == --deploy-consent ]] || { echo 'Modo inválido para migração.' >&2; exit 1; }
  mkdir -p "$PROD/database/migrations"
  if [[ ! -f "$PROD/$CONSENT_MIGRATION" ]]; then
    gitrepo show "$TARGET:linkstack/$CONSENT_MIGRATION" > "$backup/consent-migration.php"
    php -l "$backup/consent-migration.php" >/dev/null
    install -m 644 "$backup/consent-migration.php" "$PROD/$CONSENT_MIGRATION"
  fi
  (cd "$PROD" && runuser -u www-data -- php artisan migrate --path="$CONSENT_MIGRATION" --force --no-interaction)
  [[ "$(sqlite3 -readonly "$PROD/database/database.sqlite" "SELECT COUNT(*) FROM sqlite_master WHERE name='registration_consents' AND type='table';")" == 1 ]] || {
    echo 'Tabela de consentimento ausente: código NÃO publicado.' >&2; exit 1;
  }
fi
# Migrations M13, permitidas apenas no modo autorizado, com backup pronto.
if [[ "$MODE" == --deploy-m13 ]]; then
  [[ -f "$backup/sqlite-before-deploy.sqlite" ]] || { echo 'Backup SQLite ausente. Não migrar.' >&2; exit 1; }
  [[ "$(sqlite3 "$backup/sqlite-before-deploy.sqlite" 'PRAGMA quick_check;')" == ok ]] || {
    echo 'Backup SQLite íntegro não confirmado.' >&2; exit 1;
  }
  mkdir -p "$PROD/database/migrations"
  for migration in "$M13_MIGRATION_05" "$M13_MIGRATION_06" "$M13_MIGRATION_07"; do
    gitrepo show "$TARGET:linkstack/$migration" > "$backup/${migration##*/}"
    php -l "$backup/${migration##*/}" >/dev/null
    if [[ ! -f "$PROD/$migration" ]]; then
      install -m 644 "$backup/${migration##*/}" "$PROD/$migration"
    fi
    (cd "$PROD" && runuser -u www-data -- php artisan migrate --path="$migration" --force --no-interaction)
  done
  for table in referral_codes referrals verification_requests verification_audit_events abuse_reports abuse_report_events; do
    [[ "$(sqlite3 -readonly "$PROD/database/database.sqlite" "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='$table';")" == 1 ]] || {
      echo "Tabela M13 ausente: $table" >&2; exit 1;
    }
  done
  for column in verified_at verification_visible; do
    [[ "$(sqlite3 -readonly "$PROD/database/database.sqlite" "SELECT COUNT(*) FROM pragma_table_info('users') WHERE name='$column';")" == 1 ]] || {
      echo "Coluna ausente: users.$column" >&2; exit 1;
    }
  done
  for migration in "${m13_pending[@]}"; do
    migration_name="${migration##*/}"
    migration_name="${migration_name%.php}"
    [[ "$(sqlite3 -readonly "$PROD/database/database.sqlite" "SELECT COUNT(*) FROM migrations WHERE migration='$migration_name';")" == 1 ]] || {
      echo "Migration M13 não registrada como Ran: $migration_name" >&2; exit 1;
    }
  done
  [[ "$(sqlite3 -readonly "$PROD/database/database.sqlite" 'PRAGMA quick_check;')" == ok ]] || {
    echo 'Falha no quick_check após migração M13.' >&2; exit 1;
  }
  [[ -z "$(sqlite3 -readonly "$PROD/database/database.sqlite" 'PRAGMA foreign_key_check;')" ]] || {
    echo 'Falha no foreign_key_check após migração M13.' >&2; exit 1;
  }
  echo 'Migrations 000002–000004 aplicadas e esquema verificado, antes de publicar código.'
fi
# Migrar avaliações no banco SQLite após backup online e antes do código novo.
if [[ "$MODE" == --deploy-reviews ]]; then
  [[ "$review_migration" == true && -f "$backup/sqlite-before-deploy.sqlite" ]] || {
    echo 'Migração de avaliações ou backup SQLite não encontrado.' >&2; exit 1
  }
  [[ "$(sqlite3 "$backup/sqlite-before-deploy.sqlite" 'PRAGMA quick_check;')" == ok ]] || {
    echo 'Backup SQLite inválido; migração cancelada.' >&2; exit 1
  }
  mkdir -p "$PROD/database/migrations"
  gitrepo show "$TARGET:linkstack/$REVIEW_MIGRATION" > "$backup/reviews-migration.php"
  php -l "$backup/reviews-migration.php" >/dev/null
  if [[ ! -f "$PROD/$REVIEW_MIGRATION" ]]; then
    install -m 644 "$backup/reviews-migration.php" "$PROD/$REVIEW_MIGRATION"
  fi
  (cd "$PROD" && runuser -u www-data -- php artisan migrate --path="$REVIEW_MIGRATION" --force --no-interaction)
  for column in user_id rating testimonial publication_consent consented_at consent_version status reviewed_by reviewed_at; do
    [[ "$(sqlite3 -readonly "$PROD/database/database.sqlite" "SELECT COUNT(*) FROM pragma_table_info('platform_reviews') WHERE name='$column';")" == 1 ]] || {
      echo "Coluna platform_reviews.$column ausente." >&2; exit 1
    }
  done
  [[ "$(sqlite3 -readonly "$PROD/database/database.sqlite" "SELECT COUNT(*) FROM migrations WHERE migration='2026_11_26_000001_create_platform_reviews_table';")" == 1 ]] || {
    echo 'Migração de avaliações não registrada.' >&2; exit 1
  }
  [[ "$(sqlite3 -readonly "$PROD/database/database.sqlite" 'PRAGMA quick_check;')" == ok ]] || {
    echo 'SQLite inválido após migração de avaliações.' >&2; exit 1
  }
  [[ -z "$(sqlite3 -readonly "$PROD/database/database.sqlite" 'PRAGMA foreign_key_check;')" ]] || {
    echo 'Foreign keys inválidas após migração de avaliações.' >&2; exit 1
  }
  echo 'Migração de avaliações aprovada antes da publicação.'
fi
# Só cria diretórios que não existem; nunca executa chown recursivo.
if [[ ! -d "$PROD/storage/app/linkfree" ]]; then
  install -d -m 750 -o www-data -g www-data "$PROD/storage/app/linkfree"
fi
if [[ ! -d "$PROD/storage/app/linkfree/visit-sources" ]]; then
  install -d -m 750 -o www-data -g www-data "$PROD/storage/app/linkfree/visit-sources"
fi
runuser -u www-data -- test -w "$PROD/storage/app/linkfree" || { echo 'Sem acesso às configurações.' >&2; exit 1; }
runuser -u www-data -- test -w "$PROD/storage/app/linkfree/visit-sources" || { echo 'Sem acesso aos relatórios.' >&2; exit 1; }

if ((${#files[@]})); then
  deploy_started=true
  # Rsync apenas dos arquivos alterados: sem --delete, sem tocar em storage, .env ou DB.
  rsync -ac --chmod=F644,D755 --chown=ubuntu:ubuntu \
    --files-from="$backup/files.txt" "$REPO/linkstack/" "$DEV/"
  rsync -ac --chmod=F644,D755 --no-owner --no-group \
    --files-from="$backup/files.txt" "$REPO/linkstack/" "$PROD/"
  verify_published_files "$DEV"
  verify_published_files "$PROD"
fi

# Nunca executar config:cache, route:cache ou optimize neste projeto.
(cd "$PROD" && runuser -u www-data -- php artisan config:clear && runuser -u www-data -- php artisan route:clear && runuser -u www-data -- php artisan view:clear)
printf '%s\n' "$TARGET" > "$LAST_FILE"
completed=true
printf '\nPUBLICACAO CONCLUIDA: %.12s\n' "$TARGET"
echo "Backup: $backup"
echo "Site: $SITE"
echo 'O smoke test não autenticado é executado pelo instalador unificado (deploy).'
echo 'Confira com sessão autenticada: /dashboard, /studio/profile e /admin/config.'
