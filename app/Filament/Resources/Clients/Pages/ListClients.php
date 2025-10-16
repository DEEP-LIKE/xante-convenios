<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use Filament\Actions\Action; // <-- Importación necesaria para el botón personalizado
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification; // <-- Importación necesaria para mostrar un mensaje

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Botón de Sincronización Personalizado
            Action::make('sync')
                ->label('Sincronizar') // Etiqueta del botón
                ->icon('heroicon-o-arrow-path') // Icono de sincronización
                ->color('danger') // Color principal de Filament (azul)
                ->action(function () {
                    // Lógica que se ejecuta al presionar el botón (ej: llamar a un job o servicio)
                    Notification::make()
                        ->title('Sincronización Iniciada')
                        ->body('La sincronización con Hubspot de la fuente de datos externa ha comenzado.')
                        ->warning() // CAMBIADO A COLOR AMARILLO/WARNING
                        ->icon('heroicon-o-arrow-path') // AÑADIDO ÍCONO DE SINCRONIZACIÓN
                        ->send();
                }),

            // Botón de Crear Cliente (ya existente)
            CreateAction::make(),
        ];
    }
}
