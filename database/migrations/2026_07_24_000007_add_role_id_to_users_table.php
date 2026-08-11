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
            $table->foreignId('role_id')->nullable()->after('id')
                ->constrained('roles')->nullOnDelete();
        });

        $adminRoleId = DB::table('roles')->insertGetId([
            'nombre'      => 'Administrador',
            'descripcion' => 'Acceso completo al sistema.',
            'activo'      => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        DB::table('users')->update(['role_id' => $adminRoleId]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });

        DB::table('roles')->where('nombre', 'Administrador')->delete();
    }
};
