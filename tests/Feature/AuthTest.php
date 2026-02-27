<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Hash;

// ═══════════════════════════════════════════════════════════════════════════════
// INSCRIPTION
// ═══════════════════════════════════════════════════════════════════════════════

describe('Inscription', function () {

    it('affiche la page d\'inscription', function () {
        $this->get('/register')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Auth/Register'));
    });

    it('crée une entreprise et un admin lors de l\'inscription', function () {
        $this->post('/register', [
            'company_name'          => 'Acme SAS',
            'first_name'            => 'Jean',
            'last_name'             => 'Dupont',
            'email'                 => 'jean@acme.fr',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms'                 => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('companies', ['name' => 'Acme SAS']);
        $this->assertDatabaseHas('users', [
            'email' => 'jean@acme.fr',
            'role'  => UserRole::Admin->value,
        ]);

        // L'utilisateur est connecté après inscription
        $this->assertAuthenticated();
    });

    it('crée l\'admin avec le rôle admin et l\'email vérifié', function () {
        $this->post('/register', [
            'company_name'          => 'Tech Corp',
            'first_name'            => 'Marie',
            'last_name'             => 'Martin',
            'email'                 => 'marie@tech.fr',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms'                 => true,
        ]);

        $user = User::where('email', 'marie@tech.fr')->first();

        expect($user->role)->toBe(UserRole::Admin)
            ->and($user->email_verified_at)->not->toBeNull()
            ->and($user->is_active)->toBeTrue();
    });

    it('refuse l\'inscription si l\'email existe déjà', function () {
        User::factory()->admin()->create([
            'email'      => 'existing@test.fr',
            'company_id' => Company::factory()->create()->id,
        ]);

        $this->post('/register', [
            'company_name'          => 'Autre Société',
            'first_name'            => 'Paul',
            'last_name'             => 'Martin',
            'email'                 => 'existing@test.fr',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms'                 => true,
        ])->assertInvalid(['email']);

        $this->assertGuest();
    });

    it('refuse l\'inscription sans accepter les CGU', function () {
        $this->post('/register', [
            'company_name'          => 'Acme SAS',
            'first_name'            => 'Jean',
            'last_name'             => 'Dupont',
            'email'                 => 'jean@acme.fr',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms'                 => false,
        ])->assertInvalid(['terms']);
    });

    it('refuse un mot de passe trop court', function () {
        $this->post('/register', [
            'company_name'          => 'Acme SAS',
            'first_name'            => 'Jean',
            'last_name'             => 'Dupont',
            'email'                 => 'jean@acme.fr',
            'password'              => 'short',
            'password_confirmation' => 'short',
            'terms'                 => true,
        ])->assertInvalid(['password']);
    });

});

// ═══════════════════════════════════════════════════════════════════════════════
// CONNEXION
// ═══════════════════════════════════════════════════════════════════════════════

describe('Connexion', function () {

    it('affiche la page de connexion', function () {
        $this->get('/login')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Auth/Login'));
    });

    it('connecte un utilisateur avec des identifiants valides', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $this->post('/login', [
            'email'    => $admin->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($admin);
    });

    it('refuse la connexion avec un mauvais mot de passe', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $this->post('/login', [
            'email'    => $admin->email,
            'password' => 'mauvais-mot-de-passe',
        ])->assertInvalid(['email']);

        $this->assertGuest();
    });

    it('refuse la connexion avec un email inexistant', function () {
        $this->post('/login', [
            'email'    => 'inexistant@test.fr',
            'password' => 'password',
        ])->assertInvalid(['email']);

        $this->assertGuest();
    });

    it('redirige vers le dashboard après connexion', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $this->post('/login', [
            'email'    => $admin->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');
    });

});

// ═══════════════════════════════════════════════════════════════════════════════
// DÉCONNEXION
// ═══════════════════════════════════════════════════════════════════════════════

describe('Déconnexion', function () {

    it('déconnecte l\'utilisateur', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $this->actingAs($admin)
            ->post('/logout')
            ->assertRedirect();

        $this->assertGuest();
    });

});

// ═══════════════════════════════════════════════════════════════════════════════
// PROTECTION DES ROUTES
// ═══════════════════════════════════════════════════════════════════════════════

describe('Protection des routes', function () {

    it('redirige vers /login si non authentifié', function () {
        $this->get('/dashboard')
            ->assertRedirect('/login');
    });

    it('permet l\'accès au dashboard si authentifié', function () {
        [$company, $admin] = createCompanyWithAdmin();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertStatus(200);
    });

    it('interdit l\'accès aux routes admin pour un employé', function () {
        [$company, $admin] = createCompanyWithAdmin();
        $employee = createEmployee($company);

        // Un employé ne peut pas accéder à la page de création d'employé
        $this->actingAs($employee)
            ->post('/employees', [
                'first_name' => 'Test',
                'last_name'  => 'User',
                'email'      => 'test@test.fr',
                'role'       => 'employee',
            ])->assertStatus(403);
    });

});
