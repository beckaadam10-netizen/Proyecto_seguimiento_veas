<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titulo');
            $table->string('fojas')->nullable();
            $table->string('archivo');
            $table->timestamps();
            $table->index(['expediente_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
