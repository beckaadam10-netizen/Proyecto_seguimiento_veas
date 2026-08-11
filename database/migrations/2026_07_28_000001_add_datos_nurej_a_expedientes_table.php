<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->date('fecha_recepcion')->nullable()->after('fecha_inicio');
            $table->string('tipo_proceso')->nullable()->after('tipo_causa');
            $table->string('procedimiento')->nullable()->after('tipo_proceso');
        });
    }

    public function down(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropColumn(['fecha_recepcion', 'tipo_proceso', 'procedimiento']);
        });
    }
};
