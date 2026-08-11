<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audiencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained('expedientes')->onDelete('cascade');
            $table->foreignId('abogado_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('titulo');
            $table->enum('tipo', [
                'preliminar',
                'de_vista',
                'oral',
                'conciliacion',
                'pericial',
                'informativa',
                'sentencia',
                'otro'
            ])->default('otro');
            $table->datetime('fecha_hora');
            $table->integer('duracion_estimada')->default(60)->comment('Duración en minutos');
            $table->string('lugar')->nullable();
            $table->string('sala')->nullable();
            $table->enum('estado', [
                'programada',
                'confirmada',
                'realizada',
                'suspendida',
                'reprogramada',
                'cancelada'
            ])->default('programada');
            $table->text('resultado')->nullable()->comment('Resultado o acta de la audiencia');
            $table->date('proxima_fecha')->nullable();
            $table->boolean('notificado_cliente')->default(false);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['expediente_id', 'fecha_hora']);
            $table->index(['fecha_hora', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audiencias');
    }
};
