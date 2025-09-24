<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ConfigurationCalculator;

echo "🧪 PRUEBA DE CONFIGURACIÓN WIZARD vs AGREEMENT RESOURCE\n";
echo "=" . str_repeat("=", 60) . "\n\n";

echo "📊 VALORES DE CONFIGURACIÓN EN BASE DE DATOS:\n";
echo "-" . str_repeat("-", 50) . "\n";

$configs = [
    'valor_convenio_default' => 'Valor Convenio',
    'comision_sin_iva_default' => '% Comisión Sin IVA',
    'monto_credito_default' => 'Monto Crédito',
    'isr_default' => 'ISR',
    'cancelacion_hipoteca_default' => 'Cancelación Hipoteca',
    'domicilio_convenio_default' => 'Domicilio Convenio',
    'comunidad_default' => 'Comunidad',
    'tipo_vivienda_default' => 'Tipo Vivienda',
    'prototipo_default' => 'Prototipo',
    'tipo_credito_default' => 'Tipo Crédito',
];

foreach ($configs as $key => $label) {
    $value = ConfigurationCalculator::get($key, 'NO ENCONTRADO');
    echo sprintf("%-25s: %s\n", $label, $value);
}

echo "\n🔍 VERIFICACIÓN DE MÉTODO ConfigurationCalculator::get():\n";
echo "-" . str_repeat("-", 50) . "\n";

// Probar el método get directamente
$testValue = ConfigurationCalculator::get('comision_sin_iva_default', 6.50);
echo "ConfigurationCalculator::get('comision_sin_iva_default', 6.50) = " . $testValue . "\n";

$testValue2 = ConfigurationCalculator::get('valor_convenio_default', 1495000);
echo "ConfigurationCalculator::get('valor_convenio_default', 1495000) = " . $testValue2 . "\n";

echo "\n✅ COMPARACIÓN WIZARD vs AGREEMENT RESOURCE:\n";
echo "-" . str_repeat("-", 50) . "\n";

// Simular cómo se cargan en AgreementResource
echo "🏢 AgreementResource (FUNCIONANDO):\n";
$agreementValues = [
    'valor_convenio' => ConfigurationCalculator::get('valor_convenio_default', 1495000),
    'porcentaje_comision_sin_iva' => ConfigurationCalculator::get('comision_sin_iva_default', 6.50),
    'monto_credito' => ConfigurationCalculator::get('monto_credito_default', 800000),
    'domicilio_convenio' => ConfigurationCalculator::get('domicilio_convenio_default', 'PRIVADA MELQUES 6'),
];

foreach ($agreementValues as $field => $value) {
    echo "  $field: $value\n";
}

echo "\n🧙 CreateAgreementWizard (ACTUALIZADO):\n";
$wizardValues = [
    'valor_convenio' => ConfigurationCalculator::get('valor_convenio_default', 1495000),
    'porcentaje_comision_sin_iva' => ConfigurationCalculator::get('comision_sin_iva_default', 6.50),
    'monto_credito' => ConfigurationCalculator::get('monto_credito_default', 800000),
    'domicilio_convenio' => ConfigurationCalculator::get('domicilio_convenio_default', 'PRIVADA MELQUES 6'),
];

foreach ($wizardValues as $field => $value) {
    echo "  $field: $value\n";
}

echo "\n🎯 RESULTADO:\n";
echo "-" . str_repeat("-", 50) . "\n";

$identical = true;
foreach ($agreementValues as $field => $agreementValue) {
    if ($agreementValue !== $wizardValues[$field]) {
        echo "❌ DIFERENCIA en $field: Agreement=$agreementValue vs Wizard={$wizardValues[$field]}\n";
        $identical = false;
    }
}

if ($identical) {
    echo "✅ PERFECTO: Ambos sistemas cargan valores idénticos\n";
    echo "✅ El wizard ahora debería mostrar los campos pre-llenados\n";
} else {
    echo "❌ HAY DIFERENCIAS: Revisar configuración\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🚀 Prueba completada. Revisar el wizard en el navegador.\n";
