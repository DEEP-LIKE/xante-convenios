<?php

namespace App\Console\Commands;

use App\Services\HubspotSyncService;
use App\Jobs\SyncHubspotClientsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class TestHubspotIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hubspot:test {--sync : Execute full synchronization} {--job : Test job execution}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test HubSpot integration functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Probando Integración HubSpot...');
        
        try {
            $syncService = new HubspotSyncService();
            
            // Test 1: Verificar configuración
            $this->info("\n1️⃣ Verificando configuración...");
            $token = config('hubspot.token');
            if (!$token) {
                $this->error('❌ HUBSPOT_TOKEN no configurado');
                return 1;
            }
            $this->info('✅ Token configurado: ' . substr($token, 0, 10) . '...');
            
            // Test 2: Verificar conexión
            $this->info("\n2️⃣ Probando conexión con HubSpot...");
            $connectionTest = $syncService->testConnection();
            if ($connectionTest['success']) {
                $this->info('✅ Conexión exitosa con HubSpot');
            } else {
                $this->error('❌ Error de conexión: ' . $connectionTest['message']);
                return 1;
            }
            
            // Test 3: Obtener estadísticas actuales
            $this->info("\n3️⃣ Estadísticas actuales...");
            $stats = $syncService->getSyncStats();
            $this->table(['Métrica', 'Valor'], [
                ['Total clientes', $stats['total_clients']],
                ['Con HubSpot ID', $stats['clients_with_hubspot_id']],
                ['Sin HubSpot ID', $stats['clients_without_hubspot_id']],
                ['Última sincronización', $stats['last_sync'] ? $stats['last_sync']->format('d/m/Y H:i') : 'Nunca'],
            ]);
            
            // Test 4: Opción de sincronización
            if ($this->option('sync')) {
                $this->info("\n4️⃣ Ejecutando sincronización desde Deals...");
                $this->info('⏳ Sincronizando clientes desde Deals con estatus "Aceptado"...');
                
                $syncStats = $syncService->syncClients(maxPages: 5, timeLimit: 30);
                
                $this->info("\n📊 Resultados de la sincronización:");
                $this->table(['Métrica', 'Valor'], [
                    ['Total Deals procesados', $syncStats['total_deals']],
                    ['Clientes nuevos', $syncStats['new_clients']],
                    ['Clientes actualizados', $syncStats['updated_clients']],
                    ['Omitidos', $syncStats['skipped']],
                    ['Errores', $syncStats['errors']],
                    ['Páginas procesadas', $syncStats['processed_pages']],
                ]);
                
                if ($syncStats['time_limited']) {
                    $this->warn('⚠️  Sincronización detenida por límite de tiempo');
                }
                if ($syncStats['max_pages_reached']) {
                    $this->warn('⚠️  Sincronización detenida por límite de páginas');
                }
            }
            
            // Test 5: Opción de job
            if ($this->option('job')) {
                $this->info("\n5️⃣ Despachando job de sincronización...");
                SyncHubspotClientsJob::dispatch();
                $this->info('✅ Job despachado. Revisa los logs para ver el progreso.');
            }
            
            if (!$this->option('sync') && !$this->option('job')) {
                $this->info("\n💡 Opciones disponibles:");
                $this->line("  --sync : Ejecutar sincronización de prueba desde Deals");
                $this->line("  --job  : Despachar job de sincronización");
            }
            
            $this->info("\n✅ Pruebas completadas exitosamente");
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error en las pruebas: ' . $e->getMessage());
            return 1;
        }
    }
}
