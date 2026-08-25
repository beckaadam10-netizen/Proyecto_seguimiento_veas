<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Hasta ahora, poder elegir la fecha de actuación al registrar/editar un seguimiento
// (en vez de que quede fija en la fecha de hoy) estaba hardcodeado a "es Administrador"
// (User::esAdmin()). Se pasa a un permiso propio (seguimientos.modificar_fecha) para
// poder dárselo a otros roles (ej. Abogado) desde la matriz de Roles, sin tocar código.
// Administrador lo hereda automáticamente para no perder la capacidad que ya tenía.
return new class extends Migration
{
    public function up(): void
    {
        $ahora = now();

        $permisoId = DB::table('permisos')->insertGetId([
            'nombre'      => 'seguimientos.modificar_fecha',
            'descripcion' => 'Elegir la fecha de actuación al registrar/editar un seguimiento',
            'activo'      => true,
            'created_at'  => $ahora,
            'updated_at'  => $ahora,
        ]);

        $adminRoleId = DB::table('roles')->where('nombre', 'Administrador')->value('id');

        if ($adminRoleId) {
            DB::table('permiso_rol')->insertOrIgnore([
                'rol_id'     => $adminRoleId,
                'permiso_id' => $permisoId,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }
    }

    public function down(): void
    {
        $id = DB::table('permisos')->where('nombre', 'seguimientos.modificar_fecha')->value('id');
        DB::table('permiso_rol')->where('permiso_id', $id)->delete();
        DB::table('permisos')->where('nombre', 'seguimientos.modificar_fecha')->delete();
    }
};
