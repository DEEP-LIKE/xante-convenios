<?php

require __DIR__.'/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$token = config('hubspot.token');
$baseUrl = config('hubspot.api_base_url');

// Email a buscar
$searchEmail = $argv[1] ?? 'miguel.alfaro@carbono.mx';

echo "\n===============================================================\n";
echo "  UBICACIÓN DEL CAMPO XANTE_ID\n";
echo "  Buscando: {$searchEmail}\n";
echo "===============================================================\n\n";

// ========================================
// 1. BUSCAR EN CONTACTS (CLIENTES)
// ========================================
echo "🔍 BUSCANDO EN CONTACTS (CLIENTES)...\n";
echo "------------------------------------------------\n";

$contactResponse = Http::withHeaders([
    'Authorization' => "Bearer {$token}",
    'Content-Type' => 'application/json',
])->post($baseUrl.'/crm/v3/objects/contacts/search', [
    'filterGroups' => [
        [
            'filters' => [
                [
                    'propertyName' => 'email',
                    'operator' => 'EQ',
                    'value' => $searchEmail,
                ],
            ],
        ],
    ],
    'properties' => [
        'email',
        'firstname',
        'lastname',
        'xante_id',
        'xante_client_id',
        'id_xante',
        'client_xante_id',
        'hs_object_id',
    ],
    'limit' => 1,
]);

if ($contactResponse->successful()) {
    $contactResults = $contactResponse->json()['results'] ?? [];

    if (! empty($contactResults)) {
        $contact = $contactResults[0];
        $contactProps = $contact['properties'] ?? [];

        echo "✅ CONTACTO ENCONTRADO\n";
        echo '   HubSpot Contact ID: '.($contact['id'] ?? 'N/A')."\n";
        echo '   Nombre: '.($contactProps['firstname'] ?? '').' '.($contactProps['lastname'] ?? '')."\n";
        echo '   Email: '.($contactProps['email'] ?? 'N/A')."\n\n";

        echo "   Campos xante_id en CONTACT:\n";
        $xanteFields = ['xante_id', 'xante_client_id', 'id_xante', 'client_xante_id'];
        foreach ($xanteFields as $field) {
            $value = $contactProps[$field] ?? null;
            $status = $value ? '✅' : '❌';
            $displayValue = $value ?: '(vacío)';
            echo "   - {$field}: {$status} {$displayValue}\n";
        }

        $contactId = $contact['id'];
    } else {
        echo "❌ No se encontró contacto con ese email\n";
        $contactId = null;
    }
} else {
    echo '❌ Error al buscar en Contacts: '.$contactResponse->status()."\n";
    $contactId = null;
}

echo "\n";

// ========================================
// 2. BUSCAR DEALS ASOCIADOS AL CONTACT
// ========================================
if ($contactId) {
    echo "🔍 BUSCANDO DEALS ASOCIADOS AL CONTACTO...\n";
    echo "------------------------------------------------\n";

    // Obtener deals asociados al contacto
    $dealsResponse = Http::withHeaders([
        'Authorization' => "Bearer {$token}",
    ])->get($baseUrl."/crm/v3/objects/contacts/{$contactId}/associations/deals");

    if ($dealsResponse->successful()) {
        $dealAssociations = $dealsResponse->json()['results'] ?? [];

        if (! empty($dealAssociations)) {
            echo '✅ DEALS ENCONTRADOS: '.count($dealAssociations)."\n\n";

            foreach ($dealAssociations as $index => $assoc) {
                $dealId = $assoc['id'] ?? $assoc['toObjectId'] ?? null;

                if (! $dealId) {
                    continue;
                }

                // Obtener detalles del deal
                $dealDetailsResponse = Http::withHeaders([
                    'Authorization' => "Bearer {$token}",
                ])->get($baseUrl."/crm/v3/objects/deals/{$dealId}", [
                    'properties' => implode(',', [
                        'dealname',
                        'amount',
                        'estatus_de_convenio',
                        'xante_id',
                        'xante_client_id',
                        'id_xante',
                        'client_xante_id',
                        'hs_object_id',
                    ]),
                ]);

                if ($dealDetailsResponse->successful()) {
                    $deal = $dealDetailsResponse->json();
                    $dealProps = $deal['properties'] ?? [];

                    echo '   📋 DEAL #'.($index + 1).":\n";
                    echo '      HubSpot Deal ID: '.($deal['id'] ?? 'N/A')."\n";
                    echo '      Nombre: '.($dealProps['dealname'] ?? 'N/A')."\n";
                    echo '      Monto: $'.number_format($dealProps['amount'] ?? 0, 2)."\n";
                    echo '      Estatus: '.($dealProps['estatus_de_convenio'] ?? 'N/A')."\n\n";

                    echo "      Campos xante_id en DEAL:\n";
                    $xanteFields = ['xante_id', 'xante_client_id', 'id_xante', 'client_xante_id'];
                    foreach ($xanteFields as $field) {
                        $value = $dealProps[$field] ?? null;
                        $status = $value ? '✅' : '❌';
                        $displayValue = $value ?: '(vacío)';
                        echo "      - {$field}: {$status} {$displayValue}\n";
                    }
                    echo "\n";
                }
            }
        } else {
            echo "❌ No se encontraron deals asociados\n\n";
        }
    } else {
        echo '❌ Error al buscar deals asociados: '.$dealsResponse->status()."\n\n";
    }
}

// ========================================
// 3. RESUMEN Y CONCLUSIÓN
// ========================================
echo "===============================================================\n";
echo "  RESUMEN\n";
echo "===============================================================\n\n";

echo "El campo 'xante_id' se encuentra en:\n";
echo "  📌 CONTACT (Cliente): Es donde se almacena el ID Xante\n";
echo "  📌 DEAL (Negocio): Puede o no tener este campo\n\n";

echo "En el sistema de sincronización actual:\n";
echo "  1. Se buscan Deals con estatus 'Aceptado'\n";
echo "  2. Se obtiene el Contact asociado al Deal\n";
echo "  3. Se extrae el 'xante_id' del CONTACT (no del Deal)\n";
echo "  4. Se crea/actualiza el cliente en la base de datos local\n\n";

echo "===============================================================\n\n";
