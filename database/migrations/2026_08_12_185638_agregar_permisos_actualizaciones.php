<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// El módulo "Actualizaciones del caso" (notas de avance de expedientes) tenía sus
// acciones prestadas de expedientes.ver/modificar. Se le da su propio set de
// permisos para poder habilitarlo por rol de forma independiente.
return new class extends Migration
{
    private array $permisos = [
        'actualizaciones.ver'       => 'Ver actualizaciones del caso',
        'actualizaciones.crear'     => 'Agregar actualizaciones del caso',
        'actualizaciones.modificar' => 'Editar actualizaciones del caso',
        'actualizaciones.eliminar'  => 'Eliminar actualizaciones del caso',
    ];

    public function up(): void
    {
        $ahora = now();
        $adminRoleId = DB::table('roles')->where('nombre', 'Administrador')->value('id');

        // Quien ya podía modificar expedientes tenía, de hecho, control total sobre
        // las actualizaciones (antes gateadas por expedientes.modificar/ver): hereda
        // los 4 permisos nuevos para no perder capacidad.
        $rolesConModificarExpedientes = DB::table('permiso_rol')
            ->join('permisos', 'permisos.id', '=', 'permiso_rol.permiso_id')
            ->where('permisos.nombre', 'expedientes.modificar')
            ->pluck('permiso_rol.rol_id');

        $rolesConVerExpedientes = DB::table('permiso_rol')
            ->join('permisos', 'permisos.id', '=', 'permiso_rol.permiso_id')
            ->where('permisos.nombre', 'expedientes.ver')
            ->pluck('permiso_rol.rol_id');

        foreach ($this->permisos as $nombre => $descripcion) {
            $permisoId = DB::table('permisos')->insertGetId([
                'nombre'      => $nombre,
                'descripcion' => $descripcion,
                'activo'      => true,
                'created_at'  => $ahora,
                'updated_at'  => $ahora,
            ]);

            $rolesHeredan = $nombre === 'actualizaciones.ver' ? $rolesConVerExpedientes : $rolesConModificarExpedientes;

            $filas = $rolesHeredan->push($adminRoleId)
                ->filter()
                ->unique()
                ->map(fn ($rolId) => [
                    'rol_id'     => $rolId,
                    'permiso_id' => $permisoId,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ])->all();

            if ($filas) {
                DB::table('permiso_rol')->insertOrIgnore($filas);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permisos')->whereIn('nombre', array_keys($this->permisos))->pluck('id');
        DB::table('permiso_rol')->whereIn('permiso_id', $ids)->delete();
        DB::table('permisos')->whereIn('nombre', array_keys($this->permisos))->delete();
    }
};
