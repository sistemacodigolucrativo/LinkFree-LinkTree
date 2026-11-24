#!/usr/bin/env python3
"""Execute the official rollback/verification fragment only in temporary fixtures."""
from pathlib import Path
import os
import shutil
import sqlite3
import subprocess
import tempfile

SCRIPT = Path(__file__).with_name("atualizar-linkstack.sh").read_text()
FRAGMENT = SCRIPT[SCRIPT.index("deploy_started=false\n"):SCRIPT.index("trap rollback EXIT\n") + len("trap rollback EXIT\n")]
RSYNC = shutil.which("rsync")
assert RSYNC, "real rsync required"

def run_case(name, mode, expected):
    with tempfile.TemporaryDirectory(prefix="lf-rollback-") as tmp:
        root = Path(tmp)
        backup = root / "backup"
        repo = root / "repo" / "linkstack"
        repo.mkdir(parents=True)
        (repo / "old.php").write_bytes(b"new!")
        (repo / "added.php").write_bytes(b"added")
        (backup / "files.txt").parent.mkdir()
        (backup / "files.txt").write_text("old.php\nadded.php\n")
        stamp = 1700000000
        for label in ("dev", "prod"):
            dest = root / label
            dest.mkdir(mode=0o755)
            (backup / label).mkdir(mode=0o700)
            (backup / label / "old.php").write_bytes(b"old!")
            (dest / "old.php").write_bytes(b"new!")
            (dest / "added.php").write_bytes(b"added")
            for p in (backup / label / "old.php", dest / "old.php", repo / "old.php"):
                os.utime(p, (stamp, stamp))
            (backup / label / "old.php").chmod(0o640)
            (dest / "old.php").chmod(0o644)
            (backup / ("new-" + label + ".txt")).write_text("added.php\n")
        db = root / "prod" / "database.sqlite"
        with sqlite3.connect(db) as conn:
            conn.execute("create table t (id integer primary key)")
            conn.executemany("insert into t values (?)", [(1,), (2,)])
        (root / "prod" / ".env").write_text("synthetic\n")
        protected = [db, root / "prod" / ".env"]
        original = [p.read_bytes() for p in protected]
        if mode == "missing":
            (backup / "dev" / "old.php").unlink()
        env = dict(os.environ, ROOT=str(root), MODE_TEST=mode, REAL_RSYNC=RSYNC)
        setup = r'''set -Eeuo pipefail
backup="$ROOT/backup"; DEV="$ROOT/dev"; PROD="$ROOT/prod"; REPO="$ROOT/repo"
files=(old.php added.php)
runuser() {
  printf '%s\n' "$*" >> "$ROOT/cache.log"
  [[ "$MODE_TEST" != cache ]]
}
rsync() {
  if [[ "$MODE_TEST" == rsync-fail ]]; then return 23; fi
  if [[ "$MODE_TEST" == rsync-noop ]]; then return 0; fi
  "$REAL_RSYNC" "$@"
}
rm() {
  if [[ "$MODE_TEST" == remove-fail ]]; then return 1; fi
  command rm "$@"
}
'''
        action = 'deploy_started=true\nexit 42\n'
        if mode == "forward-mismatch":
            action = 'deploy_started=true\nprintf "bad!" > "$DEV/old.php"\nverify_published_files "$DEV"\nexit 99\n'
        elif mode == "no-deploy":
            action = 'exit 42\n'
        elif mode == "completed":
            action = 'deploy_started=true\ncompleted=true\nexit 0\n'
        result = subprocess.run(["bash", "-c", setup + FRAGMENT + action], env=env,
                                text=True, capture_output=True, timeout=30)
        assert result.returncode == expected, (name, result.returncode, result.stderr)
        if expected == 74:
            assert "ROLLBACK INCOMPLETO" in result.stderr and "ROLLBACK DE CODIGO VERIFICADO" not in result.stderr
        elif mode not in ("no-deploy", "completed"):
            assert "ROLLBACK DE CODIGO VERIFICADO" in result.stderr
            for label in ("dev", "prod"):
                dest = root / label
                assert (dest / "old.php").read_bytes() == b"old!"
                assert (dest / "old.php").stat().st_mode & 0o777 == 0o640
                assert not (dest / "added.php").exists()
                assert dest.stat().st_mode & 0o777 == 0o755
        else:
            assert (root / "prod" / "old.php").read_bytes() == b"new!"
            assert (root / "prod" / "added.php").exists()
        if mode not in ("no-deploy", "completed"):
            assert len((root / "cache.log").read_text().splitlines()) == 3
        assert [p.read_bytes() for p in protected] == original
        with sqlite3.connect(db) as conn:
            assert conn.execute("pragma quick_check").fetchone() == ("ok",)
            assert conn.execute("select id from t order by id").fetchall() == [(1,), (2,)]
        print("PASS:", name)

if __name__ == "__main__":
    for args in [
        ("same size/mtime restores both destinations, preserves status and DB", "normal", 42),
        ("rsync error is not suppressed", "rsync-fail", 74),
        ("rsync false success detected by byte comparison", "rsync-noop", 74),
        ("missing backup detected", "missing", 74),
        ("new file removal error detected", "remove-fail", 74),
        ("cache clear failure reported after all attempts", "cache", 74),
        ("forward mismatch triggers verified rollback", "forward-mismatch", 1),
        ("failure before publication does not restore", "no-deploy", 42),
        ("successful publication does not restore", "completed", 0),
    ]:
        run_case(*args)
    # The normal publication uses checksum copy on both destinations and checks
    # actual bytes before the cache clear and successful-SHA marker.
    assert 'rsync -ac --chmod=F644,D755 --chown=ubuntu:ubuntu' in SCRIPT
    assert 'rsync -ac --chmod=F644,D755 --no-owner --no-group' in SCRIPT
    assert SCRIPT.index('verify_published_files "$PROD"') < SCRIPT.rindex('printf \'%s\\n\' "$TARGET" > "$LAST_FILE"')
    print("PASS: publication checksum and verification ordering")
