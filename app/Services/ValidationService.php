<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\QuoteValidation;
use App\Models\User;
use App\Notifications\ValidationRequestedNotification;
use App\Notifications\ValidationApprovedNotification;
use App\Notifications\ValidationWithObservationsNotification;
use App\Notifications\ValidationRejectedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ValidationService
{
    /**
     * Crear solicitud de validación
     */
    public function requestValidation(Agreement $agreement, User $user): QuoteValidation
    {
        return DB::transaction(function () use ($agreement, $user) {
            // Crear la validación usando el método del modelo
            $validation = $agreement->requestValidation($user->id);

            // Notificar a coordinadores solo si es una nueva validación
            if ($validation->wasRecentlyCreated) {
                $this->notifyCoordinators($validation);
                
                Log::info('Validación solicitada', [
                    'agreement_id' => $agreement->id,
                    'validation_id' => $validation->id,
                    'requested_by' => $user->id,
                ]);
            } else {
                Log::info('Validación actualizada (cambios en wizard)', [
                    'agreement_id' => $agreement->id,
                    'validation_id' => $validation->id,
                    'updated_by' => $user->id,
                ]);
            }

            return $validation;
        });
    }

    /**
     * Aprobar validación
     */
    public function approveValidation(QuoteValidation $validation, User $coordinator): void
    {
        DB::transaction(function () use ($validation, $coordinator) {
            $validation->approve($coordinator->id);

            // Notificar al ejecutivo
            $this->notifyExecutive($validation, 'approved');

            Log::info('Validación aprobada', [
                'validation_id' => $validation->id,
                'approved_by' => $coordinator->id,
            ]);
        });
    }

    /**
     * Solicitar cambios
     */
    public function requestChanges(QuoteValidation $validation, User $coordinator, string $observations): void
    {
        DB::transaction(function () use ($validation, $coordinator, $observations) {
            $validation->requestChanges($coordinator->id, $observations);

            // Notificar al ejecutivo
            $this->notifyExecutive($validation, 'with_observations');

            Log::info('Cambios solicitados en validación', [
                'validation_id' => $validation->id,
                'coordinator_id' => $coordinator->id,
            ]);
        });
    }

    /**
     * Rechazar validación
     */
    public function rejectValidation(QuoteValidation $validation, User $coordinator, string $reason): void
    {
        DB::transaction(function () use ($validation, $coordinator, $reason) {
            $validation->reject($coordinator->id, $reason);

            // Notificar al ejecutivo
            $this->notifyExecutive($validation, 'rejected');

            Log::info('Validación rechazada', [
                'validation_id' => $validation->id,
                'rejected_by' => $coordinator->id,
            ]);
        });
    }

    /**
     * Obtener snapshot de calculadora
     */
    public function getCalculatorSnapshot(Agreement $agreement): array
    {
        return [
            'precio_promocion' => $agreement->precio_promocion,
            'valor_convenio' => $agreement->valor_convenio,
            'porcentaje_comision_sin_iva' => $agreement->porcentaje_comision_sin_iva,
            'monto_credito' => $agreement->monto_credito,
            'tipo_credito' => $agreement->tipo_credito,
            'otro_banco' => $agreement->otro_banco,
            'isr' => $agreement->isr,
            'cancelacion_hipoteca' => $agreement->cancelacion_hipoteca,
            'comision_total' => $agreement->comision_total,
            'ganancia_final' => $agreement->ganancia_final,
            'indicador_ganancia' => $agreement->indicador_ganancia,
            'total_gastos_fi' => $agreement->total_gastos_fi,
        ];
    }

    /**
     * Notificar a coordinadores FI
     */
    public function notifyCoordinators(QuoteValidation $validation): void
    {
        // Obtener todos los coordinadores FI y gerencia
        $coordinators = User::whereIn('role', ['coordinador_fi', 'gerencia'])->get();

        foreach ($coordinators as $coordinator) {
            try {
                $coordinator->notify(new ValidationRequestedNotification($validation->id));
            } catch (\Exception $e) {
                Log::error('Error al notificar coordinador', [
                    'coordinator_id' => $coordinator->id,
                    'validation_id' => $validation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Notificar al ejecutivo
     */
    public function notifyExecutive(QuoteValidation $validation, string $action): void
    {
        $executive = $validation->requestedBy;

        if (!$executive) {
            return;
        }

        try {
            $notification = match($action) {
                'approved' => new ValidationApprovedNotification($validation->id),
                'with_observations' => new ValidationWithObservationsNotification($validation->id),
                'rejected' => new ValidationRejectedNotification($validation->id),
                default => null,
            };

            if ($notification) {
                $executive->notify($notification);
            }
        } catch (\Exception $e) {
            Log::error('Error al notificar ejecutivo', [
                'executive_id' => $executive->id,
                'validation_id' => $validation->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Obtener validaciones pendientes para un coordinador
     */
    public function getPendingValidations(): \Illuminate\Database\Eloquent\Collection
    {
        return QuoteValidation::with(['agreement', 'requestedBy'])
            ->pending()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtener historial de validaciones de un agreement
     */
    public function getValidationHistory(Agreement $agreement): \Illuminate\Database\Eloquent\Collection
    {
        return $agreement->validations()
            ->with(['requestedBy', 'validatedBy'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Verificar si un agreement puede generar documentos
     */
    public function canGenerateDocuments(Agreement $agreement): bool
    {
        return $agreement->canGenerateDocuments();
    }
}
