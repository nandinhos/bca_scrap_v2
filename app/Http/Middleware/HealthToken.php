<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HealthToken
{
    /**
     * Protege /health e /metrics: usuario autenticado OU header
     * X-Health-Token igual a HEALTH_TOKEN (monitor externo).
     * Sem token configurado, so autenticado.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            return $next($request);
        }

        $token = config('bca.health_token');
        $given = $request->header('X-Health-Token');

        if ($token && $given && hash_equals((string) $token, (string) $given)) {
            return $next($request);
        }

        abort(401, 'Unauthorized.');
    }
}
