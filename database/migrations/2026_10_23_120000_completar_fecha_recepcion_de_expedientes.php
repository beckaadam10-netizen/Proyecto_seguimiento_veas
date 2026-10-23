<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Expedientes cargados sin fecha de recepción: se completa con el día en que se
    // registraron (lo mismo que hace ahora Expediente::booted() al guardar).
    public function up(): void
    {
        DB::table('expedientes')
            ->whereNull('fecha_recepcion')
            ->update(['fecha_recepcion' => DB::raw('DATE(created_at)')]);
    }

    // No se puede saber cuáles estaban vacíos: no se revierte.
    public function down(): void
    {
    }
};
