<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'idxante',
        'client_id',
        'data',
        'linked',
        'created_by',
    ];

    protected $casts = [
        'data' => 'array',
        'linked' => 'boolean',
    ];

    /**
     * Relación con el cliente
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relación con el usuario que creó la propuesta
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope para propuestas enlazadas
     */
    public function scopeLinked($query)
    {
        return $query->where('linked', true);
    }

    /**
     * Scope para propuestas rápidas (no enlazadas)
     */
    public function scopeQuick($query)
    {
        return $query->where('linked', false);
    }

    /**
     * Scope para buscar por IDxante
     */
    public function scopeByIdxante($query, string $idxante)
    {
        return $query->where('idxante', $idxante);
    }

    /**
     * Obtiene el valor del convenio de los datos
     */
    public function getValorConvenioAttribute(): ?float
    {
        return isset($this->data['valor_convenio']) ? (float) $this->data['valor_convenio'] : null;
    }

    /**
     * Obtiene la ganancia final de los datos
     */
    public function getGananciaFinalAttribute(): ?float
    {
        return isset($this->data['ganancia_final']) ? (float) $this->data['ganancia_final'] : null;
    }

    /**
     * Verifica si la propuesta es rentable
     */
    public function getEsRentableAttribute(): bool
    {
        return $this->ganancia_final > 0;
    }

    /**
     * Obtiene un resumen de la propuesta
     */
    public function getResumenAttribute(): string
    {
        $valorConvenio = $this->valor_convenio;
        $gananciaFinal = $this->ganancia_final;

        if (! $valorConvenio) {
            return 'Propuesta sin datos financieros';
        }

        $valorFormateado = '$'.number_format($valorConvenio, 2);
        $gananciaFormateada = '$'.number_format($gananciaFinal, 2);

        return "Convenio: {$valorFormateado} | Ganancia: {$gananciaFormateada}";
    }
}
