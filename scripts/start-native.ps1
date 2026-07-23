# Start Product Web native stack (MySQL + Laravel)
# Usage: powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\start-native.ps1
$ErrorActionPreference = "Continue"
$root = Split-Path $PSScriptRoot -Parent
Set-Location $root

$php = "C:\tools\php\php.exe"
$mysqlBin = "C:\tools\mysql\bin"
$env:Path = "$mysqlBin;C:\tools\php;C:\tools\composer;C:\tools\node;" + $env:Path

if (-not (Test-Path $php)) {
    Write-Host "ERROR: PHP not found at $php"
    exit 1
}

# --- MySQL ---
$svc = Get-Service MySQL80Product -ErrorAction SilentlyContinue
if ($svc) {
    if ($svc.Status -ne "Running") {
        Write-Host "Starting MySQL80Product service..."
        Start-Service MySQL80Product
        Start-Sleep -Seconds 4
    } else {
        Write-Host "MySQL: running"
    }
} else {
    if (-not (Get-Process mysqld -ErrorAction SilentlyContinue)) {
        Write-Host "Starting mysqld.exe..."
        Start-Process "C:\tools\mysql\bin\mysqld.exe" `
            -ArgumentList "--defaults-file=C:\tools\mysql\my.ini" `
            -WindowStyle Hidden
        Start-Sleep -Seconds 4
    }
}

# --- Free 8080 / old php serve ---
Get-CimInstance Win32_Process -Filter "Name = 'php.exe'" -ErrorAction SilentlyContinue | ForEach-Object {
    if ($_.CommandLine -and ($_.CommandLine -like "*artisan*serve*")) {
        Write-Host "Stopping old serve PID $($_.ProcessId)"
        Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue
    }
}
Start-Sleep -Seconds 1

# --- Start Laravel (detached, no stdout redirect = process survives) ---
$logDir = Join-Path $root "storage\logs"
if (-not (Test-Path $logDir)) { New-Item -ItemType Directory -Path $logDir -Force | Out-Null }

Write-Host "Starting Laravel: http://127.0.0.1:8080"
# cmd start /B detaches from this PowerShell session
$cmd = "cd /d `"$root`" && `"$php`" artisan serve --host=0.0.0.0 --port=8080 >> `"$logDir\artisan-serve.log`" 2>>&1"
Start-Process -FilePath "cmd.exe" -ArgumentList "/c", $cmd -WindowStyle Hidden

# Wait until port listens
$ready = $false
for ($i = 1; $i -le 20; $i++) {
    Start-Sleep -Seconds 1
    $listen = Get-NetTCPConnection -LocalPort 8080 -State Listen -ErrorAction SilentlyContinue
    if ($listen) {
        $ready = $true
        Write-Host "Port 8080 LISTEN (pid $($listen.OwningProcess | Select-Object -First 1))"
        break
    }
}

if (-not $ready) {
    Write-Host "ERROR: port 8080 not listening. Last log:"
    if (Test-Path "$logDir\artisan-serve.log") {
        Get-Content "$logDir\artisan-serve.log" -Tail 40
    }
    exit 1
}

try {
    $r = Invoke-WebRequest -Uri "http://127.0.0.1:8080/login" -UseBasicParsing -TimeoutSec 15
    Write-Host ("OK HTTP {0} - open http://127.0.0.1:8080  (login admin/admin)" -f $r.StatusCode)
} catch {
    Write-Host ("WARN: listen OK but /login failed: {0}" -f $_.Exception.Message)
}
