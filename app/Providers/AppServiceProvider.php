<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Company;
use App\Models\Schedule;
use App\Policies\PlanningPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Stripe Cashier : utilise Company comme modèle facturable
        Cashier::useCustomerModel(Company::class);

        // Forcer HTTPS en production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Policy : PlanningPolicy couvre le modèle Schedule
        Gate::policy(Schedule::class, PlanningPolicy::class);

        // Règles de mot de passe globales
        Password::defaults(function () {
            return Password::min(8)
                ->letters()
                ->numbers();
        });
    }
}
