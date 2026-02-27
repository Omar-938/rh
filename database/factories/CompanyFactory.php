<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name'          => $name,
            'slug'          => Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 9999),
            'siret'         => fake()->numerify('##############'),
            'address'       => fake()->streetAddress(),
            'city'          => fake()->city(),
            'postal_code'   => fake()->postcode(),
            'phone'         => fake()->phoneNumber(),
            'logo_path'     => null,
            'primary_color' => '#1B4F72',
            'plan'          => 'trial',
            'trial_ends_at' => now()->addDays(30),
            'settings'      => [
                'timezone'                 => 'Europe/Paris',
                'work_hours_per_day'       => 7,
                'work_days_per_week'       => 5,
                'overtime_auto_detect'     => true,
                'overtime_threshold_alert' => 10,
                'overtime_rate_25'         => 25,
                'overtime_rate_50'         => 50,
                'overtime_annual_quota'    => 220,
                'leave_carryover'          => false,
                'leave_carryover_max_days' => 0,
                'payroll_reminder_day'     => 3,
                'accountant_emails'        => [],
                'payroll_export_format'    => 'pdf',
            ],
        ];
    }

    /**
     * Plan d'abonnement Starter.
     */
    public function starter(): static
    {
        return $this->state(['plan' => 'starter', 'trial_ends_at' => null]);
    }

    /**
     * Plan d'abonnement Business.
     */
    public function business(): static
    {
        return $this->state(['plan' => 'business', 'trial_ends_at' => null]);
    }

    /**
     * Essai expiré.
     */
    public function trialExpired(): static
    {
        return $this->state(['plan' => 'trial', 'trial_ends_at' => now()->subDay()]);
    }
}
