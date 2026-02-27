<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LeaveBalance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveBalance>
 */
class LeaveBalanceFactory extends Factory
{
    protected $model = LeaveBalance::class;

    public function definition(): array
    {
        $allocated = $this->faker->randomFloat(2, 10, 25);
        $used      = $this->faker->randomFloat(2, 0, $allocated * 0.7);
        $pending   = $this->faker->randomFloat(2, 0, max(0, ($allocated - $used) * 0.3));

        return [
            'year'         => now()->year,
            'allocated'    => $allocated,
            'used'         => $used,
            'pending'      => $pending,
            'carried_over' => $this->faker->randomFloat(2, 0, 5),
            'adjustment'   => 0,
        ];
    }

    public function forCurrentYear(): static
    {
        return $this->state(['year' => now()->year]);
    }

    public function empty(): static
    {
        return $this->state([
            'allocated'    => 0,
            'used'         => 0,
            'pending'      => 0,
            'carried_over' => 0,
            'adjustment'   => 0,
        ]);
    }

    public function full(): static
    {
        return $this->state(fn () => [
            'allocated' => 25.00,
            'used'      => 25.00,
            'pending'   => 0,
        ]);
    }
}
