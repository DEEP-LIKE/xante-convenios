<?php

/**
 * Script de diagnóstico para encontrar deals en HubSpot
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$userEmail = 'miguel.alfaro@carbono.mx';
$token = config('hubspot.token');
$baseUrl = config('hubspot.api_base_url');

echo "\n===============================================================\n";
echo "  DIAGNOSTICO: Buscando Deals en HubSpot\n";
echo "===============================================================\n\n";

// 1. Buscar TODOS los deals (sin filtro de estado)
echo "[TEST 1] Buscando deals solo por email (sin filtro de estado)...\n";
$response1 = Http::withHeaders([
    'Authorization' => "Bearer {$token}",
    'Content-Type' => 'application/json',
])->post($baseUrl . '/crm/v3/objects/deals/search', [
    'filterGroups' => [
        [
            'filters' => [
                [
                    'propertyName' => 'email',
                    'operator' => 'EQ',
                    'value' => $userEmail
                ]
            ]
        ]
    ],
    'properties' => ['dealname', 'email', 'estatus_de_convenio', 'nombre_completo'],
    'limit' => 10
]);

echo "Status: " . $response1->status() . "\n";
echo "Total encontrados: " . ($response1->json('total') ?? 0) . "\n";
if ($response1->json('total') > 0) {
    foreach ($response1->json('results') as $deal) {
        echo "  - Deal ID: {$deal['id']}\n";
        echo "    Nombre: " . ($deal['properties']['dealname'] ?? 'N/A') . "\n";
        echo "    Email: " . ($deal['properties']['email'] ?? 'N/A') . "\n";
        echo "    Estado: " . ($deal['properties']['estatus_de_convenio'] ?? 'N/A') . "\n\n";
    }
}

// 2. Buscar deals con estado "Aceptado" (sin filtro de email)
echo "\n[TEST 2] Buscando deals solo con estado 'Aceptado' (sin filtro de email)...\n";
$response2 = Http::withHeaders([
    'Authorization' => "Bearer {$token}",
    'Content-Type' => 'application/json',
])->post($baseUrl . '/crm/v3/objects/deals/search', [
    'filterGroups' => [
        [
            'filters' => [
                [
                    'propertyName' => 'estatus_de_convenio',
                    'operator' => 'EQ',
                    'value' => 'Aceptado'
                ]
            ]
        ]
    ],
    'properties' => ['dealname', 'email', 'estatus_de_convenio', 'nombre_completo'],
    'limit' => 5
]);

echo "Status: " . $response2->status() . "\n";
echo "Total encontrados: " . ($response2->json('total') ?? 0) . "\n";
if ($response2->json('total') > 0) {
    echo "Primeros 5 deals con estado 'Aceptado':\n";
    foreach ($response2->json('results') as $deal) {
        echo "  - Deal ID: {$deal['id']}\n";
        echo "    Nombre: " . ($deal['properties']['dealname'] ?? 'N/A') . "\n";
        echo "    Email: " . ($deal['properties']['email'] ?? 'N/A') . "\n";
        echo "    Estado: " . ($deal['properties']['estatus_de_convenio'] ?? 'N/A') . "\n\n";
    }
}

// 3. Listar todas las propiedades disponibles del Deal
echo "\n[TEST 3] Verificando si 'email' es una propiedad válida de Deal...\n";
$propsResponse = Http::withHeaders([
    'Authorization' => "Bearer {$token}",
])->get($baseUrl . '/crm/v3/properties/deals');

if ($propsResponse->successful()) {
    $properties = $propsResponse->json('results');
    $emailProp = collect($properties)->firstWhere('name', 'email');
    
    if ($emailProp) {
        echo "✓ La propiedad 'email' EXISTE en deals\n";
        echo "  Tipo: " . $emailProp['type'] . "\n";
        echo "  Label: " . $emailProp['label'] . "\n";
    } else {
        echo "✗ La propiedad 'email' NO EXISTE en deals\n";
        echo "  Propiedades disponibles que contienen 'email':\n";
        foreach ($properties as $prop) {
            if (str_contains(strtolower($prop['name']), 'email') || str_contains(strtolower($prop['label']), 'email')) {
                echo "    - {$prop['name']} ({$prop['label']})\n";
            }
        }
    }
}

echo "\n===============================================================\n";
echo "  FIN DEL DIAGNOSTICO\n";
echo "===============================================================\n\n";
