<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Mismos 6 estados y colores que ya existían hardcodeados en Expediente::estadosLabels()
    // y Expediente::getEstadoColorAttribute(), para no cambiarle el comportamiento a nadie
    // al migrar a catálogo.
    private array $estados = [
        ['nombre' => 'Activo',      'color' => 'green'],
        ['nombre' => 'Suspendido',  'color' => 'yellow'],
        ['nombre' => 'Archivado',   'color' => 'gray'],
        ['nombre' => 'Cerrado',     'color' => 'gray'],
        ['nombre' => 'Ganado',      'color' => 'emerald'],
        ['nombre' => 'Perdido',     'color' => 'red'],
    ];

    public function up(): void
    {
        $ahora = now();

        foreach ($this->estados as $estado) {
            DB::table('estados_expediente')->insert([
                'nombre'     => $estado['nombre'],
                'color'      => $estado['color'],
                'activo'     => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('estados_expediente')->whereIn('nombre', array_column($this->estados, 'nombre'))->delete();
    }
};
