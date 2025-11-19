<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiagnoseHubspotRelations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hubspot:diagnose-relations {--deal-id= : Specific Deal ID to diagnose}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnosticar relaciones entre Deals y Contacts en HubSpot';

    private string $token;
    private string $baseUrl;

    public function __construct()
    {
        parent::__construct();
        $this->token = config('hubspot.token');
        $this->baseUrl = config('hubspot.api_base_url');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Diagnóstico de Relaciones HubSpot Deal ↔ Contact');
        $this->info('═══════════════════════════════════════════════════');
        
        if (!$this->token) {
            $this->error('❌ HUBSPOT_TOKEN no está configurado');
            return 1;
        }

        $dealId = $this->option('deal-id');

        try {
            // Si no se proporciona Deal ID, obtener el primero disponible
            if (!$dealId) {
                $dealId = $this->getFirstDealId();
                if (!$dealId) {
                    $this->error('❌ No se encontraron Deals en HubSpot');
                    return 1;
                }
            }

            $this->info("\n🎯 Analizando Deal ID: {$dealId}");
            $this->newLine();

            // Estrategia 1: Analizar propiedades del Deal
            $deal = $this->strategy1_analyzeDealProperties($dealId);
            
            // Estrategia 2: Verificar asociaciones API v3
            $this->strategy2_checkAssociationsV3($dealId);
            
            // Estrategia 3: Verificar asociaciones API v4
            $this->strategy3_checkAssociationsV4($dealId);
            
            // Estrategia 4: Search API con asociaciones
            $this->strategy4_searchWithAssociations($dealId);
            
            // Estrategia 5: Buscar campo hs_all_associated_contact_ids
            $this->strategy5_checkAssociatedContactIds($dealId);
            
            // Resumen y recomendaciones
            $this->showRecommendations();

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            Log::error('HubSpot Diagnosis Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    private function getFirstDealId(): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/crm/v3/objects/deals', [
                'limit' => 1
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $deals = $data['results'] ?? [];
                
                if (!empty($deals)) {
                    return $deals[0]['id'];
                }
            }
        } catch (\Exception $e) {
            $this->warn("Error obteniendo Deal: " . $e->getMessage());
        }

        return null;
    }

    private function strategy1_analyzeDealProperties(string $dealId): ?array
    {
        $this->info("📦 ESTRATEGIA 1: Analizando propiedades del Deal");
        $this->line('─────────────────────────────────────────────────');

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . "/crm/v3/objects/deals/{$dealId}");

            if ($response->successful()) {
                $deal = $response->json();
                $properties = $deal['properties'] ?? [];
                
                $this->line("Deal Name: " . ($properties['dealname'] ?? 'N/A'));
                $this->line("Amount: " . ($properties['amount'] ?? 'N/A'));
                
                // Buscar campos relacionados con contactos
                $this->newLine();
                $this->info("🔎 Buscando campos relacionados con contactos...");
                
                $contactFields = [];
                $keywords = ['contact', 'cliente', 'email', 'telefono', 'phone', 'xante', 'associated', 'nombre'];
                
                foreach ($properties as $key => $value) {
                    $lowerKey = strtolower($key);
                    foreach ($keywords as $keyword) {
                        if (str_contains($lowerKey, $keyword)) {
                            $contactFields[$key] = $value;
                            break;
                        }
                    }
                }
                
                if (!empty($contactFields)) {
                    $this->info("✅ Campos potencialmente útiles encontrados:");
                    foreach ($contactFields as $key => $value) {
                        $displayValue = is_string($value) ? substr($value, 0, 100) : json_encode($value);
                        $this->line("   • {$key}: {$displayValue}");
                    }
                } else {
                    $this->warn("⚠️  No se encontraron campos obvios con datos de contacto");
                }
                
                $this->newLine();
                return $deal;
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }

        return null;
    }

    private function strategy2_checkAssociationsV3(string $dealId): void
    {
        $this->info("📦 ESTRATEGIA 2: Verificando asociaciones API v3");
        $this->line('─────────────────────────────────────────────────');

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . "/crm/v3/objects/deals/{$dealId}/associations/contacts");

            if ($response->successful()) {
                $data = $response->json();
                $results = $data['results'] ?? [];
                
                if (!empty($results)) {
                    $this->info("✅ ¡Asociaciones encontradas! Total: " . count($results));
                    
                    foreach (array_slice($results, 0, 3) as $assoc) {
                        $contactId = $assoc['id'] ?? $assoc['toObjectId'] ?? null;
                        if ($contactId) {
                            $this->line("   • Contact ID: {$contactId}");
                            
                            // Obtener datos del contacto
                            $contact = $this->getContactData($contactId);
                            if ($contact) {
                                $props = $contact['properties'] ?? [];
                                $name = trim(($props['firstname'] ?? '') . ' ' . ($props['lastname'] ?? ''));
                                $email = $props['email'] ?? 'N/A';
                                $xanteId = $props['xante_id'] ?? 'N/A';
                                
                                $this->line("     → Nombre: {$name}");
                                $this->line("     → Email: {$email}");
                                $this->line("     → Xante ID: {$xanteId}");
                            }
                        }
                    }
                } else {
                    $this->warn("⚠️  No hay asociaciones Deal→Contact en API v3");
                }
            } else {
                $this->warn("⚠️  Error en API v3: " . $response->status());
            }
        } catch (\Exception $e) {
            $this->error("❌ Error verificando asociaciones v3: " . $e->getMessage());
        }

        $this->newLine();
    }

    private function strategy3_checkAssociationsV4(string $dealId): void
    {
        $this->info("📦 ESTRATEGIA 3: Verificando asociaciones API v4");
        $this->line('─────────────────────────────────────────────────');

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . "/crm/v4/objects/deals/{$dealId}/associations/contacts");

            if ($response->successful()) {
                $data = $response->json();
                $results = $data['results'] ?? [];
                
                if (!empty($results)) {
                    $this->info("✅ Asociaciones v4 encontradas: " . count($results));
                    foreach (array_slice($results, 0, 3) as $assoc) {
                        $contactId = $assoc['toObjectId'] ?? $assoc['id'] ?? null;
                        $this->line("   • Contact ID: {$contactId}");
                    }
                } else {
                    $this->warn("⚠️  No hay asociaciones en API v4");
                }
            } else {
                $this->warn("⚠️  Error en API v4: " . $response->status());
            }
        } catch (\Exception $e) {
            $this->error("❌ Error verificando asociaciones v4: " . $e->getMessage());
        }

        $this->newLine();
    }

    private function strategy4_searchWithAssociations(string $dealId): void
    {
        $this->info("📦 ESTRATEGIA 4: Probando Search API con asociaciones");
        $this->line('─────────────────────────────────────────────────');

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/crm/v3/objects/deals/search', [
                'filterGroups' => [
                    [
                        'filters' => [
                            [
                                'propertyName' => 'hs_object_id',
                                'operator' => 'EQ',
                                'value' => $dealId
                            ]
                        ]
                    ]
                ],
                'properties' => [
                    'dealname',
                    'amount',
                    'estatus_de_convenio',
                    'hs_all_associated_contact_ids'
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $results = $data['results'] ?? [];
                
                if (!empty($results)) {
                    $result = $results[0];
                    $props = $result['properties'] ?? [];
                    
                    if (isset($props['hs_all_associated_contact_ids'])) {
                        $contactIds = $props['hs_all_associated_contact_ids'];
                        $this->info("✅ Campo hs_all_associated_contact_ids encontrado:");
                        $this->line("   → Valor: {$contactIds}");
                        
                        if (!empty($contactIds)) {
                            $ids = explode(';', $contactIds);
                            $this->info("   → IDs de contactos: " . implode(', ', $ids));
                        }
                    } else {
                        $this->warn("⚠️  Campo hs_all_associated_contact_ids no disponible");
                    }
                } else {
                    $this->warn("⚠️  No se encontró el Deal en Search API");
                }
            } else {
                $this->warn("⚠️  Error en Search API: " . $response->status());
            }
        } catch (\Exception $e) {
            $this->error("❌ Error en Search API: " . $e->getMessage());
        }

        $this->newLine();
    }

    private function strategy5_checkAssociatedContactIds(string $dealId): void
    {
        $this->info("📦 ESTRATEGIA 5: Verificando campo hs_all_associated_contact_ids");
        $this->line('─────────────────────────────────────────────────');

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . "/crm/v3/objects/deals/{$dealId}", [
                'properties' => 'hs_all_associated_contact_ids,dealname'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $props = $data['properties'] ?? [];
                
                if (isset($props['hs_all_associated_contact_ids']) && !empty($props['hs_all_associated_contact_ids'])) {
                    $contactIds = $props['hs_all_associated_contact_ids'];
                    $this->info("✅ Campo encontrado con valor:");
                    $this->line("   → {$contactIds}");
                    
                    // Intentar parsear IDs
                    $ids = explode(';', $contactIds);
                    if (count($ids) > 0) {
                        $this->info("   → Total de contactos asociados: " . count($ids));
                        $this->line("   → Primer Contact ID: {$ids[0]}");
                    }
                } else {
                    $this->warn("⚠️  Campo hs_all_associated_contact_ids vacío o no disponible");
                }
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }

        $this->newLine();
    }

    private function getContactData(string $contactId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . "/crm/v3/objects/contacts/{$contactId}", [
                'properties' => 'firstname,lastname,email,phone,xante_id'
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            // Silently fail
        }

        return null;
    }

    private function showRecommendations(): void
    {
        $this->info("📋 RESUMEN Y RECOMENDACIONES");
        $this->line('═══════════════════════════════════════════════════');
        $this->newLine();
        
        $this->line("Basado en el diagnóstico, aquí están tus opciones:");
        $this->newLine();
        
        $this->info("✅ OPCIÓN 1: Usar asociaciones directas (si se encontraron)");
        $this->line("   Implementación: Obtener Contact asociado via API v3/v4");
        $this->line("   Ventaja: Datos completos del contacto");
        $this->line("   Desventaja: Requiere que existan asociaciones");
        $this->newLine();
        
        $this->info("✅ OPCIÓN 2: Usar campo hs_all_associated_contact_ids");
        $this->line("   Implementación: Parsear IDs del campo y obtener contactos");
        $this->line("   Ventaja: Más confiable que asociaciones directas");
        $this->line("   Desventaja: Requiere request adicional por contacto");
        $this->newLine();
        
        $this->info("✅ OPCIÓN 3: Usar campos personalizados del Deal");
        $this->line("   Implementación: Extraer xante_id/email directamente del Deal");
        $this->line("   Ventaja: Un solo request, más rápido");
        $this->line("   Desventaja: Requiere que el Deal tenga esos campos");
        $this->newLine();
        
        $this->info("✅ OPCIÓN 4: Estrategia híbrida (RECOMENDADA)");
        $this->line("   1. Intentar obtener Contact asociado");
        $this->line("   2. Si falla, buscar en campos personalizados del Deal");
        $this->line("   3. Si falla, buscar Contact por email/xante_id");
        $this->line("   4. Si todo falla, omitir el Deal");
        $this->newLine();
        
        $this->comment("💡 Ejecuta este comando con diferentes Deal IDs para confirmar el patrón:");
        $this->line("   php artisan hubspot:diagnose-relations --deal-id=XXXXXXX");
    }
}
