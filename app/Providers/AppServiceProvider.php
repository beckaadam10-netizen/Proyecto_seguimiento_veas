<?php

namespace App\Providers;

use App\Models\Audiencia;
use App\Models\Bitacora;
use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\Documento;
use App\Models\Expediente;
use App\Models\Gasto;
use App\Models\Rol;
use App\Models\Seguimiento;
use App\Models\Tramite;
use App\Models\User;
use App\Observers\BitacoraObserver;
use App\Observers\ClienteObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Registered::class, SendEmailVerificationNotification::class);

        // Bitácora de auditoría: altas, bajas y cambios de los módulos de negocio.
        foreach ([Cliente::class, Expediente::class, Tramite::class, Seguimiento::class, Gasto::class, Cobro::class, Documento::class, Audiencia::class, User::class, Rol::class] as $modelo) {
            $modelo::observe(BitacoraObserver::class);
        }

        // Todo cliente nuevo recibe de una su cuenta de acceso con el rol "Cliente".
        Cliente::observe(ClienteObserver::class);

        Event::listen(Login::class, function (Login $event): void {
            Bitacora::create([
                'usuario_id'  => $event->user->id,
                'accion'      => 'inicio_sesion',
                'modelo'      => 'User',
                'modelo_id'   => $event->user->id,
                'descripcion' => "Inició sesión {$event->user->name}",
                'ip'          => request()?->ip(),
            ]);
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if (! $event->user) {
                return;
            }

            Bitacora::create([
                'usuario_id'  => $event->user->id,
                'accion'      => 'cierre_sesion',
                'modelo'      => 'User',
                'modelo_id'   => $event->user->id,
                'descripcion' => "Cerró sesión {$event->user->name}",
                'ip'          => request()?->ip(),
            ]);
        });
    }
}
