<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expediente_partes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained('expedientes')->onDelete('cascade');
            $table->enum('tipo', ['demandante', 'demandado']);
            $table->string('nombre');
            $table->string('representante')->nullable();
            $table->string('abogados_registrados')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['expediente_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expediente_partes');
    }
};
