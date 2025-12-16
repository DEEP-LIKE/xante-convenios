<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Agreement;
use App\Services\PdfGenerationService;
use Illuminate\Support\Facades\Log;

$agreementId = 466;
$agreement = Agreement::with('client')->find($agreementId);

if (!$agreement) {
    echo "❌ Convenio #$agreementId no encontrado.\n";
    exit;
}

echo "✅ Convenio #$agreementId encontrado.\n";
echo "   Cliente: " . $agreement->client->name . "\n";
echo "   Status: " . $agreement->status . "\n";
echo "   Estado Propiedad: " . ($agreement->wizard_data['estado_propiedad'] ?? 'No definido') . "\n";

// Mostrar documentos actuales
$currentDocs = $agreement->generatedDocuments()->count();
echo "   Documentos actuales: $currentDocs\n";

echo "\n🗑️  Limpiando referencias de documentos anteriores...\n";
$agreement->generatedDocuments()->delete();
echo "   ✓ Referencias eliminadas (archivos físicos conservados)\n";

echo "\n📄 Regenerando PDFs...\n";

try {
    $pdfService = new PdfGenerationService();
    echo "   ... Iniciando generateAllDocuments\n";
    $documents = $pdfService->generateAllDocuments($agreement);
    echo "   ... generateAllDocuments completado\n";
    
    echo "✅ " . count($documents) . " PDFs regenerados exitosamente:\n";
    foreach ($documents as $doc) {
        echo "   ✓ {$doc->document_name}\n";
        echo "     - Archivo: {$doc->file_name}\n";
        echo "     - Tamaño: " . number_format($doc->file_size / 1024, 2) . " KB\n";
    }
    
    echo "\n📊 Resumen:\n";
    echo "   - Total documentos: " . count($documents) . "\n";
    echo "   - Tamaño total: " . number_format($pdfService->getTotalDocumentsSize($agreement) / 1024, 2) . " KB\n";
    echo "   - Estado convenio: " . $agreement->fresh()->status . "\n";
    
    // Verificar datos bancarios en wizard_data
    if (isset($agreement->wizard_data['estado_propiedad'])) {
        $stateName = $agreement->wizard_data['estado_propiedad'];
        $bankAccount = \App\Models\StateBankAccount::where('state_name', $stateName)->first();
        
        echo "\n🏦 Datos bancarios utilizados:\n";
        echo "   - Estado: $stateName\n";
        if ($bankAccount) {
            echo "   - Banco: {$bankAccount->bank_name}\n";
            echo "   - Cuenta: {$bankAccount->account_number}\n";
            echo "   - CLABE: {$bankAccount->clabe}\n";
        } else {
            echo "   ⚠️  No se encontró cuenta bancaria para este estado (se usaron valores por defecto)\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Excepción: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
