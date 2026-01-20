<?php

namespace App\Policies;

use App\Models\StateCommissionRate;
use App\Models\User;

class StateCommissionRatePolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Todos pueden ver
    }

    public function view(User $user, StateCommissionRate $stateCommissionRate): bool
    {
        return true; // Todos pueden ver detalles
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['coordinador_fi', 'gerencia']);
    }

    public function update(User $user, StateCommissionRate $stateCommissionRate): bool
    {
        return in_array($user->role, ['coordinador_fi', 'gerencia']);
    }

    public function delete(User $user, StateCommissionRate $stateCommissionRate): bool
    {
        return $user->role === 'gerencia';
    }

    public function restore(User $user, StateCommissionRate $stateCommissionRate): bool
    {
        return $user->role === 'gerencia';
    }

    public function forceDelete(User $user, StateCommissionRate $stateCommissionRate): bool
    {
        return $user->role === 'gerencia';
    }
}
