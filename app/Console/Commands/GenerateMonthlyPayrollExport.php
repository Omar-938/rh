<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\PayrollExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Génère automatiquement les exports de paie du mois précédent pour toutes les sociétés.
 * À exécuter le 1er de chaque mois à 7h00.
 *
 * Usage manuel :
 *   php artisan payroll:generate-monthly
 *   php artisan payroll:generate-monthly --period=2026-01
 *   php artisan payroll:generate-monthly --company=5
 *   php artisan payroll:generate-monthly --dry-run
 */
class GenerateMonthlyPayrollExport extends Command
{
    protected $signature = 'payroll:generate-monthly
                            {--period=    : Période cible YYYY-MM (défaut : mois précédent)}
                            {--company=   : ID d\'une société spécifique}
                            {--dry-run    : Simule sans écrire en base}';

    protected $description = 'Génère les exports variables de paie (le 1er de chaque mois)';

    public function __construct(private PayrollExportService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        // Par défaut : mois précédent (le cron tourne le 1er du mois courant)
        $period = $this->option('period')
            ? Carbon::parse($this->option('period') . '-01')->format('Y-m')
            : now()->subMonth()->format('Y-m');

        $isDryRun = (bool) $this->option('dry-run');

        $this->info(sprintf(
            '[%s] Génération export paie — %s%s',
            now()->format('d/m/Y H:i'),
            $period,
            $isDryRun ? ' (simulation)' : '',
        ));

        $companies = Company::whereNull('deleted_at');

        if ($companyId = $this->option('company')) {
            $companies->where('id', (int) $companyId);
        }

        $total     = 0;
        $processed = 0;
        $errors    = 0;

        foreach ($companies->get() as $company) {
            $this->line("  → {$company->name} (id:{$company->id})");

            if ($isDryRun) {
                $employees = \App\Models\User::withoutGlobalScopes()
                    ->where('company_id', $company->id)
                    ->where('is_active', true)
                    ->count();
                $this->line("     [DRY-RUN] {$employees} employé(s) — aucune écriture");
                $processed++;
                continue;
            }

            try {
                $export = $this->service->findOrCreate($company, $period);

                if (! $export->canBeModified()) {
                    $this->warn("     Export déjà {$export->status->label()} — ignoré.");
                    continue;
                }

                $this->service->compile($export);

                $count = $export->lines()->count();
                $this->line("     ✓ {$count} ligne(s) compilée(s).");
                $processed++;
            } catch (\Throwable $e) {
                $this->error("     ✗ Erreur : {$e->getMessage()}");
                $errors++;
            }

            $total++;
        }

        $this->newLine();
        $this->info(sprintf(
            '%d société(s) traitée(s)%s%s',
            $processed,
            $errors > 0 ? ", {$errors} erreur(s)" : '',
            $isDryRun ? ' (simulation)' : '',
        ));

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
