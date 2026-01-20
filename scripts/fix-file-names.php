<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$agreement = \App\Models\Agreement::find(11);

if (! $agreement) {
    echo "Agreement 11 no encontrado\n";
    exit(1);
}

$docs = $agreement->generatedDocuments;

echo "Actualizando file_name para {$docs->count()} documentos...\n\n";

foreach ($docs as $doc) {
    // Extraer el nombre del archivo de la ruta completa
    $fileName = basename($doc->file_path);

    echo "- Documento ID {$doc->id}\n";
    echo "  file_path: {$doc->file_path}\n";
    echo "  file_name anterior: '{$doc->file_name}'\n";
    echo "  file_name nuevo: '{$fileName}'\n";

    $doc->update(['file_name' => $fileName]);

    echo "  ✅ Actualizado\n\n";
}

echo "Proceso completado.\n";
