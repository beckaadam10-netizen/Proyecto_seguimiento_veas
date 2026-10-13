<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Los documentos sueltos (cargados sin pasar por un seguimiento) ahora pueden
// pertenecer a un expediente o a un trámite.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            $table->unsignedBigInteger('expediente_id')->nullable()->change();
            $table->foreignId('tramite_id')->nullable()->after('expediente_id')->constrained()->cascadeOnDelete();
            $table->index(['tramite_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            $table->dropIndex(['tramite_id', 'created_at']);
            $table->dropConstrainedForeignId('tramite_id');
            $table->unsignedBigInteger('expediente_id')->nullable(false)->change();
        });
    }
};
