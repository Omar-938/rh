<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AcquisitionType;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        return [
            'name'                 => 'Congés payés',
            'color'                => '#2E86C1',
            'days_per_year'        => 25.00,
            'requires_approval'    => true,
            'is_paid'              => true,
            'is_active'            => true,
            'acquisition_type'     => AcquisitionType::Monthly,
            'max_consecutive_days' => null,
            'notice_days'          => 7,
            'sort_order'           => 0,
        ];
    }

    // ── États prédéfinis ───────────────────────────────────────────────────────

    public function congesPaies(): static
    {
        return $this->state([
            'name'             => 'Congés payés',
            'color'            => '#2E86C1',
            'days_per_year'    => 25.00,
            'acquisition_type' => AcquisitionType::Monthly,
            'is_paid'          => true,
            'notice_days'      => 7,
            'sort_order'       => 1,
        ]);
    }

    public function rtt(): static
    {
        return $this->state([
            'name'             => 'RTT',
            'color'            => '#9B59B6',
            'days_per_year'    => 10.00,
            'acquisition_type' => AcquisitionType::Monthly,
            'is_paid'          => true,
            'notice_days'      => 2,
            'sort_order'       => 2,
        ]);
    }

    public function sansSolde(): static
    {
        return $this->state([
            'name'                 => 'Congé sans solde',
            'color'                => '#95A5A6',
            'days_per_year'        => 0.00,
            'acquisition_type'     => AcquisitionType::None,
            'is_paid'              => false,
            'requires_approval'    => true,
            'max_consecutive_days' => 90,
            'notice_days'          => 30,
            'sort_order'           => 3,
        ]);
    }

    public function maladie(): static
    {
        return $this->state([
            'name'             => 'Arrêt maladie',
            'color'            => '#E74C3C',
            'days_per_year'    => 0.00,
            'acquisition_type' => AcquisitionType::None,
            'is_paid'          => false,
            'requires_approval'=> false,
            'sort_order'       => 4,
        ]);
    }

    public function eventsFamiliaux(): static
    {
        return $this->state([
            'name'                 => 'Événements familiaux',
            'color'                => '#27AE60',
            'days_per_year'        => 5.00,
            'acquisition_type'     => AcquisitionType::Annual,
            'is_paid'              => true,
            'max_consecutive_days' => 5,
            'notice_days'          => 0,
            'sort_order'           => 5,
        ]);
    }
}
