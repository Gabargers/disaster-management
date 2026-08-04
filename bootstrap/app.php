<?php

use App\Http\Middleware\AuditPersonAffectedApi;
use App\Http\Middleware\EnsureSystemApiTokenIsValid;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RecordUserActivity;
use App\Http\Middleware\RequireBoundedJsonBody;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            RecordUserActivity::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'active' => EnsureUserIsActive::class,
            'system.api.token' => EnsureSystemApiTokenIsValid::class,
            'api.audit' => AuditPersonAffectedApi::class,
            'api.bounded-json' => RequireBoundedJsonBody::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
