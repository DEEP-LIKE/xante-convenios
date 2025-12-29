<?php

namespace Database\Seeders;

use App\Models\ConfigurationCalculator;
use Illuminate\Database\Seeder;

class ConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $configurations = [
            // Comisiones
            [
                'key' => 'comision_sin_iva_default',
                'description' => 'Porcentaje de comisión sin IVA por defecto',
                'value' => '6.50',
                'type' => 'decimal',
            ],
            [
                'key' => 'iva_multiplier',
                'description' => 'Multiplicador para agregar IVA (16%)',
                'value' => '1.16',
                'type' => 'decimal',
            ],
            
            // Gastos
            [
                'key' => 'isr_default',
                'description' => 'Valor por defecto para ISR',
                'value' => '0',
                'type' => 'number',
            ],
            [
                'key' => 'cancelacion_hipoteca_default',
                'description' => 'Valor por defecto para cancelación de hipoteca',
                'value' => '20000',
                'type' => 'number',
            ],
            [
                'key' => 'total_gastos_fi_default',
                'description' => 'Total de gastos FI por defecto',
                'value' => '20000',
                'type' => 'number',
            ],
            
            // Créditos
            [
                'key' => 'monto_credito_default',
                'description' => 'Monto de crédito por defecto',
                'value' => '800000',
                'type' => 'number',
            ],
            [
                'key' => 'tipo_credito_default',
                'description' => 'Tipo de crédito por defecto',
                'value' => 'BANCARIO',
                'type' => 'text',
            ],
            [
                'key' => 'otro_banco_default',
                'description' => 'Valor por defecto para otro banco',
                'value' => 'NO APLICA',
                'type' => 'text',
            ],
            
            // General
            [
                'key' => 'precio_promocion_multiplier',
                'description' => 'Multiplicador para calcular precio promoción (1.09 = 9%)',
                'value' => '1.09',
                'type' => 'decimal',
            ],
        ];

        foreach ($configurations as $config) {
            ConfigurationCalculator::updateOrCreate(
                ['key' => $config['key']],
                $config
            );
        }
    }
}
