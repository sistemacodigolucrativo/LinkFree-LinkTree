#!/usr/bin/env python3
from pathlib import Path
root=Path(__file__).resolve().parents[1]
dep=(root/'scripts/atualizar-linkstack.sh').read_text()
boot=(root/'scripts/instalar-deploy-unificado.sh').read_text()
for file in ('2026_11_23_000002_create_referral_tables.php',
             '2026_11_23_000003_create_profile_verification_workflow.php',
             '2026_11_23_000004_create_abuse_reports_tables.php'):
    assert file in dep and (root/'linkstack/database/migrations'/file).is_file()
assert 'database/migrations/*) manual+=' in dep
assert 'if [[ "$MODE" == --deploy-m13 ]]; then' in dep
assert '"$MODE" != --check && "$MODE" != --deploy-m13' in dep
assert '--deploy-consent|--deploy-m13|--homologar)' in boot
assert dep.index('sqlite-before-deploy.sqlite') < dep.index('php artisan migrate --path="$migration"')
assert dep.index('php artisan migrate --path="$migration"') < dep.index('deploy_started=true')
assert 'PRAGMA foreign_key_check' in dep
assert 'PRAGMA quick_check' in dep
assert 'm13_pending[@]' in dep
print('PASS: explicit M13 allowance, locked unknown migrations, backup and migration-before-code')
