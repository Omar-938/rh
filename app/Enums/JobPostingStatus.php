<?php

declare(strict_types=1);

namespace App\Enums;

enum JobPostingStatus: string
{
    case Draft  = 'draft';
    case Open   = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match($this) {
            self::Draft  => 'Brouillon',
            self::Open   => 'Ouverte',
            self::Closed => 'Fermée',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft  => 'slate',
            self::Open   => 'emerald',
            self::Closed => 'red',
        };
    }
}
