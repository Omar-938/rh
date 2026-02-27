<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDepartmentRequest;
use App\Http\Requests\Admin\UpdateDepartmentRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    /**
     * Liste tous les départements de la company avec stats.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Department::class);

        $departments = Department::with([
                'manager:id,first_name,last_name',
                'users:id,department_id,first_name,last_name',
            ])
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (Department $d) => [
                'id'          => $d->id,
                'name'        => $d->name,
                'color'       => $d->color,
                'description' => $d->description,
                'users_count' => $d->users_count,
                'manager'     => $d->manager ? [
                    'id'       => $d->manager->id,
                    'name'     => $d->manager->full_name,
                    'initials' => $d->manager->initials,
                ] : null,
                'members'     => $d->users->take(5)->map(fn (User $u) => [
                    'id'       => $u->id,
                    'initials' => $u->initials,
                    'name'     => $u->full_name,
                ])->values(),
            ]);

        // Managers et admins disponibles comme responsables de département
        $managers = User::whereIn('role', ['admin', 'manager'])
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (User $u) => [
                'id'   => $u->id,
                'name' => $u->full_name,
            ]);

        return Inertia::render('Settings/Departments', [
            'departments' => $departments,
            'managers'    => $managers,
        ]);
    }

    /**
     * Crée un nouveau département.
     */
    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        Department::create($request->validated());

        return back()->with('success', 'Département créé avec succès.');
    }

    /**
     * Met à jour un département existant.
     */
    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $this->authorize('update', $department);

        $department->update($request->validated());

        return back()->with('success', 'Département mis à jour avec succès.');
    }

    /**
     * Supprime un département et désaffecte ses membres.
     */
    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        // Désaffecter les employés du département avant suppression
        $department->users()->update(['department_id' => null]);

        $department->delete();

        return back()->with('success', 'Département supprimé. Les employés ont été désaffectés.');
    }
}
