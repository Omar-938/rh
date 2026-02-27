<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LeaveType;
use App\Models\User;

class LeaveTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, LeaveType $leaveType): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, LeaveType $leaveType): bool
    {
        return $user->isAdmin();
    }
}
