<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OvertimeStatus;
use App\Http\Requests\StoreOvertimeRequest;
use App\Models\OvertimeEntry;
use App\Services\OvertimeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OvertimeController extends Controller
{
    public function __construct(private OvertimeService $overtimeService) {}

    /**
     * Liste des heures supplémentaires.
     * Employee → les siennes. Admin/Manager → toute l'équipe.
     */
    public function index(Request $request): Response
    {
        $user   = $request->user();
        $status = $request->query('status', 'all');

        $query = OvertimeEntry::withoutGlobalScopes()
            ->with(['user', 'reviewer'])
            ->where('company_id', $user->company_id)
            ->orderByDesc('date');

        // Employee voit seulement ses propres entrées
        if (! $user->isAdmin() && ! $user->isManager()) {
            $query->where('user_id', $user->id);
        }

        if ($status !== 'all' && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        $entries = $query->paginate(20)->withQueryString();

        // Compteurs par statut
        $baseQuery = OvertimeEntry::withoutGlobalScopes()
            ->where('company_id', $user->company_id);
        if (! $user->isAdmin() && ! $user->isManager()) {
            $baseQuery->where('user_id', $user->id);
        }

        $counts = $baseQuery->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $annualStats = $this->overtimeService->getAnnualStats($user);

        return Inertia::render('Overtime/Index', [
            'entries'      => $entries->through(fn (OvertimeEntry $e) => $this->overtimeService->serialize($e)),
            'status_tab'   => $status,
            'counts'       => [
                'all'      => $counts->sum(),
                'pending'  => $counts->get('pending',  0),
                'approved' => $counts->get('approved', 0),
                'rejected' => $counts->get('rejected', 0),
            ],
            'annual_stats'      => $annualStats,
            'is_reviewer'       => $user->isAdmin() || $user->isManager(),
        ]);
    }

    /**
     * Formulaire de déclaration.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Overtime/Declare', [
            'default_date' => now()->toDateString(),
        ]);
    }

    /**
     * Enregistrer la déclaration.
     */
    public function store(StoreOvertimeRequest $request): RedirectResponse
    {
        $entry = $this->overtimeService->declare($request->user(), $request->validated());

        return redirect()->route('overtime.index')
            ->with('flash', [
                'type'    => 'success',
                'message' => 'Déclaration de ' . $entry->hours_label . ' enregistrée et en attente de validation.',
            ]);
    }

    /**
     * Détail d'une entrée.
     */
    public function show(Request $request, OvertimeEntry $overtime): Response
    {
        $user = $request->user();

        // Vérification d'accès : owner ou admin/manager de la même société
        abort_unless(
            $overtime->company_id === $user->company_id &&
            ($overtime->user_id === $user->id || $user->isAdmin() || $user->isManager()),
            403
        );

        $overtime->load(['user', 'reviewer', 'timeEntry']);

        return Inertia::render('Overtime/Show', [
            'entry'       => $this->overtimeService->serialize($overtime),
            'is_reviewer' => $user->isAdmin() || $user->isManager(),
            'is_pending'  => $overtime->isPending(),
        ]);
    }

    /**
     * Approuver (step 20 — accessible ici pour ne pas bloquer le workflow).
     */
    public function approve(Request $request, OvertimeEntry $overtime): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isManager(), 403);

        $request->validate(['comment' => ['nullable', 'string', 'max:1000']]);

        $this->overtimeService->approve($overtime, $request->user(), $request->input('comment'));

        return back()->with('flash', [
            'type'    => 'success',
            'message' => 'Heures supplémentaires approuvées.',
        ]);
    }

    /**
     * Rejeter (step 20).
     */
    public function reject(Request $request, OvertimeEntry $overtime): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isManager(), 403);

        $request->validate(['comment' => ['nullable', 'string', 'max:1000']]);

        $this->overtimeService->reject($overtime, $request->user(), $request->input('comment'));

        return back()->with('flash', [
            'type'    => 'success',
            'message' => 'Heures supplémentaires refusées.',
        ]);
    }
}
