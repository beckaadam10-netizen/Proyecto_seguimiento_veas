<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tipos = [
        'escrito'         => 'Escrito',
        'notificacion'    => 'Notificación',
        'audiencia'       => 'Audiencia',
        'resolucion'      => 'Resolución',
        'sentencia'       => 'Sentencia',
        'pericia'         => 'Pericia',
        'oficio'          => 'Oficio',
        'medida_cautelar' => 'Medida cautelar',
        'apelacion'       => 'Apelación',
        'otro'            => 'Otro',
    ];

    public function up(): void
    {
        Schema::table('seguimientos', function (Blueprint $table) {
            $table->foreignId('tipo_actuacion_id')->nullable()->after('tipo_actuacion')
                ->constrained('tipos_actuacion')->nullOnDelete();
        });

        $idsPorEnum = [];
        foreach ($this->tipos as $enumValue => $nombre) {
            $id = DB::table('tipos_actuacion')->where('nombre', $nombre)->value('id');

            if (! $id) {
                $id = DB::table('tipos_actuacion')->insertGetId([
                    'nombre'     => $nombre,
                    'activo'     => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $idsPorEnum[$enumValue] = $id;
        }

        foreach ($idsPorEnum as $enumValue => $id) {
            DB::table('seguimientos')->where('tipo_actuacion', $enumValue)->update(['tipo_actuacion_id' => $id]);
        }

        Schema::table('seguimientos', function (Blueprint $table) {
            $table->dropColumn('tipo_actuacion');
        });
    }

    public function down(): void
    {
        Schema::table('seguimientos', function (Blueprint $table) {
            $table->enum('tipo_actuacion', [
                'escrito', 'notificacion', 'audiencia', 'resolucion', 'sentencia', 'pericia', 'oficio', 'medida_cautelar', 'apelacion', 'otro',
            ])->default('otro')->after('usuario_id');
        });

        Schema::table('seguimientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tipo_actuacion_id');
        });
    }
};
