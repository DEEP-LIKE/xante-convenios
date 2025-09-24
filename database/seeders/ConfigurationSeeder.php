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
                'name' => '% Comisión (Sin IVA)',
                'description' => 'Porcentaje de comisión sin IVA por defecto',
                'value' => '6.50',
                'type' => 'decimal',
                'group' => 'comisiones',
            ],
            [
                'key' => 'iva_multiplier',
                'name' => 'Multiplicador IVA',
                'description' => 'Multiplicador para agregar IVA (16%)',
                'value' => '1.16',
                'type' => 'decimal',
                'group' => 'comisiones',
            ],
            
            // Gastos
            [
                'key' => 'isr_default',
                'name' => 'ISR por defecto',
                'description' => 'Valor por defecto para ISR',
                'value' => '0',
                'type' => 'number',
                'group' => 'gastos',
            ],
            [
                'key' => 'cancelacion_hipoteca_default',
                'name' => 'Cancelación de hipoteca',
                'description' => 'Valor por defecto para cancelación de hipoteca',
                'value' => '20000',
                'type' => 'number',
                'group' => 'gastos',
            ],
            [
                'key' => 'total_gastos_fi_default',
                'name' => 'Total Gastos FI (Venta)',
                'description' => 'Total de gastos FI por defecto',
                'value' => '20000',
                'type' => 'number',
                'group' => 'gastos',
            ],
            
            // Créditos
            [
                'key' => 'monto_credito_default',
                'name' => 'Monto de crédito por defecto',
                'description' => 'Monto de crédito por defecto',
                'value' => '800000',
                'type' => 'number',
                'group' => 'creditos',
            ],
            [
                'key' => 'tipo_credito_default',
                'name' => 'Tipo de crédito por defecto',
                'description' => 'Tipo de crédito por defecto',
                'value' => 'BANCARIO',
                'type' => 'text',
                'group' => 'creditos',
            ],
            [
                'key' => 'otro_banco_default',
                'name' => 'Otro banco por defecto',
                'description' => 'Valor por defecto para otro banco',
                'value' => 'NO APLICA',
                'type' => 'text',
                'group' => 'creditos',
            ],
            
            // General
            [
                'key' => 'precio_promocion_multiplier',
                'name' => 'Multiplicador precio promoción',
                'description' => 'Multiplicador para calcular precio promoción (1.09 = 9%)',
                'value' => '1.09',
                'type' => 'decimal',
                'group' => 'general',
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
