<?php

namespace App\Filament\Schemas\CreateAgreement;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\HtmlString;

class StepFiveSchema
{
    public static function make($page): Step
    {
        return Step::make('Validación')
            ->description('Resumen y confirmación de datos')
            ->icon('heroicon-o-clipboard-document-check')
            ->afterValidation(function () use ($page) {
                $page->saveStepData(5);
            })
            ->schema([
                // Renderizar el Infolist definido en el Page
                \Filament\Forms\Components\ViewField::make('agreement_summary')
                    ->hiddenLabel()
                    ->view('filament.pages.components.agreement-summary-infolist')
                    ->columnSpanFull(),

                // SECCIÓN DE CONFIRMACIÓN
                Section::make('Confirmación Final')
                    ->schema([
                        Placeholder::make('warning_message')
                            ->content(new HtmlString('
                                <div style="display: flex; align-items: flex-start; gap: 1rem; padding: 1rem; background-color: #fff7ed; border-left: 4px solid #FFD729; border-radius: 0 0.5rem 0.5rem 0; margin-bottom: 1rem;">
                                    <div style="flex-shrink: 0;">
                                        <svg style="height: 1.5rem; width: 1.5rem; color: #f59e0b;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 style="font-size: 0.875rem; font-weight: 700; color: #92400e; margin-bottom: 0.25rem;">
                                            Acción Irreversible
                                        </h3>
                                        <p style="font-size: 0.875rem; color: #78350f;">
                                            Al generar los documentos, la información quedará bloqueada. Verifique cuidadosamente todos los datos antes de continuar.
                                        </p>
                                    </div>
                                </div>
                            '))
                            ->hiddenLabel(),

                        Checkbox::make('confirm_data_correct')
                            ->label('He revisado toda la información y confirmo que es correcta')
                            ->required()
                            ->accepted()
                            ->validationMessages([
                                'accepted' => 'Debe confirmar que la información es correcta para continuar.',
                            ])
                            ->inline(true)  // Estilo inline como Filament
                            ->dehydrated(),
                    ])
                    ->columnSpanFull()
            ]);
    }
}
