<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Hasta ahora, borrar registros viejos de la Bitácora (auditoría) usaba el mismo permiso
// que verla (bitacora.ver) — cualquiera que pudiera ver el historial también podía
// purgarlo, sin una acción separada. Se agrega bitacora.eliminar, propio, y lo hereda
// quien ya tenía bitacora.ver, para no cambiar quién puede hacerlo hoy.
return new class extends Migration
{
    public function up(): void
    {
        $ahora = now();

        $permisoId = DB::table('permisos')->insertGetId([
            'nombre'      => 'bitacora.eliminar',
            'descripcion' => 'Eliminar registros viejos de la bitácora',
            'activo'      => true,
            'created_at'  => $ahora,
            'updated_at'  => $ahora,
        ]);

        $rolesConVerBitacora = DB::table('permiso_rol')
            ->join('permisos', 'permisos.id', '=', 'permiso_rol.permiso_id')
            ->where('permisos.nombre', 'bitacora.ver')
            ->pluck('permiso_rol.rol_id');

        $filas = $rolesConVerBitacora->map(fn ($rolId) => [
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
        $id = DB::table('permisos')->where('nombre', 'bitacora.eliminar')->value('id');
        DB::table('permiso_rol')->where('permiso_id', $id)->delete();
        DB::table('permisos')->where('nombre', 'bitacora.eliminar')->delete();
    }
};
