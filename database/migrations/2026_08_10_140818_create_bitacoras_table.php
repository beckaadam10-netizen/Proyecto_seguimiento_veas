<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacoras', function (Blueprint $table) {
            $table->id();
            // nullOnDelete: si se borra el usuario, el registro histórico de lo que hizo
            // tiene que seguir existiendo (no tiene sentido borrar la bitácora con él).
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion', 30);
            $table->string('modelo', 60)->nullable();
            $table->unsignedBigInteger('modelo_id')->nullable();
            $table->string('descripcion');
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['modelo', 'modelo_id']);
            $table->index('accion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacoras');
    }
};
