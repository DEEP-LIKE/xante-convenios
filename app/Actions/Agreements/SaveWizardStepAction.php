<?php

namespace App\Actions\Agreements;

use App\Models\Agreement;
use Filament\Notifications\Notification;

class SaveWizardStepAction
{
    public function execute(int $agreementId, int $step, array $data, callable $updateClientData = null): bool
    {
        try {
            if (!$agreementId) {
                Notification::make()
                    ->title('Error de guardado')
                    ->body('No se pudo identificar el convenio. Por favor, intente nuevamente.')
                    ->danger()
                    ->duration(5000)
                    ->send();
                return false;
            }

            $agreement = Agreement::find($agreementId);

            if (!$agreement) {
                Notification::make()
                    ->title('Error de guardado')
                    ->body('No se encontró el convenio en la base de datos.')
                    ->danger()
                    ->duration(5000)
                    ->send();
                return false;
            }

            // Actualizar datos del convenio
            try {
                $updated = $agreement->update([
                    'current_step' => $step,
                    'wizard_data' => $data,
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                Notification::make()
                    ->title('⚠️ Error de guardado')
                    ->body('Error al guardar en BD: ' . $e->getMessage())
                    ->danger()
                    ->duration(8000)
                    ->send();
                return false;
            }

            if (!$updated) {
                Notification::make()
                    ->title('Error de guardado')
                    ->body('No se pudieron guardar los datos. Por favor, intente nuevamente.')
                    ->danger()
                    ->duration(5000)
                    ->send();
                return false;
            }

            // Calcular porcentaje de completitud
            $agreement->calculateCompletionPercentage();

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

            // Si estamos en el paso 2 y hay un cliente seleccionado, actualizar sus datos
            if ($step === 2 && isset($data['client_id']) && $data['client_id'] && $updateClientData) {
                $updateClientData($data['client_id']);
            }

            return true;

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error inesperado')
                ->body('Ocurrió un error al guardar: ' . $e->getMessage())
                ->danger()
                ->duration(8000)
                ->send();
            return false;
        }
    }
}
