<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Checkbox;
use Illuminate\Support\HtmlString;
use App\Models\Agreement;
use App\Services\PdfGenerationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use BackedEnum;

class ManageAgreementDocuments extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected $listeners = [
        'sendDocumentsToClient' => 'sendDocumentsToClient',
        'downloadAllDocuments' => 'downloadAllDocuments',
        'generateFinalReport' => 'generateFinalReport',
    ];

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $title = 'Gestión de Documentos';
    protected static bool $shouldRegisterNavigation = false;

    public string $view = 'filament.pages.manage-agreement-documents';
    public Agreement $agreement;
    public ?array $data = [];
    public int $currentStep = 1;
    public int $totalSteps = 3;

    public function mount(): void
    {
        // Obtener el ID del agreement desde la URL
        // URL esperada: /admin/manage-agreement-documents/272
        $recordId = request()->segment(3); // Posición 3 en la URL
        
        // Log para debugging
        Log::info('ManageAgreementDocuments mount', [
            'url' => request()->url(),
            'segments' => request()->segments(),
            'recordId' => $recordId
        ]);
        
        if (!$recordId || !is_numeric($recordId)) {
            abort(404, 'Agreement ID not found or invalid: ' . $recordId);
        }
        
        $this->agreement = Agreement::findOrFail($recordId);
        
        // Determinar el paso actual basado en el estado del convenio
        $this->currentStep = match($this->agreement->status) {
            'documents_generating', 'documents_generated' => 1,
            'documents_sent', 'awaiting_client_docs' => 2,
            'documents_complete', 'completed' => 3,
            default => 1
        };
        
        // Si llegamos al paso 3 y el estado no es 'completed', actualizarlo
        if ($this->currentStep === 3 && $this->agreement->status !== 'completed') {
            $this->markAsCompleted();
        }
        
        // Refrescar las relaciones para asegurar que tenemos los datos más recientes
        $this->agreement->refresh();
        $this->agreement->load(['generatedDocuments', 'clientDocuments']);
        
        // Inicializar el formulario
        $this->form->fill([]);
        
        // Precargar documentos existentes en el formulario
        $this->loadExistingDocuments();
        
        // Forzar actualización del estado del formulario
        $this->dispatch('refresh-form');
        
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
        return [
            Wizard::make([
                Step::make('Envío de Documentos')
                    ->description('Generar y enviar documentos al cliente')
                    ->icon('heroicon-o-paper-airplane')
                    ->completedIcon('heroicon-o-check-circle')
                    ->schema($this->getStepOneSchema()),
                    
                Step::make('Recepción de Documentos')
                    ->description('Recibir y validar documentos del cliente')
                    ->icon('heroicon-o-document-arrow-up')
                    ->completedIcon('heroicon-o-check-circle')
                    ->schema($this->getStepTwoSchema()),
                    
                Step::make('Cierre Exitoso')
                    ->description('Finalizar el proceso del convenio')
                    ->icon('heroicon-o-check-badge')
                    ->completedIcon('heroicon-o-check-circle')
                    ->schema($this->getStepThreeSchema()),
            ])
            ->startOnStep($this->currentStep)
            ->skippable(false)
            ->persistStepInQueryString()
        ];
    }
    
    // PASO 1: Envío de Documentos
    private function getStepOneSchema(): array
    {
        return [
            Section::make('📄 Información del Convenio')
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
                
            Section::make('📋 Documentos Disponibles')
                ->description('Documentos PDF generados para este convenio')
                ->schema($this->getDocumentFields())
                ->visible($this->agreement->generatedDocuments->isNotEmpty()),
                
            Section::make('⚠️ Sin Documentos')
                ->description('No hay documentos generados')
                ->schema([
                    Placeholder::make('no_documents')
                        ->label('Estado')
                        ->content('No se encontraron documentos generados para este convenio. Use el botón "Regenerar Documentos" en la parte superior.'),
                ])
                ->visible($this->agreement->generatedDocuments->isEmpty()),
                
            Section::make('📤 Enviar al Cliente')
                ->description('Enviar documentos por correo electrónico')
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
                
            Section::make('✅ Documentos Enviados')
                ->description('Los documentos han sido enviados al cliente')
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
            Section::make('📋 Documentos Requeridos del Cliente')
                ->description('Gestionar documentos que debe proporcionar el cliente')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Section::make('👤 Documentación del Titular')
                                ->schema([
                                    FileUpload::make('holder_id_front')
                                        ->label('INE/IFE Frontal')
                                        ->required()
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                                        ->maxSize(10240) // 10MB
                                        ->directory('convenios/' . $this->agreement->id . '/client_documents/titular')
                                        ->disk('private')
                                        ->visibility('public')
                                        ->image() // Habilita preview de imágenes
                                        ->loadingIndicatorPosition('center')
                                        ->panelLayout('integrated')
                                        ->removeUploadedFileButtonPosition('top-right')
                                        ->uploadButtonPosition('center')
                                        ->uploadProgressIndicatorPosition('center')
                                        ->getUploadedFileNameForStorageUsing(function ($file) {
                                            // Generar nombre amigable basado en el tipo de documento
                                            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                                            return 'ine_frontal_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state, $component) {
                                            if ($state) {
                                                try {
                                                    $this->saveClientDocument('holder_id_front', $state, 'INE/IFE Frontal', 'titular');
                                                } catch (\Exception $e) {
                                                    Notification::make()
                                                        ->title('❌ Error al Guardar')
                                                        ->body('Error: ' . $e->getMessage())
                                                        ->danger()
                                                        ->send();
                                                }
                                            } else {
                                                // Si $state está vacío, significa que se eliminó el archivo
                                                $this->deleteClientDocument('holder_id_front', 'titular');
                                            }
                                        })
                                        ->hint('Subir imagen clara del frente de la identificación'),
                                        
                                    FileUpload::make('holder_id_back')
                                        ->label('INE/IFE Reverso')
                                        ->required()
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                                        ->maxSize(10240)
                                        ->directory('convenios/' . $this->agreement->id . '/client_documents/titular')
                                        ->disk('private')
                                        ->visibility('public')
                                        ->image()
                                        ->loadingIndicatorPosition('center')
                                        ->panelLayout('integrated')
                                        ->removeUploadedFileButtonPosition('top-right')
                                        ->uploadButtonPosition('center')
                                        ->uploadProgressIndicatorPosition('center')
                                        ->getUploadedFileNameForStorageUsing(function ($file) {
                                            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                                            return 'ine_reverso_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state) {
                                            if ($state) {
                                                $this->saveClientDocument('holder_id_back', $state, 'INE/IFE Reverso', 'titular');
                                            } else {
                                                $this->deleteClientDocument('holder_id_back', 'titular');
                                            }
                                        })
                                        ->hint('Subir imagen clara del reverso de la identificación'),
                                        
                                    FileUpload::make('holder_curp')
                                        ->label('CURP')
                                        ->required()
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                                        ->maxSize(10240)
                                        ->directory('convenios/' . $this->agreement->id . '/client_documents/titular')
                                        ->disk('private')
                                        ->visibility('public')
                                        ->image()
                                        ->loadingIndicatorPosition('center')
                                        ->panelLayout('integrated')
                                        ->removeUploadedFileButtonPosition('top-right')
                                        ->uploadButtonPosition('center')
                                        ->uploadProgressIndicatorPosition('center')
                                        ->getUploadedFileNameForStorageUsing(function ($file) {
                                            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                                            return 'curp_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state) {
                                            if ($state) {
                                                $this->saveClientDocument('holder_curp', $state, 'CURP', 'titular');
                                            }
                                        }),
                                        
                                    FileUpload::make('holder_proof_address')
                                        ->label('Comprobante de Domicilio')
                                        ->required()
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                                        ->maxSize(10240)
                                        ->directory('convenios/' . $this->agreement->id . '/client_documents/titular')
                                        ->disk('private')
                                        ->visibility('public')
                                        ->image()
                                        ->loadingIndicatorPosition('center')
                                        ->panelLayout('integrated')
                                        ->removeUploadedFileButtonPosition('top-right')
                                        ->uploadButtonPosition('center')
                                        ->uploadProgressIndicatorPosition('center')
                                        ->getUploadedFileNameForStorageUsing(function ($file) {
                                            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                                            return 'comprobante_domicilio_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state) {
                                            if ($state) {
                                                $this->saveClientDocument('holder_proof_address', $state, 'Comprobante de Domicilio', 'titular');
                                            }
                                        })
                                        ->hint('No mayor a 3 meses'),
                                ])
                                ->collapsible(),
                                
                            Section::make('🏠 Documentación de la Propiedad')
                                ->schema([
                                    FileUpload::make('property_deed')
                                        ->label('Escrituras de la Propiedad')
                                        ->required()
                                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                                        ->directory('convenios/' . $this->agreement->id . '/client_documents/propiedad')
                                        ->disk('private')
                                        ->getUploadedFileNameForStorageUsing(function ($file) {
                                            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                                            return 'escrituras_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state) {
                                            if ($state) {
                                                $this->saveClientDocument('property_deed', $state, 'Escrituras de la Propiedad', 'propiedad');
                                            }
                                        })
                                        ->hint('Documento legal de propiedad'),
                                        
                                    FileUpload::make('property_tax')
                                        ->label('Predial Actualizado')
                                        ->required()
                                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                                        ->directory('convenios/' . $this->agreement->id . '/client_documents/propiedad')
                                        ->disk('private')
                                        ->getUploadedFileNameForStorageUsing(function ($file) {
                                            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                                            return 'predial_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state) {
                                            if ($state) {
                                                $this->saveClientDocument('property_tax', $state, 'Predial Actualizado', 'propiedad');
                                            }
                                        })
                                        ->hint('Comprobante de pago del predial'),
                                        
                                    FileUpload::make('property_water')
                                        ->label('Recibo de Agua')
                                        ->required()
                                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                                        ->directory('convenios/' . $this->agreement->id . '/client_documents/propiedad')
                                        ->disk('private')
                                        ->getUploadedFileNameForStorageUsing(function ($file) {
                                            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                                            return 'recibo_agua_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state) {
                                            if ($state) {
                                                $this->saveClientDocument('property_water', $state, 'Recibo de Agua', 'propiedad');
                                            }
                                        })
                                        ->hint('Último recibo de agua'),
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
        return [
            Section::make('🎉 Convenio Completado')
                ->description('El proceso ha sido finalizado exitosamente')
                ->schema([
                    Placeholder::make('completion_summary')
                        ->label('🎉 Convenio Completado Exitosamente')
                        ->content('El convenio ha sido finalizado exitosamente. El proceso de gestión documental está completo.'),
                        
                    Grid::make(3)
                        ->schema([
                            Placeholder::make('completion_date')
                                ->label('Fecha de Finalización')
                                ->content($this->agreement->completed_at?->format('d/m/Y H:i') ?? 'En proceso'),
                                
                            Placeholder::make('total_documents')
                                ->label('Documentos Generados')
                                ->content($this->agreement->generatedDocuments->count() . ' PDFs'),
                                
                            Placeholder::make('final_status')
                                ->label('Estado Final')
                                ->content('✅ Completado')
                        ]),
                        
                    Placeholder::make('final_actions_css')
                        ->label('')
                        ->content('<div class="text-center space-y-4">
                            <div class="flex flex-col sm:flex-row justify-center gap-4">
                                <button wire:click="downloadAllDocuments" 
                                        class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors shadow-lg">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    📦 Descargar Todos los Documentos
                                </button>
                                <button wire:click="generateFinalReport" 
                                        class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition-colors shadow-lg">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    📊 Generar Reporte Final
                                </button>
                            </div>
                            <p class="text-sm text-gray-600">El convenio ha sido completado exitosamente. Todos los documentos están disponibles.</p>
                        </div>')
                        ->html(),
                ])
        ];
    }
    
    private function getDocumentFields(): array
    {
        // Array para almacenar los Placeholders de cada documento.
        $documentPlaceholders = [];
    
        foreach ($this->agreement->generatedDocuments as $document) {
            // Creamos el Placeholder para cada documento.
            // Hemos eliminado el Grid anidado que limitaba el diseño a 1 columna.
            $documentPlaceholders[] = Placeholder::make('doc_name_' . $document->id)
                // Etiqueta del documento con el icono.
                ->label('📄 ' . $document->formatted_type)
                // Contenido HTML (el botón de descarga de Livewire).
                ->content(function () use ($document) {
                    $downloadUrl = route('documents.download', ['document' => $document->id]);
                    
                    return new HtmlString(
                        '<div style="display: flex; justify-content: flex-start; align-items: center; height: 100%;">
                            <a href="' . $downloadUrl . '" 
                               target="_self"
                               style="display: inline-flex; align-items: center; padding: 10px 20px; background: linear-gradient(135deg, #BDCE0F 0%, #C9D534 100%); color: #342970; font-size: 14px; font-weight: 600; border-radius: 12px; border: none; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 16px rgba(189, 206, 15, 0.25); text-decoration: none; font-family: \'Inter\', sans-serif;"
                               onmouseover="this.style.background=\'linear-gradient(135deg, #C9D534 0%, #BDCE0F 100%)\'; this.style.boxShadow=\'0 8px 32px rgba(189, 206, 15, 0.4)\'; this.style.transform=\'translateY(-2px) scale(1.02)\';"
                               onmouseout="this.style.background=\'linear-gradient(135deg, #BDCE0F 0%, #C9D534 100%)\'; this.style.boxShadow=\'0 4px 16px rgba(189, 206, 15, 0.25)\'; this.style.transform=\'translateY(0) scale(1)\';">
                                <svg style="width: 18px; height: 18px; margin-right: 8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                </svg>
                                📄 Descargar PDF
                            </a>
                        </div>'
                    );
                })
                ->html(); // Es importante mantener este método
        }
        
        // Devolvemos un array que contiene un único componente Grid
        // configurado para 2 columnas, el cual contendrá todos los placeholders.
        // Esto asegura que los documentos se muestren uno al lado del otro (máximo 2 por fila).
        return [
            Grid::make(2) // <- Esta línea define el diseño de 2 columnas
                ->schema($documentPlaceholders)
                ->columnSpanFull(), // Asegura que el Grid ocupe todo el ancho disponible si está anidado
        ];
    }
    
    // Métodos para obtener datos del cliente desde wizard_data
    private function getClientName(): string
    {
        $wizardData = $this->agreement->wizard_data ?? [];
        
        // Intentar obtener el nombre del titular desde wizard_data
        $holderName = $wizardData['holder_name'] ?? null;
        
        // Si no está en wizard_data, usar el cliente relacionado
        if (!$holderName && $this->agreement->client) {
            $holderName = $this->agreement->client->name;
        }
        
        return $holderName ?? 'N/A';
    }
    
    private function getClientEmail(): string
    {
        $wizardData = $this->agreement->wizard_data ?? [];
        
        // Intentar obtener el email del titular desde wizard_data
        $holderEmail = $wizardData['holder_email'] ?? null;
        
        // Si no está en wizard_data, usar el cliente relacionado
        if (!$holderEmail && $this->agreement->client) {
            $holderEmail = $this->agreement->client->email;
        }
        
        return $holderEmail ?? 'No disponible';
    }
    
    private function getClientPhone(): string
    {
        $wizardData = $this->agreement->wizard_data ?? [];
        
        // Intentar obtener el teléfono del titular desde wizard_data
        $holderPhone = $wizardData['holder_phone'] ?? null;
        
        // Si no está en wizard_data, usar el cliente relacionado
        if (!$holderPhone && $this->agreement->client) {
            $holderPhone = $this->agreement->client->phone;
        }
        
        return $holderPhone ?? 'No disponible';
    }
    
    private function getPropertyAddress(): string
    {
        $wizardData = $this->agreement->wizard_data ?? [];
        
        // Intentar obtener el domicilio desde wizard_data
        $address = $wizardData['domicilio_convenio'] ?? null;
        
        return $address ?? 'No disponible';
    }
    
    private function getPropertyCommunity(): string
    {
        $wizardData = $this->agreement->wizard_data ?? [];
        
        // Intentar obtener la comunidad desde wizard_data
        $community = $wizardData['comunidad'] ?? null;
        
        return $community ?? 'No disponible';
    }
    
    private function getAgreementValue(): string
    {
        $wizardData = $this->agreement->wizard_data ?? [];
        
        // Intentar obtener el valor del convenio desde wizard_data
        $value = $wizardData['valor_convenio'] ?? null;
        
        if ($value) {
            return '$' . number_format($value, 2);
        }
        
        return 'No disponible';
    }
    

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
            
            // Abrir la descarga en nueva ventana usando la ruta existente
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
    
    public function regenerateDocuments(): void
    {
        try {
            $pdfService = app(PdfGenerationService::class);
            
            // Limpiar documentos existentes
            $pdfService->cleanupGeneratedDocuments($this->agreement);
            
            // Generar nuevos documentos
            $documents = $pdfService->generateAllDocuments($this->agreement);

            Notification::make()
                ->title('📄 Documentos Regenerados')
                ->body('Se han regenerado exitosamente ' . count($documents) . ' documentos')
                ->success()
                ->duration(5000)
                ->send();
                
            // Recargar la página para mostrar los nuevos documentos
            $this->redirect(request()->url());

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error al Regenerar')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    // Métodos de acción para los botones del wizard
    public function sendDocumentsToClient(): void
    {
        try {
            // Verificar que hay documentos para enviar
            if ($this->agreement->generatedDocuments->isEmpty()) {
                Notification::make()
                    ->title('Sin Documentos')
                    ->body('No hay documentos generados para enviar')
                    ->warning()
                    ->send();
                return;
            }
            
            // Verificar que el cliente tiene email
            $clientEmail = $this->getClientEmail();
            if ($clientEmail === 'No disponible') {
                Notification::make()
                    ->title('Email No Disponible')
                    ->body('El cliente no tiene un email registrado en el convenio')
                    ->warning()
                    ->send();
                return;
            }
            
            // Actualizar estado del convenio
            $this->agreement->update([
                'status' => 'documents_sent',
                'documents_sent_at' => now(),
            ]);

            // Enviar correo con documentos adjuntos
            \Illuminate\Support\Facades\Mail::to($clientEmail)->send(new \App\Mail\DocumentsReadyMail($this->agreement));

            Notification::make()
                ->title('📤 Documentos Enviados')
                ->body('Los documentos han sido enviados al cliente por correo electrónico')
                ->success()
                ->duration(5000)
                ->send();

            // Avanzar al siguiente paso
            $this->currentStep = 2;
            $this->redirect(request()->url());

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al Enviar')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function markDocumentsReceived(): void
    {
        try {
            // Actualizar el estado del convenio a completado
            $this->agreement->update([
                'status' => 'completed', // Cambiar a completed en lugar de documents_complete
                'wizard2_current_step' => 3,
                'completion_percentage' => 100, // Marcar como 100% completado
                'completed_at' => now(), // Agregar fecha de finalización
            ]);

            Notification::make()
                ->title('🎉 Convenio Completado')
                ->body('El convenio ha sido finalizado exitosamente. Todos los documentos han sido recibidos y validados.')
                ->success()
                ->duration(5000)
                ->send();

            $this->currentStep = 3;
            
            // Refresh the page to show the completion state
            $this->redirect(request()->url());

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
            Log::error('Error marking agreement as completed', [
                'agreement_id' => $this->agreement->id,
                'error' => $e->getMessage()
            ]);
            
            Notification::make()
                ->title('Error')
                ->body('Error al completar el convenio: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function downloadAllDocuments(): void
    {
        // TODO: Implementar descarga de ZIP con todos los documentos
        Notification::make()
            ->title('Función en Desarrollo')
            ->body('La descarga masiva estará disponible próximamente')
            ->info()
            ->send();
    }
    
    public function generateFinalReport(): void
    {
        // TODO: Implementar generación de reporte final
        Notification::make()
            ->title('Función en Desarrollo')
            ->body('El reporte final estará disponible próximamente')
            ->info()
            ->send();
    }

    /**
     * Guarda un documento del cliente en la base de datos
     */
    private function saveClientDocument(string $fieldName, $filePath, string $documentName, string $category): void
    {
        try {
            Log::info('Guardando documento del cliente', [
                'field_name' => $fieldName,
                'file_path' => $filePath,
                'document_name' => $documentName,
                'category' => $category,
                'agreement_id' => $this->agreement->id
            ]);
            // Mapear nombres de campos a tipos de documento
            $documentTypeMap = [
                'holder_id_front' => 'titular_ine_frontal',
                'holder_id_back' => 'titular_ine_reverso',
                'holder_curp' => 'titular_curp',
                'holder_proof_address' => 'titular_comprobante_domicilio',
                'property_deed' => 'propiedad_instrumento_notarial',
                'property_tax' => 'propiedad_recibo_predial',
                'property_water' => 'propiedad_recibo_agua',
            ];
            
            $documentType = $documentTypeMap[$fieldName] ?? $fieldName;
            
            // Obtener información del archivo
            $fileSize = null;
            $fileName = null;
            $finalFilePath = null;
            
            Log::info('Procesando archivo recibido', [
                'filePath_type' => gettype($filePath),
                'filePath_class' => is_object($filePath) ? get_class($filePath) : 'N/A',
                'is_array' => is_array($filePath),
                'is_string' => is_string($filePath),
                'is_object' => is_object($filePath)
            ]);
            
            // Manejar diferentes tipos de datos que puede enviar Filament
            if (is_string($filePath) && !empty($filePath)) {
                // Caso 1: String directo (ruta del archivo)
                $finalFilePath = $filePath;
                $fileName = basename($filePath);
                if (Storage::disk('private')->exists($filePath)) {
                    $fileSize = Storage::disk('private')->size($filePath);
                }
            } elseif (is_array($filePath) && !empty($filePath)) {
                // Caso 2: Array de rutas
                $firstFile = $filePath[0];
                if (!empty($firstFile)) {
                    $finalFilePath = $firstFile;
                    $fileName = basename($firstFile);
                    if (Storage::disk('private')->exists($firstFile)) {
                        $fileSize = Storage::disk('private')->size($firstFile);
                    }
                }
            } elseif (is_object($filePath)) {
                // Caso 3: Objeto UploadedFile o TemporaryUploadedFile
                if (method_exists($filePath, 'getClientOriginalName')) {
                    // Es un UploadedFile
                    $fileName = $filePath->getClientOriginalName();
                    $finalFilePath = $filePath->store('convenios/' . $this->agreement->id . '/client_documents/' . $category, 'private');
                    $fileSize = $filePath->getSize();
                } elseif (method_exists($filePath, 'getRealPath')) {
                    // Es un archivo temporal
                    $fileName = basename($filePath->getRealPath());
                    $finalFilePath = $filePath->getRealPath();
                    $fileSize = filesize($filePath->getRealPath());
                } else {
                    // Intentar obtener propiedades del objeto
                    $properties = get_object_vars($filePath);
                    Log::info('Propiedades del objeto recibido', $properties);
                    throw new \Exception('Objeto no reconocido: ' . get_class($filePath));
                }
            }
            
            // Validar que tenemos una ruta válida
            if (empty($finalFilePath)) {
                throw new \Exception("No se pudo obtener la ruta del archivo. Tipo recibido: " . gettype($filePath));
            }
            
            // Asegurar que fileName no sea null
            if (empty($fileName)) {
                $extension = pathinfo($finalFilePath, PATHINFO_EXTENSION) ?: 'pdf';
                $fileName = $documentName . '_' . time() . '.' . $extension;
            }
            
            // Verificar si ya existe un documento para este tipo
            $existingDocument = $this->agreement->clientDocuments()
                ->where('document_type', $documentType)
                ->first();
            
            if ($existingDocument) {
                // Actualizar documento existente
                $existingDocument->update([
                    'file_path' => $finalFilePath,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'document_category' => $category,
                ]);
                
                $message = "Documento '{$documentName}' actualizado exitosamente";
            } else {
                // Crear nuevo documento
                // Validar datos antes de guardar
                if (empty($finalFilePath)) {
                    throw new \Exception('file_path no puede estar vacío');
                }
                if (empty($fileName)) {
                    throw new \Exception('file_name no puede estar vacío');
                }
                
                $documentData = [
                    'agreement_id' => $this->agreement->id,
                    'document_type' => $documentType,
                    'document_category' => $category,
                    'file_name' => $fileName,
                    'file_path' => $finalFilePath,
                    'file_size' => $fileSize ?? 0,
                    'is_validated' => false,
                ];
                
                Log::info('Creando nuevo documento', $documentData);
                
                $this->agreement->clientDocuments()->create($documentData);
                
                $message = "Documento '{$documentName}' guardado exitosamente";
            }
            
            // Actualizar estado del convenio si es necesario
            if ($this->agreement->status === 'documents_sent') {
                $this->agreement->update([
                    'status' => 'awaiting_client_docs'
                ]);
            }
            
            Notification::make()
                ->title('📄 Documento Guardado')
                ->body($message)
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Log::error('Error guardando documento del cliente', [
                'field_name' => $fieldName,
                'document_name' => $documentName,
                'agreement_id' => $this->agreement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Notification::make()
                ->title('Error al Guardar Documento')
                ->body('Error guardando documento: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    /**
     * Configuración común para campos FileUpload
     */
    private function getFileUploadConfig(): array
    {
        return [
            'acceptedFileTypes' => ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'],
            'maxSize' => 10240, // 10MB
            'disk' => 'private',
            'visibility' => 'private',
            'image' => true,
            'imagePreviewHeight' => '150',
            'loadingIndicatorPosition' => 'center',
            'panelAspectRatio' => '16:9',
            'panelLayout' => 'integrated',
            'removeUploadedFileButtonPosition' => 'top-right',
            'uploadButtonPosition' => 'center',
            'uploadProgressIndicatorPosition' => 'center',
            'live' => true
        ];
    }
    
    /**
     * Precarga los documentos existentes en el formulario
     */
    private function loadExistingDocuments(): void
    {
        try {
            $documentFieldMap = [
                'titular_ine_frontal' => 'holder_id_front',
                'titular_ine_reverso' => 'holder_id_back',
                'titular_curp' => 'holder_curp',
                'titular_comprobante_domicilio' => 'holder_proof_address',
                'propiedad_instrumento_notarial' => 'property_deed',
                'propiedad_recibo_predial' => 'property_tax',
                'propiedad_recibo_agua' => 'property_water',
            ];
            
            $formData = [];
            
            foreach ($this->agreement->clientDocuments as $document) {
                $fieldName = $documentFieldMap[$document->document_type] ?? null;
                if ($fieldName && $document->file_path) {
                    // Verificar que el archivo existe en el servidor (private o public)
                    $fileExists = Storage::disk('private')->exists($document->file_path) || 
                                 Storage::disk('public')->exists($document->file_path);
                    
                    if ($fileExists) {
                        // Para archivos existentes, cargar la ruta
                        $formData[$fieldName] = [$document->file_path];
                        
                        Log::info('Documento precargado', [
                            'field' => $fieldName,
                            'file_path' => $document->file_path,
                            'document_type' => $document->document_type
                        ]);
                    } else {
                        Log::warning('Archivo no encontrado en servidor', [
                            'file_path' => $document->file_path,
                            'document_type' => $document->document_type
                        ]);
                    }
                }
            }
            
            // Asignar los datos al formulario
            if (!empty($formData)) {
                // Usar form()->fill() para cargar los datos correctamente
                $this->form->fill($formData);
                
                Log::info('Documentos precargados en formulario', [
                    'count' => count($formData),
                    'fields' => array_keys($formData),
                    'data' => $formData
                ]);
                
                // También asignar a $this->data como respaldo
                $this->data = array_merge($this->data ?? [], $formData);
                
                // Notificar al usuario
                Notification::make()
                    ->title('📄 Documentos Precargados')
                    ->body('Se han precargado ' . count($formData) . ' documentos existentes')
                    ->info()
                    ->duration(3000)
                    ->send();
            }
            
        } catch (\Exception $e) {
            Log::error('Error precargando documentos existentes', [
                'error' => $e->getMessage(),
                'agreement_id' => $this->agreement->id
            ]);
        }
    }
    
    /**
     * Elimina un documento del cliente del servidor y base de datos
     */
    private function deleteClientDocument(string $fieldName, string $category): void
    {
        try {
            // Mapear nombres de campos a tipos de documento
            $documentTypeMap = [
                'holder_id_front' => 'titular_ine_frontal',
                'holder_id_back' => 'titular_ine_reverso',
                'holder_curp' => 'titular_curp',
                'holder_proof_address' => 'titular_comprobante_domicilio',
                'property_deed' => 'propiedad_instrumento_notarial',
                'property_tax' => 'propiedad_recibo_predial',
                'property_water' => 'propiedad_recibo_agua',
            ];
            
            $documentType = $documentTypeMap[$fieldName] ?? $fieldName;
            
            // Buscar el documento en la base de datos
            $existingDocument = $this->agreement->clientDocuments()
                ->where('document_type', $documentType)
                ->first();
            
            if ($existingDocument) {
                // Eliminar archivo del servidor
                if ($existingDocument->file_path && Storage::disk('private')->exists($existingDocument->file_path)) {
                    Storage::disk('private')->delete($existingDocument->file_path);
                    Log::info('Archivo eliminado del servidor', [
                        'file_path' => $existingDocument->file_path,
                        'document_type' => $documentType
                    ]);
                }
                
                // Eliminar registro de la base de datos
                $existingDocument->delete();
                
                Notification::make()
                    ->title('🗑️ Documento Eliminado')
                    ->body('Documento eliminado del servidor y base de datos')
                    ->warning()
                    ->send();
            }
            
        } catch (\Exception $e) {
            Log::error('Error eliminando documento del cliente', [
                'field_name' => $fieldName,
                'category' => $category,
                'error' => $e->getMessage()
            ]);
            
            Notification::make()
                ->title('Error al Eliminar')
                ->body('Error eliminando documento: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function getHeaderActions(): array
    {
        $actions = [
            Action::make('back_to_list')
                ->label('Volver al Listado')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url('/admin/wizard-resources')
        ];
        
        // Agregar botón de regenerar si no hay documentos o si hay pocos (deberían ser 6)
        if ($this->agreement->generatedDocuments->isEmpty() || $this->agreement->generatedDocuments->count() < 6) {
            $actions[] = Action::make('regenerate_documents')
                ->label('Regenerar Documentos (Opcional)')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action('regenerateDocuments')
                ->requiresConfirmation()
                ->modalHeading('Regenerar Documentos')
                ->modalDescription('Esta es una acción opcional. Use esta opción solo si los documentos no se generaron correctamente de manera automática. ¿Está seguro de regenerar todos los documentos?')
                ->modalSubmitActionLabel('Sí, Regenerar');
        }
        
        return $actions;
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }
}
