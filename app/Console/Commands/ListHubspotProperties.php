<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ListHubspotProperties extends Command
{
    protected $signature = 'hubspot:list-properties {object=deals}';
    protected $description = 'Lista todas las propiedades disponibles en HubSpot para un objeto (deals, contacts)';

    public function handle()
    {
        $object = $this->argument('object');
        $token = config('hubspot.token');
        $baseUrl = config('hubspot.api_base_url');

        if (!$token) {
            $this->error('Token de HubSpot no configurado');
            return 1;
        }

        $this->info("Consultando propiedades de '{$object}' en HubSpot...");

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                ])
                ->get("{$baseUrl}/crm/v3/properties/{$object}");

            if (!$response->successful()) {
                $this->error("Error HTTP {$response->status()}: {$response->body()}");
                return 1;
            }

            $properties = $response->json()['results'] ?? [];

            if (empty($properties)) {
                $this->warn('No se encontraron propiedades');
                return 0;
            }

            // Crear tabla
            $headers = ['Nombre Interno', 'Etiqueta', 'Tipo', 'Descripción'];
            $rows = [];

            foreach ($properties as $prop) {
                $rows[] = [
                    $prop['name'] ?? 'N/A',
                    $prop['label'] ?? 'N/A',
                    $prop['type'] ?? 'N/A',
                    substr($prop['description'] ?? '', 0, 50),
                ];
            }

            $this->table($headers, $rows);
            $this->info("\nTotal de propiedades: " . count($properties));

            // Guardar en archivo
            $filename = storage_path("logs/hubspot_{$object}_properties_" . date('Y-m-d_His') . ".json");
            file_put_contents($filename, json_encode($properties, JSON_PRETTY_PRINT));
            $this->info("Detalles completos guardados en: {$filename}");

            return 0;

        } catch (\Exception $e) {
            $this->error("Excepción: " . $e->getMessage());
            return 1;
        }
    }
}
