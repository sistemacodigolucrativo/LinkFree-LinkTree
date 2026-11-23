#!/usr/bin/env bash
# Avalia somente o esquema SQLite da VPS; nao faz migracao, deploy nem escrita.
set -Eeuo pipefail
if [[ "$#" -ne 1 || "$1" != "--read-only" ]]; then
  echo 'Uso: bash scripts/preflight-m13-readonly.sh --read-only' >&2
  exit 2
fi
DB=/srv/linkfree/database/database.sqlite
STATE=/var/lib/linkfree-deploy/last-successful-sha
command -v sqlite3 >/dev/null || { echo 'sqlite3 nao encontrado.' >&2; exit 1; }
[[ -f "$DB" && -r "$DB" && ! -L "$DB" ]] || {
  echo 'Banco inexistente, inacessivel ou simbolico.' >&2; exit 1;
}
echo 'LinkFree M13 - leitura somente'
if [[ -r "$STATE" ]]; then
  printf 'SHA publicado: '
  head -n1 "$STATE"
else
  echo 'AVISO: SHA publicado nao verificado'
fi
[[ "$(sqlite3 -readonly "$DB" 'PRAGMA quick_check;')" == ok ]] || {
  echo 'FALHA: quick_check SQLite.' >&2; exit 1;
}
echo 'OK: integridade SQLite'
[[ -z "$(sqlite3 -readonly "$DB" 'PRAGMA foreign_key_check;')" ]] || {
  echo 'FALHA: foreign_key_check SQLite.' >&2; exit 1;
}
echo 'OK: referencias SQLite'
pending=0
for t in registration_consents referral_codes referrals verification_requests verification_audit_events abuse_reports abuse_report_events; do
  found="$(sqlite3 -readonly "$DB" "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='$t';")"
  if [[ "$found" == 1 ]]; then echo "OK: tabela $t"; else
    echo "PENDENTE: tabela $t"; pending=$((pending+1))
  fi
done
for c in verified_at verification_visible; do
  found="$(sqlite3 -readonly "$DB" "SELECT COUNT(*) FROM pragma_table_info('users') WHERE name='$c';")"
  if [[ "$found" == 1 ]]; then echo "OK: users.$c"; else
    echo "PENDENTE: users.$c"; pending=$((pending+1))
  fi
done
for m in \
  2026_11_23_000001_create_registration_consents_table \
  2026_11_23_000002_create_referral_tables \
  2026_11_23_000003_create_profile_verification_workflow \
  2026_11_23_000004_create_abuse_reports_tables; do
  found="$(sqlite3 -readonly "$DB" "SELECT COUNT(*) FROM migrations WHERE migration='$m';")"
  if [[ "$found" == 1 ]]; then echo "OK: migration $m"; else
    echo "PENDENTE: migration $m"; pending=$((pending+1))
  fi
done
if ((pending>0)); then
  echo "RESULTADO: $pending pendencia(s) de schema. Nao publique codigo novo."
  exit 3
fi
echo 'RESULTADO: esquema presente, SOMENTE preflight tecnico (nao homologacao).'
