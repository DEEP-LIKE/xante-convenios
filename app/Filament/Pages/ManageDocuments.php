<?php

namespace App\Filament\Pages;

use App\Actions\Agreements\GenerateDocumentsZipAction;
use App\Actions\Agreements\SaveDocumentStepAction;
use App\Actions\Agreements\SendDocumentsAction;
use App\Actions\Agreements\SyncClientToHubspotAction;
use App\Models\Agreement;
use App\Services\DocumentEmailService;
use App\Services\DocumentFileManager;
use App\Services\DocumentStateManager;
use App\Services\DocumentUploadService;
use App\Services\PdfGenerationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\Facades\Storage;

class ManageDocuments extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

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


    /**
     * Método boot para inyección de dependencias
     */
    public function boot(
        DocumentStateManager $stateManager,
        DocumentUploadService $uploadService,
        DocumentEmailService $emailService,
        DocumentFileManager $fileManager
    ): void {
        $this->stateManager = $stateManager;
        $this->uploadService = $uploadService;
        $this->emailService = $emailService;
        $this->fileManager = $fileManager;
    }



    // ========================================
    // LIFECYCLE METHODS
    // ========================================

    public function mount(): void
    {
        // 1. Determinar el paso máximo permitido según el estado actual
        $maxAllowedStep = $this->stateManager->determineCurrentStep($this->agreement);

        // 2. Verificar si hay un paso solicitado en la URL
        $urlStep = $this->getCurrentUrlStep();

        // 3. Establecer paso actual con validación (no permitir avanzar más allá del estado real)
        if ($urlStep && $urlStep <= $maxAllowedStep) {
            $this->currentStep = $urlStep;
        } else {
            // Si no hay URL o el paso URL es inválido/futuro, usar el paso determinado por el estado
            $this->currentStep = $maxAllowedStep;
        }

        // 4. Lógica de seguridad: Si estamos en paso 2 pero no se ha enviado el correo, forzar paso 1
        // Esto corrige el caso donde se fuerza el paso 2 por URL pero falta el envío
        if ($this->currentStep === 2 && ! $this->agreement->documents_sent_at) {
            $this->currentStep = 1;
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

        if (! empty($existingDocuments)) {
            $this->form->fill($existingDocuments);
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
                            \Log::debug('Step 1 afterValidation triggered', [
                                'agreement_id' => $this->agreement->id,
                                'status' => $this->agreement->status,
                                'sent_at' => $this->agreement->documents_sent_at,
                            ]);

                            // CORRECCIÓN: Verificar solo si NO se han enviado los documentos (documents_sent_at es NULL)
                            // No verificar el status porque puede ser 'completed' de operaciones previas
                            \Log::info('Checking Step 1 completion status', [
                                'id' => $this->agreement->id, 
                                'status' => $this->agreement->status, 
                                'sent_at' => $this->agreement->documents_sent_at
                            ]);
                            
                            if (!$this->agreement->documents_sent_at) {
                                \Log::info('Step 1 Condition met for agreement ' . $this->agreement->id . ', calling sendDocumentsToClient');
                                $this->sendDocumentsToClient(app(SendDocumentsAction::class));

                                Notification::make()
                                    ->title('✅ Documentos Enviados')
                                    ->body('Los documentos han sido enviados exitosamente al cliente y al asesor.')
                                    ->success()
                                    ->duration(5000)
                                    ->send();
                            } else {
                                \Log::debug('Trigger automático omitido (documentos ya enviados previamente)', [
                                    'sent_at' => $this->agreement->documents_sent_at
                                ]);
                            }

                            // Sincronizar datos básicos del cliente (Wizard 1) con HubSpot
                            // Esto asegura que Nombre, Teléfono, Email, etc. se actualicen si se modificaron
                            try {
                                app(SyncClientToHubspotAction::class)->execute($this->agreement, $this->agreement->wizard_data ?? []);
                                \Log::info('HubSpot sincronizado tras envío de documentos (Step 1)', ['agreement_id' => $this->agreement->id]);
                            } catch (\Exception $e) {
                                \Log::error('Error sincronizando HubSpot en Step 1', ['error' => $e->getMessage()]);
                            }

                            $this->saveStepData(1);
                        }),

                    Step::make('Recepción de Documentos')
                        ->description('Recibir y validar documentos del cliente')
                        ->icon('heroicon-o-document-arrow-up')
                        ->completedIcon('heroicon-o-check-circle')
                        ->schema($this->getStepTwoSchema())
                        ->afterValidation(function () {
                            \Log::debug('Step 2 afterValidation triggered', [
                                'agreement_id' => $this->agreement->id,
                                'documents_received_at' => $this->agreement->documents_received_at,
                                'status' => $this->agreement->status,
                            ]);

                            // CORRECCIÓN: Trigger de completado y email aquí, ya que handleStepChange no siempre se dispara
                            if (! $this->agreement->documents_received_at) {
                                try {
                                    $this->agreement->update([
                                        'status' => 'completed',
                                        'documents_received_at' => now(),
                                        'completion_percentage' => 100,
                                    ]);

                                    \Log::info('Agreement marked as completed in Step 2 afterValidation', ['id' => $this->agreement->id]);

                                    // Enviar email de confirmación
                                    $this->emailService->sendDocumentsReceivedConfirmation($this->agreement);

                                    Notification::make()
                                        ->title('🎉 Convenio Completado')
                                        ->body('El convenio ha sido marcado como exitoso y se envió la confirmación.')
                                        ->success()
                                        ->duration(5000)
                                        ->send();

                                    // Sincronizar con HubSpot
                                    try {
                                        app(SyncClientToHubspotAction::class)->execute($this->agreement, $this->agreement->wizard_data ?? []);
                                        
                                        // Sincronización Fase 2: Actualizar 'nombre_inmueble' (XA-ID)
                                        if ($this->agreement->client && $this->agreement->client->hubspot_deal_id) {
                                            $hubspotDealId = $this->agreement->client->hubspot_deal_id;
                                            $xanteId = $this->agreement->client->xante_id;
                                            
                                            if ($hubspotDealId && $xanteId) {
                                                $nombreInmueble = str_starts_with($xanteId, 'XA-') ? $xanteId : 'XA-'.$xanteId;
                                                app(\App\Services\HubSpotService::class)->updateDeal($hubspotDealId, [
                                                    'nombre_inmueble' => $nombreInmueble,
                                                ]);
                                                \Log::info('HubSpot Deal updated with nombre_inmueble in afterValidation', ['deal_id' => $hubspotDealId]);
                                            }
                                        }
                                    } catch (\Exception $e) {
                                        \Log::error('Error syncing to HubSpot in Step 2 afterValidation', ['error' => $e->getMessage()]);
                                    }
                                } catch (\Exception $e) {
                                    \Log::error('Error finalizing agreement in Step 2 afterValidation', ['error' => $e->getMessage()]);
                                    Notification::make()
                                        ->title('❌ Error al Completar')
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }

                            $this->saveStepData(2);
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
        // NOTA: Este método se ejecuta al CARGAR el schema del paso 3, no al LLEGAR al paso 3
        // El status se actualiza correctamente en handleStepChange al pasar del paso 2→3
        // NO actualizar status aquí
        
        return \App\Filament\Schemas\ManageDocuments\StepThreeSchema::make($this);
    }

    // ========================================
    // WIZARD STEP MANAGEMENT
    // ========================================

    private function customizeNextActionForStep2(Action $action): Action
    {
        if ($this->currentStep === 2) {
            return $action
                ->label('Enviar Confirmación y Continuar')
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
            'agreement_id' => $this->agreement->id,
        ]);

        if ($oldStep === 1 && $newStep === 2) {
            \Log::info('HandleStepChange 1->2 detected', [
                'agreement_id' => $this->agreement->id, 
                'status' => $this->agreement->status,
                'documents_sent_at' => $this->agreement->documents_sent_at
            ]);
            
            // CORRECCIÓN: Solo verificar si documents_sent_at es NULL
            // No verificar status porque puede ser 'completed' de operaciones previas
            if (!$this->agreement->documents_sent_at) {
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
                        ->body('Error: '.$e->getMessage())
                        ->danger()
                        ->duration(7000)
                        ->send();
                }
            } else {
                \Log::debug('Email sending skipped - documents already sent', [
                    'sent_at' => $this->agreement->documents_sent_at
                ]);
            }
        }

        // Paso 2 -> 3: El completado se maneja ahora en el afterValidation del Step 2
        // para asegurar que el trigger de email se dispare correctamente.
    }

    public function saveStepData(int $step): void
    {
        try {
            if (! $this->agreement) {
                return;
            }

            try {
                $formData = $this->form->getRawState();
            } catch (\Exception $e) {
                $formData = $this->data ?? [];
            }

            $this->data = array_merge($this->data ?? [], $formData);

            $action = new SaveDocumentStepAction;
            $action->execute($this->agreement, $step, $this->data);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error inesperado')
                ->body('Error en saveStepData: '.$e->getMessage())
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
                ->body('Iniciando proceso de envío...')
                ->info()
                ->duration(3000)
                ->send();

            $documentsCount = $action->execute($this->agreement, $advisor);
            \Log::info('sendDocumentsToClient executed action', ['count' => $documentsCount]);

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
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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

        if (! $holderPhone && $this->agreement->client) {
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
            return '$'.number_format($value, 2);
        }

        return 'No disponible';
    }

    // ========================================
    // ACTION METHODS
    // ========================================

    public function returnToHome()
    {
        Notification::make()
            ->title('Regresando al Inicio')
            ->body('Redirigiendo al dashboard principal...')
            ->success()
            ->send();

        return $this->redirect('/admin');
    }

    public function downloadUpdatedChecklist()
    {
        try {
            if (! $this->agreement) {
                throw new \Exception('No se encontró el convenio');
            }

            $uploadedDocuments = \App\Models\ClientDocument::where('agreement_id', $this->agreement->id)
                ->pluck('document_type')
                ->toArray();

            $pdfService = app(PdfGenerationService::class);
            $pdf = $pdfService->generateChecklist($this->agreement, $uploadedDocuments, true);

            $fileName = 'checklist_actualizado_'.$this->agreement->id.'_'.now()->format('Y-m-d_H-i-s').'.pdf';

            Notification::make()
                ->title('Lista Actualizada Generada')
                ->body('El checklist con documentos marcados ha sido generado exitosamente')
                ->success()
                ->duration(4000)
                ->send();

            return response()->streamDownload(
                fn () => print ($pdf->output()),
                $fileName,
                ['Content-Type' => 'application/pdf']
            );

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error al Generar')
                ->body('No se pudo generar el checklist: '.$e->getMessage())
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

            // Sincronizar monto de propuesta con HubSpot
            try {
                $syncAction = app(SyncClientToHubspotAction::class);
                $syncAction->execute($this->agreement, $this->agreement->wizard_data ?? []);
                \Log::info('HubSpot actualizado con valor de propuesta', ['agreement_id' => $this->agreement->id]);
            } catch (\Exception $e) {
                \Log::error('Error sincronizando HubSpot al guardar valor propuesta', ['error' => $e->getMessage()]);
            }

            $this->agreement->refresh();
            $existingDocuments = $this->uploadService->loadDocuments($this->agreement);
            $existingDocuments['proposal_value'] = $this->agreement->proposal_value;
            $this->form->fill($existingDocuments);

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error al Guardar')
                ->body('Ocurrió un error: '.$e->getMessage())
                ->danger()
                ->duration(7000)
                ->send();
        }
    }

    public function getDocumentFields(): array
    {
        $documentComponents = [];

        foreach ($this->agreement->generatedDocuments as $document) {
            $documentComponents[] = \Filament\Forms\Components\Placeholder::make("document_{$document->id}")
                ->label($document->document_name ?? $document->document_type)
                ->content(function () use ($document) {
                    $downloadUrl = route('documents.download', ['document' => $document->id]);
                    $fileName = $document->file_name ?? basename($document->file_path);
                    $fileSize = $document->formatted_size ?? 'N/A';

                    return new \Illuminate\Support\HtmlString("
                        <div style='display: flex; align-items: center; justify-content: space-between; padding: 16px; background: #f9fafb; border-radius: 12px; border: 1px solid #e5e7eb; transition: background-color 0.2s;' onmouseover='this.style.background=\"#f3f4f6\"' onmouseout='this.style.background=\"#f9fafb\"'>
                            <div style='flex: 1; min-width: 0; padding-right: 16px;'>
                                <div style='font-weight: 600; color: #111827; margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'>{$fileName}</div>
                                <div style='font-size: 12px; color: #6b7280;'>Tamaño: {$fileSize}</div>
                            </div>
                            <a href='{$downloadUrl}' 
                               target='_blank'
                               style='display: inline-flex; align-items: center; padding: 10px 20px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; font-weight: 500; border-radius: 8px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3); white-space: nowrap;'
                               onmouseover=\"this.style.background='linear-gradient(135deg, #059669 0%, #047857 100%)'; this.style.boxShadow='0 4px 12px rgba(16, 185, 129, 0.5)'; this.style.transform='translateY(-2px)';\"
                               onmouseout=\"this.style.background='linear-gradient(135deg, #10b981 0%, #059669 100%)'; this.style.boxShadow='0 2px 8px rgba(16, 185, 129, 0.3)'; this.style.transform='translateY(0)';\">
                                <svg style='width: 16px; height: 16px; margin-right: 8px;' fill='none' stroke='currentColor' viewBox='0 0 24 24' stroke-width='2'>
                                    <path stroke-linecap='round' stroke-linejoin='round' d='M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'/>
                                </svg>
                                Descargar PDF
                            </a>
                        </div>
                    ");
                });
        }

        // Envolver en un Grid de 2 columnas
        return [
            \Filament\Schemas\Components\Grid::make(2)
                ->schema($documentComponents),
        ];
    }

    /**
     * Descarga el checklist de expediente actualizado con los documentos subidos marcados
     */
    public function downloadUpdatedChecklistAction()
    {
        try {
            // Obtener los documentos subidos del cliente
            $uploadedDocuments = $this->agreement->clientDocuments()
                ->pluck('document_type')
                ->toArray();

            // Generar el PDF del checklist con los documentos marcados
            $pdfService = new PdfGenerationService;

            // Preparar los datos para el template
            $wizardData = $this->agreement->wizard_data ?? [];
            $templateData = $pdfService->prepareTemplateData($this->agreement);

            // Agregar información de documentos subidos
            $templateData['uploadedDocuments'] = $uploadedDocuments;
            $templateData['isUpdated'] = true; // Indica que es la versión actualizada

            // Generar el PDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                'pdfs.templates.checklist_expediente',
                $templateData
            );

            $pdf->setPaper('letter', 'portrait');

            // Nombre del archivo
            $fileName = 'checklist_expediente_actualizado_'.$this->agreement->id.'_'.now()->format('Ymd').'.pdf';

            // Descargar el PDF
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $fileName, [
                'Content-Type' => 'application/pdf',
            ]);

        } catch (\Exception $e) {
            \Log::error('Error generating updated checklist', [
                'agreement_id' => $this->agreement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('Error al generar checklist')
                ->body('No se pudo generar el checklist actualizado: '.$e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }

    /**
     * Descarga todos los documentos (generados + cliente) en un archivo ZIP
     */
    public function downloadAllDocuments()
    {
        try {
            // Notificar al usuario que se está generando el ZIP
            Notification::make()
                ->title('📦 Generando ZIP')
                ->body('Preparando todos los documentos para descarga...')
                ->info()
                ->send();

            $zipAction = new GenerateDocumentsZipAction;
            $zipPath = $zipAction->execute($this->agreement);

            // Nombre del archivo para descarga
            $downloadName = 'convenio_'.$this->agreement->id.'_documentos_completos.zip';

            // Notificar éxito
            Notification::make()
                ->title('✅ ZIP Generado')
                ->body('Descargando archivo con todos los documentos...')
                ->success()
                ->duration(3000)
                ->send();

            // Descargar y luego eliminar el archivo temporal
            return response()->download($zipPath, $downloadName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            \Log::error('Error downloading all documents', [
                'agreement_id' => $this->agreement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('❌ Error al descargar documentos')
                ->body('No se pudieron descargar los documentos: '.$e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }

    public function regenerateDocuments()
    {
        try {
            if (! $this->agreement) {
                return;
            }

            Notification::make()
                ->title('🔄 Regenerando Documentos')
                ->body('Iniciando proceso de generación de documentos PDF...')
                ->info()
                ->send();

            $action = app(\App\Actions\Agreements\GenerateAgreementDocumentsAction::class);
            $resultUrl = $action->execute(
                $this->agreement->id,
                $this->agreement->wizard_data ?? [],
                true
            );

            if ($resultUrl) {
                // Recargar datos
                $this->agreement->refresh();
                $this->agreement->load(['generatedDocuments']);
                
                // Forzar actualización de la UI de Livewire
                $this->form->fill($this->uploadService->loadDocuments($this->agreement));

                Notification::make()
                    ->title('✅ Documentos Regenerados')
                    ->body('Los documentos se han regenerado y guardado en S3 exitosamente.')
                    ->success()
                    ->duration(5000)
                    ->send();
            }

        } catch (\Exception $e) {
            \Log::error('Error regenerating documents', [
                'agreement_id' => $this->agreement->id,
                'error' => $e->getMessage()
            ]);

            Notification::make()
                ->title('❌ Error al Regenerar')
                ->body($e->getMessage())
                ->danger()
                ->duration(8000)
                ->send();
        }
    }
}
