<?php

declare(strict_types=1);

namespace App\Enums;

enum LeaveStatus: string
{
    case Pending   = 'pending';
    case Approved  = 'approved';
    case Rejected  = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'En attente',
            self::Approved  => 'Approuvé',
            self::Rejected  => 'Refusé',
            self::Cancelled => 'Annulé',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending   => 'warning',
            self::Approved  => 'success',
            self::Rejected  => 'danger',
            self::Cancelled => 'gray',
        };
    }
}
