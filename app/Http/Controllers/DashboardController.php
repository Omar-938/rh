<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LeaveStatus;
use App\Enums\OvertimeStatus;
use App\Enums\ScheduleType;
use App\Models\LeaveRequest;
use App\Models\OvertimeEntry;
use App\Models\Schedule;
use App\Models\User;
use App\Services\TimeTrackingService;
use Carbon\Carbon;
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
                'leaves_pending'     => 0,
                'overtime_pending'   => $overtimePendingCount,
                'documents_unsigned' => 0,
                'payroll_due'        => false,
            ],
            'pending_leaves'   => [],
            'pending_overtime' => $pendingOvertimeItems,
            'recent_activity'  => [],
            'is_new_account'   => $employeesTotal === 0,
            'team_today'       => $this->buildTeamToday($companyId),
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
            'week_schedule'    => $this->buildWeekSchedule($user),
            'time_entry_today' => $ttSvc->serialize($today),
            'upcoming_leaves'  => [],
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * Construit le planning des 7 jours de la semaine courante pour un employé.
     * Utilise les Schedule DB si définis, sinon déduit un défaut lun-ven 9h-17h.
     */
    private function buildWeekSchedule(User $user): array
    {
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        $schedules = Schedule::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->whereBetween('date', [
                $monday->toDateString(),
                $monday->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
            ])
            ->get()
            ->keyBy(fn (Schedule $s) => $s->date->format('Y-m-d'));

        $week = [];
        for ($i = 0; $i < 7; $i++) {
            $day     = $monday->copy()->addDays($i);
            $dateStr = $day->toDateString();
            $entry   = $schedules->get($dateStr);

            if ($entry) {
                $isOff = in_array($entry->type, [ScheduleType::Off, ScheduleType::Leave, ScheduleType::Sick]);
                $week[] = [
                    'is_off'      => $isOff,
                    'type'        => $entry->type->value,
                    'type_label'  => $entry->type->label(),
                    'type_emoji'  => $entry->type->emoji(),
                    'start'       => $entry->start_time ? substr($entry->start_time, 0, 5) : null,
                    'end'         => $entry->end_time   ? substr($entry->end_time, 0, 5)   : null,
                    'duration'    => $entry->duration_label,
                ];
            } else {
                // Défaut : lundi-vendredi → travail 9h-17h, week-end → repos
                $isWeekend = $day->isWeekend();
                $week[] = [
                    'is_off'     => $isWeekend,
                    'type'       => $isWeekend ? 'off' : 'work',
                    'type_label' => $isWeekend ? 'Repos' : 'Travail',
                    'type_emoji' => $isWeekend ? '😴' : '💼',
                    'start'      => $isWeekend ? null : '09:00',
                    'end'        => $isWeekend ? null : '17:00',
                    'duration'   => $isWeekend ? null : '7h',
                ];
            }
        }

        return $week;
    }

    /**
     * Résumé présence équipe pour aujourd'hui (admin/manager).
     */
    private function buildTeamToday(int $companyId): array
    {
        $today    = Carbon::today()->toDateString();
        $employees = User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->get(['id', 'first_name', 'last_name', 'role']);

        $schedulesByUser = Schedule::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereDate('date', $today)
            ->whereIn('user_id', $employees->pluck('id'))
            ->get()
            ->keyBy('user_id');

        $absentUserIds = LeaveRequest::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', LeaveStatus::Approved->value)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->pluck('user_id')
            ->flip();

        $result = ['present' => 0, 'remote' => 0, 'absent' => 0, 'unknown' => 0, 'members' => []];

        foreach ($employees as $emp) {
            $schedule = $schedulesByUser->get($emp->id);
            $onLeave  = $absentUserIds->has($emp->id);

            if ($onLeave) {
                $status = 'absent'; $emoji = '🌴';
            } elseif ($schedule) {
                $status = match ($schedule->type) {
                    ScheduleType::Work, ScheduleType::Training => 'present',
                    ScheduleType::Remote                       => 'remote',
                    default                                    => 'absent',
                };
                $emoji = $schedule->type->emoji();
            } else {
                // Pas de schedule défini → supposé présent si jour ouvré
                $isWeekend = Carbon::today()->isWeekend();
                $status = $isWeekend ? 'absent' : 'present';
                $emoji  = $isWeekend ? '😴' : '💼';
            }

            $result[$status]++;
            $result['members'][] = [
                'id'       => $emp->id,
                'name'     => $emp->first_name . ' ' . $emp->last_name[0] . '.',
                'initials' => strtoupper(substr($emp->first_name, 0, 1) . substr($emp->last_name, 0, 1)),
                'status'   => $status,
                'emoji'    => $emoji,
            ];
        }

        return $result;
    }
}
