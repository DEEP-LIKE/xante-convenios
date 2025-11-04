<?php

namespace App\Jobs;

use App\Services\HubspotSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;

class SyncHubspotClientsJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $timeout = 300; // 5 minutos
    public int $tries = 3;
    public int $maxExceptions = 3;

    private ?int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(?int $userId = null)
    {
        $this->userId = $userId;
        // $this->onQueue('hubspot-sync');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Iniciando job de sincronización HubSpot', [
            'user_id' => $this->userId,
            'job_id' => $this->job->getJobId()
        ]);

        // Marcar sincronización en progreso
        Cache::put('hubspot_sync_in_progress', true, 600); // 10 minutos
        Cache::put('hubspot_sync_started_at', now(), 600);

        try {
            $syncService = new HubspotSyncService();
            
            // Verificar conexión primero
            $connectionTest = $syncService->testConnection();
            if (!$connectionTest['success']) {
                throw new \Exception('Error de conexión con HubSpot: ' . $connectionTest['message']);
            }

            // Ejecutar sincronización
            $stats = $syncService->syncClients();
            
            // Guardar estadísticas en caché
            Cache::put('hubspot_last_sync_stats', $stats, 3600); // 1 hora
            Cache::put('hubspot_last_sync_completed_at', now(), 3600);
            
            Log::info('Sincronización HubSpot completada exitosamente', $stats);
            
            // Notificar al usuario si está disponible
            if ($this->userId) {
                $this->sendSuccessNotification($stats);
            }

        } catch (\Exception $e) {
            Log::error('Error en job de sincronización HubSpot', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $this->userId
            ]);

            // Notificar error al usuario
            if ($this->userId) {
                $this->sendErrorNotification($e->getMessage());
            }

            throw $e;

        } finally {
            // Limpiar flags de progreso
            Cache::forget('hubspot_sync_in_progress');
            Cache::forget('hubspot_sync_started_at');
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job de sincronización HubSpot falló', [
            'error' => $exception->getMessage(),
            'user_id' => $this->userId,
            'attempts' => $this->attempts()
        ]);

        // Limpiar flags de progreso
        Cache::forget('hubspot_sync_in_progress');
        Cache::forget('hubspot_sync_started_at');

        // Notificar fallo al usuario
        if ($this->userId) {
            $this->sendErrorNotification('La sincronización falló después de ' . $this->attempts() . ' intentos');
        }
    }

    /**
     * Enviar notificación de éxito
     */
    private function sendSuccessNotification(array $stats): void
    {
        try {
            Notification::make()
                ->title('Sincronización HubSpot Completada')
                ->body(sprintf(
                    'Nuevos clientes: %d | Actualizados: %d | Omitidos: %d | Errores: %d',
                    $stats['new_clients'],
                    $stats['updated_clients'],
                    $stats['skipped'],
                    $stats['errors']
                ))
                ->success()
                ->icon('heroicon-o-check-circle')
                ->duration(10000)
                ->send();
                // ->sendToDatabase(\App\Models\User::find($this->userId));

        } catch (\Exception $e) {
            Log::warning('No se pudo enviar notificación de éxito', [
                'error' => $e->getMessage(),
                'user_id' => $this->userId
            ]);
        }
    }

    /**
     * Enviar notificación de error
     */
    private function sendErrorNotification(string $errorMessage): void
    {
        try {
            Notification::make()
                ->title('Error en Sincronización HubSpot')
                ->body($errorMessage)
                ->danger()
                ->icon('heroicon-o-exclamation-triangle')
                ->duration(15000)
                ->sendToDatabase(\App\Models\User::find($this->userId));

        } catch (\Exception $e) {
            Log::warning('No se pudo enviar notificación de error', [
                'error' => $e->getMessage(),
                'user_id' => $this->userId
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['hubspot', 'sync', 'clients', 'user:' . $this->userId];
    }
}
