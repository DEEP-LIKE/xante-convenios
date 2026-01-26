<?php

namespace App\Actions\Agreements;

use App\Models\Agreement;
use App\Services\PdfGenerationService;
use Filament\Notifications\Notification;

class GenerateAgreementDocumentsAction
{
    public function __construct(
        protected PdfGenerationService $pdfService
    ) {}

    public function execute(int $agreementId, array $wizardData, bool $confirmDataCorrect = true): ?string
    {
        // Ya no se requiere confirmación de checkbox - el ejecutivo ya pasó validación del coordinador

        $agreement = Agreement::find($agreementId);

        if (! $agreement) {
            Notification::make()
                ->title('Error')
                ->body('No se encontró el convenio.')
                ->danger()
                ->send();

            return null;
        }

        // Actualizar estado inicial
        $agreement->update([
            'status' => 'documents_generating',
            'current_step' => 5,
            'current_wizard' => 2,
            'wizard2_current_step' => 1,
            'completion_percentage' => 100,
            'wizard_data' => $wizardData,
            'can_return_to_wizard1' => false,
        ]);

        try {
            // Generar documentos de forma síncrona
            $documents = $this->pdfService->generateAllDocuments($agreement);

            Notification::make()
                ->title('📄 Documentos Generados')
                ->body('Se generaron exitosamente '.count($documents).' documentos')
                ->success()
                ->duration(5000)
                ->send();

            // Retornar URL de redirección
            return "/admin/manage-documents/{$agreement->id}";

        } catch (\Exception $e) {
            // Si hay error, actualizar estado y mostrar error
            $agreement->update(['status' => 'error_generating_documents']);

            Notification::make()
                ->title('❌ Error al Generar Documentos')
                ->body('Error ('.get_class($e).'): '.$e->getMessage().'. Por favor, revisa los logs de Laravel para más detalles.')
                ->danger()
                ->duration(12000)
                ->send();

            return null;
        }
    }
}
