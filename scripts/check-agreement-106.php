<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$agreementId = 106;
$agreement = \App\Models\Agreement::find($agreementId);

if (!$agreement) {
    echo "Agreement {$agreementId} no encontrado\n";
    exit(1);
}

echo "=== Agreement #{$agreementId} ===\n";
echo "Cliente: {$agreement->client->name}\n";
echo "Estado: {$agreement->status}\n";
echo "Paso actual: {$agreement->current_step}\n";
echo "Documentos generados: " . $agreement->generatedDocuments()->count() . "\n\n";

if ($agreement->generatedDocuments()->count() > 0) {
    echo "Documentos:\n";
    foreach ($agreement->generatedDocuments as $doc) {
        echo "- {$doc->document_name} ({$doc->file_name})\n";
    }
} else {
    echo "❌ No hay documentos generados para este convenio.\n";
    echo "\nVerificando wizard_data:\n";
    $wizardData = $agreement->wizard_data ?? [];
    echo "- client_id: " . ($wizardData['client_id'] ?? 'NO') . "\n";
    echo "- holder_name: " . ($wizardData['holder_name'] ?? 'NO') . "\n";
    echo "- domicilio_convenio: " . ($wizardData['domicilio_convenio'] ?? 'NO') . "\n";
    echo "- valor_convenio: " . ($wizardData['valor_convenio'] ?? 'NO') . "\n";
}
