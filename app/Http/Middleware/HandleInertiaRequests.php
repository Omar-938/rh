<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Données partagées avec toutes les pages Inertia.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),

            'auth' => fn () => [
                'user' => $user ? [
                    'id'         => $user->id,
                    'full_name'  => $user->full_name,
                    'first_name' => $user->first_name,
                    'last_name'  => $user->last_name,
                    'email'      => $user->email,
                    'role'       => $user->role?->value,
                    'initials'   => $user->initials,
                    'avatar_url' => $user->avatar_url,
                    'company_id' => $user->company_id,
                    'is_active'  => $user->is_active,
                ] : null,
                'company' => $user?->company ? [
                    'id'            => $user->company->id,
                    'name'          => $user->company->name,
                    'logo_url'      => $user->company->logo_url,
                    'primary_color' => $user->company->primary_color,
                    'plan'          => $user->company->plan?->value,
                    'trial_ends_at' => $user->company->trial_ends_at?->toDateString(),
                    'trial_expired' => $user->company->trial_expired,
                ] : null,
            ],

            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'info'    => $request->session()->get('info'),
            ],

            // Compteur de notifications non lues (lazy — calculé à chaque requête)
            'unread_notifications_count' => fn () => $user
                ? $user->unreadNotifications()->count()
                : 0,

            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
