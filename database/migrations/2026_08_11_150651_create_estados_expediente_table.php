<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estados_expediente', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->text('descripcion')->nullable();
            // Nombre de color de Tailwind (sin el prefijo bg-/text-), para pintar el
            // badge en toda la app sin tener el mapeo hardcodeado en cada vista.
            $table->string('color')->default('gray');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estados_expediente');
    }
};
