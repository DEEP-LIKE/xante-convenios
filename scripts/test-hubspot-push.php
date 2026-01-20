<?php

/**
 * Script para probar el envío de datos a HubSpot (Push)
 */

require __DIR__.'/../vendor/autoload.php';

use App\Models\Client;
use App\Services\HubspotSyncService;

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$userEmail = 'miguel.alfaro@carbono.mx';

echo "\n===============================================================\n";
echo "  TEST: Push Local -> HubSpot\n";
echo "===============================================================\n\n";

// 1. Obtener cliente
$client = Client::with('spouse')->where('email', $userEmail)->first();

if (! $client) {
    exit("❌ Error: Cliente {$userEmail} no encontrado en BD local.\n");
}

echo "Cliente encontrado: {$client->name} (ID: {$client->id})\n";
echo 'HubSpot Deal ID: '.($client->hubspot_deal_id ?: 'N/A')."\n";
echo 'HubSpot Contact ID: '.($client->hubspot_id ?: 'N/A')."\n\n";

if (! $client->hubspot_deal_id) {
    exit("❌ Error: El cliente no tiene Deal ID asociado. No se puede probar el push.\n");
}

// 2. Simular cambio de datos (Opcional - descomentar para probar cambios reales)
// $client->occupation = "Occupation Test " . date('H:i:s');
// $client->save();
// echo "Datos locales actualizados para prueba.\n\n";

// 3. Ejecutar Push
echo "Ejecutando pushClientToHubspot()...\n";

try {
    $service = new HubspotSyncService;
    $result = $service->pushClientToHubspot($client);

    echo "\nResultados:\n";
    echo "---------------------------------------------------------------\n";
    echo 'Deal Actualizado: '.($result['deal_updated'] ? '✅ SI' : '❌ NO')."\n";
    echo 'Contact Actualizado: '.($result['contact_updated'] ? '✅ SI' : '❌ NO')."\n";

    if (! empty($result['errors'])) {
        echo "\nErrores:\n";
        foreach ($result['errors'] as $error) {
            echo " - {$error}\n";
        }
    } else {
        echo "\n✅ Proceso completado sin errores reportados.\n";
    }

} catch (\Exception $e) {
    echo "\n❌ Excepción: ".$e->getMessage()."\n";
    echo $e->getTraceAsString();
}

echo "\n===============================================================\n";
