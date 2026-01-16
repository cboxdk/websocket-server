<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = config('services.api.admin_token');

        if (empty($configuredToken)) {
            return response()->json([
                'error' => 'API authentication not configured',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $providedToken = $request->bearerToken();

        if (! $providedToken) {
            return response()->json([
                'error' => 'Authorization token required',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (! hash_equals($configuredToken, $providedToken)) {
            return response()->json([
                'error' => 'Invalid authorization token',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
