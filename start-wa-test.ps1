# start-wa-test.ps1
# Jalankan server CRM (port 8001) + cloudflared tunnel untuk testing webhook Fonnte.
# Cara pakai:  klik kanan > Run with PowerShell,  atau di terminal:  .\start-wa-test.ps1

$ErrorActionPreference = 'Stop'
$projectDir = $PSScriptRoot

Write-Host "== Aterra CRM - WA test ==" -ForegroundColor Cyan

# 0) PHP 8.4 Herd (Laravel 13 butuh PHP >= 8.3). Fallback ke 'php' di PATH.
$php = Join-Path $env:USERPROFILE ".config\herd\bin\php84\php.exe"
if (-not (Test-Path $php)) { $php = "php" }
Write-Host "[0] PHP: $php" -ForegroundColor DarkGray

# 1) Backend di jendela terpisah (port 8001)
Start-Process powershell -ArgumentList @(
    '-NoExit', '-Command',
    "Set-Location '$projectDir'; & '$php' artisan serve --port=8001"
)
Write-Host "[1] Backend dimulai di port 8001 (jendela baru)." -ForegroundColor Green

# 2) Cari cloudflared
$cloudflared = "C:\Program Files (x86)\cloudflared\cloudflared.exe"
if (-not (Test-Path $cloudflared)) {
    $cmd = Get-Command cloudflared -ErrorAction SilentlyContinue
    if ($cmd) { $cloudflared = $cmd.Source }
}
if (-not (Test-Path $cloudflared)) {
    Write-Host "cloudflared tidak ditemukan." -ForegroundColor Red
    Write-Host "Install dulu:  winget install --id Cloudflare.cloudflared" -ForegroundColor Yellow
    return
}

# 3) Tunnel di jendela terpisah
Start-Process powershell -ArgumentList @(
    '-NoExit', '-Command',
    "& '$cloudflared' tunnel --url http://localhost:8001"
)
Write-Host "[2] Cloudflared tunnel dimulai (jendela baru)." -ForegroundColor Green

# 4) Ambil secret dari .env untuk membentuk URL webhook lengkap
$secret = ''
$envFile = Join-Path $projectDir '.env'
if (Test-Path $envFile) {
    $line = Get-Content $envFile | Where-Object { $_ -match '^FONNTE_WEBHOOK_SECRET=' } | Select-Object -First 1
    if ($line) { $secret = ($line -replace '^FONNTE_WEBHOOK_SECRET=', '').Trim() }
}

Write-Host ""
Write-Host "Langkah selanjutnya:" -ForegroundColor Cyan
Write-Host " - Lihat jendela cloudflared, salin URL https://xxxx.trycloudflare.com"
Write-Host " - Pasang di dashboard Fonnte (Webhook URL untuk pesan masuk):"
if ($secret -ne '') {
    Write-Host "   https://xxxx.trycloudflare.com/api/webhooks/fonnte/$secret" -ForegroundColor Yellow
} else {
    Write-Host "   https://xxxx.trycloudflare.com/api/webhooks/fonnte/<SECRET>" -ForegroundColor Yellow
    Write-Host "   (isi <SECRET> dengan FONNTE_WEBHOOK_SECRET di file .env)" -ForegroundColor DarkGray
}
Write-Host ""
Write-Host "Catatan:" -ForegroundColor DarkGray
Write-Host " - URL trycloudflare berubah tiap dijalankan -> update lagi di Fonnte." -ForegroundColor DarkGray
Write-Host " - Nomor sama dengan larashop: webhook ini menggantikan webhook larashop sementara." -ForegroundColor DarkGray
Write-Host " - Kirim WhatsApp ke nomor device -> cek Inbox: http://localhost:8001/inbox" -ForegroundColor DarkGray
