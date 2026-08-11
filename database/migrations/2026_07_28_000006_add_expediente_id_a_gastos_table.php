<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Un gasto ahora puede pertenecer a un trámite o a un expediente (no ambos).
        DB::statement('ALTER TABLE gastos MODIFY tramite_id BIGINT UNSIGNED NULL');

        Schema::table('gastos', function (Blueprint $table) {
            $table->foreignId('expediente_id')->nullable()->after('tramite_id')
                ->constrained('expedientes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expediente_id');
        });

        DB::statement('ALTER TABLE gastos MODIFY tramite_id BIGINT UNSIGNED NOT NULL');
    }
};
