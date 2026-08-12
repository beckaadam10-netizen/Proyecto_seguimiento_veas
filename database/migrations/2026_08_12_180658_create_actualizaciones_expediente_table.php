<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Notas cortas de avance del caso (ej. "Estaba con radicatoria en Sala Social 2...").
// Es más liviano que Seguimiento (actuaciones): sin tipo, sin archivo adjunto, solo
// texto libre con fecha, para ir dejando constancia de cómo va el expediente.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actualizaciones_expediente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('texto');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actualizaciones_expediente');
    }
};
