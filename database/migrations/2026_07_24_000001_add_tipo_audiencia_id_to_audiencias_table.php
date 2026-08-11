<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tipos = [
        'preliminar'   => 'Preliminar',
        'de_vista'     => 'De vista',
        'oral'         => 'Oral',
        'conciliacion' => 'Conciliación',
        'pericial'     => 'Pericial',
        'informativa'  => 'Informativa',
        'sentencia'    => 'Sentencia',
        'otro'         => 'Otro',
    ];

    public function up(): void
    {
        Schema::table('audiencias', function (Blueprint $table) {
            $table->foreignId('tipo_audiencia_id')->nullable()->after('titulo')
                ->constrained('tipos_audiencia')->nullOnDelete();
        });

        $idsPorEnum = [];
        foreach ($this->tipos as $enumValue => $nombre) {
            $id = DB::table('tipos_audiencia')->where('nombre', $nombre)->value('id');

            if (! $id) {
                $id = DB::table('tipos_audiencia')->insertGetId([
                    'nombre'     => $nombre,
                    'activo'     => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $idsPorEnum[$enumValue] = $id;
        }

        foreach ($idsPorEnum as $enumValue => $id) {
            DB::table('audiencias')->where('tipo', $enumValue)->update(['tipo_audiencia_id' => $id]);
        }

        Schema::table('audiencias', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('audiencias', function (Blueprint $table) {
            $table->enum('tipo', [
                'preliminar', 'de_vista', 'oral', 'conciliacion', 'pericial', 'informativa', 'sentencia', 'otro',
            ])->default('otro')->after('titulo');
        });

        Schema::table('audiencias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tipo_audiencia_id');
        });
    }
};
