<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Reunimos los usuarios ya asignados como abogado/responsable.
        $userIds = collect()
            ->merge(DB::table('expedientes')->whereNotNull('abogado_id')->pluck('abogado_id'))
            ->merge(DB::table('audiencias')->whereNotNull('abogado_id')->pluck('abogado_id'))
            ->merge(DB::table('tramites')->whereNotNull('responsable_id')->pluck('responsable_id'))
            ->unique();

        $mapaUsuarioAbogado = [];
        foreach ($userIds as $userId) {
            $user = DB::table('users')->find($userId);
            if (! $user) {
                continue;
            }

            $mapaUsuarioAbogado[$userId] = DB::table('abogados')->insertGetId([
                'nombre'     => $user->name,
                'email'      => $user->email,
                'activo'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Retargeteamos las foreign keys de "users" a "abogados".
        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropForeign(['abogado_id']);
        });
        Schema::table('audiencias', function (Blueprint $table) {
            $table->dropForeign(['abogado_id']);
        });
        Schema::table('tramites', function (Blueprint $table) {
            $table->dropForeign(['responsable_id']);
        });

        // 3. Actualizamos los valores existentes al nuevo id de abogado.
        foreach ($mapaUsuarioAbogado as $userId => $abogadoId) {
            DB::table('expedientes')->where('abogado_id', $userId)->update(['abogado_id' => $abogadoId]);
            DB::table('audiencias')->where('abogado_id', $userId)->update(['abogado_id' => $abogadoId]);
            DB::table('tramites')->where('responsable_id', $userId)->update(['responsable_id' => $abogadoId]);
        }

        Schema::table('expedientes', function (Blueprint $table) {
            $table->foreign('abogado_id')->references('id')->on('abogados')->nullOnDelete();
        });
        Schema::table('audiencias', function (Blueprint $table) {
            $table->foreign('abogado_id')->references('id')->on('abogados')->nullOnDelete();
        });
        Schema::table('tramites', function (Blueprint $table) {
            $table->foreign('responsable_id')->references('id')->on('abogados')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropForeign(['abogado_id']);
        });
        Schema::table('audiencias', function (Blueprint $table) {
            $table->dropForeign(['abogado_id']);
        });
        Schema::table('tramites', function (Blueprint $table) {
            $table->dropForeign(['responsable_id']);
        });

        DB::table('expedientes')->update(['abogado_id' => null]);
        DB::table('audiencias')->update(['abogado_id' => null]);
        DB::table('tramites')->update(['responsable_id' => null]);

        Schema::table('expedientes', function (Blueprint $table) {
            $table->foreign('abogado_id')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('audiencias', function (Blueprint $table) {
            $table->foreign('abogado_id')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('tramites', function (Blueprint $table) {
            $table->foreign('responsable_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
