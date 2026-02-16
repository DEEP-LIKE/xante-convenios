<?php

use Illuminate\Support\Facades\Http;

$token = env('HUBSPOT_TOKEN');

if (!$token) {
    echo "ERROR: HUBSPOT_TOKEN no está configurado\n";
    exit(1);
}

echo "=== BUSCANDO DEAL: CESAR SALGADO MARTINEZ ===\n\n";

// Buscar el Deal por nombre
$response = Http::timeout(30)
    ->withHeaders([
        'Authorization' => "Bearer {$token}",
        'Content-Type' => 'application/json',
    ])
    ->post('https://api.hubapi.com/crm/v3/objects/deals/search', [
        'filterGroups' => [
            [
                'filters' => [
                    [
                        'propertyName' => 'dealname',
                        'operator' => 'CONTAINS_TOKEN',
                        'value' => 'CESAR SALGADO MARTINEZ'
                    ]
                ]
            ]
        ],
        'properties' => [
            'dealname',
            'nombre_del_desarrollo',
            'calle_o_privada_',
            'tipo_de_inmueble_',
            'ciudad',
            'state',
            'hipotecada',
            'tipo_de_hipoteca',
            'niveles_casa',
        ],
        'limit' => 5
    ]);

if ($response->failed()) {
    echo "ERROR: " . $response->status() . "\n";
    echo $response->body() . "\n";
    exit(1);
}

$results = $response->json();
$deals = $results['results'] ?? [];

echo "Encontrados: " . count($deals) . " deals\n\n";

foreach ($deals as $deal) {
    $props = $deal['properties'];
    echo "=== DEAL: {$props['dealname']} ===\n";
    echo "ID: {$deal['id']}\n\n";
    
    echo "--- DATOS DE PROPIEDAD ---\n";
    echo "nombre_del_desarrollo: " . ($props['nombre_del_desarrollo'] ?? 'NULL') . "\n";
    echo "calle_o_privada_: " . ($props['calle_o_privada_'] ?? 'NULL') . "\n";
    echo "tipo_de_inmueble_: " . ($props['tipo_de_inmueble_'] ?? 'NULL') . "\n";
    echo "ciudad: " . ($props['ciudad'] ?? 'NULL') . "\n";
    echo "state: " . ($props['state'] ?? 'NULL') . "\n";
    echo "hipotecada: " . ($props['hipotecada'] ?? 'NULL') . "\n";
    echo "tipo_de_hipoteca: " . ($props['tipo_de_hipoteca'] ?? 'NULL') . "\n";
    echo "niveles_casa: " . ($props['niveles_casa'] ?? 'NULL') . "\n";
    echo "\n" . str_repeat("-", 60) . "\n\n";
}
