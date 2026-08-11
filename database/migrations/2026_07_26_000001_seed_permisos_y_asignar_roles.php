<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Módulo => etiqueta legible. Define también el orden en que se muestran en la matriz.
    private array $modulos = [
        'clientes'       => 'Clientes',
        'abogados'       => 'Abogados',
        'expedientes'    => 'Expedientes',
        'tramites'       => 'Trámites',
        'gastos_cobros'  => 'Gastos y Cobros',
        'seguimientos'   => 'Seguimientos',
        'audiencias'     => 'Audiencias',
        'parametros'     => 'Parámetros',
        'administracion' => 'Administración',
    ];

    private array $descripciones = [
        'ver'        => 'Ver el listado y el detalle de {modulo}',
        'gestionar'  => 'Crear y editar {modulo}',
        'eliminar'   => 'Eliminar {modulo}',
    ];

    public function up(): void
    {
        $ahora = now();
        $ids   = [];

        foreach ($this->modulos as $modulo => $etiqueta) {
            foreach (['ver', 'gestionar', 'eliminar'] as $accion) {
                $nombre = "{$modulo}.{$accion}";
                $ids[$nombre] = DB::table('permisos')->insertGetId([
                    'nombre'      => $nombre,
                    'descripcion' => str_replace('{modulo}', $etiqueta, $this->descripciones[$accion]),
                    'activo'      => true,
                    'created_at'  => $ahora,
                    'updated_at'  => $ahora,
                ]);
            }
        }

        // Reportes es de solo lectura: no tiene gestionar/eliminar.
        $ids['reportes.ver'] = DB::table('permisos')->insertGetId([
            'nombre'      => 'reportes.ver',
            'descripcion' => 'Ver los reportes y descargarlos en PDF',
            'activo'      => true,
            'created_at'  => $ahora,
            'updated_at'  => $ahora,
        ]);

        // ── Administrador: acceso total ──────────────────────────────
        $adminRoleId = DB::table('roles')->where('nombre', 'Administrador')->value('id');
        if ($adminRoleId) {
            $this->asignar($adminRoleId, array_values($ids), $ahora);
        }

        // ── Abogado: operativo en todos los módulos de trabajo, sin administración ──
        $abogadoRoleId = DB::table('roles')->where('nombre', 'Abogado')->value('id');
        if ($abogadoRoleId) {
            $this->asignar($abogadoRoleId, array_intersect_key($ids, array_flip([
                'clientes.ver', 'clientes.gestionar',
                'abogados.ver',
                'expedientes.ver', 'expedientes.gestionar',
                'tramites.ver', 'tramites.gestionar',
                'gastos_cobros.ver', 'gastos_cobros.gestionar',
                'seguimientos.ver', 'seguimientos.gestionar', 'seguimientos.eliminar',
                'audiencias.ver', 'audiencias.gestionar',
                'parametros.ver',
                'reportes.ver',
            ])), $ahora);
        }

        // ── Pasante: mayormente lectura, puede cargar seguimientos ───
        $pasanteRoleId = DB::table('roles')->where('nombre', 'Pasante')->value('id');
        if ($pasanteRoleId) {
            $this->asignar($pasanteRoleId, array_intersect_key($ids, array_flip([
                'clientes.ver',
                'abogados.ver',
                'expedientes.ver',
                'tramites.ver',
                'seguimientos.ver', 'seguimientos.gestionar',
                'audiencias.ver',
                'reportes.ver',
            ])), $ahora);
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
            DB::table('permiso_rol')->insert($filas);
        }
    }

    public function down(): void
    {
        $nombres = [];
        foreach (array_keys($this->modulos) as $modulo) {
            foreach (['ver', 'gestionar', 'eliminar'] as $accion) {
                $nombres[] = "{$modulo}.{$accion}";
            }
        }
        $nombres[] = 'reportes.ver';

        $permisoIds = DB::table('permisos')->whereIn('nombre', $nombres)->pluck('id');
        DB::table('permiso_rol')->whereIn('permiso_id', $permisoIds)->delete();
        DB::table('permisos')->whereIn('nombre', $nombres)->delete();
    }
};
