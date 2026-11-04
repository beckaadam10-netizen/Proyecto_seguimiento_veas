<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => \App\Http\Middleware\EnsurePermission::class,
            'cliente.verificado' => \App\Http\Middleware\EnsureClienteVerificado::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\RedireccionSubidaConProgreso::class,
        ]);

        // El rastreo público solo consulta (no modifica nada) y lo usan clientes sin
        // sesión: con CSRF, si la página quedaba abierta más que la sesión (o el
        // navegador la mostraba desde caché) la búsqueda fallaba con "419 Page Expired".
        // Lo protege el throttle de la ruta.
        $middleware->validateCsrfTokens(except: [
            'rastreo',
        ]);

        // La página Respaldos la lee desde JS para saber que la descarga terminó; solo
        // lleva un token numérico (ver RespaldoController::crear).
        $middleware->encryptCookies(except: [
            'respaldo_listo',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
