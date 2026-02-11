<?php

namespace App\Filament\Schemas\CreateAgreement;

use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\HtmlString;

class StepFiveSchema
{
    public static function make($page): Step
    {
        return Step::make('Validación')
            ->description('Validación del Coordinador FI')
            ->icon('heroicon-o-shield-check')
            ->completedIcon('heroicon-o-check-circle')
            ->schema([
                // 1. ACCIONES DE COORDINADOR (Solo para roles superiores y si está pendiente)
                Section::make('Acciones de Coordinación')
                    ->description('Como coordinador o administrador, puedes revisar y tomar una decisión sobre esta solicitud.')
                    ->schema([])
                    ->headerActions([
                        Action::make('approve')
                            ->label('Aprobar Calculadora')
                            ->color('success')
                            ->icon('heroicon-o-check-circle')
                            ->requiresConfirmation()
                            ->modalHeading('Aprobar Validación')
                            ->modalDescription('¿Estás seguro de que deseas aprobar esta calculadora? El ejecutivo podrá proceder con la generación de documentos.')
                            ->form([]) // Required to allow actions in some Filament versions
                            ->action(fn ($livewire) => $livewire->approveAgreementAction()),

                        Action::make('requestChanges')
                            ->label('Solicitar Cambios')
                            ->color('warning')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->form([
                                Textarea::make('observations')
                                    ->label('Observaciones / Comentarios')
                                    ->required()
                                    ->placeholder('Explica qué cambios se requieren...'),
                            ])
                            ->modalHeading('Solicitar Cambios')
                            ->modalSubmitActionLabel('Enviar Observaciones')
                            ->action(fn ($livewire, array $data) => $livewire->requestChangesAction($data)),

                        Action::make('reject')
                            ->label('Rechazar')
                            ->color('danger')
                            ->icon('heroicon-o-x-circle')
                            ->form([
                                Textarea::make('reason')
                                    ->label('Motivo del Rechazo')
                                    ->required(),
                            ])
                            ->modalHeading('Rechazar Validación')
                            ->modalSubmitActionLabel('Confirmar Rechazo')
                            ->action(fn ($livewire, array $data) => $livewire->rejectAgreementAction($data)),
                    ])
                    ->visible(function () use ($page) {
                        $agreementId = $page->agreementId ?? request()->get('agreement');
                        $agreement = $agreementId ? \App\Models\Agreement::find($agreementId) : null;
                        
                        return in_array(auth()->user()->role, ['admin', 'coordinador_fi', 'gerencia']) && 
                               $agreement?->validation_status === 'pending';
                    })
                    ->columnSpanFull(),

                // 2. ESTADO DE VALIDACIÓN (Lo más importante - se muestra primero)
                Placeholder::make('validation_status_display')
                    ->label('')
                    ->content(function () use ($page) {
                        $agreementId = $page->agreementId ?? request()->get('agreement');
                        $agreement = $agreementId ? \App\Models\Agreement::find($agreementId) : null;
                        $status = $agreement?->validation_status ?? 'not_required';

                        // Resolver el servicio directamente del contenedor
                        $renderer = app(\App\Services\WizardSummaryRenderer::class);

                        return new HtmlString(
                            $renderer->renderValidationStatus($status, $agreement?->currentValidation)
                        );
                    })
                    ->html()
                    ->hiddenLabel()
                    ->columnSpanFull(),

                // 2. RESUMEN DEL CONVENIO (Después del estado de validación)
                \Filament\Forms\Components\ViewField::make('agreement_summary')
                    ->hiddenLabel()
                    ->view('filament.pages.components.agreement-summary-infolist')
                    ->columnSpanFull(),
            ]);
    }
}
