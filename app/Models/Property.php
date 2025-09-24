<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'address',
        'community',
        'property_type',
        'value',
        'mortgage_amount',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'mortgage_amount' => 'decimal:2',
        ];
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(Agreement::class);
    }
}
