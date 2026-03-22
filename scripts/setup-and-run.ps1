Param(
    [string]$DbHost = "localhost",
    [int]$DbPort = 3306,
    [string]$DbUser = "root",
    [string]$DbPass = "root",
    [string]$DbName = "academy_management_db",
    [string]$AppHost = "0.0.0.0",
    [int]$AppPort = 8765
)

$ErrorActionPreference = "Stop"

function Require-Command {
    param([string]$Name)
    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "Required command not found: $Name"
    }
}

Write-Host "== Checking required tools =="
Require-Command php
Require-Command composer
Require-Command mysql

if (-not (Test-Path "composer.json")) {
    throw "Please run this script from project root (composer.json not found)."
}

Write-Host "== Waiting for MySQL to be reachable =="
$connected = $false
for ($i = 1; $i -le 20; $i++) {
    try {
        & mysql -h $DbHost -P $DbPort -u $DbUser "-p$DbPass" -e "SELECT 1;" | Out-Null
        $connected = $true
        break
    } catch {
        Start-Sleep -Seconds 1
    }
}
if (-not $connected) {
    throw "Cannot connect to MySQL at ${DbHost}:$DbPort with provided credentials."
}

Write-Host "== Installing dependencies =="
& composer install

Write-Host "== Creating database and importing schema/data =="
& mysql -h $DbHost -P $DbPort -u $DbUser "-p$DbPass" -e "CREATE DATABASE IF NOT EXISTS \`$DbName\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
Get-Content "config/schema/academy_management_db.sql" | & mysql -h $DbHost -P $DbPort -u $DbUser "-p$DbPass" $DbName
Get-Content "config/schema/seed_admin.sql" | & mysql -h $DbHost -P $DbPort -u $DbUser "-p$DbPass" $DbName

Write-Host "== Ensuring local app config exists =="
if (-not (Test-Path "config/app_local.php")) {
    Copy-Item "config/app_local.example.php" "config/app_local.php"
}

Write-Host "== Verifying demo admin account exists =="
& mysql -h $DbHost -P $DbPort -u $DbUser "-p$DbPass" -D $DbName -e "SELECT user_id,email,user_role,account_status FROM users WHERE email='admin@candlecraft.com';"

Write-Host "== Starting CakePHP server =="
Write-Host "URL: http://localhost:$AppPort"
Write-Host "Login: admin@candlecraft.com / admin123"

# Use DATABASE_URL to guarantee runtime DB settings without manual file edits.
$env:DATABASE_URL = "mysql://${DbUser}:${DbPass}@${DbHost}:${DbPort}/${DbName}?encoding=utf8mb4&timezone=UTC"
& bin/cake server -H $AppHost -p $AppPort
