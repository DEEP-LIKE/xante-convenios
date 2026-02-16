<?php

namespace App\Console\Commands;

use App\Models\ConfigurationCalculator;
use Illuminate\Console\Command;

class RepairCalculatorConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xante:repair-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asegura que todas las claves de configuración de la calculadora existan en la base de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $configs = [
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

        $keysToKeep = array_column($configs, 'key');

        // Cleanup other technical keys
        ConfigurationCalculator::whereNotIn('key', $keysToKeep)
            ->whereIn('key', [
                'iva_multiplier',
                'precio_promocion_multiplier',
                'isr_default',
                'cancelacion_hipoteca_default',
                'monto_credito_default'
            ])
            ->delete();

        foreach ($configs as $config) {
            $exists = ConfigurationCalculator::where('key', $config['key'])->exists();
            
            if (!$exists) {
                ConfigurationCalculator::create($config);
                $this->info("Creada clave: {$config['key']}");
            } else {
                $this->line("Clave ya existe: {$config['key']}");
            }
        }

        $this->info('Reparación de configuración completada. Solo quedan los 2 valores principales.');
        
        // Limpiar cache
        \Illuminate\Support\Facades\Cache::forget('calculator_configurations');
        foreach ($configs as $config) {
            \Illuminate\Support\Facades\Cache::forget("config.{$config['key']}");
        }
    }
}
