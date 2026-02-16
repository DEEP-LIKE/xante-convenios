<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgreementRecalculation extends Model
{
    protected $fillable = [
        'agreement_id',
        'user_id',
        'recalculation_number',
        'agreement_value',
        'proposal_value',
        'commission_total',
        'final_profit',
        'calculation_data',
        'motivo',
    ];

    protected $casts = [
        'calculation_data' => 'array',
        'agreement_value' => 'decimal:2',
        'proposal_value' => 'decimal:2',
        'commission_total' => 'decimal:2',
        'final_profit' => 'decimal:2',
    ];

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
