<?php

namespace App\Policies;

use App\Models\FinalPriceAuthorization;
use App\Models\User;

class FinalPriceAuthorizationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Solo administradores pueden ver las autorizaciones
        return $user->role === 'gerencia';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, FinalPriceAuthorization $finalPriceAuthorization): bool
    {
        return $user->role === 'gerencia';
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Los ejecutivos crean solicitudes desde ManageDocumentsPage
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, FinalPriceAuthorization $finalPriceAuthorization): bool
    {
        // Solo admin puede actualizar (aprobar/rechazar) y solo si está pendiente
        return $user->role === 'gerencia' && $finalPriceAuthorization->status === 'pending';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FinalPriceAuthorization $finalPriceAuthorization): bool
    {
        return false; // No se pueden eliminar
    }

    /**
     * Determine whether the user can approve the authorization.
     */
    public function approve(User $user, FinalPriceAuthorization $finalPriceAuthorization): bool
    {
        return $user->role === 'gerencia' && $finalPriceAuthorization->status === 'pending';
    }

    /**
     * Determine whether the user can reject the authorization.
     */
    public function reject(User $user, FinalPriceAuthorization $finalPriceAuthorization): bool
    {
        return $user->role === 'gerencia' && $finalPriceAuthorization->status === 'pending';
    }
}
