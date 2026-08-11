<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tramites', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->foreignId('tipo_tramite_id')->nullable()->constrained('tipos_tramite')->nullOnDelete();
            $table->string('nombre');
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('institucion_publica_id')->nullable()->constrained('instituciones_publicas')->nullOnDelete();
            $table->enum('estado', [
                'iniciado',
                'en_proceso',
                'presentado',
                'observado',
                'aprobado',
                'rechazado',
                'finalizado',
                'cancelado',
            ])->default('iniciado');
            $table->enum('prioridad', ['baja', 'media', 'alta', 'urgente'])->default('media');
            $table->date('fecha_inicio');
            $table->date('fecha_fin_aproximada')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['estado', 'fecha_inicio']);
            $table->index(['cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tramites');
    }
};
