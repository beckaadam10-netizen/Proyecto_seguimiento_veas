<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expedientes', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique()->comment('Número de expediente');
            $table->string('caratula');
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('abogado_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('tipo_causa', [
                'civil',
                'comercial',
                'laboral',
                'penal',
                'familia',
                'administrativo',
                'constitucional',
                'otro'
            ])->default('civil');
            $table->enum('estado', [
                'activo',
                'suspendido',
                'archivado',
                'cerrado',
                'ganado',
                'perdido'
            ])->default('activo');
            $table->string('juzgado')->nullable();
            $table->string('secretaria')->nullable();
            $table->string('fuero')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_vencimiento')->nullable();
            $table->decimal('monto_reclamado', 15, 2)->nullable();
            $table->text('descripcion')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['estado', 'tipo_causa']);
            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expedientes');
    }
};
