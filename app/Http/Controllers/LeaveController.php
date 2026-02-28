<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ReviewLeaveRequestRequest;
use App\Http\Requests\StoreLeaveRequestRequest;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

class LeaveController extends Controller
{
    public function __construct(private LeaveService $leaveService) {}

    /**
     * Liste des demandes de congé selon le rôle de l'utilisateur.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', LeaveRequest::class);

        $user   = $request->user();
        $status = $request->query('status', 'all');

        $query = LeaveRequest::with([
                'user:id,first_name,last_name,avatar_path',
                'leaveType:id,name,color,icon',
            ])
            ->orderByDesc('created_at');

        // Filtrage selon le rôle
        if ($user->isEmployee()) {
            $query->where('user_id', $user->id);
        }
        // Admin et managers voient toutes les demandes de la company (global scope)

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $requests = $query->paginate(20)->through(fn (LeaveRequest $lr) => [
            'id'               => $lr->id,
            'user'             => $lr->user ? [
                'id'       => $lr->user->id,
                'name'     => $lr->user->full_name,
                'initials' => $lr->user->initials,
                'avatar'   => $lr->user->avatar_path,
            ] : null,
            'leave_type'       => $lr->leaveType ? [
                'name'  => $lr->leaveType->name,
                'color' => $lr->leaveType->color,
                'icon'  => $lr->leaveType->icon,
            ] : null,
            'period_label'     => $lr->period_label,
            'days_count'       => (float) $lr->days_count,
            'status'           => $lr->status->value,
            'status_label'     => $lr->status->label(),
            'status_color'     => $lr->status->color(),
            'can_cancel'       => $user->can('cancel', $lr),
            'can_review'       => $user->can('review', $lr),
            'created_at'       => $lr->created_at->format('d/m/Y'),
        ]);

        // Compteurs par statut pour les onglets
        $counts = $this->buildStatusCounts($user);

        return Inertia::render('Leaves/Index', [
            'requests'      => $requests,
            'counts'        => $counts,
            'current_status'=> $status,
            'can_create'    => $user->can('create', LeaveRequest::class),
            'is_reviewer'   => $user->isAdmin() || $user->isManager(),
        ]);
    }

    /**
     * Formulaire de demande de congé.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', LeaveRequest::class);

        $user = $request->user();

        $leaveTypes = LeaveType::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (LeaveType $lt) => [
                'id'                  => $lt->id,
                'name'                => $lt->name,
                'color'               => $lt->color,
                'icon'                => $lt->icon,
                'requires_approval'   => $lt->requires_approval,
                'requires_attachment' => $lt->requires_attachment,
                'is_paid'             => $lt->is_paid,
                'needs_balance'       => $lt->acquisition_type->value !== 'none',
            ]);

        // Soldes de l'employé pour chaque type
        $balances = LeaveBalance::where('user_id', $user->id)
            ->where('year', now()->year)
            ->get()
            ->keyBy('leave_type_id')
            ->map(fn (LeaveBalance $b) => [
                'allocated'         => (float) $b->allocated,
                'used'              => (float) $b->used,
                'pending'           => (float) $b->pending,
                'effective_remaining' => (float) $b->effective_remaining,
            ]);

        return Inertia::render('Leaves/Request', [
            'leave_types' => $leaveTypes,
            'balances'    => $balances,
        ]);
    }

    /**
     * Enregistre une nouvelle demande de congé.
     */
    public function store(StoreLeaveRequestRequest $request): RedirectResponse
    {
        $this->authorize('create', LeaveRequest::class);

        $data = $request->validated();
        $user = $request->user();
        $days = $this->leaveService->calculateWorkingDays(
            $data['start_date'],
            $data['end_date'],
            $data['start_half'] ?? null,
            $data['end_half']   ?? null,
            $user->company_id,
        );

        if ($days <= 0) {
            return back()->withErrors(['end_date' => 'La période sélectionnée ne contient aucun jour ouvré.']);
        }

        // Vérification du solde
        $balanceError = $this->leaveService->checkBalance($user, (int) $data['leave_type_id'], $days);
        if ($balanceError) {
            return back()->withErrors(['leave_type_id' => $balanceError]);
        }

        $this->leaveService->create($user, $data);

        return redirect()->route('leaves.index')
            ->with('success', 'Votre demande de congé a été soumise avec succès.');
    }

    /**
     * Détail d'une demande (pour révision ou consultation).
     */
    public function show(LeaveRequest $leave): Response
    {
        $this->authorize('view', $leave);

        $leave->load(['user', 'leaveType', 'reviewer']);

        // Solde de l'employé pour ce type de congé
        $balance = LeaveBalance::where('user_id', $leave->user_id)
            ->where('leave_type_id', $leave->leave_type_id)
            ->where('year', now()->year)
            ->first();

        $user = auth()->user();

        return Inertia::render('Leaves/Approve', [
            'leave_request' => [
                'id'               => $leave->id,
                'status'           => $leave->status->value,
                'status_label'     => $leave->status->label(),
                'status_color'     => $leave->status->color(),
                'days_count'       => (float) $leave->days_count,
                'period_label'     => $leave->period_label,
                'start_date'       => $leave->start_date->format('Y-m-d'),
                'end_date'         => $leave->end_date->format('Y-m-d'),
                'start_half'       => $leave->start_half,
                'end_half'         => $leave->end_half,
                'employee_comment'          => $leave->employee_comment,
                'attachment_url'            => $leave->attachment_path
                    ? route('leaves.download-attachment', $leave->id)
                    : null,
                'attachment_original_name'  => $leave->attachment_original_name,
                'reviewer_comment' => $leave->reviewer_comment,
                'reviewed_at'      => $leave->reviewed_at?->format('d/m/Y H:i'),
                'cancelled_at'     => $leave->cancelled_at?->format('d/m/Y H:i'),
                'created_at'       => $leave->created_at->format('d/m/Y H:i'),
                'employee'         => [
                    'id'       => $leave->user->id,
                    'name'     => $leave->user->full_name,
                    'initials' => $leave->user->initials,
                    'avatar'   => $leave->user->avatar_path,
                ],
                'leave_type'       => [
                    'name'  => $leave->leaveType->name,
                    'color' => $leave->leaveType->color,
                    'icon'  => $leave->leaveType->icon,
                ],
                'reviewer'         => $leave->reviewer ? [
                    'name' => $leave->reviewer->full_name,
                ] : null,
            ],
            'balance'    => $balance ? [
                'allocated'          => (float) $balance->allocated,
                'used'               => (float) $balance->used,
                'pending'            => (float) $balance->pending,
                'effective_remaining'=> (float) $balance->effective_remaining,
            ] : null,
            'can_review' => $user->can('review', $leave),
            'can_cancel' => $user->can('cancel', $leave),
        ]);
    }

    /**
     * Approuve une demande de congé.
     */
    public function approve(ReviewLeaveRequestRequest $request, LeaveRequest $leave): RedirectResponse
    {
        $this->authorize('review', $leave);

        if (! $leave->isPending()) {
            return back()->withErrors(['status' => 'Cette demande ne peut plus être approuvée.']);
        }

        $this->leaveService->approve($leave, $request->user(), $request->input('comment'));

        return redirect()->route('leaves.index')
            ->with('success', "La demande de {$leave->user->full_name} a été approuvée.");
    }

    /**
     * Rejette une demande de congé.
     */
    public function reject(ReviewLeaveRequestRequest $request, LeaveRequest $leave): RedirectResponse
    {
        $this->authorize('review', $leave);

        if (! $leave->isPending()) {
            return back()->withErrors(['status' => 'Cette demande ne peut plus être refusée.']);
        }

        $comment = $request->input('comment', '');

        $this->leaveService->reject($leave, $request->user(), $comment);

        return redirect()->route('leaves.index')
            ->with('success', "La demande de {$leave->user->full_name} a été refusée.");
    }

    /**
     * Annule une demande de congé.
     */
    public function cancel(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $this->authorize('cancel', $leave);

        $this->leaveService->cancel($leave);

        return back()->with('success', 'La demande de congé a été annulée.');
    }

    /**
     * Téléchargement sécurisé de la pièce jointe d'une demande de congé.
     */
    public function downloadAttachment(LeaveRequest $leave): StreamedResponse
    {
        $this->authorize('view', $leave);

        if (! $leave->attachment_path || ! Storage::disk('local')->exists($leave->attachment_path)) {
            abort(404, 'Pièce jointe introuvable.');
        }

        return Storage::disk('local')->download(
            $leave->attachment_path,
            $leave->attachment_original_name ?? basename($leave->attachment_path),
        );
    }

    /**
     * Soldes de congés.
     */
    public function balances(Request $request): Response
    {
        $user = $request->user();

        if ($user->isAdmin() || $user->isManager()) {
            // Vue d'ensemble : tous les employés
            $employees = User::where('is_active', true)
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name']);

            $leaveTypes = LeaveType::where('is_active', true)
                ->whereNot('acquisition_type', 'none')
                ->orderBy('sort_order')
                ->get(['id', 'name', 'color', 'icon', 'days_per_year']);

            $balances = LeaveBalance::where('year', now()->year)
                ->get()
                ->groupBy('user_id')
                ->map(fn ($userBalances) => $userBalances->keyBy('leave_type_id'));

            return Inertia::render('Leaves/Balances', [
                'employees'   => $employees->map(fn (User $u) => [
                    'id'       => $u->id,
                    'name'     => $u->full_name,
                    'initials' => $u->initials,
                ]),
                'leave_types' => $leaveTypes->map(fn (LeaveType $lt) => [
                    'id'           => $lt->id,
                    'name'         => $lt->name,
                    'color'        => $lt->color,
                    'icon'         => $lt->icon,
                    'days_per_year'=> (float) $lt->days_per_year,
                ]),
                'balances'    => $balances->map(fn ($userBals) => $userBals->map(fn (LeaveBalance $b) => [
                    'allocated'          => (float) $b->allocated,
                    'used'               => (float) $b->used,
                    'pending'            => (float) $b->pending,
                    'effective_remaining'=> (float) $b->effective_remaining,
                    'used_percentage'    => (float) $b->used_percentage,
                ])),
                'is_reviewer' => true,
                'can_adjust'  => $user->isAdmin(),
                'year'        => now()->year,
            ]);
        }

        // Vue employé : ses propres soldes
        $leaveTypes = LeaveType::where('is_active', true)
            ->whereNot('acquisition_type', 'none')
            ->orderBy('sort_order')
            ->get();

        $balances = LeaveBalance::where('user_id', $user->id)
            ->where('year', now()->year)
            ->get()
            ->keyBy('leave_type_id');

        return Inertia::render('Leaves/Balances', [
            'leave_types' => $leaveTypes->map(fn (LeaveType $lt) => [
                'id'           => $lt->id,
                'name'         => $lt->name,
                'color'        => $lt->color,
                'icon'         => $lt->icon,
                'days_per_year'=> (float) $lt->days_per_year,
                'balance'      => isset($balances[$lt->id]) ? [
                    'allocated'          => (float) $balances[$lt->id]->allocated,
                    'used'               => (float) $balances[$lt->id]->used,
                    'pending'            => (float) $balances[$lt->id]->pending,
                    'effective_remaining'=> (float) $balances[$lt->id]->effective_remaining,
                    'used_percentage'    => (float) $balances[$lt->id]->used_percentage,
                ] : null,
            ]),
            'is_reviewer' => false,
            'can_adjust'  => false,
            'year'        => now()->year,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers privés
    // -------------------------------------------------------------------------

    private function buildStatusCounts(User $user): array
    {
        $base = LeaveRequest::query();

        if ($user->isEmployee()) {
            $base->where('user_id', $user->id);
        }

        return [
            'all'       => (clone $base)->count(),
            'pending'   => (clone $base)->where('status', 'pending')->count(),
            'approved'  => (clone $base)->where('status', 'approved')->count(),
            'rejected'  => (clone $base)->where('status', 'rejected')->count(),
            'cancelled' => (clone $base)->where('status', 'cancelled')->count(),
        ];
    }
}
