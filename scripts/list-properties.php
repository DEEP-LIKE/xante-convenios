<?php

require __DIR__.'/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$token = config('hubspot.token');
$baseUrl = config('hubspot.api_base_url');

echo "\n===============================================================\n";
echo "  LISTADO DE PROPIEDADES DE HUBSPOT\n";
echo "===============================================================\n\n";

// Definir los objetos a consultar
$objects = [
    'contacts' => 'CONTACTOS (Clientes/Usuarios)',
    'deals' => 'DEALS (Negocios)',
];

foreach ($objects as $objectType => $displayName) {
    echo "\n";
    echo "===============================================================\n";
    echo "  {$displayName}\n";
    echo "===============================================================\n\n";

    $response = Http::withHeaders([
        'Authorization' => "Bearer {$token}",
    ])->get($baseUrl."/crm/v3/properties/{$objectType}");

    if ($response->successful()) {
        $properties = $response->json('results');

        // Separar propiedades por tipo
        $defaultProperties = [];
        $customProperties = [];

        foreach ($properties as $prop) {
            // Las propiedades custom tienen un grupo que empieza con el nombre del objeto
            // o tienen hubspotDefined = false
            if (isset($prop['hubspotDefined']) && $prop['hubspotDefined'] === false) {
                $customProperties[] = $prop;
            } else {
                $defaultProperties[] = $prop;
            }
        }

        echo 'Total propiedades: '.count($properties)."\n";
        echo '  - Propiedades por defecto: '.count($defaultProperties)."\n";
        echo '  - Propiedades custom: '.count($customProperties)."\n\n";

        // Mostrar propiedades por defecto
        if (count($defaultProperties) > 0) {
            echo "PROPIEDADES POR DEFECTO:\n";
            echo "------------------------------------------------\n";
            printf("%-40s | %-40s | %-15s | %s\n", 'NOMBRE INTERNO (API)', 'ETIQUETA (LABEL)', 'TIPO', 'GRUPO');
            echo "------------------------------------------------\n";

            foreach ($defaultProperties as $prop) {
                $name = $prop['name'];
                $label = $prop['label'];
                $type = $prop['type'];
                $group = $prop['groupName'] ?? 'N/A';

                printf(
                    "%-40s | %-40s | %-15s | %s\n",
                    substr($name, 0, 40),
                    substr($label, 0, 40),
                    substr($type, 0, 15),
                    substr($group, 0, 30)
                );
            }
            echo "\n";
        }

        // Mostrar propiedades custom
        if (count($customProperties) > 0) {
            echo "PROPIEDADES CUSTOM:\n";
            echo "------------------------------------------------\n";
            printf("%-40s | %-40s | %-15s | %s\n", 'NOMBRE INTERNO (API)', 'ETIQUETA (LABEL)', 'TIPO', 'GRUPO');
            echo "------------------------------------------------\n";

            foreach ($customProperties as $prop) {
                $name = $prop['name'];
                $label = $prop['label'];
                $type = $prop['type'];
                $group = $prop['groupName'] ?? 'N/A';

                printf(
                    "%-40s | %-40s | %-15s | %s\n",
                    substr($name, 0, 40),
                    substr($label, 0, 40),
                    substr($type, 0, 15),
                    substr($group, 0, 30)
                );
            }
            echo "\n";
        }

    } else {
        echo "Error al obtener propiedades de {$displayName}: ".$response->status()."\n";
        echo $response->body()."\n";
    }
}

echo "\n===============================================================\n";
echo "  FIN DEL LISTADO\n";
echo "===============================================================\n\n";
