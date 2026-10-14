<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Constancia de cuando administración corrige el "Hasta" de un período que se generó
    // con una fecha futura (ver ReporteController::pasantesCorregirPeriodo).
    public function up(): void
    {
        Schema::table('reportes_pasantes_generados', function (Blueprint $table) {
            $table->date('hasta_original')->nullable()->after('hasta');
            $table->foreignId('corregido_por')->nullable()->after('revisado_at')->constrained('users')->nullOnDelete();
            $table->timestamp('corregido_at')->nullable()->after('corregido_por');
        });
    }

    public function down(): void
    {
        Schema::table('reportes_pasantes_generados', function (Blueprint $table) {
            $table->dropConstrainedForeignId('corregido_por');
            $table->dropColumn(['hasta_original', 'corregido_at']);
        });
    }
};
