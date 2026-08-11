<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seguimientos', function (Blueprint $table) {
            $table->foreignId('tipo_documento_id')->nullable()->after('archivo_adjunto')
                ->constrained('tipos_documento')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('seguimientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tipo_documento_id');
        });
    }
};
