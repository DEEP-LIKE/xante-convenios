<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Agreement;
use App\Models\Client;
use Illuminate\Support\Facades\Http;

$email = 'miguel.alfaro@carbono.mx';
$token = config('hubspot.token');
$baseUrl = config('hubspot.api_base_url');

// ... (inicio igual)

try {
    // 1. Buscar Contacto en HubSpot
    echo "\n📡 Consultando HubSpot (Contact Search)...\n";
    $response = Http::withToken($token)->post("$baseUrl/crm/v3/objects/contacts/search", [
        'filterGroups' => [
            [
                'filters' => [
                    [
                        'propertyName' => 'email',
                        'operator' => 'EQ',
                        'value' => $email,
                    ],
                ],
            ],
        ],
        'properties' => [
            'firstname', 'lastname', 'email', 'phone', 'mobilephone', 'address', 'city', 'state', 'zip', 'jobtitle',
        ],
    ]);

    if (! $response->successful()) {
        echo '❌ Error al buscar contacto en HubSpot: '.$response->body()."\n";
        exit;
    }

    $results = $response->json()['results'] ?? [];

    if (empty($results)) {
        echo "❌ No se encontró el contacto en HubSpot.\n";
        exit;
    }

    $contact = $results[0];
    $contactId = $contact['id'];
    $props = $contact['properties'];

    echo "✅ Contacto encontrado (ID: $contactId)\n";
    echo '   Nombre: '.($props['firstname'] ?? '').' '.($props['lastname'] ?? '')."\n";
    echo '   Email: '.($props['email'] ?? '')."\n";
    echo '   Teléfono: '.($props['phone'] ?? $props['mobilephone'] ?? 'N/A')."\n";

    // 2. Buscar Negocios (Deals) asociados
    echo "\n📡 Consultando Negocios Asociados en HubSpot...\n";
    $associationsResponse = Http::withToken($token)->get("$baseUrl/crm/v3/objects/contacts/$contactId/associations/deals");

    $dealIds = [];
    if ($associationsResponse->successful()) {
        $associations = $associationsResponse->json()['results'] ?? [];
        foreach ($associations as $assoc) {
            // Intentar diferentes estructuras posibles
            if (isset($assoc['toObjectId'])) {
                $dealIds[] = $assoc['toObjectId'];
            } elseif (isset($assoc['id'])) {
                $dealIds[] = $assoc['id'];
            }
        }
    }

    echo '   Negocios encontrados: '.count($dealIds)."\n";

    $hubspotDeals = [];
    if (! empty($dealIds)) {
        foreach ($dealIds as $dealId) {
            // Usar query string explícito que sabemos que funciona
            $dealResponse = Http::withToken($token)->get("$baseUrl/crm/v3/objects/deals/$dealId?properties=estatus_de_convenio,dealstage,pipeline,amount,dealname,tipo_de_vivienda,desarrollo,calle_y_numero,colonia,municipio,estado");

            if ($dealResponse->successful()) {
                $hubspotDeals[] = $dealResponse->json();
            }
        }
    }

    // 3. Buscar Información Local
    echo "\n💾 Consultando Base de Datos Local...\n";
    $localClient = Client::where('email', $email)->first();

    if ($localClient) {
        echo "✅ Cliente Local encontrado (ID: {$localClient->id})\n";
        echo "   Nombre: {$localClient->first_name} {$localClient->last_name}\n";
        echo "   HubSpot ID: {$localClient->hubspot_id}\n";

        $localAgreements = Agreement::where('client_id', $localClient->id)->get();
        echo '   Convenios Locales: '.$localAgreements->count()."\n";
    } else {
        echo "❌ Cliente NO encontrado en base de datos local.\n";
    }

    // 4. Comparativa
    echo "\n📊 COMPARATIVA DETALLADA\n";
    echo "================================================\n";

    if ($localClient) {
        echo str_pad('CAMPO', 20).str_pad('HUBSPOT', 40).str_pad('LOCAL', 40)."\n";
        echo str_repeat('-', 100)."\n";

        $fields = [
            'Nombre' => [($props['firstname'] ?? ''), $localClient->first_name],
            'Apellido' => [($props['lastname'] ?? ''), $localClient->last_name],
            'Email' => [($props['email'] ?? ''), $localClient->email],
            'Teléfono' => [($props['phone'] ?? $props['mobilephone'] ?? ''), $localClient->phone],
            'HubSpot ID' => [$contactId, $localClient->hubspot_id],
        ];

        foreach ($fields as $label => $values) {
            $hsVal = substr((string) $values[0], 0, 38);
            $locVal = substr((string) $values[1], 0, 38);
            $match = $values[0] == $values[1] ? '✅' : '⚠️';
            echo str_pad($label, 20).str_pad($hsVal, 40).str_pad($locVal, 40)." $match\n";
        }
    }

    echo "\n💼 COMPARATIVA DE NEGOCIOS (DEALS)\n";
    echo "================================================\n";

    foreach ($hubspotDeals as $hsDeal) {
        $hProps = $hsDeal['properties'];
        $dealId = $hsDeal['id'];
        $dealName = $hProps['dealname'] ?? 'N/A';

        echo "\n🔹 HubSpot Deal: $dealName (ID: $dealId)\n";
        echo '   Estatus Convenio: '.($hProps['estatus_de_convenio'] ?? 'N/A')."\n";
        echo '   Monto: '.($hProps['amount'] ?? '0')."\n";

        // Buscar si existe en local
        if ($localClient) {
            $localAgreement = Agreement::where('client_id', $localClient->id)
                ->get()
                ->filter(function ($agreement) use ($dealId) {
                    $data = $agreement->wizard_data;

                    return is_array($data) && isset($data['hubspot_deal_id']) && $data['hubspot_deal_id'] == $dealId;
                })->first();

            if ($localAgreement) {
                echo "   ✅ Coincidencia Local: Convenio #{$localAgreement->id}\n";
                echo "      Status Local: {$localAgreement->status}\n";
            } else {
                echo "   ⚠️ NO encontrado en local (o no vinculado por ID)\n";
            }
        }
    }

} catch (\Exception $e) {
    echo "\n❌ ERROR CRÍTICO: ".$e->getMessage()."\n";
    echo 'File: '.$e->getFile().' Line: '.$e->getLine()."\n";
}

echo "\n";
