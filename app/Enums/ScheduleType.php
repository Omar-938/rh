<?php

declare(strict_types=1);

namespace App\Enums;

enum ScheduleType: string
{
    case Work     = 'work';
    case Remote   = 'remote';
    case Off      = 'off';
    case Leave    = 'leave';
    case Sick     = 'sick';
    case Training = 'training';

    public function label(): string
    {
        return match($this) {
            self::Work     => 'Travail',
            self::Remote   => 'Télétravail',
            self::Off      => 'Repos',
            self::Leave    => 'Congé',
            self::Sick     => 'Maladie',
            self::Training => 'Formation',
        };
    }

    public function emoji(): string
    {
        return match($this) {
            self::Work     => '💼',
            self::Remote   => '🏠',
            self::Off      => '😴',
            self::Leave    => '🌴',
            self::Sick     => '🤒',
            self::Training => '📚',
        };
    }

    /** Couleur Tailwind pour les badges (classe CSS). */
    public function colorClass(): string
    {
        return match($this) {
            self::Work     => 'success',
            self::Remote   => 'primary',
            self::Off      => 'slate',
            self::Leave    => 'warning',
            self::Sick     => 'danger',
            self::Training => 'violet',
        };
    }

    /** Hex pour les styles inline. */
    public function hexBg(): string
    {
        return match($this) {
            self::Work     => '#DCFCE7',
            self::Remote   => '#DBEAFE',
            self::Off      => '#F1F5F9',
            self::Leave    => '#FEF3C7',
            self::Sick     => '#FEE2E2',
            self::Training => '#EDE9FE',
        };
    }

    public function hexText(): string
    {
        return match($this) {
            self::Work     => '#15803D',
            self::Remote   => '#1D4ED8',
            self::Off      => '#64748B',
            self::Leave    => '#92400E',
            self::Sick     => '#991B1B',
            self::Training => '#5B21B6',
        };
    }

    /** Vrai si l'employé est physiquement présent. */
    public function isPresent(): bool
    {
        return in_array($this, [self::Work, self::Training]);
    }

    /** Vrai si l'employé est absent du bureau. */
    public function isAbsent(): bool
    {
        return in_array($this, [self::Leave, self::Sick, self::Off]);
    }
}
