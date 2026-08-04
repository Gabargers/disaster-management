<?php

namespace App\Http\Middleware;

use App\Models\Integration\ApiAuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class AuditPersonAffectedApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);
        $requestId = $this->requestId($request);
        $response = null;
        $exception = null;

        try {
            $response = $next($request);
            $response->headers->set('X-Request-ID', $requestId);

            return $response;
        } catch (Throwable $throwable) {
            $exception = $throwable;

            throw $throwable;
        } finally {
            $this->record($request, $requestId, $startedAt, $response, $exception);
        }
    }

    private function requestId(Request $request): string
    {
        $provided = $request->header('X-Request-ID');

        if (is_string($provided) && preg_match('/\A[A-Za-z0-9][A-Za-z0-9._:-]{7,127}\z/', $provided) === 1) {
            return $provided;
        }

        return (string) Str::uuid();
    }

    private function record(
        Request $request,
        string $requestId,
        int $startedAt,
        ?Response $response,
        ?Throwable $exception
    ): void {
        $status = $this->responseStatus($response, $exception);

        try {
            ApiAuditLog::create([
                'client_id' => $this->limitedString($request->attributes->get('api_client_id'), 255),
                'request_id' => $requestId,
                'event_reference' => $this->limitedString($request->header('Idempotency-Key'), 255),
                'control_number' => $this->limitedString($request->input('control_number'), 255),
                'source_ip' => $this->limitedString($request->ip(), 45),
                'http_method' => $request->method(),
                'route' => '/api/person-affecteds',
                'response_status' => $status,
                'processing_time_ms' => max(0, (int) round((hrtime(true) - $startedAt) / 1_000_000)),
                'outcome' => $this->outcome($response, $status),
                'created_at' => now(),
            ]);
        } catch (Throwable $auditFailure) {
            // Audit storage must not alter the API result. Do not include request
            // headers, bodies, credentials, or personal data in the fallback log.
            Log::error('Person affected API audit record could not be stored.', [
                'request_id' => $requestId,
                'exception' => $auditFailure::class,
            ]);
        }
    }

    private function responseStatus(?Response $response, ?Throwable $exception): int
    {
        if ($response) {
            return $response->getStatusCode();
        }

        if ($exception instanceof ValidationException) {
            return Response::HTTP_UNPROCESSABLE_ENTITY;
        }

        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    private function outcome(?Response $response, int $status): string
    {
        if ($status === Response::HTTP_CONFLICT) {
            return 'conflicted';
        }

        if ($status >= 400) {
            return 'rejected';
        }

        if ($response && $response->headers->get('Idempotency-Replayed') === 'true') {
            return 'retried';
        }

        $body = $response ? json_decode((string) $response->getContent(), true) : null;

        return data_get($body, 'data.event_created') === true ? 'created' : 'retried';
    }

    private function limitedString(mixed $value, int $length): ?string
    {
        return is_scalar($value) ? Str::limit((string) $value, $length, '') : null;
    }
}
