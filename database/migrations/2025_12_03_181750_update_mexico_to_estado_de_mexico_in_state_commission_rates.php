<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizar el nombre del estado de "México" a "Estado de México"
        DB::table('state_commission_rates')
            ->where('state_name', 'México')
            ->update(['state_name' => 'Estado de México']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir el cambio
        DB::table('state_commission_rates')
            ->where('state_name', 'Estado de México')
            ->update(['state_name' => 'México']);
    }
};
