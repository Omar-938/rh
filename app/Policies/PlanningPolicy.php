<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class PlanningPolicy
{
    /** Tous les utilisateurs authentifiés peuvent consulter le planning. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Seuls admin et manager peuvent créer/modifier/supprimer des entrées. */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return $user->isAdmin() || $user->isManager();
    }
}
