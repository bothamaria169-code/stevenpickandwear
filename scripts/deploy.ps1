# Steven Picknwear: deploy to the VPS over SSH (PC, PowerShell).
#
#   powershell -ExecutionPolicy Bypass -File scripts\deploy.ps1
#   powershell -ExecutionPolicy Bypass -File scripts\deploy.ps1 -Migration database\migrations\2026-10-29-catalog-update.sql
#
# 1. Checks the domain resolves to the VPS and SSH works (stops if not).
# 2. Pushes main to the server (the post-receive hook deploys the code + images).
# 3. Optionally backs up the live database and runs a migration SQL file on it.
# 4. Checks the live site answers.
param(
    [string]$Migration = '',
    [string]$SshHost = 'gmf',
    [string]$Domain = 'stevenpickandwear.co.za',
    [string]$ServerIp = '201.3.127.250'
)
$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)

Write-Host "== 1. Checks"
$ips = @()
try { $ips = (Resolve-DnsName $Domain -Type A -Server 1.1.1.1 -DnsOnly -ErrorAction Stop | Where-Object Type -eq 'A').IPAddress } catch {}
if ($ips -notcontains $ServerIp) {
    Write-Host "  $Domain resolves to '$($ips -join ', ')', not $ServerIp. DNS hasn't propagated yet: try again later." -ForegroundColor Yellow
    exit 1
}
Write-Host "  DNS ok ($Domain -> $ServerIp)"
ssh -o BatchMode=yes -o ConnectTimeout=15 $SshHost "test -d /var/www/stevenpickandwear"
if ($LASTEXITCODE -ne 0) { Write-Host "  SSH to '$SshHost' failed. Fix key login first (see README2)." -ForegroundColor Red; exit 1 }
Write-Host "  SSH ok"
if (git status --porcelain --untracked-files=no) { Write-Host "  Uncommitted changes: commit them first." -ForegroundColor Red; exit 1 }

Write-Host "== 2. Push code"
git push live main
if ($LASTEXITCODE -ne 0) { exit 1 }

if ($Migration) {
    Write-Host "== 3. Database: backup, then $Migration"
    $stamp = Get-Date -Format 'yyyy-MM-dd_HHmm'
    ssh $SshHost "mkdir -p /root/backups && mysqldump --single-transaction spw_store | gzip > /root/backups/spw_store_before_$stamp.sql.gz && ls -lh /root/backups/spw_store_before_$stamp.sql.gz"
    if ($LASTEXITCODE -ne 0) { Write-Host "  Backup failed, migration NOT run." -ForegroundColor Red; exit 1 }
    Get-Content -Raw -Encoding UTF8 $Migration | ssh $SshHost "mysql --default-character-set=utf8mb4 spw_store"
    if ($LASTEXITCODE -ne 0) { Write-Host "  Migration failed. Restore: gunzip < /root/backups/spw_store_before_$stamp.sql.gz | mysql spw_store" -ForegroundColor Red; exit 1 }
    ssh $SshHost "mysql spw_store -e `"SELECT c.slug, COUNT(*) AS products, MIN(p.base_price) AS min_price, MAX(p.base_price) AS max_price FROM products p JOIN categories c ON c.id = p.category_id GROUP BY c.slug; SELECT key_name, value FROM settings WHERE key_name LIKE 'bank%';`""
}

Write-Host "== 4. Live check"
$code = curl.exe -s -o NUL -w "%{http_code}" --max-time 20 "https://$Domain/"
Write-Host "  https://$Domain/ -> HTTP $code"
