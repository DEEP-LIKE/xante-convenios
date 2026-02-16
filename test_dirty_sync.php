<?php

use App\Models\Client;
use App\Actions\Agreements\UpdateClientFromWizardAction;

echo "=== PRUEBA DE DIRTY SYNC ===\n\n";

// Buscar un cliente con datos
$client = Client::whereNotNull('hubspot_deal_id')->first();

if (!$client) {
    echo "No se encontró ningún cliente\n";
    exit(0);
}

echo "Cliente: {$client->name}\n";
echo "ID: {$client->id}\n\n";

echo "--- VALORES ORIGINALES EN BD ---\n";
echo "Email: {$client->email}\n";
echo "Phone: {$client->phone}\n";
echo "Comunidad: " . ($client->comunidad ?? 'NULL') . "\n";
echo "Domicilio Convenio: " . ($client->domicilio_convenio ?? 'NULL') . "\n\n";

// Simular datos del Wizard
// Escenario 1: Solo cambiar el teléfono
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  ESCENARIO 1: Cambiar solo el teléfono                       ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$wizardData1 = [
    'holder_email' => $client->email,        // Sin cambio
    'holder_phone' => '555-TEST-1111',       // ✅ CAMBIO
    'holder_curp' => $client->curp,          // Sin cambio
    'comunidad' => $client->comunidad,       // Sin cambio
];

$action = new UpdateClientFromWizardAction();
$dirtyFields1 = $action->execute($client->id, $wizardData1);

echo "Campos detectados como modificados:\n";
if (empty($dirtyFields1)) {
    echo "❌ NINGUNO (ERROR)\n";
} else {
    foreach ($dirtyFields1 as $entity => $fields) {
        echo "\n{$entity}:\n";
        foreach ($fields as $field => $value) {
            echo "  ✅ {$field}: {$value}\n";
        }
    }
}

echo "\n✅ ESPERADO: Solo 'phone' debe aparecer\n";
echo "❌ ERROR SI: Aparecen 'email', 'curp', 'comunidad' u otros campos\n\n";

// Recargar cliente para siguiente test
$client->refresh();

// Escenario 2: Cambiar múltiples campos
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  ESCENARIO 2: Cambiar teléfono Y email                       ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$wizardData2 = [
    'holder_email' => 'nuevo@email.com',     // ✅ CAMBIO
    'holder_phone' => '555-TEST-2222',       // ✅ CAMBIO
    'holder_curp' => $client->curp,          // Sin cambio
    'comunidad' => $client->comunidad,       // Sin cambio
];

$dirtyFields2 = $action->execute($client->id, $wizardData2);

echo "Campos detectados como modificados:\n";
if (empty($dirtyFields2)) {
    echo "❌ NINGUNO (ERROR)\n";
} else {
    foreach ($dirtyFields2 as $entity => $fields) {
        echo "\n{$entity}:\n";
        foreach ($fields as $field => $value) {
            echo "  ✅ {$field}: {$value}\n";
        }
    }
}

echo "\n✅ ESPERADO: Solo 'phone' y 'email' deben aparecer\n";
echo "❌ ERROR SI: Aparecen 'curp', 'comunidad' u otros campos\n\n";

// Recargar cliente
$client->refresh();

// Escenario 3: No cambiar nada
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  ESCENARIO 3: No cambiar ningún campo                        ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$wizardData3 = [
    'holder_email' => $client->email,        // Sin cambio
    'holder_phone' => $client->phone,        // Sin cambio
    'holder_curp' => $client->curp,          // Sin cambio
    'comunidad' => $client->comunidad,       // Sin cambio
];

$dirtyFields3 = $action->execute($client->id, $wizardData3);

echo "Campos detectados como modificados:\n";
if (empty($dirtyFields3)) {
    echo "✅ NINGUNO (CORRECTO)\n";
} else {
    echo "❌ ERROR: Se detectaron cambios cuando no debería haber ninguno:\n";
    foreach ($dirtyFields3 as $entity => $fields) {
        echo "\n{$entity}:\n";
        foreach ($fields as $field => $value) {
            echo "  ❌ {$field}: {$value}\n";
        }
    }
}

echo "\n✅ ESPERADO: Ningún campo debe aparecer\n";
echo "❌ ERROR SI: Aparece cualquier campo\n\n";

// Escenario 4: Cambiar campo con espacios
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  ESCENARIO 4: Cambiar campo agregando solo espacios          ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$wizardData4 = [
    'holder_email' => " {$client->email} ",  // Mismo valor con espacios
    'holder_phone' => $client->phone,        // Sin cambio
];

$dirtyFields4 = $action->execute($client->id, $wizardData4);

echo "Campos detectados como modificados:\n";
if (empty($dirtyFields4)) {
    echo "✅ NINGUNO (CORRECTO - trim() funcionó)\n";
} else {
    echo "⚠️  Se detectaron cambios (puede ser esperado si trim() no se aplicó):\n";
    foreach ($dirtyFields4 as $entity => $fields) {
        echo "\n{$entity}:\n";
        foreach ($fields as $field => $value) {
            echo "  {$field}: '{$value}'\n";
        }
    }
}

echo "\n✅ ESPERADO: Ningún campo (trim() debe normalizar espacios)\n";
echo "⚠️  ACEPTABLE: Solo 'email' si decidimos NO aplicar trim()\n\n";

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                      RESUMEN FINAL                            ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

echo "Si todos los escenarios pasaron correctamente:\n";
echo "✅ Dirty Sync está funcionando PERFECTAMENTE\n";
echo "✅ Solo se enviarán a HubSpot los campos que realmente cambiaron\n";
echo "✅ El historial de HubSpot se mantendrá limpio\n";
