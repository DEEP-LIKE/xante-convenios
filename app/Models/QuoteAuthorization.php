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
        return $this->save();
    }

    public function reject(int $authorizedById, string $reason): bool
    {
        $this->status = 'rejected';
        $this->authorized_by = $authorizedById;
        $this->authorized_at = now();
        $this->rejection_reason = $reason;
        return $this->save();
    }
}
