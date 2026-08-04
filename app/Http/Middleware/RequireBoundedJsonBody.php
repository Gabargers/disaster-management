<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireBoundedJsonBody
{
    public function handle(Request $request, Closure $next): Response
    {
        $contentType = strtolower(trim(explode(';', (string) $request->header('Content-Type'))[0]));

        if ($contentType !== 'application/json') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Content-Type must be application/json.',
            ], Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        }

        $maximumBytes = (int) config('services.system_a.max_body_bytes', 262144);
        $declaredBytes = (int) $request->header('Content-Length', 0);

        if ($declaredBytes > $maximumBytes || strlen($request->getContent()) > $maximumBytes) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Request body is too large.',
            ], Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        return $next($request);
    }
}
