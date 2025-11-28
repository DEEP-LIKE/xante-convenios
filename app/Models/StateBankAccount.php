<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StateBankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_name',
        'state_code',
        'account_holder',
        'bank_name',
        'account_number',
        'clabe',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get formatted account info
     */
    public function getFormattedAccountInfoAttribute(): string
    {
        return "{$this->bank_name} - {$this->account_number}";
    }

    /**
     * Get the commission rate associated with the state
     */
    public function commissionRate()
    {
        return $this->belongsTo(StateCommissionRate::class, 'state_code', 'state_code');
    }
}
