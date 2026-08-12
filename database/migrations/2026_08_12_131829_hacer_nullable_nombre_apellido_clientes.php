<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Persona jurídica no tiene nombre/apellido (usa razon_social), así que estas
// columnas deben admitir NULL. Se usa SQL crudo para no depender de doctrine/dbal.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE clientes MODIFY nombre VARCHAR(255) NULL');
        DB::statement('ALTER TABLE clientes MODIFY apellido VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE clientes SET nombre = '' WHERE nombre IS NULL");
        DB::statement("UPDATE clientes SET apellido = '' WHERE apellido IS NULL");
        DB::statement('ALTER TABLE clientes MODIFY nombre VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE clientes MODIFY apellido VARCHAR(255) NOT NULL');
    }
};
