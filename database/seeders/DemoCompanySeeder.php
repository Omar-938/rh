<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Department;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Crée un environnement de démonstration complet :
 * - 1 company "Démo SAS"
 * - 3 départements
 * - 1 admin + 2 managers + 5 employés
 * - Types de congés par défaut
 * - Soldes de congés pour l'année en cours
 */
class DemoCompanySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // ── 1. Entreprise démo ──────────────────────────────────────────────
            /** @var Company $company */
            $company = Company::withoutGlobalScopes()->create([
                'name'          => 'Démo SAS',
                'slug'          => 'demo-sas',
                'siret'         => '12345678901234',
                'address'       => '12 rue de la Paix',
                'city'          => 'Paris',
                'postal_code'   => '75001',
                'phone'         => '01 23 45 67 89',
                'plan'          => 'business',
                'trial_ends_at' => null,
                'settings'      => [
                    'timezone'                  => 'Europe/Paris',
                    'work_hours_per_day'        => 7,
                    'work_days_per_week'        => 5,
                    'overtime_auto_detect'      => true,
                    'overtime_threshold_alert'  => 10,
                    'overtime_rate_25'          => 25,
                    'overtime_rate_50'          => 50,
                    'overtime_annual_quota'     => 220,
                    'leave_carryover'           => false,
                    'leave_carryover_max_days'  => 0,
                    'payroll_reminder_day'      => 3,
                    'accountant_emails'         => ['comptable@demo-sas.fr'],
                    'payroll_export_format'     => 'pdf',
                ],
            ]);

            // ── 2. Admin RH ────────────────────────────────────────────────────
            /** @var User $admin */
            $admin = User::withoutGlobalScopes()->create([
                'company_id'        => $company->id,
                'first_name'        => 'Sophie',
                'last_name'         => 'Martin',
                'email'             => 'admin@demo.simplirh.fr',
                'password'          => Hash::make('password'),
                'role'              => UserRole::Admin,
                'email_verified_at' => now(),
                'hire_date'         => '2020-01-06',
                'contract_type'     => 'cdi',
                'weekly_hours'      => 35.0,
                'is_active'         => true,
            ]);

            // ── 3. Départements ────────────────────────────────────────────────
            $deptIT = Department::withoutGlobalScopes()->create([
                'company_id'  => $company->id,
                'name'        => 'Informatique',
                'color'       => '#2E86C1',
                'description' => 'Développement et infrastructure IT',
            ]);

            $deptRH = Department::withoutGlobalScopes()->create([
                'company_id'  => $company->id,
                'name'        => 'Ressources Humaines',
                'color'       => '#9B59B6',
                'description' => 'Gestion du personnel et recrutement',
            ]);

            $deptComm = Department::withoutGlobalScopes()->create([
                'company_id'  => $company->id,
                'name'        => 'Commercial',
                'color'       => '#27AE60',
                'description' => 'Ventes et développement client',
            ]);

            // Rattacher l'admin au département RH
            $admin->update(['department_id' => $deptRH->id]);

            // ── 4. Managers ────────────────────────────────────────────────────
            $managerIT = User::withoutGlobalScopes()->create([
                'company_id'        => $company->id,
                'department_id'     => $deptIT->id,
                'first_name'        => 'Thomas',
                'last_name'         => 'Dupont',
                'email'             => 'manager.it@demo.simplirh.fr',
                'password'          => Hash::make('password'),
                'role'              => UserRole::Manager,
                'email_verified_at' => now(),
                'hire_date'         => '2019-03-01',
                'contract_type'     => 'cdi',
                'weekly_hours'      => 39.0,
                'is_active'         => true,
            ]);

            $managerComm = User::withoutGlobalScopes()->create([
                'company_id'        => $company->id,
                'department_id'     => $deptComm->id,
                'first_name'        => 'Isabelle',
                'last_name'         => 'Leroy',
                'email'             => 'manager.comm@demo.simplirh.fr',
                'password'          => Hash::make('password'),
                'role'              => UserRole::Manager,
                'email_verified_at' => now(),
                'hire_date'         => '2018-09-01',
                'contract_type'     => 'cdi',
                'weekly_hours'      => 35.0,
                'is_active'         => true,
            ]);

            // Assigner les managers aux départements
            $deptIT->update(['manager_id' => $managerIT->id]);
            $deptComm->update(['manager_id' => $managerComm->id]);
            $deptRH->update(['manager_id' => $admin->id]);

            // ── 5. Employés ────────────────────────────────────────────────────
            $employees = [
                [
                    'first_name'    => 'Lucas',
                    'last_name'     => 'Bernard',
                    'email'         => 'lucas.bernard@demo.simplirh.fr',
                    'department_id' => $deptIT->id,
                    'manager_id'    => $managerIT->id,
                    'hire_date'     => '2021-05-03',
                    'weekly_hours'  => 39.0,
                ],
                [
                    'first_name'    => 'Emma',
                    'last_name'     => 'Petit',
                    'email'         => 'emma.petit@demo.simplirh.fr',
                    'department_id' => $deptIT->id,
                    'manager_id'    => $managerIT->id,
                    'hire_date'     => '2022-01-10',
                    'weekly_hours'  => 35.0,
                ],
                [
                    'first_name'    => 'Hugo',
                    'last_name'     => 'Moreau',
                    'email'         => 'hugo.moreau@demo.simplirh.fr',
                    'department_id' => $deptComm->id,
                    'manager_id'    => $managerComm->id,
                    'hire_date'     => '2020-07-01',
                    'weekly_hours'  => 35.0,
                ],
                [
                    'first_name'    => 'Chloé',
                    'last_name'     => 'Simon',
                    'email'         => 'chloe.simon@demo.simplirh.fr',
                    'department_id' => $deptComm->id,
                    'manager_id'    => $managerComm->id,
                    'hire_date'     => '2023-02-13',
                    'weekly_hours'  => 28.0,
                ],
                [
                    'first_name'    => 'Nathan',
                    'last_name'     => 'Roux',
                    'email'         => 'nathan.roux@demo.simplirh.fr',
                    'department_id' => $deptRH->id,
                    'manager_id'    => $admin->id,
                    'hire_date'     => '2024-09-02',
                    'weekly_hours'  => 35.0,
                    'contract_type' => 'alternance',
                ],
            ];

            $createdEmployees = [];
            foreach ($employees as $data) {
                $createdEmployees[] = User::withoutGlobalScopes()->create([
                    'company_id'        => $company->id,
                    'role'              => UserRole::Employee,
                    'email_verified_at' => now(),
                    'password'          => Hash::make('password'),
                    'is_active'         => true,
                    'contract_type'     => $data['contract_type'] ?? 'cdi',
                    ...$data,
                ]);
            }

            // ── 6. Types de congés ─────────────────────────────────────────────
            DefaultLeaveTypesSeeder::seedForCompany($company);

            // ── 7. Soldes de congés (année en cours) ───────────────────────────
            $currentYear = now()->year;
            $leaveTypes  = LeaveType::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->get();

            $allUsers = collect([$admin, $managerIT, $managerComm, ...$createdEmployees]);

            foreach ($allUsers as $user) {
                foreach ($leaveTypes as $lt) {
                    // Calcule les jours acquis depuis la date d'embauche
                    $hireDate    = Carbon::parse($user->hire_date ?? now()->startOfYear());
                    $startOfYear = Carbon::create($currentYear, 1, 1);
                    $monthsWorked = max(0, $startOfYear->diffInMonths(
                        $hireDate->gt($startOfYear) ? $hireDate : $startOfYear
                    ));

                    $allocated = match($lt->acquisition_type->value) {
                        'monthly' => round(($lt->days_per_year / 12) * (12 - $monthsWorked), 2),
                        'annual'  => $hireDate->year <= $currentYear ? $lt->days_per_year : 0,
                        'none'    => 0,
                    };

                    // Simule quelques jours utilisés pour la démo
                    $used = $lt->acquisition_type->value !== 'none'
                        ? round(fake()->randomFloat(2, 0, min($allocated * 0.6, 10)), 2)
                        : 0;

                    LeaveBalance::withoutGlobalScopes()->create([
                        'user_id'       => $user->id,
                        'leave_type_id' => $lt->id,
                        'company_id'    => $company->id,
                        'year'          => $currentYear,
                        'allocated'     => $allocated,
                        'used'          => $used,
                        'pending'       => 0,
                        'carried_over'  => 0,
                        'adjustment'    => 0,
                    ]);
                }
            }
        });

        $this->command?->info('✅ DemoCompanySeeder : environnement de démonstration créé (admin@demo.simplirh.fr / password)');
    }
}
