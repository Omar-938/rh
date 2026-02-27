<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ContractType;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    // ─── Liste ──────────────────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $search     = $request->query('search', '');
        $roleFilter = $request->query('role', 'all');
        $deptFilter = (int) $request->query('department', 0);
        $statusFilter = $request->query('status', 'active');

        $query = User::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->where('id', '!=', $user->id)  // exclude self
            ->with('department')
            ->withTrashed(false);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        if ($roleFilter !== 'all') {
            $query->where('role', $roleFilter);
        }

        if ($deptFilter > 0) {
            $query->where('department_id', $deptFilter);
        }

        match ($statusFilter) {
            'active'   => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            default    => null,
        };

        $employees = $query->orderBy('last_name')->orderBy('first_name')
            ->get()
            ->map(fn (User $u) => $this->serializeEmployee($u));

        $departments = Department::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $stats = [
            'total'    => User::withoutGlobalScopes()->where('company_id', $user->company_id)->where('id', '!=', $user->id)->count(),
            'active'   => User::withoutGlobalScopes()->where('company_id', $user->company_id)->where('id', '!=', $user->id)->where('is_active', true)->count(),
            'inactive' => User::withoutGlobalScopes()->where('company_id', $user->company_id)->where('id', '!=', $user->id)->where('is_active', false)->count(),
        ];

        return Inertia::render('Employees/Index', [
            'employees'   => $employees,
            'departments' => $departments,
            'stats'       => $stats,
            'filters'     => [
                'search'     => $search,
                'role'       => $roleFilter,
                'department' => $deptFilter,
                'status'     => $statusFilter,
            ],
            'contract_types' => array_map(fn (ContractType $ct) => [
                'value' => $ct->value,
                'label' => $ct->label(),
            ], ContractType::cases()),
            'roles' => array_map(fn (UserRole $r) => [
                'value' => $r->value,
                'label' => $r->label(),
            ], UserRole::cases()),
        ]);
    }

    // ─── Fiche employé ──────────────────────────────────────────────────────────

    public function show(Request $request, User $employee): Response
    {
        $user = $request->user();
        abort_unless($employee->company_id === $user->company_id, 403);
        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $employee->load('department', 'manager');

        $departments = Department::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $managers = User::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->where('id', '!=', $employee->id)
            ->whereIn('role', ['admin', 'manager'])
            ->orderBy('last_name')
            ->get()
            ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->full_name]);

        return Inertia::render('Employees/Show', [
            'employee'       => $this->serializeEmployeeFull($employee),
            'departments'    => $departments,
            'managers'       => $managers,
            'contract_types' => array_map(fn (ContractType $ct) => [
                'value' => $ct->value, 'label' => $ct->label(),
            ], ContractType::cases()),
            'roles' => array_map(fn (UserRole $r) => [
                'value' => $r->value, 'label' => $r->label(),
            ], UserRole::cases()),
        ]);
    }

    // ─── Créer ──────────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin(), 403);

        $data = $request->validate([
            'first_name'      => ['required', 'string', 'max:255'],
            'last_name'       => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'max:255'],
            'role'            => ['required', 'string', 'in:admin,manager,employee'],
            'department_id'   => ['nullable', 'integer', 'exists:departments,id'],
            'contract_type'   => ['required', 'string', 'in:cdi,cdd,interim,stage,alternance'],
            'hire_date'       => ['nullable', 'date'],
            'weekly_hours'    => ['nullable', 'numeric', 'min:1', 'max:60'],
            'phone'           => ['nullable', 'string', 'max:20'],
            'employee_id'     => ['nullable', 'string', 'max:50'],
        ]);

        // Vérifie unicité email dans l'entreprise
        $exists = User::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->where('email', $data['email'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['email' => 'Cette adresse email est déjà utilisée dans votre entreprise.']);
        }

        $tempPassword = Str::password(16);

        $employee = User::create([
            'company_id'       => $user->company_id,
            'first_name'       => $data['first_name'],
            'last_name'        => $data['last_name'],
            'email'            => $data['email'],
            'password'         => Hash::make($tempPassword),
            'role'             => $data['role'],
            'department_id'    => $data['department_id'] ?? null,
            'contract_type'    => $data['contract_type'],
            'hire_date'        => $data['hire_date'] ?? null,
            'weekly_hours'     => $data['weekly_hours'] ?? 35,
            'phone'            => $data['phone'] ?? null,
            'employee_id'      => $data['employee_id'] ?? null,
            'email_verified_at'=> now(),
            'is_active'        => true,
        ]);

        // Envoie un email pour définir le mot de passe
        Password::sendResetLink(['email' => $employee->email]);

        return redirect()
            ->route('employees.show', $employee->id)
            ->with('success', "{$employee->full_name} ajouté(e). Un email d'activation a été envoyé.");
    }

    // ─── Modifier ───────────────────────────────────────────────────────────────

    public function update(Request $request, User $employee): RedirectResponse
    {
        $user = $request->user();
        abort_unless($employee->company_id === $user->company_id, 403);
        abort_unless($user->isAdmin(), 403);

        $data = $request->validate([
            'first_name'              => ['sometimes', 'string', 'max:255'],
            'last_name'               => ['sometimes', 'string', 'max:255'],
            'email'                   => ['sometimes', 'email', 'max:255'],
            'role'                    => ['sometimes', 'string', 'in:admin,manager,employee'],
            'department_id'           => ['sometimes', 'nullable', 'integer', 'exists:departments,id'],
            'manager_id'              => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'contract_type'           => ['sometimes', 'string', 'in:cdi,cdd,interim,stage,alternance'],
            'hire_date'               => ['sometimes', 'nullable', 'date'],
            'contract_end_date'       => ['sometimes', 'nullable', 'date'],
            'trial_end_date'          => ['sometimes', 'nullable', 'date'],
            'weekly_hours'            => ['sometimes', 'numeric', 'min:1', 'max:60'],
            'phone'                   => ['sometimes', 'nullable', 'string', 'max:20'],
            'employee_id'             => ['sometimes', 'nullable', 'string', 'max:50'],
            'birth_date'              => ['sometimes', 'nullable', 'date'],
            'address'                 => ['sometimes', 'nullable', 'string', 'max:1000'],
            'city'                    => ['sometimes', 'nullable', 'string', 'max:255'],
            'postal_code'             => ['sometimes', 'nullable', 'string', 'max:10'],
            'emergency_contact_name'  => ['sometimes', 'nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        // Vérifie unicité email si changé
        if (isset($data['email']) && $data['email'] !== $employee->email) {
            $exists = User::withoutGlobalScopes()
                ->where('company_id', $user->company_id)
                ->where('email', $data['email'])
                ->where('id', '!=', $employee->id)
                ->exists();

            if ($exists) {
                return back()->withErrors(['email' => 'Cette adresse email est déjà utilisée.']);
            }
        }

        $employee->update($data);

        return back()->with('success', 'Profil mis à jour.');
    }

    // ─── Activer / Désactiver ────────────────────────────────────────────────────

    public function toggleActive(Request $request, User $employee): RedirectResponse
    {
        $user = $request->user();
        abort_unless($employee->company_id === $user->company_id, 403);
        abort_unless($user->isAdmin(), 403);
        abort_if($employee->id === $user->id, 403);

        $employee->update(['is_active' => ! $employee->is_active]);

        $status = $employee->is_active ? 'activé(e)' : 'désactivé(e)';

        return back()->with('success', "{$employee->full_name} {$status}.");
    }

    // ─── Réinitialiser le mot de passe ───────────────────────────────────────────

    public function resetPassword(Request $request, User $employee): RedirectResponse
    {
        $user = $request->user();
        abort_unless($employee->company_id === $user->company_id, 403);
        abort_unless($user->isAdmin(), 403);

        Password::sendResetLink(['email' => $employee->email]);

        return back()->with('success', "Un email de réinitialisation a été envoyé à {$employee->email}.");
    }

    // ─── Page import CSV ─────────────────────────────────────────────────────────

    public function importPage(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->isAdmin(), 403);

        $departments = Department::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Employees/Import', [
            'departments' => $departments,
            'result'      => session('import_result'),
        ]);
    }

    // ─── Confirmer import CSV ────────────────────────────────────────────────────

    /**
     * Reçoit un tableau JSON de lignes validées côté client,
     * crée les comptes et envoie les emails d'activation.
     */
    public function importConfirm(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin(), 403);

        $request->validate([
            'employees'                      => ['required', 'array', 'min:1', 'max:500'],
            'employees.*.first_name'         => ['required', 'string', 'max:255'],
            'employees.*.last_name'          => ['required', 'string', 'max:255'],
            'employees.*.email'              => ['required', 'email', 'max:255'],
            'employees.*.role'               => ['required', 'string', 'in:admin,manager,employee'],
            'employees.*.contract_type'      => ['required', 'string', 'in:cdi,cdd,interim,stage,alternance'],
            'employees.*.hire_date'          => ['nullable', 'date'],
            'employees.*.weekly_hours'       => ['nullable', 'numeric', 'min:1', 'max:60'],
            'employees.*.phone'              => ['nullable', 'string', 'max:20'],
            'employees.*.employee_id'        => ['nullable', 'string', 'max:50'],
            'employees.*.department_id'      => ['nullable', 'integer'],
        ]);

        $rows     = $request->input('employees');
        $created  = 0;
        $skipped  = 0;

        // Pré-charge les emails existants pour comparaison rapide
        $existingEmails = User::withoutGlobalScopes()
            ->where('company_id', $user->company_id)
            ->pluck('email')
            ->map(fn ($e) => mb_strtolower($e))
            ->toArray();

        DB::transaction(function () use ($rows, $user, $existingEmails, &$created, &$skipped): void {
            foreach ($rows as $row) {
                if (in_array(mb_strtolower($row['email']), $existingEmails, true)) {
                    $skipped++;
                    continue;
                }

                $employee = User::create([
                    'company_id'        => $user->company_id,
                    'first_name'        => $row['first_name'],
                    'last_name'         => $row['last_name'],
                    'email'             => $row['email'],
                    'password'          => Hash::make(Str::password(20)),
                    'role'              => $row['role']          ?? 'employee',
                    'contract_type'     => $row['contract_type'] ?? 'cdi',
                    'department_id'     => $row['department_id'] ?? null,
                    'hire_date'         => $row['hire_date']     ?? null,
                    'weekly_hours'      => $row['weekly_hours']  ?? 35,
                    'phone'             => $row['phone']         ?? null,
                    'employee_id'       => $row['employee_id']   ?? null,
                    'email_verified_at' => now(),
                    'is_active'         => true,
                ]);

                // Envoie l'email d'activation (set password)
                Password::sendResetLink(['email' => $employee->email]);

                $created++;
            }
        });

        $msg = "{$created} collaborateur(s) importé(s) avec succès.";
        if ($skipped > 0) {
            $msg .= " {$skipped} ignoré(s) (email déjà existant).";
        }

        return redirect()->route('employees.import')->with('import_result', [
            'created' => $created,
            'skipped' => $skipped,
            'message' => $msg,
        ]);
    }

    // ─── Sérialisation ──────────────────────────────────────────────────────────

    private function serializeEmployee(User $u): array
    {
        return [
            'id'              => $u->id,
            'full_name'       => $u->full_name,
            'first_name'      => $u->first_name,
            'last_name'       => $u->last_name,
            'email'           => $u->email,
            'phone'           => $u->phone,
            'role'            => $u->role?->value,
            'role_label'      => $u->role?->label(),
            'initials'        => $u->initials,
            'avatar_url'      => $u->avatar_url,
            'department_id'   => $u->department_id,
            'department_name' => $u->department?->name,
            'contract_type'   => $u->contract_type?->value,
            'contract_label'  => $u->contract_type?->label(),
            'hire_date'       => $u->hire_date?->translatedFormat('j M Y'),
            'weekly_hours'    => $u->weekly_hours,
            'employee_id'     => $u->employee_id,
            'is_active'       => $u->is_active,
            'last_login_at'   => $u->last_login_at?->diffForHumans(),
            'show_url'        => route('employees.show', $u->id),
        ];
    }

    private function serializeEmployeeFull(User $u): array
    {
        return [
            ...$this->serializeEmployee($u),
            'birth_date'              => $u->birth_date?->format('Y-m-d'),
            'address'                 => $u->address,
            'city'                    => $u->city,
            'postal_code'             => $u->postal_code,
            'hire_date_raw'           => $u->hire_date?->format('Y-m-d'),
            'contract_end_date'       => $u->contract_end_date?->format('Y-m-d'),
            'trial_end_date'          => $u->trial_end_date?->format('Y-m-d'),
            'manager_id'              => $u->manager_id,
            'manager_name'            => $u->manager?->full_name,
            'emergency_contact_name'  => $u->emergency_contact_name,
            'emergency_contact_phone' => $u->emergency_contact_phone,
        ];
    }
}
