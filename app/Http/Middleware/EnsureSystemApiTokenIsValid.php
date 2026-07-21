<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemApiTokenIsValid
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = config('services.system_a.api_token');
        $providedToken = $request->bearerToken();

        if (! is_string($configuredToken) || $configuredToken === '' ||
            ! is_string($providedToken) || ! hash_equals($configuredToken, $providedToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
