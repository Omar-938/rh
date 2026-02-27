<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Services\HolidayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class HolidayController extends Controller
{
    public function __construct(private HolidayService $service) {}

    /**
     * Liste des jours fériés avec filtre par année.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $year = (int) $request->query('year', now()->year);

        $holidays = Holiday::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->whereYear('date', $year)
            ->orderBy('date')
            ->get()
            ->map(fn (Holiday $h) => [
                'id'           => $h->id,
                'name'         => $h->name,
                'date'         => $h->date->format('Y-m-d'),
                'date_label'   => $h->date->translatedFormat('l j F'),
                'month'        => $h->date->translatedFormat('F'),
                'month_num'    => (int) $h->date->format('n'),
                'day_of_week'  => $h->date->translatedFormat('l'),
                'is_recurring' => $h->is_recurring,
            ]);

        // Comptages par année pour afficher les badges
        // strftime('%Y', date) fonctionne sur SQLite et MySQL (via YEAR() émulé par Eloquent)
        $yearlyCounts = Holiday::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->get(['date'])
            ->groupBy(fn ($h) => $h->date->year)
            ->map(fn ($group) => $group->count())
            ->toArray();

        return Inertia::render('Settings/Holidays', [
            'holidays'     => $holidays,
            'year'         => $year,
            'years'        => range(now()->year - 1, now()->year + 2),
            'yearlyCounts' => $yearlyCounts,
        ]);
    }

    /**
     * Ajoute un jour férié personnalisé.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'date'         => ['required', 'date'],
            'is_recurring' => ['boolean'],
        ]);

        try {
            Holiday::create([
                'company_id'   => $user->company_id,
                'name'         => $data['name'],
                'date'         => $data['date'],
                'is_recurring' => $data['is_recurring'] ?? false,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->withErrors(['date' => 'Un jour férié existe déjà à cette date.']);
            }
            throw $e;
        }

        return back()->with('success', "Jour férié « {$data['name']} » ajouté.");
    }

    /**
     * Modifie un jour férié.
     */
    public function update(Request $request, Holiday $holiday): RedirectResponse
    {
        abort_unless($holiday->company_id === $request->user()->company_id, 403);

        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'date'         => ['required', 'date'],
            'is_recurring' => ['boolean'],
        ]);

        try {
            $holiday->update($data);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->withErrors(['date' => 'Un jour férié existe déjà à cette date.']);
            }
            throw $e;
        }

        return back()->with('success', 'Jour férié mis à jour.');
    }

    /**
     * Supprime un jour férié.
     */
    public function destroy(Request $request, Holiday $holiday): RedirectResponse
    {
        abort_unless($holiday->company_id === $request->user()->company_id, 403);

        $name = $holiday->name;
        $holiday->delete();

        return back()->with('success', "« {$name} » supprimé.");
    }

    /**
     * Importe les 11 jours fériés français pour une année donnée.
     * Les jours déjà présents sont ignorés (pas d'écrasement).
     */
    public function importFrench(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        $year     = (int) $data['year'];
        $holidays = HolidayService::frenchHolidays($year);
        $inserted = 0;
        $skipped  = 0;

        DB::transaction(function () use ($holidays, $user, &$inserted, &$skipped): void {
            foreach ($holidays as $h) {
                $exists = Holiday::withoutGlobalScopes()
                    ->where('company_id', $user->company_id)
                    ->whereDate('date', $h['date'])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                Holiday::create([
                    'company_id'   => $user->company_id,
                    'name'         => $h['name'],
                    'date'         => $h['date'],
                    'is_recurring' => $h['is_recurring'],
                ]);

                $inserted++;
            }
        });

        $msg = $inserted > 0
            ? "{$inserted} jour(s) férié(s) importé(s) pour {$year}."
            : "Tous les jours fériés {$year} sont déjà présents.";

        if ($skipped > 0 && $inserted > 0) {
            $msg .= " ({$skipped} déjà présent(s).)";
        }

        return back()->with('success', $msg);
    }
}
