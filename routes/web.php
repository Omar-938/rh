<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\RecruitmentController;
use App\Http\Controllers\PayrollExportController;
use App\Http\Controllers\Admin\LeaveBalanceController;
use App\Http\Controllers\Admin\LeaveTypeController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\CompanySettingsController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\Admin\OvertimeRulesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\TimeTrackingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Page d'accueil publique
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Welcome');
})->name('home');

/*
|--------------------------------------------------------------------------
| Route publique de signature (sans authentification)
|--------------------------------------------------------------------------
*/
Route::prefix('signature')->name('signature.')->group(function () {
    Route::get('/{token}',          [SignatureController::class, 'show'])   ->name('show');
    Route::post('/{token}/signer',  [SignatureController::class, 'sign'])   ->name('sign');
    Route::post('/{token}/refuser', [SignatureController::class, 'decline'])->name('decline');
});

/*
|--------------------------------------------------------------------------
| Routes auth (Fortify + custom)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';

// ── OAuth Google ─────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/auth/google',          [SocialAuthController::class, 'redirectToGoogle'])   ->name('auth.google');
    Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
    Route::get('/auth/google/complete', [SocialAuthController::class, 'showComplete'])        ->name('auth.google.complete');
    Route::post('/auth/google/complete', [SocialAuthController::class, 'complete'])           ->name('auth.google.complete.post');
});

/*
|--------------------------------------------------------------------------
| Routes protégées (auth + email vérifié + scope company)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'company.scope'])->group(function () {

    // ── Collaborateurs ────────────────────────────────────────────────────────
    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/',                              [EmployeeController::class, 'index'])        ->name('index');
        Route::post('/',                             [EmployeeController::class, 'store'])        ->name('store');
        Route::get('/import',                        [EmployeeController::class, 'importPage'])   ->name('import');
        Route::post('/import/confirmer',             [EmployeeController::class, 'importConfirm'])->name('import.confirm');
        Route::get('/{employee}',                    [EmployeeController::class, 'show'])         ->name('show');
        Route::patch('/{employee}',                  [EmployeeController::class, 'update'])       ->name('update');
        Route::patch('/{employee}/toggle-active',    [EmployeeController::class, 'toggleActive']) ->name('toggle-active');
        Route::post('/{employee}/reset-password',    [EmployeeController::class, 'resetPassword'])->name('reset-password');
    });

    // ── Dashboard ────────────────────────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Notifications ─────────────────────────────────────────────────────────
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/',                        [NotificationController::class, 'index'])      ->name('index');
        Route::get('/recent',                  [NotificationController::class, 'recent'])     ->name('recent');
        Route::post('/{id}/read',              [NotificationController::class, 'markAsRead']) ->name('read');
        Route::post('/read-all',               [NotificationController::class, 'markAllAsRead'])->name('read-all');
    });

    // ── Planning ─────────────────────────────────────────────────────────────
    Route::prefix('planning')->name('planning.')->group(function () {
        Route::get('/',                      [PlanningController::class, 'index'])  ->name('index');
        Route::get('/semaine/{date?}',       [PlanningController::class, 'week'])   ->name('week');
        Route::get('/mois/{date?}',          [PlanningController::class, 'month'])  ->name('month');
        Route::post('/schedules',            [PlanningController::class, 'store'])  ->name('schedules.store');
        Route::put('/schedules/{schedule}',  [PlanningController::class, 'update']) ->name('schedules.update');
        Route::delete('/schedules/{schedule}',[PlanningController::class, 'destroy'])->name('schedules.destroy');
    });

    // ── Documents ─────────────────────────────────────────────────────────────
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/',       [DocumentController::class, 'index'])  ->name('index');
        Route::get('/upload', [DocumentController::class, 'create']) ->name('create');
        Route::post('/upload',[DocumentController::class, 'store'])  ->name('store');

        Route::get('/{document}',                    [DocumentController::class, 'show'])            ->name('show');
        Route::get('/{document}/download',           [DocumentController::class, 'download'])           ->name('download');
        Route::get('/{document}/certificat',         [DocumentController::class, 'downloadCertificate'])->name('certificate');
        Route::post('/{document}/demande-signature', [DocumentController::class, 'requestSignature'])->name('request-signature');
        Route::post('/{document}/renvoyer-signature',[DocumentController::class, 'resendSignature']) ->name('resend-signature');
        Route::delete('/{document}/signature',       [DocumentController::class, 'revokeSignature']) ->name('revoke-signature');
        Route::delete('/{document}',                 [DocumentController::class, 'destroy'])         ->name('destroy');
    });

    // ── Bulletins de paie ─────────────────────────────────────────────────────
    Route::prefix('bulletins')->name('payslips.')->group(function () {
        Route::get('/',                        [PayslipController::class, 'index'])          ->name('index');
        Route::get('/importer',                [PayslipController::class, 'create'])         ->name('create');
        Route::post('/importer',               [PayslipController::class, 'store'])          ->name('store');
        Route::post('/distribuer-lot',         [PayslipController::class, 'distributeMany']) ->name('distribute-many');
        Route::get('/{payslip}/download',      [PayslipController::class, 'download'])       ->name('download');
        Route::post('/{payslip}/distribuer',   [PayslipController::class, 'distribute'])     ->name('distribute');
        Route::delete('/{payslip}',            [PayslipController::class, 'destroy'])        ->name('destroy');
    });

    // ── Congés ────────────────────────────────────────────────────────────────
    Route::prefix('conges')->name('leaves.')->group(function () {
        Route::get('/',                                [LeaveController::class, 'index'])  ->name('index');
        Route::get('/demande',                         [LeaveController::class, 'create']) ->name('create');
        Route::post('/',                               [LeaveController::class, 'store'])  ->name('store');
        Route::get('/soldes',                          [LeaveController::class, 'balances'])->name('balances');
        Route::get('/{leave}',                         [LeaveController::class, 'show'])               ->name('show');
        Route::get('/{leave}/justificatif',            [LeaveController::class, 'downloadAttachment']) ->name('download-attachment');
        Route::post('/{leave}/approuver',              [LeaveController::class, 'approve'])            ->name('approve');
        Route::post('/{leave}/rejeter',                [LeaveController::class, 'reject'])             ->name('reject');
        Route::delete('/{leave}',                      [LeaveController::class, 'cancel'])             ->name('cancel');
    });

    // ── Pointage ──────────────────────────────────────────────────────────────
    Route::prefix('pointage')->name('time.')->group(function () {
        Route::get('/',           [TimeTrackingController::class, 'clock'])     ->name('clock');
        Route::post('/arrivee',  [TimeTrackingController::class, 'clockIn'])   ->name('clock-in');
        Route::post('/depart',   [TimeTrackingController::class, 'clockOut'])  ->name('clock-out');
        Route::post('/pause',    [TimeTrackingController::class, 'startBreak'])->name('break-start');
        Route::post('/reprise',  [TimeTrackingController::class, 'endBreak'])  ->name('break-end');
        Route::get('/historique',[TimeTrackingController::class, 'history'])   ->name('history');
        Route::get('/rapport',   [TimeTrackingController::class, 'report'])       ->name('report')
             ->middleware('role:admin,manager');
        Route::get('/equipe',    [TimeTrackingController::class, 'teamOverview'])->name('team-overview')
             ->middleware('role:admin,manager');
    });

    // ── Heures supplémentaires ────────────────────────────────────────────────
    Route::prefix('heures-supplementaires')->name('overtime.')->group(function () {
        Route::get('/',           [OvertimeController::class, 'index'])  ->name('index');
        Route::get('/declarer',   [OvertimeController::class, 'create']) ->name('create');
        Route::post('/declarer',  [OvertimeController::class, 'store'])  ->name('store');
        Route::get('/{overtime}', [OvertimeController::class, 'show'])   ->name('show');
        Route::post('/{overtime}/approuver', [OvertimeController::class, 'approve'])->name('approve');
        Route::post('/{overtime}/rejeter',   [OvertimeController::class, 'reject']) ->name('reject');
    });

    // ── Exports variables de paie ─────────────────────────────────────────────
    Route::prefix('exports-paie')->name('payroll-exports.')->group(function () {
        Route::get('/',                          [PayrollExportController::class, 'index'])     ->name('index');
        Route::post('/generer',                  [PayrollExportController::class, 'generate'])  ->name('generate');
        Route::get('/{export}',                  [PayrollExportController::class, 'show'])      ->name('show');
        Route::post('/{export}/recompiler',      [PayrollExportController::class, 'recompile'])  ->name('recompile');
        Route::post('/{export}/valider',         [PayrollExportController::class, 'validate'])   ->name('validate');
        Route::put('/{export}/lignes/{line}',          [PayrollExportController::class, 'updateLine']) ->name('update-line');
        Route::post('/{export}/envoyer',               [PayrollExportController::class, 'send'])       ->name('send');
        Route::get('/{export}/telecharger/{format}',   [PayrollExportController::class, 'download'])   ->name('download');
    });

    // ── Recrutement ──────────────────────────────────────────────────────────
    Route::prefix('recrutement')->name('recruitment.')->group(function () {
        Route::get('/',                                    [RecruitmentController::class, 'index'])          ->name('index');
        Route::post('/offres',                             [RecruitmentController::class, 'storePosting'])   ->name('postings.store');
        Route::get('/offres/{posting}',                    [RecruitmentController::class, 'showPosting'])    ->name('postings.show');
        Route::patch('/offres/{posting}',                  [RecruitmentController::class, 'updatePosting'])  ->name('postings.update');
        Route::delete('/offres/{posting}',                 [RecruitmentController::class, 'destroyPosting']) ->name('postings.destroy');
        Route::post('/candidats',                          [RecruitmentController::class, 'storeCandidate']) ->name('candidates.store');
        Route::patch('/candidats/{candidate}/etape',       [RecruitmentController::class, 'updateStage'])    ->name('candidates.update-stage');
        Route::patch('/candidats/{candidate}',             [RecruitmentController::class, 'updateCandidate'])->name('candidates.update');
        Route::delete('/candidats/{candidate}',            [RecruitmentController::class, 'destroyCandidate'])->name('candidates.destroy');
        Route::get('/candidats/{candidate}/cv',            [RecruitmentController::class, 'downloadCv'])    ->name('candidates.cv');
    });

    // ── Paramètres Admin ─────────────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('parametres')->name('settings.')->group(function () {

        // Hub paramètres
        Route::get('/', fn () => Inertia::render('Settings/Index'))->name('index');

        // Facturation & abonnement
        Route::get('/facturation',          [BillingController::class, 'index'])   ->name('billing.index');
        Route::post('/facturation/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
        Route::get('/facturation/succes',    [BillingController::class, 'success']) ->name('billing.success');
        Route::post('/facturation/portail',  [BillingController::class, 'portal'])  ->name('billing.portal');
        Route::post('/facturation/changer',  [BillingController::class, 'swap'])    ->name('billing.swap');

        // Informations & personnalisation entreprise
        Route::get('/entreprise',         [CompanySettingsController::class, 'index'])     ->name('company.index');
        Route::post('/entreprise',        [CompanySettingsController::class, 'update'])    ->name('company.update');
        Route::delete('/entreprise/logo', [CompanySettingsController::class, 'deleteLogo'])->name('company.delete-logo');

        // Départements
        Route::resource('departements', DepartmentController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['departements' => 'department']);

        // Types de congés
        Route::resource('types-conges', LeaveTypeController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['types-conges' => 'leaveType']);

        Route::patch('types-conges/{leaveType}/toggle',  [LeaveTypeController::class, 'toggle'])
            ->name('types-conges.toggle');
        Route::post('types-conges/reorder', [LeaveTypeController::class, 'reorder'])
            ->name('types-conges.reorder');

        // Gestion soldes congés
        Route::post('soldes/ajuster', [LeaveBalanceController::class, 'adjust'])->name('balances.adjust');
        Route::post('soldes/reinitialiser', [LeaveBalanceController::class, 'reset'])->name('balances.reset');

        // Règles heures supplémentaires
        Route::get('heures-supplementaires',   [OvertimeRulesController::class, 'index']) ->name('overtime-rules.index');
        Route::patch('heures-supplementaires', [OvertimeRulesController::class, 'update'])->name('overtime-rules.update');

        // Jours fériés
        Route::get('jours-feries',                    [HolidayController::class, 'index'])        ->name('holidays.index');
        Route::post('jours-feries',                   [HolidayController::class, 'store'])        ->name('holidays.store');
        Route::put('jours-feries/{holiday}',          [HolidayController::class, 'update'])       ->name('holidays.update');
        Route::delete('jours-feries/{holiday}',       [HolidayController::class, 'destroy'])      ->name('holidays.destroy');
        Route::post('jours-feries/importer-francais', [HolidayController::class, 'importFrench']) ->name('holidays.import-french');

    });

});
