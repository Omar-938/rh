<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OvertimeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OvertimeRulesController extends Controller
{
    public function __construct(private OvertimeService $overtimeService) {}

    /**
     * Affiche la page des règles et compteurs heures supplémentaires.
     */
    public function index(Request $request): Response
    {
        $user    = $request->user();
        $company = $user->company;
        $year    = (int) $request->query('year', now()->year);

        $settings = [
            'overtime_auto_detect'       => (bool)  $company->getSetting('overtime_auto_detect',       true),
            'overtime_threshold_minutes' => (int)   $company->getSetting('overtime_threshold_minutes', 15),
            'overtime_annual_quota'      => (int)   $company->getSetting('overtime_annual_quota',      220),
            'overtime_rate_50_threshold' => (int)   $company->getSetting('overtime_rate_50_threshold', 43),
            'overtime_threshold_alert'   => (int)   $company->getSetting('overtime_threshold_alert',   10),
        ];

        $companyStats  = $this->overtimeService->getCompanyStats($company->id, $year);
        $employeeStats = $this->overtimeService->getEmployeeStats($company->id, $year, $settings['overtime_annual_quota']);

        return Inertia::render('Settings/OvertimeRules', [
            'settings'       => $settings,
            'company_stats'  => $companyStats,
            'employee_stats' => $employeeStats,
            'year'           => $year,
            'current_year'   => now()->year,
        ]);
    }

    /**
     * Enregistre les règles heures supplémentaires de l'entreprise.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'overtime_auto_detect'       => ['required', 'boolean'],
            'overtime_threshold_minutes' => ['required', 'integer', 'min:5', 'max:60'],
            'overtime_annual_quota'      => ['required', 'integer', 'min:0', 'max:1000'],
            'overtime_rate_50_threshold' => ['required', 'integer', 'min:36', 'max:60'],
            'overtime_threshold_alert'   => ['required', 'integer', 'min:0', 'max:200'],
        ]);

        $request->user()->company->updateSettings($validated);

        return back()->with('flash', [
            'type'    => 'success',
            'message' => 'Règles heures supplémentaires mises à jour.',
        ]);
    }
}
