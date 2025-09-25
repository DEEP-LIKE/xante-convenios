<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use App\Models\Agreement;
use App\Models\Client;
use App\Models\ConfigurationCalculator;
use Illuminate\Support\Facades\Auth;
use BackedEnum;


class CreateAgreementWizard extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-plus';
    protected static ?string $title = 'Nuevo Convenio';
    protected static ?string $navigationLabel = 'Crear Convenio';
    protected static bool $shouldRegisterNavigation = false;
    
    public string $view = 'filament.pages.create-agreement-wizard';
    
    public ?array $data = [];
    public ?int $agreementId = null;
    public int $currentStep = 1;
    public int $totalSteps = 4;

    public function mount(?int $agreement = null): void
    {
        if ($agreement) {
            $this->agreementId = $agreement;
            $agreementModel = Agreement::findOrFail($agreement);
            $this->data = $agreementModel->wizard_data ?? [];
        } else {
            // Crear nuevo convenio
            $newAgreement = Agreement::create([
                'status' => 'expediente_incompleto',
                'current_step' => 1,
                'created_by' => Auth::id(),
            ]);
            $this->agreementId = $newAgreement->id;
            $this->data = [];
        }
        
        // Pre-cargar valores de configuración
        $this->loadCalculatorDefaults();
        
        // Llenar el formulario con los datos
        $this->form->fill($this->data);
    }

    protected function getFormSchema(): array
    {
        return [
                Wizard::make([
                    Step::make('Búsqueda e Identificación')
                        ->icon('heroicon-o-magnifying-glass')
                        ->schema([
                            TextInput::make('search_term')
                                ->label('Buscar Cliente')
                                ->placeholder('ID Xante, nombre, email, CURP')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state) {
                                    if (!empty($state)) {
                                        $this->searchClient();
                                    }
                                }),
                            Select::make('client_id')
                                ->label('Cliente Seleccionado')
                                ->options(Client::limit(50)->pluck('name', 'id'))
                                ->searchable()
                                ->createOptionForm([
                                    TextInput::make('name')->required(),
                                    TextInput::make('email')->email(),
                                    TextInput::make('phone'),
                                ])
                                ->createOptionUsing(function (array $data) {
                                    return Client::create($data)->id;
                                }),
                        ])
                        ->afterValidation(function () {
                            $this->saveStepData(1);
                        }),

                    Step::make('Datos del Cliente')
                        ->icon('heroicon-o-user')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('client_name')
                                        ->label('Nombre Completo')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('client_email')
                                        ->label('Email')
                                        ->email()
                                        ->required(),
                                    TextInput::make('client_phone')
                                        ->label('Teléfono')
                                        ->tel()
                                        ->maxLength(20),
                                    TextInput::make('client_curp')
                                        ->label('CURP')
                                        ->maxLength(18)
                                        ->minLength(18),
                                ]),
                        ])
                        ->afterValidation(function () {
                            $this->saveStepData(2);
                        }),

                    Step::make('Calculadora Financiera')
                        ->icon('heroicon-o-calculator')
                        ->schema([
                            Section::make('DATOS Y VALOR VIVIENDA')
                                ->description('Campo de entrada principal: Precio Promoción')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('precio_promocion')
                                                ->label('Precio Promoción (CAMPO PRINCIPAL)')
                                                ->numeric()
                                                ->prefix('$')
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    // FLUJO CORRECTO: Precio Promoción → Valor Convenio (÷1.09)
                                                    if ($state && $state > 0) {
                                                        $valorConvenio = round($state / 1.09, 2);
                                                        $set('valor_convenio', $valorConvenio);
                                                        $this->recalculateFinancials($set, $get);
                                                    }
                                                })
                                                ->helperText('Campo de entrada principal. Al modificar este valor se recalcula automáticamente el Valor Convenio.'),
                                            TextInput::make('porcentaje_comision_sin_iva')
                                                ->label('% Comisión (Sin IVA)')
                                                ->numeric()
                                                ->suffix('%')
                                                ->step(0.01)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    $this->recalculateFinancials($set, $get);
                                                }),
                                        ]),
                                ]),
                                
                            Section::make('Cálculo de Valores de Convenio')
                                ->description('Valores calculados automáticamente')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('valor_convenio')
                                                ->label('Valor Convenio (Calculado)')
                                                ->numeric()
                                                ->prefix('$')
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    // Solo recalcular comisiones, NO precio promoción
                                                    $this->recalculateFinancials($set, $get);
                                                })
                                                ->helperText('Se calcula automáticamente desde Precio Promoción. También puede editarse directamente.'),
                                            TextInput::make('monto_credito')
                                                ->label('Monto Crédito')
                                                ->numeric()
                                                ->prefix('$')
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    $this->recalculateFinancials($set, $get);
                                                }),
                                        ]),
                                ]),
                            
                            Section::make('Información de la Propiedad')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('domicilio_convenio')
                                                ->label('Domicilio Viv. Convenio')
                                                ->maxLength(255),
                                            TextInput::make('comunidad')
                                                ->label('Comunidad')
                                                ->maxLength(255),
                                            TextInput::make('tipo_vivienda')
                                                ->label('Tipo Vivienda')
                                                ->maxLength(100),
                                            TextInput::make('prototipo')
                                                ->label('Prototipo')
                                                ->maxLength(100),
                                        ]),
                                ]),
                            
                            Section::make('Costos de Operación y Ganancia Final')
                                ->description('Campos editables y cálculo final de ganancia')
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('isr')
                                                ->label('ISR')
                                                ->numeric()
                                                ->prefix('$')
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    $this->recalculateFinancials($set, $get);
                                                }),
                                            TextInput::make('cancelacion_hipoteca')
                                                ->label('Cancelación Hipoteca')
                                                ->numeric()
                                                ->prefix('$')
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    $this->recalculateFinancials($set, $get);
                                                }),
                                            TextInput::make('ganancia_final')
                                                ->label('Ganancia Final')
                                                ->prefix('$')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->extraAttributes(['class' => 'text-green-600 font-bold'])
                                                ->helperText('Calculado automáticamente: Valor Convenio - Comisión Total - ISR - Cancelación'),
                                        ]),
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('valor_compraventa')
                                                ->label('Valor CompraVenta')
                                                ->prefix('$')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->helperText('Espejo del Valor Convenio'),
                                            TextInput::make('monto_comision_sin_iva')
                                                ->label('Monto Comisión (Sin IVA)')
                                                ->prefix('$')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->helperText('Valor Convenio × % Comisión'),
                                            TextInput::make('comision_total_pagar')
                                                ->label('Comisión Total a Pagar')
                                                ->prefix('$')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->helperText('Valor Convenio × 7.54%'),
                                        ]),
                                ]),
                        ])
                        ->afterValidation(function () {
                            $this->saveStepData(3);
                        }),

                    Step::make('Documentación')
                        ->icon('heroicon-o-document-check')
                        ->schema([
                            CheckboxList::make('documents_required')
                                ->label('Documentos Requeridos')
                                ->options([
                                    'ine' => 'INE o Identificación Oficial',
                                    'curp' => 'CURP',
                                    'rfc' => 'RFC',
                                    'comprobante_ingresos' => 'Comprobante de Ingresos',
                                    'escrituras' => 'Escrituras de la Propiedad',
                                    'avaluo' => 'Avalúo Actualizado',
                                ])
                                ->columns(2)
                                ->gridDirection('row'),
                        ])
                        ->afterValidation(function () {
                            $this->saveStepData(4);
                        }),
                ])
                ->submitAction(Action::make('submit')
                    ->label('Finalizar Convenio')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action('submit'))
                ->nextAction(fn (Action $action) => $action->label('Siguiente'))
                ->previousAction(fn (Action $action) => $action->label('Anterior'))
                ->cancelAction('cancelar', '/admin/wizard')
                // ->cancelAction(fn (Action $action) => $action->label('Cancelar')->url('/admin/wizards'))
                ->persistStepInQueryString()
                ->startOnStep(1)
        ];
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    protected function loadCalculatorDefaults(): void
    {
        // Obtener valores de configuración
        $configValues = ConfigurationCalculator::whereIn('key', [
            'valor_convenio_default',
            'comision_sin_iva_default', 
            'monto_credito_default',
            'isr_default',
            'cancelacion_hipoteca_default',
            'domicilio_convenio_default',
            'comunidad_default',
            'tipo_vivienda_default',
            'prototipo_default'
        ])->pluck('value', 'key');

        // Aplicar valores por defecto si no existen
        $defaults = [
            'valor_convenio' => $configValues['valor_convenio_default'] ?? 1495000,
            'porcentaje_comision_sin_iva' => $configValues['comision_sin_iva_default'] ?? 6.50,
            'monto_credito' => $configValues['monto_credito_default'] ?? 800000,
            'isr' => $configValues['isr_default'] ?? 0,
            'cancelacion_hipoteca' => $configValues['cancelacion_hipoteca_default'] ?? 20000,
            'domicilio_convenio' => $configValues['domicilio_convenio_default'] ?? 'PRIVADA MELQUES 6',
            'comunidad' => $configValues['comunidad_default'] ?? 'REAL SEGOVIA',
            'tipo_vivienda' => $configValues['tipo_vivienda_default'] ?? 'CASA',
            'prototipo' => $configValues['prototipo_default'] ?? 'BURGOS',
        ];

        // Calcular valores derivados según fórmulas exactas
        $valorConvenio = (float) $defaults['valor_convenio'];
        $porcentajeComision = (float) $defaults['porcentaje_comision_sin_iva'];
        
        // FLUJO CORRECTO: Valor Convenio → Precio Promoción (×1.09)
        $defaults['precio_promocion'] = round($valorConvenio * 1.09, 0);
        
        // Monto Comisión (Sin IVA) = Valor Convenio × % Comisión ÷ 100
        $defaults['monto_comision_sin_iva'] = round(($valorConvenio * $porcentajeComision) / 100, 2);
        
        // Comisión Total Pagar = Valor Convenio × 7.54% (fórmula fija del Excel)
        $defaults['comision_total_pagar'] = round(($valorConvenio * 7.54) / 100, 2);
        
        // Valor CompraVenta = Valor Convenio (espejo)
        $defaults['valor_compraventa'] = $valorConvenio;
        
        // Ganancia Final = Valor CompraVenta - Comisión Total - ISR - Cancelación
        $defaults['ganancia_final'] = round(
            $valorConvenio - $defaults['comision_total_pagar'] - $defaults['isr'] - $defaults['cancelacion_hipoteca'], 
            2
        );

        $this->data = array_merge($defaults, $this->data);
    }

    protected function recalculateFinancials(callable $set, callable $get): void
    {
        $valorConvenio = (float) ($get('valor_convenio') ?? 0);
        $porcentajeComision = (float) ($get('porcentaje_comision_sin_iva') ?? 6.50);
        $isr = (float) ($get('isr') ?? 0);
        $cancelacion = (float) ($get('cancelacion_hipoteca') ?? 0);
        
        if ($valorConvenio > 0) {
            // FÓRMULAS EXACTAS SEGÚN MEMORIA:
            
            // 1. Monto Comisión (Sin IVA) = Valor Convenio × % Comisión ÷ 100
            $montoComisionSinIva = round(($valorConvenio * $porcentajeComision) / 100, 2);
            $set('monto_comision_sin_iva', $montoComisionSinIva);
            
            // 2. Comisión Total Pagar = Valor Convenio × 7.54% ÷ 100 (fórmula fija del Excel)
            $comisionTotalPagar = round(($valorConvenio * 7.54) / 100, 2);
            $set('comision_total_pagar', $comisionTotalPagar);
            
            // 3. Valor CompraVenta = Valor Convenio (espejo)
            $set('valor_compraventa', $valorConvenio);
            
            // 4. Ganancia Final = Valor CompraVenta - Comisión Total - ISR - Cancelación Hipoteca
            $gananciaFinal = round($valorConvenio - $comisionTotalPagar - $isr - $cancelacion, 2);
            $set('ganancia_final', $gananciaFinal);
            
            // NOTA: NO recalculamos precio_promocion aquí para evitar bucles infinitos
            // El precio promoción solo se calcula desde el campo de entrada principal
        }
    }

    public function saveStepData(int $step): void
    {
        if ($this->agreementId) {
            Agreement::find($this->agreementId)->update([
                'current_step' => $step,
                'wizard_data' => $this->data,
                'completion_percentage' => ($step / 4) * 100,
            ]);
        }
    }

    public function searchClient(): void
    {
        $searchTerm = $this->data['search_term'] ?? '';
        
        if (!empty($searchTerm)) {
            // Buscar clientes que coincidan
            $clients = Client::where('name', 'like', "%{$searchTerm}%")
                ->orWhere('email', 'like', "%{$searchTerm}%")
                ->orWhere('xante_id', 'like', "%{$searchTerm}%")
                ->limit(10)
                ->get();
            
            if ($clients->count() > 0) {
                // Si encontramos clientes, auto-llenar con el primero
                $firstClient = $clients->first();
                $this->data['client_name'] = $firstClient->name;
                $this->data['client_email'] = $firstClient->email;
                $this->data['client_phone'] = $firstClient->phone ?? '';
                
                Notification::make()
                    ->title('Cliente encontrado')
                    ->body("Se encontró: {$firstClient->name}")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Sin resultados')
                    ->body('No se encontraron clientes. Puede crear uno nuevo en el siguiente paso.')
                    ->warning()
                    ->send();
            }
        }
    }

    public function submit(): void
    {
        if ($this->agreementId) {
            Agreement::find($this->agreementId)->update([
                'status' => 'expediente_completo',
                'completion_percentage' => 100,
                'completed_at' => now(),
                'wizard_data' => $this->data,
            ]);
        }
        
        Notification::make()
            ->title('Convenio creado exitosamente')
            ->body('El convenio ha sido completado y guardado.')
            ->success()
            ->send();
        
        $this->redirect('/admin/wizard');
    }
}
