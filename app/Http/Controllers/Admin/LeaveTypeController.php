<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLeaveTypeRequest;
use App\Http\Requests\Admin\UpdateLeaveTypeRequest;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LeaveTypeController extends Controller
{
    /**
     * Liste tous les types de congés, ordonnés par sort_order.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', LeaveType::class);

        $leaveTypes = LeaveType::withCount('leaveRequests')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (LeaveType $lt) => [
                'id'                   => $lt->id,
                'name'                 => $lt->name,
                'slug'                 => $lt->slug,
                'color'                => $lt->color,
                'icon'                 => $lt->icon,
                'days_per_year'        => (float) $lt->days_per_year,
                'requires_approval'    => $lt->requires_approval,
                'is_paid'              => $lt->is_paid,
                'is_active'            => $lt->is_active,
                'acquisition_type'     => $lt->acquisition_type->value,
                'acquisition_label'    => $lt->acquisition_type->label(),
                'max_consecutive_days' => $lt->max_consecutive_days,
                'notice_days'          => $lt->notice_days,
                'requires_attachment'  => $lt->requires_attachment,
                'sort_order'           => $lt->sort_order,
                'leave_requests_count' => $lt->leave_requests_count,
            ]);

        return Inertia::render('Settings/LeaveTypes', [
            'leaveTypes' => $leaveTypes,
        ]);
    }

    /**
     * Crée un nouveau type de congé.
     */
    public function store(StoreLeaveTypeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Initialise sort_order à la fin de la liste
        $data['sort_order'] = LeaveType::max('sort_order') + 1;

        LeaveType::create($data);

        return back()->with('success', 'Type de congé créé avec succès.');
    }

    /**
     * Met à jour un type de congé.
     */
    public function update(UpdateLeaveTypeRequest $request, LeaveType $leaveType): RedirectResponse
    {
        $this->authorize('update', $leaveType);

        $leaveType->update($request->validated());

        return back()->with('success', 'Type de congé mis à jour.');
    }

    /**
     * Active ou désactive un type de congé.
     */
    public function toggle(LeaveType $leaveType): RedirectResponse
    {
        $this->authorize('update', $leaveType);

        $leaveType->update(['is_active' => ! $leaveType->is_active]);

        $label = $leaveType->is_active ? 'activé' : 'désactivé';

        return back()->with('success', "Type « {$leaveType->name} » {$label}.");
    }

    /**
     * Réordonne les types de congés.
     * Reçoit un tableau d'IDs dans l'ordre souhaité.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $this->authorize('create', LeaveType::class); // admin check

        $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        DB::transaction(function () use ($request): void {
            foreach ($request->ids as $position => $id) {
                LeaveType::where('id', $id)->update(['sort_order' => $position + 1]);
            }
        });

        return back()->with('success', 'Ordre mis à jour.');
    }

    /**
     * Supprime un type de congé.
     * Bloqué s'il existe des demandes de congé liées.
     */
    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        $this->authorize('delete', $leaveType);

        if ($leaveType->leaveRequests()->exists()) {
            return back()->with(
                'error',
                "Impossible de supprimer « {$leaveType->name} » : des demandes de congé y sont rattachées. Désactivez-le à la place."
            );
        }

        $name = $leaveType->name;
        $leaveType->delete();

        return back()->with('success', "Type « {$name} » supprimé.");
    }
}
