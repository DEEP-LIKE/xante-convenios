<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ClientDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'agreement_id',
        'document_type',
        'document_name',
        'document_category',
        'category',
        'file_name',
        'file_path',
        'file_size',
        'uploaded_at',
        'is_validated',
        'validated_by',
        'validated_at',
        'validation_notes',
    ];

    protected function casts(): array
    {
        return [
            'is_validated' => 'boolean',
            'uploaded_at' => 'datetime',
            'validated_at' => 'datetime',
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Obtiene la URL firmada para descargar el documento
     */
    public function getDownloadUrl(): string
    {
        return Storage::disk('private')->temporaryUrl(
            $this->file_path,
            now()->addHours(1)
        );
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
     * Obtiene el nombre del tipo de documento formateado
     */
    public function getFormattedTypeAttribute(): string
    {
        return match($this->document_type) {
            // Documentación Titular
            'titular_ine' => 'INE (A color, tamaño original, no fotos)',
            'titular_curp' => 'CURP (Mes corriente)',
            'titular_situacion_fiscal' => 'Constancia de Situación Fiscal',
            'titular_comprobante_domicilio' => 'Comprobante de Domicilio Vivienda',
            'titular_comprobante_domicilio_titular' => 'Comprobante de Domicilio Titular',
            'titular_acta_nacimiento' => 'Acta Nacimiento',
            'titular_acta_matrimonio' => 'Acta Matrimonio (Si aplica)',
            'titular_estado_cuenta' => 'Carátula Estado de Cuenta Bancario',
            
            // Documentación Propiedad
            'propiedad_instrumento_notarial' => 'Instrumento Notarial',
            'propiedad_recibo_predial' => 'Recibo predial (Mes corriente)',
            'propiedad_recibo_agua' => 'Recibo de Agua (Mes corriente)',
            'propiedad_recibo_cfe' => 'Recibo CFE con datos fiscales',
            
            default => $this->document_type,
        };
    }

    /**
     * Verifica si el documento es requerido
     */
    public function isRequired(): bool
    {
        // Solo 'titular_acta_matrimonio' es opcional
        return $this->document_type !== 'titular_acta_matrimonio';
    }

    /**
     * Marca el documento como validado
     */
    public function markAsValidated(int $userId, string $notes = null): void
    {
        $this->update([
            'is_validated' => true,
            'validated_by' => $userId,
            'validated_at' => now(),
            'validation_notes' => $notes,
        ]);
    }

    /**
     * Scope para documentos validados
     */
    public function scopeValidated($query)
    {
        return $query->where('is_validated', true);
    }

    /**
     * Scope para documentos pendientes de validación
     */
    public function scopePending($query)
    {
        return $query->where('is_validated', false);
    }

    /**
     * Scope para filtrar por categoría
     */
    public function scopeOfCategory($query, string $category)
    {
        return $query->where('document_category', $category);
    }

    /**
     * Scope para filtrar por tipo
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

    /**
     * Obtiene todos los tipos de documentos requeridos
     */
    public static function getRequiredDocumentTypes(): array
    {
        return [
            // Documentación Titular
            'titular_ine' => 'INE (A color, tamaño original, no fotos)',
            'titular_curp' => 'CURP (Mes corriente)',
            'titular_situacion_fiscal' => 'Constancia de Situación Fiscal',
            'titular_comprobante_domicilio' => 'Comprobante de Domicilio Vivienda',
            'titular_comprobante_domicilio_titular' => 'Comprobante de Domicilio Titular',
            'titular_acta_nacimiento' => 'Acta Nacimiento',
            'titular_acta_matrimonio' => 'Acta Matrimonio (Si aplica)', // Opcional
            'titular_estado_cuenta' => 'Carátula Estado de Cuenta Bancario',
            
            // Documentación Propiedad
            'propiedad_instrumento_notarial' => 'Instrumento Notarial',
            'propiedad_recibo_predial' => 'Recibo predial (Mes corriente)',
            'propiedad_recibo_agua' => 'Recibo de Agua (Mes corriente)',
            'propiedad_recibo_cfe' => 'Recibo CFE con datos fiscales',
        ];
    }
}
