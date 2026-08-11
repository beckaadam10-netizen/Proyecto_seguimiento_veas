<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Reemplaza el permiso "X.gestionar" (crear Y editar juntos) por dos permisos
    // separados: "X.crear" y "X.modificar", en cada módulo que lo tenía.
    private array $modulos = [
        'clientes'       => 'Clientes',
        'abogados'       => 'Abogados',
        'expedientes'    => 'Expedientes',
        'tramites'       => 'Trámites',
        'gastos_cobros'  => 'Gastos y Cobros',
        'seguimientos'   => 'Seguimientos',
        'audiencias'     => 'Audiencias',
        'documentos'     => 'Documentos',
        'parametros'     => 'Parámetros',
        'administracion' => 'Administración',
    ];

    public function up(): void
    {
        $ahora = now();

        foreach ($this->modulos as $modulo => $etiqueta) {
            $gestionar = DB::table('permisos')->where('nombre', "{$modulo}.gestionar")->first();
            if (! $gestionar) {
                continue;
            }

            $crearId = DB::table('permisos')->insertGetId([
                'nombre'      => "{$modulo}.crear",
                'descripcion' => "Crear {$etiqueta}",
                'activo'      => true,
                'created_at'  => $ahora,
                'updated_at'  => $ahora,
            ]);

            $modificarId = DB::table('permisos')->insertGetId([
                'nombre'      => "{$modulo}.modificar",
                'descripcion' => "Editar {$etiqueta}",
                'activo'      => true,
                'created_at'  => $ahora,
                'updated_at'  => $ahora,
            ]);

            // Todo rol que ya tenía "gestionar" recibe los dos nuevos, para no perder acceso.
            $rolesConGestionar = DB::table('permiso_rol')->where('permiso_id', $gestionar->id)->pluck('rol_id');

            $filas = [];
            foreach ($rolesConGestionar as $rolId) {
                $filas[] = ['rol_id' => $rolId, 'permiso_id' => $crearId, 'created_at' => $ahora, 'updated_at' => $ahora];
                $filas[] = ['rol_id' => $rolId, 'permiso_id' => $modificarId, 'created_at' => $ahora, 'updated_at' => $ahora];
            }
            if ($filas) {
                DB::table('permiso_rol')->insertOrIgnore($filas);
            }

            DB::table('permiso_rol')->where('permiso_id', $gestionar->id)->delete();
            DB::table('permisos')->where('id', $gestionar->id)->delete();
        }
    }

    public function down(): void
    {
        $ahora = now();

        foreach ($this->modulos as $modulo => $etiqueta) {
            $crear = DB::table('permisos')->where('nombre', "{$modulo}.crear")->first();
            $modificar = DB::table('permisos')->where('nombre', "{$modulo}.modificar")->first();
            if (! $crear && ! $modificar) {
                continue;
            }

            $gestionarId = DB::table('permisos')->insertGetId([
                'nombre'      => "{$modulo}.gestionar",
                'descripcion' => "Crear y editar {$etiqueta}",
                'activo'      => true,
                'created_at'  => $ahora,
                'updated_at'  => $ahora,
            ]);

            $rolesIds = collect();
            if ($crear) {
                $rolesIds = $rolesIds->merge(DB::table('permiso_rol')->where('permiso_id', $crear->id)->pluck('rol_id'));
            }
            if ($modificar) {
                $rolesIds = $rolesIds->merge(DB::table('permiso_rol')->where('permiso_id', $modificar->id)->pluck('rol_id'));
            }

            $filas = $rolesIds->unique()->map(fn ($rolId) => [
                'rol_id' => $rolId, 'permiso_id' => $gestionarId, 'created_at' => $ahora, 'updated_at' => $ahora,
            ])->all();
            if ($filas) {
                DB::table('permiso_rol')->insertOrIgnore($filas);
            }

            if ($crear) {
                DB::table('permiso_rol')->where('permiso_id', $crear->id)->delete();
                DB::table('permisos')->where('id', $crear->id)->delete();
            }
            if ($modificar) {
                DB::table('permiso_rol')->where('permiso_id', $modificar->id)->delete();
                DB::table('permisos')->where('id', $modificar->id)->delete();
            }
        }
    }
};
