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
        // 1. Asegurar comision_sin_iva_default
        $existsComision = DB::table('configurations')->where('key', 'comision_sin_iva_default')->exists();
        if (!$existsComision) {
            DB::table('configurations')->insert([
                'key' => 'comision_sin_iva_default',
                'description' => 'Porcentaje de comisión sin IVA por defecto para la calculadora',
                'value' => '6.50',
                'type' => 'decimal',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Asegurar iva_valor
        $existsIva = DB::table('configurations')->where('key', 'iva_valor')->exists();
        if (!$existsIva) {
            DB::table('configurations')->insert([
                'key' => 'iva_valor',
                'description' => 'Porcentaje de IVA a aplicar',
                'value' => '16.00',
                'type' => 'decimal',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Limpiar otras claves técnicas sobrantes
        DB::table('configurations')->whereIn('key', [
            'iva_multiplier',
            'precio_promocion_multiplier',
            'isr_default',
            'cancelacion_hipoteca_default',
            'monto_credito_default'
        ])->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('configurations')->whereIn('key', ['iva_valor', 'comision_sin_iva_default'])->delete();
    }
};
