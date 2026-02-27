<?php

declare(strict_types=1);

namespace App\Enums;

enum PayrollExportStatus: string
{
    case Draft     = 'draft';
    case Validated = 'validated';
    case Sent      = 'sent';
    case Corrected = 'corrected';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Brouillon',
            self::Validated => 'Validé',
            self::Sent      => 'Envoyé',
            self::Corrected => 'Corrigé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft     => 'slate',
            self::Validated => 'blue',
            self::Sent      => 'emerald',
            self::Corrected => 'amber',
        };
    }

    /** Seul le brouillon est modifiable. */
    public function canEdit(): bool
    {
        return $this === self::Draft;
    }
}
