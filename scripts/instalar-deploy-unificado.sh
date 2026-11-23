#!/usr/bin/env bash
# Instala sem alterar o alias 'deploy' já usado no shell do root.
# A substituição é atômica, preserva uma cópia do script anterior e não publica o site.
set -Eeuo pipefail
umask 077
(( EUID == 0 )) || { echo 'Execute como root.' >&2; exit 1; }
cd /
DEST=/root/atualizar-linkstack.sh
BACKUP_DIR=/root/linkfree-deploy-scripts-backup
[[ ! -L "$DEST" ]] || { echo 'Destino é symlink. Operação recusada.' >&2; exit 1; }
mkdir -p "$BACKUP_DIR"
work="$(mktemp /root/.linkfree-bootstrap-XXXXXX)"
trap 'rm -f -- "$work"' EXIT
cat > "$work" <<'LINKFREE_BOOTSTRAP'
#!/usr/bin/env bash
# Dispatcher estável para o alias existente; baixa a versão mais recente do
# atualizador do repositório autorizado antes de cada verificação ou deploy.
set -Eeuo pipefail
umask 077
cd /
if (( EUID != 0 )); then echo 'Execute como root.' >&2; exit 1; fi
if (( $# != 1 )); then
  echo 'Uso: atualizar-linkstack.sh --check | --deploy | --homologar' >&2
  exit 2
fi
mode="$1"
case "$mode" in
  --check|--deploy|--deploy-consent|--homologar) ;;
  *) echo "Modo não autorizado: $mode" >&2; exit 2 ;;
esac
REPO=/home/ubuntu/src-Projects/LinkFree-LinkTree
[[ -d "$REPO/.git" && ! -L "$REPO" ]] || { echo 'Clone do LinkFree indisponível.' >&2; exit 1; }
[[ "$(runuser -u ubuntu -- git -C "$REPO" symbolic-ref --short HEAD)" == main ]] || {
  echo 'Clone não está na branch main.' >&2; exit 1;
}
[[ -z "$(runuser -u ubuntu -- git -C "$REPO" status --porcelain)" ]] || {
  echo 'Clone possui modificações não publicadas; nenhum deploy será executado.' >&2; exit 1;
}
remote="$(runuser -u ubuntu -- git -C "$REPO" config --get remote.origin.url)"
case "$remote" in
  https://github.com/sistemacodigolucrativo/LinkFree-LinkTree.git|https://github.com/sistemacodigolucrativo/LinkFree-LinkTree|git@github.com:sistemacodigolucrativo/LinkFree-LinkTree.git) ;;
  *) echo 'Repositório remoto diferente do autorizado.' >&2; exit 1 ;;
esac
runuser -u ubuntu -- git -C "$REPO" fetch --quiet origin main
sha="$(runuser -u ubuntu -- git -C "$REPO" rev-parse 'origin/main^{commit}')"
[[ "$sha" =~ ^[a-f0-9]{40}$ ]] || { echo 'Revisão Git inválida.' >&2; exit 1; }
work="$(mktemp -d /root/.linkfree-run-XXXXXX)"
trap 'rm -rf -- "$work"' EXIT
if [[ "$mode" == --homologar ]]; then
  runuser -u ubuntu -- git -C "$REPO" show "$sha:scripts/homologar-linkfree.sh" > "$work/homologar.sh"
  bash -n "$work/homologar.sh"
  bash "$work/homologar.sh" --read-only
  exit $?
fi
runuser -u ubuntu -- git -C "$REPO" show "$sha:scripts/atualizar-linkstack.sh" > "$work/deploy.sh"
bash -n "$work/deploy.sh"
echo "DEPLOY LINKFREE: script revisado da main $(printf '%s' "$sha" | cut -c1-12)"
bash "$work/deploy.sh" "$mode"
if [[ "$mode" == --deploy || "$mode" == --deploy-consent ]]; then
  echo 'Verificando site, banco e arquivos persistentes após publicação...'
  runuser -u ubuntu -- git -C "$REPO" show "$sha:scripts/homologar-linkfree.sh" > "$work/homologar.sh"
  bash -n "$work/homologar.sh"
  if ! bash "$work/homologar.sh" --read-only; then
    echo 'ATENÇÃO: deploy executado, mas homologação técnica detectou falhas.' >&2
    echo 'Não repetir ou reverter migrações automaticamente. Examine o relatório.' >&2
    exit 1
  fi
fi
LINKFREE_BOOTSTRAP
bash -n "$work"
if [[ -f "$DEST" ]]; then
  stamp="$(date -u +%Y%m%dT%H%M%SZ)"
  cp -a -- "$DEST" "$BACKUP_DIR/atualizar-linkstack-$stamp.sh"
  echo "Backup do script anterior: $BACKUP_DIR/atualizar-linkstack-$stamp.sh"
fi
chmod 700 "$work"
mv -f -- "$work" "$DEST"
trap - EXIT
echo 'Instalado: /root/atualizar-linkstack.sh'
echo "O alias existente 'deploy' passa a buscar a versão revisada na main automaticamente."
echo 'Nenhum banco de dados, arquivo de usuário ou conteúdo do site foi modificado.'
echo 'Para executar a homologação sem deploy: bash /root/atualizar-linkstack.sh --homologar'
