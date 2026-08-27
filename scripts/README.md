# MariaDB operations

The recurring corruption was caused by unclean MariaDB termination. The
MariaDB error log showed crash recovery on every startup and no completed
normal shutdown.

Before closing XAMPP or shutting down Windows, run:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\mariadb-safe-stop.ps1
```

To start MariaDB and verify that it accepts connections, run:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\mariadb-safe-start.ps1
```

To check the critical application and MariaDB system tables, run:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\mariadb-health-check.ps1
```

For automatic graceful Windows shutdown handling, run XAMPP Control Panel as
Administrator once, stop MySQL, enable its **Svc** checkbox, then start MySQL.
Windows Service Control Manager will then stop MariaDB cleanly during Windows
shutdown and restart it after a process failure.
