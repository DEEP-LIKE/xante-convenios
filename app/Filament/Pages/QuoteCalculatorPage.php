<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use App\Models\Client;
use App\Models\Proposal;
use App\Services\AgreementCalculatorService;
use Illuminate\Support\Facades\Auth;
use BackedEnum;

class QuoteCalculatorPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Propuesta / Calculadora';
    protected static ?string $title = 'Calculadora de Cotizaciones';
    protected static ?string $slug = 'quote-calculator';
    protected static ?int $navigationSort = 3;
    protected static bool $shouldRegisterNavigation = true;

    public string $view = 'filament.pages.quote-calculator';

    public ?array $data = [];
    public ?int $selectedClientId = null;
    public ?string $selectedClientIdxante = null;
    public bool $showResults = false;
    public array $calculationResults = [];

    protected AgreementCalculatorService $calculatorService;

    public function boot(AgreementCalculatorService $calculatorService): void
    {
        $this->calculatorService = $calculatorService;
    }

    public function mount(): void
    {
        // Cargar valores por defecto de configuración
        $defaults = $this->calculatorService->getDefaultConfiguration();
        $this->data = $defaults;
        
        // Llenar el formulario con los valores por defecto
        $this->form->fill($this->data);
    }

    protected function getFormSchema(): array
    {
        return [
            // Selector de Cliente (Opcional)
            Section::make('🎯 SELECCIÓN DE CLIENTE')
                ->description('Opcional: Seleccione un cliente para enlazar la cotización')
                ->schema([
                    Select::make('client_id')
                        ->label('Seleccionar cliente (opcional)')
                        ->placeholder('Busque por nombre o ID Xante...')
                        ->options(function () {
                            return Client::query()
                                ->selectRaw("id, CONCAT(name, ' — ', xante_id) as display_name")
                                ->pluck('display_name', 'id')
                                ->toArray();
                        })
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state) {
                            if ($state) {
                                $client = Client::find($state);
                                if ($client) {
                                    $this->selectedClientId = $client->id;
                                    $this->selectedClientIdxante = $client->xante_id;
                                    
                                    // Intentar precargar propuesta existente
                                    $this->tryLoadExistingProposal($client->xante_id);
                                    
                                    Notification::make()
                                        ->title('Cliente seleccionado')
                                        ->body("Cliente: {$client->name} (ID: {$client->xante_id})")
                                        ->success()
                                        ->send();
                                } else {
                                    $this->selectedClientId = null;
                                    $this->selectedClientIdxante = null;
                                }
                            } else {
                                $this->selectedClientId = null;
                                $this->selectedClientIdxante = null;
                            }
                        })
                        ->suffixAction(
                            Action::make('clear_client')
                                ->icon('heroicon-o-x-mark')
                                ->color('gray')
                                ->action(function () {
                                    $this->selectedClientId = null;
                                    $this->selectedClientIdxante = null;
                                    $this->data['client_id'] = null;
                                    $this->form->fill($this->data);
                                    
                                    Notification::make()
                                        ->title('Cliente deseleccionado')
                                        ->body('Ahora está en modo calculadora rápida')
                                        ->info()
                                        ->send();
                                })
                        ),
                ])
                ->collapsible()
                ->collapsed(false),

            // Campo Principal: Valor Convenio
            Section::make('💰 VALOR PRINCIPAL DEL CONVENIO')
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
                                ->afterStateUpdated(function ($state) {
                                    if ($state && $state > 0) {
                                        $this->recalculateAllFinancials();
                                    } else {
                                        $this->clearCalculatedFields();
                                    }
                                })
                                ->helperText('Ingrese el valor del convenio para activar todos los cálculos automáticos')
                                ->extraAttributes(['class' => 'text-lg font-semibold'])
                                ->inputMode('numeric'),
                        ]),
                ])
                ->collapsible(),

            // Configuración de Parámetros
            Section::make('⚙️ PARÁMETROS DE CÁLCULO')
                ->description('Configuración de porcentajes y valores base')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('porcentaje_comision_sin_iva')
                                ->label('% Comisión (Sin IVA)')
                                ->numeric()
                                ->suffix('%')
                                ->step(0.01)
                                ->disabled()
                                ->dehydrated(false)
                                ->extraAttributes(['class' => 'bg-gray-50'])
                                ->helperText('Valor fijo desde configuración'),
                            TextInput::make('porcentaje_comision_iva_incluido')
                                ->label('% Comisión IVA Incluido')
                                ->numeric()
                                ->suffix('%')
                                ->step(0.01)
                                ->disabled()
                                ->dehydrated(false)
                                ->extraAttributes(['class' => 'bg-gray-50'])
                                ->helperText('Valor fijo desde configuración'),
                            TextInput::make('precio_promocion_multiplicador')
                                ->label('Multiplicador Precio Promoción')
                                ->numeric()
                                ->step(0.01)
                                ->disabled()
                                ->dehydrated(false)
                                ->extraAttributes(['class' => 'bg-gray-50'])
                                ->helperText('Valor fijo desde configuración'),
                            TextInput::make('monto_credito')
                                ->label('Monto de Crédito')
                                ->numeric()
                                ->prefix('$')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state) {
                                    if ($this->data['valor_convenio'] ?? false) {
                                        $this->recalculateAllFinancials();
                                    }
                                })
                                ->helperText('Valor editable - precargado desde configuración')
                                ->inputMode('numeric'),
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
            Section::make('📊 VALORES CALCULADOS')
                ->description('Estos valores se calculan automáticamente al ingresar el Valor Convenio')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('precio_promocion')
                                ->label('Precio Promoción')
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(false)
                                ->extraAttributes(['class' => 'bg-blue-50 text-blue-800 font-semibold'])
                                ->helperText('Valor Convenio × Multiplicador Precio Promoción'),
                            TextInput::make('valor_compraventa')
                                ->label('Valor CompraVenta')
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(false)
                                ->extraAttributes(['class' => 'bg-blue-50 text-blue-800 font-semibold'])
                                ->helperText('Espejo del Valor Convenio'),
                            TextInput::make('monto_comision_sin_iva')
                                ->label('Monto Comisión (Sin IVA)')
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(false)
                                ->extraAttributes(['class' => 'bg-yellow-50 text-yellow-800 font-semibold'])
                                ->helperText('Valor Convenio × % Comisión'),
                            TextInput::make('comision_total_pagar')
                                ->label('Comisión Total a Pagar')
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(false)
                                ->extraAttributes(['class' => 'bg-yellow-50 text-yellow-800 font-semibold'])
                                ->helperText('Valor Convenio × % Comisión IVA Incluido'),
                        ]),
                ])
                ->collapsible(),

            // Costos de Operación
            Section::make('💸 COSTOS DE OPERACIÓN')
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
                                ->afterStateUpdated(function ($state) {
                                    if ($this->data['valor_convenio'] ?? false) {
                                        $this->recalculateAllFinancials();
                                    }
                                })
                                ->inputMode('numeric'),
                            TextInput::make('cancelacion_hipoteca')
                                ->label('Cancelación de Hipoteca')
                                ->numeric()
                                ->prefix('$')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state) {
                                    if ($this->data['valor_convenio'] ?? false) {
                                        $this->recalculateAllFinancials();
                                    }
                                })
                                ->inputMode('numeric'),
                            TextInput::make('total_gastos_fi_venta')
                                ->label('Total Gastos FI (Venta)')
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(false)
                                ->extraAttributes(['class' => 'bg-yellow-50 text-yellow-800 font-semibold'])
                                ->helperText('ISR + Cancelación de Hipoteca'),
                            TextInput::make('ganancia_final')
                                ->label('Ganancia Final (Est.)')
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(false)
                                ->extraAttributes(['class' => 'bg-green-50 text-green-800 font-bold text-lg'])
                                ->helperText('Valor CompraVenta - ISR - Cancelación - Comisión Total - Monto Crédito'),
                        ]),
                ])
                ->collapsible(),
        ];
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    /**
     * Recalcula todos los valores financieros
     */
    protected function recalculateAllFinancials(): void
    {
        $valorConvenio = (float) ($this->data['valor_convenio'] ?? 0);
        
        if ($valorConvenio <= 0) {
            $this->clearCalculatedFields();
            return;
        }

        // Validar parámetros
        $errors = $this->calculatorService->validateParameters($valorConvenio, $this->data);
        if (!empty($errors)) {
            foreach ($errors as $error) {
                Notification::make()
                    ->title('Error de validación')
                    ->body($error)
                    ->danger()
                    ->send();
            }
            return;
        }

        // Calcular valores
        $calculations = $this->calculatorService->calculateAllFinancials($valorConvenio, $this->data);
        $formattedValues = $this->calculatorService->formatCalculationsForUI($calculations);

        // Actualizar campos calculados
        $this->data = array_merge($this->data, $formattedValues);
        $this->calculationResults = $calculations;
        $this->showResults = true;

        // Actualizar el formulario
        $this->form->fill($this->data);
    }

    /**
     * Limpia todos los campos calculados
     */
    protected function clearCalculatedFields(): void
    {
        $fieldsTolear = [
            'precio_promocion',
            'valor_compraventa',
            'monto_comision_sin_iva',
            'comision_total_pagar',
            'total_gastos_fi_venta',
            'ganancia_final',
        ];

        foreach ($fieldsTolear as $field) {
            $this->data[$field] = '';
        }

        $this->showResults = false;
        $this->calculationResults = [];
        $this->form->fill($this->data);
    }

    /**
     * Intenta cargar una propuesta existente por IDxante
     */
    protected function tryLoadExistingProposal(string $idxante): void
    {
        $proposal = Proposal::where('idxante', $idxante)
            ->where('linked', true)
            ->latest()
            ->first();

        if ($proposal && !empty($proposal->data)) {
            $this->data = array_merge($this->data, $proposal->data);
            $this->form->fill($this->data);
            
            // Si hay valor convenio, recalcular
            if (!empty($this->data['valor_convenio'])) {
                $this->recalculateAllFinancials();
            }

            Notification::make()
                ->title('Propuesta precargada')
                ->body('Se encontró una propuesta anterior para este cliente y se ha precargado.')
                ->info()
                ->duration(5000)
                ->send();
        }
    }

    /**
     * Enlaza la propuesta con el cliente seleccionado
     */
    public function linkProposal(): void
    {
        if (!$this->selectedClientId || !$this->selectedClientIdxante) {
            Notification::make()
                ->title('Error')
                ->body('Debe seleccionar un cliente para enlazar la propuesta.')
                ->danger()
                ->send();
            return;
        }

        if (empty($this->data['valor_convenio']) || $this->data['valor_convenio'] <= 0) {
            Notification::make()
                ->title('Error')
                ->body('Debe ingresar un valor de convenio válido antes de enlazar.')
                ->danger()
                ->send();
            return;
        }

        try {
            // Obtener datos actuales del formulario
            $formData = $this->form->getState();
            $this->data = array_merge($this->data, $formData);

            // Crear o actualizar propuesta
            $proposal = Proposal::updateOrCreate(
                [
                    'idxante' => $this->selectedClientIdxante,
                    'linked' => true,
                ],
                [
                    'client_id' => $this->selectedClientId,
                    'data' => $this->data,
                    'created_by' => Auth::id(),
                ]
            );

            Notification::make()
                ->title('✅ Propuesta Enlazada')
                ->body("La propuesta ha sido enlazada exitosamente al cliente {$this->selectedClientIdxante}")
                ->success()
                ->duration(5000)
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al enlazar')
                ->body('Ocurrió un error al guardar la propuesta: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Realiza un cálculo rápido sin guardar
     */
    public function quickCalculate(): void
    {
        if (empty($this->data['valor_convenio']) || $this->data['valor_convenio'] <= 0) {
            Notification::make()
                ->title('Error')
                ->body('Debe ingresar un valor de convenio válido para calcular.')
                ->danger()
                ->send();
            return;
        }

        // Los cálculos ya se realizaron automáticamente
        // Solo mostrar notificación de confirmación
        $valorConvenio = (float) $this->data['valor_convenio'];
        $gananciaFinal = !empty($this->calculationResults['ganancia_final']) ? $this->calculationResults['ganancia_final'] : 0;

        Notification::make()
            ->title('🧮 Cálculo Realizado')
            ->body("Valor: $" . number_format($valorConvenio, 2) . " | Ganancia: $" . number_format($gananciaFinal, 2))
            ->success()
            ->duration(5000)
            ->send();
    }

    /**
     * Limpia todos los campos del formulario
     */
    public function resetForm(): void
    {
        $defaults = $this->calculatorService->getDefaultConfiguration();
        $this->data = $defaults;
        $this->selectedClientId = null;
        $this->selectedClientIdxante = null;
        $this->showResults = false;
        $this->calculationResults = [];
        
        $this->form->fill($this->data);

        Notification::make()
            ->title('Formulario reiniciado')
            ->body('Todos los campos han sido limpiados y restaurados a valores por defecto.')
            ->info()
            ->send();
    }

    /**
     * Obtiene las acciones de la página
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('link_proposal')
                ->label('Enlazar Valor Propuesta')
                ->icon('heroicon-o-link')
                ->color('primary')
                ->visible(fn () => $this->selectedClientId !== null)
                ->requiresConfirmation()
                ->modalHeading('Enlazar Propuesta')
                ->modalDescription('¿Está seguro de que desea enlazar esta propuesta al cliente seleccionado?')
                ->action('linkProposal'),

            Action::make('quick_calculate')
                ->label('Calcular (Rápido)')
                ->icon('heroicon-o-calculator')
                ->color('secondary')
                ->visible(fn () => $this->selectedClientId === null)
                ->action('quickCalculate'),

            Action::make('reset_form')
                ->label('Limpiar Campos')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Limpiar Formulario')
                ->modalDescription('¿Está seguro de que desea limpiar todos los campos?')
                ->action('resetForm'),
        ];
    }
}
