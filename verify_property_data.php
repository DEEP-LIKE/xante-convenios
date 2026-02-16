<?php

use App\Models\Client;

// Buscar un cliente con datos de HubSpot
$client = Client::whereNotNull('hubspot_deal_id')->first();

if ($client) {
    echo "=== VERIFICACIÓN DE DATOS DE PROPIEDAD ===\n\n";
    echo "Cliente: {$client->name}\n";
    echo "HubSpot Deal ID: {$client->hubspot_deal_id}\n\n";
    
    echo "--- Datos de la Propiedad ---\n";
    echo "Domicilio Convenio: " . ($client->domicilio_convenio ?? 'NULL') . "\n";
    echo "Comunidad: " . ($client->comunidad ?? 'NULL') . "\n";
    echo "Tipo Vivienda: " . ($client->tipo_vivienda ?? 'NULL') . "\n";
    echo "Prototipo: " . ($client->prototipo ?? 'NULL') . "\n";
    echo "Lote: " . ($client->lote ?? 'NULL') . "\n";
    echo "Manzana: " . ($client->manzana ?? 'NULL') . "\n";
    echo "Etapa: " . ($client->etapa ?? 'NULL') . "\n";
    echo "Municipio Propiedad: " . ($client->municipio_propiedad ?? 'NULL') . "\n";
    echo "Estado Propiedad: " . ($client->estado_propiedad ?? 'NULL') . "\n";
} else {
    echo "No se encontró ningún cliente con HubSpot Deal ID\n";
}
