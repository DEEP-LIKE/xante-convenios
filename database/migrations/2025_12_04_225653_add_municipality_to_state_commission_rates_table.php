<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('state_commission_rates', function (Blueprint $table) {
            $table->string('municipality', 100)->nullable()->after('state_name');
        });

        // Eliminar unique constraint anterior si existe
        try {
            DB::statement('ALTER TABLE state_commission_rates DROP INDEX state_commission_rates_state_code_unique');
        } catch (\Exception $e) {
            try {
                DB::statement('ALTER TABLE state_commission_rates DROP INDEX `state_code`');
            } catch (\Exception $e2) {
                // Si no existe, continuar
            }
        }

        // Crear nuevo unique constraint compuesto
        Schema::table('state_commission_rates', function (Blueprint $table) {
            $table->unique(['state_code', 'municipality'], 'state_municipality_unique');
        });
    }

    public function down(): void
    {
        Schema::table('state_commission_rates', function (Blueprint $table) {
            $table->dropUnique('state_municipality_unique');
            $table->dropColumn('municipality');
            $table->unique('state_code');
        });
    }
};
