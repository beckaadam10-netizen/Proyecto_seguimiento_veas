<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Los formularios con archivos se envían por XHR para poder mostrar la barra de
// carga (ver layouts/app.blade.php). XHR sigue las redirecciones solo, y al hacerlo
// consumiría los mensajes flash ("guardado correctamente", errores de validación)
// antes de que el navegador llegue a mostrarlos. Por eso, en esas peticiones, la
// redirección se devuelve como JSON y es el navegador el que navega al destino.
class RedireccionSubidaConProgreso
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->header('X-Subida-Progreso') && $response instanceof RedirectResponse) {
            return response()->json(['redirect' => $response->getTargetUrl()]);
        }

        return $response;
    }
}
