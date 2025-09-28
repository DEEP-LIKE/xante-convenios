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
        'client_xante_id',
        'property_id',
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
        'holder_current_address',
        'holder_neighborhood',
        'holder_postal_code',
        'holder_municipality',
        'holder_state',
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
            'completed_at' => 'datetime',
            'current_step' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_xante_id', 'xante_id');
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'sin_convenio' => 'Sin Convenio',
            'expediente_incompleto' => 'Expediente Incompleto',
            'expediente_completo' => 'Expediente Completo',
            'convenio_proceso' => 'Convenio en Proceso',
            'convenio_firmado' => 'Convenio Firmado',
            default => $this->status,
        };
    }
    
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'sin_convenio' => 'gray',
            'expediente_incompleto' => 'warning',
            'expediente_completo' => 'success',
            'convenio_proceso' => 'info',
            'convenio_firmado' => 'success',
            default => 'gray',
        };
    }

    // Métodos helper para el wizard
    public function getWizardSteps(): array
    {
        return [
            1 => 'Búsqueda e Identificación',
            2 => 'Datos del Cliente',
            3 => 'Datos de la propiedad',
            4 => 'Calculadora Financiera',
            5 => 'Envio de documentación',
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
}
