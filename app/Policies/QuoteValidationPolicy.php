<?php

namespace App\Policies;

use App\Models\QuoteValidation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class QuoteValidationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Coordinadores FI y Gerencia pueden ver todas las validaciones
        return in_array($user->role, ['coordinador_fi', 'gerencia']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, QuoteValidation $quoteValidation): bool
    {
        // El ejecutivo puede ver su propia validación
        if ($user->id === $quoteValidation->requested_by) {
            return true;
        }

        // Coordinadores FI y Gerencia pueden ver todas
        return in_array($user->role, ['coordinador_fi', 'gerencia']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Solo ejecutivos pueden crear solicitudes de validación
        return $user->role === 'ejecutivo';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, QuoteValidation $quoteValidation): bool
    {
        // Solo el solicitante puede actualizar si está pendiente
        return $user->id === $quoteValidation->requested_by 
            && $quoteValidation->isPending();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, QuoteValidation $quoteValidation): bool
    {
        // Solo el solicitante puede eliminar si está pendiente
        return $user->id === $quoteValidation->requested_by 
            && $quoteValidation->isPending();
    }

    /**
     * Determine whether the user can approve the validation.
     */
    public function approve(User $user, QuoteValidation $quoteValidation): bool
    {
        // No puede aprobar su propia solicitud
        if ($user->id === $quoteValidation->requested_by) {
            return false;
        }

        // Solo puede aprobar si está pendiente
        if (!$quoteValidation->isPending()) {
            return false;
        }

        // Solo coordinadores FI y gerencia pueden aprobar
        return in_array($user->role, ['coordinador_fi', 'gerencia']);
    }

    /**
     * Determine whether the user can request changes.
     */
    public function requestChanges(User $user, QuoteValidation $quoteValidation): bool
    {
        // Mismas reglas que aprobar
        return $this->approve($user, $quoteValidation);
    }

    /**
     * Determine whether the user can reject the validation.
     */
    public function reject(User $user, QuoteValidation $quoteValidation): bool
    {
        // Mismas reglas que aprobar
        return $this->approve($user, $quoteValidation);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, QuoteValidation $quoteValidation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, QuoteValidation $quoteValidation): bool
    {
        return $user->role === 'gerencia';
    }
}
