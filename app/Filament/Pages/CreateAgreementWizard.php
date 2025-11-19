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

class CreateAgreementWizard extends Page implements HasForms
{
    use InteractsWithForms;

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
                    ->label('Validar y Generar Documentos')
                    ->icon('heroicon-o-document-plus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar Envío')
                    ->modalDescription('¿Estás seguro de que desea finalizar y generar los documentos?')
                    ->modalSubmitActionLabel('Sí, Confirmar')
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
            'porcentaje_comision_iva_incluido' => (float) ($get('porcentaje_comision_iva_incluido') ?? 7.54),
            'precio_promocion_multiplicador' => (float) ($get('precio_promocion_multiplicador') ?? 1.09),
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
        $success = $action->execute(
            $this->agreementId,
            $step,
            $this->data,
            fn($clientId) => $this->updateClientData($clientId)
        );

        if ($success && !request()->has('agreement')) {
            $this->dispatch('update-query-string', ['agreement' => $this->agreementId]);
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

        $action = app(GenerateAgreementDocumentsAction::class);
        $redirectUrl = $action->execute(
            $this->agreementId,
            $this->data,
            $this->data['confirm_data_correct'] ?? false
        );

        if ($redirectUrl) {
            $this->stateManager->clearSession();
            $this->redirect($redirectUrl);
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
