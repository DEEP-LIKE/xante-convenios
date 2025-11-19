<?php

namespace App\Actions\Agreements;

use App\Models\Agreement;
use Filament\Notifications\Notification;

/**
 * Action para guardar datos de un paso del wizard de documentos
 */
class SaveDocumentStepAction
{
    /**
     * Guarda los datos del paso actual
     */
    public function execute(Agreement $agreement, int $step, array $data): bool
    {
        try {
            $updated = $agreement->update([
                'current_wizard' => 2,
                'wizard2_current_step' => $step,
                'wizard_data' => array_merge($agreement->wizard_data ?? [], $data),
                'updated_at' => now(),
            ]);

            if ($updated && $step >= 1) {
                Notification::make()
                    ->title("Guardando")
                    ->body("Se ha guardado el paso #{$step}")
                    ->icon('heroicon-o-server')
                    ->success()
                    ->duration(4000)
                    ->send();
            }

            return $updated;

        } catch (\Exception $e) {
            \Log::error('Error saving document step data', [
                'agreement_id' => $agreement->id,
                'step' => $step,
                'error' => $e->getMessage()
            ]);

            Notification::make()
                ->title('⚠️ Error de guardado')
                ->body('Error al guardar en BD: ' . $e->getMessage())
                ->danger()
                ->duration(8000)
                ->send();

            return false;
        }
    }
}
