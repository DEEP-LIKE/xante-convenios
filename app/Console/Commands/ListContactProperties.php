<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ListContactProperties extends Command
{
    protected $signature = 'hubspot:list-contact-properties';
    protected $description = 'Lista todas las propiedades disponibles para Contactos en HubSpot';

    public function handle()
    {
        $token = config('hubspot.token');
        $baseUrl = config('hubspot.api_base_url');
        
        $this->info("Consultando propiedades de Contactos en HubSpot...");
        
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json',
        ])->get("{$baseUrl}/crm/v3/properties/contacts");
        
        if ($response->successful()) {
            $properties = $response->json()['results'];
            $this->info("Se encontraron " . count($properties) . " propiedades.");
            
            $headers = ['Name', 'Label', 'Type', 'Group'];
            $data = [];
            
            // Filtrar propiedades relevantes (excluir metadatos de sistema irrelevantes si son muchos)
            foreach ($properties as $prop) {
                // Buscamos propiedades que suenen a lo que necesitamos
                $name = $prop['name'];
                $label = $prop['label'];
                
                // Filtro simple para no mostrar 500 propiedades
                if (
                    str_contains($name, 'curp') || 
                    str_contains($name, 'rfc') || 
                    str_contains($name, 'address') || 
                    str_contains($name, 'city') || 
                    str_contains($name, 'state') || 
                    str_contains($name, 'zip') || 
                    str_contains($name, 'colonia') || 
                    str_contains($name, 'municipio') || 
                    str_contains($name, 'civil') || 
                    str_contains($name, 'conyuge') || 
                    str_contains($name, 'birth') || 
                    str_contains($name, 'nacimiento') ||
                    str_contains($name, 'ocupacion') ||
                    str_contains($name, 'job') ||
                    str_contains($name, 'regimen')
                ) {
                    $data[] = [
                        $prop['name'],
                        $prop['label'],
                        $prop['type'],
                        $prop['groupName']
                    ];
                }
            }
            
            $this->table($headers, $data);
            
            // Guardar en archivo para análisis
            $output = "";
            foreach ($data as $row) {
                $output .= "{$row[0]} | {$row[1]} | {$row[2]}\n";
            }
            file_put_contents('hubspot_contact_properties.txt', $output);
            $this->info("Lista guardada en hubspot_contact_properties.txt");
            
        } else {
            $this->error("Error: " . $response->body());
        }
    }
}
