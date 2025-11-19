<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use App\Models\Agreement;
use App\Services\DocumentStateManager;
use App\Services\DocumentUploadService;
use App\Services\DocumentEmailService;
use App\Services\DocumentFileManager;
use App\Services\DocumentRenderer;
use App\Services\DocumentComparisonService;
use App\Services\PdfGenerationService;
use App\Actions\Agreements\SendDocumentsAction;
use App\Actions\Agreements\GenerateDocumentsZipAction;
use App\Actions\Agreements\MarkAgreementCompletedAction;
use App\Actions\Agreements\SaveDocumentStepAction;
use BackedEnum;

class ManageDocuments extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected $listeners = ['stepChanged' => 'handleStepChange'];

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationLabel = 'Gestión de Documentos';
    protected static ?string $title = 'Gestión de Documentos del Convenio';
    protected static ?string $slug = 'manage-documents/{agreement?}';
    protected static ?int $navigationSort = 5;
    protected static bool $shouldRegisterNavigation = false;

    public string $view = 'filament.pages.manage-documents';

    public ?Agreement $agreement = null;
    public array $data = [];
    public ?float $proposal_value = null;

    // Propiedades para FileUpload con ->live()
    public array $holder_ine = [];
    public array $holder_curp = [];
    public array $holder_fiscal_status = [];
    public array $holder_proof_address_home = [];
    public array $holder_proof_address_titular = [];
    public array $holder_birth_certificate = [];
    public array $holder_marriage_certificate = [];
    public array $holder_bank_statement = [];
    public array $property_notarial_instrument = [];
    public array $property_tax_receipt = [];
    public array $property_water_receipt = [];
    public array $property_cfe_receipt = [];
    
    public int $currentStep = 1;
    public int $totalSteps = 3;
    public array $processingDeletions = [];
    public bool $isDeletingDocuments = false;
    public bool $documents_validated = false;

    // ========================================
    // DEPENDENCY INJECTION
    // ========================================

    protected DocumentStateManager $stateManager;
    protected DocumentUploadService $uploadService;
    protected DocumentEmailService $emailService;
    protected DocumentFileManager $fileManager;
    protected DocumentRenderer $renderer;
    protected DocumentComparisonService $comparisonService;

    /**
     * Método boot para inyección de dependencias
     */
    public function boot(
        DocumentStateManager $stateManager,
        DocumentUploadService $uploadService,
        DocumentEmailService $emailService,
        DocumentFileManager $fileManager,
        DocumentRenderer $renderer,
        DocumentComparisonService $comparisonService
    ): void {
        $this->stateManager = $stateManager;
        $this->uploadService = $uploadService;
        $this->emailService = $emailService;
        $this->fileManager = $fileManager;
        $this->renderer = $renderer;
        $this->comparisonService = $comparisonService;
    }

    // ========================================
    // LIFECYCLE METHODS
    // ========================================

    public function mount(): void
    {
        // Determinar paso actual
        $this->currentStep = $this->stateManager->determineCurrentStep($this->agreement);

        // Verificar si hay paso específico en URL
        $urlStep = $this->getCurrentUrlStep();
        if ($urlStep && $urlStep !== $this->currentStep) {
            $this->currentStep = $urlStep;
            if ($this->currentStep === 3 && $this->agreement->status !== 'completed') {
                $this->stateManager->markAsCompleted($this->agreement);
            }
        } elseif ($this->stateManager->shouldMarkAsCompleted($this->agreement, $this->currentStep)) {
            $this->stateManager->markAsCompleted($this->agreement);
        }

        // Refrescar relaciones
        $this->agreement->refresh();
        $this->agreement->load(['generatedDocuments', 'clientDocuments']);

        // Limpiar archivos huérfanos
        $this->fileManager->cleanOrphanFiles($this->agreement);

        // Cargar documentos existentes
        $existingDocuments = $this->uploadService->loadDocuments($this->agreement);
        
        if ($this->agreement->proposal_value) {
            $existingDocuments['proposal_value'] = $this->agreement->proposal_value;
            $this->proposal_value = $this->agreement->proposal_value;
        }
        
        if (!empty($existingDocuments)) {
            $this->form->fill($existingDocuments);
            
            Notification::make()
                ->title('Documentos Cargados')
                ->body("Se han recuperado " . count($existingDocuments) . " documentos previamente subidos")
                ->success()
                ->duration(4000)
                ->send();
        }
        
        $this->documents_validated = $this->agreement->status === 'completed';
        $this->stateManager->showStatusNotification($this->agreement);
    }

    private function getCurrentUrlStep(): ?int
    {
        $queryParams = request()->query();

        if (isset($queryParams['step'])) {
            $stepParam = $queryParams['step'];

            if (preg_match('/wizard-step$/', $stepParam)) {
                return 3;
            }
            if (preg_match('/envio-de-documentos/', $stepParam)) {
                return 1;
            }
            if (preg_match('/recepcion-de-documentos/', $stepParam)) {
                return 2;
            }
        }

        return null;
    }

    // ========================================
    // FORM SCHEMA
    // ========================================

    protected function getFormSchema(): array
    {
        if (class_exists(Wizard::class)) {
            return [
                Wizard::make([
                    Step::make('Envío de Documentos')
                        ->description('Enviar documentos al cliente por correo electrónico')
                        ->icon('heroicon-o-paper-airplane')
                        ->completedIcon('heroicon-o-check-circle')
                        ->schema($this->getStepOneSchema())
                        ->afterValidation(function () {
                            if ($this->agreement->status !== 'documents_sent' && !$this->agreement->documents_sent_at) {
                                $this->sendDocumentsToClient(app(SendDocumentsAction::class));
                                
                                Notification::make()
                                    ->title('✅ Documentos Enviados')
                                    ->body('Los documentos han sido enviados exitosamente al cliente y al asesor.')
                                    ->success()
                                    ->duration(5000)
                                    ->send();
                            }
                            $this->saveStepData(1);
                        }),

                    Step::make('Recepción de Documentos')
                        ->description('Recibir y validar documentos del cliente')
                        ->icon('heroicon-o-document-arrow-up')
                        ->completedIcon('heroicon-o-check-circle')
                        ->schema($this->getStepTwoSchema())
                        ->afterValidation(function () {
                            if (!$this->agreement->documents_received_at) {
                                $this->agreement->update([
                                    'status' => 'completed',
                                    'documents_received_at' => now(),
                                    'completion_percentage' => 100,
                                ]);
                                
                                $this->emailService->sendDocumentsReceivedConfirmation($this->agreement);
                                
                                Notification::make()
                                    ->title('🎉 Convenio Completado')
                                    ->body('El convenio ha sido marcado como exitoso y se ha enviado la confirmación por correo.')
                                    ->success()
                                    ->duration(5000)
                                    ->send();
                            }
                            $this->saveStepData(3);
                        }),

                    Step::make('Cierre Exitoso')
                        ->description('Finalizar el proceso del convenio')
                        ->icon('heroicon-o-check-badge')
                        ->completedIcon('heroicon-o-check-circle')
                        ->schema($this->getStepThreeSchema())
                        ->afterValidation(function () {
                            $this->saveStepData(3);
                        }),
                ])
                ->nextAction(fn (Action $action) => $this->customizeNextActionForStep2($action))
                ->previousAction(fn (Action $action) => $action->label('Anterior'))
                ->startOnStep($this->currentStep)
                ->skippable(false)
                ->persistStepInQueryString(),
            ];
        }

        return $this->getStepOneSchema();
    }

    // ========================================
    // STEP SCHEMAS
    // ========================================

    private function getStepOneSchema(): array
    {
        return \App\Filament\Schemas\ManageDocuments\StepOneSchema::make($this);
    }
    
    private function getStepTwoSchema(): array
    {
        return \App\Filament\Schemas\ManageDocuments\StepTwoSchema::make($this);
    }

    private function getStepThreeSchema(): array
    {
        if ($this->agreement && $this->agreement->status !== 'completed') {
            $this->agreement->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);
        }

        return \App\Filament\Schemas\ManageDocuments\StepThreeSchema::make($this);
    }

    // ========================================
    // WIZARD STEP MANAGEMENT
    // ========================================

    private function customizeNextActionForStep2(Action $action): Action
    {
        if ($this->currentStep === 2) {
            return $action
                ->label('📧 Enviar Confirmación y Continuar')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->size('lg')
                ->requiresConfirmation()
                ->modalHeading('Confirmar Envío de Confirmación')
                ->modalDescription('¿Está seguro de enviar la confirmación de documentos recibidos al cliente y asesor?')
                ->modalSubmitActionLabel('Sí, Enviar y Continuar')
                ->modalCancelActionLabel('Cancelar')
                ->before(function () {
                    if ($this->agreement->documents_received_at) {
                        Notification::make()
                            ->title('ℹ️ Confirmación Ya Enviada')
                            ->body('La confirmación ya fue enviada anteriormente.')
                            ->info()
                            ->duration(3000)
                            ->send();
                        return;
                    }
                    
                    Notification::make()
                        ->title('📤 Enviando Confirmación...')
                        ->body('Enviando confirmación de documentos recibidos...')
                        ->info()
                        ->duration(3000)
                        ->send();
                });
        }
        
        return $action->label('Siguiente');
    }

    public function handleStepChange($newStep, $oldStep)
    {
        \Log::info('Step change detected', [
            'old_step' => $oldStep,
            'new_step' => $newStep,
            'agreement_id' => $this->agreement->id
        ]);
        
        // Paso 1 -> 2: Enviar documentos
        if ($oldStep === 1 && $newStep === 2 && $this->agreement->status !== 'documents_sent' && !$this->agreement->documents_sent_at) {
            try {
                $this->sendDocumentsToClient(app(SendDocumentsAction::class));
                
                Notification::make()
                    ->title('✅ Documentos Enviados')
                    ->body('Los documentos han sido enviados exitosamente.')
                    ->success()
                    ->duration(5000)
                    ->send();
            } catch (\Exception $e) {
                Notification::make()
                    ->title('❌ Error al Enviar Documentos')
                    ->body('Error: ' . $e->getMessage())
                    ->danger()
                    ->duration(7000)
                    ->send();
            }
        }
        
        // Paso 2 -> 3: Completar convenio
        if ($oldStep === 2 && $newStep === 3 && !$this->agreement->documents_received_at) {
            try {
                $this->agreement->update([
                    'status' => 'completed',
                    'documents_received_at' => now(),
                    'completion_percentage' => 100,
                ]);
                
                $this->emailService->sendDocumentsReceivedConfirmation($this->agreement);
                
                Notification::make()
                    ->title('🎉 Convenio Completado')
                    ->body('El convenio ha sido marcado como exitoso.')
                    ->success()
                    ->duration(5000)
                    ->send();
            } catch (\Exception $e) {
                Notification::make()
                    ->title('❌ Error al Completar Convenio')
                    ->body('Error: ' . $e->getMessage())
                    ->danger()
                    ->duration(7000)
                    ->send();
            }
        }
    }

    public function saveStepData(int $step): void
    {
        try {
            if (!$this->agreement) {
                return;
            }

            try {
                $formData = $this->form->getRawState();
            } catch (\Exception $e) {
                $formData = $this->data ?? [];
            }
            
            $this->data = array_merge($this->data ?? [], $formData);
            
            $action = new SaveDocumentStepAction();
            $action->execute($this->agreement, $step, $this->data);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error inesperado')
                ->body('Error en saveStepData: ' . $e->getMessage())
                ->danger()
                ->duration(8000)
                ->send();
        }
    }

    // ========================================
    // DOCUMENT MANAGEMENT (Delegated to Services)
    // ========================================

    public function handleDocumentStateChange(string $fieldName, string $category, $state): void
    {
        if ($state) {
            $this->uploadService->saveDocument(
                $this->agreement,
                $fieldName,
                $state,
                $this->uploadService->getDocumentDisplayName($fieldName),
                $category
            );
        } else {
            $this->uploadService->deleteDocument($this->agreement, $fieldName);
        }
    }

    // ========================================
    // EMAIL METHODS (Delegated to Service)
    // ========================================

    public function sendDocumentsToClient(SendDocumentsAction $action)
    {
        try {
            $advisor = auth()->user();
            $clientEmail = $this->emailService->getClientEmail($this->agreement);
            
            Notification::make()
                ->title('📤 Enviando Documentos...')
                ->body("Iniciando proceso de envío...")
                ->info()
                ->duration(3000)
                ->send();

            $documentsCount = $action->execute($this->agreement, $advisor);

            Notification::make()
                ->title('✅ Documentos Enviados Exitosamente')
                ->body("Los documentos han sido enviados a {$clientEmail} y al asesor.")
                ->success()
                ->duration(7000)
                ->send();

            $this->dispatch('email-sent');

        } catch (\Exception $e) {
            \Log::error('Error sending documents to client', [
                'agreement_id' => $this->agreement->id,
                'error' => $e->getMessage()
            ]);

            Notification::make()
                ->title('❌ Error al Enviar Documentos')
                ->body($e->getMessage())
                ->danger()
                ->duration(7000)
                ->send();

            $this->dispatch('email-sent');
        }
    }

    // ========================================
    // RENDERING METHODS (Delegated to Service)
    // ========================================

    public function getOriginalValorCompraventa(): string
    {
        return $this->renderer->renderOriginalValorCompraventa($this->agreement);
    }

    public function getOriginalComisionTotal(): string
    {
        return $this->renderer->renderOriginalComisionTotal($this->agreement);
    }

    public function getOriginalGananciaFinal(): string
    {
        return $this->renderer->renderOriginalGananciaFinal($this->agreement);
    }

    public function getValueComparison(): string
    {
        return $this->renderer->renderValueComparison($this->agreement);
    }

    // ========================================
    // HELPER METHODS (Delegated to Services)
    // ========================================

    public function getClientName(): string
    {
        return $this->emailService->getClientName($this->agreement);
    }

    public function getClientEmail(): string
    {
        return $this->emailService->getClientEmail($this->agreement);
    }

    public function getClientPhone(): string
    {
        $wizardData = $this->agreement->wizard_data ?? [];
        $holderPhone = $wizardData['holder_phone'] ?? null;

        if (!$holderPhone && $this->agreement->client) {
            $holderPhone = $this->agreement->client->phone;
        }

        return $holderPhone ?? 'No disponible';
    }

    public function getPropertyAddress(): string
    {
        $wizardData = $this->agreement->wizard_data ?? [];
        return $wizardData['domicilio_convenio'] ?? 'No disponible';
    }

    public function getPropertyCommunity(): string
    {
        $wizardData = $this->agreement->wizard_data ?? [];
        return $wizardData['comunidad'] ?? 'No disponible';
    }

    public function getAgreementValue(): string
    {
        $wizardData = $this->agreement->wizard_data ?? [];
        $value = $wizardData['valor_convenio'] ?? null;

        if ($value) {
            return '$' . number_format($value, 2);
        }

        return 'No disponible';
    }

    // ========================================
    // ACTION METHODS
    // ========================================

    public function downloadAllDocuments(GenerateDocumentsZipAction $action)
    {
        try {
            if (!$this->agreement) {
                throw new \Exception('No se encontró el convenio');
            }

            $zipPath = $action->execute($this->agreement);
            $zipFileName = basename($zipPath);

            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al Descargar')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function returnToHome()
    {
        Notification::make()
            ->title('🏠 Regresando al Inicio')
            ->body('Redirigiendo al dashboard principal...')
            ->success()
            ->send();

        return $this->redirect('/admin');
    }

    public function downloadUpdatedChecklist()
    {
        try {
            if (!$this->agreement) {
                throw new \Exception('No se encontró el convenio');
            }

            $uploadedDocuments = \App\Models\ClientDocument::where('agreement_id', $this->agreement->id)
                ->pluck('document_type')
                ->toArray();

            $pdfService = app(PdfGenerationService::class);
            $pdf = $pdfService->generateChecklist($this->agreement, $uploadedDocuments, true);

            $fileName = 'checklist_actualizado_' . $this->agreement->id . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';

            Notification::make()
                ->title('📋 Lista Actualizada Generada')
                ->body('El checklist con documentos marcados ha sido generado exitosamente')
                ->success()
                ->duration(4000)
                ->send();

            return response()->streamDownload(
                fn() => print($pdf->output()),
                $fileName,
                ['Content-Type' => 'application/pdf']
            );

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error al Generar')
                ->body('No se pudo generar el checklist: ' . $e->getMessage())
                ->danger()
                ->duration(6000)
                ->send();
        }
    }

    public function saveProposalValue(): void
    {
        try {
            $proposalValue = $this->proposal_value;
            
            if (is_null($proposalValue)) {
                $formData = $this->form->getRawState();
                $proposalValue = $formData['proposal_value'] ?? null;
            }

            if (is_null($proposalValue) || $proposalValue === '' || $proposalValue <= 0) {
                Notification::make()
                    ->title('❌ Error de Validación')
                    ->body('Debe ingresar un valor válido antes de guardar.')
                    ->danger()
                    ->send();
                return;
            }

            $this->agreement->update([
                'proposal_value' => $proposalValue,
                'proposal_saved_at' => now(),
                'wizard2_current_step' => 3,
            ]);

            Notification::make()
                ->title('✅ Valor Guardado Exitosamente')
                ->body('El Valor de Propuesta Final ha sido registrado en el convenio.')
                ->success()
                ->duration(4000)
                ->send();

            $this->agreement->refresh();
            $existingDocuments = $this->uploadService->loadDocuments($this->agreement);
            $existingDocuments['proposal_value'] = $this->agreement->proposal_value;
            $this->form->fill($existingDocuments);

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error al Guardar')
                ->body('Ocurrió un error: ' . $e->getMessage())
                ->danger()
                ->duration(7000)
                ->send();
        }
    }

    public function getDocumentFields(): array
    {
        // Este método se mantiene para compatibilidad con StepOneSchema
        // La lógica de renderizado está en el schema
        return [];
    }
}
