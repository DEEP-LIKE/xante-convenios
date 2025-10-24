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

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationLabel = 'Gestión de Documentos';
    protected static ?string $title = 'Gestión de Documentos del Convenio';
    protected static ?string $slug = 'manage-documents/{agreement?}';
    protected static ?int $navigationSort = 5;
    protected static bool $shouldRegisterNavigation = false;

    public string $view = 'filament.pages.manage-documents';

    public ?Agreement $agreement = null;
    public array $data = [];

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
                    ->description('Generar y enviar documentos al cliente')
                    ->icon('heroicon-o-paper-airplane')
                    ->completedIcon('heroicon-o-check-circle')
                    ->schema($this->getStepOneSchema())
                    ->afterValidation(function () {
                        $this->saveStepData(1);
                    }),

                Step::make('Recepción de Documentos')
                    ->description('Recibir y validar documentos del cliente')
                    ->icon('heroicon-o-document-arrow-up')
                    ->completedIcon('heroicon-o-check-circle')
                    ->schema($this->getStepTwoSchema())
                    ->afterValidation(function () {
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
            ->nextAction(fn (Action $action) => $action->label('Siguiente'))
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
                                    
                                    return "Valor: {$agreementValue}<br>Comunidad: {$community}<br>Creado: {$createdDate}";
                                })
                                ->html(),
                        ]),
                        Placeholder::make('send_button_css')
                            ->label('')
                            ->content(function () {
                                if ($this->agreement->status === 'documents_sent') {
                                    return '<div style="text-align: center;">
                                        <div style="display: inline-flex; align-items: center; padding: 16px 32px; background: linear-gradient(135deg, #C9D534 0%, #BDCE0F 100%); border: 2px solid #BDCE0F; color: #342970; font-weight: 600; border-radius: 16px; font-family: \'Franie\', sans-serif; box-shadow: 0 8px 32px rgba(189, 206, 15, 0.3);">
                                            <svg style="width: 24px; height: 24px; margin-right: 12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            ✅ Documentos Enviados Exitosamente
                                        </div>
                                    </div>';
                                }
                                
                                return '<div style="text-align: left;">
                                    <button wire:click="sendDocumentsToClient" 
                                            wire:confirm="¿Está seguro de enviar todos los documentos al cliente por correo electrónico?"
                                            style="display: inline-flex; align-items: center; padding: 16px 32px; background: linear-gradient(135deg, #6C2582 0%, #7C4794 100%); color: white; font-weight: 600; border-radius: 16px; border: none; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 8px 32px rgba(108, 37, 130, 0.3); text-decoration: none; font-family: \'Franie\', sans-serif; font-size: 16px;"
                                            onmouseover="this.style.background=\'linear-gradient(135deg, #7C4794 0%, #62257D 100%)\'; this.style.boxShadow=\'0 12px 48px rgba(108, 37, 130, 0.5)\'; this.style.transform=\'translateY(-3px) scale(1.05)\';"
                                            onmouseout="this.style.background=\'linear-gradient(135deg, #6C2582 0%, #7C4794 100%)\'; this.style.boxShadow=\'0 8px 32px rgba(108, 37, 130, 0.3)\'; this.style.transform=\'translateY(0) scale(1)\';">
                                        <svg style="width: 24px; height: 24px; margin-right: 12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                        </svg>
                                        📧 Enviar Documentos al Cliente
                                    </button>
                                </div>';
                            })
                            ->html()
                ])
                ->visible($this->agreement->generatedDocuments->isNotEmpty() && $this->agreement->status !== 'documents_sent'),
                
            Section::make('Documentos Enviados')
                ->description('Los documentos han sido enviados al cliente')
                ->icon('heroicon-o-check') // Usamos el icono oficial de Heroicons
                ->iconColor('success') // Le damos un color llamativo (verde)
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
                        ])
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
                ])
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
                
            // Section::make('Resumen del Proceso')
            //     ->icon('heroicon-o-clipboard-document-check')
            //     ->iconColor('info')
            //     ->description('Pasos completados durante el proceso')
            //     ->schema([
            //         Placeholder::make('process_step_1')
            //             ->label('✓ Paso 1')
            //             ->content('Captura de información completada'),
                        
            //         Placeholder::make('process_step_2')
            //             ->label('✓ Paso 2')
            //             ->content('Documentos PDF generados automáticamente'),
                        
            //         Placeholder::make('process_step_3')
            //             ->label('✓ Paso 3')
            //             ->content('Documentos enviados al cliente'),
                        
            //         Placeholder::make('process_step_4')
            //             ->label('✓ Paso 4')
            //             ->content('Documentos del cliente recibidos y validados'),
                        
            //         Placeholder::make('process_step_5')
            //             ->label('✓ Paso 5')
            //             ->content('Convenio cerrado exitosamente')
            //             ->extraAttributes(['class' => 'font-semibold']),
            //     ])
            //     ->columns(5),
                
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
                            
                        // Card: Enviar Correos
                        Placeholder::make('action_email')
                            ->label('📧 Enviar por Email')
                            ->content(fn() => view('components.action-button', [
                                'icon' => 'heroicon-o-envelope',
                                'label' => 'Enviar Correos',
                                'sublabel' => 'Reenviar Documentos',
                                'action' => 'sendDocumentsToClient',
                                'color' => 'info',
                                'confirm' => '¿Está seguro de reenviar los documentos al cliente?'
                            ])),
                            
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
                                                    target="_blank"
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
                ->send();
        }
    }

    public function sendDocumentsToClient()
    {
        try {
            if ($this->agreement->generatedDocuments->isEmpty()) {
                Notification::make()
                    ->title('Sin Documentos')
                    ->body('No hay documentos generados para enviar')
                    ->warning()
                    ->send();
                return;
            }

            $clientEmail = $this->getClientEmail();
            if ($clientEmail === 'No disponible') {
                Notification::make()
                    ->title('Email No Disponible')
                    ->body('El cliente no tiene un email registrado en el convenio')
                    ->warning()
                    ->send();
                return;
            }

            $this->agreement->update([
                'status' => 'documents_sent',
                'documents_sent_at' => now(),
            ]);

            Mail::to($clientEmail)->send(new DocumentsReadyMail($this->agreement));

            Notification::make()
                ->title('📤 Documentos Enviados')
                ->body('Los documentos han sido enviados al cliente por correo electrónico')
                ->success()
                ->duration(5000)
                ->send();

            $this->currentStep = 2;
            return $this->redirect(request()->url());

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al Enviar')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
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

            $documents = $this->agreement->generatedDocuments;
            
            if ($documents->isEmpty()) {
                Notification::make()
                    ->title('Sin Documentos')
                    ->body('No hay documentos generados para descargar')
                    ->warning()
                    ->send();
                return;
            }

            // Crear un ZIP con todos los documentos
            $zipFileName = 'convenio_' . $this->agreement->id . '_documentos_' . now()->format('Y-m-d') . '.zip';
            $zipPath = storage_path('app/temp/' . $zipFileName);
            
            // Crear directorio temporal si no existe
            if (!file_exists(dirname($zipPath))) {
                mkdir(dirname($zipPath), 0755, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                foreach ($documents as $document) {
                    $filePath = Storage::disk('private')->path($document->file_path);
                    if (file_exists($filePath)) {
                        $zip->addFile($filePath, $document->document_name . '.pdf');
                    }
                }
                $zip->close();

                Notification::make()
                    ->title('📦 Descarga Iniciada')
                    ->body('Se han preparado ' . $documents->count() . ' documentos para descarga')
                    ->success()
                    ->send();

                // Descargar el archivo
                return response()->download($zipPath, $zipFileName)->deleteFileAfterSend();
            } else {
                throw new \Exception('No se pudo crear el archivo ZIP');
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
}
