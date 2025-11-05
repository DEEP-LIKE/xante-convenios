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
        Schema::table('clients', function (Blueprint $table) {
            // Agregar campo fecha_registro para almacenar la fecha de creación de HubSpot
            $table->timestamp('fecha_registro')->nullable()->after('hubspot_synced_at')
                ->comment('Fecha de creación del contacto en HubSpot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('fecha_registro');
        });
    }
};
