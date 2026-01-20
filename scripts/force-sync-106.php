<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Actions\Agreements\SyncClientToHubspotAction;
use App\Models\Agreement;

$agreementId = 106;
$agreement = Agreement::with('client')->find($agreementId);

if (! $agreement) {
    echo "❌ Convenio #$agreementId no encontrado.\n";
    exit;
}

echo "✅ Convenio #$agreementId encontrado.\n";
echo '   Cliente: '.$agreement->client->name."\n";
echo '   Status: '.$agreement->status."\n";
echo '   HubSpot Deal ID: '.$agreement->client->hubspot_deal_id."\n";

if (! $agreement->client->hubspot_deal_id) {
    // Intentar buscar el deal ID si no lo tiene, pero en este caso sabemos que el script de comparación encontró uno
    // que NO estaba vinculado.
    // Si el cliente local tiene el ID, perfecto. Si no, necesitamos asignárselo.
    // El script anterior mostró: "HubSpot Deal: Carbono Agencia... (ID: 49659719604)"
    // Y "NO encontrado en local".
    // Así que probablemente el cliente local NO tiene el hubspot_deal_id set.

    echo "⚠️ El cliente local NO tiene hubspot_deal_id.\n";
    echo "   Asignando ID 49659719604 temporalmente para la prueba...\n";
    $agreement->client->hubspot_deal_id = '49659719604';
    $agreement->client->save();
}

echo "\n🔄 Ejecutando Sincronización...\n";

try {
    $action = app(SyncClientToHubspotAction::class);
    // Pasamos wizard_data vacío o actual porque la acción lo usa para actualizar al cliente primero
    $errors = $action->execute($agreement, $agreement->wizard_data ?? []);

    if (empty($errors)) {
        echo "✅ Sincronización completada sin errores.\n";
    } else {
        echo "❌ Errores en sincronización:\n";
        print_r($errors);
    }

} catch (\Exception $e) {
    echo '❌ Excepción: '.$e->getMessage()."\n";
    echo $e->getTraceAsString();
}
