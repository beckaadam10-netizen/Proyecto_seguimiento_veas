<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Una actuación ahora puede pertenecer a un expediente o a un trámite.
        DB::statement('ALTER TABLE seguimientos MODIFY expediente_id BIGINT UNSIGNED NULL');

        Schema::table('seguimientos', function (Blueprint $table) {
            $table->foreignId('tramite_id')->nullable()->after('expediente_id')
                ->constrained('tramites')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('seguimientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tramite_id');
        });

        DB::statement('ALTER TABLE seguimientos MODIFY expediente_id BIGINT UNSIGNED NOT NULL');
    }
};
