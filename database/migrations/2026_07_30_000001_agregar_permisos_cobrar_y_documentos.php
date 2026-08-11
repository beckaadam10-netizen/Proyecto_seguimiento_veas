<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Nuevos permisos, más finos que "gestionar", para poder delegar sin dar acceso de más:
    // - gastos_cobros.cobrar: registrar/editar cobros, separado de registrar/editar gastos.
    // - documentos.ver / documentos.descargar: hoy los documentos se ven y descargan sin
    //   ningún control de permiso (son links directos al storage público); estos permisos
    //   pasan a exigirse en las rutas nuevas que sirven esos archivos.
    private array $nuevos = [
        'gastos_cobros.cobrar'   => 'Registrar y editar cobros a clientes',
        'documentos.ver'         => 'Ver el contenido de un documento adjunto',
        'documentos.descargar'   => 'Descargar un documento adjunto',
    ];

    public function up(): void
    {
        $ahora = now();
        $ids   = [];

        foreach ($this->nuevos as $nombre => $descripcion) {
            $ids[$nombre] = DB::table('permisos')->insertGetId([
                'nombre'      => $nombre,
                'descripcion' => $descripcion,
                'activo'      => true,
                'created_at'  => $ahora,
                'updated_at'  => $ahora,
            ]);
        }

        // Administrador: acceso total, como con el resto de permisos.
        $adminRoleId = DB::table('roles')->where('nombre', 'Administrador')->value('id');
        if ($adminRoleId) {
            $this->asignar($adminRoleId, array_values($ids), $ahora);
        }

        // Hasta ahora cualquiera que pudiera ver un expediente/trámite podía ver y descargar
        // sus documentos sin restricción, y cualquiera con "gastos_cobros.gestionar" podía
        // cobrar. Para no dejar a nadie sin acceso a algo que ya tenía, los roles que ya
        // tenían el permiso "de origen" heredan el nuevo automáticamente.
        $rolesConGestionarGastos = DB::table('permiso_rol')
            ->join('permisos', 'permisos.id', '=', 'permiso_rol.permiso_id')
            ->where('permisos.nombre', 'gastos_cobros.gestionar')
            ->pluck('permiso_rol.rol_id');

        foreach ($rolesConGestionarGastos as $rolId) {
            $this->asignar($rolId, [$ids['gastos_cobros.cobrar']], $ahora);
        }

        $rolesConVerSeguimientos = DB::table('permiso_rol')
            ->join('permisos', 'permisos.id', '=', 'permiso_rol.permiso_id')
            ->where('permisos.nombre', 'seguimientos.ver')
            ->pluck('permiso_rol.rol_id');

        foreach ($rolesConVerSeguimientos as $rolId) {
            $this->asignar($rolId, [$ids['documentos.ver'], $ids['documentos.descargar']], $ahora);
        }
    }

    private function asignar(int $rolId, array $permisoIds, $ahora): void
    {
        $filas = array_map(fn ($permisoId) => [
            'rol_id'     => $rolId,
            'permiso_id' => $permisoId,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ], $permisoIds);

        if ($filas) {
            DB::table('permiso_rol')->insertOrIgnore($filas);
        }
    }

    public function down(): void
    {
        $permisoIds = DB::table('permisos')->whereIn('nombre', array_keys($this->nuevos))->pluck('id');
        DB::table('permiso_rol')->whereIn('permiso_id', $permisoIds)->delete();
        DB::table('permisos')->whereIn('nombre', array_keys($this->nuevos))->delete();
    }
};
