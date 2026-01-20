<?php

namespace App\Filament\Resources\QuoteValidationResource\Pages;

use App\Filament\Resources\QuoteValidationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuoteValidation extends EditRecord
{
    protected static string $resource = QuoteValidationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Aprobar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Aprobar Validación')
                ->modalDescription('¿Está seguro de que desea aprobar esta validación?')
                ->visible(fn (): bool => in_array($this->record->status, ['pending', 'rejected']) &&
                    auth()->user()->can('approve', $this->record) &&
                    ! $this->record->hasValueChanges(
                        (float) str_replace([',', '$'], '', $this->data['calculator_snapshot']['valor_convenio'] ?? 0),
                        (float) str_replace([',', '$'], '', $this->data['calculator_snapshot']['porcentaje_comision_sin_iva'] ?? 0)
                    ))
                ->action(function () {
                    // Guardar primero por si hubo cambios menores no detectados (aunque debería estar bloqueado)
                    $this->save();

                    app(\App\Services\ValidationService::class)->approveValidation($this->record, auth()->user());

                    \Filament\Notifications\Notification::make()
                        ->title('Validación Aprobada')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            Actions\Action::make('approve_with_changes')
                ->label('Solicitar Autorización')
                ->icon('heroicon-o-shield-check')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Solicitar Autorización de Cambios')
                ->modalDescription('Se han detectado cambios en los valores originales. Ingrese el motivo del descuento para solicitar autorización a gerencia.')
                ->form([
                    \Filament\Forms\Components\Textarea::make('justification')
                        ->label('Motivo y Monto de Descuento')
                        ->required()
                        ->rows(4)
                        ->placeholder('Justifique por qué se están modificando los valores...'),
                ])
                ->visible(fn (): bool => in_array($this->record->status, ['pending', 'rejected']) &&
                    auth()->user()->role === 'coordinador_fi' &&
                    $this->record->hasValueChanges(
                        (float) str_replace([',', '$'], '', $this->data['calculator_snapshot']['valor_convenio'] ?? 0),
                        (float) str_replace([',', '$'], '', $this->data['calculator_snapshot']['porcentaje_comision_sin_iva'] ?? 0)
                    ))
                ->action(function (array $data) {
                    // NO guardar los cambios en el registro principal ($this->save())
                    // Los valores nuevos deben quedar SOLO en la solicitud de autorización

                    // 1. Valores Originales (del registro en BD sin modificar)
                    $oldSnapshot = $this->record->calculator_snapshot;
                    $oldPrice = (float) str_replace([',', '$'], '', $oldSnapshot['valor_convenio'] ?? 0);
                    $oldCommission = (float) str_replace([',', '$'], '', $oldSnapshot['porcentaje_comision_sin_iva'] ?? 0);

                    // 2. Valores Nuevos (del formulario actual)
                    $formSnapshot = $this->data['calculator_snapshot'] ?? [];
                    $newPrice = (float) str_replace([',', '$'], '', $formSnapshot['valor_convenio'] ?? 0);
                    $newCommission = (float) str_replace([',', '$'], '', $formSnapshot['porcentaje_comision_sin_iva'] ?? 0);

                    $this->record->requestAuthorization(
                        auth()->id(),
                        $newPrice,
                        $newCommission,
                        $data['justification'],
                        $oldPrice,
                        $oldCommission
                    );

                    \Filament\Notifications\Notification::make()
                        ->title('Autorización Solicitada')
                        ->body('Se ha enviado la solicitud a gerencia.')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            Actions\Action::make('request_changes')
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

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            Actions\Action::make('reject')
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

                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
