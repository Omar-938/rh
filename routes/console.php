<?php

declare(strict_types=1);

use App\Console\Commands\AccrueMonthlyLeaves;
use App\Console\Commands\GenerateMonthlyPayrollExport;
use App\Console\Commands\ProcessYearStartLeaves;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Planification des tâches
|--------------------------------------------------------------------------
*/

// Acquisition mensuelle des congés — le 1er de chaque mois à minuit
Schedule::command(AccrueMonthlyLeaves::class)
    ->cron('0 0 1 * *')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/leaves-accrual.log'));

// Export variables de paie — le 1er de chaque mois à 7h00 (données du mois précédent)
Schedule::command(GenerateMonthlyPayrollExport::class)
    ->cron('0 7 1 * *')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/payroll-export.log'));

// Traitement début d'année (alloc. annuelle + report N-1) — le 1er janvier à 00h30
Schedule::command(ProcessYearStartLeaves::class)
    ->cron('30 0 1 1 *')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/leaves-year-start.log'));
