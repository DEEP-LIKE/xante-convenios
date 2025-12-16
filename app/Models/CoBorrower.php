<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoBorrower extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'birthdate',
        'curp',
        'rfc',
        'civil_status',
        'regime_type',
        'occupation',
        'delivery_file',
        'current_address',
        'house_number',
        'neighborhood',
        'postal_code',
        'municipality',
        'state',
    ];

    protected $casts = [
        'birthdate' => 'date',
    ];
}
