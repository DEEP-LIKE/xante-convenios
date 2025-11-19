<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExploreHubspotDeals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hubspot:explore-deals {--limit=5 : Number of deals to fetch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Explore HubSpot Deals API structure and find status field';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Explorando Deals de HubSpot...');
        
        $token = config('hubspot.token');
        
        if (!$token) {
            $this->error('❌ HUBSPOT_TOKEN no está configurado en el archivo .env');
            return 1;
        }

        $this->info("✅ Token encontrado: " . substr($token, 0, 10) . "...");
        $limit = $this->option('limit');

        // Explorar Deals
        $this->exploreDeals($token, $limit);
        
        // Buscar propiedades personalizadas
        $this->searchDealProperties($token);
        
        // Explorar asociaciones Deal → Contact
        $this->exploreAssociations($token);
        
        return 0;
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
                'properties' => 'dealname,amount,dealstage,closedate,createdate,hs_object_id'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $this->info("✅ Respuesta exitosa - Total encontrados: " . ($data['total'] ?? 'N/A'));
                $this->info("📄 Registros en esta página: " . count($data['results'] ?? []));
                
                if (!empty($data['results'])) {
                    $this->info("\n📝 Estructura del primer Deal:");
                    $firstDeal = $data['results'][0];
                    $this->line(json_encode($firstDeal, JSON_PRETTY_PRINT));
                    
                    $this->info("\n🔑 Propiedades disponibles en el Deal:");
                    if (isset($firstDeal['properties'])) {
                        foreach ($firstDeal['properties'] as $key => $value) {
                            $displayValue = is_string($value) ? substr($value, 0, 50) : json_encode($value);
                            $this->line("  - {$key}: {$displayValue}");
                        }
                    }

                    // Mostrar ID del Deal para explorar asociaciones
                    $dealId = $firstDeal['id'] ?? null;
                    if ($dealId) {
                        $this->info("\n🆔 ID del primer Deal: {$dealId}");
                    }
                }
                
            } else {
                $this->error("❌ Error en Deals API: " . $response->status());
                $this->error("Respuesta: " . $response->body());
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Excepción en Deals: " . $e->getMessage());
            Log::error('HubSpot Deals API Error', ['error' => $e->getMessage()]);
        }
    }

    private function searchDealProperties(string $token): void
    {
        $this->info("\n🔍 Buscando propiedades personalizadas de Deals...");
        
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
            ])->get("https://api.hubapi.com/crm/v3/properties/deals");

            if ($response->successful()) {
                $properties = $response->json()['results'] ?? [];
                
                // Buscar propiedades relacionadas con "estatus", "convenio", "status"
                $keywords = ['estatus', 'convenio', 'status', 'estado', 'aceptado', 'xante'];
                
                $relevantProperties = array_filter($properties, function($prop) use ($keywords) {
                    $name = strtolower($prop['name'] ?? '');
                    $label = strtolower($prop['label'] ?? '');
                    
                    foreach ($keywords as $keyword) {
                        if (strpos($name, $keyword) !== false || strpos($label, $keyword) !== false) {
                            return true;
                        }
                    }
                    return false;
                });
                
                if (!empty($relevantProperties)) {
                    $this->info("🎯 Propiedades relevantes encontradas:");
                    foreach ($relevantProperties as $prop) {
                        $this->line(sprintf(
                            "  - Nombre: %s | Label: %s | Tipo: %s",
                            $prop['name'] ?? 'N/A',
                            $prop['label'] ?? 'N/A',
                            $prop['type'] ?? 'N/A'
                        ));
                        
                        // Mostrar opciones si es un campo de selección
                        if (isset($prop['options']) && !empty($prop['options'])) {
                            $this->line("    Opciones disponibles:");
                            foreach ($prop['options'] as $option) {
                                $this->line("      • " . ($option['label'] ?? $option['value'] ?? 'N/A'));
                            }
                        }
                    }
                } else {
                    $this->warn("⚠️  No se encontraron propiedades relacionadas con estatus/convenio");
                }
                
                // Mostrar todas las propiedades personalizadas (no estándar)
                $customProperties = array_filter($properties, function($prop) {
                    return isset($prop['hubspotDefined']) && $prop['hubspotDefined'] === false;
                });
                
                if (!empty($customProperties)) {
                    $this->info("\n📋 Todas las propiedades personalizadas:");
                    foreach ($customProperties as $prop) {
                        $this->line(sprintf(
                            "  - %s (%s)",
                            $prop['name'] ?? 'N/A',
                            $prop['label'] ?? 'N/A'
                        ));
                    }
                }
                
            } else {
                $this->warn("⚠️  No se pudieron obtener las propiedades de Deals");
            }
            
        } catch (\Exception $e) {
            $this->warn("⚠️  Error obteniendo propiedades de Deals: " . $e->getMessage());
        }
    }

    private function exploreAssociations(string $token): void
    {
        $this->info("\n🔗 Explorando asociaciones Deal → Contact...");
        
        try {
            // Primero obtener un Deal
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
            ])->get('https://api.hubapi.com/crm/v3/objects/deals', [
                'limit' => 1,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $deals = $data['results'] ?? [];
                
                if (empty($deals)) {
                    $this->warn("⚠️  No hay Deals disponibles para explorar asociaciones");
                    return;
                }
                
                $dealId = $deals[0]['id'];
                $this->info("📌 Usando Deal ID: {$dealId}");
                
                // Obtener asociaciones del Deal
                $assocResponse = Http::withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Content-Type' => 'application/json',
                ])->get("https://api.hubapi.com/crm/v3/objects/deals/{$dealId}/associations/contacts");

                if ($assocResponse->successful()) {
                    $associations = $assocResponse->json();
                    
                    $this->info("✅ Asociaciones encontradas:");
                    $this->line(json_encode($associations, JSON_PRETTY_PRINT));
                    
                    $contacts = $associations['results'] ?? [];
                    if (!empty($contacts)) {
                        $contactId = $contacts[0]['id'] ?? null;
                        if ($contactId) {
                            $this->info("\n👤 Obteniendo datos del Contact asociado (ID: {$contactId})...");
                            
                            // Obtener datos del Contact
                            $contactResponse = Http::withHeaders([
                                'Authorization' => "Bearer {$token}",
                                'Content-Type' => 'application/json',
                            ])->get("https://api.hubapi.com/crm/v3/objects/contacts/{$contactId}", [
                                'properties' => 'firstname,lastname,email,phone,xante_id'
                            ]);

                            if ($contactResponse->successful()) {
                                $contact = $contactResponse->json();
                                $this->info("✅ Datos del Contact:");
                                $this->line(json_encode($contact, JSON_PRETTY_PRINT));
                            }
                        }
                    } else {
                        $this->warn("⚠️  Este Deal no tiene Contacts asociados");
                    }
                    
                } else {
                    $this->error("❌ Error obteniendo asociaciones: " . $assocResponse->status());
                }
                
            }
            
        } catch (\Exception $e) {
            $this->warn("⚠️  Error explorando asociaciones: " . $e->getMessage());
        }
    }
}
