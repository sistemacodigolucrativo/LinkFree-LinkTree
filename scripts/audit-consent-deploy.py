#!/usr/bin/env python3
"""Preflight contract for the only allowed consent schema deployment."""
from pathlib import Path
base=Path(__file__).resolve().parents[1]
shell=(base/'scripts/atualizar-linkstack.sh').read_text(encoding='utf-8')
mig='database/migrations/2026_11_23_000001_create_registration_consents_table.php'
assert f"CONSENT_MIGRATION='{mig}'" in shell
assert 'database/migrations/*) manual+=' in shell, 'unrelated migrations must stay blocked'
assert '[[ "$MODE" == --deploy || "$MODE" == --deploy-consent ]]' in shell, 'known additive migration must be safe with plain deploy'
assert '--deploy-consent' in shell, 'legacy consent mode should remain supported'
assert 'cd /' in shell, 'must avoid www-data cwd in /root'
assert 'sqlite3 "$PROD/database/database.sqlite" ".backup' in shell
assert 'sqlite-before-deploy.sqlite' in shell
assert 'storage-app/linkfree' in shell
assert 'runuser -u www-data -- php artisan migrate --path="$CONSENT_MIGRATION" --force --no-interaction' in shell
assert shell.index("sqlite-before-deploy.sqlite") < shell.index('php artisan migrate --path="$CONSENT_MIGRATION"'), 'backup must precede migration'
assert shell.index('php artisan migrate --path="$CONSENT_MIGRATION"') < shell.index('deploy_started=true\n  # Rsync'), 'migration must precede new source publication'
assert '--delete' not in '\n'.join(l for l in shell.splitlines() if l.strip().startswith('rsync ')), 'never delete customer data'
assert 'chown -R www-data' not in shell, 'never recursively change customer ownership'
print('PASS: known consent migration uses normal deploy with backup, safe ordering and isolation')
