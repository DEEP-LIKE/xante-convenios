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

echo 'Total documentos en BD: '.$docs->count()."\n\n";

foreach ($docs as $doc) {
    $exists = \Illuminate\Support\Facades\Storage::disk('private')->exists($doc->file_path);
    echo "- {$doc->file_name}\n";
    echo "  Tipo: {$doc->document_type}\n";
    echo "  Nombre: {$doc->document_name}\n";
    echo "  Ruta: {$doc->file_path}\n";
    echo '  Existe físicamente: '.($exists ? 'SI' : 'NO')."\n\n";
}
