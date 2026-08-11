<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        abort_unless(
            $request->user()?->tienePermiso($permiso),
            403,
            'No tenés permiso para acceder a esta sección.'
        );

        return $next($request);
    }
}
