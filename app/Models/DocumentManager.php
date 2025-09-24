<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DocumentManager extends Model
{
    use HasFactory;

    protected $table = 'document_managers';

    protected $fillable = [
        'agreement_id',
        'document_type',
        'document_category',
        'file_path',
        'file_name',
        'original_name',
        'file_size',
        'mime_type',
        'upload_status',
        'validation_status',
        'validation_notes',
        'extracted_data',
        'uploaded_by',
        'validated_by',
        'uploaded_at',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'extracted_data' => 'array',
            'validation_notes' => 'array',
            'uploaded_at' => 'datetime',
            'validated_at' => 'datetime',
        ];
    }

    // Constantes para tipos de documentos
    const DOCUMENT_TYPES = [
        // Documentos del Titular
        'titular_ine' => 'INE Titular',
        'titular_curp' => 'CURP Titular',
        'titular_rfc' => 'RFC Titular',
        'titular_comprobante_domicilio' => 'Comprobante Domicilio Titular',
        'titular_acta_nacimiento' => 'Acta de Nacimiento Titular',
        'titular_acta_matrimonio' => 'Acta de Matrimonio',
        'titular_estado_cuenta' => 'Estado de Cuenta Titular',
        
        // Documentos del Cónyuge
        'conyuge_ine' => 'INE Cónyuge',
        'conyuge_curp' => 'CURP Cónyuge',
        'conyuge_rfc' => 'RFC Cónyuge',
        'conyuge_comprobante_domicilio' => 'Comprobante Domicilio Cónyuge',
        'conyuge_acta_nacimiento' => 'Acta de Nacimiento Cónyuge',
        'conyuge_estado_cuenta' => 'Estado de Cuenta Cónyuge',
        
        // Documentos de la Propiedad
        'propiedad_instrumento_notarial' => 'Instrumento Notarial',
        'propiedad_traslado_dominio' => 'Traslado de Dominio',
        'propiedad_recibo_predial' => 'Recibo Predial',
        'propiedad_recibo_agua' => 'Recibo de Agua',
        'propiedad_recibo_cfe' => 'Recibo CFE',
        
        // Otros documentos
        'otros_referencias_comerciales' => 'Referencias Comerciales',
        'otros_referencias_personales' => 'Referencias Personales',
        'otros_autorizacion_buro' => 'Autorización Buró de Crédito',
    ];

    const DOCUMENT_CATEGORIES = [
        'titular' => 'Documentos del Titular',
        'conyuge' => 'Documentos del Cónyuge',
        'propiedad' => 'Documentos de la Propiedad',
        'otros' => 'Otros Documentos',
    ];

    const UPLOAD_STATUS = [
        'pending' => 'Pendiente',
        'uploading' => 'Subiendo',
        'uploaded' => 'Subido',
        'failed' => 'Error',
    ];

    const VALIDATION_STATUS = [
        'pending' => 'Pendiente Validación',
        'validating' => 'Validando',
        'valid' => 'Válido',
        'invalid' => 'Inválido',
        'requires_review' => 'Requiere Revisión',
    ];

    // Relaciones
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('document_category', $category);
    }

    public function scopeValid($query)
    {
        return $query->where('validation_status', 'valid');
    }

    public function scopePendingValidation($query)
    {
        return $query->where('validation_status', 'pending');
    }

    public function scopeInvalid($query)
    {
        return $query->where('validation_status', 'invalid');
    }

    // Métodos helper
    public function getDocumentTypeLabel(): string
    {
        return self::DOCUMENT_TYPES[$this->document_type] ?? $this->document_type;
    }

    public function getCategoryLabel(): string
    {
        return self::DOCUMENT_CATEGORIES[$this->document_category] ?? $this->document_category;
    }

    public function getUploadStatusLabel(): string
    {
        return self::UPLOAD_STATUS[$this->upload_status] ?? $this->upload_status;
    }

    public function getValidationStatusLabel(): string
    {
        return self::VALIDATION_STATUS[$this->validation_status] ?? $this->validation_status;
    }

    public function getFileUrl(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return Storage::url($this->file_path);
    }

    public function getFileSizeFormatted(): string
    {
        $bytes = $this->file_size;
        
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    public function markAsValid(User $validator, array $notes = []): void
    {
        $this->update([
            'validation_status' => 'valid',
            'validated_by' => $validator->id,
            'validated_at' => now(),
            'validation_notes' => $notes,
        ]);
    }

    public function markAsInvalid(User $validator, array $notes = []): void
    {
        $this->update([
            'validation_status' => 'invalid',
            'validated_by' => $validator->id,
            'validated_at' => now(),
            'validation_notes' => $notes,
        ]);
    }

    public function requiresReview(User $validator, array $notes = []): void
    {
        $this->update([
            'validation_status' => 'requires_review',
            'validated_by' => $validator->id,
            'validated_at' => now(),
            'validation_notes' => $notes,
        ]);
    }

    public function updateExtractedData(array $data): void
    {
        $this->update([
            'extracted_data' => array_merge($this->extracted_data ?? [], $data),
        ]);
    }

    public function deleteFile(): bool
    {
        if ($this->file_path && Storage::exists($this->file_path)) {
            return Storage::delete($this->file_path);
        }
        
        return true;
    }
}
