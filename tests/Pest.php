<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers globaux
|--------------------------------------------------------------------------
*/

/**
 * Crée une entreprise + un admin RH prêt à l'emploi.
 */
function createCompanyWithAdmin(array $companyOverrides = [], array $userOverrides = []): array
{
    $company = Company::factory()->create($companyOverrides);
    $admin   = User::factory()->admin()->create(array_merge([
        'company_id'        => $company->id,
        'email_verified_at' => now(),
    ], $userOverrides));

    return [$company, $admin];
}

/**
 * Crée un employé dans une entreprise donnée.
 */
function createEmployee(Company $company, array $overrides = []): User
{
    return User::factory()->employee()->create(array_merge([
        'company_id'        => $company->id,
        'email_verified_at' => now(),
    ], $overrides));
}

/**
 * Crée un manager dans une entreprise donnée.
 */
function createManager(Company $company, array $overrides = []): User
{
    return User::factory()->manager()->create(array_merge([
        'company_id'        => $company->id,
        'email_verified_at' => now(),
    ], $overrides));
}
