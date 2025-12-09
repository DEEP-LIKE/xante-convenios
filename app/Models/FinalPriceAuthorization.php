<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalPriceAuthorization extends Model
{
    protected $fillable = [
        'agreement_id',
        'requested_by',
        'final_price',
        'justification',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'final_price' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Relación con el convenio
     */
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    /**
     * Usuario que solicitó la autorización
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Usuario que revisó la autorización
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Aprobar la autorización
     */
    public function approve(User $admin): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        // Actualizar el convenio con el precio final aprobado
        $this->agreement->update([
            'final_price_authorization_id' => $this->id,
            'final_offer_price' => $this->final_price,
        ]);
    }

    /**
     * Rechazar la autorización
     */
    public function reject(User $admin, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }
}
