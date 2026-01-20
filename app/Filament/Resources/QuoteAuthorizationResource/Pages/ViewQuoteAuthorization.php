<?php

namespace App\Filament\Resources\QuoteAuthorizationResource\Pages;

use App\Filament\Resources\QuoteAuthorizationResource;
use Filament\Resources\Pages\ViewRecord;

class ViewQuoteAuthorization extends ViewRecord
{
    protected static string $resource = QuoteAuthorizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('approve')
                ->label('Aprobar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Aprobar Solicitud')
                ->modalDescription('¿Está seguro de que desea aprobar esta solicitud?')
                ->visible(fn ($record): bool => $record->isPending() && auth()->user()->can('approve', $record))
                ->action(function ($record) {
                    $record->approve(auth()->id());

                    \Filament\Notifications\Notification::make()
                        ->title('Solicitud Aprobada')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            \Filament\Actions\Action::make('reject')
                ->label('Rechazar')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Rechazar Solicitud')
                ->form([
                    \Filament\Forms\Components\Textarea::make('rejection_reason')
                        ->label('Motivo del Rechazo')
                        ->required()
                        ->rows(3),
                ])
                ->visible(fn ($record): bool => $record->isPending() && auth()->user()->can('reject', $record))
                ->action(function ($record, array $data) {
                    $record->reject(auth()->id(), $data['rejection_reason']);

                    \Filament\Notifications\Notification::make()
                        ->title('Solicitud Rechazada')
                        ->danger()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }
}
