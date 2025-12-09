<?php

namespace App\Filament\Schemas\CreateAgreement;

use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Section;
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
                // 1. ESTADO DE VALIDACIÓN (Lo más importante - se muestra primero)
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
