<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\CompanyPlan;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirige vers l'authentification Google.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Callback Google OAuth.
     * - Utilisateur existant → connexion directe
     * - Nouvel utilisateur → stocke les données en session et redirige vers completion
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        // Cherche un utilisateur avec ce google_id ou cet email
        $user = User::withoutGlobalScopes()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            // Met à jour le google_id si ce n'est pas encore fait
            if (! $user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }

            Auth::login($user, true);
            $user->update(['last_login_at' => now()]);

            return redirect()->intended('/dashboard');
        }

        // Nouvel utilisateur — stocker les données Google en session
        session(['google_pending' => [
            'google_id'  => $googleUser->getId(),
            'first_name' => $googleUser->user['given_name']  ?? explode(' ', $googleUser->getName())[0] ?? '',
            'last_name'  => $googleUser->user['family_name'] ?? explode(' ', $googleUser->getName())[1] ?? '',
            'email'      => $googleUser->getEmail(),
            'avatar'     => $googleUser->getAvatar(),
        ]]);

        return redirect()->route('auth.google.complete');
    }

    /**
     * Page de complétion pour les nouveaux utilisateurs Google.
     * Ils doivent saisir le nom de leur entreprise.
     */
    public function showComplete(): Response
    {
        abort_unless(session()->has('google_pending'), 404);

        return Inertia::render('Auth/GoogleComplete', [
            'google_user' => session('google_pending'),
        ]);
    }

    /**
     * Finalise l'inscription via Google (création company + user).
     */
    public function complete(Request $request): RedirectResponse
    {
        abort_unless(session()->has('google_pending'), 404);

        $googleData = session('google_pending');

        $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'terms'        => ['accepted'],
        ], [
            'company_name.required' => 'Le nom de votre entreprise est requis.',
            'terms.accepted'        => 'Vous devez accepter les conditions d\'utilisation.',
        ]);

        $user = DB::transaction(function () use ($request, $googleData): User {
            $company = Company::create([
                'name'          => $request->input('company_name'),
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

            return User::create([
                'company_id'        => $company->id,
                'first_name'        => $googleData['first_name'],
                'last_name'         => $googleData['last_name'],
                'email'             => $googleData['email'],
                'password'          => Hash::make(Str::random(32)), // mot de passe aléatoire inutilisable
                'google_id'         => $googleData['google_id'],
                'role'              => UserRole::Admin->value,
                'email_verified_at' => now(),
                'is_active'         => true,
                'hire_date'         => now()->toDateString(),
            ]);
        });

        session()->forget('google_pending');

        Auth::login($user, true);

        return redirect('/dashboard')->with('success', 'Bienvenue sur SimpliRH, ' . $user->first_name . ' !');
    }
}
