<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->after('id')
                ->constrained('clientes')->nullOnDelete();
        });

        $ahora = now();

        $rolClienteId = DB::table('roles')->where('nombre', 'Cliente')->value('id');
        if (! $rolClienteId) {
            $rolClienteId = DB::table('roles')->insertGetId([
                'nombre'      => 'Cliente',
                'descripcion' => 'Acceso del cliente para ver sus propios trámites y expedientes.',
                'activo'      => true,
                'created_at'  => $ahora,
                'updated_at'  => $ahora,
            ]);
        }

        $permisoIds = DB::table('permisos')
            ->whereIn('nombre', ['tramites.ver', 'expedientes.ver'])
            ->pluck('id');

        foreach ($permisoIds as $permisoId) {
            $yaAsignado = DB::table('permiso_rol')
                ->where('rol_id', $rolClienteId)
                ->where('permiso_id', $permisoId)
                ->exists();

            if (! $yaAsignado) {
                DB::table('permiso_rol')->insert([
                    'rol_id'     => $rolClienteId,
                    'permiso_id' => $permisoId,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cliente_id');
        });

        $rolClienteId = DB::table('roles')->where('nombre', 'Cliente')->value('id');
        if ($rolClienteId) {
            DB::table('permiso_rol')->where('rol_id', $rolClienteId)->delete();
            DB::table('roles')->where('id', $rolClienteId)->delete();
        }
    }
};
