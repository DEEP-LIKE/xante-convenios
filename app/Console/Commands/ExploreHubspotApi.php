<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExploreHubspotApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hubspot:explore {--limit=5 : Number of records to fetch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Explore HubSpot API structure for contacts and deals';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Explorando API de HubSpot...');
        
        $token = config('hubspot.token');
        
        if (!$token) {
            $this->error('❌ HUBSPOT_TOKEN no está configurado en el archivo .env');
            return 1;
        }

        $this->info("✅ Token encontrado: " . substr($token, 0, 10) . "...");
        $limit = $this->option('limit');

        // Explorar Contacts
        $this->exploreContacts($token, $limit);
        
        // Explorar Deals
        $this->exploreDeals($token, $limit);
        
        return 0;
    }

    private function exploreContacts(string $token, int $limit): void
    {
        $this->info("\n📋 === EXPLORANDO CONTACTS ===");
        
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
            ])->get('https://api.hubapi.com/crm/v3/objects/contacts', [
                'limit' => $limit,
                'properties' => 'firstname,lastname,email,phone,hs_object_id,createdate,lastmodifieddate'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $this->info("✅ Respuesta exitosa - Total encontrados: " . ($data['total'] ?? 'N/A'));
                $this->info("📄 Registros en esta página: " . count($data['results'] ?? []));
                
                if (!empty($data['results'])) {
                    $this->info("\n📝 Estructura del primer contacto:");
                    $firstContact = $data['results'][0];
                    $this->line(json_encode($firstContact, JSON_PRETTY_PRINT));
                    
                    $this->info("\n🔑 Propiedades disponibles:");
                    if (isset($firstContact['properties'])) {
                        foreach ($firstContact['properties'] as $key => $value) {
                            $this->line("  - {$key}: " . (is_string($value) ? substr($value, 0, 50) : json_encode($value)));
                        }
                    }
                }
                
                // Buscar propiedades personalizadas
                $this->searchCustomProperties($token, 'contacts');
                
            } else {
                $this->error("❌ Error en Contacts API: " . $response->status());
                $this->error("Respuesta: " . $response->body());
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Excepción en Contacts: " . $e->getMessage());
            Log::error('HubSpot Contacts API Error', ['error' => $e->getMessage()]);
        }
    }

    private function exploreDeals(string $token, int $limit): void
    {
        $this->info("\n💼 === EXPLORANDO DEALS ===");
        
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
            ])->get('https://api.hubapi.com/crm/v3/objects/deals', [
                'limit' => $limit,
                'properties' => 'dealname,amount,dealstage,createdate,closedate,hs_object_id'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $this->info("✅ Respuesta exitosa - Total encontrados: " . ($data['total'] ?? 'N/A'));
                $this->info("📄 Registros en esta página: " . count($data['results'] ?? []));
                
                if (!empty($data['results'])) {
                    $this->info("\n📝 Estructura del primer deal:");
                    $firstDeal = $data['results'][0];
                    $this->line(json_encode($firstDeal, JSON_PRETTY_PRINT));
                    
                    $this->info("\n🔑 Propiedades disponibles:");
                    if (isset($firstDeal['properties'])) {
                        foreach ($firstDeal['properties'] as $key => $value) {
                            $this->line("  - {$key}: " . (is_string($value) ? substr($value, 0, 50) : json_encode($value)));
                        }
                    }
                }
                
                // Buscar propiedades personalizadas
                $this->searchCustomProperties($token, 'deals');
                
            } else {
                $this->error("❌ Error en Deals API: " . $response->status());
                $this->error("Respuesta: " . $response->body());
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Excepción en Deals: " . $e->getMessage());
            Log::error('HubSpot Deals API Error', ['error' => $e->getMessage()]);
        }
    }

    private function searchCustomProperties(string $token, string $objectType): void
    {
        $this->info("\n🔍 Buscando propiedades personalizadas para {$objectType}...");
        
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
            ])->get("https://api.hubapi.com/crm/v3/properties/{$objectType}");

            if ($response->successful()) {
                $properties = $response->json()['results'] ?? [];
                
                $customProperties = array_filter($properties, function($prop) {
                    return isset($prop['name']) && 
                           (strpos(strtolower($prop['name']), 'xante') !== false ||
                            strpos(strtolower($prop['label'] ?? ''), 'xante') !== false);
                });
                
                if (!empty($customProperties)) {
                    $this->info("🎯 Propiedades relacionadas con 'xante' encontradas:");
                    foreach ($customProperties as $prop) {
                        $this->line("  - Nombre: {$prop['name']} | Label: " . ($prop['label'] ?? 'N/A') . " | Tipo: " . ($prop['type'] ?? 'N/A'));
                    }
                } else {
                    $this->warn("⚠️  No se encontraron propiedades personalizadas con 'xante' en {$objectType}");
                }
                
            } else {
                $this->warn("⚠️  No se pudieron obtener las propiedades de {$objectType}");
            }
            
        } catch (\Exception $e) {
            $this->warn("⚠️  Error obteniendo propiedades de {$objectType}: " . $e->getMessage());
        }
    }
}
