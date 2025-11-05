<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class HubspotSyncService
{
    private string $token;
    private string $baseUrl;
    private array $config;

    public function __construct()
    {
        $this->token = config('hubspot.token');
        $this->baseUrl = config('hubspot.api_base_url');
        $this->config = config('hubspot');
        
        if (!$this->token) {
            throw new \Exception('HubSpot token no configurado');
        }
    }

    /**
     * Sincronizar clientes desde HubSpot
     */
    public function syncClients(int $maxPages = null, int $timeLimit = 45): array
    {
        Log::info('Iniciando sincronización de clientes desde HubSpot');
        
        $stats = [
            'total_hubspot' => 0,
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
                // Verificar límite de tiempo
                if ((time() - $startTime) >= $timeLimit) {
                    $stats['time_limited'] = true;
                    Log::info('Sincronización detenida por límite de tiempo', [
                        'time_elapsed' => time() - $startTime,
                        'time_limit' => $timeLimit
                    ]);
                    break;
                }

                // Verificar límite de páginas
                if ($maxPages && $stats['processed_pages'] >= $maxPages) {
                    $stats['max_pages_reached'] = true;
                    Log::info('Sincronización detenida por límite de páginas', [
                        'pages_processed' => $stats['processed_pages'],
                        'max_pages' => $maxPages
                    ]);
                    break;
                }

                $response = $this->fetchContacts($after);
                
                if (!$response['success']) {
                    Log::error('Error fetching contacts from HubSpot', $response);
                    $stats['errors']++;
                    break;
                }
                
                $data = $response['data'];
                $contacts = $data['results'] ?? [];
                $stats['total_hubspot'] += count($contacts);
                $stats['processed_pages']++;
                
                foreach ($contacts as $contact) {
                    $result = $this->processContact($contact);
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
                'trace' => $e->getTraceAsString()
            ]);
            $stats['errors']++;
        }

        Log::info('Sincronización completada', $stats);
        return $stats;
    }

    /**
     * Sincronización rápida (solo primeras páginas)
     */
    public function syncClientsQuick(): array
    {
        return $this->syncClients(maxPages: 10, timeLimit: 30);
    }

    /**
     * Sincronización por lotes (fragmentos pequeños)
     */
    public function syncClientsBatch(int $batchSize = 5): array
    {
        return $this->syncClients(maxPages: $batchSize, timeLimit: 40);
    }

    /**
     * Obtener contactos desde HubSpot
     */
    private function fetchContacts(?string $after = null): array
    {
        try {
            $params = [
                'limit' => $this->config['sync']['batch_size'],
                'properties' => $this->getContactProperties(),
            ];
            
            if ($after) {
                $params['after'] = $after;
            }

            $response = Http::timeout($this->config['sync']['timeout'])
                ->withHeaders([
                    'Authorization' => "Bearer {$this->token}",
                    'Content-Type' => 'application/json',
                ])
                ->get($this->baseUrl . $this->config['endpoints']['contacts'], $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => "HTTP {$response->status()}: {$response->body()}"
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Procesar un contacto individual
     */
    private function processContact(array $contact): string
    {
        try {
            $hubspotId = $contact['id'] ?? null;
            $properties = $contact['properties'] ?? [];
            
            if (!$hubspotId) {
                Log::warning('Contacto sin ID de HubSpot', $contact);
                return 'skipped';
            }

            // VALIDACIÓN CRÍTICA: Buscar xante_id en propiedades personalizadas
            $xanteId = $this->extractXanteId($properties);
            
            // REGLA DE ORO: Sin xante_id válido = NO IMPORTAR
            if (!$xanteId || empty(trim($xanteId))) {
                Log::info("Contacto {$hubspotId} sin xante_id válido - OMITIDO", [
                    'hubspot_id' => $hubspotId,
                    'email' => $properties['email'] ?? null,
                    'firstname' => $properties['firstname'] ?? null
                ]);
                return 'skipped';
            }

            // Verificar si ya existe el cliente (prioridad: hubspot_id, luego xante_id)
            $existingClient = Client::where('hubspot_id', $hubspotId)
                ->orWhere('xante_id', $xanteId)
                ->first();
            
            if ($existingClient) {
                // Actualizar cliente existente
                $this->updateExistingClient($existingClient, $properties, $xanteId);
                return 'updated_clients';
            } else {
                // Crear nuevo cliente (solo si tiene xante_id válido)
                $this->createNewClient($hubspotId, $properties, $xanteId);
                return 'new_clients';
            }

        } catch (\Exception $e) {
            Log::error('Error procesando contacto', [
                'contact' => $contact,
                'error' => $e->getMessage()
            ]);
            return 'errors';
        }
    }

    /**
     * Extraer xante_id de las propiedades (VALIDACIÓN CRÍTICA)
     */
    private function extractXanteId(array $properties): ?string
    {
        $possibleFields = $this->config['mapping']['custom_properties'];
        
        foreach ($possibleFields as $field) {
            if (isset($properties[$field]) && !empty(trim($properties[$field]))) {
                $xanteId = trim($properties[$field]);
                
                // Validación adicional: debe ser numérico y mayor a 0
                if (is_numeric($xanteId) && (int)$xanteId > 0) {
                    return $xanteId;
                }
                
                Log::warning("xante_id inválido encontrado", [
                    'field' => $field,
                    'value' => $xanteId,
                    'hubspot_properties' => array_keys($properties)
                ]);
            }
        }
        
        return null;
    }

    /**
     * Crear nuevo cliente
     */
    private function createNewClient(string $hubspotId, array $properties, string $xanteId): void
    {
        $clientData = $this->mapHubspotToClient($properties);
        $clientData['hubspot_id'] = $hubspotId;
        $clientData['xante_id'] = $xanteId;
        $clientData['hubspot_synced_at'] = now();

        $client = Client::create($clientData);
        
        Log::info('Nuevo cliente creado desde HubSpot', [
            'client_id' => $client->id,
            'xante_id' => $xanteId,
            'hubspot_id' => $hubspotId,
            'email' => $clientData['email'] ?? null
        ]);
    }

    /**
     * Actualizar cliente existente
     */
    private function updateExistingClient(Client $client, array $properties, string $xanteId): void
    {
        $clientData = $this->mapHubspotToClient($properties);
        
        // ASEGURAR IDs CRÍTICOS: xante_id y hubspot_id siempre correctos
        $clientData['xante_id'] = $xanteId;
        $clientData['hubspot_synced_at'] = now();
        
        // Si el cliente no tenía hubspot_id, asignarlo ahora
        if (empty($client->hubspot_id)) {
            $hubspotId = $properties['hs_object_id'] ?? null;
            if ($hubspotId) {
                $clientData['hubspot_id'] = $hubspotId;
                Log::info('Asignando hubspot_id faltante', [
                    'client_id' => $client->id,
                    'xante_id' => $xanteId,
                    'hubspot_id' => $hubspotId
                ]);
            }
        }

        $client->update($clientData);
        
        Log::info('Cliente actualizado desde HubSpot', [
            'client_id' => $client->id,
            'xante_id' => $xanteId,
            'hubspot_id' => $client->hubspot_id,
            'updated_fields' => array_keys($clientData)
        ]);
    }

    /**
     * Mapear datos de HubSpot a estructura de Cliente
     */
    private function mapHubspotToClient(array $properties): array
    {
        $mapping = $this->config['mapping']['contact_fields'];
        $clientData = [];

        foreach ($mapping as $hubspotField => $clientField) {
            if (isset($properties[$hubspotField]) && !empty($properties[$hubspotField])) {
                $value = $properties[$hubspotField];
                
                // Procesar fechas de HubSpot (vienen en milisegundos)
                if (in_array($clientField, ['fecha_registro', 'updated_at']) && is_numeric($value)) {
                    $value = Carbon::createFromTimestampMs($value);
                }
                
                $clientData[$clientField] = $value;
            }
        }

        // Mapeos específicos
        if (isset($properties['firstname']) && isset($properties['lastname'])) {
            $clientData['name'] = trim(($properties['firstname'] ?? '') . ' ' . ($properties['lastname'] ?? ''));
        } elseif (isset($properties['firstname'])) {
            $clientData['name'] = $properties['firstname'];
        }

        return $clientData;
    }

    /**
     * Obtener propiedades de contacto a solicitar
     */
    private function getContactProperties(): string
    {
        $standardProperties = array_keys($this->config['mapping']['contact_fields']);
        $customProperties = $this->config['mapping']['custom_properties'];
        
        $allProperties = array_merge($standardProperties, $customProperties, [
            'firstname', 'lastname', 'hs_object_id'
        ]);

        return implode(',', array_unique($allProperties));
    }

    /**
     * Obtener estadísticas de sincronización
     */
    public function getSyncStats(): array
    {
        return [
            'total_clients' => Client::count(),
            'clients_with_hubspot_id' => Client::whereNotNull('hubspot_id')->count(),
            'clients_without_hubspot_id' => Client::whereNull('hubspot_id')->count(),
            'last_sync' => Client::whereNotNull('hubspot_synced_at')
                ->orderBy('hubspot_synced_at', 'desc')
                ->first()?->hubspot_synced_at,
        ];
    }

    /**
     * Verificar conectividad con HubSpot
     */
    public function testConnection(): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->token}",
                    'Content-Type' => 'application/json',
                ])
                ->get($this->baseUrl . $this->config['endpoints']['contacts'], [
                    'limit' => 1
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Conexión exitosa con HubSpot',
                    'status_code' => $response->status()
                ];
            }

            return [
                'success' => false,
                'message' => 'Error de conexión con HubSpot',
                'status_code' => $response->status(),
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Excepción al conectar con HubSpot',
                'error' => $e->getMessage()
            ];
        }
    }
}
