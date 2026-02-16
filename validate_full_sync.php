<?php

use App\Models\Client;
use App\Actions\Hubspot\Transform\TransformHubspotDeal;
use Illuminate\Support\Facades\Http;

$token = env('HUBSPOT_TOKEN');

if (!$token) {
    echo "ERROR: HUBSPOT_TOKEN no está configurado\n";
    exit(1);
}

echo "=== VALIDACIÓN COMPLETA DE SINCRONIZACIÓN HUBSPOT ===\n\n";

// Buscar un Deal con datos completos
$searchResponse = Http::timeout(30)
    ->withHeaders([
        'Authorization' => "Bearer {$token}",
        'Content-Type' => 'application/json',
    ])
    ->post('https://api.hubapi.com/crm/v3/objects/deals/search', [
        'filterGroups' => [
            [
                'filters' => [
                    [
                        'propertyName' => 'nombre_del_desarrollo',
                        'operator' => 'HAS_PROPERTY'
                    ]
                ]
            ]
        ],
        'properties' => [
            'dealname',
            // Titular
            'nombre_completo', 'email', 'phone', 'curp', 'rfc', 'estado_civil', 'ocupacion',
            'domicilio_actual', 'colonia', 'codigo_postal', 'municipio', 'estado',
            // Propiedad
            'nombre_del_desarrollo', 'calle_o_privada_', 'tipo_de_inmueble_', 'ciudad', 'state',
            'hipotecada', 'tipo_de_hipoteca', 'niveles_casa',
            // Cónyuge
            'nombre_completo_conyuge', 'email_conyuge', 'telefono_movil_conyuge', 'curp_conyuge',
            'domicilio_actual_conyuge', 'colonia_conyuge', 'codigo_postal_conyuge',
            'municipio_conyuge', 'estado_conyuge',
        ],
        'limit' => 1
    ]);

if ($searchResponse->failed()) {
    echo "ERROR al buscar deals: " . $searchResponse->status() . "\n";
    exit(1);
}

$results = $searchResponse->json();
$deals = $results['results'] ?? [];

if (empty($deals)) {
    echo "No se encontraron deals con datos de propiedad\n";
    exit(0);
}

$deal = $deals[0];
$props = $deal['properties'];

echo "Deal encontrado: {$props['dealname']}\n";
echo "Deal ID: {$deal['id']}\n\n";

// Transformar datos
$transformer = new TransformHubspotDeal();
$transformedData = $transformer->execute($props);

// Mostrar resultados
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                  DATOS DEL TITULAR                            ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$titularFields = [
    'name' => 'Nombre',
    'email' => 'Email',
    'phone' => 'Teléfono',
    'curp' => 'CURP',
    'rfc' => 'RFC',
    'civil_status' => 'Estado Civil',
    'occupation' => 'Ocupación',
    'current_address' => 'Domicilio',
    'neighborhood' => 'Colonia',
    'postal_code' => 'Código Postal',
    'municipality' => 'Municipio',
    'state' => 'Estado',
];

foreach ($titularFields as $field => $label) {
    $value = $transformedData[$field] ?? 'NULL';
    $status = !empty($transformedData[$field]) ? '✅' : '❌';
    echo sprintf("%s %-20s: %s\n", $status, $label, $value);
}

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                  DATOS DE LA PROPIEDAD                        ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$propiedadFields = [
    'comunidad' => 'Comunidad/Desarrollo',
    'domicilio_convenio' => 'Domicilio Convenio',
    'tipo_vivienda' => 'Tipo de Vivienda',
    'municipio_propiedad' => 'Municipio',
    'estado_propiedad' => 'Estado',
    'hipotecado' => 'Hipotecado',
    'tipo_hipoteca' => 'Tipo de Hipoteca',
    'niveles' => 'Niveles',
];

foreach ($propiedadFields as $field => $label) {
    $value = $transformedData[$field] ?? 'NULL';
    $status = !empty($transformedData[$field]) ? '✅' : '❌';
    echo sprintf("%s %-25s: %s\n", $status, $label, $value);
}

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                  DATOS DEL CÓNYUGE                            ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

// Verificar si hay datos de cónyuge en HubSpot
$hasConyuge = !empty($props['nombre_completo_conyuge']);

if ($hasConyuge) {
    echo "✅ El Deal tiene datos de cónyuge en HubSpot\n\n";
    echo "nombre_completo_conyuge: " . ($props['nombre_completo_conyuge'] ?? 'NULL') . "\n";
    echo "email_conyuge: " . ($props['email_conyuge'] ?? 'NULL') . "\n";
    echo "telefono_movil_conyuge: " . ($props['telefono_movil_conyuge'] ?? 'NULL') . "\n";
    echo "curp_conyuge: " . ($props['curp_conyuge'] ?? 'NULL') . "\n";
} else {
    echo "ℹ️  El Deal NO tiene datos de cónyuge en HubSpot\n";
}

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                      RESUMEN                                  ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$totalFields = count($titularFields) + count($propiedadFields);
$filledFields = 0;

foreach (array_merge($titularFields, $propiedadFields) as $field => $label) {
    if (!empty($transformedData[$field])) {
        $filledFields++;
    }
}

$percentage = round(($filledFields / $totalFields) * 100);

echo "Campos con datos: {$filledFields}/{$totalFields} ({$percentage}%)\n";

if ($percentage >= 80) {
    echo "✅ EXCELENTE: La mayoría de los campos tienen datos\n";
} elseif ($percentage >= 50) {
    echo "⚠️  ACEPTABLE: Algunos campos están vacíos\n";
} else {
    echo "❌ ATENCIÓN: Muchos campos están vacíos\n";
}
