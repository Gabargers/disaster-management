<?php

namespace App\Http\Middleware;

use App\Models\Auth\User;
use App\Models\Disaster\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RecordUserActivity
{
    private const SENSITIVE_KEYS = [
        '_token', 'password', 'password_confirmation', 'current_password',
        'token', 'remember_token', 'signature', 'thumbmark', 'photo',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $actor = $request->user();

        try {
            $response = $next($request);
            $this->record($request, $request->user() ?? $actor, $response->getStatusCode());

            return $response;
        } catch (Throwable $exception) {
            $this->record($request, $request->user() ?? $actor, 500, $exception::class);
            throw $exception;
        }
    }

    private function record(Request $request, ?User $actor, int $status, ?string $exception = null): void
    {
        $routeName = $request->route()?->getName();

        if (! $actor || str_starts_with((string) $routeName, 'activity-logs.')) {
            return;
        }

        try {
            AuditLog::create([
                'user_id' => $actor->id,
                'auditable_type' => User::class,
                'auditable_id' => $actor->id,
                'action' => $routeName ?: strtolower($request->method()).'_request',
                'new_values' => [
                    'route' => $routeName,
                    'method' => $request->method(),
                    'path' => '/'.ltrim($request->path(), '/'),
                    'status_code' => $status,
                    'roles' => $actor->getRoleNames()->values()->all(),
                    'input' => $this->sanitize($request->except(self::SENSITIVE_KEYS)),
                    'exception' => $exception,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            ]);
        } catch (Throwable) {
            // Activity logging must never prevent the requested operation.
        }
    }

    private function sanitize(array $values): array
    {
        foreach ($values as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                unset($values[$key]);
            } elseif (is_array($value)) {
                $values[$key] = $this->sanitize($value);
            } elseif (is_object($value)) {
                $values[$key] = '[object omitted]';
            } elseif (is_string($value) && strlen($value) > 1000) {
                $values[$key] = substr($value, 0, 1000).'…';
            }
        }

        return $values;
    }
}
