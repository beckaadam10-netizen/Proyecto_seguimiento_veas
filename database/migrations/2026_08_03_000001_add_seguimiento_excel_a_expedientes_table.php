<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->string('piso')->nullable()->after('juzgado');
            $table->string('direccion')->nullable()->after('piso');
            $table->foreignId('encargado_actual_id')->nullable()->after('abogado_id')->constrained('users')->nullOnDelete();
            $table->foreignId('enc_anterior_id')->nullable()->after('encargado_actual_id')->constrained('users')->nullOnDelete();
        });

        Schema::create('expediente_seguidores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['expediente_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expediente_seguidores');

        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('encargado_actual_id');
            $table->dropConstrainedForeignId('enc_anterior_id');
            $table->dropColumn(['piso', 'direccion']);
        });
    }
};
