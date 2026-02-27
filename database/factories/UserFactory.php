<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContractType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'company_id'               => Company::factory(),
            'department_id'            => null,
            'manager_id'               => null,
            'first_name'               => fake()->firstName(),
            'last_name'                => fake()->lastName(),
            'email'                    => fake()->unique()->safeEmail(),
            'email_verified_at'        => now(),
            'password'                 => static::$password ??= Hash::make('password'),
            'remember_token'           => Str::random(10),
            'role'                     => UserRole::Employee->value,
            'phone'                    => fake()->optional(0.7)->phoneNumber(),
            'avatar_path'              => null,
            'hire_date'                => fake()->dateTimeBetween('-5 years', '-1 month')->format('Y-m-d'),
            'contract_type'            => ContractType::CDI->value,
            'contract_end_date'        => null,
            'trial_end_date'           => null,
            'weekly_hours'             => 35.0,
            'employee_id'              => fake()->optional(0.5)->numerify('EMP-####'),
            'birth_date'               => fake()->optional(0.8)->dateTimeBetween('-60 years', '-20 years')?->format('Y-m-d'),
            'address'                  => fake()->optional(0.6)->streetAddress(),
            'city'                     => fake()->optional(0.6)->city(),
            'postal_code'              => fake()->optional(0.6)->postcode(),
            'social_security_number'   => null,
            'iban'                     => null,
            'emergency_contact_name'   => null,
            'emergency_contact_phone'  => null,
            'is_active'                => true,
            'last_login_at'            => fake()->optional(0.8)->dateTimeBetween('-30 days'),
        ];
    }

    // -------------------------------------------------------------------------
    // États
    // -------------------------------------------------------------------------

    /** Administrateur RH. */
    public function admin(): static
    {
        return $this->state(['role' => UserRole::Admin->value]);
    }

    /** Manager. */
    public function manager(): static
    {
        return $this->state(['role' => UserRole::Manager->value]);
    }

    /** Employé classique (défaut). */
    public function employee(): static
    {
        return $this->state(['role' => UserRole::Employee->value]);
    }

    /** Compte désactivé. */
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /** Email non vérifié. */
    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }

    /** CDD avec date de fin. */
    public function cdd(int $months = 6): static
    {
        return $this->state([
            'contract_type'     => ContractType::CDD->value,
            'contract_end_date' => now()->addMonths($months)->format('Y-m-d'),
        ]);
    }

    /** Stage avec date de fin. */
    public function stage(int $months = 6): static
    {
        return $this->state([
            'contract_type'     => ContractType::Stage->value,
            'contract_end_date' => now()->addMonths($months)->format('Y-m-d'),
            'weekly_hours'      => 35.0,
        ]);
    }

    /** N'a jamais effectué de connexion. */
    public function neverLoggedIn(): static
    {
        return $this->state(['last_login_at' => null]);
    }
}
