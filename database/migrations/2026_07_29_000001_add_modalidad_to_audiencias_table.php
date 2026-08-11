<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audiencias', function (Blueprint $table) {
            $table->enum('modalidad', ['presencial', 'virtual'])->default('presencial')->after('fecha_hora');
        });
    }

    public function down(): void
    {
        Schema::table('audiencias', function (Blueprint $table) {
            $table->dropColumn('modalidad');
        });
    }
};
