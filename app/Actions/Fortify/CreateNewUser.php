<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Enums\CompanyPlan;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Crée une nouvelle entreprise et son administrateur RH.
     * Tout se passe dans une transaction pour garantir l'intégrité.
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'company_name' => ['required', 'string', 'max:255'],
            'first_name'   => ['required', 'string', 'max:100'],
            'last_name'    => ['required', 'string', 'max:100'],
            'email'        => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'     => ['required', 'confirmed', Password::defaults()],
            'terms'        => ['accepted'],
        ], [
            'company_name.required' => 'Le nom de votre entreprise est requis.',
            'company_name.max'      => 'Le nom de l\'entreprise ne peut pas dépasser 255 caractères.',
            'first_name.required'   => 'Votre prénom est requis.',
            'last_name.required'    => 'Votre nom est requis.',
            'email.required'        => 'L\'adresse email est requise.',
            'email.email'           => 'Veuillez saisir une adresse email valide.',
            'email.unique'          => 'Cette adresse email est déjà utilisée.',
            'password.required'     => 'Le mot de passe est requis.',
            'password.confirmed'    => 'Les mots de passe ne correspondent pas.',
            'terms.accepted'        => 'Vous devez accepter les conditions d\'utilisation.',
        ])->validate();

        return DB::transaction(function () use ($input): User {
            // 1. Créer l'entreprise avec 30 jours d'essai
            $company = Company::create([
                'name'          => $input['company_name'],
                'plan'          => CompanyPlan::Trial->value,
                'trial_ends_at' => now()->addDays(30),
                'settings'      => [
                    'timezone'                 => 'Europe/Paris',
                    'work_hours_per_day'       => 7,
                    'work_days_per_week'       => 5,
                    'overtime_auto_detect'     => true,
                    'overtime_threshold_alert' => 10,
                    'overtime_rate_25'         => 25,
                    'overtime_rate_50'         => 50,
                    'overtime_annual_quota'    => 220,
                    'leave_carryover'          => false,
                    'leave_carryover_max_days' => 0,
                    'payroll_reminder_day'     => 3,
                    'accountant_emails'        => [],
                    'payroll_export_format'    => 'pdf',
                ],
            ]);

            // 2. Créer l'administrateur RH
            $user = User::create([
                'company_id'        => $company->id,
                'first_name'        => $input['first_name'],
                'last_name'         => $input['last_name'],
                'email'             => $input['email'],
                'password'          => Hash::make($input['password']),
                'role'              => UserRole::Admin->value,
                'email_verified_at' => now(), // Admin vérifié d'emblée
                'is_active'         => true,
                'hire_date'         => now()->toDateString(),
            ]);

            return $user;
        });
    }
}
