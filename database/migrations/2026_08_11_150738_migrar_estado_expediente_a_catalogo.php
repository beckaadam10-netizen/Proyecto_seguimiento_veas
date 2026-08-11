<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->foreignId('estado_expediente_id')->nullable()->after('estado')
                ->constrained('estados_expediente');
        });

        // Backfill: cada expediente queda apuntando al estado del catálogo con el mismo
        // nombre que su valor de texto actual (ya sembrado en la migración anterior con
        // los mismos 6 nombres que existían hardcodeados).
        $idsPorNombre = DB::table('estados_expediente')->pluck('id', 'nombre');

        DB::table('expedientes')->select('id', 'estado')->orderBy('id')->chunk(200, function ($expedientes) use ($idsPorNombre) {
            foreach ($expedientes as $expediente) {
                $id = $idsPorNombre[ucfirst($expediente->estado)] ?? $idsPorNombre['Activo'];
                DB::table('expedientes')->where('id', $expediente->id)->update(['estado_expediente_id' => $id]);
            }
        });

        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }

    public function down(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->string('estado')->default('activo')->after('estado_expediente_id');
        });

        DB::table('expedientes')
            ->join('estados_expediente', 'estados_expediente.id', '=', 'expedientes.estado_expediente_id')
            ->update(['expedientes.estado' => DB::raw('LOWER(estados_expediente.nombre)')]);

        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('estado_expediente_id');
        });
    }
};
