<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\WizardProgress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WizardService
{
    /**
     * Crea un nuevo convenio con wizard
     */
    public function createAgreement(array $initialData = []): Agreement
    {
        return DB::transaction(function () use ($initialData) {
            $agreement = Agreement::create([
                'current_step' => 1,
                'completion_percentage' => 0,
                'status' => 'expediente_incompleto',
                'created_by' => Auth::id(),
                'client_xante_id' => $initialData['client_xante_id'] ?? null, // Solo asignar si existe
                'wizard_data' => $initialData,
            ]);

            // Inicializar progreso del wizard
            $this->initializeWizardProgress($agreement);

            return $agreement;
        });
    }

    /**
     * Inicializa el progreso del wizard para un convenio
     */
    public function initializeWizardProgress(Agreement $agreement): void
    {
        $steps = $agreement->getWizardSteps();

        foreach ($steps as $stepNumber => $stepName) {
            WizardProgress::create([
                'agreement_id' => $agreement->id,
                'step_number' => $stepNumber,
                'step_name' => $stepName,
                'is_completed' => false,
                'completion_percentage' => 0,
                'created_by' => Auth::id(),
            ]);
        }
    }

    /**
     * Guarda el progreso de un paso específico
     */
    public function saveStep(int $agreementId, int $stepNumber, array $data): bool
    {
        try {
            return DB::transaction(function () use ($agreementId, $stepNumber, $data) {
                $agreement = Agreement::findOrFail($agreementId);

                // Validar que el paso sea accesible
                if (! $agreement->canAccessStep($stepNumber)) {
                    throw new \Exception("No se puede acceder al paso {$stepNumber}. Complete los pasos anteriores primero.");
                }

                // Validar los datos del paso
                $validationResult = $this->validateStep($stepNumber, $data);
                if (! $validationResult->isValid()) {
                    throw ValidationException::withMessages($validationResult->getErrors());
                }

                // Actualizar el progreso del paso
                $progress = WizardProgress::where('agreement_id', $agreementId)
                    ->where('step_number', $stepNumber)
                    ->first();

                if ($progress) {
                    $progress->update([
                        'step_data' => array_merge($progress->step_data ?? [], $data),
                        'last_saved_at' => now(),
                        'updated_by' => Auth::id(),
                    ]);
                }

                // Actualizar datos del convenio según el paso
                $this->updateAgreementFromStep($agreement, $stepNumber, $data);

                // Actualizar paso actual si es necesario
                if ($stepNumber > $agreement->current_step) {
                    $agreement->update(['current_step' => $stepNumber]);
                }

                return true;
            });
        } catch (\Exception $e) {
            \Log::error('Error saving wizard step: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Valida los datos de un paso específico
     */
    public function validateStep(int $stepNumber, array $data): ValidationResult
    {
        $errors = [];

        switch ($stepNumber) {
            case 1: // Búsqueda e Identificación
                $errors = $this->validateStep1($data);
                break;

            case 2: // Datos del Cliente Titular
                $errors = $this->validateStep2($data);
                break;

            case 3: // Datos del Cónyuge/Coacreditado
                $errors = $this->validateStep3($data);
                break;

            case 4: // Información de la Propiedad
                $errors = $this->validateStep4($data);
                break;

            case 5: // Calculadora de Convenio
                $errors = $this->validateStep5($data);
                break;

            case 6: // Documentación y Cierre
                $errors = $this->validateStep6($data);
                break;
        }

        return new ValidationResult(empty($errors), $errors);
    }

    /**
     * Determina el siguiente paso basado en el paso actual y los datos
     */
    public function getNextStep(int $currentStep, array $agreementData): int
    {
        switch ($currentStep) {
            case 1:
                return 2; // Siempre ir a datos del titular

            case 2:
                // Verificar si necesita datos del cónyuge
                $civilStatus = $agreementData['holder_civil_status'] ?? '';
                if (in_array($civilStatus, ['casado', 'union_libre'])) {
                    return 3; // Ir a datos del cónyuge
                }

                return 4; // Saltar a información de propiedad

            case 3:
                return 4; // Ir a información de propiedad

            case 4:
                return 5; // Ir a calculadora

            case 5:
                return 6; // Ir a documentación

            case 6:
                return 6; // Ya es el último paso

            default:
                return min($currentStep + 1, 6);
        }
    }

    /**
     * Calcula el porcentaje de progreso total
     */
    public function calculateProgress(int $agreementId): int
    {
        $agreement = Agreement::findOrFail($agreementId);

        $totalSteps = count($agreement->getWizardSteps());
        $completedSteps = WizardProgress::where('agreement_id', $agreementId)
            ->where('is_completed', true)
            ->count();

        return $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100) : 0;
    }

    /**
     * Marca un paso como completado
     */
    public function markStepAsCompleted(int $agreementId, int $stepNumber): void
    {
        DB::transaction(function () use ($agreementId, $stepNumber) {
            $progress = WizardProgress::where('agreement_id', $agreementId)
                ->where('step_number', $stepNumber)
                ->first();

            if ($progress) {
                $progress->markAsCompleted();
            }

            // Actualizar porcentaje de completitud del convenio
            $agreement = Agreement::findOrFail($agreementId);
            $agreement->calculateCompletionPercentage();
        });
    }

    /**
     * Obtiene el resumen del progreso del wizard
     */
    public function getWizardSummary(int $agreementId): array
    {
        $agreement = Agreement::with('wizardProgress')->findOrFail($agreementId);

        $steps = [];
        foreach ($agreement->wizardProgress as $progress) {
            $steps[$progress->step_number] = [
                'name' => $progress->step_name,
                'is_completed' => $progress->is_completed,
                'completion_percentage' => $progress->completion_percentage,
                'last_saved_at' => $progress->last_saved_at,
                'can_access' => $agreement->canAccessStep($progress->step_number),
            ];
        }

        return [
            'agreement_id' => $agreementId,
            'current_step' => $agreement->current_step,
            'overall_completion' => $agreement->completion_percentage,
            'steps' => $steps,
            'next_step' => $agreement->getNextStep(),
            'previous_step' => $agreement->getPreviousStep(),
        ];
    }

    /**
     * Clona un convenio existente para acelerar la captura
     */
    public function cloneAgreement(int $sourceAgreementId, array $overrides = []): Agreement
    {
        return DB::transaction(function () use ($sourceAgreementId, $overrides) {
            $sourceAgreement = Agreement::findOrFail($sourceAgreementId);

            $newAgreementData = $sourceAgreement->toArray();

            // Remover campos que no deben clonarse
            unset($newAgreementData['id'], $newAgreementData['created_at'], $newAgreementData['updated_at']);

            // Resetear campos del wizard
            $newAgreementData['current_step'] = 1;
            $newAgreementData['completion_percentage'] = 0;
            $newAgreementData['status'] = 'expediente_incompleto';
            $newAgreementData['created_by'] = Auth::id();
            $newAgreementData['completed_at'] = null;

            // Aplicar overrides
            $newAgreementData = array_merge($newAgreementData, $overrides);

            $newAgreement = Agreement::create($newAgreementData);

            // Inicializar progreso del wizard
            $this->initializeWizardProgress($newAgreement);

            return $newAgreement;
        });
    }

    // Métodos de validación privados para cada paso

    private function validateStep1(array $data): array
    {
        $errors = [];

        if (empty($data['search_type'])) {
            $errors['search_type'] = 'Debe seleccionar un tipo de búsqueda';
        }

        if (empty($data['search_term'])) {
            $errors['search_term'] = 'Debe ingresar un término de búsqueda';
        }

        return $errors;
    }

    private function validateStep2(array $data): array
    {
        $errors = [];

        $requiredFields = ['holder_name', 'holder_email', 'holder_phone'];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[$field] = 'Este campo es obligatorio';
            }
        }

        // Validar email
        if (! empty($data['holder_email']) && ! filter_var($data['holder_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['holder_email'] = 'El formato del email no es válido';
        }

        return $errors;
    }

    private function validateStep3(array $data): array
    {
        $errors = [];

        // Solo validar si se proporcionan datos del cónyuge
        if (! empty($data['spouse_name'])) {
            if (! empty($data['spouse_email']) && ! filter_var($data['spouse_email'], FILTER_VALIDATE_EMAIL)) {
                $errors['spouse_email'] = 'El formato del email no es válido';
            }
        }

        return $errors;
    }

    private function validateStep4(array $data): array
    {
        $errors = [];

        $requiredFields = ['domicilio_vivienda', 'tipo_vivienda'];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[$field] = 'Este campo es obligatorio';
            }
        }

        return $errors;
    }

    private function validateStep5(array $data): array
    {
        $errors = [];

        if (empty($data['precio_promocion']) && empty($data['valor_convenio'])) {
            $errors['base_value'] = 'Debe ingresar el precio de promoción o el valor del convenio';
        }

        if (! empty($data['precio_promocion']) && $data['precio_promocion'] <= 0) {
            $errors['precio_promocion'] = 'El precio de promoción debe ser mayor a cero';
        }

        return $errors;
    }

    private function validateStep6(array $data): array
    {
        $errors = [];

        // Validar que al menos algunos documentos estén marcados
        if (empty($data['documents_checklist']) || count($data['documents_checklist']) < 3) {
            $errors['documents_checklist'] = 'Debe marcar al menos 3 documentos como entregados';
        }

        return $errors;
    }

    /**
     * Actualiza el convenio basado en los datos del paso
     */
    private function updateAgreementFromStep(Agreement $agreement, int $stepNumber, array $data): void
    {
        switch ($stepNumber) {
            case 1:
                // Actualizar datos de búsqueda/identificación
                if (! empty($data['client_xante_id'])) {
                    $agreement->update(['client_xante_id' => $data['client_xante_id']]);
                }
                break;

            case 2:
                // Actualizar datos del titular
                $holderFields = array_filter($data, function ($key) {
                    return strpos($key, 'holder_') === 0;
                }, ARRAY_FILTER_USE_KEY);

                if (! empty($holderFields)) {
                    $agreement->update($holderFields);
                }
                break;

            case 3:
                // Actualizar datos del cónyuge
                $spouseFields = array_filter($data, function ($key) {
                    return strpos($key, 'spouse_') === 0;
                }, ARRAY_FILTER_USE_KEY);

                if (! empty($spouseFields)) {
                    $agreement->update($spouseFields);
                }
                break;

            case 4:
                // Actualizar datos de la propiedad
                $propertyFields = ['domicilio_vivienda', 'comunidad', 'tipo_vivienda', 'prototipo'];
                $updateData = array_intersect_key($data, array_flip($propertyFields));

                if (! empty($updateData)) {
                    $agreement->update($updateData);
                }
                break;

            case 5:
                // Actualizar datos de la calculadora
                $calculatorFields = [
                    'precio_promocion', 'valor_convenio', 'porcentaje_comision_sin_iva',
                    'monto_credito', 'tipo_credito', 'otro_banco', 'isr', 'cancelacion_hipoteca',
                ];
                $updateData = array_intersect_key($data, array_flip($calculatorFields));

                if (! empty($updateData)) {
                    $agreement->update($updateData);
                }
                break;

            case 6:
                // Actualizar documentos y estado final
                if (! empty($data['documents_checklist'])) {
                    $agreement->update(['documents_checklist' => $data['documents_checklist']]);
                }

                // Si el paso 6 está completo, marcar como convenio en proceso
                if ($this->isStep6Complete($data)) {
                    $agreement->update(['status' => 'convenio_proceso']);
                }
                break;
        }
    }

    /**
     * Verifica si el paso 6 está completo
     */
    private function isStep6Complete(array $data): bool
    {
        return ! empty($data['documents_checklist']) && count($data['documents_checklist']) >= 5;
    }
}

/**
 * Clase helper para resultados de validación
 */
class ValidationResult
{
    private bool $valid;

    private array $errors;

    public function __construct(bool $valid, array $errors = [])
    {
        $this->valid = $valid;
        $this->errors = $errors;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
