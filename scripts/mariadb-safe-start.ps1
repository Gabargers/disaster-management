$ErrorActionPreference = 'Stop'

$mysqlServer = 'C:\xampp\mysql\bin\mysqld.exe'
$mysqlAdmin = 'C:\xampp\mysql\bin\mysqladmin.exe'
$config = 'C:/xampp/mysql/bin/my.ini'

if (Get-Process mysqld -ErrorAction SilentlyContinue) {
    & $mysqlAdmin --connect-timeout=5 -h 127.0.0.1 -P 3306 -u root ping
    exit $LASTEXITCODE
}

Start-Process -FilePath $mysqlServer `
    -ArgumentList "--defaults-file=$config", '--console' `
    -WindowStyle Hidden

Start-Sleep -Seconds 5
& $mysqlAdmin --connect-timeout=5 -h 127.0.0.1 -P 3306 -u root ping

if ($LASTEXITCODE -ne 0) {
    throw 'MariaDB failed its startup health check. See C:\xampp\mysql\data\mysql_error.log.'
}

Write-Host 'MariaDB started and passed its health check.'
