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
        
        // Datos personales coacreditado/cónyuge
        'spouse_name',
        'spouse_birthdate',
        'spouse_curp',
        'spouse_rfc',
        'spouse_email',
        'spouse_phone',
        'spouse_delivery_file',
        'spouse_civil_status',
        'spouse_regime_type',
        'spouse_occupation',
        'spouse_office_phone',
        'spouse_additional_contact_phone',
        'spouse_current_address',
        'spouse_neighborhood',
        'spouse_postal_code',
        'spouse_municipality',
        'spouse_state',
        
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
            'spouse_birthdate' => 'date',
            'hubspot_synced_at' => 'datetime',
        ];
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(Agreement::class, 'client_xante_id', 'xante_id');
    }
    
    public function latestAgreement()
    {
        return $this->hasOne(Agreement::class, 'client_xante_id', 'xante_id')
            ->latest('created_at');
    }

    public function getAgreementStatusAttribute(): string
    {
        return $this->latestAgreement?->status ?? 'sin_convenio';
    }
}
