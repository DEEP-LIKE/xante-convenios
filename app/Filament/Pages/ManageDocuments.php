<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\Actions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use App\Models\Agreement;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\ConfigurationCalculator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\UploadedFile;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use App\Jobs\GenerateAgreementDocumentsJob;
use Illuminate\Support\HtmlString;
use App\Services\PdfGenerationService;
use App\Mail\DocumentsReadyMail;
use ZipArchive;

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

    // Propiedades para los campos de FileUpload con ->live() - inicializadas como arrays vacíos
    // DOCUMENTACIÓN TITULAR
    public array $holder_ine = [];
    public array $holder_curp = [];
    public array $holder_fiscal_status = [];
    public array $holder_proof_address_home = [];
    public array $holder_proof_address_titular = [];
    public array $holder_birth_certificate = [];
    public array $holder_marriage_certificate = [];
    public array $holder_bank_statement = [];
    
    // DOCUMENTACIÓN PROPIEDAD
    public array $property_notarial_instrument = [];
    public array $property_tax_receipt = [];
    public array $property_water_receipt = [];
    public array $property_cfe_receipt = [];
    public int $currentStep = 1;
    public int $totalSteps = 3;
    
    // Control de eliminaciones múltiples - SIMPLIFICADO (sin static para Livewire)
    public array $processingDeletions = [];
    public bool $isDeletingDocuments = false;
    
    // Propiedad para el checkbox de validación de documentos
    public bool $documents_validated = false;
    private function getCurrentUrlStep(): ?int
    {
        $currentUrl = request()->url();
        $queryParams = request()->query();

        // Buscar el parámetro 'step' en la URL
        if (isset($queryParams['step'])) {
            $stepParam = $queryParams['step'];

            // El formato del parámetro step es algo como: "form.wizard.cierre-exitoso:::wizard-step"
            // Necesitamos extraer el número del paso del final
            if (preg_match('/wizard-step$/', $stepParam)) {
                // Si termina con "wizard-step", asumimos que es el paso 3 (cierre-exitoso)
                return 3;
            }

            // También podemos buscar patrones como "form.wizard.envio-de-documentos:::wizard-step" para paso 1
            if (preg_match('/envio-de-documentos/', $stepParam)) {
                return 1;
            }

            // "form.wizard.recepcion-de-documentos:::wizard-step" para paso 2
            if (preg_match('/recepcion-de-documentos/', $stepParam)) {
                return 2;
            }
        }

        return null;
    }

    public function mount(): void
    {
        // Determinar el paso actual basado en el estado del convenio
        $this->currentStep = match($this->agreement->status) {
            'documents_generating', 'documents_generated' => 1,
            'documents_sent', 'awaiting_client_docs' => 2,
            'documents_complete', 'completed' => 3,
            default => 1
        };

        // Verificar si hay un parámetro de paso específico en la URL
        $currentUrlStep = $this->getCurrentUrlStep();

        // Si hay un paso específico en la URL y es diferente al calculado por estado, usarlo
        if ($currentUrlStep && $currentUrlStep !== $this->currentStep) {
            $this->currentStep = $currentUrlStep;

            // SOLO marcar como completado si realmente llegamos al paso 3 desde la interfaz
            // No marcar como completado si navegamos manualmente al paso 3 desde URL
            if ($this->currentStep === 3 && $this->agreement->status !== 'completed' && $currentUrlStep === 3) {
                $this->markAsCompleted();
            }
        } else {
            // Marcar como completado solo si llegamos al paso 3 por estado Y el estado no es 'completed'
            if ($this->currentStep === 3 && $this->agreement->status !== 'completed') {
                $this->markAsCompleted();
            }
        }

        // Refrescar las relaciones para asegurar que tenemos los datos más recientes
        $this->agreement->refresh();
        $this->agreement->load(['generatedDocuments', 'clientDocuments']);

        // Limpiar archivos huérfanos antes de cargar documentos
        $this->cleanOrphanFiles();

        // Cargar documentos del cliente y llenar el formulario SOLO UNA VEZ
        $existingDocuments = $this->loadClientDocuments();
        
        // Agregar el proposal_value al formulario si existe
        if ($this->agreement->proposal_value) {
            $existingDocuments['proposal_value'] = $this->agreement->proposal_value;
            $this->proposal_value = $this->agreement->proposal_value;
        }
        
        if (!empty($existingDocuments)) {
            $this->form->fill($existingDocuments);
        }
        
        // Inicializar el checkbox de validación de documentos
        $this->documents_validated = $this->agreement->status === 'completed';
        
        // Notificar si se cargaron documentos existentes
        if (!empty($existingDocuments)) {
            $documentCount = count($existingDocuments);
            Notification::make()
                ->title('Documentos Cargados')
                ->body("Se han recuperado {$documentCount} documentos previamente subidos")
                ->success()
                ->duration(4000)
                ->send();
        }

        // Notificación informativa sobre el estado actual
        $statusMessages = [
            'documents_generating' => 'Los documentos se están generando en segundo plano...',
            'documents_generated' => 'Documentos listos para enviar al cliente',
            'documents_sent' => 'Documentos enviados, esperando documentos del cliente',
            'awaiting_client_docs' => 'Esperando que el cliente suba sus documentos',
            'documents_complete' => 'Todos los documentos recibidos',
            'completed' => 'Convenio completado exitosamente'
        ];

        if (isset($statusMessages[$this->agreement->status])) {
            Notification::make()
                ->title('Estado del Convenio')
                ->body($statusMessages[$this->agreement->status])
                ->info()
                ->duration(5000)
                ->send();
        }
    }

    protected function getFormSchema(): array
{
    // Verificar si Wizard está disponible
    if (class_exists(Wizard::class)) {
        return [
            Wizard::make([
                Step::make('Envío de Documentos')
                    ->description('Enviar documentos al cliente por correo electrónico')
                    ->icon('heroicon-o-paper-airplane')
                    ->completedIcon('heroicon-o-check-circle')
                    ->schema($this->getStepOneSchema())
                    ->afterValidation(function () {
                        // Solo enviar si no se han enviado previamente
                        if ($this->agreement->status !== 'documents_sent' && !$this->agreement->documents_sent_at) {
                            
                            \Log::info('Sending documents from afterValidation', [
                                'agreement_id' => $this->agreement->id,
                                'current_status' => $this->agreement->status
                            ]);
                            
                            // Enviar documentos después de validar el paso 1
                            $this->sendDocumentsToClient();
                            
                            // Notificación de éxito
                            Notification::make()
                                ->title('✅ Documentos Enviados')
                                ->body('Los documentos han sido enviados exitosamente al cliente y al asesor.')
                                ->success()
                                ->duration(5000)
                                ->send();
                        } else {
                            \Log::info('Documents already sent, skipping', [
                                'agreement_id' => $this->agreement->id,
                                'status' => $this->agreement->status,
                                'sent_at' => $this->agreement->documents_sent_at
                            ]);
                        }
                        
                        $this->saveStepData(1);
                    }),

                Step::make('Recepción de Documentos')
                    ->description('Recibir y validar documentos del cliente')
                    ->icon('heroicon-o-document-arrow-up')
                    ->completedIcon('heroicon-o-check-circle')
                    ->schema($this->getStepTwoSchema())
                    ->afterValidation(function () {
                        \Log::info('Step 2 afterValidation triggered', [
                            'agreement_id' => $this->agreement->id,
                            'current_status' => $this->agreement->status,
                            'documents_received_at' => $this->agreement->documents_received_at
                        ]);
                        
                        // Solo enviar correo si no se ha enviado previamente (basado únicamente en documents_received_at)
                        if (!$this->agreement->documents_received_at) {
                            
                            \Log::info('Completing agreement from step 2 afterValidation', [
                                'agreement_id' => $this->agreement->id,
                                'current_status' => $this->agreement->status
                            ]);
                            
                            try {
                                // Marcar convenio como completado
                                $this->agreement->update([
                                    'status' => 'completed',
                                    'documents_received_at' => now(),
                                    'completion_percentage' => 100,
                                ]);
                                
                                \Log::info('Agreement status updated, now sending confirmation email');
                                
                                // Enviar correo de confirmación (igual que en paso 1)
                                $this->sendDocumentsReceivedConfirmation();
                                
                                \Log::info('Confirmation email sent successfully');
                                
                                // Notificación de éxito
                                Notification::make()
                                    ->title('🎉 Convenio Completado')
                                    ->body('El convenio ha sido marcado como exitoso y se ha enviado la confirmación por correo.')
                                    ->success()
                                    ->duration(5000)
                                    ->send();
                                    
                            } catch (\Exception $e) {
                                \Log::error('Error in step 2 afterValidation', [
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString()
                                ]);
                                
                                Notification::make()
                                    ->title('❌ Error al Completar')
                                    ->body('Error: ' . $e->getMessage())
                                    ->danger()
                                    ->duration(7000)
                                    ->send();
                            }
                        } else {
                            \Log::info('Confirmation email already sent, skipping', [
                                'agreement_id' => $this->agreement->id,
                                'status' => $this->agreement->status,
                                'documents_received_at' => $this->agreement->documents_received_at
                            ]);
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
    } else {
        // Fallback a Tabs si Wizard no está disponible
        return $this->getStepOneSchema(); // Mostrar solo el primer paso
    }
}

    // Personalizar botón "Siguiente" para paso 2
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
                ->modalDescription('¿Está seguro de enviar la confirmación de documentos recibidos al cliente y asesor? Después del envío avanzará automáticamente al siguiente paso.')
                ->modalSubmitActionLabel('Sí, Enviar y Continuar')
                ->modalCancelActionLabel('Cancelar')
                ->before(function () {
                    // Verificar si ya se envió
                    if ($this->agreement->documents_received_at) {
                        Notification::make()
                            ->title('ℹ️ Confirmación Ya Enviada')
                            ->body('La confirmación ya fue enviada anteriormente. Avanzando al siguiente paso.')
                            ->info()
                            ->duration(3000)
                            ->send();
                        return;
                    }
                    
                    // Mostrar notificación de inicio
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

    // Manejar cambios de paso del wizard
    public function handleStepChange($newStep, $oldStep)
    {
        \Log::info('Step change detected', [
            'old_step' => $oldStep,
            'new_step' => $newStep,
            'agreement_id' => $this->agreement->id
        ]);
        
        // Si estamos avanzando del paso 1 al paso 2, enviar documentos (solo si no se han enviado)
        if ($oldStep === 1 && $newStep === 2 && $this->agreement->status !== 'documents_sent' && !$this->agreement->documents_sent_at) {
            try {
                \Log::info('Sending documents from handleStepChange (backup)', [
                    'agreement_id' => $this->agreement->id,
                    'current_status' => $this->agreement->status
                ]);
                
                $this->sendDocumentsToClient();
                
                Notification::make()
                    ->title('✅ Documentos Enviados')
                    ->body('Los documentos han sido enviados exitosamente al cliente y al asesor.')
                    ->success()
                    ->duration(5000)
                    ->send();
                    
            } catch (\Exception $e) {
                \Log::error('Error sending documents on step change', [
                    'error' => $e->getMessage(),
                    'agreement_id' => $this->agreement->id
                ]);
                
                Notification::make()
                    ->title('❌ Error al Enviar Documentos')
                    ->body('Error: ' . $e->getMessage())
                    ->danger()
                    ->duration(7000)
                    ->send();
            }
        } else if ($oldStep === 1 && $newStep === 2) {
            \Log::info('Documents already sent, advancing normally', [
                'agreement_id' => $this->agreement->id,
                'status' => $this->agreement->status,
                'sent_at' => $this->agreement->documents_sent_at
            ]);
        }
        
        // Si estamos avanzando del paso 2 al paso 3, completar convenio (backup como en paso 1)
        if ($oldStep === 2 && $newStep === 3 && !$this->agreement->documents_received_at) {
            try {
                \Log::info('Completing agreement from handleStepChange step 2->3 (backup)', [
                    'agreement_id' => $this->agreement->id,
                    'current_status' => $this->agreement->status
                ]);
                
                // Marcar convenio como completado
                $this->agreement->update([
                    'status' => 'completed',
                    'documents_received_at' => now(),
                    'completion_percentage' => 100,
                ]);
                
                // Enviar correo de confirmación
                $this->sendDocumentsReceivedConfirmation();
                
                Notification::make()
                    ->title('🎉 Convenio Completado')
                    ->body('El convenio ha sido marcado como exitoso y se ha enviado la confirmación por correo.')
                    ->success()
                    ->duration(5000)
                    ->send();
                    
            } catch (\Exception $e) {
                \Log::error('Error completing agreement on step change 2->3', [
                    'error' => $e->getMessage(),
                    'agreement_id' => $this->agreement->id
                ]);
                
                Notification::make()
                    ->title('❌ Error al Completar Convenio')
                    ->body('Error: ' . $e->getMessage())
                    ->danger()
                    ->duration(7000)
                    ->send();
            }
        } else if ($oldStep === 2 && $newStep === 3) {
            \Log::info('Agreement already completed, advancing normally from step 2->3', [
                'agreement_id' => $this->agreement->id,
                'status' => $this->agreement->status,
                'completed_at' => $this->agreement->documents_received_at
            ]);
        }
    }

    // PASO 1: Envío de Documentos
    private function getStepOneSchema(): array
    {
        return [
                
            Section::make('Información del Convenio')
                ->icon('heroicon-o-document-text') // Usamos el icono oficial de Heroicons
                ->iconColor('success') // Le damos un color llamativo (verde)
                ->description('Datos básicos del convenio')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Placeholder::make('agreement_id')
                                ->label('ID del Convenio')
                                ->content($this->agreement->id),
                                
                            Placeholder::make('client_name')
                                ->label('Cliente Titular')
                                ->content($this->getClientName()),
                                
                            Placeholder::make('client_email')
                                ->label('Email del Cliente')
                                ->content($this->getClientEmail()),
                                
                            Placeholder::make('client_phone')
                                ->label('Teléfono del Cliente')
                                ->content($this->getClientPhone()),
                                
                            Placeholder::make('property_address')
                                ->label('Domicilio de la Propiedad')
                                ->content($this->getPropertyAddress()),
                                
                            Placeholder::make('documents_count')
                                ->label('Documentos Generados')
                                ->content($this->agreement->generatedDocuments->count() . ' PDFs'),
                        ])
                ]),
                
            Section::make('Documentos Disponibles')
                ->description('Documentos PDF generados para este convenio')
                ->icon('heroicon-o-document-text') // Usamos el icono oficial de Heroicons
                ->iconColor('success') // Le damos un color llamativo (verde)
                ->schema($this->getDocumentFields())
                ->visible($this->agreement->generatedDocuments->isNotEmpty()),
                
            Section::make('Sin Documentos')
                ->description('No hay documentos generados')
                ->icon('heroicon-o-exclamation-triangle') // Usamos el icono oficial de Heroicons
                ->iconColor('warning') // Le damos un color llamativo (verde)
                ->schema([
                    Placeholder::make('no_documents')
                        ->label('Estado')
                        ->content('No se encontraron documentos generados para este convenio. Use el botón "Regenerar Documentos" en la parte superior.'),
                ])
                ->visible($this->agreement->generatedDocuments->isEmpty()),
                
            Section::make('Enviar al Cliente')
                ->description('Enviar documentos por correo electrónico')
                ->icon('heroicon-o-paper-airplane') // Usamos el icono oficial de Heroicons
                ->iconColor('success') // Le damos un color llamativo (verde)
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Placeholder::make('send_summary')
                                ->label('📋 Resumen del Envío')
                                ->content(function () {
                                    $clientName = $this->getClientName();
                                    $clientEmail = $this->getClientEmail();
                                    $docsCount = $this->agreement->generatedDocuments->count();
                                    $propertyAddress = $this->getPropertyAddress();
                                    
                                    return "Cliente: {$clientName}<br>Email: {$clientEmail}<br>Documentos: {$docsCount} PDFs<br>Propiedad: {$propertyAddress}";
                                })
                                ->html(),
                                
                            Placeholder::make('agreement_summary')
                                ->label('💰 Datos del Convenio')
                                ->content(function () {
                                    $agreementValue = $this->getAgreementValue();
                                    $community = $this->getPropertyCommunity();
                                    $createdDate = $this->agreement->created_at->format('d/m/Y');
                                    
                                    $content = "Valor: {$agreementValue}<br>Comunidad: {$community}<br>Creado: {$createdDate}";
                                    
                                    // Agregar fecha de envío si los documentos ya fueron enviados
                                    if ($this->agreement->documents_sent_at) {
                                        $sentDate = $this->agreement->documents_sent_at->format('Y-m-d H:i:s');
                                        $content .= "<br><span style='color: #10b981; font-weight: 600;'>📧 Enviado: {$sentDate}</span>";
                                    }
                                    
                                    return $content;
                                })
                                ->html(),
                        ]),
                        
                        // Información sobre el envío automático
                        // Placeholder::make('send_info')
                        //     ->label('📧 Envío Automático')
                        //     ->content('Al presionar "Siguiente" se enviarán automáticamente todos los documentos al cliente y al asesor por correo electrónico.')
                        //     ->visible(fn () => $this->agreement->status !== 'documents_sent'),
                            
                        // Mensaje de documentos ya enviados
                        Placeholder::make('sent_info')
                            ->label('✅ Documentos Enviados')
                            ->content(function () {
                                $sentDate = $this->agreement->documents_sent_at ? 
                                    $this->agreement->documents_sent_at->format('d/m/Y H:i') : 
                                    'Fecha no disponible';
                                return "Los documentos fueron enviados exitosamente el {$sentDate}";
                            })
                            ->visible(fn () => $this->agreement->status === 'documents_sent')
                                    ])
                                    ->visible($this->agreement->generatedDocuments->isNotEmpty() && $this->agreement->status !== 'documents_sent'),
                
            Section::make('Documentos Enviados')
                ->description('Los documentos han sido enviados al cliente exitosamente')
                ->icon('heroicon-o-check-circle')
                ->iconColor('success')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Placeholder::make('sent_confirmation')
                                ->label('📤 Estado del Envío')
                                ->content(function () {
                                    $sentDate = $this->agreement->documents_sent_at?->format('d/m/Y H:i') ?? 'fecha no disponible';
                                    $clientName = $this->getClientName();
                                    $clientEmail = $this->getClientEmail();
                                    $docsCount = $this->agreement->generatedDocuments->count();
                                    
                                    return "✅ Enviado exitosamente el {$sentDate}<br>Cliente: {$clientName}<br>Email: {$clientEmail}<br>Documentos: {$docsCount} PDFs";
                                })
                                ->html(),
                                
                            Placeholder::make('next_steps')
                                ->label('📋 Próximos Pasos')
                                ->content('El cliente debe revisar los documentos y enviar la documentación requerida. Proceda al siguiente paso para gestionar la recepción de documentos del cliente.')
                        ]),
                        
                    // ⭐ BOTÓN para reenviar documentos si es necesario (estilo inline)
                    Placeholder::make('resend_button')
                        ->label('')
                        ->content(function () {
                            return '<div style="display: flex; justify-content: center; width: 100%; margin-top: 16px;">
                                <button wire:click="sendDocumentsToClient" 
                                        wire:confirm="¿Desea reenviar los documentos al cliente?"
                                        style="
                                            display: inline-flex; 
                                            align-items: center; 
                                            padding: 12px 24px; 
                                            background: linear-gradient(135deg, #6B7280 0%, #4B5563 100%); 
                                            color: white; 
                                            font-weight: 500; 
                                            border-radius: 12px; 
                                            border: none; 
                                            cursor: pointer; 
                                            transition: all 0.3s ease; 
                                            box-shadow: 0 4px 16px rgba(107, 114, 128, 0.3); 
                                            text-decoration: none; 
                                            font-size: 14px;
                                        "
                                        onmouseover="this.style.background=\'linear-gradient(135deg, #4B5563 0%, #374151 100%)\'; this.style.boxShadow=\'0 8px 24px rgba(107, 114, 128, 0.5)\'; this.style.transform=\'translateY(-2px) scale(1.02)\';"
                                        onmouseout="this.style.background=\'linear-gradient(135deg, #6B7280 0%, #4B5563 100%)\'; this.style.boxShadow=\'0 4px 16px rgba(107, 114, 128, 0.3)\'; this.style.transform=\'translateY(0) scale(1)\';">
                                    <svg style="width: 16px; height: 16px; margin-right: 8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    🔄 Reenviar Documentos
                                </button>
                            </div>';
                        })
                        ->html()
                ])
                ->visible($this->agreement->status === 'documents_sent'),
        ];
    }
    
    // PASO 2: Recepción de Documentos
    private function getStepTwoSchema(): array
    {
        return [
          
            Section::make('Documentos Requeridos del Cliente')
                ->description(new HtmlString(
                        'Gestionar documentos que debe proporcionar el cliente <br> <span class="font-semibold text-gray-700">Documento cargado previamente se mostrará automáticamente</span>'
                    ))                      
                ->icon('heroicon-o-clipboard-document-list')
                ->iconColor('info')
                ->headerActions([
                    // ⭐ BOTÓN usando Filament Actions nativo
                    \Filament\Actions\Action::make('downloadUpdatedChecklist')
                        ->label('Descargar Lista Actualizada')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function () {
                            return $this->downloadUpdatedChecklistAction();
                        })
                ])
                ->schema([
                    Grid::make(1)
                        ->schema([
                           
                            Section::make('DOCUMENTACIÓN TITULAR')
                                ->icon('heroicon-o-user')
                                ->description('Todos los documentos son obligatorios')
                                ->iconColor('primary')
                                ->columns(2) 
                                ->schema([
                                    FileUpload::make('holder_ine')
                                        ->label('1. INE (A color, tamaño original, no fotos)')
                                        ->required()
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                                        ->maxSize(10240)
                                        ->directory('client_documents/' . $this->agreement->id . '/titular')
                                        ->disk('private')
                                        ->image()
                                        ->imageEditor()
                                        ->placeholder('📄 Arrastra tu archivo aquí o haz clic para seleccionar')
                                        ->getUploadedFileNameForStorageUsing(function ($file) {
                                            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                                            return 'ine_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state) {
                                            $this->handleDocumentStateChange('holder_ine', 'titular', $state);
                                        }),
                                        
                                    FileUpload::make('holder_curp')
                                        ->label('2. CURP (Mes corriente)')
                                        ->required()
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                                        ->maxSize(10240)
                                        ->directory('client_documents/' . $this->agreement->id . '/titular')
                                        ->disk('private')
                                        ->image()
                                        ->imageEditor()
                                        ->getUploadedFileNameForStorageUsing(function ($file) {
                                            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                                            return 'curp_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state) {
                                            $this->handleDocumentStateChange('holder_curp', 'titular', $state);
                                        }),
                                        
                                    FileUpload::make('holder_fiscal_status')
                                        ->label('3. Constancia de Situación Fiscal (Mes corriente, completa)')
                                        ->required()
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                        ->maxSize(10240)
                                        ->directory('client_documents/' . $this->agreement->id . '/titular')
                                        ->disk('private')
                                        ->getUploadedFileNameForStorageUsing(function ($file) {
                                            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                                            return 'constancia_fiscal_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state) {
                                            $this->handleDocumentStateChange('holder_fiscal_status', 'titular', $state);
                                        }),
                                        
                                    FileUpload::make('holder_proof_address_home')
                                        ->label('4. Comprobante de Domicilio Vivienda (Mes corriente)')
                                        ->required()
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                                        ->maxSize(10240)
                                        ->directory('client_documents/' . $this->agreement->id . '/titular')
                                        ->disk('private')
                                        ->image()
                                        ->imageEditor()
                                        ->getUploadedFileNameForStorageUsing(function ($file) {
                                            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                                            return 'comprobante_domicilio_vivienda_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state) {
                                            $this->handleDocumentStateChange('holder_proof_address_home', 'titular', $state);
                                        }),
                                        
                                    FileUpload::make('holder_proof_address_titular')
                                        ->label('5. Comprobante de Domicilio Titular (Mes corriente)')
                                        ->required()
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                                        ->maxSize(10240)
                                        ->directory('client_documents/' . $this->agreement->id . '/titular')
                                        ->disk('private')
                                        ->image()
                                        ->imageEditor()
                                        ->getUploadedFileNameForStorageUsing(function ($file) {
                                            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                                            return 'comprobante_domicilio_titular_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state) {
                                            $this->handleDocumentStateChange('holder_proof_address_titular', 'titular', $state);
                                        }),
                                        
                                    FileUpload::make('holder_birth_certificate')
                                        ->label('6. Acta Nacimiento')
                                        ->required()
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                        ->maxSize(10240)
                                        ->directory('client_documents/' . $this->agreement->id . '/titular')
                                        ->disk('private')
                                        ->getUploadedFileNameForStorageUsing(function ($file) {
                                            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                                            return 'acta_nacimiento_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state) {
                                            $this->handleDocumentStateChange('holder_birth_certificate', 'titular', $state);
                                        }),
                                        
                                    FileUpload::make('holder_marriage_certificate')
                                        ->label('7. Acta Matrimonio (Si aplica)')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                        ->maxSize(10240)
                                        ->directory('client_documents/' . $this->agreement->id . '/titular')
                                        ->disk('private')
                                        ->getUploadedFileNameForStorageUsing(function ($file) {
                                            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                                            return 'acta_matrimonio_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state) {
                                            $this->handleDocumentStateChange('holder_marriage_certificate', 'titular', $state);
                                        }),
                                        
                                    FileUpload::make('holder_bank_statement')
                                        ->label('8. Carátula Estado de Cuenta Bancario con Datos Fiscales (Mes corriente)')
                                        ->required()
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                        ->maxSize(10240)
                                        ->directory('client_documents/' . $this->agreement->id . '/titular')
                                        ->disk('private')
                                        ->getUploadedFileNameForStorageUsing(function ($file) {
                                            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                                            return 'estado_cuenta_bancario_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state) {
                                            $this->handleDocumentStateChange('holder_bank_statement', 'titular', $state);
                                        }),
                                ])
                                ->collapsible(),
                                
                            Section::make('DOCUMENTACIÓN PROPIEDAD')
                                ->icon('heroicon-o-home')
                                ->description('Todos los documentos son obligatorios')
                                ->iconColor('primary')
                                ->columns(2) 
                                ->schema([
                                    FileUpload::make('property_notarial_instrument')
                                        ->label('1. Instrumento Notarial con Antecedentes Registrales (Datos Registrales y Traslado de Dominio) Escaneada, visible')
                                        ->required()
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                        ->maxSize(10240)
                                        ->directory('client_documents/' . $this->agreement->id . '/propiedad')
                                        ->disk('private')
                                        ->getUploadedFileNameForStorageUsing(function ($file) {
                                            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                                            return 'instrumento_notarial_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state) {
                                            $this->handleDocumentStateChange('property_notarial_instrument', 'propiedad', $state);
                                        }),
                                        
                                    FileUpload::make('property_tax_receipt')
                                        ->label('2. Recibo predial (Mes corriente)')
                                        ->required()
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                        ->maxSize(10240)
                                        ->directory('client_documents/' . $this->agreement->id . '/propiedad')
                                        ->disk('private')
                                        ->image()
                                        ->imageEditor()
                                        ->getUploadedFileNameForStorageUsing(function ($file) {
                                            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                                            return 'recibo_predial_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state) {
                                            $this->handleDocumentStateChange('property_tax_receipt', 'propiedad', $state);
                                        }),
                                        
                                    FileUpload::make('property_water_receipt')
                                        ->label('3. Recibo de Agua (Mes corriente)')
                                        ->required()
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                        ->maxSize(10240)
                                        ->directory('client_documents/' . $this->agreement->id . '/propiedad')
                                        ->disk('private')
                                        ->image()
                                        ->imageEditor()
                                        ->getUploadedFileNameForStorageUsing(function ($file) {
                                            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                                            return 'recibo_agua_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state) {
                                            $this->handleDocumentStateChange('property_water_receipt', 'propiedad', $state);
                                        }),
                                        
                                    FileUpload::make('property_cfe_receipt')
                                        ->label('4. Recibo CFE con datos fiscales (Mes corriente)')
                                        ->required()
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                        ->maxSize(10240)
                                        ->directory('client_documents/' . $this->agreement->id . '/propiedad')
                                        ->disk('private')
                                        ->image()
                                        ->imageEditor()
                                        ->getUploadedFileNameForStorageUsing(function ($file) {
                                            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                                            return 'recibo_cfe_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state) {
                                            $this->handleDocumentStateChange('property_cfe_receipt', 'propiedad', $state);
                                        }),
                                ])
                                ->collapsible(),
                        ]),
                        
                    Grid::make(1)
                        ->schema([
                            Checkbox::make('documents_validated')
                                ->label('Marcar todos los documentos como válidos para concluir el convenio')
                                ->helperText('Al marcar esta casilla, confirmará que todos los documentos del cliente han sido recibidos y validados correctamente.')
                                ->disabled($this->agreement->status === 'completed')
                                ->default($this->agreement->status === 'completed')
                                ->live()
                                ->afterStateUpdated(function ($state) {
                                    if ($state && $this->agreement->status !== 'completed') {
                                        $this->markDocumentsReceived();
                                    }
                                })
                        ])
                ]),
                
            // Información sobre el envío de confirmación (siempre visible)
            Section::make('Estado de Confirmación')
                ->description('Información sobre el correo de confirmación')
                ->icon(fn () => $this->agreement->documents_received_at ? 'heroicon-o-envelope-open' : 'heroicon-o-envelope')
                ->iconColor(fn () => $this->agreement->documents_received_at ? 'success' : 'warning')
                ->schema([
                    Placeholder::make('confirmation_status')
                        ->label('📧 Correo de Confirmación')
                        ->content(function () {
                            if ($this->agreement->documents_received_at) {
                                $receivedDate = $this->agreement->documents_received_at->format('Y-m-d H:i:s');
                                $clientEmail = $this->getClientEmail();
                                $advisorEmail = auth()->user()->email ?? 'No disponible';
                                
                                return "✅ <strong>Correo de confirmación enviado</strong><br>" .
                                       "📅 Fecha: {$receivedDate}<br>" .
                                       "👤 Cliente: {$clientEmail}<br>" .
                                       "🏢 Asesor: {$advisorEmail}<br>" .
                                       "📋 Estado: Convenio completado exitosamente<br>" .
                                       "🎯 <strong>Etapa: Proceso Completado Exitosamente</strong>";
                            } else {
                                return "⏳ <strong>Pendiente de envío</strong><br>" .
                                       "El correo de confirmación se enviará automáticamente al avanzar al siguiente paso.";
                            }
                        })
                        ->html()
                ]),
        ];
    }
    
   // PASO 3: Cierre Exitoso
    private function getStepThreeSchema(): array
    {
        $agreement = $this->agreement ?? new Agreement();
        $documents = $agreement->generatedDocuments ?? collect();

        // Marcar convenio como completado al llegar a este paso
        if ($agreement && $agreement->status !== 'completed') {
            $agreement->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);
        }

        return [
            Section::make('¡Convenio Finalizado con Éxito!')
                ->icon('heroicon-o-check-badge')
                ->iconColor('success')
                ->description('El proceso de gestión documental ha finalizado correctamente')
                ->schema([
                    // Mensaje de celebración
                    Placeholder::make('celebration')
                        ->content('✅ El convenio se ha completado exitosamente. Todos los documentos han sido procesados.')
                        ->columnSpanFull(),
                        
                    // Información del convenio
                    Grid::make(3)
                        ->schema([
                            Placeholder::make('completion_date')
                                ->label('📅 Fecha de Finalización')
                                ->content($agreement->completed_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i')),
                                
                            Placeholder::make('total_documents')
                                ->label('📄 Documentos Generados')
                                ->content($documents->count() . ' PDFs'),
                                
                            Placeholder::make('final_status')
                                ->label('✅ Estado Final')
                                ->content('Completado')
                        ]),
                ]),
                
                
            Section::make('💰 Valor de Cierre')
                ->icon('heroicon-o-currency-dollar')
                ->iconColor('success')
                ->description('Registrar el valor final con el que se cerró el convenio')
                ->schema([
                    // Valores de Referencia Original
                    Grid::make(3)
                        ->schema([
                            Placeholder::make('original_valor_compraventa')
                                ->label('📋 Valor CompraVenta Original')
                                ->content(fn() => $this->getOriginalValorCompraventa())
                                ->html(),
                                
                            Placeholder::make('original_comision_total')
                                ->label('💰 Comisión Total Original')
                                ->content(fn() => $this->getOriginalComisionTotal())
                                ->html(),
                                
                            Placeholder::make('original_ganancia_final')
                                ->label('💵 Ganancia Final Original')
                                ->content(fn() => $this->getOriginalGananciaFinal())
                                ->html(),
                        ])
                        ->columnSpanFull(),
                        
                    // Separador visual
                    Placeholder::make('separator')
                        ->label('')
                        ->content('<hr style="border: 1px solid #e5e7eb; margin: 16px 0;">')
                        ->html()
                        ->columnSpanFull(),
                        
                    // Valor de Cierre Final
                    Grid::make(2)
                        ->schema([
                            TextInput::make('proposal_value')
                                ->label('🎯 Valor de Propuesta Final Ofrecido')
                                ->numeric()
                                ->prefix('$')
                                ->step(0.01)
                                ->placeholder('Ej: 14896545.50')
                                ->helperText(fn() => $this->agreement->proposal_value 
                                    ? 'Valor registrado el ' . $this->agreement->proposal_saved_at?->format('d/m/Y H:i')
                                    : 'Ingrese el valor final con el que se cerró el convenio (contraoferta)'
                                )
                                ->default(fn() => $this->agreement->proposal_value ?? null)
                                ->disabled(fn() => $this->agreement->proposal_value !== null)
                                ->statePath('proposal_value'),
                                
                            Placeholder::make('proposal_status')
                                ->label('Estado de Registro')
                                ->content(fn() => $this->agreement->proposal_value 
                                    ? '✅ Valor registrado: $' . number_format($this->agreement->proposal_value, 2)
                                    : '⏳ Pendiente de registro'
                                )
                                ->html(),
                        ]),
                        
                    // Comparación de valores
                    // Grid::make(1)
                    //     ->schema([
                    //         Placeholder::make('value_comparison')
                    //             ->label('📊 Comparación de Valores')
                    //             ->content(fn() => $this->getValueComparison())
                    //             ->html()
                    //             ->visible(fn() => $this->agreement->proposal_value !== null),
                    //     ])
                    //     ->columnSpanFull(),
                        
                    Placeholder::make('save_proposal_button')
                        ->label('💾 Guardar Valor de Propuesta')
                        ->content(fn() => view('components.action-button', [
                            'icon' => 'heroicon-o-check-circle',
                            'label' => 'Guardar Valor',
                            'sublabel' => 'de Propuesta Final',
                            'color' => 'success',
                            'action' => 'saveProposalValue',
                            'confirm' => '¿Desea guardar el valor de propuesta registrado?',
                            'prevent' => false // Evitar el prevent. que causa conflictos con wire:confirm
                        ]))
                        ->visible(fn() => $this->agreement->proposal_value === null),
                ]),
                
            Section::make('Acciones Disponibles')
            ->icon('heroicon-o-wrench-screwdriver')
            ->iconColor('warning')
            ->description('Opciones para gestionar el convenio completado')
            ->schema([
                Grid::make(3)
                    ->schema([
                        // Card: Descargar Todos los Documentos
                        Placeholder::make('action_download')
                            ->label('📥 Descargar Documentos')
                            ->content(fn() => view('components.action-button', [
                                'icon' => 'heroicon-o-arrow-down-tray',
                                'label' => 'Descargar Todos',
                                'sublabel' => 'los Documentos PDF',
                                'action' => 'downloadAllDocuments',
                                'color' => 'success'
                            ])),
                            
                        // // Card: Enviar Correos
                        // Placeholder::make('action_email')
                        //     ->label('📧 Enviar por Email')
                        //     ->content(fn() => view('components.action-button', [
                        //         'icon' => 'heroicon-o-envelope',
                        //         'label' => 'Enviar Correos',
                        //         'sublabel' => 'Reenviar Documentos',
                        //         'action' => 'sendDocumentsToClient',
                        //         'color' => 'info',
                        //         'confirm' => '¿Está seguro de reenviar los documentos al cliente?'
                        //     ])),
                            
                        // Card: Regresar a Inicio
                        Placeholder::make('action_home')
                            ->label('🏠 Regresar al Dashboard')
                            ->content(fn() => view('components.action-button', [
                                'icon' => 'heroicon-o-home',
                                'label' => 'Volver al Inicio',
                                'sublabel' => 'Dashboard Principal',
                                'action' => 'returnToHome',
                                'color' => 'primary'
                            ])),
                    ]),
            ]),
        ];
    }

    private function getDocumentFields(): array
    {
        $documentSections = [];
    
        // Definimos un color temático en HEX para consistencia. Usamos un verde/lima llamativo.
        $color_bg = '#84CC16'; // Lima (lime-500)
        $color_hover = '#65A30D'; // Lima oscuro (lime-600)
        $color_text = '#1F2937'; // Gris oscuro (gray-800)
    
        foreach ($this->agreement->generatedDocuments as $document) {
            $documentSections[] = Section::make($document->formatted_type) // Eliminamos el emoji 📄
                    ->icon('heroicon-o-document-check') // Usamos el icono oficial de Heroicons
                    ->iconColor('success') // Le damos un color llamativo (verde)
                    ->description('Documento PDF generado')
                    ->schema([
                        // Se usa un Grid de 1 columna para mostrar el botón de descarga.
                        Grid::make(1) 
                            ->schema([
                                // Eliminamos el Placeholder de información y dejamos solo la acción.
    
                                Placeholder::make('document_actions_' . $document->id)
                                    ->label('Documento') // Etiqueta solicitada
                                    ->content(function () use ($document, $color_bg, $color_hover, $color_text) {
                                        $downloadUrl = route('documents.download', ['document' => $document->id]);
    
                                        // Botón con estilos INLINE para asegurar compatibilidad si Tailwind falla.
                                        // Se usa justify-content: flex-start para alinear a la izquierda.
                                        return new HtmlString('
                                            <div style="display: flex; justify-content: flex-start; gap: 8px; align-items: center; height: 100%;">
                                                <a href="' . $downloadUrl . '"
                                                    target="_self"
                                                    style="
                                                        display: inline-flex; 
                                                        align-items: center; 
                                                        padding: 10px 16px; 
                                                        background-color: ' . $color_bg . '; 
                                                        color: ' . $color_text . '; 
                                                        border: none;
                                                        border-radius: 8px; 
                                                        font-weight: 600; 
                                                        font-size: 12px;
                                                        text-transform: uppercase;
                                                        text-decoration: none;
                                                        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                                                        transition: background-color 0.15s ease-in-out;
                                                    "
                                                    onmouseover="this.style.backgroundColor=\'' . $color_hover . '\';"
                                                    onmouseout="this.style.backgroundColor=\'' . $color_bg . '\';"
                                                >
                                                    <!-- Icono SVG de Heroicons: arrow-down-tray (Descarga) -->
                                                    <svg style="width: 16px; height: 16px; margin-right: 6px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0 4.5-4.5m-4.5 4.5V3" />
                                                    </svg>
                                                    Descargar
                                                </a>
                                            </div>
                                        ');
                                    })
                                    // Aseguramos que el Placeholder de la acción ocupe 1 columna
                                    ->columnSpanFull(), // Al usar Grid::make(1), columnSpanFull es la opción más segura.
                            ])
                            // Las celdas internas de información y acción ocupan la mitad de la sección.
                            ->columnSpanFull(), 
                    ])
                    ->collapsible()
                    ->collapsed(false);
        }
    
        // RETORNAMOS UN ÚNICO GRID DE 2 COLUMNAS QUE CONTIENE TODAS LAS SECCIONES.
        return [
            Grid::make(2) // <--- ESTO FUERZA EL LAYOUT EXTERNO DE 2 COLUMNAS
                ->schema($documentSections)
                ->columnSpanFull(), // Asegura que el Grid principal use todo el ancho
        ];
    }

    // Métodos auxiliares
    private function getClientName(): string
    {
        $wizardData = $this->agreement->wizard_data ?? [];

        $holderName = $wizardData['holder_name'] ?? null;

        if (!$holderName && $this->agreement->client) {
            $holderName = $this->agreement->client->name;
        }

        return $holderName ?? 'N/A';
    }

    private function getClientEmail(): string
    {
        $wizardData = $this->agreement->wizard_data ?? [];

        $holderEmail = $wizardData['holder_email'] ?? null;

        if (!$holderEmail && $this->agreement->client) {
            $holderEmail = $this->agreement->client->email;
        }

        return $holderEmail ?? 'No disponible';
    }

    private function getClientPhone(): string
    {
        $wizardData = $this->agreement->wizard_data ?? [];

        $holderPhone = $wizardData['holder_phone'] ?? null;

        if (!$holderPhone && $this->agreement->client) {
            $holderPhone = $this->agreement->client->phone;
        }

        return $holderPhone ?? 'No disponible';
    }

    private function getPropertyAddress(): string
    {
        $wizardData = $this->agreement->wizard_data ?? [];

        $address = $wizardData['domicilio_convenio'] ?? null;

        return $address ?? 'No disponible';
    }

    private function getPropertyCommunity(): string
    {
        $wizardData = $this->agreement->wizard_data ?? [];

        $community = $wizardData['comunidad'] ?? null;

        return $community ?? 'No disponible';
    }

    private function getAgreementValue(): string
    {
        $wizardData = $this->agreement->wizard_data ?? [];

        $value = $wizardData['valor_convenio'] ?? null;

        if ($value) {
            return '$' . number_format($value, 2);
        }

        return 'No disponible';
    }

    // Métodos de acción
    public function downloadDocument(int $documentId)
    {
        try {
            $document = $this->agreement->generatedDocuments()->findOrFail($documentId);

            if (!$document->fileExists()) {
                Notification::make()
                    ->title('Error')
                    ->body('El archivo no existe')
                    ->danger()
                    ->send();
                return;
            }

            $downloadUrl = route('documents.download', ['document' => $documentId]);
            $this->dispatch('open-url', ['url' => $downloadUrl]);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Error al descargar: ' . $e->getMessage())
                ->danger()
                ->duration(7000)
                ->send();
        }
    }

    /**
     * Limpiar nombre de archivo para evitar caracteres problemáticos en ZIP
     */
    private function cleanFileName(?string $fileName): string
    {
        // Manejar valores nulos o vacíos
        if (empty($fileName)) {
            return 'documento_' . time();
        }
        
        // Enfoque ultra-simple: solo letras, números y guiones bajos
        // Convertir todo a minúsculas y remover acentos
        $cleanName = strtolower($fileName);
        $cleanName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $cleanName);
        
        // Reemplazar CUALQUIER cosa que no sea letra o número con guión bajo
        $cleanName = preg_replace('/[^a-z0-9]/', '_', $cleanName);
        
        // Remover guiones bajos múltiples
        $cleanName = preg_replace('/_+/', '_', $cleanName);
        
        // Remover guiones bajos al inicio y final
        $cleanName = trim($cleanName, '_');
        
        // Limitar longitud
        if (strlen($cleanName) > 50) {
            $cleanName = substr($cleanName, 0, 50);
        }
        
        // Asegurar que no esté vacío
        if (empty($cleanName)) {
            $cleanName = 'doc_' . time();
        }
        
        return $cleanName;
    }

    // Enviar correo de confirmación de documentos recibidos
    public function sendDocumentsReceivedConfirmation()
    {
        try {
            // Log para debugging
            \Log::info('sendDocumentsReceivedConfirmation method called', [
                'agreement_id' => $this->agreement->id,
                'user_id' => auth()->id(),
                'current_status' => $this->agreement->status
            ]);

            // Validar email del cliente
            $clientEmail = $this->getClientEmail();
            if ($clientEmail === 'No disponible' || empty($clientEmail)) {
                Notification::make()
                    ->title('❌ Email No Disponible')
                    ->body('El cliente no tiene un email registrado en el convenio.')
                    ->warning()
                    ->duration(5000)
                    ->send();
                return;
            }

            // Obtener email del asesor (usuario autenticado)
            $advisorEmail = auth()->user()->email;
            $advisorName = auth()->user()->name ?? 'Asesor';

            // Obtener documentos del cliente recibidos
            $clientDocuments = \App\Models\ClientDocument::where('agreement_id', $this->agreement->id)->get();

            // Mostrar notificación de inicio de envío
            Notification::make()
                ->title('📤 Enviando Confirmación...')
                ->body("Enviando confirmación de documentos recibidos a {$clientEmail} y al asesor {$advisorName}")
                ->info()
                ->duration(3000)
                ->send();

            // Log antes de enviar el correo
            \Log::info('About to send confirmation email', [
                'agreement_id' => $this->agreement->id,
                'client_email' => $clientEmail,
                'advisor_email' => $advisorEmail,
                'documents_count' => $clientDocuments->count()
            ]);

            // Enviar el correo de confirmación al cliente con copia al asesor (inmediatamente, no en cola)
            try {
                Mail::to($clientEmail)
                    ->cc($advisorEmail)
                    ->send(new \App\Mail\DocumentsReceivedConfirmationMail($this->agreement, $clientDocuments));
                    
                \Log::info('Mail::send completed successfully');
            } catch (\Exception $mailException) {
                \Log::error('Error in Mail::send', [
                    'error' => $mailException->getMessage(),
                    'trace' => $mailException->getTraceAsString()
                ]);
                throw $mailException;
            }

            \Log::info('Documents received confirmation email sent successfully', [
                'agreement_id' => $this->agreement->id,
                'client_email' => $clientEmail,
                'advisor_email' => $advisorEmail,
                'documents_count' => $clientDocuments->count()
            ]);

        } catch (\Exception $e) {
            // Log del error para debugging
            \Log::error('Error sending documents received confirmation', [
                'agreement_id' => $this->agreement->id,
                'client_email' => $this->getClientEmail(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->title('❌ Error al Enviar Confirmación')
                ->body('Ocurrió un error al enviar la confirmación. Por favor, inténtelo nuevamente.')
                ->danger()
                ->duration(7000)
                ->send();

            throw $e;
        }
    }

    public function sendDocumentsToClient()
    {
        // Log para debugging
        \Log::info('sendDocumentsToClient method called', [
            'agreement_id' => $this->agreement->id,
            'user_id' => auth()->id()
        ]);
        
        try {
            // Validar que existen documentos generados
            if ($this->agreement->generatedDocuments->isEmpty()) {
                Notification::make()
                    ->title('❌ Sin Documentos')
                    ->body('No hay documentos generados para enviar. Por favor, genere los documentos primero.')
                    ->warning()
                    ->duration(5000)
                    ->send();
                return;
            }

            // Validar email del cliente
            $clientEmail = $this->getClientEmail();
            if ($clientEmail === 'No disponible' || empty($clientEmail)) {
                Notification::make()
                    ->title('❌ Email No Disponible')
                    ->body('El cliente no tiene un email registrado en el convenio. Por favor, actualice los datos del cliente.')
                    ->warning()
                    ->duration(5000)
                    ->send();
                return;
            }

            // Validar que los archivos PDF existen físicamente
            $documentsWithFiles = $this->agreement->generatedDocuments->filter(function ($document) {
                return $document->fileExists();
            });

            if ($documentsWithFiles->isEmpty()) {
                Notification::make()
                    ->title('❌ Archivos No Encontrados')
                    ->body('Los archivos PDF no se encontraron en el servidor. Por favor, regenere los documentos.')
                    ->warning()
                    ->duration(5000)
                    ->send();
                return;
            }

            // Obtener email del asesor (usuario autenticado)
            $advisorEmail = auth()->user()->email;
            $advisorName = auth()->user()->name ?? 'Asesor';

            // Mostrar notificación de inicio de envío
            Notification::make()
                ->title('📤 Enviando Documentos...')
                ->body("Enviando {$documentsWithFiles->count()} documentos a {$clientEmail} y al asesor {$advisorName}")
                ->info()
                ->duration(3000)
                ->send();

            // Actualizar estado del convenio
            $this->agreement->update([
                'status' => 'documents_sent',
                'documents_sent_at' => now(),
            ]);

            // Enviar el correo al cliente con copia al asesor
            Mail::to($clientEmail)
                ->cc($advisorEmail)
                ->send(new DocumentsReadyMail($this->agreement));

            // Notificación de éxito
            Notification::make()
                ->title('✅ Documentos Enviados Exitosamente')
                ->body("Los documentos han sido enviados a {$clientEmail} y al asesor {$advisorName} ({$advisorEmail}). Ambos recibirán {$documentsWithFiles->count()} archivos PDF adjuntos.")
                ->success()
                ->duration(7000)
                ->send();

            // Emitir evento para restaurar el botón
            $this->dispatch('email-sent');

            // No redirigir cuando se llama desde el wizard
            // El wizard manejará el avance de paso

        } catch (\Exception $e) {
            // Log del error para debugging
            \Log::error('Error sending documents to client', [
                'agreement_id' => $this->agreement->id,
                'client_email' => $this->getClientEmail(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->title('❌ Error al Enviar Documentos')
                ->body('Ocurrió un error al enviar los documentos. Por favor, inténtelo nuevamente.')
                ->danger()
                ->duration(7000)
                ->send();

            // Emitir evento para restaurar el botón en caso de error
            $this->dispatch('email-sent');
        }
    }

    public function markDocumentsReceived()
    {
        try {
            $this->agreement->update([
                'status' => 'completed',
                'wizard2_current_step' => 3,
                'completion_percentage' => 100,
                'completed_at' => now(),
            ]);

            Notification::make()
                ->title('🎉 Convenio Completado')
                ->body('El convenio ha sido finalizado exitosamente. Todos los documentos han sido recibidos y validados.')
                ->success()
                ->duration(5000)
                ->send();

            $this->currentStep = 3;
            return $this->redirect(request()->url());

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error al Completar')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function markAsCompleted(): void
    {
        try {
            $this->agreement->update([
                'status' => 'completed',
                'completed_at' => now(),
                'wizard2_current_step' => 3,
            ]);

            Notification::make()
                ->title('🎉 Convenio Completado')
                ->body('El convenio ha sido marcado como completado exitosamente')
                ->success()
                ->duration(5000)
                ->send();

        } catch (\Exception $e) {

            Notification::make()
                ->title('Error')
                ->body('Error al completar el convenio: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function downloadAllDocuments()
    {
        try {
            if (!$this->agreement) {
                throw new \Exception('No se encontró el convenio');
            }

            $generatedDocuments = $this->agreement->generatedDocuments;
            $clientDocuments = \App\Models\ClientDocument::where('agreement_id', $this->agreement->id)->get();
            
            // Log para debugging
            \Log::info('downloadAllDocuments - Documents found', [
                'agreement_id' => $this->agreement->id,
                'generated_count' => $generatedDocuments->count(),
                'client_count' => $clientDocuments->count(),
                'generated_documents' => $generatedDocuments->pluck('document_name', 'file_path')->toArray(),
                'client_documents' => $clientDocuments->pluck('document_name', 'file_path')->toArray()
            ]);
            
            if ($generatedDocuments->isEmpty() && $clientDocuments->isEmpty()) {
                Notification::make()
                    ->title('Sin Documentos')
                    ->body('No hay documentos disponibles para descargar')
                    ->warning()
                    ->send();
                return;
            }

            // Crear un ZIP con todos los documentos (nombre ultra-simple)
            $timestamp = time();
            $zipFileName = 'convenio_' . $this->agreement->id . '_' . $timestamp . '.zip';
            $zipPath = storage_path('app/temp/' . $zipFileName);
            
            \Log::info('Creating ZIP file', [
                'zip_name' => $zipFileName,
                'full_path' => $zipPath,
                'agreement_id' => $this->agreement->id
            ]);
            
            // Crear directorio temporal si no existe
            if (!file_exists(dirname($zipPath))) {
                mkdir(dirname($zipPath), 0755, true);
            }

            $zip = new ZipArchive();
            
            \Log::info('Attempting to create ZIP', [
                'zip_path' => $zipPath,
                'zip_filename' => $zipFileName
            ]);
            
            $zipResult = $zip->open($zipPath, ZipArchive::CREATE);
            if ($zipResult === TRUE) {
                
                $addedFiles = 0;
                
                // Agregar documentos generados (PDFs del sistema)
                if (!$generatedDocuments->isEmpty()) {
                    foreach ($generatedDocuments as $document) {
                        $filePath = Storage::disk('private')->path($document->file_path);
                        \Log::info('Processing generated document', [
                            'document_name' => $document->document_name,
                            'file_path' => $document->file_path,
                            'full_path' => $filePath,
                            'exists' => file_exists($filePath)
                        ]);
                        
                        if (file_exists($filePath)) {
                            // Limpiar nombre del documento para evitar caracteres problemáticos
                            $documentName = $document->document_name ?? 'documento_generado_' . $document->id;
                            $cleanDocumentName = $this->cleanFileName($documentName);
                            $zipFileName = 'generados/' . $cleanDocumentName . '.pdf';
                            
                            \Log::info('Adding generated file to ZIP', [
                                'original_name' => $documentName,
                                'clean_name' => $cleanDocumentName,
                                'zip_filename' => $zipFileName,
                                'source_path' => $filePath
                            ]);
                            
                            try {
                                $result = $zip->addFile($filePath, $zipFileName);
                            } catch (\Exception $e) {
                                \Log::error('Error adding generated file to ZIP', [
                                    'error' => $e->getMessage(),
                                    'zip_filename' => $zipFileName,
                                    'source_path' => $filePath
                                ]);
                                throw $e;
                            }
                            if ($result) {
                                $addedFiles++;
                                \Log::info('Added generated document to ZIP', ['file' => $zipFileName]);
                            } else {
                                \Log::error('Failed to add generated document to ZIP', ['file' => $zipFileName]);
                            }
                        } else {
                            \Log::warning('Generated document file not found', ['path' => $filePath]);
                        }
                    }
                }
                
                // Agregar documentos del cliente (subidos en paso 2)
                if (!$clientDocuments->isEmpty()) {
                    foreach ($clientDocuments as $document) {
                        $filePath = Storage::disk('private')->path($document->file_path);
                        \Log::info('Processing client document', [
                            'document_name' => $document->document_name,
                            'file_path' => $document->file_path,
                            'full_path' => $filePath,
                            'exists' => file_exists($filePath)
                        ]);
                        
                        if (file_exists($filePath)) {
                            // Obtener extensión del archivo original
                            $extension = pathinfo($document->file_path, PATHINFO_EXTENSION);
                            // Limpiar nombre del documento para evitar caracteres problemáticos
                            $documentName = $document->document_name ?? 'documento_cliente_' . $document->id;
                            $cleanDocumentName = $this->cleanFileName($documentName);
                            $fileName = $cleanDocumentName . '.' . $extension;
                            $zipFileName = 'cliente/' . $fileName;
                            
                            \Log::info('Adding client file to ZIP', [
                                'original_name' => $documentName,
                                'clean_name' => $cleanDocumentName,
                                'extension' => $extension,
                                'zip_filename' => $zipFileName,
                                'source_path' => $filePath
                            ]);
                            
                            try {
                                $result = $zip->addFile($filePath, $zipFileName);
                            } catch (\Exception $e) {
                                \Log::error('Error adding client file to ZIP', [
                                    'error' => $e->getMessage(),
                                    'zip_filename' => $zipFileName,
                                    'source_path' => $filePath
                                ]);
                                throw $e;
                            }
                            if ($result) {
                                $addedFiles++;
                                \Log::info('Added client document to ZIP', ['file' => $zipFileName]);
                            } else {
                                \Log::error('Failed to add client document to ZIP', ['file' => $zipFileName]);
                            }
                        } else {
                            \Log::warning('Client document file not found', ['path' => $filePath]);
                        }
                    }
                }
                
                $zip->close();

                $totalDocuments = $generatedDocuments->count() + $clientDocuments->count();
                
                \Log::info('ZIP creation completed', [
                    'total_documents_found' => $totalDocuments,
                    'files_added_to_zip' => $addedFiles,
                    'zip_path' => $zipPath,
                    'zip_exists' => file_exists($zipPath),
                    'zip_size' => file_exists($zipPath) ? filesize($zipPath) : 0
                ]);
                
                Notification::make()
                    ->title('📦 Descarga Iniciada')
                    ->body("Se han preparado {$addedFiles} de {$totalDocuments} documentos para descarga ({$generatedDocuments->count()} generados + {$clientDocuments->count()} del cliente)")
                    ->success()
                    ->duration(5000)
                    ->send();

                // Limpiar el nombre del archivo para la descarga
                $downloadFileName = $this->cleanFileName(pathinfo($zipFileName, PATHINFO_FILENAME)) . '.zip';
                
                \Log::info('Starting download', [
                    'zip_path' => $zipPath,
                    'original_filename' => $zipFileName,
                    'download_filename' => $downloadFileName
                ]);
                
                // Descargar el archivo con nombre limpio y headers seguros
                return response()->download($zipPath, $downloadFileName, [
                    'Content-Type' => 'application/zip',
                    'Content-Disposition' => 'attachment; filename="' . $downloadFileName . '"',
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Expires' => '0'
                ])->deleteFileAfterSend();
            } else {
                $errorMessage = 'No se pudo crear el archivo ZIP. Código de error: ' . $zipResult;
                \Log::error('ZIP creation failed', [
                    'zip_result' => $zipResult,
                    'zip_path' => $zipPath,
                    'zip_filename' => $zipFileName
                ]);
                throw new \Exception($errorMessage);
            }

        } catch (\Exception $e) {

            Notification::make()
                ->title('❌ Error en Descarga')
                ->body('Error al preparar la descarga: ' . $e->getMessage())
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

        // Redirigir al dashboard principal
        return $this->redirect('/admin');
    }

    /**
     * Descarga el checklist actualizado con documentos marcados
     */
    public function downloadUpdatedChecklist()
    {
        try {
            if (!$this->agreement) {
                throw new \Exception('No se encontró el convenio');
            }

            // Obtener documentos cargados del cliente
            $uploadedDocuments = ClientDocument::where('agreement_id', $this->agreement->id)
                ->pluck('document_type')
                ->toArray();

            // Generar PDF con datos actualizados usando el servicio
            $pdfService = app(PdfGenerationService::class);
            
            // Generar checklist con flag de actualización
            $pdf = $pdfService->generateChecklist(
                $this->agreement,
                $uploadedDocuments, // Lista de tipos de documentos ya cargados
                true // Flag: isUpdatedVersion
            );

            // Nombre del archivo con timestamp
            $fileName = 'checklist_actualizado_' . $this->agreement->id . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';

            // Notificación de éxito
            Notification::make()
                ->title('📋 Lista Actualizada Generada')
                ->body('El checklist con documentos marcados ha sido generado exitosamente')
                ->success()
                ->duration(4000)
                ->send();

            // Descargar PDF
            return response()->streamDownload(
                fn() => print($pdf->output()),
                $fileName,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
                ]
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

    private function saveClientDocument(string $fieldName, $filePath, string $documentName, string $category): void
    {
        try {

            $documentTypeMap = [
                'holder_ine' => 'titular_ine',
                'holder_curp' => 'titular_curp',
                'holder_fiscal_status' => 'titular_constancia_fiscal',
                'holder_proof_address_home' => 'titular_comprobante_domicilio_vivienda',
                'holder_proof_address_titular' => 'titular_comprobante_domicilio_titular',
                'holder_birth_certificate' => 'titular_acta_nacimiento',
                'holder_marriage_certificate' => 'titular_acta_matrimonio',
                'holder_bank_statement' => 'titular_estado_cuenta_bancario',
                'property_notarial_instrument' => 'propiedad_instrumento_notarial',
                'property_tax_receipt' => 'propiedad_recibo_predial',
                'property_water_receipt' => 'propiedad_recibo_agua',
                'property_cfe_receipt' => 'propiedad_recibo_cfe',
            ];

            $documentType = $documentTypeMap[$fieldName] ?? $fieldName;

            $fileSize = null;
            $fileName = null;
            $finalFilePath = null;

            if (is_string($filePath) && !empty($filePath)) {
                $finalFilePath = $filePath;
                $fileName = basename($filePath);
                if (Storage::disk('private')->exists($filePath)) {
                    $fileSize = Storage::disk('private')->size($filePath);
                }
            } elseif (is_array($filePath) && !empty($filePath)) {
                $firstFile = $filePath[0];
                if (!empty($firstFile)) {
                    $finalFilePath = $firstFile;
                    $fileName = basename($firstFile);
                    if (Storage::disk('private')->exists($firstFile)) {
                        $fileSize = Storage::disk('private')->size($firstFile);
                    }
                }
            } elseif (is_object($filePath)) {
                if (method_exists($filePath, 'getClientOriginalName')) {
                    $fileName = $filePath->getClientOriginalName();
                    $finalFilePath = $filePath->store('convenios/' . $this->agreement->id . '/client_documents/' . $category, 'private');
                    $fileSize = $filePath->getSize();
                } elseif (method_exists($filePath, 'getRealPath')) {
                    $fileName = basename($filePath->getRealPath());
                    $finalFilePath = $filePath->getRealPath();
                    $fileSize = filesize($filePath->getRealPath());
                } else {
                    $properties = get_object_vars($filePath);
                    throw new \Exception('Objeto no reconocido: ' . get_class($filePath));
                }
            }

            if (empty($finalFilePath)) {
                throw new \Exception("No se pudo obtener la ruta del archivo. Tipo recibido: " . gettype($filePath));
            }

            if (empty($fileName)) {
                $extension = pathinfo($finalFilePath, PATHINFO_EXTENSION) ?: 'pdf';
                $fileName = $documentName . '_' . time() . '.' . $extension;
            }

            // Buscar si ya existe un documento de este tipo para evitar duplicados
            $existingDocument = ClientDocument::where('agreement_id', $this->agreement->id)
                ->where('document_type', $documentType)
                ->first();

            if ($existingDocument) {
                // Eliminar el archivo anterior del disco
                if (!empty($existingDocument->file_path) && Storage::disk('private')->exists($existingDocument->file_path)) {
                    Storage::disk('private')->delete($existingDocument->file_path);
                }
                
                // Actualizar el registro existente
                $existingDocument->update([
                    'document_name' => $documentName,
                    'file_path' => $finalFilePath,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'uploaded_at' => now(),
                ]);
            } else {
                // Crear nuevo registro si no existe
                ClientDocument::create([
                    'agreement_id' => $this->agreement->id,
                    'document_type' => $documentType,
                    'document_name' => $documentName,
                    'file_path' => $finalFilePath,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'category' => $category,
                    'uploaded_at' => now(),
                ]);
            }

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Maneja cambios de estado de documentos SIN debounce para evitar parpadeo
     */
    private function handleDocumentStateChange(string $fieldName, string $category, $state): void
    {
        if ($state) {
            // Documento subido - guardar inmediatamente
            $this->saveClientDocument($fieldName, $state, $this->getDocumentDisplayName($fieldName), $category);
        } else {
            // Documento eliminado - procesar inmediatamente SIN cola
            $this->deleteClientDocumentImmediate($fieldName, $category);
        }
    }

    /**
     * Obtiene el nombre de visualización del documento
     */
    private function getDocumentDisplayName(string $fieldName): string
    {
        $displayNames = [
            'holder_ine' => 'INE',
            'holder_curp' => 'CURP',
            'holder_fiscal_status' => 'Constancia de Situación Fiscal',
            'holder_proof_address_home' => 'Comprobante de Domicilio Vivienda',
            'holder_proof_address_titular' => 'Comprobante de Domicilio Titular',
            'holder_birth_certificate' => 'Acta Nacimiento',
            'holder_marriage_certificate' => 'Acta Matrimonio',
            'holder_bank_statement' => 'Carátula Estado de Cuenta Bancario',
            'property_notarial_instrument' => 'Instrumento Notarial',
            'property_tax_receipt' => 'Recibo Predial',
            'property_water_receipt' => 'Recibo de Agua',
            'property_cfe_receipt' => 'Recibo CFE',
        ];

        return $displayNames[$fieldName] ?? $fieldName;
    }

    /**
     * Elimina un documento inmediatamente sin cola para evitar parpadeo
     */
    private function deleteClientDocumentImmediate(string $fieldName, string $category): void
    {
        // Crear clave única para evitar eliminaciones duplicadas
        $deletionKey = $this->agreement->id . '_' . $fieldName;
        
        // Si ya se está procesando esta eliminación, salir
        if (isset($this->processingDeletions[$deletionKey])) {
            return;
        }
        
        // Marcar como en proceso
        $this->processingDeletions[$deletionKey] = true;
        
        try {
            // Mapeo de nombres de campo a tipos de documento
            $documentTypeMap = [
                'holder_ine' => 'titular_ine',
                'holder_curp' => 'titular_curp',
                'holder_fiscal_status' => 'titular_constancia_fiscal',
                'holder_proof_address_home' => 'titular_comprobante_domicilio_vivienda',
                'holder_proof_address_titular' => 'titular_comprobante_domicilio_titular',
                'holder_birth_certificate' => 'titular_acta_nacimiento',
                'holder_marriage_certificate' => 'titular_acta_matrimonio',
                'holder_bank_statement' => 'titular_estado_cuenta_bancario',
                'property_notarial_instrument' => 'propiedad_instrumento_notarial',
                'property_tax_receipt' => 'propiedad_recibo_predial',
                'property_water_receipt' => 'propiedad_recibo_agua',
                'property_cfe_receipt' => 'propiedad_recibo_cfe',
            ];

            $documentType = $documentTypeMap[$fieldName] ?? $fieldName;

            // Buscar el documento en la base de datos
            $clientDocument = ClientDocument::where('agreement_id', $this->agreement->id)
                ->where('document_type', $documentType)
                ->first();

            if ($clientDocument) {
                $documentName = $clientDocument->document_name;
                
                // Eliminar el archivo físico del disco
                if (!empty($clientDocument->file_path) && Storage::disk('private')->exists($clientDocument->file_path)) {
                    Storage::disk('private')->delete($clientDocument->file_path);
                }

                // Eliminar el registro de la base de datos
                $clientDocument->delete();

                // Notificación de eliminación exitosa
                Notification::make()
                    ->title('🗑️ Documento Eliminado')
                    ->body('El documento "' . $documentName . '" ha sido eliminado correctamente')
                    ->success()
                    ->duration(3000)
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error al Eliminar')
                ->body('Error al eliminar el documento: ' . $e->getMessage())
                ->danger()
                ->duration(4000)
                ->send();
        } finally {
            // Liberar el bloqueo
            unset($this->processingDeletions[$deletionKey]);
        }
    }

    /**
     * Método legacy - mantenido para compatibilidad
     */
    private function deleteClientDocument(string $fieldName, string $category): void
    {
        $this->deleteClientDocumentImmediate($fieldName, $category);
    }

    /**
     * Carga los documentos del cliente desde la base de datos
     */
    private function loadClientDocuments(): array
    {
        $documents = [];
        
        // Mapeo de tipos de documento a nombres de campo
        $fieldMap = [
            'titular_ine' => 'holder_ine',
            'titular_curp' => 'holder_curp',
            'titular_constancia_fiscal' => 'holder_fiscal_status',
            'titular_comprobante_domicilio_vivienda' => 'holder_proof_address_home',
            'titular_comprobante_domicilio_titular' => 'holder_proof_address_titular',
            'titular_acta_nacimiento' => 'holder_birth_certificate',
            'titular_acta_matrimonio' => 'holder_marriage_certificate',
            'titular_estado_cuenta_bancario' => 'holder_bank_statement',
            'propiedad_instrumento_notarial' => 'property_notarial_instrument',
            'propiedad_recibo_predial' => 'property_tax_receipt',
            'propiedad_recibo_agua' => 'property_water_receipt',
            'propiedad_recibo_cfe' => 'property_cfe_receipt',
        ];

        // Obtener documentos del cliente desde la base de datos
        $clientDocuments = ClientDocument::where('agreement_id', $this->agreement->id)->get();
        $documentsToDelete = [];

        foreach ($clientDocuments as $document) {
            $fieldName = $fieldMap[$document->document_type] ?? null;
            if ($fieldName && !empty($document->file_path)) {
                // Verificar que el archivo existe físicamente
                if (Storage::disk('private')->exists($document->file_path)) {
                    $documents[$fieldName] = [$document->file_path];
                } else {
                    // El archivo no existe físicamente, marcar para eliminar de BD
                    $documentsToDelete[] = $document->id;
                }
            }
        }

        // Limpiar registros huérfanos (que no tienen archivo físico)
        if (!empty($documentsToDelete)) {
            ClientDocument::whereIn('id', $documentsToDelete)->delete();
        }

        return $documents;
    }

    private function loadExistingDocuments(): void
    {
        // Método legacy - ahora usamos loadClientDocuments()
    }

    /**
     * Limpia archivos huérfanos del disco que no tienen registro en BD
     */
    private function cleanOrphanFiles(): void
    {
        try {
            $clientDocumentPaths = [
                'client_documents/' . $this->agreement->id . '/titular',
                'client_documents/' . $this->agreement->id . '/propiedad'
            ];

            // Obtener todos los file_path de la BD para este convenio
            $dbFilePaths = ClientDocument::where('agreement_id', $this->agreement->id)
                ->pluck('file_path')
                ->toArray();

            foreach ($clientDocumentPaths as $path) {
                if (Storage::disk('private')->exists($path)) {
                    $files = Storage::disk('private')->files($path);
                    
                    foreach ($files as $file) {
                        // Si el archivo no está en la BD, eliminarlo
                        if (!in_array($file, $dbFilePaths)) {
                            Storage::disk('private')->delete($file);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Silencioso - no queremos interrumpir el flujo por limpieza
        }
    }

    /**
     * Método eliminado para evitar parpadeo - Filament maneja automáticamente
     */

    /**
     * Método mejorado de guardado automático para Wizard 2
     */
    public function saveStepData(int $step): void
    {
        try {
            
            if (!$this->agreement) {
                return;
            }

            // CRÍTICO: Obtener datos sin validación para evitar errores de campos obligatorios
            try {
                $formData = $this->form->getRawState();
            } catch (\Exception $e) {
                // Si falla getRawState(), usar los datos actuales
                $formData = $this->data ?? [];
            }
            
            // Actualizar $this->data con los datos del formulario
            $this->data = array_merge($this->data ?? [], $formData);
            

            // Actualizar datos del convenio con manejo de errores
            try {
                $updated = $this->agreement->update([
                    'current_wizard' => 2,
                    'wizard2_current_step' => $step,
                    'wizard_data' => array_merge($this->agreement->wizard_data ?? [], $this->data),
                    'updated_at' => now(),
                ]);


            } catch (\Exception $e) {
                
                Notification::make()
                    ->title('⚠️ Error de guardado')
                    ->body('Error al guardar en BD: ' . $e->getMessage())
                    ->danger()
                    ->duration(8000)
                    ->send();
                return;
            }

            // Mostrar notificación de guardado automático
            if ($step >= 1) {
                Notification::make()
                    ->title("Guardando")
                    ->body("Se ha guardado el paso #{$step}")
                    ->icon('heroicon-o-server')
                    ->success()
                    ->duration(4000)
                    ->send();
                    
            }

        } catch (\Exception $e) {
            
            Notification::make()
                ->title('Error inesperado')
                ->body('Error en saveStepData: ' . $e->getMessage())
                ->danger()
                ->duration(8000)
                ->send();
        }
    }


    // protected function getActions(): array
    // {
    //     return [
    //         Action::make('viewClientData')
    //             ->label('Ver Datos del Cliente')
    //             ->icon('heroicon-o-user')
    //             ->color('primary')
    //             ->modalHeading('Datos Completos del Cliente')
    //             ->modalDescription('Información detallada del convenio')
    //             ->modalContent(fn () => $this->getClientDataModalContent())
    //             ->modalWidth('7xl')
    //             ->visible(fn () => $this->agreement && $this->agreement->wizard_data),
    //     ];
    // }

    // protected function getClientDataModalContent()
    // {
    //     if (!$this->agreement || !$this->agreement->wizard_data) {
    //         return view('components.no-data-available');
    //     }

    //     return view('components.client-data-modal-content', [
    //         'agreement' => $this->agreement,
    //         'data' => $this->agreement->wizard_data
    //     ]);
    // }

    /**
     * Método de acción para descargar checklist actualizado usando Filament Actions
     */
    public function downloadUpdatedChecklistAction()
    {
        try {
            $agreementId = $this->agreement->id;
            
            // Notificación de inicio
            Notification::make()
                ->title('Generando PDF...')
                ->body('El checklist actualizado se está generando')
                ->info()
                ->send();

            // Redirigir a la descarga
            return redirect()->route('download.updated.checklist', ['agreement' => $agreementId]);
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al Generar')
                ->body('No se pudo generar el checklist: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Guarda el valor de propuesta en el convenio sin afectar el wizard
     */
    public function saveProposalValue(): void
    {
        try {
            \Log::info('saveProposalValue method called');
            
            // Obtener el valor de la propiedad pública o del formulario
            $proposalValue = $this->proposal_value;
            
            // Si no está en la propiedad, intentar obtenerlo del formulario
            if (is_null($proposalValue)) {
                $formData = $this->form->getRawState();
                $proposalValue = $formData['proposal_value'] ?? null;
            }
            
            \Log::info('Proposal value from property: ' . $this->proposal_value);
            \Log::info('Final proposal value: ' . $proposalValue);

            if (is_null($proposalValue) || $proposalValue === '' || $proposalValue <= 0) {
                Notification::make()
                    ->title('❌ Error de Validación')
                    ->body('Debe ingresar un valor válido antes de guardar.')
                    ->danger()
                    ->send();
                return;
            }

            // Actualizar los valores en la base de datos
            $this->agreement->update([
                'proposal_value' => $proposalValue,
                'proposal_saved_at' => now(),
                'wizard2_current_step' => 3,
            ]);

            // Enviar notificación de éxito
            Notification::make()
                ->title('✅ Valor Guardado Exitosamente')
                ->body('El Valor de Propuesta Final ha sido registrado en el convenio.')
                ->success()
                ->duration(4000)
                ->send();

            // Refrescar el estado del formulario desde la base de datos para mostrar el valor actualizado
            $this->agreement->refresh();
            
            // Recargar documentos del cliente y agregar el proposal_value actualizado
            $existingDocuments = $this->loadClientDocuments();
            $existingDocuments['proposal_value'] = $this->agreement->proposal_value;
            $this->form->fill($existingDocuments);

        } catch (\Exception $e) {
            \Log::error('Error al guardar proposal_value: ' . $e->getMessage());
            
            Notification::make()
                ->title('❌ Error al Guardar')
                ->body('Ocurrió un error al intentar registrar el valor: ' . $e->getMessage())
                ->danger()
                ->duration(7000)
                ->send();
        }
    }

    /**
     * Obtiene el valor CompraVenta original del wizard
     */
    private function getOriginalValorCompraventa(): string
    {
        $wizardData = $this->agreement->wizard_data ?? [];
        $valorCompraventa = $wizardData['valor_compraventa'] ?? $wizardData['valor_convenio'] ?? null;
        
        if ($valorCompraventa) {
            $valorCompraventa = (float) str_replace(['$', ','], '', $valorCompraventa);
            return '<span style="color: #059669; font-weight: 600; font-size: 16px;">$' . number_format($valorCompraventa, 2) . '</span>';
        }
        
        return '<span style="color: #6B7280;">No disponible</span>';
    }

    /**
     * Obtiene la comisión total original del wizard
     */
    private function getOriginalComisionTotal(): string
    {
        $wizardData = $this->agreement->wizard_data ?? [];
        $comisionTotal = $wizardData['comision_total_pagar'] ?? null;
        
        if ($comisionTotal) {
            $comisionTotal = (float) str_replace(['$', ','], '', $comisionTotal);
            return '<span style="color: #DC2626; font-weight: 600; font-size: 16px;">$' . number_format($comisionTotal, 2) . '</span>';
        }
        
        return '<span style="color: #6B7280;">No disponible</span>';
    }

    /**
     * Obtiene la ganancia final original del wizard
     */
    private function getOriginalGananciaFinal(): string
    {
        $wizardData = $this->agreement->wizard_data ?? [];
        $gananciaFinal = $wizardData['ganancia_final'] ?? null;
        
        if ($gananciaFinal) {
            $gananciaFinal = (float) str_replace(['$', ','], '', $gananciaFinal);
            return '<span style="color: #7C3AED; font-weight: 600; font-size: 16px;">$' . number_format($gananciaFinal, 2) . '</span>';
        }
        
        return '<span style="color: #6B7280;">No disponible</span>';
    }

    /**
     * Genera la comparación entre valores originales y finales
     */
    private function getValueComparison(): string
    {
        if (!$this->agreement->proposal_value) {
            return '';
        }

        $wizardData = $this->agreement->wizard_data ?? [];
        $valorOriginal = $wizardData['valor_compraventa'] ?? $wizardData['valor_convenio'] ?? 0;
        $valorFinal = $this->agreement->proposal_value;
        
        // Convertir a números y validar
        $valorOriginal = is_numeric($valorOriginal) ? (float) $valorOriginal : 0;
        $valorFinal = is_numeric($valorFinal) ? (float) $valorFinal : 0;
        
        if ($valorOriginal <= 0 || $valorFinal <= 0) {
            return '<div style="padding: 12px; background: #FEF3C7; border: 1px solid #F59E0B; border-radius: 8px; color: #92400E;">
                        <strong>⚠️ Advertencia:</strong> No se encontraron valores válidos para comparar
                    </div>';
        }

        $diferencia = $valorFinal - $valorOriginal;
        $porcentajeDiferencia = (($diferencia / $valorOriginal) * 100);
        
        $colorDiferencia = $diferencia >= 0 ? '#059669' : '#DC2626'; // Verde si es positivo, rojo si es negativo
        $iconoDiferencia = $diferencia >= 0 ? '📈' : '📉';
        $textoDiferencia = $diferencia >= 0 ? 'Mayor' : 'Menor';
        
        return '<div style="padding: 16px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 12px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 12px;">
                        <div style="text-align: center;">
                            <div style="font-size: 12px; color: #6B7280; margin-bottom: 4px;">VALOR ORIGINAL</div>
                            <div style="font-size: 18px; font-weight: 600; color: #374151;">$' . number_format($valorOriginal, 2) . '</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 12px; color: #6B7280; margin-bottom: 4px;">VALOR FINAL</div>
                            <div style="font-size: 18px; font-weight: 600; color: #374151;">$' . number_format($valorFinal, 2) . '</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 12px; color: #6B7280; margin-bottom: 4px;">DIFERENCIA</div>
                            <div style="font-size: 18px; font-weight: 600; color: ' . $colorDiferencia . ';">' . $iconoDiferencia . ' $' . number_format(abs($diferencia), 2) . '</div>
                        </div>
                    </div>
                    <div style="text-align: center; padding: 8px; background: white; border-radius: 8px; border: 1px solid #E5E7EB;">
                        <span style="color: ' . $colorDiferencia . '; font-weight: 600;">' . $textoDiferencia . ' en ' . number_format(abs($porcentajeDiferencia), 2) . '%</span>
                        ' . ($diferencia >= 0 ? '(Contraoferta exitosa)' : '(Descuento aplicado)') . '
                    </div>
                </div>';
    }
}
