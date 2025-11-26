<?php

use App\Models\Agreement;
use App\Services\PdfGenerationService;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$agreementId = 106;
$agreement = Agreement::find($agreementId);

if (!$agreement) {
    echo "❌ Acuerdo #{$agreementId} no encontrado.\n";
    exit(1);
}

echo "🔄 Regenerando documentos para Acuerdo #{$agreementId}...\n";

try {
    $service = new PdfGenerationService();
    $documents = $service->generateAllDocuments($agreement);
    
    echo "✅ Documentos regenerados exitosamente:\n";
    foreach ($documents as $doc) {
        echo "- {$doc->document_name} ({$doc->file_name})\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error regenerando documentos: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
