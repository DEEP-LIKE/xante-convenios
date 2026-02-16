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
        // Insertar 'iva_valor' si no existe
        $exists = DB::table('configurations')->where('key', 'iva_valor')->exists();

        if (!$exists) {
            DB::table('configurations')->insert([
                'key' => 'iva_valor',
                'description' => 'Porcentaje de IVA a aplicar',
                'value' => '16.00',
                'type' => 'decimal',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('configurations')->where('key', 'iva_valor')->delete();
    }
};
