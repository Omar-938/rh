<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PayslipStatus;
use App\Models\Payslip;
use App\Models\User;
use App\Services\PayslipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayslipController extends Controller
{
    public function __construct(private PayslipService $payslipService) {}

    /**
     * Liste des bulletins avec filtres.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $periodYear  = (int) ($request->query('year',  now()->year));
        $periodMonth = (int) ($request->query('month', 0)); // 0 = tous les mois
        $userId      = $request->query('user_id', '');
        $status      = $request->query('status', 'all');

        // ── Query de base ──────────────────────────────────────────────────────
        $baseQuery = Payslip::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->whereNull('deleted_at');

        if ($user->isAdmin() || $user->isManager()) {
            // Admin/manager : tous les bulletins de la société
        } else {
            // Employé : seulement ses bulletins distribués
            $baseQuery->where('user_id', $user->id)
                      ->where('status', 'distributed');
        }

        // ── Compteurs ──────────────────────────────────────────────────────────
        $counts = [
            'all'         => (clone $baseQuery)->count(),
            'draft'       => (clone $baseQuery)->where('status', 'draft')->count(),
            'distributed' => (clone $baseQuery)->where('status', 'distributed')->count(),
            'unread'      => (clone $baseQuery)->where('status', 'distributed')->where('is_viewed', false)->count(),
        ];

        // ── Filtres ────────────────────────────────────────────────────────────
        $query = clone $baseQuery;

        if ($periodYear > 0) {
            $query->where('period_year', $periodYear);
        }
        if ($periodMonth > 0) {
            $query->where('period_month', $periodMonth);
        }
        if ($userId !== '' && ($user->isAdmin() || $user->isManager())) {
            $query->where('user_id', (int) $userId);
        }
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // ── Résultats ─────────────────────────────────────────────────────────
        $payslips = $query
            ->with(['user', 'uploadedBy'])
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->orderBy('user_id')
            ->paginate(30)
            ->withQueryString();

        // ── Employés (admin/manager) ───────────────────────────────────────────
        $employees = [];
        if ($user->isAdmin() || $user->isManager()) {
            $employees = User::withoutGlobalScopes()
                ->where('company_id', $user->company_id)
                ->where('is_active', true)
                ->orderBy('last_name')
                ->get()
                ->map(fn (User $u) => [
                    'id'   => $u->id,
                    'name' => $u->full_name,
                ])
                ->toArray();
        }

        // Années disponibles pour le filtre
        $availableYears = Payslip::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->whereNull('deleted_at')
            ->selectRaw('DISTINCT period_year')
            ->orderByDesc('period_year')
            ->pluck('period_year')
            ->toArray();

        if (empty($availableYears)) {
            $availableYears = [now()->year];
        }

        return Inertia::render('Payslips/Index', [
            'payslips'       => $payslips->through(fn (Payslip $p) => $this->payslipService->serialize($p)),
            'employees'      => $employees,
            'counts'         => $counts,
            'available_years'=> $availableYears,
            'is_uploader'    => $user->isAdmin() || $user->isManager(),
            'filters'        => [
                'year'    => $periodYear,
                'month'   => $periodMonth,
                'user_id' => $userId,
                'status'  => $status,
            ],
        ]);
    }

    /**
     * Page d'upload en lot.
     */
    public function create(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $employees = User::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (User $u) => [
                'id'        => $u->id,
                'name'      => $u->full_name,
                'initials'  => $u->initials,
                'last_name' => $u->last_name,
                'first_name'=> $u->first_name,
            ])
            ->toArray();

        return Inertia::render('Payslips/Upload', [
            'employees'      => $employees,
            'current_year'   => now()->year,
            'current_month'  => now()->month,
        ]);
    }

    /**
     * Upload en lot de bulletins de paie.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $request->validate([
            'files'                  => ['required', 'array', 'min:1', 'max:200'],
            'files.*'                => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'entries'                => ['required', 'array'],
            'entries.*.user_id'      => ['nullable', 'integer', 'exists:users,id'],
            'entries.*.period_year'  => ['required', 'integer', 'min:2000', 'max:2099'],
            'entries.*.period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'entries.*.notes'        => ['nullable', 'string', 'max:1000'],
        ]);

        $files   = $request->file('files');
        $entries = $request->input('entries', []);
        $count   = 0;

        foreach ($files as $idx => $file) {
            $entry = $entries[$idx] ?? [];

            // Vérifier que l'employé appartient à la société
            if (! empty($entry['user_id'])) {
                User::withoutGlobalScopes()
                    ->where('id', $entry['user_id'])
                    ->where('company_id', $user->company_id)
                    ->firstOrFail();
            }

            $this->payslipService->upload($file, $user, $entry);
            $count++;
        }

        return redirect()->route('payslips.index')->with('flash', [
            'type'    => 'success',
            'message' => "{$count} bulletin" . ($count > 1 ? 's' : '') . " importé" . ($count > 1 ? 's' : '') . " avec succès.",
        ]);
    }

    /**
     * Distribution d'un bulletin (draft → distributed + notification employé).
     */
    public function distribute(Request $request, Payslip $payslip): RedirectResponse
    {
        $user = $request->user();
        abort_unless($payslip->company_id === $user->company_id, 403);
        abort_unless($user->isAdmin() || $user->isManager(), 403);
        abort_unless($payslip->isDraft(), 422);

        $this->payslipService->distribute($payslip, $user);

        $name   = $payslip->user?->full_name ?? 'employé non assigné';
        $period = $payslip->period_label;

        return back()->with('success', "Bulletin de « {$name} » ({$period}) distribué avec succès.");
    }

    /**
     * Distribution en lot (liste d'IDs de brouillons).
     */
    public function distributeMany(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $request->validate([
            'ids'   => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['required', 'integer'],
        ]);

        $payslips = Payslip::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->whereIn('id', $request->input('ids'))
            ->where('status', PayslipStatus::Draft->value)
            ->whereNull('deleted_at')
            ->with('user')
            ->get();

        foreach ($payslips as $payslip) {
            $this->payslipService->distribute($payslip, $user);
        }

        $count = $payslips->count();
        $msg   = "{$count} bulletin" . ($count > 1 ? 's' : '') . " distribué" . ($count > 1 ? 's' : '') . " avec succès.";

        return back()->with('success', $msg);
    }

    /**
     * Téléchargement sécurisé d'un bulletin.
     */
    public function download(Request $request, Payslip $payslip): StreamedResponse
    {
        $user = $request->user();
        abort_unless($this->payslipService->canAccess($payslip, $user), 403);

        return $this->payslipService->download($payslip, $user);
    }

    /**
     * Suppression d'un bulletin (admin/manager uniquement).
     */
    public function destroy(Request $request, Payslip $payslip): RedirectResponse
    {
        $user = $request->user();
        abort_unless($payslip->company_id === $user->company_id, 403);
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $this->payslipService->deleteFile($payslip);
        $payslip->delete();

        return back()->with('flash', [
            'type'    => 'success',
            'message' => "Bulletin de « {$payslip->user?->full_name} » ({$payslip->period_label}) supprimé.",
        ]);
    }
}
