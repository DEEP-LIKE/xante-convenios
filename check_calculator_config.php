<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ConfigurationCalculator;

echo "=== CONFIGURACIONES DE CALCULADORA (% Calculadora) ===\n\n";

$configs = ConfigurationCalculator::orderBy('key')->get();

if ($configs->count() == 0) {
    echo "❌ NO HAY CONFIGURACIONES EN LA TABLA configuration_calculators\n";
    echo "Ejecuta: php artisan db:seed --class=CalculatorConfigurationSeeder\n";
} else {
    echo "✅ Configuraciones encontradas: " . $configs->count() . "\n\n";
    
    foreach ($configs as $config) {
        echo sprintf("%-35s: %s\n", $config->key, $config->value);
    }
    
    echo "\n=== VERIFICACIÓN DE CAMPOS CLAVE ===\n";
    
    $requiredKeys = [
        'valor_convenio_default',
        'comision_sin_iva_default', 
        'monto_credito_default',
        'isr_default',
        'cancelacion_hipoteca_default',
        'domicilio_convenio_default',
        'comunidad_default',
        'tipo_vivienda_default',
        'prototipo_default',
        'tipo_credito_default'
    ];
    
    $configArray = $configs->pluck('value', 'key')->toArray();
    
    foreach ($requiredKeys as $key) {
        $status = isset($configArray[$key]) ? '✅' : '❌';
        $value = $configArray[$key] ?? 'FALTANTE';
        echo "$status $key: $value\n";
    }
}
