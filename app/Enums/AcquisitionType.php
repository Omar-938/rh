<?php

declare(strict_types=1);

namespace App\Enums;

enum AcquisitionType: string
{
    case Monthly = 'monthly'; // 2.08j/mois pour 25j/an
    case Annual  = 'annual';  // Tout alloué au 1er janvier
    case None    = 'none';    // Pas d'acquisition automatique (ex: sans solde)

    public function label(): string
    {
        return match($this) {
            self::Monthly => 'Mensuelle (2,08j/mois)',
            self::Annual  => 'Annuelle (1er janvier)',
            self::None    => 'Aucune acquisition automatique',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::Monthly => 'Les jours sont acquis chaque mois au prorata.',
            self::Annual  => 'Le solde complet est crédité au 1er janvier.',
            self::None    => 'Aucune acquisition automatique (congé sans solde, etc.).',
        };
    }
}
