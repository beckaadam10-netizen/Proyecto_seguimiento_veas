<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cobros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tramite_id')->constrained('tramites')->cascadeOnDelete();
            $table->foreignId('gasto_id')->nullable()->constrained('gastos')->nullOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('monto', 10, 2);
            $table->date('fecha');
            $table->enum('metodo_pago', ['efectivo', 'qr']);
            $table->timestamps();

            $table->index(['tramite_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cobros');
    }
};
