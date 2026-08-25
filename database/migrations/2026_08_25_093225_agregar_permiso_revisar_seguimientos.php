<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Marcar una actuación como revisada (Historial de Revisión) estaba hardcodeado a
// "rol Abogado o Administrador" (in_array en SeguimientoController::marcarRevisado).
// Se pasa a un permiso propio (seguimientos.revisar) editable desde la matriz de Roles.
// Los roles que hoy se llaman "Abogado" o "Administrador" lo heredan automáticamente
// para no perder la capacidad que ya tenían.
return new class extends Migration
{
    public function up(): void
    {
        $ahora = now();

        $permisoId = DB::table('permisos')->insertGetId([
            'nombre'      => 'seguimientos.revisar',
            'descripcion' => 'Marcar una actuación como revisada',
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
        $id = DB::table('permisos')->where('nombre', 'seguimientos.revisar')->value('id');
        DB::table('permiso_rol')->where('permiso_id', $id)->delete();
        DB::table('permisos')->where('nombre', 'seguimientos.revisar')->delete();
    }
};
