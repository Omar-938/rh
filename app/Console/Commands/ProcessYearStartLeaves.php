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
 * Traitement de début d'année pour les congés.
 * À exécuter le 1er janvier.
 *
 * 1) Types annuels (acquisition_type = 'annual') :
 *    crédite days_per_year sur le solde de la nouvelle année.
 *
 * 2) Report des jours non utilisés (si company.settings.leave_carryover = true) :
 *    reporte les jours restants de l'année passée sur l'année en cours,
 *    plafonné à company.settings.leave_carryover_max_days.
 */
class ProcessYearStartLeaves extends Command
{
    protected $signature = 'leaves:process-year-start
                            {--year= : Année cible (défaut : année courante)}
                            {--dry-run : Simule sans écrire en base}';

    protected $description = 'Traitement annuel des congés : allocation annuelle + report N-1 (1er janvier)';

    public function handle(): int
    {
        $targetYear = $this->option('year')
            ? (int) $this->option('year')
            : now()->year;

        $isDryRun  = $this->option('dry-run');
        $prevYear  = $targetYear - 1;

        $this->info(sprintf(
            '[%s] Traitement début d\'année %d%s',
            now()->format('d/m/Y H:i'),
            $targetYear,
            $isDryRun ? ' (simulation)' : ''
        ));

        $companies = Company::whereNull('deleted_at')->get();

        foreach ($companies as $company) {
            $this->line(sprintf('  Entreprise : %s', $company->name));

            $employees = User::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->get();

            if ($employees->isEmpty()) {
                continue;
            }

            // ── 1. Allocation annuelle ──────────────────────────────────────
            $annualTypes = LeaveType::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->where('acquisition_type', AcquisitionType::Annual)
                ->get();

            foreach ($annualTypes as $leaveType) {
                $days = (float) $leaveType->days_per_year;
                if ($days <= 0) {
                    continue;
                }

                foreach ($employees as $employee) {
                    $this->line(sprintf(
                        '    [Annuel] %-28s %-20s +%.2f j',
                        $employee->full_name,
                        $leaveType->name,
                        $days,
                    ));

                    if (! $isDryRun) {
                        DB::transaction(function () use ($employee, $leaveType, $company, $days, $targetYear): void {
                            $balance = LeaveBalance::withoutGlobalScopes()->firstOrCreate(
                                [
                                    'user_id'       => $employee->id,
                                    'leave_type_id' => $leaveType->id,
                                    'year'          => $targetYear,
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
                            $balance->accrue($days);
                        });
                    }
                }
            }

            // ── 2. Report des jours non utilisés (N-1 → N) ──────────────────
            $settings = $company->settings ?? [];
            $carryoverEnabled = (bool) ($settings['leave_carryover'] ?? false);
            $carryoverMax     = (float) ($settings['leave_carryover_max_days'] ?? 0);

            if (! $carryoverEnabled || $carryoverMax <= 0) {
                continue;
            }

            $this->line(sprintf(
                '    [Report] Report activé — plafond %.0f j',
                $carryoverMax
            ));

            $prevBalances = LeaveBalance::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('year', $prevYear)
                ->get();

            foreach ($prevBalances as $prevBalance) {
                $remaining    = (float) $prevBalance->effective_remaining;
                $toCarryover  = min($remaining, $carryoverMax);

                if ($toCarryover <= 0) {
                    continue;
                }

                $this->line(sprintf(
                    '    [Report] user_id=%d type_id=%d → +%.2f j',
                    $prevBalance->user_id,
                    $prevBalance->leave_type_id,
                    $toCarryover,
                ));

                if (! $isDryRun) {
                    DB::transaction(function () use ($prevBalance, $toCarryover, $targetYear): void {
                        $newBalance = LeaveBalance::withoutGlobalScopes()->firstOrCreate(
                            [
                                'user_id'       => $prevBalance->user_id,
                                'leave_type_id' => $prevBalance->leave_type_id,
                                'year'          => $targetYear,
                            ],
                            [
                                'company_id'   => $prevBalance->company_id,
                                'allocated'    => 0,
                                'used'         => 0,
                                'pending'      => 0,
                                'carried_over' => 0,
                                'adjustment'   => 0,
                            ]
                        );
                        $newBalance->increment('carried_over', $toCarryover);
                    });
                }
            }
        }

        $this->info('Traitement terminé.');

        return Command::SUCCESS;
    }
}
