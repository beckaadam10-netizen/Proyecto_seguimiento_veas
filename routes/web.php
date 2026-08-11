<?php

use App\Http\Controllers\AbogadoController;
use App\Http\Controllers\AdministracionController;
use App\Http\Controllers\AudienciaController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CobroController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpedienteController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\GastoCobroController;
use App\Http\Controllers\ParametroController;
use App\Http\Controllers\RastreoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\InstitucionPublicaController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\EstadoExpedienteController;
use App\Http\Controllers\SeguimientoController;
use App\Http\Controllers\TipoActuacionController;
use App\Http\Controllers\TipoAudienciaController;
use App\Http\Controllers\TipoDocumentoController;
use App\Http\Controllers\TipoGastoController;
use App\Http\Controllers\TipoTramiteController;
use App\Http\Controllers\TramiteController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

// Registra un recurso partido en 3 niveles de permiso, respetando el orden
// index → create/store → show → edit/update → destroy para que "create" no
// quede atrapado por el wildcard {id} de "show" (ambos son /recurso/{segmento}).
function recursoConPermisos(string $uri, string $controller, string $modulo, array $parametros = []): void
{
    Route::resource($uri, $controller)->only(['index'])->parameters($parametros)->middleware("permission:{$modulo}.ver");
    Route::resource($uri, $controller)->only(['create', 'store'])->parameters($parametros)->middleware("permission:{$modulo}.crear");
    Route::resource($uri, $controller)->only(['edit', 'update'])->parameters($parametros)->middleware("permission:{$modulo}.modificar");
    Route::resource($uri, $controller)->only(['show'])->parameters($parametros)->middleware("permission:{$modulo}.ver");
    Route::resource($uri, $controller)->only(['destroy'])->parameters($parametros)->middleware("permission:{$modulo}.eliminar");
}

// ── Rastreo público (sin login): estado del trámite por nombre + DNI ──────
Route::get('/rastreo', [RastreoController::class, 'index'])->name('rastreo.index');
Route::post('/rastreo', [RastreoController::class, 'buscar'])
    ->middleware('throttle:10,1')
    ->name('rastreo.buscar');

Route::middleware(['auth', 'cliente.verificado'])->group(function () {

    // ── Dashboard ─────────────────────────────────────────────────────
    Route::get('/', [DashboardController::class, 'index'])
         ->middleware('permission:dashboard.ver')
         ->name('dashboard');

    // ── Módulos ────────────────────────────────────────────────────────
    // Clientes
    recursoConPermisos('clientes', ClienteController::class, 'clientes');

    // Abogados
    recursoConPermisos('abogados', AbogadoController::class, 'abogados');

    // Expedientes
    recursoConPermisos('expedientes', ExpedienteController::class, 'expedientes');

    Route::middleware('permission:expedientes.ver')->group(function () {
        Route::get('expedientes/{expediente}/reporte', [ExpedienteController::class, 'reporte'])
             ->name('expedientes.reporte');
        Route::get('expedientes/{expediente}/reporte/pdf', [ExpedienteController::class, 'reportePdf'])
             ->name('expedientes.reporte.pdf');
    });
    Route::patch('expedientes/{expediente}/estado', [ExpedienteController::class, 'cambiarEstado'])
         ->middleware('permission:expedientes.modificar')
         ->name('expedientes.cambiar-estado');
    Route::get('expedientes/{expediente}/items-cobro/pdf', [ExpedienteController::class, 'itemsCobroPdf'])
         ->middleware('permission:gastos_cobros.ver')
         ->name('expedientes.items-cobro.pdf');
    Route::get('expedientes/{expediente}/documentos/zip', [ExpedienteController::class, 'documentosZip'])
         ->middleware(['permission:expedientes.ver', 'permission:documentos.descargar'])
         ->name('expedientes.documentos.zip');
    Route::get('expedientes/{expediente}/documentos/pdf', [ExpedienteController::class, 'documentosPdf'])
         ->middleware(['permission:expedientes.ver', 'permission:documentos.descargar'])
         ->name('expedientes.documentos.pdf');

    // Trámites
    recursoConPermisos('tramites', TramiteController::class, 'tramites');

    Route::patch('tramites/{tramite}/estado', [TramiteController::class, 'cambiarEstado'])
         ->middleware('permission:tramites.modificar')
         ->name('tramites.cambiar-estado');
    Route::middleware('permission:tramites.ver')->group(function () {
        Route::get('tramites/{tramite}/reporte', [TramiteController::class, 'reporte'])
             ->name('tramites.reporte');
        Route::get('tramites/{tramite}/reporte/pdf', [TramiteController::class, 'reportePdf'])
             ->name('tramites.reporte.pdf');
    });
    Route::get('tramites/{tramite}/items-cobro/pdf', [TramiteController::class, 'itemsCobroPdf'])
         ->middleware('permission:gastos_cobros.ver')
         ->name('tramites.items-cobro.pdf');
    Route::get('tramites/{tramite}/documentos/zip', [TramiteController::class, 'documentosZip'])
         ->middleware(['permission:tramites.ver', 'permission:documentos.descargar'])
         ->name('tramites.documentos.zip');
    Route::get('tramites/{tramite}/documentos/pdf', [TramiteController::class, 'documentosPdf'])
         ->middleware(['permission:tramites.ver', 'permission:documentos.descargar'])
         ->name('tramites.documentos.pdf');

    // Gastos y Cobros (registro rápido, eligiendo el trámite desde acá)
    Route::get('gastos-cobros', [GastoCobroController::class, 'index'])
         ->middleware('permission:gastos_cobros.ver')
         ->name('gastos-cobros.index');

    // Gastos (siempre dentro del contexto de un trámite, sin index/show propios)
    Route::resource('gastos', GastoController::class)->only(['create', 'store'])->middleware('permission:gastos_cobros.crear');
    Route::resource('gastos', GastoController::class)->only(['edit', 'update'])->middleware('permission:gastos_cobros.modificar');
    Route::resource('gastos', GastoController::class)->only(['destroy'])->middleware('permission:gastos_cobros.eliminar');

    // Cobros al cliente (siempre dentro del contexto de un trámite, sin index/show propios)
    Route::resource('cobros', CobroController::class)->only(['create', 'store', 'edit', 'update'])->middleware('permission:gastos_cobros.cobrar');
    Route::resource('cobros', CobroController::class)->only(['destroy'])->middleware('permission:gastos_cobros.eliminar');

    // Seguimientos / Actuaciones
    recursoConPermisos('seguimientos', SeguimientoController::class, 'seguimientos');
    Route::patch('seguimientos/{seguimiento}/responder', [SeguimientoController::class, 'marcarRespondido'])
         ->middleware('permission:seguimientos.modificar')
         ->name('seguimientos.responder');
    Route::get('expedientes/{expediente}/seguimientos/historial', [SeguimientoController::class, 'historial'])
         ->middleware('permission:seguimientos.ver')
         ->name('seguimientos.historial');
    Route::patch('seguimientos/{seguimiento}/revisado', [SeguimientoController::class, 'marcarRevisado'])
         ->middleware('permission:seguimientos.modificar')
         ->name('seguimientos.revisado');

    // Documentos: listado (archivos adjuntos de actuaciones + documentos sueltos por expediente)
    Route::get('documentos', [DocumentoController::class, 'index'])
         ->middleware('permission:seguimientos.ver')
         ->name('documentos.index');
    Route::get('documentos/{seguimiento}/ver', [DocumentoController::class, 'ver'])
         ->middleware('permission:documentos.ver')
         ->name('documentos.ver');
    Route::get('documentos/{seguimiento}/descargar', [DocumentoController::class, 'descargar'])
         ->middleware('permission:documentos.descargar')
         ->name('documentos.descargar');

    // Documentos sueltos, cargados directo contra un expediente (sin pasar por un seguimiento).
    Route::post('documentos', [DocumentoController::class, 'store'])
         ->middleware('permission:documentos.crear')
         ->name('documentos.store');
    Route::get('documentos/archivo/{documento}/ver', [DocumentoController::class, 'verDocumento'])
         ->middleware('permission:documentos.ver')
         ->name('documentos.archivo.ver');
    Route::get('documentos/archivo/{documento}/descargar', [DocumentoController::class, 'descargarDocumento'])
         ->middleware('permission:documentos.descargar')
         ->name('documentos.archivo.descargar');
    Route::delete('documentos/archivo/{documento}', [DocumentoController::class, 'eliminarDocumento'])
         ->middleware('permission:documentos.eliminar')
         ->name('documentos.archivo.eliminar');

    // Audiencias
    recursoConPermisos('audiencias', AudienciaController::class, 'audiencias');
    Route::patch('audiencias/{audiencia}/finalizar', [AudienciaController::class, 'marcarRealizada'])
         ->middleware('permission:audiencias.modificar')
         ->name('audiencias.finalizar');

    // Parámetros (catálogos sin acción "show", no hay riesgo de colisión con "create")
    Route::prefix('parametros')->name('parametros.')->group(function () {
        Route::get('/', [ParametroController::class, 'index'])->middleware('permission:parametros.ver')->name('index');

        $tiposParametros = [
            'tipos-audiencia'         => [TipoAudienciaController::class, 'tipo_audiencia'],
            'tipos-documento'         => [TipoDocumentoController::class, 'tipo_documento'],
            'tipos-actuacion'         => [TipoActuacionController::class, 'tipo_actuacion'],
            'tipos-tramite'           => [TipoTramiteController::class, 'tipo_tramite'],
            'instituciones-publicas'  => [InstitucionPublicaController::class, 'institucion_publica'],
            'tipos-gasto'             => [TipoGastoController::class, 'tipo_gasto'],
            'estados-expediente'      => [EstadoExpedienteController::class, 'estado_expediente'],
        ];

        foreach ($tiposParametros as $uri => [$controller, $parametro]) {
            Route::resource($uri, $controller)
                ->only(['index'])
                ->parameters([$uri => $parametro])
                ->middleware('permission:parametros.ver');

            Route::resource($uri, $controller)
                ->only(['create', 'store'])
                ->parameters([$uri => $parametro])
                ->middleware('permission:parametros.crear');

            Route::resource($uri, $controller)
                ->only(['edit', 'update'])
                ->parameters([$uri => $parametro])
                ->middleware('permission:parametros.modificar');

            Route::resource($uri, $controller)
                ->only(['destroy'])
                ->parameters([$uri => $parametro])
                ->middleware('permission:parametros.eliminar');
        }
    });

    // Administración (usuarios/roles sin index/show propios: se listan dentro de administracion.index)
    Route::prefix('administracion')->name('administracion.')->group(function () {
        Route::get('/', [AdministracionController::class, 'index'])->middleware('permission:administracion.ver')->name('index');

        Route::resource('usuarios', UsuarioController::class)
            ->only(['create', 'store'])
            ->parameters(['usuarios' => 'usuario'])
            ->middleware('permission:administracion.crear');
        Route::resource('usuarios', UsuarioController::class)
            ->only(['edit', 'update'])
            ->parameters(['usuarios' => 'usuario'])
            ->middleware('permission:administracion.modificar');
        Route::resource('usuarios', UsuarioController::class)
            ->only(['destroy'])
            ->parameters(['usuarios' => 'usuario'])
            ->middleware('permission:administracion.eliminar');

        Route::resource('roles', RolController::class)
            ->only(['create', 'store'])
            ->parameters(['roles' => 'rol'])
            ->middleware('permission:administracion.crear');
        Route::resource('roles', RolController::class)
            ->only(['edit', 'update'])
            ->parameters(['roles' => 'rol'])
            ->middleware('permission:administracion.modificar');
        Route::resource('roles', RolController::class)
            ->only(['destroy'])
            ->parameters(['roles' => 'rol'])
            ->middleware('permission:administracion.eliminar');
    });

    // Bitácora (auditoría): solo lectura, salvo la limpieza manual de registros viejos.
    Route::get('bitacora', [BitacoraController::class, 'index'])
         ->middleware('permission:bitacora.ver')
         ->name('bitacora.index');
    Route::delete('bitacora/limpiar', [BitacoraController::class, 'limpiar'])
         ->middleware('permission:bitacora.ver')
         ->name('bitacora.limpiar');

    // Reportes
    Route::prefix('reportes')->name('reportes.')->middleware('permission:reportes.ver')->group(function () {
        Route::get('/', [ReporteController::class, 'index'])->name('index');
        Route::get('/clientes', [ReporteController::class, 'clientes'])->name('clientes');
        Route::get('/clientes/pdf', [ReporteController::class, 'clientesPdf'])->name('clientes.pdf');
        Route::get('/expedientes', [ReporteController::class, 'expedientes'])->name('expedientes');
        Route::get('/expedientes/pdf', [ReporteController::class, 'expedientesPdf'])->name('expedientes.pdf');
        Route::get('/tramites', [ReporteController::class, 'tramites'])->name('tramites');
        Route::get('/tramites/pdf', [ReporteController::class, 'tramitesPdf'])->name('tramites.pdf');
        Route::get('/gastos-cobros', [ReporteController::class, 'gastosCobros'])->name('gastos-cobros');
        Route::get('/gastos-cobros/pdf', [ReporteController::class, 'gastosCobrosPdf'])->name('gastos-cobros.pdf');

        // Reporte Pasantes: permiso propio, independiente de "reportes.ver", para poder
        // dárselo a un rol (ej. Pasante) sin abrirle el resto de los reportes.
        Route::get('/pasantes', [ReporteController::class, 'pasantes'])
             ->withoutMiddleware('permission:reportes.ver')
             ->middleware('permission:reportes.pasantes')
             ->name('pasantes');
        Route::get('/pasantes/pdf', [ReporteController::class, 'pasantesPdf'])
             ->withoutMiddleware('permission:reportes.ver')
             ->middleware('permission:reportes.pasantes')
             ->name('pasantes.pdf');
        Route::get('/pasantes/{reportePasanteGenerado}/ver', [ReporteController::class, 'pasantesVerPdf'])
             ->withoutMiddleware('permission:reportes.ver')
             ->middleware('permission:reportes.pasantes')
             ->name('pasantes.ver');
        Route::get('/pasantes/{reportePasanteGenerado}/ver-cliente', [ReporteController::class, 'pasantesVerPdfCliente'])
             ->withoutMiddleware('permission:reportes.ver')
             ->middleware('permission:reportes.pasantes')
             ->name('pasantes.ver-cliente');
        Route::patch('/pasantes/{reportePasanteGenerado}/revisado', [ReporteController::class, 'pasantesMarcarRevisado'])
             ->withoutMiddleware('permission:reportes.ver')
             ->middleware('permission:reportes.pasantes')
             ->name('pasantes.revisado');
    });

});

// Autenticación (usa el scaffolding de Laravel Breeze o similar)
require __DIR__ . '/auth.php';
