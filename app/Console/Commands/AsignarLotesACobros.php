<?php

namespace App\Console\Commands;

use App\Models\Cobro;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

// Los cobros que ya existían antes de agregar la columna "lote" (o que quedaron sin lote
// por venir de app:reasignar-cobros-a-gastos) no tienen forma de saber a qué click de
// Cobrar pertenecían. Como heurística, los cobros de un mismo trámite/expediente creados
// exactamente en el mismo segundo casi seguro vinieron del mismo envío de formulario (fue
// así incluso antes de esta funcionalidad, porque se crean uno tras otro en el mismo
// request), así que se agrupan por ahí.
class AsignarLotesACobros extends Command
{
    protected $signature = 'app:asignar-lotes-a-cobros {--dry-run : Solo mostrar qué se haría, sin escribir nada}';

    protected $description = 'Agrupa retroactivamente en un mismo lote los cobros existentes que se crearon juntos (mismo trámite/expediente y mismo segundo)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $sinLote = Cobro::whereNull('lote')->orderBy('created_at')->get();

        $grupos = $sinLote->groupBy(function (Cobro $c) {
            $entidad = $c->tramite_id ? "t{$c->tramite_id}" : "e{$c->expediente_id}";

            return $entidad . '|' . $c->created_at->format('Y-m-d H:i:s');
        });

        $lotesCreados = 0;

        foreach ($grupos as $clave => $grupo) {
            $lote = (string) Str::uuid();

            $this->line("  {$clave}: {$grupo->count()} cobro(s) → lote {$lote}");

            if (! $dryRun) {
                Cobro::whereIn('id', $grupo->pluck('id'))->update(['lote' => $lote]);
            }

            $lotesCreados++;
        }

        $this->info(($dryRun ? '[dry-run] ' : '') . "Listo. Cobros sin lote encontrados: {$sinLote->count()}. Lotes asignados: {$lotesCreados}.");

        return self::SUCCESS;
    }
}
