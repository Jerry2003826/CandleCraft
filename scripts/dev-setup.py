#!/usr/bin/env python3
"""Cross-platform environment bootstrap and run helper for this project."""

from __future__ import annotations

import argparse
import os
import platform
import shutil
import subprocess
import sys
import time
from pathlib import Path
from typing import Mapping
from urllib.parse import quote


ROOT_DIR: Path = Path(__file__).resolve().parents[1]


def print_step(message: str) -> None:
    """Print a formatted step message."""
    print(f"== {message} ==")


def require_command(name: str) -> None:
    """Ensure a command exists in PATH."""
    if shutil.which(name) is None:
        raise RuntimeError(f"Required command not found: {name}")


def run_command(
    args: list[str],
    *,
    env: Mapping[str, str] | None = None,
    input_bytes: bytes | None = None,
    check: bool = True,
    capture_output: bool = False,
) -> subprocess.CompletedProcess[bytes]:
    """Run a subprocess command with optional stdin and env override."""
    return subprocess.run(
        args,
        input=input_bytes,
        env=dict(env) if env is not None else None,
        check=check,
        capture_output=capture_output,
    )


def mysql_args(host: str, port: int, user: str, password: str) -> list[str]:
    """Build base mysql client argument list."""
    return [
        "mysql",
        f"-h{host}",
        f"-P{port}",
        f"-u{user}",
        f"-p{password}",
    ]


def best_effort_start_mysql() -> None:
    """Try to start local MySQL service when possible."""
    system_name: str = platform.system().lower()
    if system_name == "darwin" and shutil.which("brew"):
        print_step("Starting MySQL via Homebrew (best effort)")
        run_command(["brew", "services", "start", "mysql"], check=False, capture_output=True)
        return

    if system_name == "windows":
        print_step("Trying to start common MySQL Windows services (best effort)")
        for service_name in ("MySQL80", "MySQL", "MariaDB"):
            run_command(["sc", "start", service_name], check=False, capture_output=True)


def wait_for_mysql(host: str, port: int, user: str, password: str, retries: int = 20) -> None:
    """Wait until MySQL accepts connection or fail."""
    print_step("Waiting for MySQL connection")
    base_args = mysql_args(host, port, user, password)
    for attempt in range(1, retries + 1):
        result = run_command(base_args + ["-e", "SELECT 1;"], check=False, capture_output=True)
        if result.returncode == 0:
            return
        if attempt == retries:
            raise RuntimeError(
                f"Cannot connect to MySQL at {host}:{port} with user '{user}'."
            )
        time.sleep(1)


def check_project_root() -> None:
    """Validate script is executed in project root context."""
    os.chdir(ROOT_DIR)
    if not (ROOT_DIR / "composer.json").exists():
        raise RuntimeError("composer.json not found; script must run in project root.")


def ensure_app_local() -> None:
    """Ensure config/app_local.php exists."""
    target = ROOT_DIR / "config" / "app_local.php"
    source = ROOT_DIR / "config" / "app_local.example.php"
    if not target.exists():
        target.write_bytes(source.read_bytes())


def import_sql_file(host: str, port: int, user: str, password: str, database: str, sql_path: Path) -> None:
    """Import one SQL file into target database via mysql stdin."""
    sql_bytes = sql_path.read_bytes()
    run_command(mysql_args(host, port, user, password) + [database], input_bytes=sql_bytes)


def create_database(host: str, port: int, user: str, password: str, database: str) -> None:
    """Create database if it does not exist."""
    create_sql = (
        f"CREATE DATABASE IF NOT EXISTS `{database}` "
        "CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    ).encode("utf-8")
    run_command(mysql_args(host, port, user, password), input_bytes=create_sql)


def verify_admin_account(host: str, port: int, user: str, password: str, database: str) -> None:
    """Print demo admin row to confirm seed data import."""
    query = (
        "SELECT user_id,email,user_role,account_status "
        "FROM users WHERE email='admin@candlecraft.com';"
    ).encode("utf-8")
    run_command(mysql_args(host, port, user, password) + [database], input_bytes=query)


def build_database_url(host: str, port: int, user: str, password: str, database: str) -> str:
    """Build DATABASE_URL with URL-encoded credentials."""
    return (
        f"mysql://{quote(user, safe='')}:{quote(password, safe='')}@"
        f"{host}:{port}/{database}?encoding=utf8mb4&timezone=UTC"
    )


def check_env(args: argparse.Namespace) -> None:
    """Run preflight checks without modifying database."""
    check_project_root()
    print_step("Checking required commands")
    require_command("php")
    require_command("composer")
    require_command("mysql")

    best_effort_start_mysql()
    wait_for_mysql(args.db_host, args.db_port, args.db_user, args.db_pass)

    print_step("Checking core files")
    required_files = [
        ROOT_DIR / "config" / "schema" / "academy_management_db.sql",
        ROOT_DIR / "config" / "schema" / "seed_admin.sql",
        ROOT_DIR / "bin" / "cake",
    ]
    for file_path in required_files:
        if not file_path.exists():
            raise RuntimeError(f"Required file missing: {file_path}")

    print_step("Environment check passed")
    print("You can now run: python scripts/dev-setup.py setup-run")


def setup_run(args: argparse.Namespace) -> None:
    """Install dependencies, initialize DB, and run app server."""
    check_project_root()
    print_step("Checking required commands")
    require_command("php")
    require_command("composer")
    require_command("mysql")

    best_effort_start_mysql()
    wait_for_mysql(args.db_host, args.db_port, args.db_user, args.db_pass)

    print_step("Installing dependencies")
    run_command(["composer", "install"])

    print_step("Creating database")
    create_database(args.db_host, args.db_port, args.db_user, args.db_pass, args.db_name)

    print_step("Importing schema")
    import_sql_file(
        args.db_host,
        args.db_port,
        args.db_user,
        args.db_pass,
        args.db_name,
        ROOT_DIR / "config" / "schema" / "academy_management_db.sql",
    )

    print_step("Importing demo seed data")
    import_sql_file(
        args.db_host,
        args.db_port,
        args.db_user,
        args.db_pass,
        args.db_name,
        ROOT_DIR / "config" / "schema" / "seed_admin.sql",
    )

    print_step("Ensuring local config file")
    ensure_app_local()

    print_step("Verifying demo admin account")
    verify_admin_account(args.db_host, args.db_port, args.db_user, args.db_pass, args.db_name)

    database_url = build_database_url(
        args.db_host, args.db_port, args.db_user, args.db_pass, args.db_name
    )
    env = os.environ.copy()
    env["DATABASE_URL"] = database_url

    print_step("Starting CakePHP server")
    print(f"URL: http://localhost:{args.app_port}")
    print("Login: admin@candlecraft.com / admin123")
    run_command(
        ["bin/cake", "server", "-H", args.app_host, "-p", str(args.app_port)],
        env=env,
    )


def parse_args(argv: list[str]) -> argparse.Namespace:
    """Parse CLI arguments for subcommands and shared DB options."""
    parser = argparse.ArgumentParser(
        description="Cross-platform setup/check script for CandleCraft Academy."
    )
    subparsers = parser.add_subparsers(dest="command", required=True)

    def add_common_options(subparser: argparse.ArgumentParser) -> None:
        subparser.add_argument("--db-host", default="localhost")
        subparser.add_argument("--db-port", type=int, default=3306)
        subparser.add_argument("--db-user", default="root")
        subparser.add_argument("--db-pass", default="root")
        subparser.add_argument("--db-name", default="academy_management_db")
        subparser.add_argument("--app-host", default="0.0.0.0")
        subparser.add_argument("--app-port", type=int, default=8765)

    check_parser = subparsers.add_parser(
        "check-env",
        help="Validate local environment and connectivity.",
    )
    add_common_options(check_parser)

    setup_parser = subparsers.add_parser(
        "setup-run",
        help="Install deps, init DB with demo data, then run server.",
    )
    add_common_options(setup_parser)

    return parser.parse_args(argv)


def main(argv: list[str]) -> int:
    """Program entrypoint."""
    args = parse_args(argv)
    try:
        if args.command == "check-env":
            check_env(args)
        elif args.command == "setup-run":
            setup_run(args)
        else:
            raise RuntimeError(f"Unsupported command: {args.command}")
    except RuntimeError as error:
        print(f"Error: {error}", file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    raise SystemExit(main(sys.argv[1:]))
