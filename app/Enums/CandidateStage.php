<?php

declare(strict_types=1);

namespace App\Enums;

enum CandidateStage: string
{
    case Received    = 'received';
    case Shortlisted = 'shortlisted';
    case Interview   = 'interview';
    case Selected    = 'selected';
    case Hired       = 'hired';
    case Rejected    = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::Received    => 'Reçu',
            self::Shortlisted => 'Présélectionné',
            self::Interview   => 'Entretien',
            self::Selected    => 'Retenu',
            self::Hired       => 'Embauché',
            self::Rejected    => 'Refusé',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Received    => 'slate',
            self::Shortlisted => 'blue',
            self::Interview   => 'amber',
            self::Selected    => 'indigo',
            self::Hired       => 'emerald',
            self::Rejected    => 'red',
        };
    }

    public function emoji(): string
    {
        return match($this) {
            self::Received    => '📥',
            self::Shortlisted => '⭐',
            self::Interview   => '🗓️',
            self::Selected    => '✅',
            self::Hired       => '🎉',
            self::Rejected    => '❌',
        };
    }

    /**
     * Ordre des étapes du pipeline (hors refusé).
     */
    public static function pipelineOrder(): array
    {
        return [
            self::Received,
            self::Shortlisted,
            self::Interview,
            self::Selected,
            self::Hired,
            self::Rejected,
        ];
    }

    public function next(): ?self
    {
        $order = array_values(array_filter(self::pipelineOrder(), fn ($s) => $s !== self::Rejected));
        $idx   = array_search($this, $order, true);
        return ($idx !== false && isset($order[$idx + 1])) ? $order[$idx + 1] : null;
    }

    public function previous(): ?self
    {
        $order = array_values(array_filter(self::pipelineOrder(), fn ($s) => $s !== self::Rejected));
        $idx   = array_search($this, $order, true);
        return ($idx > 0) ? $order[$idx - 1] : null;
    }
}
