<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// El estudio crea a los usuarios internos ya verificados a mano, así que no
// necesitan confirmar su email. Los clientes se autoregistran, así que sí:
// hasta que no confirmen, no pueden ver ningún trámite/expediente.
class EnsureClienteVerificado
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->esCliente() && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
