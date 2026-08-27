$ErrorActionPreference = 'Stop'

$mysqlAdmin = 'C:\xampp\mysql\bin\mysqladmin.exe'
$mysqlCheck = 'C:\xampp\mysql\bin\mysqlcheck.exe'

& $mysqlAdmin --connect-timeout=5 -h 127.0.0.1 -P 3306 -u root ping
if ($LASTEXITCODE -ne 0) {
    throw 'MariaDB is not responding.'
}

& $mysqlCheck -h 127.0.0.1 -P 3306 -u root `
    disaster_management users cache cache_locks

if ($LASTEXITCODE -ne 0) {
    throw 'One or more critical application tables failed the integrity check.'
}

& $mysqlCheck -h 127.0.0.1 -P 3306 -u root `
    mysql gtid_slave_pos proxies_priv

if ($LASTEXITCODE -ne 0) {
    throw 'One or more critical MariaDB system tables failed the integrity check.'
}

Write-Host 'MariaDB and all critical tables passed the health check.'
