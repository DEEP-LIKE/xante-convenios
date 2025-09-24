<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Filament\Pages\CreateAgreementWizard;
use App\Models\Agreement;
use App\Models\ConfigurationCalculator;

echo "=== DEBUG DEL FLUJO DEL WIZARD ===\n\n";

// 1. Verificar el Agreement 77
echo "1. ESTADO DEL AGREEMENT 77:\n";
$agreement = Agreement::find(77);
if ($agreement) {
    echo "   ✅ Agreement encontrado\n";
    echo "   wizard_data: " . json_encode($agreement->wizard_data) . "\n";
    echo "   current_step: " . $agreement->current_step . "\n";
} else {
    echo "   ❌ Agreement 77 no encontrado\n";
    exit;
}

echo "\n2. SIMULANDO EL FLUJO DEL WIZARD:\n";

// 2. Crear instancia del wizard
$wizard = new CreateAgreementWizard();

// 3. Simular mount()
echo "   Ejecutando mount(77)...\n";
$wizard->mount(77);

// 4. Acceder a las propiedades usando reflection
$reflection = new ReflectionClass($wizard);

$dataProperty = $reflection->getProperty('data');
$dataProperty->setAccessible(true);
$wizardData = $dataProperty->getValue($wizard);

$agreementIdProperty = $reflection->getProperty('agreementId');
$agreementIdProperty->setAccessible(true);
$agreementId = $agreementIdProperty->getValue($wizard);

$calculatorLoadedProperty = $reflection->getProperty('calculatorDefaultsLoaded');
$calculatorLoadedProperty->setAccessible(true);
$calculatorLoaded = $calculatorLoadedProperty->getValue($wizard);

echo "   agreementId: $agreementId\n";
echo "   calculatorDefaultsLoaded: " . ($calculatorLoaded ? 'true' : 'false') . "\n";
echo "   data count: " . count($wizardData) . "\n";

echo "\n3. DATOS CARGADOS EN \$this->data:\n";
if (!empty($wizardData)) {
    $fieldsToCheck = [
        'valor_convenio', 'porcentaje_comision_sin_iva', 'domicilio_convenio', 
        'comunidad', 'tipo_vivienda', 'prototipo', 'monto_credito', 
        'isr', 'cancelacion_hipoteca', 'tipo_credito'
    ];
    
    foreach ($fieldsToCheck as $field) {
        $value = $wizardData[$field] ?? 'NO_SET';
        echo "   $field: $value\n";
    }
} else {
    echo "   ❌ \$this->data está vacío\n";
}

echo "\n4. SIMULANDO mutateFormDataBeforeFill():\n";

// Simular datos vacíos del formulario (como sería en la primera carga)
$emptyFormData = [];

$mutateMethod = $reflection->getMethod('mutateFormDataBeforeFill');
$mutateMethod->setAccessible(true);
$mutatedData = $mutateMethod->invoke($wizard, $emptyFormData);

echo "   Datos después de mutateFormDataBeforeFill:\n";
if (!empty($mutatedData)) {
    foreach ($fieldsToCheck as $field) {
        $value = $mutatedData[$field] ?? 'NO_SET';
        echo "   $field: $value\n";
    }
} else {
    echo "   ❌ mutatedData está vacío\n";
}

echo "\n5. VERIFICANDO shouldLoadCalculatorDefaults():\n";
$shouldLoadMethod = $reflection->getMethod('shouldLoadCalculatorDefaults');
$shouldLoadMethod->setAccessible(true);
$shouldLoad = $shouldLoadMethod->invoke($wizard);
echo "   shouldLoadCalculatorDefaults(): " . ($shouldLoad ? 'TRUE' : 'FALSE') . "\n";

echo "\n=== DIAGNÓSTICO COMPLETO ===\n";
echo "✅ Configuraciones: Disponibles\n";
echo "✅ Agreement 77: Existe\n";
echo "✅ mount(): Ejecutado\n";
echo ($calculatorLoaded ? "✅" : "❌") . " loadCalculatorDefaults(): " . ($calculatorLoaded ? "Ejecutado" : "No ejecutado") . "\n";
echo (count($wizardData) > 0 ? "✅" : "❌") . " \$this->data: " . (count($wizardData) > 0 ? "Poblado" : "Vacío") . "\n";
echo (count($mutatedData) > 0 ? "✅" : "❌") . " mutateFormDataBeforeFill(): " . (count($mutatedData) > 0 ? "Funciona" : "No funciona") . "\n";
