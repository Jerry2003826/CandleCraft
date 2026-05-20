# Automated Class Reminders

CandleCraft Academy ships with an opt-in class reminder pipeline. Once a
booking is confirmed, the system will automatically email the customer (and
drop a matching in-app notification) before the class starts.

This document explains how the pieces fit together and how to wire it up so
reminders go out without anyone having to push a button.

## What runs

1. `bin/cake send_class_reminders` — CakePHP CLI command.
   - Looks for bookings that are `confirmed` or `completed`, have not yet
     been reminded (`bookings.reminder_sent_at IS NULL`), and whose class
     starts inside the configured window (default 24 hours from now).
   - For each match: sends `templates/email/html/class_reminder.php` via the
     mailer profile `default`, creates a `notifications` row of type
     `class_reminder`, and stamps `reminder_sent_at`.
2. `scripts/send-class-reminders.sh` — bash wrapper for cron / launchd.
3. `scripts/send-class-reminders.ps1` — PowerShell wrapper for Windows
   Task Scheduler.

## CLI options

```text
bin/cake send_class_reminders [options]

  --window-hours, -w   Lead time in hours before class start (default: 24)
  --window-span,  -s   Width of the reminder window in minutes (default: 60)
  --dry-run            Print which bookings would receive a reminder
                       without sending or persisting anything
  --limit              Hard cap on reminders per run (default: 500)
```

The `--window-span` value should be **>= the cron interval** so every
upcoming class is matched at least once. Default `--window-span 60` paired
with a 30-minute schedule gives every booking exactly one reminder.

### Useful examples

```bash
# Preview without sending
bin/cake send_class_reminders --dry-run

# Send a "starts in 2 hours" reminder series instead of 24h
bin/cake send_class_reminders --window-hours 2 --window-span 60

# Same-day reminder pass at 7:00 a.m. for everything happening today
bin/cake send_class_reminders --window-hours 0 --window-span 1440
```

## Email transport

The mailer uses the profile `default` defined in `config/app.php`. By
default it falls back to PHP's `mail()` function so the command works on a
fresh box, but you can flip it to SMTP at any time by setting any one of:

```text
EMAIL_TRANSPORT_DEFAULT_URL=smtps://username:password@smtp.example.com:465
# or
EMAIL_SMTP_HOST=smtp.example.com
EMAIL_SMTP_PORT=465
EMAIL_SMTP_USERNAME=postmaster@example.com
EMAIL_SMTP_PASSWORD=...
EMAIL_SMTP_TLS=true
EMAIL_FROM_ADDRESS=hello@candlecraft.com
EMAIL_FROM_NAME="CandleCraft Academy"
```

These environment variables can live in a `.env` file at the project root —
both wrapper scripts will source it automatically.

## Scheduling

### macOS / Linux (cron)

```cron
# every 30 minutes, send the reminder pass for classes starting in ~24h
*/30 * * * * /Users/you/Downloads/cakephp-app/scripts/send-class-reminders.sh
```

If you ever want to test the wiring without spamming your customers:

```cron
*/30 * * * * /Users/you/Downloads/cakephp-app/scripts/send-class-reminders.sh --dry-run
```

Output for every run is appended to `logs/cron-class-reminders.log`.

### macOS (launchd)

Save as `~/Library/LaunchAgents/au.candlecraft.classReminders.plist` and
`launchctl load` it.

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>Label</key>
    <string>au.candlecraft.classReminders</string>
    <key>ProgramArguments</key>
    <array>
        <string>/Users/you/Downloads/cakephp-app/scripts/send-class-reminders.sh</string>
    </array>
    <key>StartInterval</key>
    <integer>1800</integer>
    <key>RunAtLoad</key>
    <false/>
</dict>
</plist>
```

### Windows (Task Scheduler)

```powershell
schtasks /Create /SC MINUTE /MO 30 /TN "CandleCraft Class Reminders" `
  /TR "powershell -ExecutionPolicy Bypass -File C:\path\to\cakephp-app\scripts\send-class-reminders.ps1"
```

To delete it later: `schtasks /Delete /TN "CandleCraft Class Reminders"`.

## Verifying it works

```bash
# 1. Confirm at least one booking is in the window
bin/cake send_class_reminders --dry-run

# 2. Drain the queue once (real send)
bin/cake send_class_reminders

# 3. Inspect the most recent run
tail -n 40 logs/cron-class-reminders.log
```

The customer should now have:
- a class reminder email (mobile-friendly HTML + plain-text fallback), and
- a `Class Reminder` notification visible in the customer portal.

Subsequent runs will skip that booking because `reminder_sent_at` is now
populated, so it is safe to schedule the cron aggressively.
