<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Agreement;
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
     * Sincronizar clientes desde HubSpot Deals
     */
    public function syncClients(int $maxPages = null, ?int $timeLimit = null): array
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

                $response = $this->fetchDeals($after);
                
                if (!$response['success']) {
                    Log::error('Error fetching deals from HubSpot', $response);
                    $stats['errors']++;
                    break;
                }
                
                $data = $response['data'];
                $deals = $data['results'] ?? [];
                $stats['total_deals'] += count($deals);
                $stats['processed_pages']++;
                
                foreach ($deals as $deal) {
                    $result = $this->processDeal($deal);
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
     * Obtener Deals con estatus "Aceptado" desde HubSpot
     */
    private function fetchDeals(?string $after = null): array
    {
        try {
            $payload = [
                'filterGroups' => $this->config['filters']['deal_accepted']['filterGroups'],
                'properties' => $this->config['deal_sync']['properties'],
                'limit' => $this->config['sync']['batch_size'],
                'sorts' => [
                    [
                        'propertyName' => 'createdate',
                        'direction' => 'DESCENDING'
                    ]
                ],
            ];
            
            if ($after) {
                $payload['after'] = $after;
            }

            $response = Http::timeout($this->config['sync']['timeout'])
                ->withHeaders([
                    'Authorization' => "Bearer {$this->token}",
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . $this->config['endpoints']['deals_search'], $payload);

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
     * Obtener contactos desde HubSpot (método legacy para compatibilidad)
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
     * Procesar un Deal individual
     */
    private function processDeal(array $deal): string
    {
        try {
            $dealId = $deal['id'];
            $properties = $deal['properties'] ?? [];
            
            // Validar estatus
            $estatus = $properties['estatus_de_convenio'] ?? null;
            if ($estatus !== 'Aceptado') {
                return 'skipped';
            }

            // Verificar si tiene contactos asociados
            $numContacts = (int)($properties['num_associated_contacts'] ?? 0);
            if ($numContacts === 0) {
                Log::info("Deal {$dealId} sin contactos asociados - OMITIDO", [
                    'dealname' => $properties['dealname'] ?? 'N/A',
                    'estatus' => $estatus
                ]);
                return 'skipped';
            }

            // Obtener fecha de creación del Deal
            $dealCreatedAt = $properties['createdate'] ?? null;

            // Obtener Contact asociado
            $contact = $this->getContactFromDeal($dealId);
            if (!$contact) {
                return 'skipped';
            }

            $contactProps = $contact['properties'] ?? [];
            $contactId = $contact['id'] ?? null;
            
            // Extraer xante_id del Contact
            $xanteId = $this->extractXanteId($contactProps);
            if (!$xanteId) {
                Log::info("Contact del Deal {$dealId} sin xante_id válido - OMITIDO", [
                    'contact_id' => $contactId,
                    'email' => $contactProps['email'] ?? 'N/A',
                    'dealname' => $properties['dealname'] ?? 'N/A'
                ]);
                return 'skipped';
            }

            // Verificar si cliente existe
            $existingClient = Client::where('xante_id', $xanteId)
                ->orWhere('hubspot_id', $contactId)
                ->first();
            
            if ($existingClient) {
                $this->updateExistingClient($existingClient, $contactProps, $properties, $xanteId, $dealId, $dealCreatedAt);
                Log::info("Cliente actualizado desde Deal {$dealId}", [
                    'xante_id' => $xanteId,
                    'client_id' => $existingClient->id
                ]);
                return 'updated_clients';
            } else {
                $this->createNewClient($contactId, $contactProps, $properties, $xanteId, $dealId, $dealCreatedAt);
                Log::info("Cliente creado desde Deal {$dealId}", [
                    'xante_id' => $xanteId,
                    'dealname' => $properties['dealname'] ?? 'N/A'
                ]);
                return 'new_clients';
            }

        } catch (\Exception $e) {
            Log::error('Error procesando Deal', [
                'deal' => $deal,
                'error' => $e->getMessage()
            ]);
            return 'errors';
        }
    }

    /**
     * Obtener Contact asociado al Deal
     */
    private function getContactFromDeal(string $dealId): ?array
    {
        try {
            // 1. Obtener asociaciones
            $response = Http::timeout($this->config['sync']['timeout'])
                ->withHeaders([
                    'Authorization' => "Bearer {$this->token}",
                ])
                ->get($this->baseUrl . "/crm/v3/objects/deals/{$dealId}/associations/contacts");

            if (!$response->successful()) {
                Log::warning("No se pudieron obtener asociaciones del Deal {$dealId}");
                return null;
            }

            $associations = $response->json()['results'] ?? [];
            
            if (empty($associations)) {
                Log::info("Deal {$dealId} sin Contact asociado en API");
                return null;
            }

            // 2. Obtener ID del primer Contact asociado
            $contactId = $associations[0]['id'] ?? $associations[0]['toObjectId'] ?? null;
            
            if (!$contactId) {
                Log::warning("Deal {$dealId} tiene asociación pero sin Contact ID válido");
                return null;
            }

            // 3. Obtener datos del Contact
            $contactResponse = Http::timeout($this->config['sync']['timeout'])
                ->withHeaders([
                    'Authorization' => "Bearer {$this->token}",
                ])
                ->get($this->baseUrl . "/crm/v3/objects/contacts/{$contactId}", [
                    'properties' => implode(',', array_merge(
                        ['firstname', 'lastname', 'email', 'phone'],
                        $this->config['mapping']['custom_properties']
                    ))
                ]);

            if ($contactResponse->successful()) {
                return $contactResponse->json();
            }

            Log::error("Error obteniendo Contact {$contactId} del Deal {$dealId}");
            return null;

        } catch (\Exception $e) {
            Log::error("Excepción obteniendo Contact del Deal {$dealId}", [
                'error' => $e->getMessage()
            ]);
            return null;
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
    private function createNewClient(string $hubspotId, array $contactProps, array $dealProps, string $xanteId, string $dealId, ?string $dealCreatedAt = null): void
    {
        // 1. Mapear datos del Contact
        $clientData = $this->mapHubspotToClient($contactProps);
        
        // 2. Agregar IDs
        $clientData['hubspot_id'] = $hubspotId;
        $clientData['hubspot_deal_id'] = $dealId;
        $clientData['xante_id'] = $xanteId;
        $clientData['hubspot_synced_at'] = now();

        // 3. Asignar fecha de registro desde el Deal si existe
        if ($dealCreatedAt) {
            try {
                if (is_numeric($dealCreatedAt)) {
                    $clientData['fecha_registro'] = Carbon::createFromTimestampMs($dealCreatedAt);
                } else {
                    $clientData['fecha_registro'] = Carbon::parse($dealCreatedAt);
                }
            } catch (\Exception $e) {
                Log::warning("Error parseando fecha de creación del Deal: {$dealCreatedAt}");
            }
        }

        // 4. Mapear datos adicionales del Deal
        $clientData = array_merge($clientData, $this->mapDealToClient($dealProps));

        $client = Client::create($clientData);
        
        // 5. Sincronizar datos del cónyuge si existen
        $this->syncSpouseData($client, $dealProps);
        
        Log::info('Cliente creado desde HubSpot', [
            'xante_id' => $xanteId,
            'hubspot_id' => $hubspotId,
            'deal_id' => $dealId,
            'mapped_fields' => array_keys($clientData)
        ]);

        // 6. Sincronizar datos del convenio (Propiedad y Financieros)
        // DESHABILITADO POR SOLICITUD DEL USUARIO: Solo sincronizar clientes
        // $this->syncAgreementData($client, $dealProps);
    }

    /**
     * Actualizar cliente existente
     */
    private function updateExistingClient(Client $client, array $contactProps, array $dealProps, string $xanteId, string $dealId, ?string $dealCreatedAt = null): void
    {
        // --- PROTECCIÓN CONTRA RACE CONDITIONS ---
        // Verificar fecha de modificación para evitar sobrescribir datos locales más recientes
        $hsLastModified = $contactProps['lastmodifieddate'] ?? null;
        
        if ($hsLastModified && $client->updated_at) {
            try {
                // HubSpot devuelve timestamp en milisegundos o ISO8601
                $hsDate = is_numeric($hsLastModified) 
                    ? Carbon::createFromTimestampMs($hsLastModified) 
                    : Carbon::parse($hsLastModified);

                // Si la modificación local es más reciente que la de HubSpot (con margen de 2 min por relojes)
                // ENTONCES: No sobrescribimos datos del cliente (nombre, email, etc.)
                if ($client->updated_at->gt($hsDate->addMinutes(2))) {
                    Log::info("Ignorando actualización de cliente {$client->id} desde HubSpot: Datos locales más recientes", [
                        'local_updated_at' => $client->updated_at->toIso8601String(),
                        'hs_lastmodifieddate' => $hsDate->toIso8601String()
                    ]);
                    
                    // AUN ASÍ, debemos asegurar que los IDs de conexión estén correctos
                    $updates = [];
                    if (!$client->hubspot_id) $updates['hubspot_id'] = $contactProps['hs_object_id'] ?? null;
                    if (!$client->hubspot_deal_id) $updates['hubspot_deal_id'] = $dealId;
                    
                    if (!empty($updates)) {
                        $client->update($updates);
                    }
                    
                    // Intentamos sincronizar el convenio (que tiene su propia lógica de protección)
                    // DESHABILITADO POR SOLICITUD DEL USUARIO
                    // $this->syncAgreementData($client, $dealProps);
                    
                    return; // SALIR TEMPRANO
                }
            } catch (\Exception $e) {
                Log::warning("Error comparando fechas de modificación: " . $e->getMessage());
            }
        }

        // 1. Mapear datos del Contact
        $clientData = $this->mapHubspotToClient($contactProps);
        
        // 2. ASEGURAR IDs CRÍTICOS: xante_id, hubspot_id y hubspot_deal_id siempre correctos
        $clientData['xante_id'] = $xanteId;
        $clientData['hubspot_deal_id'] = $dealId;
        $clientData['hubspot_synced_at'] = now();
        
        // 3. Actualizar fecha de registro si viene del Deal
        if ($dealCreatedAt) {
            try {
                if (is_numeric($dealCreatedAt)) {
                    $clientData['fecha_registro'] = Carbon::createFromTimestampMs($dealCreatedAt);
                } else {
                    $clientData['fecha_registro'] = Carbon::parse($dealCreatedAt);
                }
            } catch (\Exception $e) {
                Log::warning("Error parseando fecha de creación del Deal: {$dealCreatedAt}");
            }
        }
        
        // 4. Si el cliente no tenía hubspot_id, asignarlo ahora
        if (empty($client->hubspot_id)) {
            $hubspotId = $contactProps['hs_object_id'] ?? null;
            if ($hubspotId) {
                $clientData['hubspot_id'] = $hubspotId;
                Log::info('Asignando hubspot_id faltante', [
                    'client_id' => $client->id,
                    'xante_id' => $xanteId,
                    'hubspot_id' => $hubspotId
                ]);
            }
        }

        // 5. Mapear datos adicionales del Deal
        $clientData = array_merge($clientData, $this->mapDealToClient($dealProps));

        $client->update($clientData);
        
        // 6. Sincronizar datos del cónyuge si existen
        $this->syncSpouseData($client, $dealProps);
        
        Log::info('Cliente actualizado desde HubSpot', [
            'client_id' => $client->id,
            'xante_id' => $xanteId,
            'hubspot_id' => $client->hubspot_id,
            'deal_id' => $dealId,
            'updated_fields' => array_keys($clientData)
        ]);

        // 7. Sincronizar datos del convenio (Propiedad y Financieros)
        // DESHABILITADO POR SOLICITUD DEL USUARIO: Solo sincronizar clientes
        // $this->syncAgreementData($client, $dealProps);
    }

    /**
     * Sincronizar datos del convenio (Propiedad y Financieros) en la tabla agreements
     */
    private function syncAgreementData(Client $client, array $dealProps): void
    {
        try {
            // Buscar si ya existe un convenio asociado a este cliente
            // Priorizamos convenios que NO estén completados, firmados o EN PROCESO avanzado
            // Solo actualizamos borradores o convenios sin iniciar para evitar sobrescribir trabajo manual
            $agreement = \App\Models\Agreement::where('client_id', $client->id)
                ->whereNotIn('status', [
                    'completed', 
                    'convenio_firmado', 
                    'in_progress', 
                    'documents_generated', 
                    'documents_sent', 
                    'awaiting_client_docs', 
                    'documents_complete'
                ])
                ->latest()
                ->first();

            // Si no existe, creamos uno nuevo en estado 'sin_convenio' (o el equivalente inicial)
            if (!$agreement) {
                $agreement = \App\Models\Agreement::create([
                    'client_id' => $client->id,
                    'status' => 'sin_convenio', // Estado inicial
                    'current_step' => 1,
                    'created_by' => 1, // Usuario sistema o default
                    'client_xante_id' => $client->xante_id,
                ]);
                Log::info('Convenio creado automáticamente desde sincronización HubSpot', ['client_id' => $client->id]);
            }

            // Preparar datos para wizard_data
            $wizardData = $agreement->wizard_data ?? [];

            // --- Paso 3: Propiedad ---
            $propertyMap = [
                'domicilio_convenio' => 'property_address', // Ajustar según nombre real en wizard
                'comunidad' => 'community',
                'tipo_vivienda' => 'housing_type',
                'prototipo' => 'prototype',
                'lote' => 'lot',
                'manzana' => 'block',
                'etapa' => 'stage',
                'municipio_propiedad' => 'property_municipality',
                'estado_propiedad' => 'property_state',
            ];

            foreach ($propertyMap as $hubspotField => $wizardField) {
                if (!empty($dealProps[$hubspotField])) {
                    $wizardData[$wizardField] = $dealProps[$hubspotField];
                }
            }

            // --- Paso 4: Financieros ---
            $financialMap = [
                'valor_convenio' => 'agreement_value',
                'precio_promocion' => 'promotion_price',
                'comision_total_pagar' => 'total_commission',
                'ganancia_final' => 'final_profit',
            ];

            foreach ($financialMap as $hubspotField => $wizardField) {
                if (!empty($dealProps[$hubspotField])) {
                    $wizardData[$wizardField] = $dealProps[$hubspotField];
                }
            }

            // Guardar datos actualizados
            $agreement->update([
                'wizard_data' => $wizardData,
                'updated_at' => now()
            ]);

            Log::info('Datos de convenio sincronizados desde HubSpot', [
                'agreement_id' => $agreement->id,
                'client_id' => $client->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error sincronizando datos de convenio: ' . $e->getMessage(), ['client_id' => $client->id]);
        }
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
                
                // Procesar fecha de nacimiento
                if ($clientField === 'birthdate') {
                    try {
                        if (is_numeric($value)) {
                            $value = Carbon::createFromTimestampMs($value)->format('Y-m-d');
                        } else {
                            $value = Carbon::parse($value)->format('Y-m-d');
                        }
                    } catch (\Exception $e) {
                        Log::warning("Error parseando fecha de nacimiento: {$value}");
                        $value = null;
                    }
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
     * Mapear datos del Deal a estructura de Cliente
     */
    private function mapDealToClient(array $dealProps): array
    {
        $clientData = [];

        // Datos del titular desde el Deal
        if (!empty($dealProps['nombre_completo'])) {
            $clientData['name'] = $dealProps['nombre_completo'];
        }
        
        if (!empty($dealProps['email'])) {
            $clientData['email'] = $dealProps['email'];
        }
        
        if (!empty($dealProps['phone'])) {
            $clientData['phone'] = $dealProps['phone'];
        }
        
        if (!empty($dealProps['mobilephone'])) {
            $clientData['phone'] = $dealProps['mobilephone'];
        }
        
        if (!empty($dealProps['telefono_oficina'])) {
            $clientData['office_phone'] = $dealProps['telefono_oficina'];
        }
        
        if (!empty($dealProps['curp'])) {
            $clientData['curp'] = $dealProps['curp'];
        }
        
        if (!empty($dealProps['rfc'])) {
            $clientData['rfc'] = $dealProps['rfc'];
        }
        
        if (!empty($dealProps['estado_civil'])) {
            $clientData['civil_status'] = $dealProps['estado_civil'];
        }
        
        if (!empty($dealProps['ocupacion'])) {
            $clientData['occupation'] = $dealProps['ocupacion'];
        }

        // Domicilio del titular desde el Deal
        if (!empty($dealProps['domicilio_actual'])) {
            $address = $dealProps['domicilio_actual'];
            if (!empty($dealProps['numero_casa'])) {
                $address .= ' #' . $dealProps['numero_casa'];
            }
            $clientData['current_address'] = $address;
        }
        
        if (!empty($dealProps['colonia'])) {
            $clientData['neighborhood'] = $dealProps['colonia'];
        }
        
        if (!empty($dealProps['codigo_postal'])) {
            $clientData['postal_code'] = $dealProps['codigo_postal'];
        }
        
        if (!empty($dealProps['municipio'])) {
            $clientData['municipality'] = $dealProps['municipio'];
        }
        
        if (!empty($dealProps['estado'])) {
            $clientData['state'] = $dealProps['estado'];
        }

        // Nuevos campos para optimización de rendimiento
        if (!empty($dealProps['amount'])) {
            $clientData['hubspot_amount'] = $dealProps['amount'];
        }
        
        if (!empty($dealProps['estatus_de_convenio'])) {
            $clientData['hubspot_status'] = $dealProps['estatus_de_convenio'];
        }

        return $clientData;
    }

    /**
     * Sincronizar datos del cónyuge desde el Deal
     */
    private function syncSpouseData(Client $client, array $dealProps): void
    {
        // Verificar si hay datos del cónyuge en el deal
        if (empty($dealProps['nombre_completo_conyuge'])) {
            // Si no hay nombre de cónyuge, eliminar registro si existe
            if ($client->spouse) {
                $client->spouse->delete();
                Log::info('Cónyuge eliminado (no hay datos en Deal)', [
                    'client_id' => $client->id
                ]);
            }
            return;
        }

        $spouseData = [
            'name' => $dealProps['nombre_completo_conyuge'],
            'email' => $dealProps['email_conyuge'] ?? null,
            'phone' => $dealProps['telefono_movil_conyuge'] ?? null,
            'curp' => $dealProps['curp_conyuge'] ?? null,
        ];

        // Domicilio del cónyuge
        if (!empty($dealProps['domicilio_actual_conyuge'])) {
            $address = $dealProps['domicilio_actual_conyuge'];
            if (!empty($dealProps['numero_casa_conyuge'])) {
                $address .= ' #' . $dealProps['numero_casa_conyuge'];
            }
            $spouseData['current_address'] = $address;
        }

        $spouseData['neighborhood'] = $dealProps['colonia_conyuge'] ?? null;
        $spouseData['postal_code'] = $dealProps['codigo_postal_conyuge'] ?? null;
        $spouseData['municipality'] = $dealProps['municipio_conyuge'] ?? null;
        $spouseData['state'] = $dealProps['estado_conyuge'] ?? null;

        // Crear o actualizar cónyuge
        if ($client->spouse) {
            $client->spouse->update($spouseData);
            Log::info('Cónyuge actualizado desde Deal', [
                'client_id' => $client->id,
                'spouse_id' => $client->spouse->id
            ]);
        } else {
            $client->spouse()->create($spouseData);
            Log::info('Cónyuge creado desde Deal', [
                'client_id' => $client->id
            ]);
        }
    }

    /**
     * Obtener propiedades de contacto a solicitar
     */
    private function getContactProperties(): string
    {
        $standardProperties = array_keys($this->config['mapping']['contact_fields']);
        $customProperties = $this->config['mapping']['custom_properties'];
        
        $allProperties = array_merge($standardProperties, $customProperties, [
            'firstname', 'lastname', 'hs_object_id', 'lastmodifieddate'
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
    /**
     * Actualizar un contacto en HubSpot
     */
    public function updateHubspotContact(string $hubspotId, array $properties): array
    {
        try {
            $response = Http::timeout($this->config['sync']['timeout'])
                ->withHeaders([
                    'Authorization' => "Bearer {$this->token}",
                    'Content-Type' => 'application/json',
                ])
                ->patch($this->baseUrl . "/crm/v3/objects/contacts/{$hubspotId}", [
                    'properties' => $properties
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::error("Error actualizando contacto HubSpot {$hubspotId}", [
                'status' => $response->status(),
                'body' => $response->body(),
                'properties' => $properties
            ]);

            return [
                'success' => false,
                'error' => "HTTP {$response->status()}: {$response->body()}"
            ];

        } catch (\Exception $e) {
            Log::error("Excepción actualizando contacto HubSpot {$hubspotId}", [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar un Deal en HubSpot
     */
    public function updateHubspotDeal(string $dealId, array $properties): array
    {
        try {
            $response = Http::timeout($this->config['sync']['timeout'])
                ->withHeaders([
                    'Authorization' => "Bearer {$this->token}",
                    'Content-Type' => 'application/json',
                ])
                ->patch($this->baseUrl . "/crm/v3/objects/deals/{$dealId}", [
                    'properties' => $properties
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::error("Error actualizando Deal HubSpot {$dealId}", [
                'status' => $response->status(),
                'body' => $response->body(),
                'properties' => $properties
            ]);

            return [
                'success' => false,
                'error' => "HTTP {$response->status()}: {$response->body()}"
            ];

        } catch (\Exception $e) {
            Log::error("Excepción actualizando Deal HubSpot {$dealId}", [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Enviar datos del cliente local a HubSpot (Push)
     */
    public function pushClientToHubspot(Client $client, ?Agreement $agreement = null): array
    {
        $results = [
            'deal_updated' => false,
            'contact_updated' => false,
            'errors' => []
        ];

        // 1. Actualizar Deal (Prioridad)
        if ($client->hubspot_deal_id) {
            $dealProps = $this->mapClientToHubspotDeal($client, $agreement);
            $dealResult = $this->updateHubspotDeal($client->hubspot_deal_id, $dealProps);
            
            if ($dealResult['success']) {
                $results['deal_updated'] = true;
                Log::info("Deal {$client->hubspot_deal_id} actualizado desde local", [
                    'client_id' => $client->id
                ]);
            } else {
                $results['errors'][] = "Error actualizando Deal: " . ($dealResult['error'] ?? 'Desconocido');
            }
        } else {
            $results['errors'][] = "Cliente sin hubspot_deal_id";
        }

        // 2. Actualizar Contacto (Secundario)
        if ($client->hubspot_id) {
            $contactProps = $this->mapClientToHubspotContact($client);
            $contactResult = $this->updateHubspotContact($client->hubspot_id, $contactProps);
            
            if ($contactResult['success']) {
                $results['contact_updated'] = true;
                Log::info("Contacto {$client->hubspot_id} actualizado desde local", [
                    'client_id' => $client->id
                ]);
            } else {
                $results['errors'][] = "Error actualizando Contacto: " . ($contactResult['error'] ?? 'Desconocido');
            }
        }

        return $results;
    }

    /**
     * Mapear Cliente Local -> Propiedades HubSpot Deal
     */
    private function mapClientToHubspotDeal(Client $client, ?Agreement $agreement = null): array
    {
        $props = [];

        // Datos del Titular (Nombres corregidos según diagnóstico)
        if ($client->name) $props['nombre_del_titular'] = $client->name; // Antes: nombre_completo
        
        // Propiedades que NO existen en este portal de HubSpot (Comentadas para evitar error)
        // if ($client->curp) $props['curp'] = $client->curp;
        // if ($client->rfc) $props['rfc'] = $client->rfc;
        // if ($client->civil_status) $props['estado_civil'] = $client->civil_status;
        // if ($client->occupation) $props['ocupacion'] = $client->occupation;

        // Domicilio del Titular
        if ($client->current_address) {
            $props['calle_o_privada_'] = $client->current_address; // Antes: domicilio_actual
        }
        if ($client->neighborhood) $props['colonia'] = $client->neighborhood;
        // if ($client->postal_code) $props['codigo_postal'] = $client->postal_code; // No encontrado
        // if ($client->municipality) $props['municipio'] = $client->municipality; // No encontrado
        if ($client->state) $props['estado'] = $client->state;

        // Datos del Cónyuge (No encontrados en diagnóstico, se comentan por seguridad)
        /*
        if ($client->spouse) {
            $spouse = $client->spouse;
            if ($spouse->name) $props['nombre_completo_conyuge'] = $spouse->name;
            // ... resto de propiedades de cónyuge
        }
        */

        // Mapeo de Estatus de Convenio desde Agreement
        if ($agreement) {
            // Mapear status local a opciones de HubSpot
            // Opciones típicas: 'En Proceso', 'Aceptado', 'Rechazado', 'Completado'
            // Ajusta según tus valores reales en HubSpot
            $statusMap = [
                'draft' => 'En Proceso',
                'in_progress' => 'En Proceso',
                'completed' => 'Aceptado', // O 'Completado'
                'cancelled' => 'Rechazado'
            ];
            
            if (isset($statusMap[$agreement->status])) {
                $props['estatus_de_convenio'] = $statusMap[$agreement->status];
            }
            
            // Si hay valor de propuesta, también podríamos enviarlo si existe el campo en HubSpot
            if ($agreement->proposal_value) {
                $props['amount'] = $agreement->proposal_value;
            }
        }

        return $props;
    }

    /**
     * Mapear Cliente Local -> Propiedades HubSpot Contact
     */
    private function mapClientToHubspotContact(Client $client): array
    {
        $props = [];

        // Mapeo básico
        if ($client->email) $props['email'] = $client->email;
        if ($client->phone) $props['phone'] = $client->phone;
        
        // Separar nombre y apellido si es posible
        if ($client->name) {
            $parts = explode(' ', $client->name);
            if (count($parts) > 1) {
                $props['firstname'] = array_shift($parts);
                $props['lastname'] = implode(' ', $parts);
            } else {
                $props['firstname'] = $client->name;
            }
        }

        // Campos adicionales mapeados en config
        if ($client->current_address) $props['address'] = $client->current_address;
        if ($client->municipality) $props['city'] = $client->municipality;
        if ($client->state) $props['state'] = $client->state;
        if ($client->postal_code) $props['zip'] = $client->postal_code;
        if ($client->occupation) $props['jobtitle'] = $client->occupation;

        return $props;
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
                ->get($this->baseUrl . "/crm/v3/objects/deals/{$dealId}", [
                    'properties' => 'dealname,amount,estatus_de_convenio,dealstage,hs_lastmodifieddate'
                ]);

            if ($response->successful()) {
                return $response->json()['properties'];
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Error obteniendo detalles del Deal {$dealId}: " . $e->getMessage());
            return null;
        }
    }
}
