<?php

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$token = config('hubspot.token');
$baseUrl = config('hubspot.api_base_url');

// Email a buscar
$searchEmail = $argv[1] ?? 'miguel.alfaro@carbono.mx';

echo "\n===============================================================\n";
echo "  VERIFICACIÓN DE CAMPOS XANTE_ID\n";
echo "  Buscando: {$searchEmail}\n";
echo "===============================================================\n\n";

// Buscar el contacto por email
$response = Http::withHeaders([
    'Authorization' => "Bearer {$token}",
    'Content-Type' => 'application/json',
])->post($baseUrl . '/crm/v3/objects/contacts/search', [
    'filterGroups' => [
        [
            'filters' => [
                [
                    'propertyName' => 'email',
                    'operator' => 'EQ',
                    'value' => $searchEmail
                ]
            ]
        ]
    ],
    'properties' => [
        'email',
        'firstname',
        'lastname',
        'xante_id',
        'xante_client_id',
        'id_xante',
        'client_xante_id',
        'hs_object_id'
    ],
    'limit' => 1
]);

if (!$response->successful()) {
    echo "❌ Error al buscar contacto: " . $response->status() . "\n";
    echo $response->body() . "\n";
    exit(1);
}

$results = $response->json()['results'] ?? [];

if (empty($results)) {
    echo "❌ No se encontró ningún contacto con el email: {$searchEmail}\n\n";
    exit(0);
}

$contact = $results[0];
$props = $contact['properties'] ?? [];

echo "✅ CONTACTO ENCONTRADO\n";
echo "------------------------------------------------\n";
echo "HubSpot ID: " . ($contact['id'] ?? 'N/A') . "\n";
echo "Nombre: " . ($props['firstname'] ?? '') . " " . ($props['lastname'] ?? '') . "\n";
echo "Email: " . ($props['email'] ?? 'N/A') . "\n";
echo "\n";

echo "VALORES DE LOS CAMPOS XANTE_ID:\n";
echo "------------------------------------------------\n";

$xanteFields = [
    'xante_id',
    'xante_client_id',
    'id_xante',
    'client_xante_id'
];

foreach ($xanteFields as $field) {
    $value = $props[$field] ?? null;
    $status = $value ? '✅' : '❌';
    $displayValue = $value ?: '(vacío)';
    
    printf("%-25s %s %s\n", $field . ':', $status, $displayValue);
}

echo "\n";

// Mostrar TODAS las propiedades del contacto para referencia
echo "TODAS LAS PROPIEDADES DEL CONTACTO:\n";
echo "------------------------------------------------\n";
foreach ($props as $key => $value) {
    if (!empty($value)) {
        printf("%-40s : %s\n", $key, substr($value, 0, 100));
    }
}

echo "\n===============================================================\n";
echo "  FIN DE LA VERIFICACIÓN\n";
echo "===============================================================\n\n";
