# =============================================================================
#  CandleCraft Academy - class reminder Task Scheduler wrapper (Windows)
# -----------------------------------------------------------------------------
#  Equivalent of scripts/send-class-reminders.sh, designed for Windows Task
#  Scheduler. cd's into the project root, optionally loads .env, runs
#  bin\cake.bat send_class_reminders, and appends the output to
#  logs\cron-class-reminders.log.
#
#  Recommended schedule: every 30 minutes (matches default --window-span 60m
#  so every booking is offered exactly one reminder).
#
#  To register with Task Scheduler:
#    schtasks /Create /SC MINUTE /MO 30 /TN "CandleCraft Class Reminders" `
#      /TR "powershell -ExecutionPolicy Bypass -File C:\path\to\scripts\send-class-reminders.ps1"
#
#  Pass -DryRun to preview without sending anything.
# =============================================================================
[CmdletBinding()]
param(
    [switch]$DryRun,
    [int]$WindowHours = 24,
    [int]$WindowSpan = 60
)

$ErrorActionPreference = 'Stop'

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir

Set-Location $ProjectRoot

# Load .env if present, so DATABASE_URL / EMAIL_* are visible to PHP CLI.
$envFile = Join-Path $ProjectRoot '.env'
if (Test-Path $envFile) {
    Get-Content $envFile | ForEach-Object {
        if ($_ -match '^\s*([A-Z_][A-Z0-9_]*)\s*=\s*(.*)$') {
            $name = $Matches[1]
            $value = $Matches[2].Trim('"').Trim("'")
            [System.Environment]::SetEnvironmentVariable($name, $value, 'Process')
        }
    }
}

$logDir = Join-Path $ProjectRoot 'logs'
if (-not (Test-Path $logDir)) {
    New-Item -ItemType Directory -Path $logDir | Out-Null
}
$logFile = Join-Path $logDir 'cron-class-reminders.log'

$ts = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'

$cakeArgs = @('send_class_reminders', '--window-hours', $WindowHours, '--window-span', $WindowSpan)
if ($DryRun) {
    $cakeArgs += '--dry-run'
}

"[${ts}] === send_class_reminders run ===" | Out-File -FilePath $logFile -Append -Encoding utf8
try {
    & (Join-Path $ProjectRoot 'bin\cake.bat') @cakeArgs *>&1 | Out-File -FilePath $logFile -Append -Encoding utf8
    $exitCode = $LASTEXITCODE
} catch {
    "[${ts}] FAILED: $_" | Out-File -FilePath $logFile -Append -Encoding utf8
    $exitCode = 1
}
"[${ts}] === run complete (exit=$exitCode) ===" | Out-File -FilePath $logFile -Append -Encoding utf8
"" | Out-File -FilePath $logFile -Append -Encoding utf8

exit $exitCode
