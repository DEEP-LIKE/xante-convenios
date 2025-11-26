<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\Client;
use App\Models\Agreement;

// Configuración
$targetEmail = 'miguel.alfaro@carbono.mx';
$token = config('hubspot.token');
$baseUrl = config('hubspot.api_base_url');

echo "\n🔍 AUDITORÍA PROFUNDA DE SINCRONIZACIÓN HUBSPOT\n";
echo "================================================\n";

// 1. Obtener Datos Locales
echo "\n📂 1. DATOS LOCALES (APP)\n";
$client = Client::where('email', $targetEmail)->with('spouse')->first();

if (!$client) {
    echo "❌ Cliente no encontrado en local.\n";
    exit;
}

$agreement = Agreement::where('client_id', $client->id)->latest()->first();

echo "   Cliente: {$client->name} (ID: {$client->id})\n";
echo "   HubSpot Contact ID: {$client->hubspot_id}\n";
echo "   HubSpot Deal ID: {$client->hubspot_deal_id}\n";
echo "   Agreement Status: " . ($agreement ? $agreement->status : 'N/A') . "\n";
echo "   Proposal Value: " . ($agreement ? $agreement->proposal_value : 'N/A') . "\n";

// 2. Obtener Datos HubSpot (Contacto)
echo "\n👤 2. DATOS HUBSPOT (CONTACTO)\n";
$hsContact = null;
if ($client->hubspot_id) {
    $response = Http::withToken($token)->get("$baseUrl/crm/v3/objects/contacts/{$client->hubspot_id}?properties=firstname,lastname,email,phone,mobilephone,address,city,state,zip,jobtitle");
    if ($response->successful()) {
        $hsContact = $response->json()['properties'];
        print_r($hsContact);
    } else {
        echo "❌ Error al obtener contacto HubSpot: " . $response->body() . "\n";
    }
}

// 3. Obtener Datos HubSpot (Deal)
echo "\n💼 3. DATOS HUBSPOT (DEAL)\n";
$hsDeal = null;
if ($client->hubspot_deal_id) {
    // Solicitar TODAS las propiedades para ver nombres internos
    $response = Http::withToken($token)->get("$baseUrl/crm/v3/objects/deals/{$client->hubspot_deal_id}?properties=dealname,amount,dealstage,pipeline,estatus_de_convenio,nombre_del_titular,calle_o_privada_,colonia,municipio,estado,curp,rfc,estado_civil,ocupacion");
    if ($response->successful()) {
        $hsDeal = $response->json()['properties'];
        print_r($hsDeal);
    } else {
        echo "❌ Error al obtener deal HubSpot: " . $response->body() . "\n";
    }
}

// 4. Análisis de Mapeo
echo "\n📊 4. ANÁLISIS DE MAPEO Y SINCRONIZACIÓN\n";
echo str_pad("CAMPO LOCAL", 30) . str_pad("CAMPO HUBSPOT", 30) . str_pad("VALOR LOCAL", 30) . str_pad("VALOR HUBSPOT", 30) . "ESTADO\n";
echo str_repeat("-", 140) . "\n";

$checks = [
    // Contacto
    ['Local: Email', 'HS: email', $client->email, $hsContact['email'] ?? 'N/A'],
    ['Local: Teléfono', 'HS: phone', $client->phone, $hsContact['phone'] ?? 'N/A'],
    ['Local: Dirección', 'HS: address', $client->current_address, $hsContact['address'] ?? 'N/A'],
    ['Local: Municipio', 'HS: city', $client->municipality, $hsContact['city'] ?? 'N/A'],
    ['Local: Estado', 'HS: state', $client->state, $hsContact['state'] ?? 'N/A'],
    ['Local: CP', 'HS: zip', $client->postal_code, $hsContact['zip'] ?? 'N/A'],
    
    // Deal - Campos mapeados en HubspotSyncService
    ['Local: Nombre', 'HS: nombre_del_titular', $client->name, $hsDeal['nombre_del_titular'] ?? 'N/A'],
    ['Local: Dirección', 'HS: calle_o_privada_', $client->current_address, $hsDeal['calle_o_privada_'] ?? 'N/A'],
    ['Local: Colonia', 'HS: colonia', $client->neighborhood, $hsDeal['colonia'] ?? 'N/A'],
    ['Local: Estado', 'HS: estado', $client->state, $hsDeal['estado'] ?? 'N/A'],
    
    // Deal - Campos críticos de flujo
    ['Agreement: Status', 'HS: estatus_de_convenio', $agreement ? $agreement->status : 'N/A', $hsDeal['estatus_de_convenio'] ?? 'N/A'],
    ['Agreement: Value', 'HS: amount', $agreement ? $agreement->proposal_value : 'N/A', $hsDeal['amount'] ?? 'N/A'],
];

foreach ($checks as $check) {
    $localVal = (string)$check[2];
    $hsVal = (string)$check[3];
    
    // Normalizar para comparación (quitar espacios extra, minúsculas)
    $match = trim(strtolower($localVal)) == trim(strtolower($hsVal));
    
    // Excepciones de lógica
    if (str_contains($check[0], 'Status')) {
        // Mapeo especial de status
        $statusMap = ['completed' => 'Aceptado', 'draft' => 'En Proceso', 'in_progress' => 'En Proceso'];
        $expectedHs = $statusMap[$localVal] ?? $localVal;
        $match = strtolower($expectedHs) == strtolower($hsVal);
    }
    
    $statusIcon = $match ? "✅ OK" : "⚠️ DIFERENCIA";
    
    echo str_pad(substr($check[0], 0, 28), 30) . 
         str_pad(substr($check[1], 0, 28), 30) . 
         str_pad(substr($localVal, 0, 28), 30) . 
         str_pad(substr($hsVal, 0, 28), 30) . 
         "$statusIcon\n";
}

echo "\n";
