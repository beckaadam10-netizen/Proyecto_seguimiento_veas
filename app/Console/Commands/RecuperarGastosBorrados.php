<?php

namespace App\Console\Commands;

use App\Models\Bitacora;
use App\Models\Cobro;
use App\Models\Expediente;
use App\Models\Gasto;
use App\Models\Seguimiento;
use App\Models\Tramite;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Antes de la corrección de SeguimientoController::sincronizarGasto(), editar una
// actuación borraba sus gastos (sin pasar por la bitácora) y, según desde dónde se
// editaba, los volvía a crear como gastos nuevos o no los volvía a crear. Los cobros de
// esos gastos quedaban sueltos (gasto_id = null): el caso aparecía "Pagado" con saldo
// negativo, o con gastos "pendientes" que el cliente ya había pagado.
//
// Este comando lo repara con los datos exactos de la bitácora (no por aproximación):
// el alta de cada cobro guarda a qué gasto pertenecía, y el alta (y las ediciones) de
// cada gasto guardan su concepto, monto, fecha, actuación y dueño.
//   - Si el gasto se volvió a crear (misma actuación, concepto y monto, sin cobros), los
//     cobros se vinculan a ese gasto recreado, que recupera su dueño y su fecha de alta.
//   - Si no se volvió a crear, se restaura el gasto original con su mismo id y datos.
class RecuperarGastosBorrados extends Command
{
    protected $signature = 'app:recuperar-gastos-borrados
        {--dry-run : Solo mostrar qué se haría, sin escribir nada}
        {--expediente= : Reparar solo este expediente (id)}
        {--tramite= : Reparar solo este trámite (id)}';

    protected $description = 'Recupera, desde la bitácora, los gastos borrados al editar actuaciones y les vuelve a vincular sus cobros';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $sueltos = Cobro::whereNull('gasto_id')
            ->when($this->option('expediente'), fn ($q, $id) => $q->where('expediente_id', $id))
            ->when($this->option('tramite'), fn ($q, $id) => $q->where('tramite_id', $id))
            ->orderBy('id')
            ->get();

        if ($sueltos->isEmpty()) {
            $this->info('No hay cobros sin gasto: no hay nada que recuperar.');

            return self::SUCCESS;
        }

        // gasto_id original de cada cobro suelto, según su alta en la bitácora.
        $altasCobro = Bitacora::where('modelo', 'Cobro')->where('accion', 'creado')
            ->whereIn('modelo_id', $sueltos->pluck('id'))
            ->get()
            ->keyBy('modelo_id');

        $porGastoOriginal = $sueltos->groupBy(fn (Cobro $c) => $altasCobro->get($c->id)?->datos_nuevos['gasto_id'] ?? 0);

        $usados = [];
        $vinculados = $restaurados = $sinDatos = 0;

        foreach ($porGastoOriginal as $gastoOriginalId => $cobros) {
            $listaCobros = $cobros->map(fn (Cobro $c) => "#{$c->id} ({$c->monto} Bs)")->implode(', ');

            if (! $gastoOriginalId) {
                $this->warn("  Cobros {$listaCobros}: se registraron sin gasto (cobro general) o no figuran en la bitácora. No se tocan.");
                $sinDatos += $cobros->count();
                continue;
            }

            $original = $this->reconstruirGasto((int) $gastoOriginalId);

            if (! $original) {
                $this->warn("  Cobros {$listaCobros}: el gasto #{$gastoOriginalId} no figura en la bitácora. No se tocan.");
                $sinDatos += $cobros->count();
                continue;
            }

            $caso = $original['expediente_id']
                ? 'Expediente ' . (Expediente::withTrashed()->find($original['expediente_id'])?->numero ?? "#{$original['expediente_id']}")
                : 'Trámite ' . (Tramite::find($original['tramite_id'])?->codigo ?? "#{$original['tramite_id']}");
            $actuacion = $original['seguimiento_id'] ? Seguimiento::find($original['seguimiento_id'])?->titulo : null;
            $caso .= $actuacion ? " · actuación \"{$actuacion}\"" : '';
            $detalle = "{$caso} · gasto #{$gastoOriginalId} \"{$original['concepto']}\" ({$original['monto']} Bs, " . Carbon::parse($original['fecha'])->format('d/m/Y') . ')';

            if (Gasto::whereKey($gastoOriginalId)->exists()) {
                $this->line("  {$detalle}: el gasto existe; se le vuelven a vincular los cobros {$listaCobros}.");
                $destino = (int) $gastoOriginalId;
                $vinculados += $cobros->count();
            } elseif ($recreado = $this->buscarRecreado($original, $usados)) {
                $usados[] = $recreado->id;
                $this->line("  {$detalle}: se volvió a crear como gasto #{$recreado->id}; se le vinculan los cobros {$listaCobros}.");
                $destino = $recreado->id;
                $vinculados += $cobros->count();

                if (! $dryRun) {
                    // Recupera el dueño y la fecha de alta originales: el gasto recreado
                    // quedó a nombre de quien editó y como "nuevo" en el Reporte de Pasantes.
                    Gasto::whereKey($recreado->id)->update([
                        'usuario_id'    => $original['usuario_id'],
                        'tipo_gasto_id' => $recreado->tipo_gasto_id ?? $original['tipo_gasto_id'],
                        'created_at'    => $original['created_at'],
                    ]);
                }
            } else {
                $this->line("  {$detalle}: no se volvió a crear; se restaura y se le vinculan los cobros {$listaCobros}.");
                $destino = (int) $gastoOriginalId;
                $restaurados++;
                $vinculados += $cobros->count();

                if (! $dryRun) {
                    DB::table('gastos')->insert([
                        'id'             => $gastoOriginalId,
                        'tramite_id'     => $original['tramite_id'],
                        'expediente_id'  => $original['expediente_id'],
                        'seguimiento_id' => $original['seguimiento_id'],
                        'tipo_gasto_id'  => $original['tipo_gasto_id'],
                        'usuario_id'     => $original['usuario_id'],
                        'concepto'       => $original['concepto'],
                        'monto'          => $original['monto'],
                        'fecha'          => Carbon::parse($original['fecha'])->toDateString(),
                        'created_at'     => $original['created_at'],
                        'updated_at'     => now(),
                    ]);
                }
            }

            if (! $dryRun) {
                Cobro::whereIn('id', $cobros->pluck('id'))->update(['gasto_id' => $destino]);
            }
        }

        $this->info(($dryRun ? '[dry-run] ' : '') . "Listo. Cobros vueltos a vincular: {$vinculados}. Gastos restaurados: {$restaurados}. Cobros sin datos para recuperar: {$sinDatos}.");

        return self::SUCCESS;
    }

    // Último estado conocido del gasto: su alta en la bitácora más cada edición posterior.
    private function reconstruirGasto(int $id): ?array
    {
        $entradas = Bitacora::where('modelo', 'Gasto')->where('modelo_id', $id)
            ->whereIn('accion', ['creado', 'actualizado'])
            ->orderBy('id')
            ->get();

        $alta = $entradas->firstWhere('accion', 'creado');

        if (! $alta) {
            return null;
        }

        $datos = $entradas->reduce(fn ($carry, Bitacora $b) => array_merge($carry, $b->datos_nuevos ?? []), []);

        return [
            'tramite_id'     => $datos['tramite_id'] ?? null,
            'expediente_id'  => $datos['expediente_id'] ?? null,
            'seguimiento_id' => $datos['seguimiento_id'] ?? null,
            'tipo_gasto_id'  => $datos['tipo_gasto_id'] ?? null,
            'usuario_id'     => $datos['usuario_id'] ?? $alta->usuario_id,
            'concepto'       => $datos['concepto'],
            'monto'          => $datos['monto'],
            'fecha'          => $datos['fecha'],
            'created_at'     => $alta->created_at,
        ];
    }

    // El gasto que se creó en lugar del borrado: misma actuación, concepto y monto, sin
    // cobros propios y dado de alta después del original.
    private function buscarRecreado(array $original, array $usados): ?Gasto
    {
        if (! $original['seguimiento_id']) {
            return null;
        }

        return Gasto::where('seguimiento_id', $original['seguimiento_id'])
            ->where('concepto', $original['concepto'])
            ->where('monto', $original['monto'])
            ->where('created_at', '>=', $original['created_at'])
            ->whereDoesntHave('cobros')
            ->whereNotIn('id', $usados)
            ->orderBy('id')
            ->first();
    }
}
