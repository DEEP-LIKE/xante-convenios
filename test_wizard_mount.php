<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Filament\Pages\CreateAgreementWizard;
use App\Models\Agreement;

echo "Simulando mount() del CreateAgreementWizard...\n\n";

// Crear una instancia del wizard
$wizard = new CreateAgreementWizard();

// Simular el mount con agreement 77
echo "1. Ejecutando mount(77)...\n";
try {
    $wizard->mount(77);
    echo "   ✓ Mount ejecutado sin errores\n";
} catch (Exception $e) {
    echo "   ✗ Error en mount: " . $e->getMessage() . "\n";
}

// Verificar el estado después del mount
echo "\n2. Estado después del mount:\n";
echo "   agreementId: " . ($wizard->agreementId ?? 'NULL') . "\n";

// Usar reflection para acceder a la propiedad privada $data
$reflection = new ReflectionClass($wizard);
$dataProperty = $reflection->getProperty('data');
$dataProperty->setAccessible(true);
$data = $dataProperty->getValue($wizard);

echo "   data count: " . count($data) . "\n";
echo "   valor_convenio: " . ($data['valor_convenio'] ?? 'NO_SET') . "\n";
echo "   domicilio_convenio: " . ($data['domicilio_convenio'] ?? 'NO_SET') . "\n";

// Verificar el archivo de debug
echo "\n3. Contenido del archivo debug:\n";
$debugFile = storage_path('app/wizard_debug.log');
if (file_exists($debugFile)) {
    echo file_get_contents($debugFile);
} else {
    echo "   Archivo debug no encontrado\n";
}

echo "\n4. Verificando Agreement 77 después del mount:\n";
$agreement = Agreement::find(77);
if ($agreement) {
    echo "   wizard_data count: " . count($agreement->wizard_data ?? []) . "\n";
    if (!empty($agreement->wizard_data)) {
        echo "   valor_convenio en DB: " . ($agreement->wizard_data['valor_convenio'] ?? 'NO_SET') . "\n";
    }
} else {
    echo "   Agreement 77 no encontrado\n";
}
