<?php

namespace App\Actions\Agreements;

use App\Models\Agreement;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SaveWizardStepAction
{
    public function execute(?int $agreementId, int $step, array $data, callable $updateClientData = null): array
    {
        try {
            // Si no hay agreementId, crear un nuevo Agreement
            if (!$agreementId) {
                $clientId = $data['client_id'] ?? null;
                
                if (!$clientId) {
                    Notification::make()
                        ->title('Error de guardado')
                        ->body('Debe seleccionar un cliente antes de continuar.')
                        ->danger()
                        ->duration(5000)
                        ->send();
                    return ['success' => false, 'agreementId' => null];
                }
                
                // Crear nuevo Agreement
                $agreement = Agreement::create([
                    'client_id' => $clientId,
                    'client_xante_id' => $data['xante_id'] ?? null,
                    'status' => 'sin_convenio',
                    'current_step' => $step,
                    'wizard_data' => $data,
                    'created_by' => auth()->id() ?? 1,
                ]);
                
                $agreementId = $agreement->id;
                
                Log::info('Nuevo Agreement creado desde wizard', [
                    'agreement_id' => $agreementId,
                    'client_id' => $clientId,
                    'step' => $step
                ]);
            } else {
                $agreement = Agreement::find($agreementId);

                if (!$agreement) {
                    Notification::make()
                        ->title('Error de guardado')
                        ->body('No se encontró el convenio en la base de datos.')
                        ->danger()
                        ->duration(5000)
                        ->send();
                    return ['success' => false, 'agreementId' => null];
                }
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
                return ['success' => false, 'agreementId' => $agreementId];
            }

            if (!$updated) {
                Notification::make()
                    ->title('Error de guardado')
                    ->body('No se pudieron guardar los datos. Por favor, intente nuevamente.')
                    ->danger()
                    ->duration(5000)
                    ->send();
                return ['success' => false, 'agreementId' => $agreementId];
            }

            // Calcular porcentaje de completitud
            $agreement->calculateCompletionPercentage();

            // Mostrar notificación de guardado automático
            if ($step >= 1) {
                // Mapeo de nombres de pasos
                $stepNames = [
                    1 => 'Identificación',
                    2 => 'Cliente',
                    3 => 'Propiedad',
                    4 => 'Calculadora',
                    5 => 'Validación',
                ];

                $completedStep = $step > 1 ? $step - 1 : 1;
                $stepName = $stepNames[$completedStep] ?? "Paso #{$completedStep}";

                Notification::make()
                    ->title("Guardando")
                    ->body("Se ha guardado el paso: {$stepName}")
                    ->icon('heroicon-o-server')
                    ->success()
                    ->duration(4000)
                    ->send();
            }

            // Si estamos en el paso 2 y hay un cliente seleccionado, actualizar sus datos
            if ($step === 2 && isset($data['client_id']) && $data['client_id'] && $updateClientData) {
                $updateClientData($data['client_id']);
            }

            return ['success' => true, 'agreementId' => $agreementId];

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error inesperado')
                ->body('Ocurrió un error al guardar: ' . $e->getMessage())
                ->danger()
                ->duration(8000)
                ->send();
            return ['success' => false, 'agreementId' => null];
        }
    }
}
