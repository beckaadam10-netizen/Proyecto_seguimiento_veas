<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seguimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained('expedientes')->onDelete('cascade');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('tipo_actuacion', [
                'escrito',
                'notificacion',
                'audiencia',
                'resolucion',
                'sentencia',
                'pericia',
                'oficio',
                'medida_cautelar',
                'apelacion',
                'otro'
            ])->default('otro');
            $table->string('titulo');
            $table->text('descripcion');
            $table->date('fecha_actuacion');
            $table->date('fecha_vencimiento')->nullable()->comment('Fecha límite para responder');
            $table->boolean('requiere_respuesta')->default(false);
            $table->boolean('respondido')->default(false);
            $table->date('fecha_respuesta')->nullable();
            $table->enum('prioridad', ['baja', 'media', 'alta', 'urgente'])->default('media');
            $table->string('archivo_adjunto')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['expediente_id', 'fecha_actuacion']);
            $table->index(['fecha_vencimiento', 'respondido']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seguimientos');
    }
};
