<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // La Bitácora es un módulo de auditoría (quién hizo qué y cuándo): solo de lectura, y
    // solo para Administrador por defecto, como Administración.
    private string $permiso = 'bitacora.ver';
    private string $descripcion = 'Ver la bitácora de auditoría del sistema';

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

        $adminRolId = DB::table('roles')->where('nombre', 'Administrador')->value('id');

        if ($adminRolId) {
            DB::table('permiso_rol')->insertOrIgnore([
                'rol_id' => $adminRolId, 'permiso_id' => $permisoId, 'created_at' => $ahora, 'updated_at' => $ahora,
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
