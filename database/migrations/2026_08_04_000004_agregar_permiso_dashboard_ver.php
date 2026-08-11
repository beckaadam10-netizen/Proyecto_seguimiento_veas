<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // El Panel de Control (dashboard) nunca tuvo permiso propio: cualquier usuario logueado
    // podía verlo. Se agrega ahora el checkbox para poder controlarlo por rol, pero como ya
    // todos podían entrar, se lo asigna a todos los roles existentes para no sacarle acceso
    // a nadie con la migración (de acá en más, es un checkbox aparte y no afecta a otros
    // módulos como Seguimientos).
    private string $permiso = 'dashboard.ver';
    private string $descripcion = 'Ver el Panel de Control';

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

        $rolesIds = DB::table('roles')->pluck('id');

        $filas = $rolesIds->map(fn ($rolId) => [
            'rol_id' => $rolId, 'permiso_id' => $permisoId, 'created_at' => $ahora, 'updated_at' => $ahora,
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
