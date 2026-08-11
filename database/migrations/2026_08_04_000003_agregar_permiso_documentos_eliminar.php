<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Documentos nunca tuvo permiso de eliminar (el módulo nació sin borrado de
    // documentos sueltos). Se agrega ahora junto con la función de eliminar.
    private string $permiso = 'documentos.eliminar';
    private string $descripcion = 'Eliminar documentos sueltos de un expediente';

    public function up(): void
    {
        $ahora = now();

        $permisoId = DB::table('permisos')->insertGetId([
            'nombre'      => $this->permiso,
            'descripcion' => $this->descripcion,
            'activo'      => true,
            'created_at'  => $ahora,
            'updated_at'  => $ahora,
        ]);

        // Es una capacidad nueva (nadie la tenía antes), así que por defecto solo
        // Administrador la recibe; el resto se asigna a mano desde la matriz de roles.
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
        $permisoId = DB::table('permisos')->where('nombre', $this->permiso)->value('id');
        DB::table('permiso_rol')->where('permiso_id', $permisoId)->delete();
        DB::table('permisos')->where('nombre', $this->permiso)->delete();
    }
};
