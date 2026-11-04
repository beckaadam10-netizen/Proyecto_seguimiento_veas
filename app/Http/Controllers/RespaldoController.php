<?php

namespace App\Http\Controllers;

use App\Console\Commands\CrearRespaldo;
use App\Services\GoogleDriveRespaldos;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

// Versión web de respaldo:crear, para descargar los respaldos sin acceso por SSH.
// Solo administrador: un respaldo tiene toda la información del sistema (datos de todos
// los clientes, sus documentos y las contraseñas cifradas de los usuarios).
class RespaldoController extends Controller
{
    // Solo nombres generados por respaldo:crear: evita que se pida "../.env" u otro archivo.
    private const NOMBRE_VALIDO = '/^respaldo-\d{4}-\d{2}-\d{2}_\d{6}\.zip$/';

    public function index(): View
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $respaldos = collect(File::isDirectory(CrearRespaldo::directorio()) ? File::files(CrearRespaldo::directorio()) : [])
            ->filter(fn ($a) => preg_match(self::NOMBRE_VALIDO, $a->getFilename()))
            ->map(fn ($a) => (object) [
                'nombre' => $a->getFilename(),
                'tamanio' => CrearRespaldo::tamanio($a->getSize()),
                'fecha'  => Carbon::createFromTimestamp($a->getMTime()),
            ])
            ->sortByDesc('fecha')
            ->values();

        $drive = [
            'configurado' => GoogleDriveRespaldos::configurado(),
            'registro'    => GoogleDriveRespaldos::registro(),
            'script'      => file_get_contents(resource_path('drive/respaldos-drive.gs')),
            'manifiesto'  => file_get_contents(resource_path('drive/appsscript.json')),
            // Solo para copiar en la configuración inicial; no se guarda en ningún lado.
            'clave_sugerida' => Str::random(40),
        ];

        return view('respaldos.index', compact('respaldos', 'drive'));
    }

    public function probarDrive(): RedirectResponse
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        try {
            $acceso = (new GoogleDriveRespaldos())->acceso();
            return back()->with('success', 'Conexión con Google Drive correcta: los respaldos se suben a la carpeta "' . ($acceso['nombre'] ?? $acceso['carpeta']) . '".');
        } catch (Throwable $e) {
            return back()->with('error', 'Google Drive: ' . $e->getMessage());
        }
    }

    // Para volver a subir un respaldo cuya subida automática falló.
    public function subirADrive(string $archivo): RedirectResponse
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        abort_unless(preg_match(self::NOMBRE_VALIDO, $archivo), 404);

        $ruta = CrearRespaldo::directorio() . DIRECTORY_SEPARATOR . $archivo;
        abort_unless(is_file($ruta), 404);

        @set_time_limit(0);
        $mensajes = [];
        $ok = CrearRespaldo::subirADrive($ruta, function ($m) use (&$mensajes) { $mensajes[] = $m; }, function ($m) use (&$mensajes) { $mensajes[] = $m; });

        return back()->with($ok ? 'success' : 'error', $ok ? "Respaldo {$archivo} subido a Google Drive." : 'No se pudo subir a Google Drive.')
            ->with('salida', implode("\n", $mensajes));
    }

    // Crea el respaldo y lo descarga en el momento.
    public function crear(Request $request): BinaryFileResponse|RedirectResponse
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        @set_time_limit(0);
        $codigo = Artisan::call('respaldo:crear');
        $salida = trim(preg_replace('/\e\[[\d;]*m/', '', Artisan::output()));

        $ultimo = collect(File::files(CrearRespaldo::directorio()))
            ->filter(fn ($a) => preg_match(self::NOMBRE_VALIDO, $a->getFilename()))
            ->sortByDesc(fn ($a) => $a->getFilename())
            ->first();

        if ($codigo !== 0 || ! $ultimo) {
            return redirect()->route('respaldos.index')
                ->with('error', 'No se pudo crear el respaldo.')
                ->with('salida', $salida);
        }

        // Cookie legible por JS (no cifrada, no httpOnly) para que la página sepa que la
        // descarga llegó y refresque la lista. Solo lleva el token numérico del formulario.
        $token = preg_replace('/\D/', '', (string) $request->input('token'));

        $respuesta = response()->download($ultimo->getPathname());
        $respuesta->headers->setCookie(cookie('respaldo_listo', $token, 1, null, null, null, false));

        return $respuesta;
    }

    public function descargar(string $archivo): BinaryFileResponse
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        abort_unless(preg_match(self::NOMBRE_VALIDO, $archivo), 404);

        $ruta = CrearRespaldo::directorio() . DIRECTORY_SEPARATOR . $archivo;
        abort_unless(is_file($ruta), 404);

        return response()->download($ruta);
    }
}
