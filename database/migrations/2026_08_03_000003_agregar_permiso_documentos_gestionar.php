<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Nuevo permiso para la carga individual de documentos (independiente de los
    // seguimientos): documentos.gestionar habilita el botón "Nuevo documento".
    private string $permiso = 'documentos.gestionar';
    private string $descripcion = 'Subir documentos sueltos de un expediente';

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

        // Administrador: acceso total, como con el resto de permisos.
        $adminRoleId = DB::table('roles')->where('nombre', 'Administrador')->value('id');
        if ($adminRoleId) {
            DB::table('permiso_rol')->insertOrIgnore([
                'rol_id'     => $adminRoleId,
                'permiso_id' => $permisoId,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }

        // Quien ya podía gestionar seguimientos (y por lo tanto adjuntar archivos ahí)
        // hereda el permiso nuevo para no perder capacidad de cargar documentos.
        $rolesConGestionarSeguimientos = DB::table('permiso_rol')
            ->join('permisos', 'permisos.id', '=', 'permiso_rol.permiso_id')
            ->where('permisos.nombre', 'seguimientos.gestionar')
            ->pluck('permiso_rol.rol_id');

        $filas = $rolesConGestionarSeguimientos->map(fn ($rolId) => [
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
        $permisoId = DB::table('permisos')->where('nombre', $this->permiso)->value('id');
        DB::table('permiso_rol')->where('permiso_id', $permisoId)->delete();
        DB::table('permisos')->where('nombre', $this->permiso)->delete();
    }
};
