<?php

declare(strict_types=1);

namespace App\Enums;

enum PayslipStatus: string
{
    case Draft       = 'draft';
    case Distributed = 'distributed';

    public function label(): string
    {
        return match ($this) {
            self::Draft       => 'Non distribué',
            self::Distributed => 'Distribué',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft       => 'gray',
            self::Distributed => 'success',
        };
    }
}
