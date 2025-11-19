<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        // Campos básicos
        'name',
        'xante_id',
        'hubspot_id',
        'hubspot_synced_at',
        'fecha_registro',
        'email',
        'phone',
        
        // Datos personales titular
        'birthdate',
        'curp',
        'rfc',
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
        

        // Contacto AC y/o Presidente de Privada
        'ac_name',
        'ac_phone',
        'ac_quota',
        'private_president_name',
        'private_president_phone',
        'private_president_quota',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'hubspot_synced_at' => 'datetime',
            'fecha_registro' => 'datetime',
        ];
    }

    public function spouse(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Spouse::class);
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(Agreement::class);
    }
    
    public function latestAgreement()
    {
        return $this->hasOne(Agreement::class)
            ->latest('created_at');
    }

    public function getAgreementStatusAttribute(): string
    {
        return $this->latestAgreement?->status ?? 'sin_convenio';
    }
}
