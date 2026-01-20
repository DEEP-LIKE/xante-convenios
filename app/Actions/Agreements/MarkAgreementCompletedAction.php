<?php

namespace App\Actions\Agreements;

use App\Models\Agreement;
use Filament\Notifications\Notification;

/**
 * Action para marcar un convenio como completado
 */
class MarkAgreementCompletedAction
{
    /**
     * Marca el convenio como completado
     */
    public function execute(Agreement $agreement): void
    {
        try {
            $agreement->update([
                'status' => 'completed',
                'completed_at' => now(),
                'wizard2_current_step' => 3,
                'completion_percentage' => 100,
            ]);

            Notification::make()
                ->title('🎉 Convenio Completado')
                ->body('El convenio ha sido finalizado exitosamente. Todos los documentos han sido recibidos y validados.')
                ->success()
                ->duration(5000)
                ->send();

        } catch (\Exception $e) {
            \Log::error('Error marking agreement as completed', [
                'agreement_id' => $agreement->id,
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('❌ Error al Completar')
                ->body('Error: '.$e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }
}
