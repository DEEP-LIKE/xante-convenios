<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Agreement;

$agreement = Agreement::find(77);

if ($agreement) {
    echo "Agreement 77 encontrado:\n";
    echo "wizard_data: " . json_encode($agreement->wizard_data, JSON_PRETTY_PRINT) . "\n";
    echo "current_step: " . $agreement->current_step . "\n";
    
    // Verificar campos específicos de calculadora
    $wizardData = $agreement->wizard_data ?? [];
    $calculatorFields = ['valor_convenio', 'porcentaje_comision_sin_iva', 'domicilio_convenio', 'comunidad'];
    
    echo "\nCampos de calculadora:\n";
    foreach ($calculatorFields as $field) {
        $value = $wizardData[$field] ?? 'NO_SET';
        echo "  $field: $value\n";
    }
    
    // Verificar si shouldLoadCalculatorDefaults() devolvería true
    $shouldLoad = true;
    foreach ($calculatorFields as $field) {
        if (isset($wizardData[$field]) && !empty($wizardData[$field])) {
            $shouldLoad = false;
            break;
        }
    }
    
    echo "\nshouldLoadCalculatorDefaults(): " . ($shouldLoad ? 'TRUE' : 'FALSE') . "\n";
} else {
    echo "Agreement 77 no encontrado\n";
}
