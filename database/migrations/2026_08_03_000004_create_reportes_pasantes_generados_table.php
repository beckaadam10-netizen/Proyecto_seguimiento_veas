<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Registra cada período (desde/hasta) para el que un pasante ya descargó su
    // reporte de gastos, para que no pueda volver a generarlo y cobrarlo dos veces.
    public function up(): void
    {
        Schema::create('reportes_pasantes_generados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->date('desde');
            $table->date('hasta');
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamps();
            $table->index(['usuario_id', 'desde', 'hasta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_pasantes_generados');
    }
};
