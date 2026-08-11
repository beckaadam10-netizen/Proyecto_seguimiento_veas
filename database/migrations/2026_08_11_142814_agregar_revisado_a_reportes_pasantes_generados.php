<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reportes_pasantes_generados', function (Blueprint $table) {
            $table->boolean('revisado')->default(false)->after('total');
            $table->timestamp('revisado_at')->nullable()->after('revisado');
        });
    }

    public function down(): void
    {
        Schema::table('reportes_pasantes_generados', function (Blueprint $table) {
            $table->dropColumn(['revisado', 'revisado_at']);
        });
    }
};
