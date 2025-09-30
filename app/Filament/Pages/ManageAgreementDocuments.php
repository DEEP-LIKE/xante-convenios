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
use App\Models\Agreement;
use App\Services\PdfGenerationService;
use Illuminate\Support\Facades\Storage;
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

    public function mount(Agreement $agreement): void
    {
        $this->agreement = $agreement;
        
        // Determinar el paso actual basado en el estado del convenio
        $this->currentStep = match($this->agreement->status) {
            'documents_generating', 'documents_generated' => 1,
            'documents_sent', 'awaiting_client_docs' => 2,
            'documents_received', 'documents_validated', 'completed' => 3,
            default => 1
        };
        
        $this->data = [];
        $this->form->fill($this->data);
        
        // Mostrar notificación informativa
        $this->showStatusNotification();
    }
    
    private function showStatusNotification(): void
    {
        $messages = [
            'documents_generating' => '⏳ Los documentos se están generando...',
            'documents_generated' => '📄 Documentos listos para enviar al cliente',
            'documents_sent' => '📤 Documentos enviados - Esperando documentos del cliente',
            'awaiting_client_docs' => '📋 Recibiendo documentos del cliente',
            'documents_received' => '✅ Todos los documentos recibidos',
            'completed' => '🎉 Convenio completado exitosamente'
        ];
        
        if (isset($messages[$this->agreement->status])) {
            Notification::make()
                ->title('Estado Actual')
                ->body($messages[$this->agreement->status])
                ->info()
                ->duration(3000)
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
                        Placeholder::make('send_button_native')
                            ->label('')
                            ->content(function () {
                                if ($this->agreement->status === 'documents_sent') {
                                    return '<div class="text-center">
                                        <span class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 rounded-lg font-medium">
                                            ✅ Documentos Enviados
                                        </span>
                                    </div>';
                                }
                                
                                return '<div class="text-center">
                                    <button wire:click="sendDocumentsToClient" 
                                            wire:confirm="¿Está seguro de enviar todos los documentos al cliente por correo electrónico?"
                                            class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors shadow-lg">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                        </svg>
                                        📤 Enviar Documentos al Cliente
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
                                        ->acceptedFileTypes(['image/*', 'application/pdf'])
                                        ->imageEditor()
                                        ->hint('Subir imagen clara del frente de la identificación'),
                                        
                                    FileUpload::make('holder_id_back')
                                        ->label('INE/IFE Reverso')
                                        ->acceptedFileTypes(['image/*', 'application/pdf'])
                                        ->imageEditor()
                                        ->hint('Subir imagen clara del reverso de la identificación'),
                                        
                                    FileUpload::make('holder_curp')
                                        ->label('CURP')
                                        ->acceptedFileTypes(['image/*', 'application/pdf'])
                                        ->imageEditor(),
                                        
                                    FileUpload::make('holder_proof_address')
                                        ->label('Comprobante de Domicilio')
                                        ->acceptedFileTypes(['image/*', 'application/pdf'])
                                        ->imageEditor()
                                        ->hint('No mayor a 3 meses'),
                                ])
                                ->collapsible(),
                                
                            Section::make('🏠 Documentación de la Propiedad')
                                ->schema([
                                    FileUpload::make('property_deed')
                                        ->label('Escrituras de la Propiedad')
                                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                                        ->hint('Documento legal de propiedad'),
                                        
                                    FileUpload::make('property_tax')
                                        ->label('Predial Actualizado')
                                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                                        ->hint('Comprobante de pago del predial'),
                                        
                                    FileUpload::make('property_water')
                                        ->label('Recibo de Agua')
                                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                                        ->hint('Último recibo de agua'),
                                ])
                                ->collapsible(),
                        ]),
                        
                    Grid::make(1)
                        ->schema([
                            Checkbox::make('documents_validated')
                                ->label('Marcar todos los documentos como válidos para concluir el convenio')
                                ->helperText('Al marcar esta casilla, confirmará que todos los documentos del cliente han sido recibidos y validados correctamente.')
                                ->disabled($this->agreement->status === 'documents_received' || $this->agreement->status === 'completed')
                                ->default($this->agreement->status === 'documents_received' || $this->agreement->status === 'completed')
                                ->live()
                                ->afterStateUpdated(function ($state) {
                                    if ($state && $this->agreement->status !== 'documents_received') {
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
                        
                    Placeholder::make('final_actions_native')
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
                        ->html()
                ])
        ];
    }
    
    private function getDocumentFields(): array
    {
        $fields = [];
        
        foreach ($this->agreement->generatedDocuments as $document) {
            $fields[] = Grid::make(2)
                ->schema([
                    Placeholder::make('doc_name_' . $document->id)
                        ->label('📄 ' . $document->formatted_type)
                        ->content('Tamaño: ' . $document->formatted_size . ' | Generado: ' . $document->generated_at->format('d/m/Y H:i')),
                        
                    Placeholder::make('doc_actions_' . $document->id)
                        ->label('Acciones')
                        ->content(function () use ($document) {
                            $viewUrl = route('documents.download', ['document' => $document->id]);
                            return '<div class="space-x-2">
                                <a href="' . $viewUrl . '" target="_blank" class="text-blue-600 hover:text-blue-800 font-medium">👁️ Ver PDF</a>
                                <a href="' . $viewUrl . '" download class="text-green-600 hover:text-green-800 font-medium">⬇️ Descargar</a>
                            </div>';
                        })
                        ->html()
                        ->extraAttributes(['class' => 'text-right']),
                ]);
        }
        
        return $fields;
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
            
            return Storage::disk('private')->download($document->file_path, $document->formatted_type . '.pdf');
            
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
                ->title('Documentos Regenerados')
                ->body('Se generaron ' . count($documents) . ' documentos exitosamente')
                ->success()
                ->send();
                
            // Recargar la página para mostrar los nuevos documentos
            $this->redirect(request()->url());

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al Regenerar')
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

            // TODO: Implementar envío real del correo
            // Mail::to($this->agreement->client->email)->send(new DocumentsReadyMail($this->agreement));

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
            $this->agreement->update([
                'status' => 'documents_received',
                'documents_received_at' => now(),
            ]);

            Notification::make()
                ->title('📋 Documentos Recibidos')
                ->body('Se ha marcado que todos los documentos del cliente han sido recibidos')
                ->success()
                ->send();

            $this->currentStep = 3;
            $this->redirect(request()->url());

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Error: ' . $e->getMessage())
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

    protected function getHeaderActions(): array
    {
        $actions = [
            Action::make('back_to_list')
                ->label('Volver al Listado')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url('/admin/wizard-resources')
        ];
        
        // Agregar botón de regenerar si no hay documentos o si hay pocos
        if ($this->agreement->generatedDocuments->isEmpty() || $this->agreement->generatedDocuments->count() < 4) {
            $actions[] = Action::make('regenerate_documents')
                ->label('Regenerar Documentos')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action('regenerateDocuments')
                ->requiresConfirmation()
                ->modalHeading('Regenerar Documentos')
                ->modalDescription('¿Está seguro de regenerar todos los documentos? Esto eliminará los documentos existentes.')
                ->modalSubmitActionLabel('Sí, Regenerar');
        }
        
        return $actions;
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }
}
