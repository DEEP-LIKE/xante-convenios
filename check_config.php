<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ConfigurationCalculator;

echo "Valores de configuración en la base de datos:\n\n";

$configValues = ConfigurationCalculator::pluck('value', 'key')->toArray();

$expectedKeys = [
    'valor_convenio_default',
    'comision_sin_iva_default', 
    'monto_credito_default',
    'isr_default',
    'cancelacion_hipoteca_default',
    'domicilio_convenio_default',
    'comunidad_default',
    'tipo_vivienda_default',
    'prototipo_default',
    'tipo_credito_default',
    'otro_banco_default'
];

foreach ($expectedKeys as $key) {
    $value = $configValues[$key] ?? 'NO_ENCONTRADO';
    echo "$key: $value\n";
}

echo "\nTotal de configuraciones encontradas: " . count($configValues) . "\n";
