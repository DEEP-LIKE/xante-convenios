<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class GeneratedDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'agreement_id',
        'document_type',
        'document_name',
        'file_path',
        'file_size',
        'template_used',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    /**
     * Obtiene la URL para descargar el documento
     */
    public function getDownloadUrl(): string
    {
        // Para desarrollo, usar una ruta directa
        return route('documents.download', ['document' => $this->id]);
    }

    /**
     * Verifica si el archivo existe en el disco
     */
    public function fileExists(): bool
    {
        return Storage::disk('private')->exists($this->file_path);
    }

    /**
     * Obtiene el tamaño del archivo formateado
     */
    public function getFormattedSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Obtiene el tipo de documento formateado
     */
    public function getFormattedTypeAttribute(): string
    {
        return match($this->document_type) {
            'acuerdo_promocion' => 'Acuerdo de Promoción Inmobiliaria',
            'datos_generales' => 'Datos Generales - Fase I',
            'checklist_expediente' => 'Checklist de Expediente Básico',
            'condiciones_comercializacion' => 'Condiciones para Comercialización',
            default => $this->document_type,
        };
    }

    /**
     * Scope para filtrar por tipo de documento
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Scope para documentos de un convenio específico
     */
    public function scopeForAgreement($query, int $agreementId)
    {
        return $query->where('agreement_id', $agreementId);
    }
}
