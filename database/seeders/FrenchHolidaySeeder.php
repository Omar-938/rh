<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Holiday;
use App\Services\HolidayService;
use Illuminate\Database\Seeder;

/**
 * Seeder des jours fériés français.
 * Insère les 11 jours fériés légaux pour l'année en cours et l'année suivante
 * dans toutes les entreprises existantes.
 */
class FrenchHolidaySeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::withoutGlobalScopes()->get();

        if ($companies->isEmpty()) {
            $this->command?->warn('FrenchHolidaySeeder : aucune entreprise trouvée, seed ignoré.');
            return;
        }

        $currentYear = (int) now()->year;
        $years       = [$currentYear, $currentYear + 1];
        $inserted    = 0;

        foreach ($companies as $company) {
            foreach ($years as $year) {
                $inserted += $this->seedForCompanyYear($company->id, $year);
            }
        }

        $this->command?->info("FrenchHolidaySeeder : {$inserted} jours fériés insérés (doublons ignorés).");
    }

    private function seedForCompanyYear(int $companyId, int $year): int
    {
        $count    = 0;
        $holidays = HolidayService::frenchHolidays($year);

        foreach ($holidays as $h) {
            $exists = Holiday::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->whereDate('date', $h['date'])
                ->exists();

            if ($exists) {
                continue;
            }

            Holiday::create([
                'company_id'   => $companyId,
                'name'         => $h['name'],
                'date'         => $h['date'],
                'is_recurring' => $h['is_recurring'],
            ]);

            $count++;
        }

        return $count;
    }
}
