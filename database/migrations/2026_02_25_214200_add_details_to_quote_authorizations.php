<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quote_authorizations', function (Blueprint $table) {
            // Campos para ISR
            $table->decimal('old_isr', 15, 2)->nullable()->after('new_price');
            $table->decimal('new_isr', 15, 2)->nullable()->after('old_isr');

            // Campos para Cancelación de Hipoteca
            $table->decimal('old_cancelacion_hipoteca', 15, 2)->nullable()->after('new_isr');
            $table->decimal('new_cancelacion_hipoteca', 15, 2)->nullable()->after('old_cancelacion_hipoteca');

            // Campos para Monto de Crédito
            $table->decimal('old_monto_credito', 15, 2)->nullable()->after('new_cancelacion_hipoteca');
            $table->decimal('new_monto_credito', 15, 2)->nullable()->after('old_monto_credito');
        });

        // Actualizar el ENUM para incluir 'recalculation'
        // En MariaDB/PostgreSQL es más seguro cambiar a string o usar un ALTER explícito
        if (config('database.default') === 'mysql' || config('database.default') === 'mariadb') {
            DB::statement("ALTER TABLE quote_authorizations MODIFY COLUMN change_type ENUM('commission', 'price', 'both', 'recalculation') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_authorizations', function (Blueprint $table) {
            $table->dropColumn([
                'old_isr', 'new_isr',
                'old_cancelacion_hipoteca', 'new_cancelacion_hipoteca',
                'old_monto_credito', 'new_monto_credito'
            ]);
        });

        if (config('database.default') === 'mysql' || config('database.default') === 'mariadb') {
            DB::statement("ALTER TABLE quote_authorizations MODIFY COLUMN change_type ENUM('commission', 'price', 'both') NOT NULL");
        }
    }
};
