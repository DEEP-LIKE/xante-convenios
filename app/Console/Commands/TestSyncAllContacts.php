<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestSyncAllContacts extends Command
{
    protected $signature = 'hubspot:test-sync-all';
    protected $description = 'Sincronizar TODOS los contactos de HubSpot (sin validar xante_id)';

    public function handle()
    {
        $this->info('🧪 Sincronizando TODOS los contactos (modo prueba)');
        
        $token = config('hubspot.token');
        $baseUrl = config('hubspot.api_base_url');
        
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
            ])->get($baseUrl . '/crm/v3/objects/contacts', [
                'limit' => 10,
                'properties' => 'firstname,lastname,email,phone,xante_id,hs_object_id'
            ]);

            if (!$response->successful()) {
                $this->error('Error al obtener contactos');
                return 1;
            }

            $data = $response->json();
            $contacts = $data['results'] ?? [];
            
            $this->info("📊 Total contactos encontrados: " . count($contacts));
            $this->newLine();

            $stats = ['nuevos' => 0, 'actualizados' => 0, 'omitidos' => 0];

            foreach ($contacts as $contact) {
                $hubspotId = $contact['id'];
                $props = $contact['properties'];
                
                // Mostrar información del contacto
                $name = trim(($props['firstname'] ?? '') . ' ' . ($props['lastname'] ?? ''));
                $xanteId = $props['xante_id'] ?? null;
                $email = $props['email'] ?? null;
                
                $this->line("👤 {$name}");
                $this->line("   HubSpot ID: {$hubspotId}");
                $this->line("   Xante ID: " . ($xanteId ?: '❌ No tiene'));
                $this->line("   Email: " . ($email ?: 'N/A'));
                
                // Verificar si existe
                $existing = Client::where('hubspot_id', $hubspotId)->first();
                
                if ($existing) {
                    $this->line("   ✅ Ya existe - ID: {$existing->id}");
                    $stats['actualizados']++;
                } else {
                    // Si no tiene xante_id, usar el hubspot_id como xante_id temporal
                    $finalXanteId = $xanteId ?: "HS-{$hubspotId}";
                    
                    try {
                        $client = Client::create([
                            'name' => $name ?: 'Sin Nombre',
                            'hubspot_id' => $hubspotId,
                            'xante_id' => $finalXanteId,
                            'email' => $email,
                            'phone' => $props['phone'] ?? null,
                            'hubspot_synced_at' => now(),
                        ]);
                        
                        $this->info("   ✨ NUEVO CLIENTE CREADO - ID: {$client->id}");
                        $stats['nuevos']++;
                        
                    } catch (\Exception $e) {
                        $this->error("   ❌ Error: " . $e->getMessage());
                        $stats['omitidos']++;
                    }
                }
                
                $this->newLine();
            }

            // Resumen
            $this->info('📊 RESUMEN:');
            $this->table(
                ['Métrica', 'Cantidad'],
                [
                    ['Nuevos', $stats['nuevos']],
                    ['Actualizados', $stats['actualizados']],
                    ['Omitidos', $stats['omitidos']],
                ]
            );

            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}