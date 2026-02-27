<?php

declare(strict_types=1);

namespace App\Enums;

enum ContractType: string
{
    case CDI        = 'cdi';
    case CDD        = 'cdd';
    case Interim    = 'interim';
    case Stage      = 'stage';
    case Alternance = 'alternance';

    public function label(): string
    {
        return match($this) {
            self::CDI        => 'CDI',
            self::CDD        => 'CDD',
            self::Interim    => 'Intérim',
            self::Stage      => 'Stage',
            self::Alternance => 'Alternance',
        };
    }
}
