<?php

/**
 * Script para verificar si las propiedades fecha_cambio_precio_convenio
 * y fecha_cambio_oferta_convenio existen en HubSpot (Deals)
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$token = config('hubspot.token');

if (!$token) {
    echo "ERROR: HUBSPOT_TOKEN no está configurado\n";
    exit(1);
}

echo "\n===============================================================\n";
echo "  VERIFICACIÓN DE PROPIEDADES EN HUBSPOT (DEALS)\n";
echo "===============================================================\n\n";

// Propiedades a buscar
$propertiesToCheck = [
    'fecha_cambio_precio_convenio',
    'fecha_cambio_oferta_convenio',
    // También verificar las existentes relacionadas con precio
    'valor_convenio',
    'precio_promocion',
    'comision_total_pagar',
    'ganancia_final',
];

echo "Consultando propiedades de Deals en HubSpot API...\n\n";

// 1. Obtener TODAS las propiedades de Deals
$response = Illuminate\Support\Facades\Http::timeout(30)
    ->withHeaders([
        'Authorization' => "Bearer {$token}",
        'Content-Type' => 'application/json',
    ])
    ->get('https://api.hubapi.com/crm/v3/properties/deals');

if ($response->failed()) {
    echo "ERROR al obtener propiedades: HTTP " . $response->status() . "\n";
    echo $response->body() . "\n";
    exit(1);
}

$allProperties = $response->json()['results'] ?? [];

echo "Total propiedades de Deals encontradas: " . count($allProperties) . "\n\n";

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║       VERIFICACIÓN DE PROPIEDADES ESPECÍFICAS                ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

// Crear índice por nombre
$propertyIndex = [];
foreach ($allProperties as $prop) {
    $propertyIndex[$prop['name']] = $prop;
}

foreach ($propertiesToCheck as $propName) {
    if (isset($propertyIndex[$propName])) {
        $prop = $propertyIndex[$propName];
        echo "✅ {$propName}\n";
        echo "   Label:       " . ($prop['label'] ?? 'N/A') . "\n";
        echo "   Type:        " . ($prop['type'] ?? 'N/A') . "\n";
        echo "   Field Type:  " . ($prop['fieldType'] ?? 'N/A') . "\n";
        echo "   Group:       " . ($prop['groupName'] ?? 'N/A') . "\n";
        echo "   Description: " . ($prop['description'] ?? 'N/A') . "\n";
        echo "\n";
    } else {
        echo "❌ {$propName} — NO EXISTE en HubSpot\n\n";
    }
}

// 2. Buscar propiedades similares con "fecha" o "precio" o "oferta"
echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  PROPIEDADES RELACIONADAS (contienen 'fecha', 'precio',     ║\n";
echo "║  'oferta', 'cambio' en el nombre)                           ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$keywords = ['fecha', 'precio', 'oferta', 'cambio', 'convenio'];
$found = [];

foreach ($allProperties as $prop) {
    $name = strtolower($prop['name']);
    $label = strtolower($prop['label'] ?? '');
    
    foreach ($keywords as $keyword) {
        if (str_contains($name, $keyword) || str_contains($label, $keyword)) {
            $found[$prop['name']] = $prop;
            break;
        }
    }
}

if (empty($found)) {
    echo "No se encontraron propiedades relacionadas.\n";
} else {
    foreach ($found as $name => $prop) {
        echo "  📋 {$name}\n";
        echo "     Label: " . ($prop['label'] ?? 'N/A') . "\n";
        echo "     Type:  " . ($prop['type'] ?? 'N/A') . " / " . ($prop['fieldType'] ?? 'N/A') . "\n\n";
    }
}

echo "\n===============================================================\n";
echo "  FIN DE VERIFICACIÓN\n";
echo "===============================================================\n";
