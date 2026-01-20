<?php

namespace App\Policies;

use App\Models\StateBankAccount;
use App\Models\User;

class StateBankAccountPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['gerencia', 'coordinador_fi']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StateBankAccount $stateBankAccount): bool
    {
        return in_array($user->role, ['gerencia', 'coordinador_fi']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['gerencia', 'coordinador_fi']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StateBankAccount $stateBankAccount): bool
    {
        return in_array($user->role, ['gerencia', 'coordinador_fi']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StateBankAccount $stateBankAccount): bool
    {
        return $user->role === 'gerencia';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StateBankAccount $stateBankAccount): bool
    {
        return $user->role === 'gerencia';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StateBankAccount $stateBankAccount): bool
    {
        return $user->role === 'gerencia';
    }
}
