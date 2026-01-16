<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MetricsAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isAuthorized($request)) {
            return response('Unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }

    /**
     * Check if the request is authorized.
     */
    protected function isAuthorized(Request $request): bool
    {
        if ($this->isIpAllowed($request)) {
            return true;
        }

        return $this->isTokenValid($request);
    }

    /**
     * Check if the request IP is in the allowed list.
     */
    protected function isIpAllowed(Request $request): bool
    {
        $allowedIps = config('metrics.auth.allowed_ips', []);

        if (empty($allowedIps)) {
            return false;
        }

        return in_array($request->ip(), $allowedIps, true);
    }

    /**
     * Check if the provided token is valid.
     */
    protected function isTokenValid(Request $request): bool
    {
        $configuredToken = config('metrics.auth.token');

        if (empty($configuredToken)) {
            return true;
        }

        $providedToken = $request->bearerToken();

        if (! $providedToken) {
            return false;
        }

        return hash_equals($configuredToken, $providedToken);
    }
}
