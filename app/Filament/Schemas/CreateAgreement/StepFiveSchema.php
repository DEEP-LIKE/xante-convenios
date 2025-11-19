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
                // Grid de 3 columnas para las secciones principales
                Grid::make(3)
                    ->schema([
                        // SECCIÓN 1: DATOS DEL TITULAR
                        Section::make('👤 DATOS DEL TITULAR')
                            ->description('Información del titular capturada en Paso 2')
                            ->schema([
                                Placeholder::make('holder_summary')
                                    ->content(function () use ($page) {
                                        return $page->renderHolderSummary($page->data);
                                    })
                                    ->html(),
                            ])
                            ->collapsible()
                            ->collapsed(false),

                        // SECCIÓN 2: DATOS DEL CÓNYUGE/COACREDITADO
                        Section::make('💑 DATOS DEL CÓNYUGE')
                            ->description('Información del cónyuge/coacreditado capturada en Paso 2')
                            ->schema([
                                Placeholder::make('spouse_summary')
                                    ->content(function () use ($page) {
                                        return $page->renderSpouseSummary($page->data);
                                    })
                                    ->html(),
                            ])
                            ->collapsible()
                            ->collapsed(false),

                        // SECCIÓN 3: DATOS DE LA PROPIEDAD
                        Section::make('🏠 DATOS DE LA PROPIEDAD')
                            ->description('Información capturada en Paso 3')
                            ->schema([
                                Placeholder::make('property_summary')
                                    ->content(function () use ($page) {
                                        return $page->renderPropertySummary($page->data);
                                    })
                                    ->html(),
                            ])
                            ->collapsible()
                            ->collapsed(false),
                    ]),

                // SECCIÓN 4: CALCULADORA FINANCIERA (ancho completo)
                Section::make('💰 RESUMEN FINANCIERO')
                    ->description('Cálculos realizados en Paso 4')
                    ->schema([
                        Placeholder::make('financial_summary')
                            ->content(function () use ($page) {
                                return $page->renderFinancialSummary($page->data);
                            })
                            ->html(),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->columnSpanFull(),

                // SECCIÓN DE CONFIRMACIÓN
                Section::make('CONFIRMACIÓN FINAL')
                    ->schema([
                        Placeholder::make('warning_message')
                            ->content(new HtmlString('
                                <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 8px;">
                                    <div style="display: flex;">
                                        <div style="flex-shrink: 0; margin-right: 12px;">
                                            <svg style="height: 20px; width: 20px; color: #f59e0b;" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 style="font-size: 14px; font-weight: 500; color: #92400e; margin: 0;">
                                                Una vez que genere los documentos, NO PODRÁ modificar esta información
                                            </h3>
                                            <div style="margin-top: 8px; font-size: 14px; color: #b45309;">
                                                <p style="margin: 0;">Los documentos PDF se generarán con la información que aparece arriba. Revise cuidadosamente antes de continuar.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            '))
                            ->hiddenLabel(),

                        Checkbox::make('confirm_data_correct')
                            ->label('✓ Confirmo que he revisado toda la información y es correcta')
                            ->required()
                            ->accepted()
                            ->validationMessages([
                                'accepted' => 'Debe confirmar que la información es correcta para continuar.',
                            ])
                            ->helperText('Esta confirmación es obligatoria para poder generar los documentos')
                            ->inline(false)
                            ->dehydrated(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
