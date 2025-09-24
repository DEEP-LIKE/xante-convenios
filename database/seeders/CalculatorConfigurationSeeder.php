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
                'name' => 'Valor Convenio Por Defecto',
                'description' => 'Valor del convenio por defecto para nuevos cálculos',
                'value' => '1495000',
                'type' => 'decimal',
                'group' => 'calculator'
            ],
            [
                'key' => 'comision_sin_iva_default',
                'name' => 'Comisión Sin IVA Por Defecto',
                'description' => 'Porcentaje de comisión sin IVA por defecto para la calculadora',
                'value' => '6.50',
                'type' => 'decimal',
                'group' => 'calculator'
            ],
            [
                'key' => 'iva_multiplier',
                'name' => 'Multiplicador IVA',
                'description' => 'Multiplicador para cálculo de IVA (16%)',
                'value' => '1.16',
                'type' => 'decimal',
                'group' => 'calculator'
            ],
            [
                'key' => 'monto_credito_default',
                'name' => 'Monto Crédito Por Defecto',
                'description' => 'Monto de crédito por defecto',
                'value' => '800000',
                'type' => 'decimal',
                'group' => 'calculator'
            ],
            [
                'key' => 'isr_default',
                'name' => 'ISR Por Defecto',
                'description' => 'ISR por defecto',
                'value' => '0',
                'type' => 'decimal',
                'group' => 'calculator'
            ],
            [
                'key' => 'cancelacion_hipoteca_default',
                'name' => 'Cancelación Hipoteca Por Defecto',
                'description' => 'Costo por defecto de cancelación de hipoteca',
                'value' => '20000.00',
                'type' => 'decimal',
                'group' => 'calculator'
            ],
            
            // Configuraciones de propiedad
            [
                'key' => 'domicilio_convenio_default',
                'name' => 'Domicilio Convenio Por Defecto',
                'description' => 'Domicilio por defecto para convenios',
                'value' => 'PRIVADA MELQUES 6',
                'type' => 'string',
                'group' => 'property'
            ],
            [
                'key' => 'comunidad_default',
                'name' => 'Comunidad Por Defecto',
                'description' => 'Comunidad por defecto para propiedades',
                'value' => 'REAL SEGOVIA',
                'type' => 'string',
                'group' => 'property'
            ],
            [
                'key' => 'tipo_vivienda_default',
                'name' => 'Tipo Vivienda Por Defecto',
                'description' => 'Tipo de vivienda por defecto',
                'value' => 'CASA',
                'type' => 'string',
                'group' => 'property'
            ],
            [
                'key' => 'prototipo_default',
                'name' => 'Prototipo Por Defecto',
                'description' => 'Prototipo por defecto para propiedades',
                'value' => 'BURGOS',
                'type' => 'string',
                'group' => 'property'
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
