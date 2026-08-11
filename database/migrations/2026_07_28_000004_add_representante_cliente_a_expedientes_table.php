<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->string('representante_cliente')->nullable()->after('procedimiento');
            $table->json('abogados_cliente')->nullable()->after('representante_cliente');
        });
    }

    public function down(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropColumn(['representante_cliente', 'abogados_cliente']);
        });
    }
};
