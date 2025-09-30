<?php

namespace App\Jobs;

use App\Models\Agreement;
use App\Services\PdfGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

class GenerateAgreementDocumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutos timeout
    public $tries = 3; // Máximo 3 intentos

    public function __construct(
        public Agreement $agreement,
        public ?int $userId = null
    ) {}

    public function handle(PdfGenerationService $pdfService): void
    {
        try {
            Log::info("Iniciando generación de documentos para Agreement #{$this->agreement->id}");

            // Actualizar estado a "generando"
            $this->agreement->update([
                'status' => 'documents_generating'
            ]);

            // Generar todos los documentos
            $documents = $pdfService->generateAllDocuments($this->agreement);

            // Verificar que todos los documentos se generaron correctamente
            if (!$pdfService->verifyDocumentsGenerated($this->agreement)) {
                throw new \Exception('No todos los documentos fueron generados correctamente');
            }

            // Enviar notificación de éxito al usuario si está especificado
            if ($this->userId) {
                Notification::make()
                    ->title('Documentos Generados Exitosamente')
                    ->body("Se generaron {count($documents)} documentos para el convenio #{$this->agreement->id}")
                    ->success()
                    ->sendToDatabase(\App\Models\User::find($this->userId));
            }

            Log::info("Documentos generados exitosamente para Agreement #{$this->agreement->id}", [
                'documents_count' => count($documents),
                'total_size' => $pdfService->getTotalDocumentsSize($this->agreement)
            ]);

        } catch (\Exception $e) {
            Log::error("Error generando documentos para Agreement #{$this->agreement->id}: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Actualizar estado a error
            $this->agreement->update([
                'status' => 'error_generating_documents'
            ]);

            // Enviar notificación de error al usuario si está especificado
            if ($this->userId) {
                Notification::make()
                    ->title('Error al Generar Documentos')
                    ->body("Ocurrió un error al generar los documentos del convenio #{$this->agreement->id}")
                    ->danger()
                    ->sendToDatabase(\App\Models\User::find($this->userId));
            }
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Job de generación de documentos falló definitivamente para Agreement #{$this->agreement->id}", [
            'exception' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Marcar como error definitivo
        $this->agreement->update([
            'status' => 'error_generating_documents'
        ]);

        // Limpiar documentos parcialmente generados
        $pdfService = app(PdfGenerationService::class);
        $pdfService->cleanupGeneratedDocuments($this->agreement);
    }
}
