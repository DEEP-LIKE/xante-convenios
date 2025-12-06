<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use App\Models\Agreement;
use App\Models\Client;
use Filament\Schemas\Components\Wizard;
use App\Services\AgreementCalculatorService;
use App\Services\WizardStateManager;
use App\Services\ProposalPreloadService;
use App\Services\ClientSearchService;
use App\Services\WizardSummaryRenderer;
use App\Actions\Agreements\SaveWizardStepAction;
use App\Actions\Agreements\UpdateClientFromWizardAction;
use App\Actions\Agreements\PreloadClientDataAction;
use App\Actions\Agreements\GenerateAgreementDocumentsAction;
use BackedEnum;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Support\Enums\FontWeight;

class CreateAgreementWizard extends Page implements HasForms, HasInfolists
{
    use InteractsWithForms;
    use InteractsWithInfolists;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-plus';
    protected static ?string $title = 'Nuevo Convenio';
    protected static ?string $navigationLabel = 'Crear Convenio';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'convenios/crear';

    public string $view = 'filament.pages.create-agreement-wizard';

    public ?array $data = [];
    public ?int $agreementId = null;
    public int $currentStep = 1;
    public int $totalSteps = 5;

    protected $listeners = [
        'stepChanged' => 'handleStepChange',
    ];

    // ... (rest of the class)

    /**
     * Define el Infolist para el resumen del convenio
     */
    public function agreementInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->state($this->data)
            ->schema([
                Section::make('Datos del Titular')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('holder_name')
                                    ->label('Nombre Completo')
                                    ->weight(FontWeight::Bold),
                                TextEntry::make('holder_email')
                                    ->label('Email')
                                    ->icon('heroicon-m-envelope'),
                                TextEntry::make('holder_phone')
                                    ->label('Teléfono Móvil')
                                    ->icon('heroicon-m-phone'),
                                TextEntry::make('holder_office_phone')
                                    ->label('Tel. Oficina'),
                                TextEntry::make('holder_curp')
                                    ->label('CURP'),
                                TextEntry::make('holder_rfc')
                                    ->label('RFC'),
                                TextEntry::make('holder_civil_status')
                                    ->label('Estado Civil'),
                                TextEntry::make('holder_occupation')
                                    ->label('Ocupación'),
                            ]),
                        Section::make('Domicilio Actual')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('current_address_full')
                                            ->label('Calle y Número')
                                            ->state(fn ($record) => ($this->data['current_address'] ?? '') . ' ' . (!empty($this->data['holder_house_number']) ? '#' . $this->data['holder_house_number'] : '')),
                                        TextEntry::make('neighborhood')
                                            ->label('Colonia'),
                                        TextEntry::make('postal_code')
                                            ->label('Código Postal'),
                                        TextEntry::make('location_full')
                                            ->label('Municipio / Estado')
                                            ->state(fn ($record) => collect([$this->data['municipality'] ?? null, $this->data['state'] ?? null])->filter()->join(', ')),
                                    ])
                            ])
                            ->visible(fn () => !empty($this->data['current_address']))
                    ])
                    ->collapsible(),

                Section::make('Cónyuge / Coacreditado')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('spouse_name')
                                    ->label('Nombre Completo')
                                    ->weight(FontWeight::Bold),
                                TextEntry::make('spouse_email')
                                    ->label('Email')
                                    ->icon('heroicon-m-envelope'),
                                TextEntry::make('spouse_phone')
                                    ->label('Teléfono Móvil')
                                    ->icon('heroicon-m-phone'),
                                TextEntry::make('spouse_curp')
                                    ->label('CURP'),
                            ]),
                        Section::make('Domicilio')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('spouse_address_full')
                                            ->label('Calle y Número')
                                            ->state(fn ($record) => ($this->data['spouse_current_address'] ?? '') . ' ' . (!empty($this->data['spouse_house_number']) ? '#' . $this->data['spouse_house_number'] : '')),
                                        TextEntry::make('spouse_neighborhood')
                                            ->label('Colonia'),
                                        TextEntry::make('spouse_postal_code')
                                            ->label('Código Postal'),
                                        TextEntry::make('spouse_location_full')
                                            ->label('Municipio / Estado')
                                            ->state(fn ($record) => collect([$this->data['spouse_municipality'] ?? null, $this->data['spouse_state'] ?? null])->filter()->join(', ')),
                                    ])
                            ])
                            ->visible(fn () => !empty($this->data['spouse_current_address']))
                    ])
                    ->visible(fn () => !empty($this->data['spouse_name']))
                    ->collapsible(),

                Section::make('Propiedad del Convenio')
                    ->icon('heroicon-o-home-modern')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('domicilio_convenio')
                                    ->label('Domicilio Vivienda'),
                                TextEntry::make('comunidad')
                                    ->label('Comunidad'),
                                TextEntry::make('tipo_vivienda')
                                    ->label('Tipo de Vivienda'),
                                TextEntry::make('prototipo')
                                    ->label('Prototipo'),
                                TextEntry::make('lote')
                                    ->label('Lote'),
                                TextEntry::make('manzana')
                                    ->label('Manzana'),
                                TextEntry::make('etapa')
                                    ->label('Etapa'),
                                TextEntry::make('municipio_propiedad')
                                    ->label('Municipio'),
                                TextEntry::make('estado_propiedad')
                                    ->label('Estado'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Resumen Financiero')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('valor_convenio')
                                    ->label('Valor Convenio')
                                    ->money('MXN')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold),
                                TextEntry::make('precio_promocion')
                                    ->label('Precio Promoción')
                                    ->money('MXN'),
                                TextEntry::make('comision_total_pagar')
                                    ->label('Comisión Total')
                                    ->money('MXN'),
                                TextEntry::make('ganancia_final')
                                    ->label('Ganancia Final Est.')
                                    ->money('MXN')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Black)
                                    ->color('success'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }


    // ========================================
    // DEPENDENCY INJECTION
    // ========================================

    protected AgreementCalculatorService $calculatorService;
    protected WizardStateManager $stateManager;
    protected ProposalPreloadService $proposalService;
    protected ClientSearchService $clientSearch;
    protected WizardSummaryRenderer $renderer;

    /**
     * Método boot para inyección de dependencias
     */
    public function boot(
        AgreementCalculatorService $calculatorService,
        WizardStateManager $stateManager,
        ProposalPreloadService $proposalService,
        ClientSearchService $clientSearch,
        WizardSummaryRenderer $renderer
    ): void {
        $this->calculatorService = $calculatorService;
        $this->stateManager = $stateManager;
        $this->proposalService = $proposalService;
        $this->clientSearch = $clientSearch;
        $this->renderer = $renderer;
    }

    // ========================================
    // LIFECYCLE METHODS
    // ========================================

    /**
     * Inicializa el wizard
     */
    public function mount(?int $agreement = null): void
    {
        // Delegar inicialización al WizardStateManager
        $state = $this->stateManager->initializeWizard(
            $agreement ?? request()->get('agreement'),
            request()->get('client_id')
        );

        $this->agreementId = $state->agreementId;
        $this->currentStep = $state->currentStep;
        $this->data = $state->data;

        // Cargar defaults de calculadora
        $this->loadCalculatorDefaults();
        
        // Redirigir según estado del acuerdo
        if ($this->agreementId) {
            $agreement = Agreement::find($this->agreementId);
            if ($agreement) {
                // Si la validación está aprobada, ir al Paso 5 (Validación) para que vean el estado
                // y puedan dar click en "Siguiente/Continuar"
                if ($agreement->validation_status === 'approved' || $agreement->can_generate_documents) {
                   $this->currentStep = 5; 
                } 
                // Si la validación está pendiente, ir a Validación (Paso 5)
                elseif ($agreement->validation_status === 'pending') {
                    $this->currentStep = 5;
                }
            }
        }

        // Precargar propuesta si estamos en paso 4 o superior
        if ($state->isInCalculatorStep() && $this->data['client_id'] ?? null) {
            $this->preloadProposalDataIfExists();
        }

        $this->form->fill($this->data);
    }

    // ========================================
    // FORM SCHEMA
    // ========================================

    protected function getFormSchema(): array
    {
        return [
            Wizard::make([
                \App\Filament\Schemas\CreateAgreement\StepOneSchema::make($this),
                \App\Filament\Schemas\CreateAgreement\StepTwoSchema::make($this),
                \App\Filament\Schemas\CreateAgreement\StepThreeSchema::make($this),
                \App\Filament\Schemas\CreateAgreement\StepFourSchema::make($this),
                \App\Filament\Schemas\CreateAgreement\StepFiveSchema::make($this),
            ])
            ->submitAction(
                Action::make('submit')
                    ->action('generateDocumentsAndProceed')
                    ->label('Continuar y Generar Documentos')
                    ->icon('heroicon-o-document-plus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar Envío')
                    ->modalDescription('¿Estás seguro de que desea finalizar y generar los documentos?')
                    ->modalSubmitActionLabel('Sí, Confirmar')
                    ->disabled(function () {
                        $agreementId = request()->get('agreement');
                        if (!$agreementId) {
                            return true; // Deshabilitar si no hay agreement
                        }
                        
                        $agreement = \App\Models\Agreement::find($agreementId);
                        if (!$agreement) {
                            return true; // Deshabilitar si no se encuentra el agreement
                        }
                        
                        // Solo habilitar si está aprobado
                        return $agreement->validation_status !== 'approved';
                    })
            )
            ->nextAction(fn (Action $action) => $action->label('Siguiente'))
            ->previousAction(fn (Action $action) => $action->label('Anterior'))
            ->persistStepInQueryString()
            ->startOnStep($this->currentStep)
            ->skippable(false)
        ];
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    // ========================================
    // CALCULATOR METHODS
    // ========================================

    /**
     * Carga los valores por defecto de la calculadora
     */
    protected function loadCalculatorDefaults(): void
    {
        $defaults = $this->calculatorService->getDefaultConfiguration();
        $this->data = array_merge($defaults, $this->data);
    }

    /**
     * Recalcula todos los valores financieros
     */
    public function recalculateAllFinancials(callable $set, callable $get): void
    {
        $valorConvenio = (float) ($get('valor_convenio') ?? 0);
        
        if ($valorConvenio <= 0) {
            $this->clearCalculatedFields($set);
            return;
        }

        $parameters = [
            'porcentaje_comision_sin_iva' => (float) ($get('porcentaje_comision_sin_iva') ?? 6.50),
            'iva_percentage' => (float) (\App\Models\ConfigurationCalculator::where('key', 'comision_iva_incluido_default')->value('value') ?? 16.00),
            'precio_promocion_multiplicador' => (float) (1 + (($get('state_commission_percentage') ?? 9) / 100)),
            'isr' => (float) ($get('isr') ?? 0),
            'cancelacion_hipoteca' => (float) ($get('cancelacion_hipoteca') ?? 20000),
            'monto_credito' => (float) ($get('monto_credito') ?? 800000),
        ];

        $calculations = $this->calculatorService->calculateAllFinancials($valorConvenio, $parameters);
        $formattedValues = $this->calculatorService->formatCalculationsForUI($calculations);

        foreach ($formattedValues as $field => $value) {
            $set($field, $value);
        }
    }

    /**
     * Limpia todos los campos calculados
     */
    public function clearCalculatedFields(callable $set): void
    {
        $set('precio_promocion', '');
        $set('valor_compraventa', '');
        $set('monto_comision_sin_iva', '');
        $set('comision_total_pagar', '');
        $set('total_gastos_fi_venta', '');
        $set('ganancia_final', '');
    }

    // ========================================
    // WIZARD STEP MANAGEMENT
    // ========================================

    /**
     * Guarda los datos del paso actual
     */
    public function saveStepData(int $step): void
    {
        try {
            $formData = $this->form->getRawState();
        } catch (\Exception $e) {
            $formData = $this->data ?? [];
        }

        $this->data = array_merge($this->data ?? [], $formData);
        $this->currentStep = $step;

        $action = new SaveWizardStepAction();
        $result = $action->execute(
            $this->agreementId,
            $step,
            $this->data,
            fn($clientId) => $this->updateClientData($clientId)
        );

        // Capturar el agreementId si se creó uno nuevo
        if ($result['success'] && $result['agreementId']) {
            $this->agreementId = $result['agreementId'];
            
            // Actualizar query string si es necesario
            if (!request()->has('agreement')) {
                $this->dispatch('update-query-string', ['agreement' => $this->agreementId]);
            }
        }
    }

    /**
     * Actualiza los datos del cliente
     */
    public function updateClientData(int $clientId): void
    {
        $action = new UpdateClientFromWizardAction();
        $action->execute($clientId, $this->data);
    }

    /**
     * Maneja el cambio de paso
     */
    public function handleStepChange($step): void
    {
        if ($step >= 1) {
            $this->saveStepData($step);
        }
    }

    /**
     * Se ejecuta cuando se actualiza cualquier propiedad
     */
    public function updated($propertyName): void
    {
        if ($propertyName === 'currentStep' && $this->currentStep >= 1) {
            $this->saveStepData($this->currentStep);
        }
    }

    // ========================================
    // CLIENT MANAGEMENT
    // ========================================

    /**
     * Precarga los datos de un cliente
     */
    public function preloadClientData(int $clientId, callable $set): void
    {
        $action = new PreloadClientDataAction();
        $action->execute($clientId, $set);
    }

    // ========================================
    // PROPOSAL MANAGEMENT
    // ========================================

    /**
     * Verifica si el cliente actual tiene un pre-cálculo previo
     */
    public function hasExistingProposal(): ?array
    {
        $clientId = $this->data['client_id'] ?? null;
        
        if (!$clientId) {
            return null;
        }

        return $this->proposalService->hasExistingProposal($clientId);
    }

    /**
     * Precarga datos de propuesta existente si existe
     */
    protected function preloadProposalDataIfExists(): void
    {
        $clientId = $this->data['client_id'] ?? null;
        
        if (!$clientId) {
            return;
        }

        $this->data = $this->proposalService->preloadIfExists($clientId, $this->data);
    }

    // ========================================
    // SUMMARY RENDERING (Delegated to Service)
    // ========================================

    /**
     * Renderiza el resumen del titular
     */
    public function renderHolderSummary(array $data): string
    {
        return $this->renderer->renderHolderSummary($data);
    }

    /**
     * Renderiza el resumen del cónyuge
     */
    public function renderSpouseSummary(array $data): string
    {
        return $this->renderer->renderSpouseSummary($data);
    }

    /**
     * Renderiza el resumen de la propiedad
     */
    public function renderPropertySummary(array $data): string
    {
        return $this->renderer->renderPropertySummary($data);
    }

    /**
     * Renderiza el resumen financiero
     */
    public function renderFinancialSummary(array $data): string
    {
        return $this->renderer->renderFinancialSummary($data);
    }

    // ========================================
    // DOCUMENT GENERATION
    // ========================================

    /**
     * Genera los documentos y procede al siguiente wizard
     */
    public function generateDocumentsAndProceed(): void
    {
        try {
            $formData = $this->form->getState();
            $this->data = array_merge($this->data ?? [], $formData);
        } catch (\Exception $e) {
            // Continuar con datos actuales
        }

        // 1. Mostrar Loading
        $this->dispatch('showLoading', message: 'Sincronizando información con HubSpot...');

        // 2. Sincronizar con HubSpot (Push)
        $agreement = Agreement::find($this->agreementId);
        if ($agreement && $agreement->client) {
            $syncAction = app(\App\Actions\Agreements\SyncClientToHubspotAction::class);
            $syncErrors = $syncAction->execute($agreement, $this->data);
            
            // Si hay errores críticos en la sincronización, DETENER el proceso
            if (!empty($syncErrors) && isset($syncErrors['error'])) {
                $this->dispatch('hideLoading');
                
                Notification::make()
                    ->title('Error de Sincronización con HubSpot')
                    ->body('No se pudo sincronizar la información con HubSpot. Por favor, intenta nuevamente en unos momentos. Tus datos han sido guardados.')
                    ->danger()
                    ->persistent()
                    ->send();
                
                \Illuminate\Support\Facades\Log::error('Sincronización HubSpot falló - Proceso detenido', [
                    'agreement_id' => $this->agreementId,
                    'errors' => $syncErrors
                ]);
                
                return; // DETENER aquí - No generar PDFs ni avanzar
            }
            
            // Si solo hay warnings (campos no sincronizables), continuar
            if (!empty($syncErrors)) {
                \Illuminate\Support\Facades\Log::info('Advertencias en sincronización HubSpot', [
                    'agreement_id' => $this->agreementId,
                    'warnings' => $syncErrors
                ]);
            }
        }

        // 3. Actualizar mensaje de loading
        $this->dispatch('updateLoadingMessage', message: 'Generando documentos PDF...');

        // 4. Generar Documentos
        $action = app(GenerateAgreementDocumentsAction::class);
        $redirectUrl = $action->execute(
            $this->agreementId,
            $this->data,
            $this->data['confirm_data_correct'] ?? false
        );

        if ($redirectUrl) {
            $this->stateManager->clearSession();
            $this->redirect($redirectUrl);
        } else {
            // Si no hay redirección (error), ocultar loading
            $this->dispatch('hideLoading');
        }
    }

    /**
     * Método legacy para compatibilidad
     */
    public function submit(): void
    {
        $this->generateDocumentsAndProceed();
    }
}
