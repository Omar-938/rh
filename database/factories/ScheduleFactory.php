<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ScheduleType;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(ScheduleType::cases());

        $startHour = $this->faker->numberBetween(8, 9);
        $endHour   = $startHour + $this->faker->numberBetween(7, 9);

        return [
            'date'          => $this->faker->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'type'          => $type,
            'start_time'    => in_array($type, [ScheduleType::Work, ScheduleType::Remote, ScheduleType::Training])
                ? sprintf('%02d:00:00', $startHour)
                : null,
            'end_time'      => in_array($type, [ScheduleType::Work, ScheduleType::Remote, ScheduleType::Training])
                ? sprintf('%02d:00:00', min($endHour, 19))
                : null,
            'break_minutes' => 60,
            'notes'         => $this->faker->optional(0.2)->sentence(),
        ];
    }

    public function work(): static
    {
        return $this->state([
            'type'       => ScheduleType::Work,
            'start_time' => '09:00:00',
            'end_time'   => '17:00:00',
        ]);
    }

    public function remote(): static
    {
        return $this->state([
            'type'       => ScheduleType::Remote,
            'start_time' => '09:00:00',
            'end_time'   => '17:00:00',
        ]);
    }

    public function leave(): static
    {
        return $this->state([
            'type'       => ScheduleType::Leave,
            'start_time' => null,
            'end_time'   => null,
        ]);
    }
}
