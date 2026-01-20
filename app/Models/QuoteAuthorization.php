<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteAuthorization extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposal_id',
        'agreement_id',
        'quote_validation_id',
        'requested_by',
        'authorized_by',
        'status',
        'change_type',
        'old_commission_percentage',
        'new_commission_percentage',
        'old_price',
        'new_price',
        'discount_amount',
        'discount_reason',
        'rejection_reason',
        'authorized_at',
    ];

    protected function casts(): array
    {
        return [
            'authorized_at' => 'datetime',
            'old_commission_percentage' => 'decimal:2',
            'new_commission_percentage' => 'decimal:2',
            'old_price' => 'decimal:2',
            'new_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
        ];
    }

    // Relaciones
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function quoteValidation(): BelongsTo
    {
        return $this->belongsTo(QuoteValidation::class, 'quote_validation_id');
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

    // Métodos de tipo
    public function isCommissionChange(): bool
    {
        return in_array($this->change_type, ['commission', 'both']);
    }

    public function isPriceChange(): bool
    {
        return in_array($this->change_type, ['price', 'both']);
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

    public function scopeForUser($query, int $userId)
    {
        return $query->where('requested_by', $userId);
    }

    // Métodos de acción
    public function approve(int $authorizedById): bool
    {
        $this->status = 'approved';
        $this->authorized_by = $authorizedById;
        $this->authorized_at = now();

        $saved = $this->save();

        // Si hay una validación vinculada, actualizar sus valores
        if ($saved && $this->quote_validation_id) {
            $validation = $this->quoteValidation;
            if ($validation) {
                $snapshot = $validation->calculator_snapshot;

                // Actualizar precio si aplica
                if ($this->isPriceChange() && $this->new_price) {
                    $snapshot['valor_convenio'] = $this->new_price;
                    $snapshot['valor_compraventa'] = $this->new_price;
                }

                // Actualizar comisión si aplica
                if ($this->isCommissionChange() && $this->new_commission_percentage) {
                    $snapshot['porcentaje_comision_sin_iva'] = $this->new_commission_percentage;
                }

                // Recalcular valores con el servicio de calculadora
                if ($this->isPriceChange() || $this->isCommissionChange()) {
                    $calculatorService = app(\App\Services\AgreementCalculatorService::class);

                    // Preparar parámetros para el cálculo
                    // Aseguramos que los valores sean float/int correctos
                    $params = $snapshot;

                    // Si cambió la comisión, asegurarse que el nuevo valor se use en el cálculo
                    if ($this->isCommissionChange() && $this->new_commission_percentage) {
                        $params['porcentaje_comision_sin_iva'] = $this->new_commission_percentage;
                        $snapshot['porcentaje_comision_sin_iva'] = $this->new_commission_percentage;
                    }

                    $recalculated = $calculatorService->calculateAllFinancials(
                        (float) ($snapshot['valor_convenio'] ?? 0),
                        $params
                    );

                    // Actualizar todos los valores calculados en el snapshot
                    $snapshot = array_merge($snapshot, $recalculated);
                }

                $validation->calculator_snapshot = $snapshot;
                // Cambiar estado de vuelta a pending para que el coordinador pueda aprobar
                $validation->status = 'pending';
                $validation->save();
            }
        }

        return $saved;
    }

    public function reject(int $authorizedById, string $reason): bool
    {
        $this->status = 'rejected';
        $this->authorized_by = $authorizedById;
        $this->authorized_at = now();
        $this->rejection_reason = $reason;

        $saved = $this->save();

        // Si hay una validación vinculada, revertir su estado a pending
        if ($saved && $this->quote_validation_id) {
            $validation = $this->quoteValidation;
            if ($validation) {
                // Cambiar estado a rejected para que el coordinador sepa que fue rechazada la solicitud
                $validation->status = 'rejected';
                $validation->save();
            }
        }

        return $saved;
    }
}
