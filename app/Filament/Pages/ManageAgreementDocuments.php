<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use App\Models\Agreement;
use App\Services\PdfGenerationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use BackedEnum;

class ManageAgreementDocuments extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $title = 'Gestion de Documentos';
    protected static bool $shouldRegisterNavigation = false;

    public string $view = 'filament.pages.manage-agreement-documents';

    public Agreement $agreement;
    public int $currentStep = 1;

    public function mount(Agreement $agreement): void
    {
        $this->agreement = $agreement;
        $this->currentStep = match($this->agreement->status) {
            'documents_generating', 'documents_ready' => 1,
            'documents_sent' => 2,
            'documents_received', 'documents_validated', 'completed' => 3,
            default => 1
        };
    }

    protected function getFormSchema(): array
    {
        return match($this->currentStep) {
            1 => $this->getStepOneSchema(),
            2 => $this->getStepTwoSchema(),
            3 => $this->getStepThreeSchema(),
            default => []
        };
    }

    private function getStepOneSchema(): array
    {
        return [
            Section::make('Documentos Disponibles')
                ->description('Documentos generados automaticamente')
                ->schema($this->getDocumentFields())
                ->collapsible(),
        ];
    }

    private function getStepTwoSchema(): array
    {
        return [
            Section::make('Documentos del Cliente')
                ->description('Subir documentos requeridos')
                ->schema([]),
        ];
    }

    private function getStepThreeSchema(): array
    {
        return [
            Section::make('Proceso Completado')
                ->description('Convenio finalizado exitosamente')
                ->schema([
                    TextInput::make('status')
                        ->label('Estado Final')
                        ->default('Convenio Completado')
                        ->disabled(),
                ]),
        ];
    }

    private function getDocumentFields(): array
    {
        $documents = $this->agreement->generatedDocuments;

        if ($documents->isEmpty()) {
            return [
                TextInput::make('no_docs')
                    ->label('Estado')
                    ->default('No hay documentos generados')
                    ->disabled()
            ];
        }

        $fields = [];

        foreach ($documents as $document) {
            $fields[] = Section::make($document->formatted_type)
                ->description('Documento disponible para ver y descargar')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('status_' . $document->id)
                                ->label('Estado')
                                ->default('Documento listo')
                                ->disabled()
                                ->prefixIcon('heroicon-o-check-circle')
                                ->prefixIconColor('success'),

                            TextInput::make('view_' . $document->id)
                                ->label('Ver Documento')
                                ->default('Ver PDF')
                                ->disabled()
                                ->prefixIcon('heroicon-o-eye')
                                ->prefixIconColor('gray')
                                ->extraInputAttributes([
                                    'onclick' => 'window.open(this.dataset.url, "_blank")',
                                    'data-url' => $document->getDownloadUrl(),
                                    'style' => 'cursor: pointer;'
                                ]),

                            TextInput::make('download_' . $document->id)
                                ->label('Descargar PDF')
                                ->default('Descargar')
                                ->disabled()
                                ->prefixIcon('heroicon-o-arrow-down-tray')
                                ->prefixIconColor('primary')
                                ->extraInputAttributes([
                                    'onclick' => 'window.location.href = this.dataset.url',
                                    'data-url' => $document->getDownloadUrl(),
                                    'style' => 'cursor: pointer;'
                                ]),
                        ])
                ])
                ->collapsible();
        }

        return $fields;
    }

    public function regenerateDocuments(): void
    {
        try {
            $pdfService = app(PdfGenerationService::class);
            $this->agreement->generatedDocuments()->delete();
            $documents = $pdfService->generateAllDocuments($this->agreement);

            Notification::make()
                ->title('Documentos Regenerados')
                ->body('Se generaron ' . count($documents) . ' documentos')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function sendDocumentsToClient(): void
    {
        try {
            $this->agreement->update([
                'status' => 'documents_sent',
                'documents_sent_at' => now()
            ]);

            Notification::make()
                ->title('Documentos Enviados')
                ->body('Documentos enviados al cliente')
                ->success()
                ->send();

            $this->currentStep = 2;

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        if ($this->currentStep === 1 && $this->agreement->status !== 'documents_sent') {
            return [
                Action::make('send_documents')
                    ->label('Enviar Documentos al Cliente')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->action('sendDocumentsToClient')
            ];
        }

        return [];
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }
}
