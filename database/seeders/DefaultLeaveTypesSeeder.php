<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AcquisitionType;
use App\Models\Company;
use App\Models\LeaveType;
use Illuminate\Database\Seeder;

/**
 * Crée les types de congés français standards pour une entreprise donnée.
 * Appelé lors de la création d'un nouveau compte et dans le DemoCompanySeeder.
 */
class DefaultLeaveTypesSeeder extends Seeder
{
    /**
     * Types de congés standards (droit français).
     */
    private static array $defaultTypes = [
        [
            'name'                 => 'Congés payés',
            'slug'                 => 'conges-payes',
            'color'                => '#2E86C1',
            'icon'                 => '🌴',
            'days_per_year'        => 25.00,
            'requires_approval'    => true,
            'is_paid'              => true,
            'is_active'            => true,
            'acquisition_type'     => 'monthly',
            'max_consecutive_days' => null,
            'notice_days'          => 7,
            'sort_order'           => 1,
        ],
        [
            'name'                 => 'RTT',
            'slug'                 => 'rtt',
            'color'                => '#9B59B6',
            'icon'                 => '🔄',
            'days_per_year'        => 10.00,
            'requires_approval'    => true,
            'is_paid'              => true,
            'is_active'            => true,
            'acquisition_type'     => 'monthly',
            'max_consecutive_days' => null,
            'notice_days'          => 2,
            'sort_order'           => 2,
        ],
        [
            'name'                 => 'Congé sans solde',
            'slug'                 => 'sans-solde',
            'color'                => '#95A5A6',
            'icon'                 => '⏸️',
            'days_per_year'        => 0.00,
            'requires_approval'    => true,
            'is_paid'              => false,
            'is_active'            => true,
            'acquisition_type'     => 'none',
            'max_consecutive_days' => 90,
            'notice_days'          => 30,
            'sort_order'           => 3,
        ],
        [
            'name'                 => 'Arrêt maladie',
            'slug'                 => 'maladie',
            'color'                => '#E74C3C',
            'icon'                 => '🤒',
            'days_per_year'        => 0.00,
            'requires_approval'    => false,
            'is_paid'              => false,
            'is_active'            => true,
            'acquisition_type'     => 'none',
            'max_consecutive_days' => null,
            'notice_days'          => 0,
            'sort_order'           => 4,
        ],
        [
            'name'                 => 'Événements familiaux',
            'slug'                 => 'evenements-familiaux',
            'color'                => '#27AE60',
            'icon'                 => '🎉',
            'days_per_year'        => 5.00,
            'requires_approval'    => true,
            'is_paid'              => true,
            'is_active'            => true,
            'acquisition_type'     => 'annual',
            'max_consecutive_days' => 5,
            'notice_days'          => 0,
            'sort_order'           => 5,
        ],
        [
            'name'                 => 'Congé maternité / paternité',
            'slug'                 => 'maternite-paternite',
            'color'                => '#F39C12',
            'icon'                 => '👶',
            'days_per_year'        => 0.00,
            'requires_approval'    => true,
            'is_paid'              => false,
            'is_active'            => true,
            'acquisition_type'     => 'none',
            'max_consecutive_days' => null,
            'notice_days'          => 30,
            'sort_order'           => 6,
        ],
    ];

    /**
     * Crée les types de congés par défaut pour la company donnée.
     * Si $company est null, crée pour toutes les companies sans types.
     */
    public function run(?Company $company = null): void
    {
        $companies = $company ? collect([$company]) : Company::withoutGlobalScopes()->get();

        foreach ($companies as $c) {
            // Ne crée que si la company n'a aucun type de congé
            if (LeaveType::withoutGlobalScopes()->where('company_id', $c->id)->exists()) {
                continue;
            }

            foreach (self::$defaultTypes as $type) {
                LeaveType::withoutGlobalScopes()->create([
                    ...$type,
                    'company_id' => $c->id,
                ]);
            }
        }
    }

    /**
     * Crée les types par défaut pour une company spécifique (usage programmatique).
     */
    public static function seedForCompany(Company $company): void
    {
        foreach (self::$defaultTypes as $type) {
            LeaveType::withoutGlobalScopes()->firstOrCreate(
                ['slug' => $type['slug'], 'company_id' => $company->id],
                [...$type, 'company_id' => $company->id]
            );
        }
    }
}
