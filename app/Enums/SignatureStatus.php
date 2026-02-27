<?php

declare(strict_types=1);

namespace App\Enums;

enum SignatureStatus: string
{
    case Pending  = 'pending';
    case Signed   = 'signed';
    case Declined = 'declined';
    case Expired  = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending  => 'En attente',
            self::Signed   => 'Signé',
            self::Declined => 'Refusé',
            self::Expired  => 'Expiré',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending  => 'warning',
            self::Signed   => 'success',
            self::Declined => 'danger',
            self::Expired  => 'gray',
        };
    }
}
