<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Jobs\SyncHubspotClientsJob;
use App\Services\HubspotSyncService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // BOTÓN ÚNICO DE SINCRONIZACIÓN CONSOLIDADO
            Action::make('sync_hubspot_unified')
                ->label('Sincronizar HubSpot')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Sincronización desde Deals de HubSpot')
                ->modalDescription('Sincroniza clientes desde Deals de HubSpot con estatus "Aceptado". Solo procesa deals que tengan un contacto asociado con xante_id válido.')
                ->modalSubmitActionLabel('Iniciar Sincronización')
                ->disabled(fn() => Cache::get('hubspot_sync_in_progress', false))
                ->action(function () {
                    try {
                        // Verificar si ya hay una sincronización en progreso
                        if (Cache::get('hubspot_sync_in_progress', false)) {
                            Notification::make()
                                ->title('Sincronización en Progreso')
                                ->body('Ya hay una sincronización ejecutándose. Por favor espera a que termine.')
                                ->warning()
                                ->icon('heroicon-o-clock')
                                ->send();
                            return;
                        }

                        // Verificar conexión con HubSpot
                        $syncService = new HubspotSyncService();
                        $connectionTest = $syncService->testConnection();
                        
                        if (!$connectionTest['success']) {
                            Notification::make()
                                ->title('Error de Conexión')
                                ->body('No se pudo conectar con HubSpot: ' . $connectionTest['message'])
                                ->danger()
                                ->icon('heroicon-o-exclamation-triangle')
                                ->duration(10000)
                                ->send();
                            return;
                        }

                        // Despachar Job asíncrono para evitar timeout
                        SyncHubspotClientsJob::dispatch();

                        Notification::make()
                            ->title('Sincronización Iniciada')
                            ->body('La sincronización de Deals de HubSpot se está ejecutando en segundo plano. Recibirás una notificación cuando termine.')
                            ->success()
                            ->icon('heroicon-o-arrow-path')
                            ->duration(8000)
                            ->send();

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error al Iniciar Sincronización')
                            ->body('Ocurrió un error: ' . $e->getMessage())
                            ->danger()
                            ->icon('heroicon-o-exclamation-triangle')
                            ->duration(10000)
                            ->send();
                    }
                }),

            // Botón para ver estadísticas de sincronización
            Action::make('sync_stats')
                ->label('Estadísticas HubSpot')
                ->icon('heroicon-o-chart-bar')
                ->color('gray')
                ->action(function () {
                    $stats = Cache::get('hubspot_last_sync_stats');
                    $lastSync = Cache::get('hubspot_last_sync_completed_at');
                    
                    if (!$stats) {
                        Notification::make()
                            ->title('Sin Estadísticas')
                            ->body('No hay estadísticas de sincronización disponibles. Ejecuta una sincronización primero.')
                            ->info()
                            ->send();
                        return;
                    }

                    $syncService = new HubspotSyncService();
                    $currentStats = $syncService->getSyncStats();

                    Notification::make()
                        ->title('Estadísticas de Sincronización HubSpot')
                        ->body(sprintf(
                            "Última sincronización: %s\n" .
                            "Total clientes: %d\n" .
                            "Con HubSpot ID: %d\n" .
                            "Sin HubSpot ID: %d\n" .
                            "Última ejecución - Nuevos: %d | Actualizados: %d | Omitidos: %d",
                            $lastSync ? $lastSync->format('d/m/Y H:i') : 'Nunca',
                            $currentStats['total_clients'],
                            $currentStats['clients_with_hubspot_id'],
                            $currentStats['clients_without_hubspot_id'],
                            $stats['new_clients'] ?? 0,
                            $stats['updated_clients'] ?? 0,
                            $stats['skipped'] ?? 0
                        ))
                        ->info()
                        ->icon('heroicon-o-information-circle')
                        ->duration(15000)
                        ->send();
                }),
        ];
    }
}
