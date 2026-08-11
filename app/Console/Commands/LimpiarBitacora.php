<?php

namespace App\Console\Commands;

use App\Models\Bitacora;
use Illuminate\Console\Command;

class LimpiarBitacora extends Command
{
    // 6 meses por defecto: suficiente para auditorías típicas sin acumular de más.
    protected $signature = 'bitacora:limpiar {--dias=180 : Antigüedad mínima (en días) para borrar un registro}';

    protected $description = 'Borra los registros de bitácora más viejos que el número de días indicado';

    public function handle(): int
    {
        $dias  = (int) $this->option('dias');
        $corte = now()->subDays($dias);

        $borrados = Bitacora::where('created_at', '<', $corte)->delete();

        $this->info("Bitácora: {$borrados} registro(s) anteriores al " . $corte->format('d/m/Y') . " eliminados.");

        return self::SUCCESS;
    }
}
