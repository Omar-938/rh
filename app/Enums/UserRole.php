<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin    = 'admin';
    case Manager  = 'manager';
    case Employee = 'employee';

    public function label(): string
    {
        return match($this) {
            self::Admin    => 'Administrateur RH',
            self::Manager  => 'Manager',
            self::Employee => 'Employé',
        };
    }

    public function canManageCompany(): bool
    {
        return $this === self::Admin;
    }

    public function canApproveRequests(): bool
    {
        return in_array($this, [self::Admin, self::Manager], true);
    }
}
