<?php

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$token = config('hubspot.token');
$baseUrl = config('hubspot.api_base_url');

$searchEmail = $argv[1] ?? 'miguel.alfaro@carbono.mx';

echo "\n===============================================================\n";
echo "  RELACIÓN ENTRE DEAL Y CONTACT EN HUBSPOT\n";
echo "  Buscando: {$searchEmail}\n";
echo "===============================================================\n\n";

// Buscar el contacto
$contactResponse = Http::withHeaders([
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
    'properties' => ['email', 'firstname', 'lastname', 'xante_id', 'hs_object_id'],
    'limit' => 1
]);

if (!$contactResponse->successful() || empty($contactResponse->json()['results'])) {
    echo "❌ No se encontró el contacto\n";
    exit(1);
}

$contact = $contactResponse->json()['results'][0];
$contactId = $contact['id'];
$contactProps = $contact['properties'];

echo "✅ CONTACTO ENCONTRADO\n";
echo "------------------------------------------------\n";
echo "Contact ID: {$contactId}\n";
echo "Nombre: " . ($contactProps['firstname'] ?? '') . " " . ($contactProps['lastname'] ?? '') . "\n";
echo "Email: " . ($contactProps['email'] ?? 'N/A') . "\n";
echo "Xante ID: " . ($contactProps['xante_id'] ?? 'N/A') . "\n\n";

// Obtener asociaciones del contacto con deals
echo "🔗 ASOCIACIONES DEL CONTACTO CON DEALS\n";
echo "------------------------------------------------\n";

$associationsResponse = Http::withHeaders([
    'Authorization' => "Bearer {$token}",
])->get($baseUrl . "/crm/v3/objects/contacts/{$contactId}/associations/deals");

if (!$associationsResponse->successful()) {
    echo "❌ Error obteniendo asociaciones\n";
    exit(1);
}

$associations = $associationsResponse->json()['results'] ?? [];

if (empty($associations)) {
    echo "❌ No hay deals asociados\n\n";
} else {
    echo "✅ Deals asociados: " . count($associations) . "\n\n";
    
    foreach ($associations as $index => $assoc) {
        $dealId = $assoc['id'] ?? $assoc['toObjectId'] ?? null;
        $associationType = $assoc['type'] ?? 'N/A';
        
        echo "   ASOCIACIÓN #" . ($index + 1) . ":\n";
        echo "   - Deal ID: {$dealId}\n";
        echo "   - Tipo de asociación: {$associationType}\n";
        
        // Obtener detalles del deal
        $dealResponse = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->get($baseUrl . "/crm/v3/objects/deals/{$dealId}", [
            'properties' => 'dealname,amount,estatus_de_convenio,hs_object_id,num_associated_contacts'
        ]);
        
        if ($dealResponse->successful()) {
            $deal = $dealResponse->json();
            $dealProps = $deal['properties'];
            
            echo "   - Nombre del Deal: " . ($dealProps['dealname'] ?? 'N/A') . "\n";
            echo "   - Monto: $" . number_format($dealProps['amount'] ?? 0, 2) . "\n";
            echo "   - Estatus: " . ($dealProps['estatus_de_convenio'] ?? 'N/A') . "\n";
            echo "   - Contactos asociados: " . ($dealProps['num_associated_contacts'] ?? '0') . "\n";
        }
        echo "\n";
    }
}

// Ahora verificar desde el Deal hacia el Contact
if (!empty($associations)) {
    $firstDealId = $associations[0]['id'] ?? $associations[0]['toObjectId'];
    
    echo "🔗 ASOCIACIONES DEL DEAL CON CONTACTS (verificación inversa)\n";
    echo "------------------------------------------------\n";
    echo "Deal ID: {$firstDealId}\n\n";
    
    $dealContactsResponse = Http::withHeaders([
        'Authorization' => "Bearer {$token}",
    ])->get($baseUrl . "/crm/v3/objects/deals/{$firstDealId}/associations/contacts");
    
    if ($dealContactsResponse->successful()) {
        $dealContacts = $dealContactsResponse->json()['results'] ?? [];
        
        echo "✅ Contacts asociados al Deal: " . count($dealContacts) . "\n\n";
        
        foreach ($dealContacts as $index => $assoc) {
            $associatedContactId = $assoc['id'] ?? $assoc['toObjectId'] ?? null;
            $associationType = $assoc['type'] ?? 'N/A';
            
            echo "   CONTACT #" . ($index + 1) . ":\n";
            echo "   - Contact ID: {$associatedContactId}\n";
            echo "   - Tipo de asociación: {$associationType}\n";
            echo "   - ¿Es el mismo contacto?: " . ($associatedContactId == $contactId ? '✅ SÍ' : '❌ NO') . "\n\n";
        }
    }
}

echo "===============================================================\n";
echo "  EXPLICACIÓN\n";
echo "===============================================================\n\n";

echo "En HubSpot, la relación entre Deal y Contact NO se hace mediante\n";
echo "un campo directo, sino mediante ASOCIACIONES (Associations).\n\n";

echo "Características de las asociaciones:\n";
echo "  • Son relaciones muchos-a-muchos\n";
echo "  • Un Deal puede tener múltiples Contacts asociados\n";
echo "  • Un Contact puede tener múltiples Deals asociados\n";
echo "  • Se acceden mediante el endpoint de asociaciones\n";
echo "  • No hay un campo 'contact_id' en el Deal ni 'deal_id' en Contact\n\n";

echo "En el código de sincronización:\n";
echo "  1. Se obtiene el Deal ID\n";
echo "  2. Se llama a: /crm/v3/objects/deals/{dealId}/associations/contacts\n";
echo "  3. Se obtiene el Contact ID del primer contacto asociado\n";
echo "  4. Se obtienen los datos del Contact usando ese ID\n";
echo "  5. Se extrae el xante_id del Contact\n\n";

echo "Referencia en el código:\n";
echo "  📄 HubspotSyncService.php líneas 351-406\n";
echo "  📄 Método: getContactFromDeal()\n\n";

echo "===============================================================\n\n";
