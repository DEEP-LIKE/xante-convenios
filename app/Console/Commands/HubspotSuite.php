<?php

namespace App\Console\Commands;

use App\Services\HubspotSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class HubspotSuite extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hubspot:suite 
                            {action? : La acción a realizar (test, diagnose, explore, list-properties)} 
                            {--id= : ID específico para diagnósticos (Deal ID o Contact ID)} 
                            {--limit=5 : Límite de resultados para listas}
                            {--sync : Ejecutar sincronización real durante el test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Suite unificada de herramientas para diagnóstico y pruebas de HubSpot';

    private string $token;

    private string $baseUrl;

    public function __construct()
    {
        parent::__construct();
        $this->token = config('hubspot.token') ?? '';
        $this->baseUrl = config('hubspot.api_base_url') ?? '';
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        if (! $action) {
            $action = $this->choice('Selecciona una acción', [
                'test',
                'diagnose',
                'explore',
                'list-properties',
                'exit',
            ], 'test');
        }

        if ($action === 'exit') {
            return 0;
        }

        $this->info('🚀 Iniciando HubSpot Suite: '.strtoupper($action));
        $this->line('═══════════════════════════════════════════════════');

        switch ($action) {
            case 'test':
                return $this->runTests();
            case 'diagnose':
                return $this->runDiagnosis();
            case 'explore':
                return $this->runExploration();
            case 'list-properties':
                return $this->listProperties();
            default:
                $this->error("Acción no reconocida: $action");

                return 1;
        }
    }

    // ==========================================
    // ACCIÓN: TEST (Integración General)
    // ==========================================
    private function runTests()
    {
        $this->info('🧪 Probando Integración General...');

        $syncService = app(HubspotSyncService::class);

        // 1. Configuración
        $this->info("\n1️⃣ Verificando configuración...");
        if (! $this->token) {
            $this->error('❌ HUBSPOT_TOKEN no configurado');

            return 1;
        }
        $this->info('✅ Token configurado: '.substr($this->token, 0, 10).'...');

        // 2. Conexión
        $this->info("\n2️⃣ Probando conexión con HubSpot...");
        $connectionTest = $syncService->testConnection();
        if ($connectionTest['success']) {
            $this->info('✅ Conexión exitosa con HubSpot');
        } else {
            $this->error('❌ Error de conexión: '.$connectionTest['message']);

            return 1;
        }

        // 3. Estadísticas
        $this->info("\n3️⃣ Estadísticas actuales...");
        $stats = $syncService->getSyncStats();
        $this->table(['Métrica', 'Valor'], [
            ['Total clientes', $stats['total_clients']],
            ['Con HubSpot ID', $stats['clients_with_hubspot_id']],
            ['Sin HubSpot ID', $stats['clients_without_hubspot_id']],
            ['Última sincronización', $stats['last_sync'] ? $stats['last_sync']->format('d/m/Y H:i') : 'Nunca'],
        ]);

        // 4. Sincronización (Opcional)
        if ($this->option('sync')) {
            $this->info("\n4️⃣ Ejecutando sincronización real...");
            $syncStats = $syncService->syncClients(maxPages: 5, timeLimit: 30);

            $this->info("\n📊 Resultados:");
            $this->table(['Métrica', 'Valor'], [
                ['Total Deals procesados', $syncStats['total_deals']],
                ['Clientes nuevos', $syncStats['new_clients']],
                ['Clientes actualizados', $syncStats['updated_clients']],
                ['Omitidos', $syncStats['skipped']],
                ['Errores', $syncStats['errors']],
            ]);
        } else {
            $this->info("\n💡 Usa --sync para ejecutar una sincronización real.");
        }

        return 0;
    }

    // ==========================================
    // ACCIÓN: DIAGNOSE (Relaciones Deal-Contact)
    // ==========================================
    private function runDiagnosis()
    {
        $dealId = $this->option('id');

        if (! $dealId) {
            $this->info('Buscando un Deal reciente para diagnosticar...');
            $dealId = $this->getFirstDealId();
            if (! $dealId) {
                $this->error('❌ No se encontraron Deals en HubSpot para analizar.');

                return 1;
            }
        }

        $this->info("\n🎯 Analizando Deal ID: {$dealId}");

        // Estrategia 1: Propiedades
        $this->info("\n📦 Analizando propiedades del Deal...");
        $syncProperties = implode(',', config('hubspot.deal_sync.properties'));
        $response = Http::withToken($this->token)->get($this->baseUrl."/crm/v3/objects/deals/{$dealId}?properties={$syncProperties}");
        
        if ($response->successful()) {
            $props = $response->json()['properties'] ?? [];
            $this->line('   • Nombre: '.($props['dealname'] ?? 'N/A'));
            $this->line('   • Monto: '.($props['amount'] ?? 'N/A'));
            $this->line('   • Estatus Convenio (estatus_de_convenio): '.($props['estatus_de_convenio'] ?? '⚠️  VACÍO'));
            $this->line('   • Domicilio Actual: '.($props['domicilio_actual'] ?? '⚠️  VACÍO'));
            $this->line('   • Nombre del Desarrollo: '.($props['nombre_del_desarrollo'] ?? '⚠️  VACÍO'));
            $this->line('   • Calle/Privada: '.($props['calle_o_privada_'] ?? '⚠️  VACÍO'));

            // Buscar campos de contacto ocultos
            $contactFields = [];
            foreach ($props as $key => $val) {
                if (str_contains($key, 'contact') || str_contains($key, 'email') || str_contains($key, 'phone')) {
                    $contactFields[$key] = $val;
                }
            }
            if ($contactFields) {
                $this->info('   ✅ Campos de contacto encontrados en propiedades:');
                foreach ($contactFields as $k => $v) {
                    $this->line("     - $k: $v");
                }
            }
        }

        // Estrategia 2: Asociaciones V3
        $this->info("\n🔗 Verificando Asociaciones (API v3)...");
        $assocResponse = Http::withToken($this->token)->get($this->baseUrl."/crm/v3/objects/deals/{$dealId}/associations/contacts");
        if ($assocResponse->successful()) {
            $results = $assocResponse->json()['results'] ?? [];
            if ($results) {
                $this->info('   ✅ '.count($results).' asociaciones encontradas.');
                foreach ($results as $assoc) {
                    $contactId = $assoc['id'] ?? $assoc['toObjectId'];
                    $this->line("     - Contact ID: {$contactId}");
                    
                    // Verificamos propiedades del contacto
                    $contactPropsToFetch = implode(',', [
                        'firstname', 'lastname', 'email', 'xante_id', 'xante_client_id', 'id_xante', 'client_xante_id'
                    ]);
                    $contactResp = Http::withToken($this->token)->get($this->baseUrl."/crm/v3/objects/contacts/{$contactId}?properties={$contactPropsToFetch}");
                    if ($contactResp->successful()) {
                        $cProps = $contactResp->json()['properties'] ?? [];
                        $this->line("       • Email: ".($cProps['email'] ?? 'N/A'));
                        
                        // Verificar Xante IDs
                        $foundId = null;
                        foreach (['xante_id', 'xante_client_id', 'id_xante', 'client_xante_id'] as $f) {
                            if (!empty($cProps[$f])) {
                                $this->line("       • ID Encontrado ({$f}): {$cProps[$f]}");
                                $foundId = $cProps[$f];
                            }
                        }
                        if (!$foundId) {
                            $this->warn("       ⚠️  No se encontró ningún ID de Xante en este contacto.");
                        }
                    }
                }
            } else {
                $this->warn('   ⚠️  Sin asociaciones directas.');
            }
        }

        // Estrategia 3: Campo hs_all_associated_contact_ids
        $this->info("\n🔍 Verificando campo 'hs_all_associated_contact_ids'...");
        $searchResponse = Http::withToken($this->token)->get($this->baseUrl."/crm/v3/objects/deals/{$dealId}?properties=hs_all_associated_contact_ids");
        if ($searchResponse->successful()) {
            $val = $searchResponse->json()['properties']['hs_all_associated_contact_ids'] ?? null;
            if ($val) {
                $this->info("   ✅ Campo encontrado: $val");
            } else {
                $this->warn('   ⚠️  Campo vacío o no existente.');
            }
        }

        return 0;
    }

    // ==========================================
    // ACCIÓN: EXPLORE (Exploración General)
    // ==========================================
    private function runExploration()
    {
        $limit = $this->option('limit');
        $this->info("🔎 Explorando últimos $limit Deals...");

        $response = Http::withToken($this->token)->get($this->baseUrl.'/crm/v3/objects/deals', [
            'limit' => $limit,
            'properties' => 'dealname,amount,dealstage,createdate,estatus_de_convenio',
        ]);

        if ($response->successful()) {
            $deals = $response->json()['results'] ?? [];
            $headers = ['ID', 'Nombre', 'Estatus Convenio', 'Fecha'];
            $data = [];

            foreach ($deals as $deal) {
                $props = $deal['properties'];
                $data[] = [
                    $deal['id'],
                    substr($props['dealname'] ?? '', 0, 30),
                    $props['estatus_de_convenio'] ?? 'N/A',
                    $props['createdate'] ?? 'N/A',
                ];
            }
            $this->table($headers, $data);
        } else {
            $this->error('Error explorando deals: '.$response->body());
        }

        return 0;
    }

    // ==========================================
    // ACCIÓN: LIST-PROPERTIES (Listar Campos)
    // ==========================================
    private function listProperties()
    {
        $this->info('📋 Listando propiedades de Contactos...');

        $response = Http::withToken($this->token)->get($this->baseUrl.'/crm/v3/properties/contacts');

        if ($response->successful()) {
            $properties = $response->json()['results'];
            $headers = ['Name', 'Label', 'Type'];
            $data = [];

            $keywords = ['address', 'city', 'state', 'zip', 'birth', 'job', 'occupation', 'curp', 'rfc', 'phone'];

            foreach ($properties as $prop) {
                $name = $prop['name'];
                // Filtrar por keywords relevantes
                foreach ($keywords as $kw) {
                    if (str_contains($name, $kw)) {
                        $data[] = [
                            $prop['name'],
                            substr($prop['label'], 0, 40),
                            $prop['type'],
                        ];
                        break;
                    }
                }
            }

            $this->table($headers, $data);
            $this->info("\nSe muestran solo propiedades relevantes (dirección, trabajo, etc.)");
        } else {
            $this->error('Error obteniendo propiedades.');
        }

        return 0;
    }

    private function getFirstDealId(): ?string
    {
        $response = Http::withToken($this->token)->get($this->baseUrl.'/crm/v3/objects/deals', ['limit' => 1]);

        return $response->json()['results'][0]['id'] ?? null;
    }
}
