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
        // Agregar campo municipality
        Schema::table('state_bank_accounts', function (Blueprint $table) {
            $table->string('municipality', 100)->nullable()->after('state_code');
        });

        // Eliminar unique constraint anterior usando raw SQL
        try {
            DB::statement('ALTER TABLE state_bank_accounts DROP INDEX state_bank_accounts_state_code_unique');
        } catch (\Exception $e) {
            // Si el índice no existe con ese nombre, intentar con el nombre generado por Laravel
            try {
                DB::statement('ALTER TABLE state_bank_accounts DROP INDEX `state_code`');
            } catch (\Exception $e2) {
                // Si tampoco existe, continuar
            }
        }

        // Agregar nuevo unique constraint compuesto
        Schema::table('state_bank_accounts', function (Blueprint $table) {
            $table->unique(['state_code', 'municipality'], 'state_bank_accounts_state_municipality_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('state_bank_accounts', function (Blueprint $table) {
            // Eliminar unique constraint compuesto
            $table->dropUnique('state_bank_accounts_state_municipality_unique');

            // Restaurar unique constraint original
            $table->unique(['state_code']);

            // Eliminar columna municipality
            $table->dropColumn('municipality');
        });
    }
};
