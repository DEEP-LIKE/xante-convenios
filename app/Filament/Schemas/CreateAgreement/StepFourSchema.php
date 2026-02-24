<?php

namespace App\Filament\Schemas\CreateAgreement;

use App\Models\ConfigurationCalculator;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\HtmlString;

class StepFourSchema
{
    public static function make($page): Step
    {
        return Step::make('Calculadora')
            ->description('Cálculos financieros del convenio')
            ->icon('heroicon-o-calculator')
            ->afterValidation(function () use ($page) {
                $page->saveStepData(4);

                // Enviar a validación después de completar la calculadora
                $agreementId = $page->agreementId ?? request()->get('agreement');

                \Log::info('Step 4 completed - Checking validation request', [
                    'agreement_id' => $agreementId,
                    'has_agreement_id' => (bool) $agreementId,
                ]);

                if ($agreementId) {
                    $agreement = \App\Models\Agreement::find($agreementId);
                    if ($agreement) {
                        // BLOQUEO: No permitir avanzar si la ganancia final es 0 o negativa
                        $gananciaFinal = (float) str_replace([',', '$'], '', $page->data['ganancia_final'] ?? 0);
                        if ($gananciaFinal <= 0) {
                            Notification::make()
                                ->title('⚠️ Ganancia insuficiente')
                                ->body('La ganancia final estimada debe ser mayor a $0.00 para proceder con este convenio por motivos de rentabilidad.')
                                ->warning()
                                ->persistent()
                                ->send();

                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'ganancia_final' => 'La ganancia final estimada debe ser positiva para continuar.',
                            ]);
                        }

                        $validationService = app(\App\Services\ValidationService::class);
                        try {
                            $validationService->requestValidation($agreement, auth()->user());
                            \Log::info('Validation requested from Filament wizard', [
                                'agreement_id' => $agreement->id,
                                'user_id' => auth()->id(),
                            ]);
                        } catch (\Exception $e) {
                            \Log::error('Error requesting validation from Filament wizard', [
                                'agreement_id' => $agreement->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    } else {
                        \Log::warning('Agreement not found for validation request', ['agreement_id' => $agreementId]);
                    }
                } else {
                    \Log::warning('No agreement ID found in Step 4 completion');
                }
            })
            ->schema([
                // ⭐ Indicador de Pre-cálculo Previo
                Section::make('PRE-CÁLCULO DETECTADO')
                    ->description('Este cliente ya tiene una cotización previa registrada')
                    ->schema([
                        Placeholder::make('existing_proposal_alert')
                            ->label('Revisar que los calculos sean los acordados')
                            ->content(function () use ($page) {
                                $proposalInfo = $page->hasExistingProposal();

                                if (! $proposalInfo) {
                                    return '';
                                }

                                $valorConvenio = $proposalInfo['valor_convenio'] ?? 0;
                                $gananciaFinal = $proposalInfo['ganancia_final'] ?? 0;
                                $fechaCalculo = $proposalInfo['created_at']->format('d/m/Y H:i');

                                return new HtmlString('
                                    <div style="background: linear-gradient(135deg, #F8FAFC 0%, #F1F5F9 100%); 
                                                border-left: 4px solid #3B82F6; 
                                                padding: 20px; 
                                                border-radius: 12px;
                                                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);">
                                        <div style="display: flex; align-items: start; gap: 16px;">
                                            <!-- Icono -->
                                            <div style="flex-shrink: 0;">
                                                <svg style="width: 32px; height: 32px; color: #3B82F6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </div>
                                            
                                            <!-- Contenido -->
                                            <div style="flex: 1;">
                                                <h3 style="font-size: 18px; font-weight: 700; color: #1E3A8A; margin: 0 0 12px 0;">
                                                    Pre-cálculo Previo Detectado
                                                </h3>
                                                
                                                <div style="background: white; padding: 16px; border-radius: 8px; margin-bottom: 12px; border: 1px solid #E2E8F0;">
                                                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; font-size: 14px;">
                                                        <div>
                                                            <strong style="color: #475569;">Fecha del cálculo:</strong><br>
                                                            <span style="color: #1E293B;">'.$fechaCalculo.'</span>
                                                        </div>
                                                        <div>
                                                            <strong style="color: #475569;">Valor Convenio:</strong><br>
                                                            <span style="color: #1E293B; font-weight: 600;">$'.number_format($valorConvenio, 2).'</span>
                                                        </div>
                                                        <div>
                                                            <strong style="color: #475569;">Ganancia Estimada:</strong><br>
                                                            <span style="color: #059669; font-weight: 700; font-size: 16px;">$'.number_format($gananciaFinal, 2).'</span>
                                                        </div>
                                                        <div>
                                                            <strong style="color: #475569;">Estado:</strong><br>
                                                            <span style="background: #3B82F6; color: white; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 600;">ENLAZADO</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <p style="font-size: 13px; color: #64748B; margin: 0; font-style: italic;">
                                                    Los valores han sido precargados automáticamente desde la calculadora previa. Puede modificarlos si es necesario.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ');
                            })
                            ->html(),
                    ])
                    ->visible(fn () => $page->hasExistingProposal() !== null)
                    ->collapsible()
                    ->collapsed(false),

                // Información de la Propiedad (Precargada)
                Section::make('INFORMACIÓN DE LA PROPIEDAD')
                    ->description('Datos precargados desde pasos anteriores')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('domicilio_convenio')
                                    ->label('Domicilio Viv. Convenio')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->extraAttributes(['class' => 'bg-gray-50']),
                                TextInput::make('comunidad')
                                    ->label('Comunidad')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->extraAttributes(['class' => 'bg-gray-50']),
                                TextInput::make('tipo_vivienda')
                                    ->label('Tipo de Vivienda')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->extraAttributes(['class' => 'bg-gray-50']),
                                TextInput::make('prototipo')
                                    ->label('Prototipo')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->extraAttributes(['class' => 'bg-gray-50']),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('lote')
                                    ->label('Lote')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->extraAttributes(['class' => 'bg-gray-50']),
                                TextInput::make('manzana')
                                    ->label('Manzana')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->extraAttributes(['class' => 'bg-gray-50']),
                                TextInput::make('etapa')
                                    ->label('Etapa')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->extraAttributes(['class' => 'bg-gray-50']),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('municipio_propiedad')
                                    ->label('Municipio')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->extraAttributes(['class' => 'bg-gray-50']),
                                TextInput::make('estado_propiedad')
                                    ->label('Estado')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->extraAttributes(['class' => 'bg-gray-50']),
                            ]),
                    ])
                    ->collapsible(),

                // Campo Principal: Valor Convenio
                Section::make('VALOR PRINCIPAL DEL CONVENIO')
                    ->description('Campo principal que rige todos los cálculos financieros')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                TextInput::make('valor_convenio')
                                    ->label('Valor Convenio')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) use ($page) {
                                        if ($state && $state > 0) {
                                            $page->recalculateAllFinancials($set, $get);
                                        } else {
                                            $page->clearCalculatedFields($set);
                                        }
                                    })
                                    ->afterStateHydrated(function ($state, callable $set, callable $get) use ($page) {
                                        if ($state && $state > 0) {
                                            $page->recalculateAllFinancials($set, $get);
                                        }
                                    })
                                    ->helperText('Ingrese el valor del convenio para activar todos los cálculos automáticos')
                                    ->extraAttributes(['class' => 'text-lg font-semibold']),
                            ]),
                    ])
                    ->collapsible(),

                // Configuración de Parámetros
                Section::make('PARÁMETROS DE CÁLCULO')
                    ->description('Configuración de porcentajes y valores base')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('porcentaje_comision_sin_iva')
                                    ->label('% Comisión Sin IVA')
                                    ->numeric()
                                    ->suffix('%')
                                    ->step(0.01)
                                    ->default(function () {
                                        $config = ConfigurationCalculator::where('key', 'comision_sin_iva_default')->first();

                                        return $config ? $config->value : 6.50;
                                    })
                                    ->disabled()
                                    ->dehydrated()
                                    ->extraAttributes(['class' => 'bg-gray-50'])
                                    ->helperText('Valor fijo desde configuración'),
                                TextInput::make('comision_iva_incluido')
                                    ->label('% Comisión TOTAL (IVA incluido)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->step(0.01)
                                    ->afterStateHydrated(function ($component, callable $get) {
                                        $sinIva = (float) $get('porcentaje_comision_sin_iva');
                                        $config = ConfigurationCalculator::where('key', 'iva_valor')->first();
                                        $ivaPercentage = $config ? (float) $config->value : 16.00;
                                        $ivaMultiplier = 1 + ($ivaPercentage / 100);

                                        if ($sinIva > 0 && $ivaMultiplier > 0) {
                                            $conIva = round($sinIva * $ivaMultiplier, 2);
                                            $component->state($conIva);
                                        }
                                    })
                                    ->disabled()
                                    ->dehydrated()
                                    ->extraAttributes(['class' => 'bg-gray-50']),
                                TextInput::make('state_commission_percentage')
                                    ->label('% DE ESCRITURACIÓN')
                                    ->numeric()
                                    ->suffix('%')
                                    ->disabled()
                                    ->dehydrated()
                                    ->extraAttributes(['class' => 'bg-gray-50'])
                                    ->afterStateHydrated(function ($component, $state, callable $get) {
                                        $stateName = $get('estado_propiedad');
                                        if ($stateName) {
                                            $latestRate = app(\App\Services\AgreementCalculatorService::class)->getLatestRateForState($stateName);
                                            if (isset($latestRate)) {
                                                $component->state($latestRate);
                                            }
                                        }
                                    }),
                                TextInput::make('monto_credito')
                                    ->label('Monto de Crédito')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) use ($page) {
                                        if ($get('valor_convenio')) {
                                            $page->recalculateAllFinancials($set, $get);
                                        }
                                    }),
                                Select::make('tipo_credito')
                                    ->label('Tipo de Crédito')
                                    ->options([
                                        'bancario' => 'Bancario',
                                        'infonavit' => 'Infonavit',
                                        'fovissste' => 'Fovissste',
                                        'otro' => 'Otro',
                                    ]),
                            ]),
                    ])
                    ->collapsible(),

                // Valores Calculados Automáticamente
                Section::make('VALORES CALCULADOS')
                    ->description('Estos valores se calculan automáticamente al ingresar el Valor Convenio')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('valor_compraventa')
                                    ->label('Valor CompraVenta')
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->extraAttributes(['class' => 'bg-blue-50 text-blue-800 font-semibold'])
                                    ->helperText('Espejo del Valor Convenio'),
                                TextInput::make('precio_promocion')
                                    ->label('Precio Promoción')
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->extraAttributes(['class' => 'bg-blue-50 text-blue-800 font-semibold'])
                                    ->helperText('Valor Convenio × % de escrituración'),
                                TextInput::make('monto_comision_sin_iva')
                                    ->label('Monto Comisión (Sin IVA)')
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->extraAttributes(['class' => 'bg-yellow-50 text-yellow-800 font-semibold'])
                                    ->helperText('Valor Convenio × % Comisión'),
                                TextInput::make('comision_total_pagar')
                                    ->label('Comisión TOTAL (IVA incluido)')
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->extraAttributes(['class' => 'bg-yellow-50 text-yellow-800 font-semibold'])
                                    ->helperText('Monto Comisión '),
                            ]),
                    ])
                    ->collapsible(),

                // Costos de Operación
                Section::make('COSTOS DE OPERACIÓN')
                    ->description('Campos editables para gastos adicionales')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('isr')
                                    ->label('ISR')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) use ($page) {
                                        if ($get('valor_convenio')) {
                                            $page->recalculateAllFinancials($set, $get);
                                        }
                                    }),
                                TextInput::make('cancelacion_hipoteca')
                                    ->label('Cancelación de Hipoteca')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) use ($page) {
                                        if ($get('valor_convenio')) {
                                            $page->recalculateAllFinancials($set, $get);
                                        }
                                    }),
                                TextInput::make('total_gastos_fi_venta')
                                    ->label('Total Gastos FI (Venta)')
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->extraAttributes(['class' => 'bg-yellow-50 text-yellow-800 font-semibold'])
                                    ->helperText('ISR + Cancelación de Hipoteca'),
                                TextInput::make('ganancia_final')
                                    ->label('Ganancia Final (Est.)')
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated()
                                    ->extraAttributes(['class' => 'bg-green-50 text-green-800 font-bold text-lg'])
                                    ->helperText('Valor Convenio - ISR - Cancelación - Comisión Total - Monto Crédito'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}
