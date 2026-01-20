<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WizardProgress extends Model
{
    use HasFactory;

    protected $table = 'wizard_progress';

    protected $fillable = [
        'agreement_id',
        'step_number',
        'step_name',
        'is_completed',
        'completion_percentage',
        'step_data',
        'validation_errors',
        'last_saved_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'completion_percentage' => 'integer',
            'step_data' => 'array',
            'validation_errors' => 'array',
            'last_saved_at' => 'datetime',
        ];
    }

    // Relaciones
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }

    public function scopeByStep($query, int $stepNumber)
    {
        return $query->where('step_number', $stepNumber);
    }

    // Métodos helper
    public function markAsCompleted(): void
    {
        $this->update([
            'is_completed' => true,
            'completion_percentage' => 100,
            'last_saved_at' => now(),
        ]);
    }

    public function updateProgress(int $percentage, array $data = []): void
    {
        $this->update([
            'completion_percentage' => min(100, max(0, $percentage)),
            'step_data' => array_merge($this->step_data ?? [], $data),
            'last_saved_at' => now(),
        ]);
    }

    public function addValidationError(string $field, string $message): void
    {
        $errors = $this->validation_errors ?? [];
        $errors[$field] = $message;

        $this->update(['validation_errors' => $errors]);
    }

    public function clearValidationErrors(): void
    {
        $this->update(['validation_errors' => []]);
    }

    public function hasValidationErrors(): bool
    {
        return ! empty($this->validation_errors);
    }
}
