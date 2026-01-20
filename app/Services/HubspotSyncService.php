<?php

namespace App\Services;

use App\Actions\Hubspot\Http\FetchDealsFromHubspot;
use App\Actions\Hubspot\Sync\ProcessDealAction;
use App\Models\Agreement;
use App\Models\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HubspotSyncService
{
    private string $token;

    private string $baseUrl;

    private array $config;

    public function __construct(
        private FetchDealsFromHubspot $fetchDeals,
        private ProcessDealAction $processDeal,
    ) {
        $this->token = config('hubspot.token');
        $this->baseUrl = config('hubspot.api_base_url');
        $this->config = config('hubspot');

        if (! $this->token) {
            throw new \Exception('HubSpot token no configurado');
        }
    }

    /**
     * Sincronizar clientes desde HubSpot Deals
     *
     * Obtiene deals con estatus "Aceptado" desde HubSpot y sincroniza los clientes
     * asociados en la base de datos local. Soporta paginación y límites de tiempo.
     *
     * @param  int|null  $maxPages  Número máximo de páginas a procesar (null = sin límite)
     * @param  int|null  $timeLimit  Tiempo máximo de ejecución en segundos (null = sin límite)
     * @return array{
     *     total_deals: int,
     *     new_clients: int,
     *     updated_clients: int,
     *     skipped: int,
     *     errors: int,
     *     processed_pages: int,
     *     time_limited: bool,
     *     max_pages_reached: bool
     * } Estadísticas de la sincronización
     *
     * @throws \Exception Si el token de HubSpot no está configurado
     */
    public function syncClients(?int $maxPages = null, ?int $timeLimit = null): array
    {
        Log::info('Iniciando sincronización de clientes desde Deals HubSpot');

        $stats = [
            'total_deals' => 0,
            'new_clients' => 0,
            'updated_clients' => 0,
            'skipped' => 0,
            'errors' => 0,
            'processed_pages' => 0,
            'time_limited' => false,
            'max_pages_reached' => false,
        ];

        $startTime = time();

        try {
            $hasMore = true;
            $after = null;

            while ($hasMore) {
                // Verificar límite de tiempo (si está configurado)
                if ($timeLimit && (time() - $startTime) >= $timeLimit) {
                    $stats['time_limited'] = true;
                    Log::info('Sincronización detenida por límite de tiempo', [
                        'time_elapsed' => time() - $startTime,
                        'time_limit' => $timeLimit,
                    ]);
                    break;
                }

                // Verificar límite de páginas
                if ($maxPages && $stats['processed_pages'] >= $maxPages) {
                    $stats['max_pages_reached'] = true;
                    Log::info('Sincronización detenida por límite de páginas', [
                        'pages_processed' => $stats['processed_pages'],
                        'max_pages' => $maxPages,
                    ]);
                    break;
                }

                $response = $this->fetchDeals->execute($after);

                if (! $response['success']) {
                    Log::error('Error fetching deals from HubSpot', $response);
                    $stats['errors']++;
                    break;
                }

                $data = $response['data'];
                $deals = $data['results'] ?? [];
                $stats['total_deals'] += count($deals);
                $stats['processed_pages']++;

                foreach ($deals as $deal) {
                    $result = $this->processDeal->execute($deal);
                    $stats[$result]++;
                }

                // Paginación
                $hasMore = isset($data['paging']['next']);
                $after = $data['paging']['next']['after'] ?? null;

                // Rate limiting
                usleep(100000); // 100ms delay between requests
            }

        } catch (\Exception $e) {
            Log::error('Error en sincronización de HubSpot', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $stats['errors']++;
        }

        Log::info('Sincronización completada', $stats);

        return $stats;
    }

    /**
     * Sincronización rápida (solo primeras páginas)
     *
     * Ejecuta una sincronización limitada a 10 páginas y 30 segundos,
     * útil para actualizaciones frecuentes sin sobrecargar el sistema.
     *
     * @return array Estadísticas de la sincronización (ver syncClients)
     */
    public function syncClientsQuick(): array
    {
        return $this->syncClients(maxPages: 10, timeLimit: 30);
    }

    /**
     * Sincronización por lotes (fragmentos pequeños)
     *
     * Ejecuta una sincronización en lotes pequeños para procesamiento gradual.
     *
     * @param  int  $batchSize  Número de páginas por lote (default: 5)
     * @return array Estadísticas de la sincronización (ver syncClients)
     */
    public function syncClientsBatch(int $batchSize = 5): array
    {
        return $this->syncClients(maxPages: $batchSize, timeLimit: 40);
    }

    // ========================================
    // MÉTODOS DE SINCRONIZACIÓN A HUBSPOT
    // (No refactorizados - mantienen funcionalidad legacy)
    // ========================================

    /**
     * Sincronizar cliente local a HubSpot
     */
    public function syncClientToHubspot(Client $client, ?Agreement $agreement = null): array
    {
        try {
            // Verificar si el cliente ya tiene un Deal en HubSpot
            if ($client->hubspot_deal_id) {
                return $this->updateDealInHubspot($client, $agreement);
            }

            // Crear nuevo Deal en HubSpot
            return $this->createDealInHubspot($client, $agreement);

        } catch (\Exception $e) {
            Log::error('Error sincronizando cliente a HubSpot', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Crear Deal en HubSpot
     */
    private function createDealInHubspot(Client $client, ?Agreement $agreement = null): array
    {
        try {
            $dealData = $this->mapClientToHubspotDeal($client, $agreement);

            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->token}",
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl.'/crm/v3/objects/deals', [
                    'properties' => $dealData,
                ]);

            if ($response->successful()) {
                $dealId = $response->json()['id'] ?? null;
                $client->update(['hubspot_deal_id' => $dealId]);

                Log::info('Deal creado en HubSpot', [
                    'client_id' => $client->id,
                    'deal_id' => $dealId,
                ]);

                return [
                    'success' => true,
                    'deal_id' => $dealId,
                ];
            }

            return [
                'success' => false,
                'error' => "HTTP {$response->status()}: {$response->body()}",
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Actualizar Deal en HubSpot
     */
    private function updateDealInHubspot(Client $client, ?Agreement $agreement = null): array
    {
        try {
            $dealData = $this->mapClientToHubspotDeal($client, $agreement);

            $response = Http::timeout($this->config['sync']['timeout'])
                ->withHeaders([
                    'Authorization' => "Bearer {$this->token}",
                    'Content-Type' => 'application/json',
                ])
                ->patch($this->baseUrl."/crm/v3/objects/deals/{$client->hubspot_deal_id}", [
                    'properties' => $dealData,
                ]);

            if ($response->successful()) {
                Log::info('Deal actualizado en HubSpot', [
                    'client_id' => $client->id,
                    'deal_id' => $client->hubspot_deal_id,
                ]);

                return [
                    'success' => true,
                    'deal_id' => $client->hubspot_deal_id,
                ];
            }

            return [
                'success' => false,
                'error' => "HTTP {$response->status()}: {$response->body()}",
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Mapear datos de cliente a formato de Deal de HubSpot
     */
    private function mapClientToHubspotDeal(Client $client, ?Agreement $agreement = null): array
    {
        $dealData = [
            'dealname' => $client->name ?? 'Sin nombre',
            'xante_id' => $client->xante_id,
            'nombre_completo' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'curp' => $client->curp,
            'rfc' => $client->rfc,
            'estado_civil' => $client->civil_status,
            'ocupacion' => $client->occupation,
        ];

        // Agregar datos del convenio si existe
        if ($agreement) {
            $dealData = array_merge($dealData, [
                'valor_convenio' => $agreement->wizard_data['agreement_value'] ?? null,
                'comision_total_pagar' => $agreement->wizard_data['total_commission'] ?? null,
                'ganancia_final' => $agreement->wizard_data['final_profit'] ?? null,
            ]);
        }

        return $dealData;
    }

    /**
     * Mapear datos de cliente a formato de Contact de HubSpot
     */
    private function mapClientToHubspotContact(Client $client): array
    {
        return [
            'firstname' => $client->name ? explode(' ', $client->name)[0] : '',
            'lastname' => $client->name ? implode(' ', array_slice(explode(' ', $client->name), 1)) : '',
            'email' => $client->email,
            'phone' => $client->phone,
            'xante_id' => $client->xante_id,
        ];
    }

    /**
     * Obtener detalles ligeros del Deal para visualización rápida
     */
    public function getDealDetails(string $dealId): ?array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->token}",
                ])
                ->get($this->baseUrl."/crm/v3/objects/deals/{$dealId}", [
                    'properties' => 'dealname,amount,estatus_de_convenio,dealstage,hs_lastmodifieddate',
                ]);

            if ($response->successful()) {
                return $response->json()['properties'];
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Error obteniendo detalles del Deal {$dealId}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Probar conexión con HubSpot
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->token}",
                ])
                ->get($this->baseUrl.'/crm/v3/objects/contacts', [
                    'limit' => 1,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Conexión exitosa con HubSpot',
                ];
            }

            return [
                'success' => false,
                'message' => "HTTP {$response->status()}: {$response->body()}",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Obtener estadísticas de sincronización
     *
     * @return array{
     *     total_clients: int,
     *     clients_with_hubspot_id: int,
     *     clients_without_hubspot_id: int,
     *     last_sync: ?\Carbon\Carbon
     * }
     */
    public function getSyncStats(): array
    {
        $totalClients = Client::count();
        $withHubspotId = Client::whereNotNull('hubspot_id')->count();
        $lastSync = Client::whereNotNull('hubspot_synced_at')
            ->orderBy('hubspot_synced_at', 'desc')
            ->value('hubspot_synced_at');

        return [
            'total_clients' => $totalClients,
            'clients_with_hubspot_id' => $withHubspotId,
            'clients_without_hubspot_id' => $totalClients - $withHubspotId,
            'last_sync' => $lastSync ? Carbon::parse($lastSync) : null,
        ];
    }
}
