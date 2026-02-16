<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'holder_marital_regime',
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
        // Campos de autorización de precio final
        'final_price_authorization_id',
        'final_offer_price',
        'co_borrower_id',
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
            'final_offer_price' => 'decimal:2',
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

    public function coBorrower(): BelongsTo
    {
        return $this->belongsTo(CoBorrower::class);
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

    // Relaciones de autorización de precio final
    public function finalPriceAuthorizations(): HasMany
    {
        return $this->hasMany(FinalPriceAuthorization::class);
    }

    public function finalPriceAuthorization(): BelongsTo
    {
        return $this->belongsTo(FinalPriceAuthorization::class);
    }

    // ========================================
    // RECALCULATIONS
    // ========================================

    public function recalculations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AgreementRecalculation::class)->orderBy('created_at', 'desc');
    }

    public function latestRecalculation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AgreementRecalculation::class)->latestOfMany();
    }

    /**
     * Obtiene los valores financieros actuales (ya sea del recálculo más reciente o del original)
     */
    public function getCurrentFinancialsAttribute(): array
    {
        $latest = $this->latestRecalculation;

        if ($latest) {
            return [
                'agreement_value' => $latest->agreement_value,
                'proposal_value' => $latest->proposal_value,
                'commission_total' => $latest->commission_total,
                'final_profit' => $latest->final_profit,
                'is_recalculated' => true,
                'recalculation_date' => $latest->created_at,
                'recalculation_number' => $latest->recalculation_number,
                'motivo' => $latest->motivo,
                'user' => $latest->user,
            ];
        }

        $wizardData = $this->wizard_data ?? [];

        return [
            'agreement_value' => $this->agreement_value > 0 ? $this->agreement_value : ($wizardData['valor_convenio'] ?? 0),
            'proposal_value' => $this->proposal_value > 0 ? $this->proposal_value : ($wizardData['precio_promocion'] ?? 0),
            'commission_total' => $this->commission_total > 0 ? $this->commission_total : ($wizardData['comision_total_pagar'] ?? 0),
            'final_profit' => $this->final_profit != 0 ? $this->final_profit : ($wizardData['ganancia_final'] ?? 0),
            'is_recalculated' => false,
            'recalculation_date' => null,
            'recalculation_number' => 0,
            'motivo' => null,
            'user' => null,
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
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
        return match ($this->status) {
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

        // Helper para sanitizar valores numéricos (convertir strings formateados a float)
        $toFloat = function ($value) {
            if (is_string($value)) {
                return (float) str_replace([',', '$', ' '], '', $value);
            }

            return (float) ($value ?? 0);
        };

        // Sanitizar todos los valores numéricos del wizard_data
        $valorConvenio = $toFloat($wizardData['valor_convenio'] ?? $this->valor_convenio ?? 0);
        $precioPromocion = $toFloat($wizardData['precio_promocion'] ?? $this->precio_promocion ?? 0);
        $porcentajeComision = $toFloat($wizardData['porcentaje_comision_sin_iva'] ?? $this->porcentaje_comision_sin_iva ?? 0);
        $stateCommission = $toFloat($wizardData['state_commission_percentage'] ?? 0);
        $montoCredito = $toFloat($wizardData['monto_credito'] ?? $this->monto_credito ?? 0);
        $isr = $toFloat($wizardData['isr'] ?? $this->isr ?? 0);
        $cancelacionHipoteca = $toFloat($wizardData['cancelacion_hipoteca'] ?? $this->cancelacion_hipoteca ?? 0);
        $montoComisionSinIva = $toFloat($wizardData['monto_comision_sin_iva'] ?? 0);
        $comisionTotal = $toFloat($wizardData['comision_total_pagar'] ?? 0);
        $gananciaFinal = $toFloat($wizardData['ganancia_final'] ?? $this->ganancia_final ?? 0);

        // Calcular valores derivados si no existen
        if ($montoComisionSinIva == 0 && $valorConvenio > 0) {
            $montoComisionSinIva = $valorConvenio * ($porcentajeComision / 100);
        }

        if ($comisionTotal == 0 && $montoComisionSinIva > 0) {
            $comisionTotal = $montoComisionSinIva * 1.16;
        }

        if ($gananciaFinal == 0 && $valorConvenio > 0) {
            $gananciaFinal = $valorConvenio - $isr - $cancelacionHipoteca - $comisionTotal - $montoCredito;
        }

        $snapshot = [
            'precio_promocion' => $precioPromocion,
            'valor_convenio' => $valorConvenio,
            'valor_compraventa' => $valorConvenio,
            'porcentaje_comision_sin_iva' => $porcentajeComision,
            'multiplicador_estado' => $stateCommission > 0 ? $stateCommission : ($valorConvenio > 0 && $precioPromocion > 0 ? (($precioPromocion / $valorConvenio) - 1) * 100 : 0),
            'comision_iva_incluido' => $porcentajeComision * 1.16,
            'estado_propiedad' => $wizardData['estado_propiedad'] ?? $wizardData['holder_state'] ?? 'Desconocido',
            'monto_credito' => $montoCredito,
            'tipo_credito' => $wizardData['tipo_credito'] ?? 'No seleccionado',
            'monto_comision_sin_iva' => $montoComisionSinIva,
            'comision_total' => $comisionTotal,
            'comision_total_pagar' => $comisionTotal,
            'isr' => $isr,
            'cancelacion_hipoteca' => $cancelacionHipoteca,
            'total_gastos_fi' => $isr + $cancelacionHipoteca,
            'total_gastos_fi_venta' => $isr + $cancelacionHipoteca,
            'ganancia_final' => $gananciaFinal,
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
        return match ($this->validation_status) {
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
        return match ($this->validation_status) {
            'not_required' => 'gray',
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'with_observations' => 'info',
            default => 'gray',
        };
    }
}
