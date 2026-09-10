<?php

namespace App\Console\Commands;

use App\Models\Cobro;
use App\Models\Expediente;
use App\Models\Tramite;
use Illuminate\Console\Command;

// Antes de esta corrección, un cobro "total" o "abono" se registraba suelto (sin
// gasto_id), así que los gastos que en realidad cubría seguían mostrándose como
// "pendientes" para siempre en el modal de Cobrar y en el detalle del trámite/expediente.
// Este comando corrige los datos ya existentes: reparte cada cobro suelto entre los
// gastos pendientes de su trámite/expediente, del más antiguo al más nuevo (FIFO), igual
// que hace CobroController::distribuirCobro() para los cobros nuevos a partir de ahora.
class ReasignarCobrosAGastos extends Command
{
    protected $signature = 'app:reasignar-cobros-a-gastos {--dry-run : Solo mostrar qué se haría, sin escribir nada}';

    protected $description = 'Vincula retroactivamente los cobros sueltos (sin gasto_id) a los gastos que cubrieron, del más antiguo al más nuevo';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $entidades = collect()
            ->concat(Tramite::has('cobros')->with(['gastos.cobros', 'cobros'])->get())
            ->concat(Expediente::has('cobros')->with(['gastos.cobros', 'cobros'])->get());

        $cobrosCreados   = 0;
        $cobrosBorrados  = 0;
        $entidadesTocadas = 0;

        foreach ($entidades as $entidad) {
            $sueltos = $entidad->cobros->whereNull('gasto_id')->sortBy([['fecha', 'asc'], ['id', 'asc']]);

            if ($sueltos->isEmpty()) {
                continue;
            }

            $etiqueta = $entidad instanceof Tramite ? "Trámite {$entidad->codigo}" : "Expediente {$entidad->numero}";

            $pendientePorGasto = $entidad->gastos
                ->sortBy([['fecha', 'asc'], ['id', 'asc']])
                ->mapWithKeys(fn ($g) => [$g->id => (float) $g->monto - (float) $g->cobros->sum('monto')]);

            $gastosOrdenados = $entidad->gastos->sortBy([['fecha', 'asc'], ['id', 'asc']]);
            $huboCambios = false;

            foreach ($sueltos as $cobro) {
                $restante = (float) $cobro->monto;

                foreach ($gastosOrdenados as $gasto) {
                    if ($restante <= 0.004) {
                        break;
                    }

                    $pendienteGasto = $pendientePorGasto[$gasto->id] ?? 0;

                    if ($pendienteGasto <= 0.004) {
                        continue;
                    }

                    $aplicar = round(min($pendienteGasto, $restante), 2);

                    $this->line("  {$etiqueta} · cobro #{$cobro->id} ({$cobro->fecha->format('d/m/Y')}): {$aplicar} Bs → gasto #{$gasto->id} ({$gasto->concepto})");

                    if (! $dryRun) {
                        $nuevo = Cobro::create([
                            'tramite_id'    => $cobro->tramite_id,
                            'expediente_id' => $cobro->expediente_id,
                            'gasto_id'      => $gasto->id,
                            'usuario_id'    => $cobro->usuario_id,
                            'monto'         => $aplicar,
                            'fecha'         => $cobro->fecha,
                            'metodo_pago'   => $cobro->metodo_pago,
                        ]);
                        $nuevo->created_at = $cobro->created_at;
                        $nuevo->updated_at = $cobro->updated_at;
                        $nuevo->saveQuietly();
                    }

                    $cobrosCreados++;
                    $restante -= $aplicar;
                    $pendientePorGasto[$gasto->id] = $pendienteGasto - $aplicar;
                    $huboCambios = true;
                }

                if ($restante > 0.004) {
                    // No hay gastos suficientes para absorberlo todo (ej. se borró un gasto
                    // después de cobrarlo): se deja como cobro suelto por el remanente.
                    $this->line("  {$etiqueta} · cobro #{$cobro->id}: queda {$restante} Bs sin gasto asociado (remanente)");
                    if (! $dryRun) {
                        $cobro->update(['monto' => round($restante, 2)]);
                    }
                } else {
                    if (! $dryRun) {
                        $cobro->delete();
                    }
                    $cobrosBorrados++;
                }
            }

            if ($huboCambios) {
                $entidadesTocadas++;
            }
        }

        $this->info(($dryRun ? '[dry-run] ' : '') . "Listo. Cobros nuevos vinculados a gastos: {$cobrosCreados}. Cobros sueltos absorbidos por completo: {$cobrosBorrados}. Trámites/expedientes afectados: {$entidadesTocadas}.");

        return self::SUCCESS;
    }
}
