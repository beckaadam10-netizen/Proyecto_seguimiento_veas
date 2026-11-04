<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\Documento;
use App\Models\Expediente;
use App\Models\Gasto;
use App\Models\Seguimiento;
use App\Models\Tramite;
use App\Services\GoogleDriveRespaldos;
use App\Support\XlsxSimple;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

// Respaldo en un solo .zip, pensado para que se pueda abrir y leer sin el sistema:
//
//   DOCUMENTOS/EXPEDIENTES/<Cliente> - <N° expediente>/<fecha> - <título>.pdf
//   DOCUMENTOS/TRAMITES/<Cliente> - <código>/...
//   BASE DE DATOS/Informacion del sistema.xlsx   (clientes, casos, gastos, cobros...)
//   BASE DE DATOS/respaldo-completo.sql          (para restaurar el sistema entero)
//
// Se guarda en storage/respaldos: fuera de storage/app (un respaldo no entra en el
// siguiente) y fuera de public (solo se baja desde la página Respaldos, como admin).
class CrearRespaldo extends Command
{
    protected $signature = 'respaldo:crear
        {--dias=14 : Se borran los respaldos con más de esta cantidad de días}
        {--sin-drive : No subirlo a Google Drive aunque esté configurado}';

    protected $description = 'Crea un respaldo (.zip) con los documentos por caso y la información del sistema';

    // Ya vienen comprimidos: volver a comprimirlos solo hace más lento el respaldo.
    private const SIN_COMPRIMIR = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'zip', 'rar', 'docx', 'xlsx', 'pptx', 'mp3', 'mp4'];

    /** @var string[] */
    private array $temporales = [];

    /** @var array<string, true> Rutas ya usadas dentro del zip, para no pisar archivos con el mismo nombre. */
    private array $usadas = [];

    public static function directorio(): string
    {
        return storage_path('respaldos');
    }

    public function handle(): int
    {
        @set_time_limit(0);

        if (! class_exists(ZipArchive::class)) {
            $this->error('El servidor no tiene la extensión zip de PHP: no se puede crear el respaldo.');
            return self::FAILURE;
        }

        File::ensureDirectoryExists(self::directorio());
        $destino = self::directorio() . '/respaldo-' . now()->format('Y-m-d_His') . '.zip';

        $zip = new ZipArchive();
        $zip->open($destino, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        try {
            $casos = $this->casos();

            $archivos = $this->agregarDocumentos($zip, $casos);
            $this->info("Documentos: {$archivos['incluidos']} archivo(s)"
                . ($archivos['faltantes'] ? ", {$archivos['faltantes']} no encontrado(s) en el servidor" : ''));

            $xlsx = $this->temporal('xlsx');
            $this->planillas($casos, $archivos['detalle'])->guardar($xlsx);
            $zip->addFile($xlsx, 'BASE DE DATOS/Informacion del sistema.xlsx');

            $sql = $this->temporal('sql');
            $metodo = $this->respaldarBase($sql);
            $zip->addFile($sql, 'BASE DE DATOS/respaldo-completo.sql');
            $this->info("Base de datos: planilla Excel y respaldo-completo.sql (con {$metodo})");

            // Los archivos agregados con addFile se leen recién acá.
            $zip->close();
        } catch (Throwable $e) {
            @$zip->close();
            File::delete($destino);
            $this->error('No se pudo crear el respaldo: ' . $e->getMessage());
            return self::FAILURE;
        } finally {
            File::delete($this->temporales);
        }

        $this->info('Respaldo: ' . basename($destino) . ' (' . self::tamanio(filesize($destino)) . ')');
        $this->borrarViejos((int) $this->option('dias'));

        if (! $this->option('sin-drive')) {
            self::subirADrive($destino, fn ($m) => $this->info($m), fn ($m) => $this->warn($m));
        }

        return self::SUCCESS;
    }

    // Si Drive falla, el respaldo igual queda en el servidor: se avisa pero no se
    // considera fallido. También la usa el botón "Subir a Drive" de la página.
    public static function subirADrive(string $ruta, callable $info, callable $aviso): bool
    {
        if (! GoogleDriveRespaldos::configurado()) {
            return false;
        }

        $drive  = new GoogleDriveRespaldos();
        $nombre = basename($ruta);

        try {
            $subido = $drive->subir($ruta, $nombre);
            GoogleDriveRespaldos::registrar($nombre, ['estado' => 'subido'] + $subido);
            $info('Google Drive: subido (' . $subido['link'] . ')');
        } catch (Throwable $e) {
            GoogleDriveRespaldos::registrar($nombre, ['estado' => 'error', 'error' => $e->getMessage()]);
            $aviso('Google Drive: no se pudo subir — ' . $e->getMessage());
            return false;
        }

        try {
            $dias = (int) config('services.google_drive.conservar', 14);
            if ($enviados = $drive->limpiar($dias)) {
                $info("Google Drive: {$enviados} respaldo(s) de más de {$dias} días enviados a la papelera.");
            }
        } catch (Throwable $e) {
            $aviso('Google Drive: no se pudieron limpiar los respaldos viejos — ' . $e->getMessage());
        }

        return true;
    }

    // ── Documentos ───────────────────────────────────────────────

    // Expedientes y trámites con el nombre de carpeta que van a tener en el zip.
    private function casos(): array
    {
        $casos = ['expediente' => [], 'tramite' => []];

        foreach (Expediente::withTrashed()->with(['cliente' => fn ($q) => $q->withTrashed()])->get() as $e) {
            $codigo = $e->numero ?: "Expediente {$e->id}";
            $casos['expediente'][$e->id] = (object) [
                'codigo'  => $codigo,
                'cliente' => $e->cliente?->nombre_completo ?? 'Sin cliente',
                'carpeta' => 'DOCUMENTOS/EXPEDIENTES/' . $this->nombreSeguro(($e->cliente?->nombre_completo ?? 'Sin cliente') . " - {$codigo}")
                    . ($e->trashed() ? ' (eliminado)' : ''),
            ];
        }

        foreach (Tramite::with(['cliente' => fn ($q) => $q->withTrashed()])->get() as $t) {
            $codigo = $t->codigo ?: "Tramite {$t->id}";
            $casos['tramite'][$t->id] = (object) [
                'codigo'  => $codigo,
                'cliente' => $t->cliente?->nombre_completo ?? 'Sin cliente',
                'carpeta' => 'DOCUMENTOS/TRAMITES/' . $this->nombreSeguro(($t->cliente?->nombre_completo ?? 'Sin cliente') . " - {$codigo}"),
            ];
        }

        return $casos;
    }

    // Los documentos del caso y los adjuntos de sus actuaciones, cada uno con un nombre
    // legible (fecha + título) en vez del nombre aleatorio con que se guardó.
    private function agregarDocumentos(ZipArchive $zip, array $casos): array
    {
        $disco = Storage::disk('public');
        $origenes = [];

        foreach (Documento::all() as $d) {
            $origenes[] = ['ruta' => $d->archivo, 'titulo' => $d->titulo, 'fecha' => $d->created_at,
                'expediente_id' => $d->expediente_id, 'tramite_id' => $d->tramite_id, 'tipo' => 'Documento'];
        }
        foreach (Seguimiento::whereNotNull('archivo_adjunto')->where('archivo_adjunto', '!=', '')->get() as $s) {
            $origenes[] = ['ruta' => $s->archivo_adjunto, 'titulo' => $s->titulo, 'fecha' => $s->fecha_actuacion,
                'expediente_id' => $s->expediente_id, 'tramite_id' => $s->tramite_id, 'tipo' => 'Adjunto de actuación'];
        }

        $detalle = [];
        $incluidos = $faltantes = 0;

        foreach ($origenes as $o) {
            $caso = $o['expediente_id'] ? ($casos['expediente'][$o['expediente_id']] ?? null) : ($casos['tramite'][$o['tramite_id']] ?? null);
            $carpeta = $caso?->carpeta ?? 'DOCUMENTOS/SIN CASO';

            $extension = strtolower(pathinfo($o['ruta'], PATHINFO_EXTENSION));
            $nombre = $this->nombreSeguro(($o['fecha'] ? $o['fecha']->format('Y-m-d') . ' - ' : '') . ($o['titulo'] ?: 'Documento'));
            $enZip = $this->rutaLibre($carpeta . '/' . $nombre, $extension);

            $existe = $disco->exists($o['ruta']);
            if ($existe) {
                $zip->addFile($disco->path($o['ruta']), $enZip);
                if (in_array($extension, self::SIN_COMPRIMIR, true)) {
                    $zip->setCompressionName($enZip, ZipArchive::CM_STORE);
                }
                $incluidos++;
            } else {
                $faltantes++;
            }

            $detalle[] = [
                $caso ? ($o['expediente_id'] ? 'Expediente' : 'Trámite') : '—',
                $caso?->codigo ?? '—',
                $caso?->cliente ?? '—',
                $o['tipo'],
                $o['titulo'],
                $existe ? $enZip : '',
                $o['ruta'],
                $existe ? 'Incluido' : 'No se encontró el archivo en el servidor',
            ];
        }

        return compact('incluidos', 'faltantes', 'detalle');
    }

    private function rutaLibre(string $base, string $extension): string
    {
        $sufijo = $extension !== '' ? ".{$extension}" : '';
        $ruta = $base . $sufijo;

        for ($n = 2; isset($this->usadas[mb_strtolower($ruta)]); $n++) {
            $ruta = "{$base} ({$n}){$sufijo}";
        }
        $this->usadas[mb_strtolower($ruta)] = true;

        return $ruta;
    }

    // Nombre válido para una carpeta o archivo en Windows (ej. "115/24" → "115-24").
    private function nombreSeguro(string $nombre): string
    {
        $nombre = preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/u', '-', $nombre);
        $nombre = trim(preg_replace('/\s+/u', ' ', $nombre), " .-");

        return mb_substr($nombre, 0, 90) ?: 'Sin nombre';
    }

    // ── Planilla con la información del sistema ──────────────────

    private function planillas(array $casos, array $archivos): XlsxSimple
    {
        $libro = new XlsxSimple();
        $caso = function ($registro) use ($casos) {
            if ($registro->expediente_id) {
                return ['Expediente', $casos['expediente'][$registro->expediente_id]->codigo ?? "#{$registro->expediente_id}",
                    $casos['expediente'][$registro->expediente_id]->cliente ?? '—'];
            }
            if ($registro->tramite_id) {
                return ['Trámite', $casos['tramite'][$registro->tramite_id]->codigo ?? "#{$registro->tramite_id}",
                    $casos['tramite'][$registro->tramite_id]->cliente ?? '—'];
            }
            return ['—', '—', '—'];
        };

        $gastos = Gasto::with(['cobros', 'tipoGasto', 'usuario'])->orderBy('fecha')->get();
        $cobros = Cobro::with(['gasto', 'usuario'])->orderBy('fecha')->get();

        // Totales por caso y por cliente, calculados una sola vez.
        $totales = [];
        foreach ($gastos as $g) {
            $totales[$g->expediente_id ? "e{$g->expediente_id}" : "t{$g->tramite_id}"]['gastos'][] = (float) $g->monto;
        }
        foreach ($cobros as $c) {
            $totales[$c->expediente_id ? "e{$c->expediente_id}" : "t{$c->tramite_id}"]['cobros'][] = (float) $c->monto;
        }
        $montos = function (string $clave) use ($totales) {
            $g = array_sum($totales[$clave]['gastos'] ?? []);
            $c = array_sum($totales[$clave]['cobros'] ?? []);
            return [round($g, 2), round($c, 2), round($g - $c, 2)];
        };

        $expedientes = Expediente::withTrashed()->with(['cliente' => fn ($q) => $q->withTrashed(), 'estadoExpediente', 'abogado'])->orderBy('numero')->get();
        $tramites = Tramite::with(['cliente' => fn ($q) => $q->withTrashed(), 'tipoTramite', 'responsable', 'institucionPublica'])->orderBy('codigo')->get();

        $porCliente = [];
        foreach ($expedientes as $e) {
            $porCliente[$e->cliente_id]['exp'] = ($porCliente[$e->cliente_id]['exp'] ?? 0) + 1;
            foreach ($montos("e{$e->id}") as $i => $m) {
                $porCliente[$e->cliente_id]['m'][$i] = ($porCliente[$e->cliente_id]['m'][$i] ?? 0) + $m;
            }
        }
        foreach ($tramites as $t) {
            $porCliente[$t->cliente_id]['tra'] = ($porCliente[$t->cliente_id]['tra'] ?? 0) + 1;
            foreach ($montos("t{$t->id}") as $i => $m) {
                $porCliente[$t->cliente_id]['m'][$i] = ($porCliente[$t->cliente_id]['m'][$i] ?? 0) + $m;
            }
        }

        $libro->hoja('Clientes',
            ['Cliente', 'Tipo', 'C.I./NIT', 'Teléfono', 'Email', 'Dirección', 'Estado', 'Expedientes', 'Trámites', 'Total gastos (Bs)', 'Total cobrado (Bs)', 'Saldo pendiente (Bs)'],
            Cliente::withTrashed()->orderBy('nombre')->get()->map(fn (Cliente $c) => [
                $c->nombre_completo,
                $c->tipo === 'persona_juridica' ? 'Persona jurídica' : 'Persona natural',
                (string) $c->dni,
                (string) $c->telefono,
                $c->email,
                $c->direccion,
                $c->trashed() ? 'Eliminado' : ($c->activo ? 'Activo' : 'Inactivo'),
                $porCliente[$c->id]['exp'] ?? 0,
                $porCliente[$c->id]['tra'] ?? 0,
                round($porCliente[$c->id]['m'][0] ?? 0, 2),
                round($porCliente[$c->id]['m'][1] ?? 0, 2),
                round($porCliente[$c->id]['m'][2] ?? 0, 2),
            ]),
            [9, 10, 11]);

        $libro->hoja('Expedientes',
            ['N° expediente', 'Carátula', 'Cliente', 'Estado', 'Tipo de causa', 'Tipo de proceso', 'Juzgado', 'Abogado', 'Encargado actual', 'Fecha de recepción', 'Monto reclamado (Bs)', 'Total gastos (Bs)', 'Total cobrado (Bs)', 'Saldo pendiente (Bs)', 'Eliminado'],
            $expedientes->map(fn (Expediente $e) => array_merge([
                (string) $e->numero,
                $e->caratula,
                $e->cliente?->nombre_completo,
                $e->estado_label,
                $e->tipo_causa,
                $e->tipo_proceso,
                $e->juzgado,
                $e->abogado?->nombre,
                $e->encargado_actual,
                $e->fecha_recepcion,
                $e->monto_reclamado !== null ? (float) $e->monto_reclamado : null,
            ], $montos("e{$e->id}"), [$e->trashed() ? 'Sí' : ''])),
            [10, 11, 12, 13]);

        $libro->hoja('Trámites',
            ['Código', 'Trámite', 'Cliente', 'Tipo', 'Estado', 'Prioridad', 'Responsable', 'Institución', 'Fecha de inicio', 'Fin aproximado', 'Total gastos (Bs)', 'Total cobrado (Bs)', 'Saldo pendiente (Bs)', 'Observaciones'],
            $tramites->map(fn (Tramite $t) => array_merge([
                $t->codigo,
                $t->nombre,
                $t->cliente?->nombre_completo,
                $t->tipoTramite?->nombre,
                $t->estado_label,
                $t->prioridad ? ucfirst($t->prioridad) : null,
                $t->responsable?->nombre,
                $t->institucionPublica?->nombre,
                $t->fecha_inicio,
                $t->fecha_fin_aproximada,
            ], $montos("t{$t->id}"), [$t->observaciones])),
            [10, 11, 12]);

        $libro->hoja('Actuaciones',
            ['Fecha', 'Caso', 'N° / Código', 'Cliente', 'Tipo de actuación', 'Título', 'Descripción', 'Registrado por', 'Requiere respuesta', 'Respondido', 'Tiene adjunto'],
            Seguimiento::with(['tipoActuacion', 'usuario'])->orderBy('fecha_actuacion')->get()->map(fn (Seguimiento $s) => array_merge(
                [$s->fecha_actuacion],
                $caso($s),
                [
                    $s->tipoActuacion?->nombre,
                    $s->titulo,
                    $s->descripcion,
                    $s->usuario?->name,
                    $s->requiere_respuesta ? 'Sí' : 'No',
                    $s->requiere_respuesta ? ($s->respondido ? 'Sí' : 'No') : '',
                    $s->archivo_adjunto ? 'Sí' : 'No',
                ]
            )));

        $libro->hoja('Gastos',
            ['Fecha', 'Caso', 'N° / Código', 'Cliente', 'Tipo de gasto', 'Concepto', 'Monto (Bs)', 'Cobrado (Bs)', 'Pendiente (Bs)', 'Cargado por'],
            $gastos->map(fn (Gasto $g) => array_merge(
                [$g->fecha],
                $caso($g),
                [
                    $g->tipoGasto?->nombre,
                    $g->concepto,
                    (float) $g->monto,
                    round((float) $g->cobros->sum('monto'), 2),
                    round(max(0, (float) $g->monto - (float) $g->cobros->sum('monto')), 2),
                    $g->usuario?->name,
                ]
            )),
            [6, 7, 8]);

        $libro->hoja('Cobros',
            ['Fecha', 'Caso', 'N° / Código', 'Cliente', 'Monto (Bs)', 'Método de pago', 'Gasto cobrado', 'Registrado por'],
            $cobros->map(fn (Cobro $c) => array_merge(
                [$c->fecha],
                $caso($c),
                [(float) $c->monto, $c->metodo_pago ? ucfirst($c->metodo_pago) : null, $c->gasto?->concepto ?? 'Pago a cuenta', $c->usuario?->name]
            )),
            [4]);

        // Para poder devolver cada archivo a su lugar si alguna vez hay que restaurar.
        $libro->hoja('Archivos',
            ['Caso', 'N° / Código', 'Cliente', 'Origen', 'Título', 'Ubicación en este respaldo', 'Ruta en el sistema', 'Estado'],
            $archivos);

        return $libro;
    }

    // ── Base de datos completa (.sql) ────────────────────────────

    // mysqldump es lo más fiel para restaurar, pero muchos hostings compartidos no lo
    // dejan ejecutar desde PHP: en ese caso se arma el volcado tabla por tabla.
    private function respaldarBase(string $destino): string
    {
        if ($this->conMysqldump($destino)) {
            return 'mysqldump';
        }

        $this->volcadoPhp($destino);
        return 'volcado PHP';
    }

    private function conMysqldump(string $destino): bool
    {
        $db = config('database.connections.' . config('database.default'));

        if (! in_array($db['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            return false;
        }

        try {
            $resultado = Process::timeout(600)
                // La contraseña por variable de entorno, no en la línea de comandos
                // (que puede verse en la lista de procesos del servidor).
                ->env(['MYSQL_PWD' => (string) ($db['password'] ?? '')])
                ->run([
                    env('MYSQLDUMP_PATH', 'mysqldump'),
                    '--single-transaction',
                    '--routines',
                    '--no-tablespaces',
                    '--default-character-set=utf8mb4',
                    '--host=' . ($db['host'] ?? '127.0.0.1'),
                    '--port=' . ($db['port'] ?? 3306),
                    '--user=' . ($db['username'] ?? ''),
                    '--result-file=' . $destino,
                    $db['database'],
                ]);
        } catch (Throwable) {
            return false;
        }

        return $resultado->successful() && is_file($destino) && filesize($destino) > 0;
    }

    private function volcadoPhp(string $destino): void
    {
        $pdo    = DB::connection()->getPdo();
        $salida = fopen($destino, 'wb');

        fwrite($salida, "-- Respaldo generado por el sistema el " . now()->format('d/m/Y H:i') . "\n");
        fwrite($salida, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        $tablas = collect(DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"))
            ->map(fn ($fila) => array_values((array) $fila)[0]);

        foreach ($tablas as $tabla) {
            $crear = array_values((array) DB::selectOne("SHOW CREATE TABLE `{$tabla}`"))[1];
            fwrite($salida, "DROP TABLE IF EXISTS `{$tabla}`;\n{$crear};\n\n");

            $lote = [];
            foreach (DB::table($tabla)->cursor() as $fila) {
                $valores = array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), (array) $fila);
                $lote[] = '(' . implode(',', $valores) . ')';

                if (count($lote) === 200) {
                    fwrite($salida, "INSERT INTO `{$tabla}` VALUES\n" . implode(",\n", $lote) . ";\n");
                    $lote = [];
                }
            }
            if ($lote) {
                fwrite($salida, "INSERT INTO `{$tabla}` VALUES\n" . implode(",\n", $lote) . ";\n");
            }
            fwrite($salida, "\n");
        }

        fwrite($salida, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($salida);
    }

    // ── Utilidades ───────────────────────────────────────────────

    private function temporal(string $extension): string
    {
        $ruta = self::directorio() . '/.tmp-' . uniqid() . ".{$extension}";
        $this->temporales[] = $ruta;

        return $ruta;
    }

    private function borrarViejos(int $dias): void
    {
        $corte = now()->subDays($dias)->getTimestamp();
        $borrados = 0;

        foreach (File::files(self::directorio()) as $archivo) {
            if (str_starts_with($archivo->getFilename(), 'respaldo-') && $archivo->getMTime() < $corte) {
                File::delete($archivo->getPathname());
                $borrados++;
            }
        }

        if ($borrados) {
            $this->line("Se borraron {$borrados} respaldo(s) de más de {$dias} días.");
        }
    }

    public static function tamanio(int $bytes): string
    {
        return $bytes >= 1048576
            ? number_format($bytes / 1048576, 1) . ' MB'
            : number_format($bytes / 1024, 0) . ' KB';
    }
}
