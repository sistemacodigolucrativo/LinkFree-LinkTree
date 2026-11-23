#!/usr/bin/env python3
"""Auditoria estática do bootstrap que preserva o alias deploy na VPS."""
from pathlib import Path
root=Path(__file__).resolve().parents[1]
installer=(root/'scripts/instalar-deploy-unificado.sh').read_text(encoding='utf8')
deploy=(root/'scripts/atualizar-linkstack.sh').read_text(encoding='utf8')
smoke=(root/'scripts/homologar-linkfree.sh').read_text(encoding='utf8')
assert "DEST=/root/atualizar-linkstack.sh" in installer
assert "BACKUP_DIR=/root/linkfree-deploy-scripts-backup" in installer
assert "git -C \"$REPO\" fetch --quiet origin main" in installer
assert 'bash -n "$work/deploy.sh"' in installer
assert 'bash "$work/deploy.sh" "$mode"' in installer
assert 'bash "$work/homologar.sh" --read-only' in installer
assert "cd /" in installer
assert "LINKFREE_BOOTSTRAP" in installer
assert "mv -f -- \"$work\" \"$DEST\"" in installer
assert 'if [[ "$MODE" == --deploy || "$MODE" == --deploy-consent ]]' in deploy
assert 'database/migrations/*) manual+=' in deploy, 'unknown migrations must be blocked'
assert 'sqlite-before-deploy.sqlite' in deploy
assert 'storage-app/linkfree' in deploy
assert deploy.index('sqlite-before-deploy.sqlite') < deploy.index('php artisan migrate --path="$CONSENT_MIGRATION"')
assert 'php artisan migrate' not in smoke, 'read-only audit cannot migrate'
for forbidden in ('INSERT INTO', 'DELETE FROM', 'UPDATE users', 'DROP TABLE', 'TRUNCATE'):
    assert forbidden not in smoke.upper(), 'audit must not mutate production'
assert "sqlite3 -readonly" in smoke
assert "curl --silent --show-error" in smoke
assert "https://linkfree.website" in smoke
assert "NÃO COBERTO" in smoke
assert 'cd /' in smoke
print('PASS: deploy bootstrap keeps old alias, updates GitHub script, backs up and runs read-only smoke.')
