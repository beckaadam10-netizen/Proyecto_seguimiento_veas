<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Permiso propio para el Reporte Pasantes, separado de "reportes.ver", para poder
    // controlarlo independientemente del resto de los reportes (ej. dárselo a Pasante
    // sin darle Clientes/Expedientes/Trámites/Gastos y Cobros).
    private string $permiso = 'reportes.pasantes';
    private string $descripcion = 'Ver y generar el Reporte Pasantes';

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

        // Quien ya podía ver reportes lo sigue pudiendo ver (no le sacamos acceso al migrar);
        // de acá en más, es un checkbox aparte en la matriz de permisos.
        $rolesConVerReportes = DB::table('permiso_rol')
            ->join('permisos', 'permisos.id', '=', 'permiso_rol.permiso_id')
            ->where('permisos.nombre', 'reportes.ver')
            ->pluck('permiso_rol.rol_id');

        $filas = $rolesConVerReportes->map(fn ($rolId) => [
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
