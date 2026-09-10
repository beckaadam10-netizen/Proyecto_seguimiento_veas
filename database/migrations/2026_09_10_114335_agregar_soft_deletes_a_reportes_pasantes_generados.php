<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Al eliminar un reporte, sus gastos deben seguir bloqueados (ya fueron facturados a
    // administración) en vez de volver a aparecer como "gastos nuevos" en Mis gastos. Por
    // eso se usa soft delete: el período desaparece de las tablas pero sigue existiendo
    // para las consultas que excluyen gastos ya reportados.
    public function up(): void
    {
        Schema::table('reportes_pasantes_generados', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('reportes_pasantes_generados', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
