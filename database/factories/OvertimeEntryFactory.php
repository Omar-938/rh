<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OvertimeStatus;
use App\Models\Company;
use App\Models\OvertimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OvertimeEntry>
 */
class OvertimeEntryFactory extends Factory
{
    protected $model = OvertimeEntry::class;

    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'company_id' => Company::factory(),
            'date'       => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'hours'      => fake()->randomFloat(2, 0.5, 8),
            'rate'       => fake()->randomElement(['25', '50']),
            'source'     => 'manual',
            'status'     => OvertimeStatus::Pending,
            'reason'     => fake()->optional(0.7)->sentence(),
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => OvertimeStatus::Approved]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => OvertimeStatus::Rejected]);
    }
}
