$ErrorActionPreference = 'Stop'

$mysqlAdmin = 'C:\xampp\mysql\bin\mysqladmin.exe'

if (-not (Get-Process mysqld -ErrorAction SilentlyContinue)) {
    Write-Host 'MariaDB is already stopped.'
    exit 0
}

& $mysqlAdmin --connect-timeout=5 -h 127.0.0.1 -P 3306 -u root shutdown
Start-Sleep -Seconds 3

if (Get-Process mysqld -ErrorAction SilentlyContinue) {
    throw 'MariaDB did not stop cleanly. Do not force-close XAMPP or Windows; check mysql_error.log.'
}

Write-Host 'MariaDB stopped cleanly. It is now safe to close XAMPP or shut down Windows.'
