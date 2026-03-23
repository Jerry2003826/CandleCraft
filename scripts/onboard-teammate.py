#!/usr/bin/env python3
"""Interactive teammate onboarding for local database profile setup."""

from __future__ import annotations

import json
import os
import platform
from getpass import getpass
from pathlib import Path
from typing import Any

ROOT_DIR: Path = Path(__file__).resolve().parents[1]
LOCAL_PROFILES: Path = ROOT_DIR / "scripts" / "db-profiles.local.json"


def read_profiles(path: Path) -> dict[str, Any]:
    """Read existing profile JSON, or return empty object."""
    if not path.exists():
        return {}
    data = json.loads(path.read_text(encoding="utf-8"))
    if not isinstance(data, dict):
        raise RuntimeError(f"Invalid profile format in {path}")
    return data


def default_mysql_cmd() -> str:
    """Return likely mysql command path by operating system."""
    system_name = platform.system().lower()
    if system_name == "windows":
        return r"C:\xampp\mysql\bin\mysql.exe"
    if system_name == "darwin":
        return "/Applications/XAMPP/xamppfiles/bin/mysql"
    return "mysql"


def prompt_text(message: str, default: str) -> str:
    """Prompt with default and return non-empty string."""
    value = input(f"{message} [{default}]: ").strip()
    return value if value else default


def prompt_password(message: str, default: str) -> str:
    """Prompt password with default fallback when blank."""
    value = getpass(f"{message} [{default}]: ").strip()
    return value if value else default


def main() -> int:
    """Run interactive onboarding and print next commands."""
    os.chdir(ROOT_DIR)
    print("=== CandleCraft teammate onboarding ===")
    print("This script writes local DB config to scripts/db-profiles.local.json")
    print("File is local-only and should not be committed.\n")

    default_profile = (
        os.getenv("USER")
        or os.getenv("USERNAME")
        or "teammate"
    )
    profile_name = prompt_text("Profile name", default_profile)
    db_host = prompt_text("Database host", "localhost")
    db_port = int(prompt_text("Database port", "3306"))
    db_user = prompt_text("Database user", "root")
    db_pass = prompt_password("Database password (blank allowed)", "")
    db_name = prompt_text("Database name", "academy_management_db")
    mysql_cmd = prompt_text("MySQL command path", default_mysql_cmd())

    profiles = read_profiles(LOCAL_PROFILES)
    profiles[profile_name] = {
        "db_host": db_host,
        "db_port": db_port,
        "db_user": db_user,
        "db_pass": db_pass,
        "db_name": db_name,
        "mysql_cmd": mysql_cmd,
    }

    LOCAL_PROFILES.write_text(
        json.dumps(profiles, ensure_ascii=True, indent=2) + "\n",
        encoding="utf-8",
    )

    print("\nSaved profile successfully.")
    print(f"Profile file: {LOCAL_PROFILES}")
    print(f"Profile name: {profile_name}\n")
    print("Next steps:")
    print(f"  python3 scripts/dev-setup.py check-env --profile {profile_name}")
    print(f"  python3 scripts/dev-setup.py setup-run --profile {profile_name}")
    print("Windows PowerShell:")
    print(f"  py .\\scripts\\dev-setup.py check-env --profile {profile_name}")
    print(f"  py .\\scripts\\dev-setup.py setup-run --profile {profile_name}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
