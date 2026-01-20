<?php

namespace App\Filament\Resources\QuoteValidationResource\Pages;

use App\Filament\Resources\QuoteValidationResource;
use Filament\Resources\Pages\ViewRecord;

class ViewQuoteValidation extends ViewRecord
{
    protected static string $resource = QuoteValidationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('approve')
                ->label('Aprobar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Aprobar Validación')
                ->modalDescription('¿Está seguro de que desea aprobar esta validación?')
                ->visible(fn (): bool => in_array($this->record->status, ['pending', 'rejected']) && auth()->user()->can('approve', $this->record))
                ->action(function () {
                    app(\App\Services\ValidationService::class)->approveValidation($this->record, auth()->user());

                    \Filament\Notifications\Notification::make()
                        ->title('Validación Aprobada')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            \Filament\Actions\Action::make('request_changes')
                ->label('Solicitar Cambios')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\Textarea::make('observations')
                        ->label('Observaciones')
                        ->required()
                        ->rows(5)
                        ->placeholder('Describe los cambios que necesitas que realice el ejecutivo...'),
                ])
                ->visible(fn (): bool => in_array($this->record->status, ['pending', 'rejected']) && auth()->user()->can('requestChanges', $this->record))
                ->action(function (array $data) {
                    app(\App\Services\ValidationService::class)->requestChanges(
                        $this->record,
                        auth()->user(),
                        $data['observations']
                    );

                    \Filament\Notifications\Notification::make()
                        ->title('Observaciones Enviadas')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            \Filament\Actions\Action::make('reject')
                ->label('Rechazar')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Motivo del Rechazo')
                        ->required()
                        ->rows(4),
                ])
                ->visible(fn (): bool => in_array($this->record->status, ['pending', 'rejected']) && auth()->user()->can('reject', $this->record))
                ->action(function (array $data) {
                    app(\App\Services\ValidationService::class)->rejectValidation(
                        $this->record,
                        auth()->user(),
                        $data['reason']
                    );

                    \Filament\Notifications\Notification::make()
                        ->title('Validación Rechazada')
                        ->danger()
                        ->send();

                    $this->refreshFormData(['status']);
                }),
        ];
    }
}
