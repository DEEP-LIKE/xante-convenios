<?php

$json = file_get_contents(storage_path('logs/hubspot_all_deal_properties.json'));
$properties = json_decode($json, true);

echo "=== BÚSQUEDA DE CAMPOS DE PROPIEDAD EN HUBSPOT ===\n\n";

$keywords = [
    'desarrollo', 'inmueble', 'vivienda', 'casa', 'calle', 'privada',
    'domicilio', 'comunidad', 'prototipo', 'lote', 'manzana', 'etapa',
    'municipio', 'estado', 'hipoteca', 'ciudad'
];

$found = [];

foreach ($properties as $prop) {
    $name = $prop['name'] ?? '';
    $label = $prop['label'] ?? '';
    
    foreach ($keywords as $keyword) {
        if (stripos($name, $keyword) !== false || stripos($label, $keyword) !== false) {
            $found[$name] = [
                'name' => $name,
                'label' => $label,
                'type' => $prop['type'] ?? 'unknown',
            ];
            break;
        }
    }
}

echo "Encontrados " . count($found) . " campos relacionados:\n\n";
echo str_pad("NOMBRE INTERNO", 50) . " | " . str_pad("ETIQUETA", 50) . " | TIPO\n";
echo str_repeat("-", 120) . "\n";

foreach ($found as $prop) {
    echo sprintf("%-50s | %-50s | %s\n", 
        $prop['name'], 
        $prop['label'], 
        $prop['type']
    );
}

// Guardar en archivo
$outputFile = storage_path('logs/property_fields_mapping.txt');
ob_start();
echo "MAPEO DE CAMPOS DE PROPIEDAD - HUBSPOT\n";
echo "Generado: " . date('Y-m-d H:i:s') . "\n\n";
echo str_pad("NOMBRE INTERNO", 50) . " | " . str_pad("ETIQUETA", 50) . " | TIPO\n";
echo str_repeat("-", 120) . "\n";
foreach ($found as $prop) {
    echo sprintf("%-50s | %-50s | %s\n", 
        $prop['name'], 
        $prop['label'], 
        $prop['type']
    );
}
$content = ob_get_clean();
file_put_contents($outputFile, $content);

echo "\n\nArchivo guardado en: {$outputFile}\n";
