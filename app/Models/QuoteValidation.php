<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuoteValidation extends Model
{
    use HasFactory;

    protected $fillable = [
        'agreement_id',
        'requested_by',
        'validated_by',
        'status',
        'observations',
        'calculator_snapshot',
        'validated_at',
        'revision_number',
    ];

    protected function casts(): array
    {
        return [
            'validated_at' => 'datetime',
            'calculator_snapshot' => 'array',
            'revision_number' => 'integer',
        ];
    }

    // Relaciones
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function authorizations(): HasMany
    {
        return $this->hasMany(QuoteAuthorization::class, 'quote_validation_id');
    }

    public function latestAuthorization()
    {
        return $this->hasOne(QuoteAuthorization::class, 'quote_validation_id')->latestOfMany();
    }

    // Métodos de estado
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function hasObservations(): bool
    {
        return $this->status === 'with_observations';
    }

    public function isAwaitingAuthorization(): bool
    {
        return $this->status === 'awaiting_management_authorization';
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeWithObservations($query)
    {
        return $query->where('status', 'with_observations');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('requested_by', $userId);
    }

    public function scopeForAgreement($query, int $agreementId)
    {
        return $query->where('agreement_id', $agreementId);
    }

    public function scopeAwaitingAuthorization($query)
    {
        return $query->where('status', 'awaiting_management_authorization');
    }

    // Métodos de acción
    public function approve(int $validatorId): bool
    {
        $this->status = 'approved';
        $this->validated_by = $validatorId;
        $this->validated_at = now();

        $saved = $this->save();

        if ($saved) {
            // Obtener snapshot con los valores finales validados
            $snapshot = $this->calculator_snapshot;

            // Sincronizar wizard_data con los valores validados
            $currentWizardData = $this->agreement->wizard_data ?? [];

            // Mapear snapshot a las claves usadas en wizard_data
            $updates = [
                'valor_convenio' => (float) str_replace([',', '$'], '', $snapshot['valor_convenio'] ?? 0),
                'valor_compraventa' => (float) str_replace([',', '$'], '', $snapshot['valor_compraventa'] ?? 0),
                'porcentaje_comision_sin_iva' => (float) str_replace([',', '$'], '', $snapshot['porcentaje_comision_sin_iva'] ?? 0),
                'precio_promocion' => (float) str_replace([',', '$'], '', $snapshot['precio_promocion'] ?? 0),
                'monto_credito' => (float) str_replace([',', '$'], '', $snapshot['monto_credito'] ?? 0),
                'tipo_credito' => $snapshot['tipo_credito'] ?? 'ninguno',
                'isr' => (float) str_replace([',', '$'], '', $snapshot['isr'] ?? 0),
                'cancelacion_hipoteca' => (float) str_replace([',', '$'], '', $snapshot['cancelacion_hipoteca'] ?? 0),
                'monto_comision_sin_iva' => (float) str_replace([',', '$'], '', $snapshot['monto_comision_sin_iva'] ?? 0),
                'comision_total_pagar' => (float) str_replace([',', '$'], '', $snapshot['comision_total_pagar'] ?? 0),
                'ganancia_final' => (float) str_replace([',', '$'], '', $snapshot['ganancia_final'] ?? 0),
                'total_gastos_fi_venta' => (float) str_replace([',', '$'], '', $snapshot['total_gastos_fi_venta'] ?? 0),
                'state_commission_percentage' => (float) ($snapshot['multiplicador_estado'] ?? 0),
                'indicador_ganancia' => $snapshot['indicador_ganancia'] ?? 'N/A',
            ];

            // Actualizar el agreement con el estado de validación y wizard_data
            $this->agreement->update([
                'validation_status' => 'approved',
                'can_generate_documents' => true,
                'wizard_data' => array_merge($currentWizardData, $updates),
            ]);

            // Crear registro en el historial de recálculos
            \App\Models\AgreementRecalculation::create([
                'agreement_id' => $this->agreement_id,
                'user_id' => $this->requested_by,
                'authorized_by' => $validatorId,
                'recalculation_number' => $this->agreement->recalculations()->count() + 1,
                'agreement_value' => $updates['valor_convenio'],
                'proposal_value' => $updates['precio_promocion'],
                'commission_total' => $updates['comision_total_pagar'],
                'final_profit' => $updates['ganancia_final'],
                'calculation_data' => $snapshot,
                'motivo' => $snapshot['motivo'] ?? $this->latestAuthorization?->discount_reason ?? 'Recálculo autorizado por validación',
            ]);
        }

        return $saved;
    }

    public function reject(int $validatorId, string $reason): bool
    {
        $this->status = 'rejected';
        $this->validated_by = $validatorId;
        $this->validated_at = now();
        $this->observations = $reason;

        $saved = $this->save();

        if ($saved) {
            // Actualizar el agreement
            $this->agreement->update([
                'validation_status' => 'rejected',
                'can_generate_documents' => false,
            ]);
        }

        return $saved;
    }

    public function requestChanges(int $validatorId, string $observations): bool
    {
        $this->status = 'with_observations';
        $this->validated_by = $validatorId;
        $this->validated_at = now();
        $this->observations = $observations;

        $saved = $this->save();

        if ($saved) {
            // Actualizar el agreement
            $this->agreement->update([
                'validation_status' => 'with_observations',
                'can_generate_documents' => false,
            ]);
        }

        return $saved;
    }

    // Métodos de utilidad
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'approved' => 'Aprobada',
            'rejected' => 'Rechazada',
            'with_observations' => 'Con Observaciones',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'with_observations' => 'info',
            'awaiting_management_authorization' => 'primary',
            default => 'gray',
        };
    }

    /**
     * Solicita autorización de gerencia para cambios en valores
     */
    public function requestAuthorization(
        int $requestedById,
        ?float $newPrice = null,
        ?float $newCommissionPercentage = null,
        ?string $justification = null,
        ?float $explicitOldPrice = null,
        ?float $explicitOldCommission = null,
        ?float $newIsr = null,
        ?float $newCancelacionHipoteca = null,
        ?float $newMontoCredito = null,
        ?float $oldIsr = null,
        ?float $oldCancelacionHipoteca = null,
        ?float $oldMontoCredito = null
    ): QuoteAuthorization {
        $snapshot = $this->calculator_snapshot;

        // Obtener valores originales si no son explícitos
        $oldPrice = $explicitOldPrice ?? (float) str_replace([',', '$'], '', $snapshot['valor_convenio'] ?? 0);
        $oldCommission = $explicitOldCommission ?? (float) str_replace([',', '$'], '', $snapshot['porcentaje_comision_sin_iva'] ?? 0);

        $oldIsr = $oldIsr ?? (float) str_replace([',', '$'], '', $snapshot['isr'] ?? 0);
        $oldCancelacionHipoteca = $oldCancelacionHipoteca ?? (float) str_replace([',', '$'], '', $snapshot['cancelacion_hipoteca'] ?? 0);
        $oldMontoCredito = $oldMontoCredito ?? (float) str_replace([',', '$'], '', $snapshot['monto_credito'] ?? 0);

        // Determinar tipo de cambio
        $priceChanged = abs(($newPrice ?? 0) - ($oldPrice ?? 0)) > 0.01;
        $commissionChanged = abs(($newCommissionPercentage ?? 0) - ($oldCommission ?? 0)) > 0.01;

        if ($priceChanged && $commissionChanged) {
            $changeType = 'both';
        } elseif ($priceChanged) {
            $changeType = 'price';
        } elseif ($commissionChanged) {
            $changeType = 'commission';
        } else {
            $changeType = 'recalculation';
        }

        // Buscar autorización pendiente existente
        $existingAuthorization = $this->authorizations()
            ->where('status', 'pending')
            ->latest()
            ->first();

        $data = [
            'quote_validation_id' => $this->id, // Added this line as it was in the original create but missing in the provided data array
            'agreement_id' => $this->agreement_id,
            'requested_by' => $requestedById,
            'change_type' => $changeType,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'old_commission_percentage' => $oldCommission,
            'new_commission_percentage' => $newCommissionPercentage,
            'old_isr' => $oldIsr,
            'new_isr' => $newIsr,
            'old_cancelacion_hipoteca' => $oldCancelacionHipoteca,
            'new_cancelacion_hipoteca' => $newCancelacionHipoteca,
            'old_monto_credito' => $oldMontoCredito,
            'new_monto_credito' => $newMontoCredito,
            'discount_reason' => $justification,
            'status' => 'pending',
        ];

        if ($existingAuthorization) {
            $existingAuthorization->update($data);
            $authorization = $existingAuthorization;
        } else {
            $authorization = $this->authorizations()->create($data);
        }

        // Actualizar estado de la validación
        $this->status = 'awaiting_management_authorization';
        $this->save();

        return $authorization;
    }

    /**
     * Verifica si hay cambios en los valores comparado con el snapshot original
     */
    public function hasValueChanges(float $currentPrice, float $currentCommission): bool
    {
        $snapshot = $this->calculator_snapshot;

        // Sanitizar valores del snapshot (pueden venir con formato de miles/moneda)
        $originalPrice = (float) str_replace([',', '$', ' '], '', $snapshot['valor_convenio'] ?? 0);
        $originalCommission = (float) str_replace([',', '$', ' '], '', $snapshot['porcentaje_comision_sin_iva'] ?? 0);

        // Comparar con tolerancia de 0.01 para evitar problemas de precisión de punto flotante
        $commissionChanged = abs($currentCommission - $originalCommission) > 0.01;

        // Según requerimiento, solo los cambios de comisión requieren el desvío de autorización de gerencia
        // El valor de convenio y gastos pueden ser aprobados directamente por el coordinador
        return $commissionChanged;
    }
}
