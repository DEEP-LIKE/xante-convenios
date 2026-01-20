<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Jobs\SyncHubspotClientsJob;
use App\Services\HubspotSyncService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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
                ->disabled(fn () => Cache::get('hubspot_sync_in_progress', false))
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
                        $syncService = app(HubspotSyncService::class);
                        $connectionTest = $syncService->testConnection();

                        if (! $connectionTest['success']) {
                            Notification::make()
                                ->title('Error de Conexión')
                                ->body('No se pudo conectar con HubSpot: '.$connectionTest['message'])
                                ->danger()
                                ->icon('heroicon-o-exclamation-triangle')
                                ->duration(10000)
                                ->send();

                            return;
                        }

                        // Despachar Job asíncrono para evitar timeout
                        SyncHubspotClientsJob::dispatch(Auth::id());

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
                            ->body('Ocurrió un error: '.$e->getMessage())
                            ->danger()
                            ->icon('heroicon-o-exclamation-triangle')
                            ->duration(10000)
                            ->send();
                    }
                }),
        ];
    }
}
