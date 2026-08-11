<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Para que esto dispare solo, el hosting necesita un cron real (Linux) o Tarea
// Programada (Windows) corriendo "php artisan schedule:run" cada minuto. Si no está
// configurado, queda el botón manual en Bitácora como respaldo.
Schedule::command('bitacora:limpiar')->daily();
