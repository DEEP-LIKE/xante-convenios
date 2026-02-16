<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Log::info('Ejecutando migración de limpieza y reparación de configuración de calculadora...');

        // 1. Asegurar comision_sin_iva_default
        DB::table('configurations')->updateOrInsert(
            ['key' => 'comision_sin_iva_default'],
            [
                'description' => 'Porcentaje de comisión sin IVA por defecto para la calculadora',
                'value' => '6.50',
                'type' => 'decimal',
                'updated_at' => now(),
            ]
        );

        // 2. Asegurar iva_valor
        DB::table('configurations')->updateOrInsert(
            ['key' => 'iva_valor'],
            [
                'description' => 'Porcentaje de IVA a aplicar',
                'value' => '16.00',
                'type' => 'decimal',
                'updated_at' => now(),
            ]
        );

        // 3. Limpiar otras claves técnicas sobrantes para mantener el panel limpio
        $deleted = DB::table('configurations')->whereIn('key', [
            'iva_multiplier',
            'precio_promocion_multiplier',
            'isr_default',
            'cancelacion_hipoteca_default',
            'monto_credito_default'
        ])->delete();

        Log::info("Migración completada. Se eliminaron {$deleted} claves sobrantes.");
        
        // Limpiar cache por si acaso
        try {
            \Illuminate\Support\Facades\Cache::forget('calculator_configurations');
            \Illuminate\Support\Facades\Cache::forget('config.comision_sin_iva_default');
            \Illuminate\Support\Facades\Cache::forget('config.iva_valor');
        } catch (\Exception $e) {
            Log::warning('No se pudo limpiar el cache durante la migración: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructivo en el down para evitar pérdida de datos si se revierte por error
    }
};
