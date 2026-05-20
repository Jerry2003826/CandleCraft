#!/usr/bin/env bash
# ============================================================================
#  CandleCraft Academy — class reminder cron wrapper
# ----------------------------------------------------------------------------
#  This script is meant to be invoked from cron / launchd / Windows
#  Task Scheduler. It cd's into the project root, loads any local env vars
#  (DATABASE_URL, EMAIL_*, etc.) from `.env` if present, runs the CakePHP
#  reminder command, and appends the output to logs/cron.log.
#
#  Recommended schedule: every 30 minutes (matches the default --window-span
#  of 60 minutes so every booking is offered exactly one reminder).
#
#  Example crontab entry (Linux / macOS):
#    */30 * * * * /full/path/to/scripts/send-class-reminders.sh
#
#  Example launchd plist label: au.candlecraft.classReminders
#  Example Windows Task Scheduler: trigger "Daily, repeat every 30m for 24h"
#  pointing at  bin\cake.bat send_class_reminders
#
#  Pass --dry-run to preview without sending anything.
# ============================================================================
set -euo pipefail

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
PROJECT_ROOT="$( cd "$SCRIPT_DIR/.." >/dev/null 2>&1 && pwd )"

cd "$PROJECT_ROOT"

# Load environment variables from .env if it exists (so DATABASE_URL,
# EMAIL_SMTP_HOST etc. are available to the CakePHP CLI).
if [ -f ".env" ]; then
    set -o allexport
    # shellcheck disable=SC1091
    . ./.env
    set +o allexport
fi

LOG_DIR="$PROJECT_ROOT/logs"
mkdir -p "$LOG_DIR"
LOG_FILE="$LOG_DIR/cron-class-reminders.log"

TS="$(date '+%Y-%m-%d %H:%M:%S')"

{
    echo "[$TS] === send_class_reminders run ==="
    php "$PROJECT_ROOT/bin/cake.php" send_class_reminders "$@" 2>&1
    echo "[$TS] === run complete (exit=$?) ==="
    echo ""
} >> "$LOG_FILE"
