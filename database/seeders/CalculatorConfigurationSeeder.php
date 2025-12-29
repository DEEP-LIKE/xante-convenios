<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConfigurationCalculator;

class CalculatorConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configurations = [
            // Configuraciones financieras
            [
                'key' => 'valor_convenio_default',
                'description' => 'Valor del convenio por defecto para nuevos cálculos',
                'value' => '1495000',
                'type' => 'decimal',
            ],
            [
                'key' => 'comision_sin_iva_default',
                'description' => 'Porcentaje de comisión sin IVA por defecto para la calculadora',
                'value' => '6.50',
                'type' => 'decimal',
            ],
            [
                'key' => 'iva_multiplier',
                'description' => 'Multiplicador para cálculo de IVA (16%)',
                'value' => '1.16',
                'type' => 'decimal',
            ],
            [
                'key' => 'monto_credito_default',
                'description' => 'Monto de crédito por defecto',
                'value' => '800000',
                'type' => 'decimal',
            ],
            [
                'key' => 'isr_default',
                'description' => 'ISR por defecto',
                'value' => '0',
                'type' => 'decimal',
            ],
            [
                'key' => 'cancelacion_hipoteca_default',
                'description' => 'Costo por defecto de cancelación de hipoteca',
                'value' => '20000.00',
                'type' => 'decimal',
            ],
            
            // Configuraciones de propiedad
            [
                'key' => 'domicilio_convenio_default',
                'description' => 'Domicilio por defecto para convenios',
                'value' => 'PRIVADA MELQUES 6',
                'type' => 'string',
            ],
            [
                'key' => 'comunidad_default',
                'description' => 'Comunidad por defecto para propiedades',
                'value' => 'REAL SEGOVIA',
                'type' => 'string',
            ],
            [
                'key' => 'tipo_vivienda_default',
                'description' => 'Tipo de vivienda por defecto',
                'value' => 'CASA',
                'type' => 'string',
            ],
            [
                'key' => 'prototipo_default',
                'description' => 'Prototipo por defecto para propiedades',
                'value' => 'BURGOS',
                'type' => 'string',
            ]
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
