<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Nuevo módulo "Historial de Revisión": vista global (todos los expedientes/trámites juntos)
// de los seguimientos pendientes/ya revisados, en vez de tener que entrar expediente por
// expediente. Solo para Administrador y Abogado.
return new class extends Migration
{
    public function up(): void
    {
        $ahora = now();

        $permisoId = DB::table('permisos')->insertGetId([
            'nombre'      => 'historial_revision.ver',
            'descripcion' => 'Ver historial de revisión',
            'activo'      => true,
            'created_at'  => $ahora,
            'updated_at'  => $ahora,
        ]);

        $rolesIds = DB::table('roles')->whereIn('nombre', ['Administrador', 'Abogado'])->pluck('id');

        $filas = $rolesIds->map(fn ($rolId) => [
            'rol_id'     => $rolId,
            'permiso_id' => $permisoId,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ])->all();

        if ($filas) {
            DB::table('permiso_rol')->insertOrIgnore($filas);
        }
    }

    public function down(): void
    {
        $id = DB::table('permisos')->where('nombre', 'historial_revision.ver')->value('id');
        DB::table('permiso_rol')->where('permiso_id', $id)->delete();
        DB::table('permisos')->where('nombre', 'historial_revision.ver')->delete();
    }
};
