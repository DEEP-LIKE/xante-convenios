<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            // Renombrar calculation_data a data si existe
            if (Schema::hasColumn('proposals', 'calculation_data')) {
                $table->renameColumn('calculation_data', 'data');
            }

            // Agregar columna linked si no existe
            if (! Schema::hasColumn('proposals', 'linked')) {
                $table->boolean('linked')->default(false)->after('client_id');
            }

            // Agregar created_by si no existe
            if (! Schema::hasColumn('proposals', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            if (Schema::hasColumn('proposals', 'data')) {
                $table->renameColumn('data', 'calculation_data');
            }

            if (Schema::hasColumn('proposals', 'linked')) {
                $table->dropColumn('linked');
            }
        });
    }
};
