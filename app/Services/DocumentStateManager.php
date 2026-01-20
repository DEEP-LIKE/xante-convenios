<?php

namespace App\Services;

use App\Models\Agreement;
use Filament\Notifications\Notification;

/**
 * Servicio para gestionar el estado del wizard de documentos
 *
 * Responsabilidades:
 * - Determinar el paso actual basado en el estado del convenio
 * - Decidir si marcar como completado
 * - Actualizar estado del convenio
 */
class DocumentStateManager
{
    /**
     * Determina el paso actual basado en el estado del convenio
     */
    public function determineCurrentStep(Agreement $agreement): int
    {
        return match ($agreement->status) {
            'documents_generating', 'documents_generated' => 1,
            'documents_sent', 'awaiting_client_docs' => 2,
            'documents_complete', 'completed' => 3,
            default => 1
        };
    }

    /**
     * Verifica si se debe marcar el convenio como completado
     */
    public function shouldMarkAsCompleted(Agreement $agreement, int $currentStep): bool
    {
        return $currentStep === 3 && $agreement->status !== 'completed';
    }

    /**
     * Marca el convenio como completado
     */
    public function markAsCompleted(Agreement $agreement): void
    {
        try {
            $agreement->update([
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
                ->body('Error al completar el convenio: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Actualiza los datos del paso actual
     */
    public function updateStepData(Agreement $agreement, int $step, array $data): bool
    {
        try {
            return $agreement->update([
                'current_wizard' => 2,
                'wizard2_current_step' => $step,
                'wizard_data' => array_merge($agreement->wizard_data ?? [], $data),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating step data', [
                'agreement_id' => $agreement->id,
                'step' => $step,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Obtiene el mensaje de estado según el estado del convenio
     */
    public function getStatusMessage(Agreement $agreement): ?string
    {
        $statusMessages = [
            'documents_generating' => 'Los documentos se están generando en segundo plano...',
            'documents_generated' => 'Documentos listos para enviar al cliente',
            'documents_sent' => 'Documentos enviados, esperando documentos del cliente',
            'awaiting_client_docs' => 'Esperando que el cliente suba sus documentos',
            'documents_complete' => 'Todos los documentos recibidos',
            'completed' => 'Convenio completado exitosamente',
        ];

        return $statusMessages[$agreement->status] ?? null;
    }

    /**
     * Muestra notificación del estado actual
     */
    public function showStatusNotification(Agreement $agreement): void
    {
        $message = $this->getStatusMessage($agreement);

        if ($message) {
            Notification::make()
                ->title('Estado del Convenio')
                ->body($message)
                ->info()
                ->duration(5000)
                ->send();
        }
    }
}
