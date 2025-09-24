<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Calculation extends Model
{
    use HasFactory;

    protected $fillable = [
        'agreement_id',
        'value_without_escrow',
        'notarial_expenses',
        'purchase_value',
        'is_isr_exempt',
        'difference_value',
        'total_payment',
    ];

    protected function casts(): array
    {
        return [
            'value_without_escrow' => 'decimal:2',
            'notarial_expenses' => 'decimal:2',
            'purchase_value' => 'decimal:2',
            'difference_value' => 'decimal:2',
            'total_payment' => 'decimal:2',
            'is_isr_exempt' => 'boolean',
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    // Calculate difference value automatically
    public function calculateDifferenceValue(): float
    {
        return $this->purchase_value - $this->value_without_escrow;
    }

    // Calculate total payment automatically
    public function calculateTotalPayment(): float
    {
        return $this->purchase_value + $this->notarial_expenses;
    }
}
