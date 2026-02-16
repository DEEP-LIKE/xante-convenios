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
    // ========================================

    /**
     * Empuja los datos del cliente y su convenio a HubSpot (Contact y Deal)
     * Este es el método principal usado por el Wizard.
     */
    public function pushClientToHubspot(Client $client, ?Agreement $agreement = null, array $dirtyFields = []): array
    {
        $result = [
            'deal_updated' => false,
            'contact_updated' => false,
            'errors' => [],
        ];

        try {
            // 1. Actualizar Contacto
            if ($client->hubspot_id) {
                // Combinar cambios de cliente y cónyuge para el contacto si es necesario
                // En este sistema, la mayoría de los cambios de contacto se manejan vía Deal o Contact
                $contactUpdate = $this->updateContactInHubspot($client, $dirtyFields['client'] ?? []);
                if ($contactUpdate['success']) {
                    $result['contact_updated'] = true;
                } else {
                    $result['errors'][] = "Error Contacto: " . $contactUpdate['error'];
                }
            }

            // 2. Actualizar Deal
            if ($client->hubspot_deal_id) {
                $dealUpdate = $this->updateDealInHubspot($client, $agreement, $dirtyFields);
                if ($dealUpdate['success']) {
                    $result['deal_updated'] = true;
                } else {
                    $result['errors'][] = "Error Deal: " . $dealUpdate['error'];
                }
            }

            // 3. Si no hay Deal ID pero sí Client, podríamos intentar crear el Deal
            // (Opcional, según flujo actual del wizard)

        } catch (\Exception $e) {
            Log::error('Excepción en pushClientToHubspot', [
                'client_id' => $client->id,
                'error' => $e->getMessage()
            ]);
            $result['errors'][] = "Excepción: " . $e->getMessage();
        }

        return $result;
    }

    /**
     * Sincronizar cliente local a HubSpot (Legacy/Simple)
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
     * Actualiza un Deal en HubSpot con propiedades específicas
     */
    public function updateHubspotDeal(string $dealId, array $properties): array
    {
        try {
            $response = Http::timeout($this->config['sync']['timeout'] ?? 30)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->token}",
                    'Content-Type' => 'application/json',
                ])
                ->patch($this->baseUrl."/crm/v3/objects/deals/{$dealId}", [
                    'properties' => $properties,
                ]);

            if ($response->successful()) {
                Log::info('Deal actualizado en HubSpot (genérico)', [
                    'deal_id' => $dealId,
                    'properties' => array_keys($properties),
                ]);

                return ['success' => true];
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
     * Actualizar Contacto en HubSpot
     */
    private function updateContactInHubspot(Client $client, array $dirtyFields = []): array
    {
        try {
            $contactData = $this->mapClientToHubspotContact($client, $dirtyFields);

            if (empty($contactData)) {
                return ['success' => true]; // Nada que actualizar
            }

            $response = Http::timeout($this->config['sync']['timeout'] ?? 30)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->token}",
                    'Content-Type' => 'application/json',
                ])
                ->patch($this->baseUrl."/crm/v3/objects/contacts/{$client->hubspot_id}", [
                    'properties' => $contactData,
                ]);

            if ($response->successful()) {
                Log::info('Contacto actualizado en HubSpot', [
                    'client_id' => $client->id,
                    'contact_id' => $client->hubspot_id,
                ]);

                return ['success' => true];
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
    private function updateDealInHubspot(Client $client, ?Agreement $agreement = null, array $dirtyFields = []): array
    {
        $dealData = $this->mapClientToHubspotDeal($client, $agreement, $dirtyFields);

        if (empty($dealData)) {
            return ['success' => true]; // Nada que actualizar
        }

        return $this->updateHubspotDeal($client->hubspot_deal_id, $dealData);
    }

    /**
     * Mapear datos de cliente a formato de Deal de HubSpot
     */
    private function mapClientToHubspotDeal(Client $client, ?Agreement $agreement = null, array $dirtyFields = []): array
    {
        $isDirtySync = ! empty($dirtyFields);
        $dirtyClient = $dirtyFields['client'] ?? [];
        $dirtySpouse = $dirtyFields['spouse'] ?? [];

        $allData = [
            'dealname' => $client->name ?? 'Sin nombre',
            'xante_id' => $client->xante_id,
            'nombre_completo' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'curp' => $client->curp,
            'rfc' => $client->rfc,
            'estado_civil' => $client->civil_status,
            'ocupacion' => $client->occupation,
            // Domicilio titular
            'domicilio_actual' => $client->current_address,
            'colonia' => $client->neighborhood,
            'codigo_postal' => $client->postal_code,
            'municipio' => $client->municipality,
            'estado' => $client->state,
        ];

        // Cónyuge si existe
        if ($client->spouse) {
            $spouse = $client->spouse;
            $allData = array_merge($allData, [
                'nombre_completo_conyuge' => $spouse->name,
                'email_conyuge' => $spouse->email,
                'telefono_movil_conyuge' => $spouse->phone,
                'curp_conyuge' => $spouse->curp,
                'domicilio_actual_conyuge' => $spouse->current_address,
                'colonia_conyuge' => $spouse->neighborhood,
                'codigo_postal_conyuge' => $spouse->postal_code,
                'municipio_conyuge' => $spouse->municipality,
                'estado_conyuge' => $spouse->state,
            ]);
        }

        // Agregar datos del convenio si existe o si el cliente los tiene (por si el wizard los guarda directamente en Agreement)
        if ($agreement) {
            $allData = array_merge($allData, [
                // Datos de la propiedad
                'domicilio_convenio' => $agreement->domicilio_convenio ?? $agreement->wizard_data['domicilio_convenio'] ?? null,
                'comunidad' => $agreement->comunidad ?? $agreement->wizard_data['comunidad'] ?? null,
                'tipo_vivienda' => $agreement->tipo_vivienda ?? $agreement->wizard_data['tipo_vivienda'] ?? null,
                'prototipo' => $agreement->prototipo ?? $agreement->wizard_data['prototipo'] ?? null,
                'lote' => $agreement->wizard_data['lote'] ?? null,
                'manzana' => $agreement->wizard_data['manzana'] ?? null,
                'etapa' => $agreement->wizard_data['etapa'] ?? null,
                'municipio_propiedad' => $agreement->municipio_propiedad ?? $agreement->wizard_data['municipio_propiedad'] ?? null,
                'estado_propiedad' => $agreement->estado_propiedad ?? $agreement->wizard_data['estado_propiedad'] ?? null,

                // Datos financieros
                'valor_convenio' => $agreement->valor_convenio ?? $agreement->wizard_data['valor_convenio'] ?? null,
                'precio_promocion' => $agreement->precio_promocion ?? $agreement->wizard_data['precio_promocion'] ?? null,
                'comision_total_pagar' => $agreement->comision_total_pagar ?? $agreement->wizard_data['comision_total_pagar'] ?? null,
                'ganancia_final' => $agreement->ganancia_final ?? $agreement->wizard_data['ganancia_final'] ?? null,
            ]);
        }

        // Si es Dirty Sync, filtrar solo los campos que cambiaron
        if ($isDirtySync) {
            $filteredData = [];
            // Mapeo inverso para saber qué campo de HubSpot corresponde a qué campo local sucio
            $mapping = [
                'name' => ['dealname', 'nombre_completo'],
                'email' => ['email'],
                'phone' => ['phone'],
                'curp' => ['curp'],
                'rfc' => ['rfc'],
                'civil_status' => ['estado_civil'],
                'occupation' => ['ocupacion'],
                'current_address' => ['domicilio_actual'],
                'neighborhood' => ['colonia'],
                'postal_code' => ['codigo_postal'],
                'municipality' => ['municipio'],
                'state' => ['estado'],
                // Propiedad
                'domicilio_convenio' => ['domicilio_convenio'],
                'comunidad' => ['comunidad'],
                'tipo_vivienda' => ['tipo_vivienda'],
                'prototipo' => ['prototipo'],
                'lote' => ['lote'],
                'manzana' => ['manzana'],
                'etapa' => ['etapa'],
                'municipio_propiedad' => ['municipio_propiedad'],
                'estado_propiedad' => ['estado_propiedad'],
            ];

            foreach ($mapping as $localField => $hubspotFields) {
                if (array_key_exists($localField, $dirtyClient)) {
                    foreach ($hubspotFields as $hsField) {
                        if (isset($allData[$hsField])) {
                            $filteredData[$hsField] = $allData[$hsField];
                        }
                    }
                }
            }

            // Mapeo Cónyuge
            if (! empty($dirtySpouse)) {
                $spouseMapping = [
                    'name' => ['nombre_completo_conyuge'],
                    'email' => ['email_conyuge'],
                    'phone' => ['telefono_movil_conyuge'],
                    'curp' => ['curp_conyuge'],
                    'current_address' => ['domicilio_actual_conyuge'],
                    'neighborhood' => ['colonia_conyuge'],
                    'codigo_postal' => ['codigo_postal_conyuge'],
                    'municipality' => ['municipio_conyuge'],
                    'state' => ['estado_conyuge'],
                ];

                foreach ($spouseMapping as $localField => $hubspotFields) {
                    if (array_key_exists($localField, $dirtySpouse)) {
                        foreach ($hubspotFields as $hsField) {
                            if (isset($allData[$hsField])) {
                                $filteredData[$hsField] = $allData[$hsField];
                            }
                        }
                    }
                }
            }

            return $filteredData;
        }

        return $allData;
    }

    /**
     * Mapear datos de cliente a formato de Contact de HubSpot
     */
    private function mapClientToHubspotContact(Client $client, array $dirtyClient = []): array
    {
        $isDirtySync = ! empty($dirtyClient);
        
        $allData = [
            'firstname' => $client->name ? explode(' ', $client->name)[0] : '',
            'lastname' => $client->name ? implode(' ', array_slice(explode(' ', $client->name), 1)) : ($client->name ?: ''),
            'email' => $client->email,
            'phone' => $client->phone,
            'xante_id' => $client->xante_id,
            'address' => $client->current_address,
            'city' => $client->municipality,
            'state' => $client->state,
            'zip' => $client->postal_code,
            'colonia' => $client->neighborhood,
            'date_of_birth' => $client->birthdate ? $client->birthdate->format('Y-m-d') : null,
            'jobtitle' => $client->occupation,
        ];

        if ($isDirtySync) {
            $filteredData = [];
            $mapping = config('hubspot.mapping.contact_fields');
            
            foreach ($mapping as $hubspotField => $localField) {
                // Caso especial para name/firstname/lastname
                if ($localField === 'name' && array_key_exists('name', $dirtyClient)) {
                    $filteredData['firstname'] = $allData['firstname'];
                    $filteredData['lastname'] = $allData['lastname'];
                } elseif (array_key_exists($localField, $dirtyClient)) {
                    if (isset($allData[$hubspotField])) {
                        $filteredData[$hubspotField] = $allData[$hubspotField];
                    }
                }
            }
            
            return $filteredData;
        }

        return $allData;
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
