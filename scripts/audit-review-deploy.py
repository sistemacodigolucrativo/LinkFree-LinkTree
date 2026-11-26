#!/usr/bin/env python3
"""Test only the review-deploy gate and migration fragment in disposable SQLite.
No production path is used or modified; artisan itself is tested by the separate
HTTP/SQLite Laravel workflow.
"""
from pathlib import Path
import os
import shlex
import shutil
import sqlite3
import subprocess
import tempfile

root = Path(__file__).resolve().parents[1]
shell = (root / "scripts/atualizar-linkstack.sh").read_text(encoding="utf-8")
dispatcher = (root / "scripts/instalar-deploy-unificado.sh").read_text(encoding="utf-8")
migration = root / "linkstack/database/migrations/2026_11_26_000001_create_platform_reviews_table.php"
assert migration.is_file()
assert shutil.which("php") and shutil.which("sqlite3"), "PHP and SQLite CLI required"
assert "bash \"$work/deploy.sh\" \"$mode\"" in dispatcher
assert "--check|--deploy|--deploy-consent|--deploy-m13|--deploy-reviews|--homologar)" in dispatcher
assert 'if [[ "$mode" == --deploy || "$mode" == --deploy-consent || "$mode" == --deploy-m13 || "$mode" == --deploy-reviews ]]; then' in dispatcher
assert shell.count("REVIEW_MIGRATION='database/migrations/2026_11_26_000001_create_platform_reviews_table.php'") == 1
assert '"$REVIEW_MIGRATION") review_migration=true; continue ;;' in shell
assert 'database/migrations/*) manual+=' in shell, "Unknown migrations must stay blocked"
assert '--deploy-reviews' in shell and "PRAGMA foreign_key_check" in shell
assert shell.index('sqlite-before-deploy.sqlite') < shell.index('php artisan migrate --path="$REVIEW_MIGRATION"')
assert shell.index('php artisan migrate --path="$REVIEW_MIGRATION"') < shell.index('deploy_started=true\n  # Rsync')
assert 'sqlite3 "$PROD/database/database.sqlite" ".backup' in shell
print("PASS: explicit bootstrap command, guarded diff, backup and migration ordering", flush=True)

gate_start = shell.index("# Avaliações exigem modo explícito")
gate_end = shell.index("# A M13 exige modo explícito", gate_start)
gate = shell[gate_start:gate_end]
for mode, pending, expected in (
    ("--check", "true", 0),
    ("--deploy-reviews", "true", 0),
    ("--deploy", "true", 1),
    ("--deploy-m13", "true", 1),
    ("--deploy-consent", "true", 1),
    ("--deploy-reviews", "false", 1),
):
    script = (
        "set -Eeuo pipefail\n"
        f"MODE={shlex.quote(mode)}\n"
        f"review_migration={pending}\n"
        "consent_migration=false\nm13_pending=()\n"
        + gate
    )
    r = subprocess.run(["bash", "-c", script], capture_output=True, text=True, timeout=12)
    assert r.returncode == expected, (mode, pending, r.returncode, r.stderr)
print("PASS: --check is read-only; migration cannot run through generic deploy or missing diff", flush=True)

begin = shell.index("# Migrar avaliações no banco SQLite")
end = shell.index("# Só cria diretórios que não existem", begin)
fragment = shell[begin:end]

def run_case(case: str, valid_backup: bool, expect: int):
    with tempfile.TemporaryDirectory(prefix="lf-reviews-deploy-") as tmp:
        temp = Path(tmp)
        prod = temp / "prod"
        backup = temp / "backup"
        (prod / "database/migrations").mkdir(parents=True)
        backup.mkdir()
        db = prod / "database/database.sqlite"
        with sqlite3.connect(db) as conn:
            conn.execute("create table users (id integer primary key)")
            conn.execute("create table migrations (migration text primary key)")
            conn.execute("insert into users values (5)")
        if valid_backup:
            with sqlite3.connect(db) as src, sqlite3.connect(backup / "sqlite-before-deploy.sqlite") as dst:
                src.backup(dst)
        starter = (
            "set -Eeuo pipefail\n"
            "MODE=--deploy-reviews\nreview_migration=true\n"
            f"PROD={shlex.quote(str(prod))}\n"
            f"backup={shlex.quote(str(backup))}\n"
            "TARGET=synthetic-head\n"
            "REVIEW_MIGRATION=database/migrations/2026_11_26_000001_create_platform_reviews_table.php\n"
            f"MIGRATION_SOURCE={shlex.quote(str(migration))}\n"
            + r'''
gitrepo() {
    [[ "$1" == show && "$2" == "$TARGET:linkstack/$REVIEW_MIGRATION" ]] || return 73
    cat -- "$MIGRATION_SOURCE"
}
runuser() {
    [[ "$*" == "-u www-data -- php artisan migrate --path=$REVIEW_MIGRATION --force --no-interaction" ]] || return 74
    # Only the shell integration is being exercised here. A separate CI job
    # runs the real Laravel migration against the disposable HTTP fixture.
    python3 - "$PROD/database/database.sqlite" <<'PY_SQL'
import sqlite3,sys
with sqlite3.connect(sys.argv[1]) as c:
    c.execute("""create table platform_reviews (
        id integer primary key, user_id integer unique,
        rating integer, testimonial text, publication_consent integer,
        consented_at text, consent_version text, status text,
        reviewed_by integer, reviewed_at text)""")
    c.execute("insert into migrations values ('2026_11_26_000001_create_platform_reviews_table')")
PY_SQL
}
'''
        )
        result = subprocess.run(["bash", "-c", starter + fragment], capture_output=True,
                                text=True, timeout=18)
        assert result.returncode == expect, (case, result.returncode, result.stderr)
        with sqlite3.connect(db) as conn:
            tables = {t[0] for t in conn.execute("select name from sqlite_master where type='table'")}
            assert conn.execute("select id from users").fetchall() == [(5,)]
            assert conn.execute("pragma quick_check").fetchone()[0] == "ok"
            assert ("platform_reviews" in tables) == valid_backup
        if valid_backup:
            assert (backup / "reviews-migration.php").read_bytes() == migration.read_bytes()
            assert (prod / "database/migrations" / migration.name).read_bytes() == migration.read_bytes()
        else:
            assert "platform_reviews" not in tables
        print(f"PASS: isolated {case}", flush=True)

run_case("guarded review migration and schema checks", True, 0)
run_case("missing backup refuses migration and preserves DB", False, 1)
print("PASS: standalone review-deploy gate and SQL verification; production not accessed", flush=True)
