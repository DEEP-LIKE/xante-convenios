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
    protected static ?string $slug = 'manage-agreement-documents/{agreement}';

    public string $view = 'filament.pages.manage-agreement-documents';

    public Agreement $agreement;
    public ?array $data = [];
    public int $currentStep = 1;
    public int $totalSteps = 3;

    public function mount(): void
    {
        // El modelo Agreement ya está inyectado automáticamente por Filament
        // No necesitamos búsqueda manual ni abort(404)

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

        // Inicializar el formulario con datos vacíos
        $this->form->fill([]);

        // Precargar documentos existentes en el formulario si el método existe
        if (method_exists($this, 'loadExistingDocuments')) {
            $this->loadExistingDocuments();
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
            ->nextAction(fn (Action $action) => $action->label('Siguiente'))
            ->previousAction(fn (Action $action) => $action->label('Anterior'))
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

                    Placeholder::make('send_documents_button')
                        ->label('')
                        ->content(function () {
                            if ($this->agreement->generatedDocuments->isEmpty() || $this->agreement->status === 'documents_sent') {
                                return '';
                            }

                            return new HtmlString('
                                <div class="text-center">
                                    <button wire:click="sendDocumentsToClient"
                                            wire:confirm="¿Está seguro de enviar todos los documentos al cliente por correo electrónico?"
                                            class="inline-flex items-center px-6 py-3 bg-primary-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        📧 Enviar Documentos al Cliente
                                    </button>
                                </div>
                            ');
                        })
                        ->visible($this->agreement->generatedDocuments->isNotEmpty() && $this->agreement->status !== 'documents_sent'),

                    Placeholder::make('documents_sent_status')
                        ->label('Estado del Envío')
                        ->content('✅ Documentos enviados exitosamente')
                        ->visible($this->agreement->status === 'documents_sent')
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
                                        ->maxSize(10240)
                                        ->directory('convenios/' . $this->agreement->id . '/client_documents/titular')
                                        ->disk('private')
                                        ->image()
                                        ->loadingIndicatorPosition('center')
                                        ->panelLayout('integrated')
                                        ->removeUploadedFileButtonPosition('top-right')
                                        ->uploadButtonPosition('center')
                                        ->uploadProgressIndicatorPosition('center')
                                        ->getUploadedFileNameForStorageUsing(function ($file) {
                                            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                                            return 'ine_frontal_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state) {
                                            if ($state) {
                                                $this->saveClientDocument('holder_id_front', $state, 'INE/IFE Frontal', 'titular');
                                            } else {
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

                    Placeholder::make('final_actions')
                        ->label('')
                        ->content(new HtmlString('
                            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                                <button wire:click="downloadAllDocuments"
                                        class="inline-flex items-center px-6 py-3 bg-primary-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    📦 Descargar Todos los Documentos
                                </button>
                                <button wire:click="generateFinalReport"
                                        class="inline-flex items-center px-6 py-3 bg-gray-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    📊 Generar Reporte Final
                                </button>
                            </div>
                        ')),
                ])
        ];
    }

    private function getDocumentFields(): array
    {
        $documentSections = [];

        foreach ($this->agreement->generatedDocuments as $document) {
            $documentSections[] = Section::make('📄 ' . $document->formatted_type)
                ->description('Documento PDF generado')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Placeholder::make('doc_info_' . $document->id)
                                ->label('Información')
                                ->content(function () use ($document) {
                                    $size = $document->file_size ?
                                        number_format($document->file_size / 1024, 1) . ' KB' :
                                        'Tamaño no disponible';
                                    $created = $document->created_at->format('d/m/Y H:i');
                                    return "Tamaño: {$size}<br>Creado: {$created}";
                                })
                                ->html(),

                            Placeholder::make('document_actions_' . $document->id)
                                ->label('')
                                ->content(function () use ($document) {
                                    $downloadUrl = route('documents.download', ['document' => $document->id]);

                                    return new HtmlString('
                                        <div class="flex gap-2">
                                            <a href="' . $downloadUrl . '"
                                               target="_blank"
                                               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                👁️ Ver PDF
                                            </a>
                                            <button wire:click="downloadDocument(' . $document->id . ')"
                                                    class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                📄 Descargar
                                            </button>
                                        </div>
                                    ');
                                })
                        ])
                ])
                ->collapsible()
                ->collapsed(false);
        }

        return $documentSections;
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

    public function sendDocumentsToClient(): void
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

            \Illuminate\Support\Facades\Mail::to($clientEmail)->send(new \App\Mail\DocumentsReadyMail($this->agreement));

            Notification::make()
                ->title('📤 Documentos Enviados')
                ->body('Los documentos han sido enviados al cliente por correo electrónico')
                ->success()
                ->duration(5000)
                ->send();

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
        Notification::make()
            ->title('Función en Desarrollo')
            ->body('La descarga masiva estará disponible próximamente')
            ->info()
            ->send();
    }

    public function generateFinalReport(): void
    {
        Notification::make()
            ->title('Función en Desarrollo')
            ->body('El reporte final estará disponible próximamente')
            ->info()
            ->send();
    }

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
                    Log::info('Propiedades del objeto recibido', $properties);
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

            // Aquí iría la lógica para guardar en la base de datos
            // Por ahora solo logueamos el éxito
            Log::info('Documento guardado exitosamente', [
                'file_path' => $finalFilePath,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'document_type' => $documentType,
                'category' => $category
            ]);

        } catch (\Exception $e) {
            Log::error('Error guardando documento del cliente', [
                'agreement_id' => $this->agreement->id,
                'field_name' => $fieldName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function deleteClientDocument(string $fieldName, string $category): void
    {
        Log::info('Documento eliminado', [
            'field_name' => $fieldName,
            'category' => $category,
            'agreement_id' => $this->agreement->id
        ]);
    }

    private function loadExistingDocuments(): void
    {
        Log::info('Cargando documentos existentes', [
            'agreement_id' => $this->agreement->id
        ]);
    }
}
