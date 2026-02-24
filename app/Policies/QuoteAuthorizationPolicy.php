<?php

namespace App\Policies;

use App\Models\QuoteAuthorization;
use App\Models\User;

class QuoteAuthorizationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Ejecutivos, Coordinador FI y Gerencia pueden ver autorizaciones
        return in_array($user->role, ['ejecutivo', 'coordinador_fi', 'gerencia']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, QuoteAuthorization $quoteAuthorization): bool
    {
        // El solicitante puede ver su propia solicitud
        if ($user->id === $quoteAuthorization->requested_by) {
            return true;
        }

        // Coordinador FI y Gerencia pueden ver todas
        return in_array($user->role, ['coordinador_fi', 'gerencia']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Ejecutivos y Coordinadores FI pueden crear solicitudes
        return in_array($user->role, ['ejecutivo', 'coordinador_fi']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, QuoteAuthorization $quoteAuthorization): bool
    {
        // Solo el solicitante puede actualizar si está pendiente
        return $user->id === $quoteAuthorization->requested_by
            && $quoteAuthorization->isPending();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, QuoteAuthorization $quoteAuthorization): bool
    {
        // Solo el solicitante puede eliminar si está pendiente
        return $user->id === $quoteAuthorization->requested_by
            && $quoteAuthorization->isPending();
    }

    /**
     * Determine whether the user can approve the authorization.
     */
    public function approve(User $user, QuoteAuthorization $quoteAuthorization): bool
    {
        // No puede aprobar su propia solicitud
        if ($user->id === $quoteAuthorization->requested_by) {
            return false;
        }

        // Solo puede aprobar si está pendiente
        if (! $quoteAuthorization->isPending()) {
            return false;
        }

        // Si el solicitante es un Coordinador FI, solo Gerencia puede aprobar
        if ($quoteAuthorization->requestedBy->role === 'coordinador_fi') {
            return $user->role === 'gerencia';
        }

        // Para otras solicitudes (ej. de ejecutivos), Coordinador FI o Gerencia pueden aprobar
        // dependiendo del tipo de cambio si se desea, por ahora mantenemos simple:
        return in_array($user->role, ['coordinador_fi', 'gerencia']);
    }

    /**
     * Determine whether the user can reject the authorization.
     */
    public function reject(User $user, QuoteAuthorization $quoteAuthorization): bool
    {
        // Mismas reglas que aprobar
        return $this->approve($user, $quoteAuthorization);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, QuoteAuthorization $quoteAuthorization): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, QuoteAuthorization $quoteAuthorization): bool
    {
        return $user->role === 'gerencia';
    }
}
