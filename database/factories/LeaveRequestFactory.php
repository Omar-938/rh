<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LeaveStatus;
use App\Models\Company;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+7 days', '+2 months');
        $end   = (clone $start)->modify('+' . fake()->numberBetween(1, 5) . ' days');

        return [
            'user_id'          => User::factory(),
            'company_id'       => Company::factory(),
            'leave_type_id'    => LeaveType::factory(),
            'start_date'       => $start->format('Y-m-d'),
            'end_date'         => $end->format('Y-m-d'),
            'days_count'       => fake()->randomFloat(1, 1, 5),
            'status'           => LeaveStatus::Pending,
            'employee_comment' => fake()->optional(0.5)->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => LeaveStatus::Pending]);
    }

    public function approved(): static
    {
        return $this->state(['status' => LeaveStatus::Approved]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => LeaveStatus::Rejected]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => LeaveStatus::Cancelled]);
    }
}
