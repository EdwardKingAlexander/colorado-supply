# Starts the local MySQL that holds the PUB LOG catalog (port 13316, database
# publog_benchmark). The app reaches it through PUBLOG_DB_* in .env. Run after
# a reboot:  powershell -ExecutionPolicy Bypass -File start-local-mysql.ps1
# Stop it cleanly with:
#   & "$PSScriptRoot\artifacts\mysql-benchmark\mysql-8.0.46-winx64\bin\mysqladmin.exe" "--host=127.0.0.1" "--port=13316" "--user=root" shutdown
$root = Join-Path $PSScriptRoot 'artifacts\mysql-benchmark'
$base = (Join-Path $root 'mysql-8.0.46-winx64') -replace '\\', '/'
$data = (Join-Path $root 'data') -replace '\\', '/'
if (Get-NetTCPConnection -LocalPort 13316 -State Listen -ErrorAction SilentlyContinue) {
    Write-Output 'MySQL is already listening on 13316.'
    return
}
Start-Process -FilePath "$base/bin/mysqld.exe" -WindowStyle Hidden -ArgumentList @(
    '--no-defaults', "--basedir=$base", "--datadir=$data", '--bind-address=127.0.0.1', '--port=13316',
    '--mysqlx=OFF', '--innodb-buffer-pool-size=256M', '--max-connections=8', '--skip-log-bin'
)
Write-Output 'Started MySQL on 13316.'
