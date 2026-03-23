#!/usr/bin/env python3
"""One-command teammate setup runner with interactive guidance."""

from __future__ import annotations

import json
import os
import platform
import subprocess
import sys
from pathlib import Path
from typing import Any

ROOT_DIR: Path = Path(__file__).resolve().parents[1]
LOCAL_PROFILES: Path = ROOT_DIR / "scripts" / "db-profiles.local.json"
DEV_SETUP_SCRIPT: Path = ROOT_DIR / "scripts" / "dev-setup.py"


def prompt_text(message: str, default: str) -> str:
    """Prompt text input with default fallback."""
    value: str = input(f"{message} [{default}]: ").strip()
    return value if value else default


def prompt_yes_no(message: str, default_yes: bool = True) -> bool:
    """Prompt for yes/no answer and return boolean."""
    default_hint: str = "Y/n" if default_yes else "y/N"
    answer: str = input(f"{message} ({default_hint}): ").strip().lower()
    if answer == "":
        return default_yes
    return answer in {"y", "yes"}


def guess_mysql_cmd() -> str:
    """Guess mysql executable path based on operating system."""
    system_name: str = platform.system().lower()
    if system_name == "windows":
        return r"C:\xampp\mysql\bin\mysql.exe"
    if system_name == "darwin":
        return "/Applications/XAMPP/xamppfiles/bin/mysql"
    return "mysql"


def read_profiles() -> dict[str, Any]:
    """Load local profiles JSON file or return empty object."""
    if not LOCAL_PROFILES.exists():
        return {}
    data: Any = json.loads(LOCAL_PROFILES.read_text(encoding="utf-8"))
    if not isinstance(data, dict):
        raise RuntimeError("Invalid profiles file format; expected JSON object.")
    return data


def write_profiles(profiles: dict[str, Any]) -> None:
    """Persist profiles JSON to local file."""
    LOCAL_PROFILES.write_text(
        json.dumps(profiles, ensure_ascii=True, indent=2) + "\n",
        encoding="utf-8",
    )


def select_or_create_profile(profiles: dict[str, Any]) -> str:
    """Select existing profile or create a new one interactively."""
    if profiles:
        print("\nExisting local profiles:")
        profile_names: list[str] = sorted(profiles.keys())
        for idx, name in enumerate(profile_names, start=1):
            print(f"  {idx}. {name}")
        if prompt_yes_no("Use an existing profile", default_yes=True):
            choice: str = prompt_text("Choose number", "1")
            try:
                selected_idx: int = int(choice)
            except ValueError as error:
                raise RuntimeError("Invalid profile selection.") from error
            if selected_idx < 1 or selected_idx > len(profile_names):
                raise RuntimeError("Profile index out of range.")
            return profile_names[selected_idx - 1]

    default_profile: str = (
        os.getenv("USER")
        or os.getenv("USERNAME")
        or "teammate"
    )
    profile_name: str = prompt_text("\nCreate profile name", default_profile)
    db_host: str = prompt_text("Database host", "localhost")
    db_port_text: str = prompt_text("Database port", "3306")
    db_user: str = prompt_text("Database user", "root")
    db_pass: str = prompt_text("Database password (blank allowed)", "")
    db_name: str = prompt_text("Database name", "academy_management_db")
    mysql_cmd: str = prompt_text("MySQL command path", guess_mysql_cmd())

    try:
        db_port: int = int(db_port_text)
    except ValueError as error:
        raise RuntimeError("Database port must be an integer.") from error

    profiles[profile_name] = {
        "db_host": db_host,
        "db_port": db_port,
        "db_user": db_user,
        "db_pass": db_pass,
        "db_name": db_name,
        "mysql_cmd": mysql_cmd,
    }
    write_profiles(profiles)
    print(f"\nSaved local profile: {profile_name}")
    print(f"Profile file: {LOCAL_PROFILES}")
    return profile_name


def run_dev_setup(command: str, profile_name: str) -> int:
    """Run dev-setup subcommand with selected profile."""
    cmd: list[str] = [
        sys.executable,
        str(DEV_SETUP_SCRIPT),
        command,
        "--profile",
        profile_name,
    ]
    process: subprocess.CompletedProcess[bytes] = subprocess.run(cmd, check=False)
    return process.returncode


def main() -> int:
    """Run guided all-in-one setup flow."""
    os.chdir(ROOT_DIR)
    if not (ROOT_DIR / "composer.json").exists():
        print("Error: Please run this from project root.", file=sys.stderr)
        return 1
    if not DEV_SETUP_SCRIPT.exists():
        print("Error: scripts/dev-setup.py not found.", file=sys.stderr)
        return 1

    print("=== CandleCraft One-Command Setup ===")
    print("This will select/create your local DB profile and run setup.\n")

    try:
        profiles: dict[str, Any] = read_profiles()
        profile_name: str = select_or_create_profile(profiles)
    except RuntimeError as error:
        print(f"Error: {error}", file=sys.stderr)
        return 1

    print("\n== Running environment check ==")
    check_code: int = run_dev_setup("check-env", profile_name)
    if check_code != 0:
        print("\nEnvironment check failed. Fix the error and run again.", file=sys.stderr)
        return check_code

    print("\n== Running full setup and start ==")
    return run_dev_setup("setup-run", profile_name)


if __name__ == "__main__":
    raise SystemExit(main())
