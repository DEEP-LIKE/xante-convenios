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
            // Actualizar el agreement
            $this->agreement->update([
                'validation_status' => 'approved',
                'can_generate_documents' => true,
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
        return match($this->status) {
            'pending' => 'Pendiente',
            'approved' => 'Aprobada',
            'rejected' => 'Rechazada',
            'with_observations' => 'Con Observaciones',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
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
        ?float $newCommissionPercentage = null
    ): QuoteAuthorization {
        // Determinar tipo de cambio
        $changeType = 'both';
        if ($newPrice && !$newCommissionPercentage) {
            $changeType = 'price';
        } elseif (!$newPrice && $newCommissionPercentage) {
            $changeType = 'commission';
        }

        // Obtener valores originales del snapshot
        $snapshot = $this->calculator_snapshot;
        $oldPrice = $snapshot['valor_convenio'] ?? null;
        $oldCommission = $snapshot['porcentaje_comision_sin_iva'] ?? null;

        // Crear la autorización
        $authorization = QuoteAuthorization::create([
            'quote_validation_id' => $this->id,
            'agreement_id' => $this->agreement_id,
            'requested_by' => $requestedById,
            'status' => 'pending',
            'change_type' => $changeType,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'old_commission_percentage' => $oldCommission,
            'new_commission_percentage' => $newCommissionPercentage,
        ]);

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
        $originalPrice = (float) ($snapshot['valor_convenio'] ?? 0);
        $originalCommission = (float) ($snapshot['porcentaje_comision_sin_iva'] ?? 0);

        $priceChanged = abs($currentPrice - $originalPrice) > 0.01;
        $commissionChanged = abs($currentCommission - $originalCommission) > 0.01;

        return $priceChanged || $commissionChanged;
    }
}
