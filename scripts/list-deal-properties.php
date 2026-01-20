<?php

require __DIR__.'/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$token = config('hubspot.token');
$baseUrl = config('hubspot.api_base_url');

echo "\n===============================================================\n";
echo "  LISTADO DE PROPIEDADES DE DEALS\n";
echo "===============================================================\n\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer {$token}",
])->get($baseUrl.'/crm/v3/properties/deals');

if ($response->successful()) {
    $properties = $response->json('results');

    // Filtrar propiedades relevantes
    $relevant = [
        'nombre', 'name', 'curp', 'rfc', 'civil', 'ocupacion',
        'domicilio', 'calle', 'colonia', 'municipio', 'estado',
        'conyuge', 'spouse', 'email', 'phone', 'celular', 'movil',
    ];

    echo 'Total propiedades encontradas: '.count($properties)."\n\n";
    echo "Propiedades que coinciden con palabras clave:\n";
    echo "------------------------------------------------\n";
    printf("%-40s | %-40s | %s\n", 'NOMBRE INTERNO (API)', 'ETIQUETA (LABEL)', 'TIPO');
    echo "------------------------------------------------\n";

    foreach ($properties as $prop) {
        $name = $prop['name'];
        $label = $prop['label'];
        $match = false;

        foreach ($relevant as $keyword) {
            if (str_contains(strtolower($name), $keyword) || str_contains(strtolower($label), $keyword)) {
                $match = true;
                break;
            }
        }

        if ($match) {
            printf("%-40s | %-40s | %s\n", substr($name, 0, 40), substr($label, 0, 40), $prop['type']);
        }
    }
} else {
    echo 'Error: '.$response->status()."\n";
    echo $response->body();
}
echo "\n";
