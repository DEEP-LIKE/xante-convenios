<?php

namespace Database\Seeders;

use App\Models\ConfigurationCalculator;
use Illuminate\Database\Seeder;

class CalculatorConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configurations = [
            // Configuraciones financieras esenciales
            [
                'key' => 'comision_sin_iva_default',
                'description' => 'Porcentaje de comisión sin IVA por defecto para la calculadora',
                'value' => '6.50',
                'type' => 'decimal',
            ],
            [
                'key' => 'iva_valor',
                'description' => 'Porcentaje de IVA a aplicar',
                'value' => '16.00',
                'type' => 'decimal',
            ],
        ];

        foreach ($configurations as $config) {
            ConfigurationCalculator::updateOrCreate(
                ['key' => $config['key']],
                $config
            );
        }

        $this->command->info('Configuraciones de calculadora creadas/actualizadas exitosamente.');
    }
}
