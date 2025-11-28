<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StateCommissionRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_name',
        'state_code',
        'commission_percentage',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'commission_percentage' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get formatted percentage
     */
    public function getFormattedPercentageAttribute(): string
    {
        return number_format($this->commission_percentage, 2) . '%';
    }
}
