#!/usr/bin/env bash
# LinkFree-LinkTree: atualizacao seletiva GitHub -> desenvolvimento -> producao.
# Uso: sudo bash atualizar-linkstack.sh --check | --deploy
set -Eeuo pipefail
umask 077

REPO=/home/ubuntu/LinkFree-LinkTree
DEV=/home/ubuntu/linkstack-dev
PROD=/srv/linkstack
BRANCH=main
STATE_DIR=/var/lib/linkstack-deploy
LAST_FILE="$STATE_DIR/last-successful-sha"
BACKUP_ROOT=/var/backups/linkstack-deploy
SITE=https://linkstack.3.141.97.135.sslip.io

MODE="${1:-}"
if [[ "$MODE" != --check && "$MODE" != --deploy ]]; then
  echo 'Uso: bash atualizar-linkstack.sh --check | --deploy' >&2
  exit 2
fi
if (( EUID != 0 )); then echo 'Execute como root (sudo).' >&2; exit 1; fi
for cmd in git rsync php flock runuser cmp mktemp cp; do
  command -v "$cmd" >/dev/null || { echo "Dependencia ausente: $cmd" >&2; exit 1; }
done
for dir in "$REPO/.git" "$REPO/linkstack" "$DEV" "$PROD"; do
  [[ -d "$dir" ]] || { echo "Diretorio ausente: $dir" >&2; exit 1; }
done
exec 9>/tmp/linkstack-deploy.lock
flock -n 9 || { echo 'Ja existe uma atualizacao em andamento.' >&2; exit 1; }

current_branch="$(git -C "$REPO" symbolic-ref --short HEAD)"
[[ "$current_branch" == "$BRANCH" ]] || { echo "Branch inesperada: $current_branch" >&2; exit 1; }
[[ -z "$(git -C "$REPO" status --porcelain)" ]] || {
  echo 'Reposítorio local possui alteracoes pendentes. Pare para evitar perda de codigo.' >&2
  git -C "$REPO" status --short >&2
  exit 1
}
remote_url="$(git -C "$REPO" config --get remote.origin.url || :)"
case "$remote_url" in
  https://github.com/sistemacodigolucrativo/LinkFree-LinkTree.git|https://github.com/sistemacodigolucrativo/LinkFree-LinkTree|git@github.com:sistemacodigolucrativo/LinkFree-LinkTree.git) ;;
  *) echo 'O origin nao corresponde ao repositorio esperado; operacao bloqueada.' >&2; exit 1 ;;
esac

echo 'Buscando atualizacoes no GitHub...'
git -C "$REPO" fetch --quiet origin "$BRANCH"
TARGET="$(git -C "$REPO" rev-parse "origin/$BRANCH^{commit}")"
LOCAL_HEAD="$(git -C "$REPO" rev-parse 'HEAD^{commit}')"
BASE="$LOCAL_HEAD"
if [[ -s "$LAST_FILE" ]]; then
  BASE="$(head -n1 "$LAST_FILE")"
  git -C "$REPO" cat-file -e "$BASE^{commit}" || { echo 'Historico de deploy invalido.' >&2; exit 1; }
fi

git -C "$REPO" merge-base --is-ancestor "$LOCAL_HEAD" "$TARGET" || {
  echo 'Main local divergiu do remoto. Resolva manualmente; nao sera usado reset --hard.' >&2
  exit 1
}
git -C "$REPO" merge-base --is-ancestor "$BASE" "$TARGET" || {
  echo 'O commit de referencia do ultimo deploy nao e ancestral de origin/main.' >&2
  exit 1
}

mapfile -t changed < <(git -C "$REPO" diff --name-only --diff-filter=ACMRT "$BASE" "$TARGET" -- linkstack/)
mapfile -t deleted < <(git -C "$REPO" diff --name-only --diff-filter=D "$BASE" "$TARGET" -- linkstack/)
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
  if git -C "$REPO" cat-file -e "$BASE:$old_path" 2>/dev/null; then old_exists=true; fi
  for dest in "$DEV" "$PROD"; do
    if [[ -L "$dest/$rel" ]]; then
      echo "CONFLITO: destino e link simbolico: $dest/$rel" >&2
      exit 1
    fi
    if [[ "$old_exists" == true ]]; then
      if [[ ! -f "$dest/$rel" ]] || ! git -C "$REPO" show "$BASE:$old_path" | cmp -s - "$dest/$rel"; then
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
  echo 'SIMULACAO CONCLUIDA: os destinos passaram na verificacao de conflito.'
  echo 'Nenhum arquivo da aplicacao foi modificado.'
  echo 'Para publicar: bash atualizar-linkstack.sh --deploy'
  exit 0
fi

if [[ "$LOCAL_HEAD" == "$TARGET" && ${#files[@]} -eq 0 ]]; then
  echo 'Repositório ja atualizado, nenhum arquivo pendente de publicacao.'
  exit 0
fi

mkdir -p "$STATE_DIR" "$BACKUP_ROOT"
if [[ ! -s "$LAST_FILE" ]]; then printf '%s\n' "$BASE" > "$LAST_FILE"; fi
backup="$(mktemp -d "$BACKUP_ROOT/$(date +%Y%m%d-%H%M%S)-XXXXXX")"
mkdir -p "$backup/dev" "$backup/prod"
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
git -C "$REPO" merge --ff-only --quiet "origin/$BRANCH"

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
echo 'Confira no navegador com sessao autenticada: /studio/theme e /admin/github-themes.'
