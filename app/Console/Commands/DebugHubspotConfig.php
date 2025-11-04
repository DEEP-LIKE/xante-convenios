<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HubspotSyncService;

class DebugHubspotConfig extends Command
{
    protected $signature = 'hubspot:debug';
    protected $description = 'Debug HubSpot configuration';

    public function handle()
    {
        $this->info('🔍 DIAGNÓSTICO DE CONFIGURACIÓN HUBSPOT');
        $this->newLine();

        // 1. Verificar variables de entorno
        $this->info('1️⃣ Variables de Entorno:');
        $this->line('   HUBSPOT_TOKEN: ' . (env('HUBSPOT_TOKEN') ? '✅ Configurado' : '❌ No encontrado'));
        $this->newLine();

        // 2. Verificar configuración cargada
        $this->info('2️⃣ Configuración Cargada:');
        $token = config('hubspot.token');
        $this->line('   Token: ' . ($token ? '✅ ' . substr($token, 0, 15) . '...' : '❌ No cargado'));
        $this->line('   Base URL: ' . (config('hubspot.api_base_url') ?: '❌ No configurado'));
        $this->line('   Endpoint Contacts: ' . (config('hubspot.endpoints.contacts') ?: '❌ No configurado'));
        $this->newLine();

        // 3. Probar servicio
        $this->info('3️⃣ Probando Servicio:');
        try {
            $service = new HubspotSyncService();
            $this->line('   Servicio: ✅ Inicializado correctamente');
            
            // Probar conexión
            $this->line('   Conexión: Probando...');
            $result = $service->testConnection();
            
            if ($result['success']) {
                $this->line('   Conexión: ✅ ' . $result['message']);
            } else {
                $this->error('   Conexión: ❌ ' . $result['message']);
                if (isset($result['error'])) {
                    $this->error('   Error: ' . $result['error']);
                }
            }
            
        } catch (\Exception $e) {
            $this->error('   Servicio: ❌ ' . $e->getMessage());
        }
        $this->newLine();

        // 4. Verificar base de datos
        $this->info('4️⃣ Base de Datos:');
        try {
            $clientsCount = \App\Models\Client::count();
            $this->line('   Total Clientes: ' . $clientsCount);
            
            $withHubspot = \App\Models\Client::whereNotNull('hubspot_id')->count();
            $this->line('   Con HubSpot ID: ' . $withHubspot);
            
            $withXante = \App\Models\Client::whereNotNull('xante_id')->count();
            $this->line('   Con Xante ID: ' . $withXante);
            
        } catch (\Exception $e) {
            $this->error('   ❌ Error en BD: ' . $e->getMessage());
        }
        $this->newLine();

        // 5. Verificar cola
        $this->info('5️⃣ Sistema de Colas:');
        $queueDriver = config('queue.default');
        $this->line('   Driver: ' . $queueDriver);
        
        if ($queueDriver === 'sync') {
            $this->warn('   ⚠️ Usando driver "sync" - los jobs se ejecutan sincrónicamente');
            $this->warn('   Para background jobs, cambia QUEUE_CONNECTION=database en .env');
        } else {
            $this->line('   ✅ Configurado para jobs en background');
        }

        $this->newLine();
        $this->info('✅ Diagnóstico completado');

        return 0;
    }
}