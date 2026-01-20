<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar columna temporal
        Schema::table('users', function (Blueprint $table) {
            $table->string('role_new', 50)->default('ejecutivo')->after('role');
        });

        // Copiar y transformar datos
        DB::statement("UPDATE users SET role_new = CASE 
            WHEN role = 'admin' THEN 'gerencia'
            WHEN role = 'asesor' THEN 'ejecutivo'
            WHEN role = 'viewer' THEN 'ejecutivo'
            ELSE 'ejecutivo'
        END");

        // Eliminar columna antigua
        // Fix for SQLite: Index must be dropped explicitly before dropping the column
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_role_index');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        // Renombrar columna nueva
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role_new', 'role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Agregar columna temporal con ENUM
        DB::statement("ALTER TABLE users ADD COLUMN role_old ENUM('admin', 'asesor', 'viewer') DEFAULT 'asesor' AFTER role");

        // Copiar y transformar datos
        DB::statement("UPDATE users SET role_old = CASE 
            WHEN role = 'gerencia' THEN 'admin'
            WHEN role = 'ejecutivo' THEN 'asesor'
            WHEN role = 'coordinador_fi' THEN 'asesor'
            ELSE 'asesor'
        END");

        // Eliminar columna nueva
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        // Renombrar columna antigua
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role_old', 'role');
        });
    }
};
