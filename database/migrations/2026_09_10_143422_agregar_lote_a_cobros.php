<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // "Lote": agrupa los cobros que se generaron en un mismo click de Cobrar (ej. un
    // "cobro total" que la app reparte entre varios gastos queda con el mismo lote), para
    // poder generar UN solo PDF por cada cobro que se hace, en vez de un PDF con el
    // historial completo o uno por cada gasto individual.
    public function up(): void
    {
        Schema::table('cobros', function (Blueprint $table) {
            $table->uuid('lote')->nullable()->after('gasto_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('cobros', function (Blueprint $table) {
            $table->dropColumn('lote');
        });
    }
};
