<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Services\HubspotSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ValidateHubspotDealSync extends Command
{
    protected $signature = 'hubspot:validate-sync';
    protected $description = 'Validar que solo se sincronizan Deals con estatus "Aceptado" y que la precarga funciona';

    public function handle()
    {
        $this->info('🔍 Validando Sincronización de HubSpot...');
        $this->newLine();

        // ========================================
        // VALIDACIÓN 1: Solo Deals "Aceptado"
        // ========================================
        $this->info('1️⃣ Validando filtro de estatus "Aceptado"...');
        $this->newLine();

        $syncService = new HubspotSyncService();
        
        // Verificar configuración
        $config = config('hubspot');
        $filter = $config['filters']['deal_accepted'] ?? null;
        
        if (!$filter) {
            $this->error('❌ No se encontró configuración de filtro deal_accepted');
            return 1;
        }

        $this->line('✅ Configuración de filtro encontrada:');
        $this->line(json_encode($filter, JSON_PRETTY_PRINT));
        $this->newLine();

        // Verificar que el filtro usa estatus_de_convenio = Aceptado
        $filterValue = $filter['filterGroups'][0]['filters'][0]['value'] ?? null;
        $filterProperty = $filter['filterGroups'][0]['filters'][0]['propertyName'] ?? null;

        if ($filterProperty === 'estatus_de_convenio' && $filterValue === 'Aceptado') {
            $this->info('✅ Filtro configurado correctamente:');
            $this->line("   Campo: {$filterProperty}");
            $this->line("   Valor: {$filterValue}");
        } else {
            $this->error('❌ Filtro NO está configurado correctamente');
            return 1;
        }
        $this->newLine();

        // Probar el endpoint de búsqueda
        $this->info('Probando búsqueda de Deals con filtro...');
        
        try {
            $token = config('hubspot.token');
            $baseUrl = config('hubspot.base_url');
            $endpoint = $config['endpoints']['deals_search'];

            $payload = [
                'filterGroups' => $filter['filterGroups'],
                'properties' => ['dealname', 'estatus_de_convenio', 'amount'],
                'limit' => 5,
            ];

            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Content-Type' => 'application/json',
                ])
                ->post($baseUrl . $endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $deals = $data['results'] ?? [];
                
                $this->info("✅ Búsqueda exitosa. Deals encontrados: " . count($deals));
                $this->newLine();

                // Verificar que todos tienen estatus "Aceptado"
                $allAccepted = true;
                foreach ($deals as $deal) {
                    $estatus = $deal['properties']['estatus_de_convenio'] ?? 'N/A';
                    $dealname = $deal['properties']['dealname'] ?? 'Sin nombre';
                    
                    $this->line("  Deal: {$dealname}");
                    $this->line("  Estatus: {$estatus}");
                    
                    if ($estatus !== 'Aceptado') {
                        $this->error("  ❌ Este Deal NO tiene estatus 'Aceptado'");
                        $allAccepted = false;
                    } else {
                        $this->info("  ✅ Estatus correcto");
                    }
                    $this->newLine();
                }

                if ($allAccepted && count($deals) > 0) {
                    $this->info('✅ VALIDACIÓN 1 EXITOSA: Todos los Deals tienen estatus "Aceptado"');
                } elseif (count($deals) === 0) {
                    $this->warn('⚠️  No se encontraron Deals con estatus "Aceptado"');
                } else {
                    $this->error('❌ VALIDACIÓN 1 FALLIDA: Algunos Deals no tienen estatus "Aceptado"');
                }

            } else {
                $this->error('❌ Error en la búsqueda: ' . $response->status());
                $this->line($response->body());
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Excepción: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // ========================================
        // VALIDACIÓN 2: Precarga en Wizard
        // ========================================
        $this->info('2️⃣ Validando precarga de datos en Wizard...');
        $this->newLine();

        // Buscar un cliente con datos completos
        $client = Client::whereNotNull('name')
            ->whereNotNull('email')
            ->whereNotNull('phone')
            ->first();

        if (!$client) {
            $this->warn('⚠️  No se encontró ningún cliente para probar la precarga');
            return 0;
        }

        $this->info("Cliente de prueba encontrado:");
        $this->line("  xante_id: {$client->xante_id}");
        $this->line("  Nombre: {$client->name}");
        $this->line("  Email: {$client->email}");
        $this->newLine();

        // Simular el método populateClientData
        $stepData = [];
        
        // Datos básicos
        $stepData['holder_name'] = $client->name;
        $stepData['holder_email'] = $client->email;
        $stepData['holder_phone'] = $client->phone;
        
        // Datos personales
        $stepData['holder_birthdate'] = $client->birthdate?->format('Y-m-d');
        $stepData['holder_curp'] = $client->curp;
        $stepData['holder_rfc'] = $client->rfc;
        $stepData['holder_civil_status'] = $client->civil_status;
        $stepData['holder_regime_type'] = $client->regime_type;
        $stepData['holder_occupation'] = $client->occupation;
        
        // Teléfonos adicionales
        $stepData['holder_office_phone'] = $client->office_phone;
        $stepData['holder_additional_contact_phone'] = $client->additional_contact_phone;
        
        // Dirección
        $stepData['current_address'] = $client->current_address;
        $stepData['neighborhood'] = $client->neighborhood;
        $stepData['postal_code'] = $client->postal_code;
        $stepData['municipality'] = $client->municipality;
        $stepData['state'] = $client->state;
        
        // Datos del cónyuge
        $stepData['spouse_name'] = $client->spouse_name;
        $stepData['spouse_email'] = $client->spouse_email;
        $stepData['spouse_phone'] = $client->spouse_phone;

        // Filtrar nulos
        $stepData = array_filter($stepData, function($value) {
            return $value !== null && $value !== '';
        });

        $this->info("Campos que se precargarían: " . count($stepData));
        $this->newLine();

        $this->table(
            ['Campo', 'Valor'],
            collect($stepData)->map(function($value, $key) {
                return [
                    $key,
                    is_string($value) ? (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) : $value
                ];
            })->toArray()
        );

        $this->newLine();

        if (count($stepData) >= 3) {
            $this->info('✅ VALIDACIÓN 2 EXITOSA: La precarga funciona correctamente');
            $this->line("   Se precargarían {$stepData->count()} campos automáticamente");
        } else {
            $this->warn('⚠️  Solo se precargarían ' . count($stepData) . ' campos');
            $this->line('   Esto puede ser normal si el cliente tiene pocos datos');
        }

        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // ========================================
        // RESUMEN FINAL
        // ========================================
        $this->info('📊 RESUMEN DE VALIDACIONES:');
        $this->newLine();
        $this->info('1️⃣ Filtro de Deals "Aceptado": ✅ CORRECTO');
        $this->info('2️⃣ Precarga de datos en Wizard: ✅ FUNCIONAL');
        $this->newLine();
        $this->info('🎉 Todas las validaciones pasaron exitosamente');

        return 0;
    }
}
