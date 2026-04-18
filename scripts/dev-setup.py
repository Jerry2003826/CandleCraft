#!/usr/bin/env python3
"""Cross-platform setup/check script with team DB profile support."""

from __future__ import annotations

import argparse
import json
import os
import platform
import secrets
import shutil
import subprocess
import sys
import time
from pathlib import Path
from typing import Any
from typing import Mapping
from urllib.parse import quote

ROOT_DIR: Path = Path(__file__).resolve().parents[1]
DEFAULT_PROFILES_FILE: Path = ROOT_DIR / "scripts" / "db-profiles.local.json"
EXAMPLE_PROFILES_FILE: Path = ROOT_DIR / "scripts" / "db-profiles.example.json"
DEFAULTS: dict[str, Any] = {
    "db_host": "localhost",
    "db_port": 3306,
    "db_user": "root",
    "db_pass": "root",
    "db_name": "academy_management_db",
    "mysql_cmd": None,
    "app_host": "0.0.0.0",
    "app_port": 8765,
}


def print_step(message: str) -> None:
    """Print a formatted step message."""
    print(f"== {message} ==")


def mysql_fallback_paths() -> list[Path]:
    """Return common mysql executable paths for non-PATH installs."""
    system_name = platform.system().lower()
    if system_name == "windows":
        return [
            Path(r"C:\xampp\mysql\bin\mysql.exe"),
            Path(r"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe"),
            Path(r"C:\Program Files\MariaDB 10.11\bin\mysql.exe"),
        ]
    if system_name == "darwin":
        return [
            Path("/Applications/XAMPP/xamppfiles/bin/mysql"),
            Path("/usr/local/mysql/bin/mysql"),
        ]
    return [Path("/usr/bin/mysql"), Path("/usr/local/bin/mysql")]


def resolve_command(name: str) -> str | None:
    """Resolve command path from PATH or known fallback locations."""
    path_in_path = shutil.which(name)
    if path_in_path is not None:
        return path_in_path

    if name == "mysql":
        for fallback in mysql_fallback_paths():
            if fallback.exists():
                return str(fallback)

    return None


def require_command(name: str) -> str:
    """Ensure a command exists and return its executable path."""
    resolved = resolve_command(name)
    if resolved is not None:
        return resolved

    if name == "mysql":
        raise RuntimeError(
            "Required command not found: mysql. If you use XAMPP, provide "
            "--mysql-cmd or use scripts/onboard-teammate.py."
        )
    raise RuntimeError(f"Required command not found: {name}")


def run_command(
    args: list[str],
    *,
    env: Mapping[str, str] | None = None,
    input_bytes: bytes | None = None,
    check: bool = True,
    capture_output: bool = False,
) -> subprocess.CompletedProcess[bytes]:
    """Run subprocess command with optional stdin and environment."""
    return subprocess.run(
        args,
        input=input_bytes,
        env=dict(env) if env is not None else None,
        check=check,
        capture_output=capture_output,
    )


def mysql_args(mysql_cmd: str, host: str, port: int, user: str, password: str) -> list[str]:
    """Build mysql CLI arguments."""
    return [mysql_cmd, f"-h{host}", f"-P{port}", f"-u{user}", f"-p{password}"]


def best_effort_start_mysql() -> None:
    """Try to start local MySQL service when possible."""
    system_name = platform.system().lower()
    if system_name == "darwin" and shutil.which("brew"):
        print_step("Starting MySQL via Homebrew (best effort)")
        run_command(["brew", "services", "start", "mysql"], check=False, capture_output=True)
        return
    if system_name == "windows":
        print_step("Trying to start common MySQL Windows services (best effort)")
        for service_name in ("MySQL80", "MySQL", "MariaDB"):
            run_command(["sc", "start", service_name], check=False, capture_output=True)


def wait_for_mysql(
    mysql_cmd: str,
    host: str,
    port: int,
    user: str,
    password: str,
    retries: int = 20,
) -> None:
    """Wait until MySQL accepts connection or fail."""
    print_step("Waiting for MySQL connection")
    base_args = mysql_args(mysql_cmd, host, port, user, password)
    for attempt in range(1, retries + 1):
        result = run_command(base_args + ["-e", "SELECT 1;"], check=False, capture_output=True)
        if result.returncode == 0:
            return
        if attempt == retries:
            raise RuntimeError(f"Cannot connect to MySQL at {host}:{port} with user '{user}'.")
        time.sleep(1)


def check_project_root() -> None:
    """Validate script is executed from project root context."""
    os.chdir(ROOT_DIR)
    if not (ROOT_DIR / "composer.json").exists():
        raise RuntimeError("composer.json not found; script must run in project root.")


def ensure_app_local() -> None:
    """Ensure config/app_local.php exists."""
    target = ROOT_DIR / "config" / "app_local.php"
    source = ROOT_DIR / "config" / "app_local.example.php"
    if not target.exists():
        target.write_bytes(source.read_bytes())
    replace_salt_placeholder(target)


def replace_salt_placeholder(config_path: Path) -> None:
    """Replace __SALT__ placeholder with a random hex string when present."""
    content = config_path.read_text(encoding="utf-8")
    if "__SALT__" not in content:
        return

    config_path.write_text(content.replace("__SALT__", secrets.token_hex(32)), encoding="utf-8")


def import_sql_file(
    mysql_cmd: str,
    host: str,
    port: int,
    user: str,
    password: str,
    database: str,
    sql_path: Path,
) -> None:
    """Import one SQL file into target database."""
    run_command(
        mysql_args(mysql_cmd, host, port, user, password) + [database],
        input_bytes=sql_path.read_bytes(),
    )


def create_database(
    mysql_cmd: str,
    host: str,
    port: int,
    user: str,
    password: str,
    database: str,
) -> None:
    """Create database if it does not exist."""
    sql = (
        f"CREATE DATABASE IF NOT EXISTS `{database}` "
        "CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    ).encode("utf-8")
    run_command(mysql_args(mysql_cmd, host, port, user, password), input_bytes=sql)


def verify_admin_account(
    mysql_cmd: str,
    host: str,
    port: int,
    user: str,
    password: str,
    database: str,
) -> None:
    """Print seeded admin user row for validation."""
    query = (
        "SELECT user_id,email,user_role,account_status "
        "FROM users WHERE email='admin@candlecraft.com';"
    ).encode("utf-8")
    run_command(mysql_args(mysql_cmd, host, port, user, password) + [database], input_bytes=query)


def build_runtime_env(config: Mapping[str, Any], admin_seed_password: str) -> dict[str, str]:
    """Build environment variables used by CakePHP CLI commands."""
    env = os.environ.copy()
    env["DATABASE_URL"] = build_database_url(
        str(config["db_host"]),
        int(config["db_port"]),
        str(config["db_user"]),
        str(config["db_pass"]),
        str(config["db_name"]),
    )
    env["ADMIN_SEED_PASSWORD"] = admin_seed_password

    return env


def build_database_url(host: str, port: int, user: str, password: str, database: str) -> str:
    """Build DATABASE_URL with URL-encoded credentials."""
    return (
        f"mysql://{quote(user, safe='')}:{quote(password, safe='')}@"
        f"{host}:{port}/{database}?encoding=utf8mb4&timezone=UTC"
    )


def read_json_file(path: Path) -> dict[str, Any]:
    """Read JSON file and return dictionary."""
    try:
        data = json.loads(path.read_text(encoding="utf-8"))
    except json.JSONDecodeError as error:
        raise RuntimeError(f"Invalid JSON file: {path}") from error
    if not isinstance(data, dict):
        raise RuntimeError(f"JSON root must be object: {path}")
    return data


def parse_env_int(var_name: str) -> int | None:
    """Read integer from environment variable."""
    value = os.getenv(var_name)
    if value is None or value == "":
        return None
    try:
        return int(value)
    except ValueError as error:
        raise RuntimeError(f"Environment variable {var_name} must be an integer.") from error


def resolve_profiles_file_path(path_value: str | None) -> Path:
    """Resolve profile file path from CLI or default."""
    if path_value is None:
        return DEFAULT_PROFILES_FILE
    candidate = Path(path_value)
    return candidate if candidate.is_absolute() else ROOT_DIR / candidate


def resolve_runtime_config(args: argparse.Namespace) -> dict[str, Any]:
    """Resolve config with precedence: CLI > ENV > profile > defaults."""
    config: dict[str, Any] = dict(DEFAULTS)
    profiles_path = resolve_profiles_file_path(args.profiles_file)
    profile_name = args.profile if args.profile is not None else os.getenv("DB_PROFILE")

    if profile_name and profiles_path.exists():
        all_profiles = read_json_file(profiles_path)
        profile_value = all_profiles.get(profile_name)
        if not isinstance(profile_value, dict):
            raise RuntimeError(f"Profile '{profile_name}' not found in {profiles_path}.")
        for key in DEFAULTS:
            if key in profile_value:
                config[key] = profile_value[key]

    env_mapping: dict[str, str] = {
        "DB_HOST": "db_host",
        "DB_USER": "db_user",
        "DB_PASS": "db_pass",
        "DB_NAME": "db_name",
        "MYSQL_CMD": "mysql_cmd",
        "APP_HOST": "app_host",
    }
    for env_name, config_key in env_mapping.items():
        env_value = os.getenv(env_name)
        if env_value is not None and env_value != "":
            config[config_key] = env_value

    db_port_env = parse_env_int("DB_PORT")
    if db_port_env is not None:
        config["db_port"] = db_port_env
    app_port_env = parse_env_int("APP_PORT")
    if app_port_env is not None:
        config["app_port"] = app_port_env

    cli_mapping: dict[str, str] = {
        "db_host": "db_host",
        "db_user": "db_user",
        "db_pass": "db_pass",
        "db_name": "db_name",
        "mysql_cmd": "mysql_cmd",
        "app_host": "app_host",
    }
    for arg_key, config_key in cli_mapping.items():
        arg_value = getattr(args, arg_key)
        if arg_value is not None:
            config[config_key] = arg_value

    if args.db_port is not None:
        config["db_port"] = args.db_port
    if args.app_port is not None:
        config["app_port"] = args.app_port

    config["profile_name"] = profile_name
    config["profiles_path"] = str(profiles_path)
    return config


def get_mysql_command(config: Mapping[str, Any]) -> str:
    """Resolve mysql command from config override or environment detection."""
    mysql_cmd_value = config.get("mysql_cmd")
    if isinstance(mysql_cmd_value, str) and mysql_cmd_value:
        mysql_path = Path(mysql_cmd_value)
        if not mysql_path.exists():
            fallback = resolve_command("mysql")
            if fallback is not None:
                return fallback
            raise RuntimeError(
                f"MySQL command not found at: {mysql_cmd_value}. "
                "Please update profile mysql_cmd or install mysql client in PATH."
            )
        return str(mysql_path)
    return require_command("mysql")


def print_effective_config(config: Mapping[str, Any], mysql_cmd: str) -> None:
    """Print selected runtime configuration for troubleshooting."""
    print_step("Effective database config")
    print(f"Profile: {config.get('profile_name') or '(none)'}")
    print(f"MySQL command: {mysql_cmd}")
    print(f"Host: {config['db_host']}  Port: {config['db_port']}")
    print(f"User: {config['db_user']}  Database: {config['db_name']}")


def check_env(args: argparse.Namespace) -> None:
    """Run preflight checks without modifying database."""
    check_project_root()
    config = resolve_runtime_config(args)
    print_step("Checking required commands")
    require_command("php")
    require_command("composer")
    mysql_cmd = get_mysql_command(config)
    print_effective_config(config, mysql_cmd)

    best_effort_start_mysql()
    wait_for_mysql(
        mysql_cmd,
        str(config["db_host"]),
        int(config["db_port"]),
        str(config["db_user"]),
        str(config["db_pass"]),
    )

    print_step("Checking core files")
    required_files = [
        ROOT_DIR / "config" / "Migrations",
        ROOT_DIR / "config" / "Seeds" / "AdminSeed.php",
        ROOT_DIR / "bin" / "cake",
    ]
    for file_path in required_files:
        if not file_path.exists():
            raise RuntimeError(f"Required file missing: {file_path}")

    if not resolve_profiles_file_path(args.profiles_file).exists():
        print_step("Tip")
        print(f"Create local profiles file from template: {EXAMPLE_PROFILES_FILE.name}")

    print_step("Environment check passed")
    print("Run next: python scripts/dev-setup.py setup-run")


def setup_run(args: argparse.Namespace) -> None:
    """Install dependencies, initialize DB, and run app server."""
    check_project_root()
    config = resolve_runtime_config(args)
    print_step("Checking required commands")
    require_command("php")
    require_command("composer")
    mysql_cmd = get_mysql_command(config)
    print_effective_config(config, mysql_cmd)

    best_effort_start_mysql()
    wait_for_mysql(
        mysql_cmd,
        str(config["db_host"]),
        int(config["db_port"]),
        str(config["db_user"]),
        str(config["db_pass"]),
    )

    print_step("Installing dependencies")
    run_command(["composer", "install"])

    print_step("Ensuring local config file")
    ensure_app_local()

    print_step("Creating database")
    create_database(
        mysql_cmd,
        str(config["db_host"]),
        int(config["db_port"]),
        str(config["db_user"]),
        str(config["db_pass"]),
        str(config["db_name"]),
    )

    admin_seed_password = os.getenv("ADMIN_SEED_PASSWORD", "admin123")
    env = build_runtime_env(config, admin_seed_password)

    print_step("Running database migrations")
    run_command(["bin/cake", "migrations", "migrate"], env=env)

    print_step("Running admin seed")
    run_command(["bin/cake", "seeds", "run", "AdminSeed", "-q"], env=env)

    print_step("Verifying demo admin account")
    verify_admin_account(
        mysql_cmd,
        str(config["db_host"]),
        int(config["db_port"]),
        str(config["db_user"]),
        str(config["db_pass"]),
        str(config["db_name"]),
    )

    print_step("Starting CakePHP server")
    print(f"URL: http://localhost:{config['app_port']}")
    print(f"Local demo login: admin@candlecraft.com / {admin_seed_password}")
    run_command(
        ["bin/cake", "server", "-H", str(config["app_host"]), "-p", str(config["app_port"])],
        env=env,
    )


def parse_args(argv: list[str]) -> argparse.Namespace:
    """Parse command-line arguments."""
    parser = argparse.ArgumentParser(
        description="Cross-platform setup/check script for CandleCraft Academy."
    )
    subparsers = parser.add_subparsers(dest="command", required=True)

    def add_common_options(subparser: argparse.ArgumentParser) -> None:
        subparser.add_argument("--profile", default=None, help="DB profile name.")
        subparser.add_argument(
            "--profiles-file",
            default=None,
            help="Path to JSON profiles file (default: scripts/db-profiles.local.json).",
        )
        subparser.add_argument("--db-host", default=None)
        subparser.add_argument("--db-port", type=int, default=None)
        subparser.add_argument("--db-user", default=None)
        subparser.add_argument("--db-pass", default=None)
        subparser.add_argument("--db-name", default=None)
        subparser.add_argument("--mysql-cmd", default=None, help="Full path to mysql client.")
        subparser.add_argument("--app-host", default=None)
        subparser.add_argument("--app-port", type=int, default=None)

    check_parser = subparsers.add_parser(
        "check-env",
        help="Validate local environment and connectivity.",
    )
    add_common_options(check_parser)

    setup_parser = subparsers.add_parser(
        "setup-run",
        help="Install dependencies, init DB with demo data, then run server.",
    )
    add_common_options(setup_parser)

    return parser.parse_args(argv)


def main(argv: list[str]) -> int:
    """Run script entrypoint."""
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
