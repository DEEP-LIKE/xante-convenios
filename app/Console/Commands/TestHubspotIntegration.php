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
            
            // Test 4: Sincronización completa (opcional)
            if ($this->option('sync')) {
                $this->info("\n4️⃣ Ejecutando sincronización completa...");
                $this->warn('⚠️  Esta operación puede tomar varios minutos');
                
                if ($this->confirm('¿Continuar con la sincronización?')) {
                    $syncStats = $syncService->syncClients();
                    
                    $this->info('✅ Sincronización completada');
                    $this->table(['Métrica', 'Cantidad'], [
                        ['Total en HubSpot', $syncStats['total_hubspot']],
                        ['Nuevos clientes', $syncStats['new_clients']],
                        ['Clientes actualizados', $syncStats['updated_clients']],
                        ['Omitidos (sin xante_id)', $syncStats['skipped']],
                        ['Errores', $syncStats['errors']],
                        ['Páginas procesadas', $syncStats['processed_pages']],
                    ]);
                }
            }
            
            // Test 5: Job de sincronización (opcional)
            if ($this->option('job')) {
                $this->info("\n5️⃣ Probando Job de sincronización...");
                
                if (Cache::get('hubspot_sync_in_progress', false)) {
                    $this->warn('⚠️  Ya hay una sincronización en progreso');
                } else {
                    SyncHubspotClientsJob::dispatch();
                    $this->info('✅ Job de sincronización despachado');
                    $this->info('💡 Ejecuta: php artisan queue:work para procesar el job');
                }
            }
            
            $this->info("\n🎉 Todas las pruebas completadas exitosamente");
            
            // Mostrar comandos útiles
            $this->info("\n📋 Comandos útiles:");
            $this->line("  • Explorar API: php artisan hubspot:explore");
            $this->line("  • Sincronización completa: php artisan hubspot:test --sync");
            $this->line("  • Probar job: php artisan hubspot:test --job");
            $this->line("  • Procesar jobs: php artisan queue:work");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error en las pruebas: ' . $e->getMessage());
            return 1;
        }
    }
}
