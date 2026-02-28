<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use App\Models\User;
use App\Services\TimeTrackingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TimeTrackingController extends Controller
{
    public function __construct(private TimeTrackingService $timeTrackingService) {}

    /**
     * Page principale de pointage.
     */
    public function clock(Request $request): Response
    {
        $user  = $request->user();
        $today = $this->timeTrackingService->getToday($user);
        $week  = $this->timeTrackingService->getWeekEntries($user);

        return Inertia::render('TimeTracking/Clock', [
            'entry'       => $this->timeTrackingService->serialize($today),
            'week'        => $week,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Clock-in.
     */
    public function clockIn(Request $request): RedirectResponse
    {
        $entry = $this->timeTrackingService->clockIn($request->user(), $request);

        return back()->with('flash', [
            'type'    => 'success',
            'message' => 'Arrivée pointée à ' . $entry->clock_in->format('H:i') . '.',
        ]);
    }

    /**
     * Clock-out — avec détection automatique des heures sup.
     */
    public function clockOut(Request $request): RedirectResponse
    {
        ['entry' => $entry, 'overtime' => $overtime] = $this->timeTrackingService->clockOut($request->user());

        $message = 'Départ pointé à ' . $entry->clock_out->format('H:i') . '. ' . $entry->duration_label . ' travaillées.';

        if ($overtime) {
            $message .= ' · ' . $overtime->hours_label . ' d\'heures sup. détectées (' . $overtime->rate_label . ') — en attente de validation.';
        }

        return back()->with('flash', [
            'type'    => $overtime ? 'warning' : 'success',
            'message' => $message,
        ]);
    }

    /**
     * Début de pause.
     */
    public function startBreak(Request $request): RedirectResponse
    {
        $this->timeTrackingService->startBreak($request->user());

        return back()->with('flash', [
            'type'    => 'success',
            'message' => 'Pause démarrée.',
        ]);
    }

    /**
     * Fin de pause.
     */
    public function endBreak(Request $request): RedirectResponse
    {
        $this->timeTrackingService->endBreak($request->user());

        return back()->with('flash', [
            'type'    => 'success',
            'message' => 'Pause terminée. Reprise du travail.',
        ]);
    }

    /**
     * Historique mensuel — employé courant ou employé sélectionné (admin/manager).
     */
    public function history(Request $request): Response
    {
        $auth  = $request->user();
        $year  = (int) ($request->query('year',  now()->year));
        $month = (int) ($request->query('month', now()->month));

        // Admin/manager peuvent consulter l'historique d'un autre employé
        $targetUser = $auth;
        $canSelectEmployee = $auth->isAdmin() || $auth->isManager();

        if ($canSelectEmployee && $request->filled('user_id')) {
            $targetUser = User::withoutGlobalScopes()
                ->where('company_id', $auth->company_id)
                ->findOrFail((int) $request->query('user_id'));
        }

        // Liste des employés pour le sélecteur (admin/manager uniquement)
        $employees = [];
        if ($canSelectEmployee) {
            $query = User::withoutGlobalScopes()
                ->where('company_id', $auth->company_id)
                ->where('is_active', true)
                ->orderBy('last_name')
                ->orderBy('first_name');

            // Manager : seulement ses subordonnés + lui-même
            if ($auth->isManager() && ! $auth->isAdmin()) {
                $query->where(function ($q) use ($auth) {
                    $q->where('manager_id', $auth->id)->orWhere('id', $auth->id);
                });
            }

            $employees = $query->get()->map(fn (User $u) => [
                'id'        => $u->id,
                'full_name' => $u->full_name,
                'initials'  => $u->initials,
                'avatar_url'=> $u->avatar_url,
            ])->values()->all();
        }

        [$calendar, $stats] = $this->buildMonthData($targetUser, $year, $month);

        $date = Carbon::createFromDate($year, $month, 1);

        return Inertia::render('TimeTracking/History', [
            'calendar'            => $calendar,
            'month_label'         => $date->translatedFormat('F Y'),
            'year'                => $year,
            'month'               => $month,
            'prev_month'          => $date->copy()->subMonth()->format('Y-m'),
            'next_month'          => $date->copy()->addMonth()->format('Y-m'),
            'stats'               => $stats,
            'employees'           => $employees,
            'selected_user_id'    => $targetUser->id,
            'selected_user_name'  => $targetUser->id === $auth->id ? null : $targetUser->full_name,
            'can_select_employee' => $canSelectEmployee,
        ]);
    }

    /**
     * Vue temps réel du pointage de l'équipe (admin/manager).
     */
    public function teamOverview(Request $request): Response
    {
        $auth  = $request->user();
        $today = Carbon::today()->toDateString();

        $query = User::withoutGlobalScopes()
            ->where('company_id', $auth->company_id)
            ->where('is_active', true)
            ->with('department')
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($auth->isManager() && ! $auth->isAdmin()) {
            $query->where(function ($q) use ($auth) {
                $q->where('manager_id', $auth->id)->orWhere('id', $auth->id);
            });
        }

        $users   = $query->get();
        $entries = TimeEntry::withoutGlobalScopes()
            ->whereIn('user_id', $users->pluck('id'))
            ->whereDate('date', $today)
            ->get()
            ->keyBy('user_id');

        $employees = $users->map(function (User $user) use ($entries): array {
            $entry  = $entries->get($user->id);
            $status = $entry ? $entry->status : 'absent';

            return [
                'id'             => $user->id,
                'full_name'      => $user->full_name,
                'initials'       => $user->initials,
                'avatar_url'     => $user->avatar_url,
                'department'     => $user->department?->name,
                'status'         => $status,
                'clock_in'       => $entry?->clock_in?->format('H:i'),
                'clock_out'      => $entry?->clock_out?->format('H:i'),
                'worked_minutes' => $entry?->worked_minutes ?? 0,
                'worked_label'   => $entry ? TimeEntry::minutesToLabel($entry->worked_minutes) : null,
                'progress'       => $entry ? round($entry->progress * 100) : 0,
            ];
        })->values()->all();

        $countByStatus = array_count_values(array_column($employees, 'status'));

        return Inertia::render('TimeTracking/TeamOverview', [
            'employees'   => $employees,
            'stats'       => [
                'total'    => count($employees),
                'working'  => $countByStatus['working']  ?? 0,
                'on_break' => $countByStatus['on_break'] ?? 0,
                'done'     => $countByStatus['done']     ?? 0,
                'absent'   => $countByStatus['absent']   ?? 0,
            ],
            'today_label' => Carbon::today()->translatedFormat('l j F Y'),
            'refreshed_at'=> now()->format('H:i'),
        ]);
    }

    /**
     * Rapport mensuel équipe (admin/manager).
     */
    public function report(Request $request): Response
    {
        $auth  = $request->user();
        $year  = (int) ($request->query('year',  now()->year));
        $month = (int) ($request->query('month', now()->month));
        $date  = Carbon::createFromDate($year, $month, 1);

        // Récupérer les employés de l'équipe
        $query = User::withoutGlobalScopes()
            ->where('company_id', $auth->company_id)
            ->where('is_active', true)
            ->with('department')
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($auth->isManager() && ! $auth->isAdmin()) {
            $query->where(function ($q) use ($auth) {
                $q->where('manager_id', $auth->id)->orWhere('id', $auth->id);
            });
        }

        $users = $query->get();

        // Charger toutes les entrées du mois en une seule requête
        $userIds = $users->pluck('id');
        $allEntries = TimeEntry::withoutGlobalScopes()
            ->whereIn('user_id', $userIds)
            ->forMonth($year, $month)
            ->get()
            ->groupBy('user_id');

        // Jours ouvrés du mois (lundi–vendredi, hors weekends)
        $workDaysInMonth = 0;
        for ($d = 1; $d <= $date->daysInMonth; $d++) {
            if (! Carbon::createFromDate($year, $month, $d)->isWeekend()) {
                $workDaysInMonth++;
            }
        }

        $rows = $users->map(function (User $user) use ($allEntries, $workDaysInMonth) {
            $entries = $allEntries->get($user->id, collect());

            $workedMin  = $entries->sum(fn (TimeEntry $e) => $e->worked_minutes);
            $daysWorked = $entries->filter(fn (TimeEntry $e) => $e->clock_in !== null)->count();
            $breakMin   = $entries->sum('total_break_minutes');
            $targetMin  = $daysWorked * 7 * 60;
            $overtimeMin = max(0, $workedMin - $targetMin);
            $avgMin     = $daysWorked > 0 ? (int) round($workedMin / $daysWorked) : 0;
            $absenceDays = max(0, $workDaysInMonth - $daysWorked);

            return [
                'user_id'        => $user->id,
                'full_name'      => $user->full_name,
                'initials'       => $user->initials,
                'avatar_url'     => $user->avatar_url,
                'department'     => $user->department?->name,
                'days_worked'    => $daysWorked,
                'absence_days'   => $absenceDays,
                'worked_minutes' => $workedMin,
                'worked_label'   => TimeEntry::minutesToLabel($workedMin),
                'avg_minutes'    => $avgMin,
                'avg_label'      => TimeEntry::minutesToLabel($avgMin),
                'break_minutes'  => $breakMin,
                'break_label'    => TimeEntry::minutesToLabel($breakMin),
                'overtime_minutes'=> $overtimeMin,
                'overtime_label' => TimeEntry::minutesToLabel($overtimeMin),
                'completion_pct' => $workDaysInMonth > 0
                    ? min(100, round(($daysWorked / $workDaysInMonth) * 100))
                    : 0,
            ];
        })->values()->all();

        // Totaux équipe
        $teamWorked  = array_sum(array_column($rows, 'worked_minutes'));
        $teamDays    = array_sum(array_column($rows, 'days_worked'));
        $teamOvertime= array_sum(array_column($rows, 'overtime_minutes'));

        return Inertia::render('TimeTracking/Report', [
            'rows'            => $rows,
            'month_label'     => $date->translatedFormat('F Y'),
            'year'            => $year,
            'month'           => $month,
            'prev_month'      => $date->copy()->subMonth()->format('Y-m'),
            'next_month'      => $date->copy()->addMonth()->format('Y-m'),
            'work_days_month' => $workDaysInMonth,
            'totals' => [
                'employees'       => count($rows),
                'worked_label'    => TimeEntry::minutesToLabel($teamWorked),
                'avg_per_employee'=> count($rows) > 0
                    ? TimeEntry::minutesToLabel((int) round($teamWorked / count($rows)))
                    : '0h00',
                'total_days'      => $teamDays,
                'overtime_label'  => TimeEntry::minutesToLabel($teamOvertime),
            ],
        ]);
    }

    // ── Helpers privés ────────────────────────────────────────────────────────

    /**
     * Construit le calendrier mensuel et les stats pour un utilisateur donné.
     *
     * @return array{0: array, 1: array}
     */
    private function buildMonthData(User $user, int $year, int $month): array
    {
        $date    = Carbon::createFromDate($year, $month, 1);
        $entries = TimeEntry::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->forMonth($year, $month)
            ->orderBy('date')
            ->get();

        $totalWorked   = $entries->sum(fn (TimeEntry $e) => $e->worked_minutes);
        $totalBreak    = $entries->sum('total_break_minutes');
        $daysWorked    = $entries->filter(fn (TimeEntry $e) => $e->clock_in !== null)->count();
        $targetMinutes = $daysWorked * 7 * 60;
        $overtime      = max(0, $totalWorked - $targetMinutes);

        $entriesByDate = $entries->keyBy(fn (TimeEntry $e) => $e->date->format('Y-m-d'));
        $calendar      = [];

        for ($d = 1; $d <= $date->daysInMonth; $d++) {
            $day     = Carbon::createFromDate($year, $month, $d);
            $dateStr = $day->toDateString();
            $entry   = $entriesByDate->get($dateStr);

            $calendar[] = [
                'date'           => $dateStr,
                'day_num'        => $d,
                'day_label'      => $day->translatedFormat('D'),
                'is_today'       => $day->isToday(),
                'is_weekend'     => $day->isWeekend(),
                'worked_minutes' => $entry?->worked_minutes ?? 0,
                'duration_label' => $entry?->duration_label,
                'status'         => $entry?->status ?? 'idle',
                'clock_in'       => $entry?->clock_in?->format('H:i'),
                'clock_out'      => $entry?->clock_out?->format('H:i'),
                'total_break'    => $entry?->total_break_minutes ?? 0,
            ];
        }

        $stats = [
            'total_worked_minutes' => $totalWorked,
            'total_worked_label'   => TimeEntry::minutesToLabel($totalWorked),
            'total_break_minutes'  => $totalBreak,
            'total_break_label'    => TimeEntry::minutesToLabel($totalBreak),
            'days_worked'          => $daysWorked,
            'overtime_minutes'     => $overtime,
            'overtime_label'       => TimeEntry::minutesToLabel($overtime),
        ];

        return [$calendar, $stats];
    }
}
