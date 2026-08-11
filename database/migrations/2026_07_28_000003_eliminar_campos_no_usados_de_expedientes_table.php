<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropColumn(['fecha_vencimiento', 'fecha_inicio', 'secretaria', 'fuero', 'observaciones']);
        });
    }

    public function down(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->date('fecha_inicio')->nullable()->after('tipo_proceso');
            $table->date('fecha_vencimiento')->nullable()->after('fecha_recepcion');
            $table->string('secretaria')->nullable()->after('juzgado');
            $table->string('fuero')->nullable()->after('secretaria');
            $table->text('observaciones')->nullable()->after('descripcion');
        });
    }
};
