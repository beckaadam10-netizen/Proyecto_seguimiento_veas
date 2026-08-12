<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\EstadoExpediente;
use App\Models\Expediente;
use App\Models\Seguimiento;
use App\Models\TipoActuacion;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

// Importa expedientes desde el CSV que usa el estudio para llevar el seguimiento en
// Excel. Columnas esperadas, en este orden exacto (por posición, no por nombre —
// el encabezado real tiene dos columnas llamadas "SEGUIMIENTO", una vacía/sin uso
// y otra con las fechas, así que no sirve buscar por nombre):
//
//   0 EXP.               -> caratula
//   1 NUREJ               -> numero
//   2 DEMANDANTE          -> cliente (se crea si no existe) + parte "demandante"
//   3 DEMANDADO           -> parte "demandado"
//   4 TIPO DE PROCESO     -> tipo_proceso
//   5 JUZGADO             -> juzgado (también se usa para adivinar tipo_causa)
//   6 PISO                -> piso
//   7 DIRRECCION          -> direccion
//   8 ENCARGADO ACTUAL    -> encargado_actual (texto libre)
//   9 SEGUIMIENTO         -> (sin uso, columna suelta del Excel original)
//   10 ENC. ANTERIOR      -> enc_anterior (texto libre)
//   11 ESTADO DEL PROCESO -> descripcion
//   12 en adelante        -> fechas de seguimiento (una por columna, formato d/m/Y),
//                            cada una genera una Actuación tipo "seguimiento al libro"
//
// Los clientes nuevos se crean con un NIT provisorio (PEND-00001, PEND-00002...)
// porque el Excel no trae DNI: hay que completarlo a mano después.
class ImportarExpedientesCsv extends Command
{
    protected $signature = 'expedientes:importar-csv
        {archivo : Ruta al CSV a importar}
        {--dry-run : Solo mostrar qué se importaría, sin guardar nada}';

    protected $description = 'Importa expedientes, sus partes y su historial de seguimiento desde un CSV exportado del Excel del estudio';

    public function handle(): int
    {
        $ruta = $this->argument('archivo');

        if (! file_exists($ruta)) {
            $this->error("No se encontró el archivo: {$ruta}");
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $tipoSeguimientoAlLibro = TipoActuacion::where('nombre', 'seguimiento al libro')->first();
        if (! $tipoSeguimientoAlLibro) {
            $this->error('No existe el tipo de actuación "seguimiento al libro". Creálo primero en Parámetros.');
            return self::FAILURE;
        }

        $estadoActivo = EstadoExpediente::where('nombre', 'Activo')->first();
        if (! $estadoActivo) {
            $this->error('No existe el estado de expediente "Activo".');
            return self::FAILURE;
        }

        $filas = $this->leerFilas($ruta);

        if (empty($filas)) {
            $this->warn('El archivo no tiene filas de datos.');
            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[DRY-RUN] ' : '') . 'Procesando ' . count($filas) . ' fila(s)...');

        $creados = 0;
        $omitidos = 0;

        foreach ($filas as $i => $fila) {
            $numeroFila = $i + 2; // +1 por índice 0, +1 por la fila de encabezado

            $numero = trim($fila[1] ?? '');
            $caratula = trim($fila[0] ?? '');

            if ($numero === '') {
                $this->warn("Fila {$numeroFila}: sin NUREJ, se omite.");
                $omitidos++;
                continue;
            }

            if (Expediente::where('numero', $numero)->exists()) {
                $this->warn("Fila {$numeroFila}: ya existe un expediente con NUREJ {$numero}, se omite.");
                $omitidos++;
                continue;
            }

            $demandante = trim($fila[2] ?? '');
            $demandado = trim($fila[3] ?? '');
            $tipoProceso = trim($fila[4] ?? '');
            $juzgado = trim($fila[5] ?? '');
            $piso = trim($fila[6] ?? '');
            $direccion = trim($fila[7] ?? '');
            $encargadoActual = trim($fila[8] ?? '');
            $encAnterior = trim($fila[10] ?? '');
            $estadoProceso = trim($fila[11] ?? '');

            $fechasSeguimiento = collect(array_slice($fila, 12))
                ->map(fn ($v) => $this->parsearFecha(trim((string) $v)))
                ->filter()
                ->values();

            if ($demandante === '') {
                $this->warn("Fila {$numeroFila}: sin demandante (no se puede crear el cliente), se omite.");
                $omitidos++;
                continue;
            }

            $this->line("Fila {$numeroFila}: {$caratula} — {$demandante} c/ {$demandado} ({$fechasSeguimiento->count()} fecha(s) de seguimiento)");

            if ($dryRun) {
                $creados++;
                continue;
            }

            $cliente = $this->buscarOCrearCliente($demandante);

            $fechaRecepcion = $fechasSeguimiento->first() ?? Carbon::now();

            $expediente = Expediente::create([
                'numero'             => $numero,
                'caratula'           => $caratula !== '' ? $caratula : "{$demandante} c/ {$demandado}",
                'cliente_id'         => $cliente->id,
                'tipo_causa'         => $this->adivinarTipoCausa($juzgado),
                'tipo_proceso'       => $tipoProceso ?: null,
                'estado_expediente_id' => $estadoActivo->id,
                'juzgado'            => $juzgado ?: null,
                'piso'               => $piso ?: null,
                'direccion'          => $direccion ?: null,
                'encargado_actual'   => $encargadoActual ?: null,
                'enc_anterior'       => $encAnterior ?: null,
                'fecha_recepcion'    => $fechaRecepcion,
                'descripcion'        => $estadoProceso ?: null,
            ]);

            $expediente->partes()->create(['tipo' => 'demandante', 'nombre' => $demandante, 'orden' => 1]);
            if ($demandado !== '') {
                $expediente->partes()->create(['tipo' => 'demandado', 'nombre' => $demandado, 'orden' => 1]);
            }

            $usuarioEncargado = $encargadoActual !== '' ? $this->buscarUsuarioPorNombre($encargadoActual) : null;

            foreach ($fechasSeguimiento as $fecha) {
                Seguimiento::create([
                    'expediente_id'      => $expediente->id,
                    'usuario_id'         => $usuarioEncargado?->id,
                    'tipo_actuacion_id'  => $tipoSeguimientoAlLibro->id,
                    'titulo'             => 'Seguimiento al libro',
                    'descripcion'        => 'Importado desde el Excel de seguimiento.',
                    'fecha_actuacion'    => $fecha,
                    'requiere_respuesta' => false,
                    'prioridad'          => 'media',
                ]);
            }

            $creados++;
        }

        $this->newLine();
        $this->info(($dryRun ? '[DRY-RUN] ' : '') . "Listo: {$creados} expediente(s) " . ($dryRun ? 'para crear' : 'creado(s)') . ", {$omitidos} omitido(s).");

        return self::SUCCESS;
    }

    // Lee el CSV completo salteando la fila de encabezado. Soporta separador coma o
    // punto y coma (Excel en español suele exportar con ;), detectándolo por la
    // primera línea.
    private function leerFilas(string $ruta): array
    {
        $contenido = file_get_contents($ruta);
        // Quita el BOM UTF-8 que agrega Excel al exportar, si está presente.
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);

        $primeraLinea = strtok($contenido, "\n");
        $separador = substr_count($primeraLinea, ';') > substr_count($primeraLinea, ',') ? ';' : ',';

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $contenido);
        rewind($handle);

        $filas = [];
        $esEncabezado = true;
        while (($fila = fgetcsv($handle, 0, $separador)) !== false) {
            if ($esEncabezado) {
                $esEncabezado = false;
                continue;
            }
            if (count(array_filter($fila, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $filas[] = $fila;
        }
        fclose($handle);

        return $filas;
    }

    private function parsearFecha(string $valor): ?Carbon
    {
        if ($valor === '') {
            return null;
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d'] as $formato) {
            try {
                return Carbon::createFromFormat($formato, $valor)->startOfDay();
            } catch (\Exception) {
                continue;
            }
        }

        return null;
    }

    private function adivinarTipoCausa(string $juzgado): string
    {
        $juzgado = Str::lower($juzgado);

        return match (true) {
            str_contains($juzgado, 'laboral')  => 'laboral',
            str_contains($juzgado, 'civil')    => 'civil',
            str_contains($juzgado, 'penal')    => 'penal',
            str_contains($juzgado, 'familia')  => 'familia',
            str_contains($juzgado, 'comercial')=> 'comercial',
            default => 'otro',
        };
    }

    // Reutiliza el cliente si ya existe uno con el mismo nombre (para no duplicar al
    // demandante entre varios expedientes); si no, lo crea con un NIT provisorio.
    private function buscarOCrearCliente(string $nombreCompleto): Cliente
    {
        $existente = Cliente::whereRaw('LOWER(TRIM(nombre)) = ?', [Str::lower(trim($nombreCompleto))])
            ->whereNull('apellido')
            ->first();

        if ($existente) {
            return $existente;
        }

        $siguiente = (int) (Cliente::where('dni', 'like', 'PEND-%')->count()) + 1;
        do {
            $dni = 'PEND-' . str_pad((string) $siguiente, 5, '0', STR_PAD_LEFT);
            $siguiente++;
        } while (Cliente::where('dni', $dni)->exists());

        return Cliente::create([
            'nombre'   => $nombreCompleto,
            'apellido' => null,
            'dni'      => $dni,
            'tipo'     => 'persona_fisica',
            'activo'   => true,
        ]);
    }

    private function buscarUsuarioPorNombre(string $nombre): ?User
    {
        return User::whereRaw('LOWER(name) = ?', [Str::lower(trim($nombre))])->first();
    }
}
