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
        DB::table('configurations')->insert([
            'key' => 'iva_percentage_default',
            'description' => 'Porcentaje de IVA (Impuesto al Valor Agregado)',
            'value' => '16',
            'type' => 'decimal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('configurations')
            ->where('key', 'iva_percentage_default')
            ->delete();
    }
};
