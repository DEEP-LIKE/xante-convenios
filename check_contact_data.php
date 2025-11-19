<?php

use Illuminate\Support\Facades\Http;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$hubspotId = $argv[1] ?? null;

if (!$hubspotId) {
    echo "Por favor proporciona un HubSpot ID.\n";
    exit(1);
}

$token = config('hubspot.token');
$baseUrl = config('hubspot.api_base_url');

echo "Consultando Contacto ID: $hubspotId en HubSpot...\n";

// Solicitamos explícitamente las propiedades que nos interesan
$properties = [
    'firstname', 'lastname', 'email', 'phone', 
    'address', 'city', 'state', 'zip', 'colonia', 
    'date_of_birth', 'jobtitle'
];

$url = "{$baseUrl}/crm/v3/objects/contacts/{$hubspotId}?properties=" . implode(',', $properties);

$response = Http::withHeaders([
    'Authorization' => "Bearer {$token}",
])->get($url);

if ($response->successful()) {
    $data = $response->json();
    echo "\n--- DATOS CRUDOS DE HUBSPOT ---\n";
    print_r($data['properties']);
    echo "\n-------------------------------\n";
    
    echo "\nAnálisis:\n";
    foreach ($properties as $prop) {
        $val = $data['properties'][$prop] ?? null;
        echo "Campo '$prop': " . ($val ? "✅ '$val'" : "❌ VACÍO/NULL") . "\n";
    }
} else {
    echo "Error: " . $response->body();
}
