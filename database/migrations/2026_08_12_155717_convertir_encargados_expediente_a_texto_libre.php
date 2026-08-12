<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Encargado actual/anterior pasan de ser una cuenta de usuario real (select) a texto
// libre: el estudio quiere poder escribir un nombre aunque esa persona no tenga cuenta
// en el sistema todavía.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropForeign('expedientes_encargado_actual_id_foreign');
            $table->dropForeign('expedientes_enc_anterior_id_foreign');
        });

        DB::statement('ALTER TABLE expedientes CHANGE encargado_actual_id encargado_actual VARCHAR(150) NULL');
        DB::statement('ALTER TABLE expedientes CHANGE enc_anterior_id enc_anterior VARCHAR(150) NULL');
    }

    public function down(): void
    {
        DB::statement('UPDATE expedientes SET encargado_actual = NULL');
        DB::statement('UPDATE expedientes SET enc_anterior = NULL');
        DB::statement('ALTER TABLE expedientes CHANGE encargado_actual encargado_actual_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE expedientes CHANGE enc_anterior enc_anterior_id BIGINT UNSIGNED NULL');

        Schema::table('expedientes', function (Blueprint $table) {
            $table->foreign('encargado_actual_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('enc_anterior_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
