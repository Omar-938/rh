<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PayrollExportStatus;
use App\Models\Company;
use App\Models\PayrollExport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollExport>
 */
class PayrollExportFactory extends Factory
{
    protected $model = PayrollExport::class;

    public function definition(): array
    {
        $year  = fake()->numberBetween(2024, 2026);
        $month = fake()->numberBetween(1, 12);

        return [
            'company_id'    => Company::factory(),
            'period'        => sprintf('%d-%02d', $year, $month),
            'status'        => PayrollExportStatus::Draft,
            'is_correction' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => PayrollExportStatus::Draft]);
    }

    public function validated(): static
    {
        return $this->state(['status' => PayrollExportStatus::Validated]);
    }

    public function sent(): static
    {
        return $this->state(['status' => PayrollExportStatus::Sent]);
    }
}
