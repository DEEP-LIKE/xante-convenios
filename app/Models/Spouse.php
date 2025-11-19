<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Spouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'birthdate',
        'curp',
        'rfc',
        'email',
        'phone',
        'delivery_file',
        'civil_status',
        'regime_type',
        'occupation',
        'office_phone',
        'additional_contact_phone',
        'current_address',
        'neighborhood',
        'postal_code',
        'municipality',
        'state',
    ];

    protected $casts = [
        'birthdate' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
