<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agreement extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'property_id',
        'proposal_id',
        'spouse_id',
        'status',
        // Campos del wizard
        'current_step',
        'wizard_data',
        'completion_percentage',
        'created_by',
        'assigned_to',
        'completed_at',
        'holder_name',
        'holder_birthdate',
        'holder_curp',
        'holder_rfc',
        'holder_email',
        'holder_phone',
        'holder_delivery_file',
        'holder_civil_status',
        'holder_regime_type',
        'holder_occupation',
        'holder_office_phone',
        'holder_additional_contact_phone',
        'current_address',
        'holder_house_number',
        'neighborhood',
        'postal_code',
        'municipality',
        'state',
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
        'spouse_house_number',
        'spouse_neighborhood',
        'spouse_postal_code',
        'spouse_municipality',
        'spouse_state',
        'ac_name',
        'ac_phone',
        'ac_quota',
        'private_president_name',
        'private_president_phone',
        'private_president_quota',
        'documents_checklist',
        'financial_evaluation',
        // Campos de calculadora financiera
        'precio_promocion',
        'domicilio_vivienda',
        'domicilio_convenio',
        'comunidad',
        'tipo_vivienda',
        'prototipo',
        'valor_convenio',
        'monto_credito',
        'tipo_credito',
        'otro_banco',
        'porcentaje_comision_sin_iva',
        'comision_iva_incluido',
        'monto_comision_sin_iva',
        'comision_total_pagar',
        'valor_compraventa',
        'comision_total',
        'ganancia_final',
        'isr',
        'cancelacion_hipoteca',
        'total_gastos_fi',
        'indicador_ganancia',
        // Nuevos campos para sistema de dos wizards
        'documents_generated_at',
        'documents_sent_at',
        'documents_received_at',
        'can_return_to_wizard1',
        'current_wizard',
        'wizard2_current_step',
        'completed_at',
        'proposal_value',
        'proposal_saved_at',
        'has_co_borrower',
        'co_borrower_relationship',
        // Campos de validación
        'validation_status',
        'current_validation_id',
        'can_generate_documents',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'birthdate' => 'date',
            'spouse_birthdate' => 'date',
            'documents_checklist' => 'array',
            'financial_evaluation' => 'array',
            // Nuevos casts para wizard
            'wizard_data' => 'array',
            'completion_percentage' => 'integer',
            'current_step' => 'integer',
            // Nuevos casts para sistema de dos wizards
            'documents_generated_at' => 'datetime',
            'documents_sent_at' => 'datetime',
            'documents_received_at' => 'datetime',
            'completed_at' => 'datetime',
            'can_return_to_wizard1' => 'boolean',
            'current_wizard' => 'integer',
            'wizard2_current_step' => 'integer',
            'proposal_value' => 'decimal:2',
            'proposal_saved_at' => 'datetime',
            'has_co_borrower' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function spouse(): BelongsTo
    {
        return $this->belongsTo(Spouse::class);
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function calculation(): HasOne
    {
        return $this->hasOne(Calculation::class);
    }

    // Nuevas relaciones para el sistema wizard
    public function wizardProgress(): HasMany
    {
        return $this->hasMany(WizardProgress::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DocumentManager::class);
    }

    // Nuevas relaciones para sistema de documentos
    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    public function clientDocuments(): HasMany
    {
        return $this->hasMany(ClientDocument::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Relaciones de validación
    public function validations(): HasMany
    {
        return $this->hasMany(QuoteValidation::class);
    }

    public function currentValidation(): BelongsTo
    {
        return $this->belongsTo(QuoteValidation::class, 'current_validation_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            // Estados originales
            'sin_convenio' => 'Sin Convenio',
            'expediente_incompleto' => 'Expediente Incompleto',
            'expediente_completo' => 'Expediente Completo',
            'convenio_proceso' => 'Convenio en Proceso',
            'convenio_firmado' => 'Convenio Firmado',
            // Nuevos estados del sistema de dos wizards
            'wizard1_in_progress' => 'Wizard 1 en Progreso',
            'wizard1_completed' => 'Wizard 1 Completado',
            'documents_generated' => 'Documentos Generados',
            'documents_sent' => 'Documentos Enviados',
            'wizard2_in_progress' => 'Wizard 2 en Progreso',
            'wizard2_completed' => 'Wizard 2 Completado',
            'completed' => 'Completado',
            // Estados de validación
            'pending_validation' => 'Pendiente de Validación',
            'validation_approved' => 'Validación Aprobada',
            'validation_rejected' => 'Validación Rechazada',
            'validation_with_observations' => 'Con Observaciones',
            default => $this->status,
        };
    }
    
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            // Estados originales
            'sin_convenio' => 'gray',
            'expediente_incompleto' => 'warning',
            'expediente_completo' => 'success',
            'convenio_proceso' => 'info',
            'convenio_firmado' => 'success',
            // Nuevos estados del sistema de dos wizards
            'wizard1_in_progress' => 'info',
            'wizard1_completed' => 'success',
            'documents_generated' => 'success',
            'documents_sent' => 'info',
            'wizard2_in_progress' => 'info',
            'wizard2_completed' => 'success',
            'completed' => 'success',
            // Estados de validación
            'pending_validation' => 'warning',
            'validation_approved' => 'success',
            'validation_rejected' => 'danger',
            'validation_with_observations' => 'info',
            default => 'gray',
        };
    }

    // Métodos helper para el wizard
    public function getWizardSteps(): array
    {
        return [
            1 => 'Identificación',
            2 => 'Cliente',
            3 => 'Propiedad',
            4 => 'Calculadora',
            5 => 'Validación',
        ];
    }

    public function getWizard2Steps(): array
    {
        return [
            1 => 'Envío de Documentos',
            2 => 'Recepción de Documentos',
            3 => 'Cierre Exitoso',
        ];
    }

    public function getCurrentStepName(): string
    {
        $steps = $this->getWizardSteps();
        return $steps[$this->current_step] ?? 'Paso Desconocido';
    }

    public function getCompletedSteps(): array
    {
        return $this->wizardProgress()
            ->where('is_completed', true)
            ->pluck('step_number')
            ->toArray();
    }

    public function isStepCompleted(int $stepNumber): bool
    {
        return $this->wizardProgress()
            ->where('step_number', $stepNumber)
            ->where('is_completed', true)
            ->exists();
    }

    public function canAccessStep(int $stepNumber): bool
    {
        // El paso 1 siempre es accesible
        if ($stepNumber === 1) {
            return true;
        }

        // Para otros pasos, verificar que el anterior esté completado
        return $this->isStepCompleted($stepNumber - 1);
    }

    public function updateWizardProgress(int $stepNumber, array $data = []): void
    {
        $this->wizardProgress()->updateOrCreate(
            ['step_number' => $stepNumber],
            [
                'step_name' => $this->getWizardSteps()[$stepNumber] ?? "Paso {$stepNumber}",
                'step_data' => $data,
                'last_saved_at' => now(),
            ]
        );

        // Actualizar el paso actual si es mayor
        if ($stepNumber > $this->current_step) {
            $this->update(['current_step' => $stepNumber]);
        }

        // Calcular porcentaje de completitud
        $this->calculateCompletionPercentage();
    }

    public function calculateCompletionPercentage(): void
    {
        $totalSteps = count($this->getWizardSteps()); // 5 pasos
        $currentStep = $this->current_step;
        
        // Calcular porcentaje basado en el paso actual
        $percentage = $totalSteps > 0 ? round(($currentStep / $totalSteps) * 100) : 0;
        
        $this->update(['completion_percentage' => $percentage]);
    }

    public function markStepAsCompleted(int $stepNumber): void
    {
        $progress = $this->wizardProgress()->where('step_number', $stepNumber)->first();
        
        if ($progress) {
            $progress->markAsCompleted();
        }

        $this->calculateCompletionPercentage();
    }

    public function isWizardCompleted(): bool
    {
        return $this->completion_percentage >= 100;
    }

    public function getNextStep(): ?int
    {
        $steps = array_keys($this->getWizardSteps());
        $currentIndex = array_search($this->current_step, $steps);
        
        if ($currentIndex !== false && isset($steps[$currentIndex + 1])) {
            return $steps[$currentIndex + 1];
        }
        
        return null;
    }

    public function getPreviousStep(): ?int
    {
        $steps = array_keys($this->getWizardSteps());
        $currentIndex = array_search($this->current_step, $steps);
        
        if ($currentIndex !== false && $currentIndex > 0) {
            return $steps[$currentIndex - 1];
        }
        
        return null;
    }

    // Métodos de validación
    public function requestValidation(int $userId): QuoteValidation
    {
        // Obtener el número de revisión
        $revisionNumber = $this->validations()->count() + 1;

        // Crear snapshot de la calculadora desde wizard_data
        $wizardData = $this->wizard_data ?? [];
        
        $snapshot = [
            'precio_promocion' => $wizardData['precio_promocion'] ?? $this->precio_promocion ?? 0,
            'precio_promocion' => $wizardData['precio_promocion'] ?? $this->precio_promocion ?? 0,
            'valor_convenio' => $wizardData['valor_convenio'] ?? $this->valor_convenio ?? 0,
            'valor_compraventa' => $wizardData['valor_compraventa'] ?? $wizardData['valor_convenio'] ?? $this->valor_convenio ?? 0, 
            'porcentaje_comision_sin_iva' => $wizardData['porcentaje_comision_sin_iva'] ?? $this->porcentaje_comision_sin_iva ?? 0,
            'multiplicador_estado' => $wizardData['state_commission_percentage'] ?? (($wizardData['valor_convenio'] ?? 0) > 0 ? ((($wizardData['precio_promocion'] ?? 0) / ($wizardData['valor_convenio'] ?? 1)) - 1) * 100 : 0), 
            'comision_iva_incluido' => ($wizardData['porcentaje_comision_sin_iva'] ?? 0) * 1.16, 
            'estado_propiedad' => $wizardData['holder_state'] ?? 'Desconocido',
            'monto_credito' => $wizardData['monto_credito'] ?? $this->monto_credito ?? 0,
            'tipo_credito' => $wizardData['tipo_credito'] ?? 'No seleccionado',
            'monto_comision_sin_iva' => ($wizardData['monto_comision_sin_iva'] ?? (($wizardData['valor_convenio'] ?? 0) * (($wizardData['porcentaje_comision_sin_iva'] ?? 0) / 100))),
            'comision_total' => ($wizardData['comision_total'] ?? ((($wizardData['valor_convenio'] ?? 0) * (($wizardData['porcentaje_comision_sin_iva'] ?? 0) / 100)) * 1.16)),
            'isr' => $wizardData['isr'] ?? $this->isr ?? 0,
            'cancelacion_hipoteca' => $wizardData['cancelacion_hipoteca'] ?? $this->cancelacion_hipoteca ?? 0,
            'total_gastos_fi' => ($wizardData['isr'] ?? 0) + ($wizardData['cancelacion_hipoteca'] ?? 0),
            'ganancia_final' => $wizardData['ganancia_final'] ?? $this->ganancia_final ?? (($wizardData['valor_convenio'] ?? 0) - (($wizardData['isr'] ?? 0) + ($wizardData['cancelacion_hipoteca'] ?? 0)) - ((($wizardData['valor_convenio'] ?? 0) * (($wizardData['porcentaje_comision_sin_iva'] ?? 0) / 100)) * 1.16) - ($wizardData['monto_credito'] ?? 0)),
            'indicador_ganancia' => $wizardData['indicador_ganancia'] ?? $this->indicador_ganancia ?? 'N/A',
        ];

        // Buscar si ya existe una validación pendiente
        $pendingValidation = $this->validations()
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($pendingValidation) {
            $pendingValidation->update([
                'calculator_snapshot' => $snapshot,
                'requested_by' => $userId,
            ]);
            
            // Asegurar que el acuerdo apunte a esta validación
            $this->update([
                'current_validation_id' => $pendingValidation->id,
                'validation_status' => 'pending',
                'can_generate_documents' => false,
                'current_step' => 5,
            ]);

            return $pendingValidation;
        }

        // Si no hay pendiente, crear nueva validación
        $revisionNumber = $this->validations()->count() + 1;

        $validation = $this->validations()->create([
            'requested_by' => $userId,
            'status' => 'pending',
            'calculator_snapshot' => $snapshot,
            'revision_number' => $revisionNumber,
        ]);

        // Actualizar estado del agreement
        $this->update([
            'validation_status' => 'pending',
            'current_validation_id' => $validation->id,
            'can_generate_documents' => false,
            'current_step' => 5,
        ]);

        return $validation;
    }

    public function hasApprovedValidation(): bool
    {
        return $this->validation_status === 'approved' && $this->can_generate_documents;
    }

    public function hasPendingValidation(): bool
    {
        return $this->validation_status === 'pending';
    }

    public function hasObservations(): bool
    {
        return $this->validation_status === 'with_observations';
    }

    public function canGenerateDocuments(): bool
    {
        return $this->can_generate_documents && $this->validation_status === 'approved';
    }

    public function getValidationStatusLabelAttribute(): string
    {
        return match($this->validation_status) {
            'not_required' => 'No Requerida',
            'pending' => 'Pendiente de Validación',
            'approved' => 'Validación Aprobada',
            'rejected' => 'Validación Rechazada',
            'with_observations' => 'Con Observaciones',
            default => $this->validation_status,
        };
    }

    public function getValidationStatusColorAttribute(): string
    {
        return match($this->validation_status) {
            'not_required' => 'gray',
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'with_observations' => 'info',
            default => 'gray',
        };
    }
}
