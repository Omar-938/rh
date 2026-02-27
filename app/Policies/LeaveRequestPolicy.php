<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    /**
     * Admin et managers voient toutes les demandes ; les employés voient les leurs.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Un employé peut voir ses propres demandes ; admin/manager voient tout.
     */
    public function view(User $user, LeaveRequest $leave): bool
    {
        if ($user->isAdmin() || $user->isManager()) {
            return true;
        }

        return $user->id === $leave->user_id;
    }

    /**
     * Tout employé actif peut soumettre une demande de congé.
     */
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Seuls admin et managers peuvent approuver/rejeter.
     */
    public function review(User $user, LeaveRequest $leave): bool
    {
        if (! ($user->isAdmin() || $user->isManager())) {
            return false;
        }

        // Un manager ne peut pas valider sa propre demande
        return $user->id !== $leave->user_id;
    }

    /**
     * L'employé concerné ou un admin peut annuler une demande.
     */
    public function cancel(User $user, LeaveRequest $leave): bool
    {
        if (! $leave->canBeCancelled()) {
            return false;
        }

        return $user->isAdmin() || $user->id === $leave->user_id;
    }
}
