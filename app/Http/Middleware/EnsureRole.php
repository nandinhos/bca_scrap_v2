<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user() || ! in_array($request->user()->role, $roles)) {
            Log::warning('Acesso negado: usuario sem permissao', [
                'user_id' => $request->user()?->id,
                'email' => $request->user()?->email,
                'role' => $request->user()?->role,
                'required_roles' => $roles,
                'uri' => $request->uri(),
                'ip' => $request->ip(),
            ]);
            abort(403, 'Acesso não autorizado.');
        }

        return $next($request);
    }
}
