<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Models\Department;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanningController extends Controller
{
    /**
     * Redirige vers la vue semaine courante.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('planning.week', [
            'date' => now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d'),
        ]);
    }

    /**
     * Vue planning semaine pour une date donnée.
     * Accessible à tous les rôles (lecture), édition admin/manager uniquement.
     */
    public function week(Request $request, ?string $date = null): Response
    {
        $this->authorize('viewAny', Schedule::class);

        $weekStart = Carbon::parse($date ?? now())
            ->startOfWeek(Carbon::MONDAY);
        $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        // Jours de la semaine
        $days = collect(CarbonPeriod::create($weekStart, $weekEnd))
            ->map(fn (Carbon $d) => [
                'date'       => $d->format('Y-m-d'),
                'label'      => ucfirst($d->translatedFormat('D')),
                'day_number' => $d->day,
                'is_today'   => $d->isToday(),
                'is_weekend' => $d->isWeekend(),
            ]);

        // Employés avec leurs plannings de la semaine
        $employees = User::with([
                'department:id,name,color',
                'schedules' => fn ($q) => $q->forWeek($weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')),
            ])
            ->where('is_active', true)
            ->orderBy('last_name')
            ->get()
            ->map(fn (User $u) => [
                'id'            => $u->id,
                'name'          => $u->full_name,
                'initials'      => $u->initials,
                'department_id' => $u->department_id,
                'department'    => $u->department
                    ? ['id' => $u->department->id, 'name' => $u->department->name, 'color' => $u->department->color]
                    : null,
                'schedules'     => $u->schedules->keyBy(fn ($s) => $s->date->format('Y-m-d'))
                    ->map(fn (Schedule $s) => [
                        'id'            => $s->id,
                        'type'          => $s->type->value,
                        'label'         => $s->type->label(),
                        'emoji'         => $s->type->emoji(),
                        'hex_bg'        => $s->type->hexBg(),
                        'hex_text'      => $s->type->hexText(),
                        'start_time'    => $s->start_time ? substr($s->start_time, 0, 5) : null,
                        'end_time'      => $s->end_time   ? substr($s->end_time, 0, 5)   : null,
                        'break_minutes' => $s->break_minutes,
                        'duration'      => $s->duration_label,
                        'notes'         => $s->notes,
                    ]),
            ]);

        // Départements pour le filtre
        $departments = Department::orderBy('name')
            ->get(['id', 'name', 'color'])
            ->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'color' => $d->color]);

        // Stats de la semaine
        $allSchedules = Schedule::whereBetween('date', [$weekStart, $weekEnd])->get();
        $stats = [
            'work'     => $allSchedules->where('type.value', 'work')->count(),
            'remote'   => $allSchedules->where('type.value', 'remote')->count(),
            'leave'    => $allSchedules->where('type.value', 'leave')->count(),
            'sick'     => $allSchedules->where('type.value', 'sick')->count(),
            'off'      => $allSchedules->where('type.value', 'off')->count(),
            'training' => $allSchedules->where('type.value', 'training')->count(),
        ];

        $user = $request->user();

        return Inertia::render('Planning/WeekView', [
            'days'       => $days,
            'employees'  => $employees,
            'departments'=> $departments,
            'stats'      => $stats,
            'week_start' => $weekStart->format('Y-m-d'),
            'week_end'   => $weekEnd->format('Y-m-d'),
            'week_label' => $this->weekLabel($weekStart, $weekEnd),
            'prev_week'  => $weekStart->copy()->subWeek()->format('Y-m-d'),
            'next_week'  => $weekStart->copy()->addWeek()->format('Y-m-d'),
            'can_edit'   => $user->isAdmin() || $user->isManager(),
            'auth_user_id' => $user->id,
        ]);
    }

    /**
     * Vue planning mois.
     */
    public function month(Request $request, ?string $date = null): Response
    {
        $this->authorize('viewAny', Schedule::class);

        $current    = Carbon::parse($date ?? now())->startOfMonth();
        $monthStart = $current->copy();
        $monthEnd   = $current->copy()->endOfMonth();

        // Toutes les entrées du mois
        $schedules = Schedule::whereBetween('date', [$monthStart, $monthEnd])
            ->with('user:id,first_name,last_name,department_id')
            ->get();

        // Jours du calendrier (padding lundi)
        $firstWeekStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $lastWeekEnd    = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

        $calendarDays = collect(CarbonPeriod::create($firstWeekStart, $lastWeekEnd))
            ->map(function (Carbon $d) use ($monthStart, $monthEnd, $schedules) {
                $daySchedules = $schedules->where('date', $d->format('Y-m-d'));
                return [
                    'date'         => $d->format('Y-m-d'),
                    'day'          => $d->day,
                    'is_today'     => $d->isToday(),
                    'is_weekend'   => $d->isWeekend(),
                    'in_month'     => $d->between($monthStart, $monthEnd),
                    'stats'        => [
                        'work'     => $daySchedules->where('type.value', 'work')->count(),
                        'remote'   => $daySchedules->where('type.value', 'remote')->count(),
                        'leave'    => $daySchedules->where('type.value', 'leave')->count(),
                        'sick'     => $daySchedules->where('type.value', 'sick')->count(),
                        'off'      => $daySchedules->where('type.value', 'off')->count(),
                        'training' => $daySchedules->where('type.value', 'training')->count(),
                        'total'    => $daySchedules->count(),
                    ],
                ];
            });

        $user = $request->user();

        return Inertia::render('Planning/MonthView', [
            'calendar_days' => $calendarDays,
            'month_label'   => ucfirst($current->translatedFormat('F Y')),
            'month_start'   => $monthStart->format('Y-m-d'),
            'prev_month'    => $current->copy()->subMonth()->format('Y-m-d'),
            'next_month'    => $current->copy()->addMonth()->format('Y-m-d'),
            'can_edit'      => $user->isAdmin() || $user->isManager(),
        ]);
    }

    /**
     * Crée ou met à jour un planning (upsert sur user_id + date).
     */
    public function store(StoreScheduleRequest $request): RedirectResponse
    {
        $this->authorize('create', Schedule::class);

        $data            = $request->validated();
        $data['created_by'] = $request->user()->id;

        Schedule::updateOrCreate(
            ['user_id' => $data['user_id'], 'date' => $data['date']],
            $data
        );

        return back()->with('success', 'Planning mis à jour.');
    }

    /**
     * Met à jour un planning existant.
     */
    public function update(UpdateScheduleRequest $request, Schedule $schedule): RedirectResponse
    {
        $this->authorize('update', $schedule);

        $schedule->update($request->validated());

        return back()->with('success', 'Planning mis à jour.');
    }

    /**
     * Supprime une entrée de planning.
     */
    public function destroy(Schedule $schedule): RedirectResponse
    {
        $this->authorize('delete', $schedule);

        $schedule->delete();

        return back()->with('success', 'Entrée supprimée.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function weekLabel(Carbon $start, Carbon $end): string
    {
        if ($start->month === $end->month) {
            return ucfirst($start->translatedFormat('j')) . ' – ' .
                   $end->translatedFormat('j F Y');
        }

        return ucfirst($start->translatedFormat('j F')) . ' – ' .
               $end->translatedFormat('j F Y');
    }
}
