<?php

declare(strict_types=1);

namespace App\Enums;

enum OvertimeStatus: string
{
    case Pending  = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::Pending  => 'En attente',
            self::Approved => 'Approuvé',
            self::Rejected => 'Refusé',
        };
    }
}
