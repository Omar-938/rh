<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OvertimeStatus;
use App\Models\OvertimeEntry;
use App\Models\User;
use App\Services\TimeTrackingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Redirige vers le tableau de bord adapté au rôle de l'utilisateur.
     */
    public function index(Request $request): Response
    {
        $user = $request->user()->load('company');

        return match ($user->role->value) {
            'admin'   => $this->adminDashboard($request),
            'manager' => $this->managerDashboard($request),
            default   => $this->employeeDashboard($request),
        };
    }

    private function adminDashboard(Request $request): Response
    {
        $user      = $request->user();
        $companyId = $user->company_id;

        $employeesTotal = User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        $overtimePendingCount = OvertimeEntry::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', OvertimeStatus::Pending->value)
            ->count();

        $pendingOvertimeItems = OvertimeEntry::withoutGlobalScopes()
            ->with('user')
            ->where('company_id', $companyId)
            ->where('status', OvertimeStatus::Pending->value)
            ->orderByDesc('date')
            ->limit(5)
            ->get()
            ->map(fn (OvertimeEntry $e) => [
                'id'            => $e->id,
                'employee_name' => $e->user?->full_name,
                'initials'      => $e->user?->initials,
                'hours'         => $e->hours_label,
                'date'          => $e->date->translatedFormat('j M Y'),
            ]);

        return Inertia::render('Dashboard/AdminDashboard', [
            'stats' => [
                'employees_total'    => $employeesTotal,
                'employees_active'   => $employeesTotal,
                'leaves_pending'     => 0,           // phase 2
                'overtime_pending'   => $overtimePendingCount,
                'documents_unsigned' => 0,           // phase 4
                'payroll_due'        => false,
            ],
            'pending_leaves'   => [],
            'pending_overtime' => $pendingOvertimeItems,
            'recent_activity'  => [],
            'is_new_account'   => $employeesTotal === 0,
        ]);
    }

    private function managerDashboard(Request $request): Response
    {
        $user      = $request->user();
        $companyId = $user->company_id;

        // Membres de l'équipe directe
        $teamIds = User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('manager_id', $user->id)
            ->where('is_active', true)
            ->pluck('id');

        $overtimePendingCount = $teamIds->isNotEmpty()
            ? OvertimeEntry::withoutGlobalScopes()
                ->whereIn('user_id', $teamIds)
                ->where('status', OvertimeStatus::Pending->value)
                ->count()
            : 0;

        $pendingOvertimeItems = $teamIds->isNotEmpty()
            ? OvertimeEntry::withoutGlobalScopes()
                ->with('user')
                ->whereIn('user_id', $teamIds)
                ->where('status', OvertimeStatus::Pending->value)
                ->orderByDesc('date')
                ->limit(10)
                ->get()
                ->map(fn (OvertimeEntry $e) => [
                    'id'            => $e->id,
                    'employee_name' => $e->user?->full_name,
                    'initials'      => $e->user?->initials,
                    'type'          => 'Heures sup.',
                    'hours'         => $e->hours_label,
                ])
            : collect();

        return Inertia::render('Dashboard/ManagerDashboard', [
            'stats' => [
                'team_size'        => $teamIds->count(),
                'absent_today'     => 0,  // phase 2
                'overtime_pending' => $overtimePendingCount,
                'present_today'    => 0,  // phase 3
            ],
            'pending_approvals' => $pendingOvertimeItems,
            'team_today'        => [],
        ]);
    }

    private function employeeDashboard(Request $request): Response
    {
        $user  = $request->user();
        $ttSvc = app(TimeTrackingService::class);
        $today = $ttSvc->getToday($user);

        return Inertia::render('Dashboard/EmployeeDashboard', [
            'leave_balances'   => [],
            'my_requests'      => [],
            'week_schedule'    => [],
            'time_entry_today' => $ttSvc->serialize($today),
            'upcoming_leaves'  => [],
        ]);
    }
}
