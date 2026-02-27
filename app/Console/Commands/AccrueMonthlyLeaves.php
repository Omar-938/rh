<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AcquisitionType;
use App\Models\Company;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Acquisition mensuelle des congés.
 * À exécuter le 1er de chaque mois.
 *
 * Pour chaque entreprise active → pour chaque type de congé mensuel actif
 * → pour chaque employé actif : crédite days_per_year / 12 jours.
 *
 * Pro-ration pour les arrivées en cours de mois.
 */
class AccrueMonthlyLeaves extends Command
{
    protected $signature = 'leaves:accrue-monthly
                            {--month= : Mois cible YYYY-MM (défaut : mois courant)}
                            {--dry-run : Simule sans écrire en base}';

    protected $description = 'Acquisition mensuelle des jours de congé (à exécuter le 1er du mois)';

    public function handle(): int
    {
        $targetMonth  = $this->option('month')
            ? Carbon::parse($this->option('month') . '-01')
            : now()->startOfMonth();

        $isDryRun = $this->option('dry-run');

        $this->info(sprintf(
            '[%s] Acquisition mensuelle — %s%s',
            now()->format('d/m/Y H:i'),
            $targetMonth->translatedFormat('F Y'),
            $isDryRun ? ' (simulation)' : ''
        ));

        $companies = Company::whereNull('deleted_at')->get();
        $totalAccrued = 0;

        foreach ($companies as $company) {
            $leaveTypes = LeaveType::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->where('acquisition_type', AcquisitionType::Monthly)
                ->get();

            if ($leaveTypes->isEmpty()) {
                continue;
            }

            $employees = User::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->get();

            if ($employees->isEmpty()) {
                continue;
            }

            foreach ($leaveTypes as $leaveType) {
                $monthlyBase = round((float) $leaveType->days_per_year / 12, 2);

                foreach ($employees as $employee) {
                    $accrualDays = $this->calculateAccrual(
                        $monthlyBase,
                        $employee->hire_date,
                        $targetMonth,
                    );

                    if ($accrualDays <= 0) {
                        continue;
                    }

                    $this->line(sprintf(
                        '  %-30s %-25s +%.2f j',
                        $employee->full_name,
                        $leaveType->name,
                        $accrualDays,
                    ));

                    if (! $isDryRun) {
                        DB::transaction(function () use ($employee, $leaveType, $company, $accrualDays, $targetMonth): void {
                            $balance = LeaveBalance::withoutGlobalScopes()->firstOrCreate(
                                [
                                    'user_id'       => $employee->id,
                                    'leave_type_id' => $leaveType->id,
                                    'year'          => $targetMonth->year,
                                ],
                                [
                                    'company_id'   => $company->id,
                                    'allocated'    => 0,
                                    'used'         => 0,
                                    'pending'      => 0,
                                    'carried_over' => 0,
                                    'adjustment'   => 0,
                                ]
                            );

                            $balance->accrue($accrualDays);
                        });
                    }

                    $totalAccrued++;
                }
            }
        }

        $this->info(sprintf(
            '%d enregistrement(s) traité(s).%s',
            $totalAccrued,
            $isDryRun ? ' (aucune écriture — mode simulation)' : ''
        ));

        return Command::SUCCESS;
    }

    /**
     * Calcule les jours à acquérir, avec pro-ration si l'employé a été embauché
     * au cours du mois cible.
     */
    private function calculateAccrual(
        float   $monthlyBase,
        ?Carbon $hireDate,
        Carbon  $targetMonth,
    ): float {
        // Pas encore embauché lors du mois cible
        if ($hireDate && $hireDate->gt($targetMonth->copy()->endOfMonth())) {
            return 0.0;
        }

        // Embauché en cours du mois cible → pro-ration
        if ($hireDate
            && $hireDate->year  === $targetMonth->year
            && $hireDate->month === $targetMonth->month
            && $hireDate->day   > 1
        ) {
            $daysInMonth     = $targetMonth->daysInMonth;
            $workedDays      = $daysInMonth - $hireDate->day + 1;
            $prorated        = round($monthlyBase * ($workedDays / $daysInMonth), 2);

            return max(0.0, $prorated);
        }

        return $monthlyBase;
    }
}
