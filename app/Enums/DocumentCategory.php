<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentCategory: string
{
    case Contract    = 'contract';
    case Amendment   = 'amendment';
    case Certificate = 'certificate';
    case Rules       = 'rules';
    case Medical     = 'medical';
    case Identity    = 'identity';
    case Rib         = 'rib';
    case Review      = 'review';
    case Other       = 'other';

    public function label(): string
    {
        return match($this) {
            self::Contract    => 'Contrat',
            self::Amendment   => 'Avenant',
            self::Certificate => 'Attestation',
            self::Rules       => 'Règlement intérieur',
            self::Medical     => 'Document médical',
            self::Identity    => 'Pièce d\'identité',
            self::Rib         => 'RIB',
            self::Review      => 'Entretien',
            self::Other       => 'Autre',
        };
    }
}
